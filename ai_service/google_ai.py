"""Извлечение блока «Обзор от ИИ» (AI Overview) из выдачи Google.

Использует Playwright + playwright-stealth (v2 API: `Stealth().use_async`).
Браузер поднимается один раз на весь жизненный цикл сервиса, на каждый
запрос создаётся отдельный контекст (чистые cookie/локальное хранилище).
"""
from __future__ import annotations

import asyncio
import random
import time
from pathlib import Path
from urllib.parse import quote_plus

from loguru import logger
from playwright.async_api import Page, async_playwright
from playwright_stealth import Stealth

from .config import Settings
from .duckduckgo_ai import fetch_duckduckgo_answer


class AIOverviewNotFound(RuntimeError):
    """Обзор от ИИ не появился в выдаче (нет блока, капча, consent и т.п.)."""


class CaptchaDetected(AIOverviewNotFound):
    """Google показал капчу/анти-бот страницу — нужен ручной вход в профиль.

    Отдельный подкласс, чтобы воркер мог отличить капчу (нужно действие человека —
    зайти по RDP и решить её) от обычного «обзор не отрендерился» и слать в
    Telegram именно капчу."""


# Пул реалистичных десктопных User-Agent'ов (свежие Chrome, Win/Mac).
# При human_like случайный выбирается на старте — меньше шаблонности для Google.
_UA_POOL = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36",
)
# Пул типичных десктопных разрешений.
_VIEWPORT_POOL = (
    {"width": 1366, "height": 768},
    {"width": 1440, "height": 900},
    {"width": 1536, "height": 864},
    {"width": 1600, "height": 900},
    {"width": 1920, "height": 1080},
)


# Заголовки/подписи блока «Обзор от ИИ» на разных локалях.
_OVERVIEW_HEADINGS = ["Обзор от ИИ", "AI Overview", "AI-обзор", "Обзор ИИ"]
# Кнопки раскрытия полного текста.
_EXPAND_LABELS = ["Развернуть", "Показать больше", "Show more", "Ещё"]
# Кнопки принятия cookie-согласия Google.
_CONSENT_LABELS = [
    "Принять все", "Принять всё", "Accept all", "Я согласен",
    "Принимаю", "Согласиться со всем",
]

# Служебные подписи Google, которые вырезаем из извлечённого текста.
_JUNK_MARKERS = (
    "Генеративный ИИ экспериментальный",
    "Обзор создан с помощью ИИ",
    "Оставить отзыв",
    "Показать все",
    "Развернуть",
    "Показать больше",
)

# JS: найти заголовок «Обзор от ИИ» и подняться к контейнеру с максимумом текста.
_HEADING_JS = """
(heading) => {
  const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_ELEMENT);
  let node;
  while ((node = walker.nextNode())) {
    const t = (node.textContent || '').trim();
    if (t === heading || (t.startsWith(heading) && t.length < heading.length + 3)) {
      let el = node;
      for (let i = 0; i < 6 && el.parentElement; i++) el = el.parentElement;
      return el.innerText || '';
    }
  }
  return '';
}
"""


def _clean(text: str) -> str:
    """Убрать служебные подписи Google из извлечённого текста."""
    lines: list[str] = []
    for raw in text.splitlines():
        line = raw.strip()
        if not line:
            continue
        if line in _OVERVIEW_HEADINGS:
            continue
        if any(m in line for m in _JUNK_MARKERS):
            continue
        lines.append(line)
    return "\n".join(lines).strip()


