"""Уведомления ai_service в Telegram — переиспользуют общий нотификатор парсеров.

Единственный интересный случай — капча Google: сервис не может решить её сам,
нужен человек (зайти по RDP и один раз пройти капчу/consent в headed-профиле).
Поэтому при капче шлём в тот же телеграм-канал, что и парсеры, сообщение с
инструкцией, а когда обзор снова начинает приходить — «капча решена».

Теперь Google — не единственный источник: при капче сервис падает в резерв
(DuckDuckGo), поэтому конвейер не встаёт. Уведомление о капче нужно лишь чтобы
человек при желании починил Google (лучшее качество), поэтому шлём его не чаще
`min_interval_s` (по умолчанию раз в час) — иначе спам на каждый запрос.
"""
from __future__ import annotations

import asyncio
import time

from loguru import logger

try:  # общий нотификатор и модель события лежат в shared_storage
    from shared_storage.models import ParserNotification
    from shared_storage.telegram_notifier import TelegramErrorNotifier
except Exception as exc:  # noqa: BLE001 — телеграм не критичен для генерации
    ParserNotification = None  # type: ignore[assignment]
    TelegramErrorNotifier = None  # type: ignore[assignment]
    logger.warning("Telegram-нотификатор недоступен ({}), уведомления выключены", exc)


class CaptchaNotifier:
    """Шлёт в Telegram события капчи ai_service (с троттлингом)."""

    def __init__(self, config_path: str, min_interval_s: float = 3600.0) -> None:
        self._active = False  # сейчас капча (инцидент открыт)
        self._min_interval = min_interval_s
        self._last_sent = 0.0
        self._notifier = None
        if TelegramErrorNotifier is not None:
            try:
                self._notifier = TelegramErrorNotifier.from_config_file(config_path)
            except Exception as exc:  # noqa: BLE001
                logger.warning("Не удалось загрузить telegram-конфиг {}: {}", config_path, exc)
        if self._notifier is None:
            logger.info("Telegram-уведомления ai_service выключены (нет конфига/бота)")

    @property
    def enabled(self) -> bool:
        return self._notifier is not None and ParserNotification is not None

    async def captcha_detected(self, detail: str) -> None:
        """Уведомить о капче, но не чаще min_interval_s (иначе спам на каждый запрос)."""
        if not self.enabled:
            return
        now = time.monotonic()
        if self._active and (now - self._last_sent) < self._min_interval:
            return  # уже уведомляли недавно — молчим
        self._active = True
        self._last_sent = now
        message = (
            "ai_service: Google показал капчу — «Обзор от ИИ» не отдаётся.\n"
            "Сейчас работает резерв (DuckDuckGo), конвейер не встал. Чтобы вернуть "
            "лучшее качество Google — зайдите по RDP и один раз пройдите капчу/"
            "consent в headed-браузере (см. RDP.md; профиль запомнит cookies).\n"
            f"Детали: {detail}"
        )
        await self._send("captcha_detected", message)

    async def captcha_resolved(self) -> None:
        """Уведомить, что обзор снова приходит (только если была капча)."""
        if not self.enabled or not self._active:
            return
        self._active = False
        await self._send("captcha_resolved", "ai_service: капча решена, «Обзор от ИИ» снова приходит.")

    async def _send(self, event_type: str, message: str) -> None:
        note = ParserNotification(source="ai_service", event_type=event_type, message=message)
        note.ensure_timestamp()
        try:
            # urllib в нотификаторе синхронный — уводим в поток, чтобы не блокировать loop.
            await asyncio.to_thread(self._notifier.notify, note)
            logger.info("Telegram: отправлено событие {}", event_type)
        except Exception as exc:  # noqa: BLE001 — телеграм не должен ронять сервис
            logger.warning("Не удалось отправить telegram-уведомление {}: {}", event_type, exc)
