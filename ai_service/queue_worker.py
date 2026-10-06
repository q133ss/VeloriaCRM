"""Очередь задач с единственным последовательным воркером.

Каждый HTTP-запрос кладёт задачу в asyncio.Queue и ждёт результат через
future. Воркер получает обзор от ИИ из Google; при неудаче делает несколько
повторных попыток (ретраи) с нарастающей паузой. Внешнего fallback нет.
Браузер используется строго по одному запросу за раз.
"""
from __future__ import annotations

import asyncio
import time
from dataclasses import dataclass, field

from loguru import logger

from .config import Settings
from .google_ai import AIOverviewNotFound, CaptchaDetected, GoogleAIClient
from .models import GenerateResponse
from .notifier import CaptchaNotifier


@dataclass
class _Job:
    prompt: str
    future: asyncio.Future = field()


class GenerationQueue:
    def __init__(self, settings: Settings) -> None:
        self._s = settings
        self._queue: asyncio.Queue[_Job] = asyncio.Queue(maxsize=settings.queue_max_size)
        self._google = GoogleAIClient(settings)
        self._worker_task: asyncio.Task | None = None
        self._notifier = CaptchaNotifier(
            settings.telegram_config_path, settings.captcha_reminder_interval_s
        )
        self._recovering = False  # идёт ли сейчас восстановление после капчи

    async def start(self) -> None:
        await self._google.start()
        self._worker_task = asyncio.create_task(self._worker(), name="ai-worker")
        logger.info("Очередь запущена (max={})", self._s.queue_max_size)

    async def stop(self) -> None:
        if self._worker_task:
            self._worker_task.cancel()
            try:
                await self._worker_task
            except asyncio.CancelledError:
                pass
        await self._google.stop()
        logger.info("Очередь остановлена")

    @property
    def depth(self) -> int:
        return self._queue.qsize()

    async def submit(self, prompt: str) -> GenerateResponse:
        loop = asyncio.get_running_loop()
        job = _Job(prompt=prompt, future=loop.create_future())
        try:
            self._queue.put_nowait(job)
        except asyncio.QueueFull as exc:
            raise RuntimeError("Очередь переполнена, попробуйте позже") from exc

        return await asyncio.wait_for(job.future, timeout=self._s.request_timeout_s)

    async def _worker(self) -> None:
        while True:
            job = await self._queue.get()
            try:
                result = await self._process(job.prompt)
                if not job.future.done():
                    job.future.set_result(result)
            except Exception as exc:  # noqa: BLE001
                if not job.future.done():
                    job.future.set_exception(exc)
            finally:
                self._queue.task_done()

    async def _process(self, prompt: str) -> GenerateResponse:
        """Получить обзор от ИИ с несколькими попытками (ретраями)."""
        started = time.monotonic()
        s = self._s
        last_exc: Exception | None = None
        saw_captcha = False

        # Google-путь можно выключить целиком (вторая «лань» на DuckDuckGo).
        google_attempts = s.overview_max_retries if s.google_enabled else 0
        for attempt in range(1, google_attempts + 1):
            try:
                text = await self._google.fetch_overview(prompt)
                if attempt > 1:
                    logger.info("Обзор от ИИ получен с {}-й попытки", attempt)
                # Обзор пришёл — если раньше была капча, сообщим, что решена.
                await self._notifier.captcha_resolved()
                return GenerateResponse(
                    text=text,
                    source="google_ai_overview",
                    elapsed_ms=int((time.monotonic() - started) * 1000),
                    attempts=attempt,
                )
            except CaptchaDetected as exc:
                last_exc = exc
                saw_captcha = True
                logger.warning(
                    "Капча Google (попытка {}/{}): {}",
                    attempt, s.overview_max_retries, exc,
                )
            except AIOverviewNotFound as exc:
                last_exc = exc
                logger.warning(
                    "Обзор от ИИ не получен (попытка {}/{}): {}",
                    attempt, s.overview_max_retries, exc,
                )
            except Exception as exc:  # noqa: BLE001
                last_exc = exc
                logger.warning(
                    "Ошибка Google-пути (попытка {}/{}): {}",
                    attempt, s.overview_max_retries, exc,
                )
            if attempt < s.overview_max_retries:
                await asyncio.sleep(s.overview_retry_delay_s * attempt)

        # Google не смог. Если это капча — уведомим (не чаще раза в час), чтобы
        # человек при желании починил Google; конвейер при этом НЕ ждём.
        if saw_captcha:
            await self._notifier.captcha_detected(str(last_exc))

        # Резерв: DuckDuckGo AI Chat (без логина/капчи) — чтобы конвейер не вставал.
        if s.duckduckgo_enabled:
            try:
                text = await self._google.fetch_duckduckgo(prompt)
                logger.info("Ответ получен из резерва DuckDuckGo")
                return GenerateResponse(
                    text=text,
                    source="duckduckgo",
                    elapsed_ms=int((time.monotonic() - started) * 1000),
                    attempts=s.overview_max_retries + 1,
                )
            except Exception as exc:  # noqa: BLE001
                last_exc = exc
                logger.warning("Резерв DuckDuckGo тоже не смог: {}", exc)

        # Резерва нет/не сработал, а это капча — как крайний случай открываем окно
        # на сервере и ждём ручного решения (старое поведение, если DDG выключен).
        if (
            saw_captcha
            and not s.duckduckgo_enabled
            and s.captcha_recovery_enabled
            and not self._recovering
        ):
            solved = await self._recover_captcha(str(last_exc))
            if solved:
                try:
                    text = await self._google.fetch_overview(prompt)
                    await self._notifier.captcha_resolved()
                    return GenerateResponse(
                        text=text,
                        source="google_ai_overview",
                        elapsed_ms=int((time.monotonic() - started) * 1000),
                        attempts=s.overview_max_retries + 1,
                    )
                except Exception as exc:  # noqa: BLE001
                    last_exc = exc

        raise RuntimeError(
            f"Не удалось получить текст ни из Google, ни из DuckDuckGo: {last_exc}"
        )

    async def _recover_captcha(self, detail: str) -> bool:
        """Открыть окно на сервере, слать Telegram раз в час и ждать решения капчи."""
        s = self._s
        self._recovering = True
        logger.warning("Капча: открываю окно на сервере и жду ручного решения")

        async def _remind() -> None:
            await self._notifier.captcha_detected(detail)

        try:
            return await self._google.solve_captcha_interactively(
                query=s.captcha_probe_query,
                on_reminder=_remind,
                poll_interval_s=s.captcha_poll_interval_s,
                reminder_interval_s=s.captcha_reminder_interval_s,
                timeout_s=s.captcha_recovery_timeout_s,
            )
        except Exception as exc:  # noqa: BLE001
            logger.error("Сбой авто-восстановления после капчи: {}", exc)
            return False
        finally:
            self._recovering = False