class GoogleAIClient:
    """Живёт всё время работы сервиса; браузер переиспользуется."""

    def __init__(self, settings: Settings) -> None:
        self._settings = settings
        self._stealth: Stealth | None = None
        self._pw_ctx = None
        self._pw = None
        self._context = None  # постоянный контекст (persistent profile)
        self._lock = asyncio.Lock()
        # Если задан — перекрывает s.headless (для временного headed-режима при капче).
        self._headless_override: bool | None = None

    @property
    def is_headless(self) -> bool:
        s = self._settings
        return s.headless if self._headless_override is None else self._headless_override

    async def start(self) -> None:
        s = self._settings
        headless = self.is_headless
        # Антидетект: случайные UA/viewport на старте (иначе — фиксированные из .env).
        user_agent = random.choice(_UA_POOL) if s.human_like else s.user_agent
        viewport = random.choice(_VIEWPORT_POOL) if s.human_like else {"width": 1366, "height": 900}
        self._stealth = Stealth(
            navigator_languages_override=("ru-RU", "ru"),
            navigator_user_agent_override=user_agent,
        )
        # Stealth.use_async оборачивает async_playwright() и патчит все контексты.
        self._pw_ctx = self._stealth.use_async(async_playwright())
        self._pw = await self._pw_ctx.__aenter__()

        Path(s.user_data_dir).mkdir(parents=True, exist_ok=True)
        launch_kwargs: dict = dict(
            user_data_dir=s.user_data_dir,
            headless=headless,
            locale=s.locale,
            user_agent=user_agent,
            viewport=viewport,
            args=[
                "--disable-blink-features=AutomationControlled",
                "--no-sandbox",
                "--disable-dev-shm-usage",
            ],
        )
        if s.browser_channel:
            launch_kwargs["channel"] = s.browser_channel
        try:
            self._context = await self._pw.chromium.launch_persistent_context(
                **launch_kwargs
            )
        except Exception as exc:  # канал chrome не установлен — откат на chromium
            if s.browser_channel:
                logger.warning(
                    "Канал '{}' недоступен ({}), запускаю встроенный Chromium",
                    s.browser_channel, exc,
                )
                launch_kwargs.pop("channel", None)
                self._context = await self._pw.chromium.launch_persistent_context(
                    **launch_kwargs
                )
            else:
                raise

        self._context.set_default_timeout(s.nav_timeout_ms)
        # Предустановим cookie согласия, чтобы не ловить consent-интерстишл.
        await self._context.add_cookies([
            {"name": "SOCS", "value": "CAI", "domain": ".google.com", "path": "/"},
            {"name": "CONSENT", "value": "YES+", "domain": ".google.com", "path": "/"},
        ])
        logger.info(
            "GoogleAIClient: браузер запущен (headless={}, channel={}, profile={})",
            s.headless, s.browser_channel or "chromium", s.user_data_dir,
        )

    async def stop(self) -> None:
        try:
            if self._context:
                await self._context.close()
        finally:
            if self._pw_ctx:
                await self._pw_ctx.__aexit__(None, None, None)
        logger.info("GoogleAIClient: браузер остановлен")

    async def fetch_overview(self, prompt: str) -> str:
        """Вернуть текст обзора от ИИ или бросить AIOverviewNotFound."""
        s = self._settings
        if self._context is None:
            raise RuntimeError("GoogleAIClient не запущен (нет браузера)")

        # Постоянный контекст один — сериализуем доступ.
        async with self._lock:
            page = await self._context.new_page()
            page.set_default_timeout(s.nav_timeout_ms)
            try:
                return await self._run(page, prompt)
            finally:
                await page.close()

    async def fetch_duckduckgo(self, prompt: str) -> str:
        """Резерв: получить текст из DuckDuckGo AI Chat в том же браузере."""
        if self._context is None:
            raise RuntimeError("GoogleAIClient не запущен (нет браузера)")
        s = self._settings
        async with self._lock:
            page = await self._context.new_page()
            page.set_default_timeout(s.nav_timeout_ms)
            try:
                return await fetch_duckduckgo_answer(
                    page, prompt,
                    nav_timeout_ms=s.nav_timeout_ms,
                    gen_timeout_ms=s.duckduckgo_gen_timeout_ms,
                )
            finally:
                await page.close()

    async def relaunch(self, *, headless: bool) -> None:
        """Перезапустить браузер в нужном режиме (headed для ручного решения капчи)."""
        if self.is_headless == headless and self._context is not None:
            return
        await self.stop()
        self._headless_override = headless
        await self.start()

    async def solve_captcha_interactively(
        self,
        *,
        query: str,
        on_reminder,
        poll_interval_s: float,
        reminder_interval_s: float,
        timeout_s: float,
    ) -> bool:
        """Открыть ВИДИМОЕ окно с выдачей Google и ждать, пока капчу решат руками.

        Периодически шлёт напоминание через `on_reminder` (раз в reminder_interval_s)
        и проверяет, ушла ли капча (раз в poll_interval_s). Возвращает True, если
        капча решена; False — если истёк timeout_s (0 — ждать бесконечно).
        По завершении всегда возвращает браузер в headless.
        """
        s = self._settings
        headed_ok = True
        try:
            await self.relaunch(headless=False)  # видимое окно на DISPLAY сервера
        except Exception as exc:  # noqa: BLE001 — нет дисплея и т.п.
            headed_ok = False
            logger.warning("Не удалось открыть headed-окно для капчи ({}); жду в headless", exc)
            try:
                await self.relaunch(headless=True)
            except Exception:  # noqa: BLE001
                pass

        url = f"{s.google_domain}/search?q={quote_plus(query)}&hl=ru&gl=ru&pws=0"
        start = time.monotonic()
        last_reminder = -1e9
        try:
            async with self._lock:
                if self._context is None:
                    raise RuntimeError("GoogleAIClient не запущен")
                page = await self._context.new_page()
                page.set_default_timeout(s.nav_timeout_ms)
                try:
                    await page.goto(url, wait_until="domcontentloaded", timeout=s.nav_timeout_ms)
                    while True:
                        now = time.monotonic()
                        if now - last_reminder >= reminder_interval_s:
                            last_reminder = now
                            try:
                                await on_reminder()
                            except Exception as exc:  # noqa: BLE001
                                logger.warning("Напоминание о капче не отправлено: {}", exc)
                        if not await self._is_blocked(page):
                            logger.info("Капча решена — выдача снова доступна")
                            return True
                        if timeout_s > 0 and (now - start) > timeout_s:
                            logger.warning("Тайм-аут ожидания решения капчи ({} с)", timeout_s)
                            return False
                        # В headless сами перезагружаем (человек не решает); в headed —
                        # просто ждём, Google сам редиректит после решения.
                        if not headed_ok:
                            try:
                                await page.goto(url, wait_until="domcontentloaded",
                                                timeout=s.nav_timeout_ms)
                            except Exception:  # noqa: BLE001
                                pass
                        await page.wait_for_timeout(int(poll_interval_s * 1000))
                finally:
                    await page.close()
        finally:
            try:
                await self.relaunch(headless=True)
            except Exception as exc:  # noqa: BLE001
                logger.error("Не удалось вернуть браузер в headless после капчи: {}", exc)

    async def _human_pause(self, page: Page, lo_ms: int = 350, hi_ms: int = 1400) -> None:
        """Случайная пауза «как человек» (если включён human_like)."""
        if self._settings.human_like:
            await page.wait_for_timeout(random.randint(lo_ms, hi_ms))

    async def _humanize(self, page: Page) -> None:
        """Немного подвигать мышью и поскроллить — снизить признаки автоматизации."""
        if not self._settings.human_like:
            return
        try:
            for _ in range(random.randint(2, 4)):
                await page.mouse.move(
                    random.randint(80, 1200), random.randint(80, 700),
                    steps=random.randint(3, 12),
                )
                await page.wait_for_timeout(random.randint(120, 480))
            for _ in range(random.randint(1, 3)):
                await page.mouse.wheel(0, random.randint(200, 750))
                await page.wait_for_timeout(random.randint(250, 800))
        except Exception as exc:  # noqa: BLE001 — поведение не критично
            logger.debug("humanize пропущен: {}", exc)

    async def _run(self, page: Page, prompt: str) -> str:
        s = self._settings

        # По-человечески: сперва главная, принять consent, потом поиск.
        if s.warmup_homepage:
            try:
                await page.goto(f"{s.google_domain}/", wait_until="domcontentloaded",
                                timeout=s.nav_timeout_ms)
                await self._accept_consent(page)
                await self._humanize(page)
                await self._human_pause(page, 400, 1200)
            except Exception as exc:  # noqa: BLE001
                logger.debug("Разогрев главной не удался: {}", exc)

        url = (
            f"{s.google_domain}/search?q={quote_plus(prompt)}"
            f"&hl=ru&gl=ru&pws=0"
        )
        logger.info("Открываю выдачу: {}", url)
        # Небольшая «пауза на подумать» перед переходом к поиску.
        await self._human_pause(page, 300, 900)
        await page.goto(url, wait_until="domcontentloaded", timeout=s.nav_timeout_ms)

        if await self._is_blocked(page):
            await self._dump(page, prompt)
            raise CaptchaDetected(
                "Google показал капчу/анти-бот страницу — нужен ручной вход в профиль"
            )

        await self._accept_consent(page)
        # Человекоподобно осмотреть выдачу перед извлечением обзора.
        await self._humanize(page)
        await self._maybe_expand(page)

        text = await self._extract(page, deadline_ms=s.ai_overview_timeout_ms)
        if not text:
            await self._dump(page, prompt)
            raise AIOverviewNotFound("Блок «Обзор от ИИ» не найден в выдаче")
        logger.info("Обзор от ИИ извлечён ({} симв.)", len(text))
        return text

    async def _is_blocked(self, page: Page) -> bool:
        """Определить страницу-заглушку Google (капча / «sorry»)."""
        try:
            if "/sorry/" in page.url:
                return True
            html = (await page.content()).lower()
        except Exception:
            return False
        markers = ("recaptcha", "captcha-form", "unusual traffic",
                   "our systems have detected", "необычный трафик")
        return any(m in html for m in markers)

    async def _accept_consent(self, page: Page) -> None:
        for label in _CONSENT_LABELS:
            try:
                btn = page.get_by_role("button", name=label, exact=False)
                if await btn.count() > 0:
                    await btn.first.click(timeout=3000)
                    logger.debug("Нажал согласие: {}", label)
                    await page.wait_for_load_state("domcontentloaded", timeout=8000)
                    return
            except Exception:
                continue

    async def _maybe_expand(self, page: Page) -> None:
        if not self._settings.expand_overview:
            return
        for label in _EXPAND_LABELS:
            try:
                btn = page.get_by_role("button", name=label, exact=False)
                if await btn.count() > 0:
                    await btn.first.click(timeout=3000)
                    logger.debug("Раскрыл обзор кнопкой: {}", label)
                    await page.wait_for_timeout(1200)
                    return
            except Exception:
                continue

    async def _extract(self, page: Page, deadline_ms: int) -> str:
        """Несколько стратегий поиска контейнера обзора; берём самую длинную.

        Google часто меняет разметку, поэтому не полагаемся на конкретные
        css-классы: ищем блок по заголовку «Обзор от ИИ» и по data-атрибутам,
        которые исторически помечают AI Overview.
        """
        end = time.monotonic() + deadline_ms / 1000
        best = ""
        while time.monotonic() < end:
            candidates: list[str] = []

            # 1) Data-атрибуты, которыми Google метит AI Overview.
            for sel in (
                "div[data-subtree='aif']",
                "div[data-al-subtree]",
                "div[jsname][data-mcpr]",
                "#m-x-content",
            ):
                try:
                    loc = page.locator(sel)
                    if await loc.count() > 0:
                        candidates.append((await loc.first.inner_text()).strip())
                except Exception:
                    pass

            # 2) По заголовку блока — берём текст ближайшего крупного контейнера.
            for heading in _OVERVIEW_HEADINGS:
                try:
                    txt = await page.evaluate(_HEADING_JS, heading)
                    if txt:
                        candidates.append(txt.strip())
                except Exception:
                    pass

            for c in candidates:
                cleaned = _clean(c)
                if len(cleaned) > len(best):
                    best = cleaned

            if len(best) >= 120:
                return best
            await page.wait_for_timeout(600)

        return best

    async def _dump(self, page: Page, prompt: str) -> None:
        if not self._settings.debug_dumps:
            return
        try:
            d = Path(self._settings.debug_dir)
            d.mkdir(parents=True, exist_ok=True)
            stamp = str(int(time.time()))
            await page.screenshot(path=str(d / f"{stamp}.png"), full_page=True)
            (d / f"{stamp}.html").write_text(await page.content(), encoding="utf-8")
            logger.warning("Дамп страницы сохранён в {} (prompt={!r})", d, prompt)
        except Exception as exc:  # noqa: BLE001
            logger.warning("Не удалось сохранить дамп: {}", exc)
