"""Конфигурация микросервиса ai_service.

Все значения читаются из переменных окружения / файла .env.
Источник ответа один — блок «Обзор от ИИ» (AI Overview) из выдачи Google;
при неудаче делаем несколько повторных попыток (ретраи), без внешнего fallback.
"""
from __future__ import annotations

from pathlib import Path

from pydantic_settings import BaseSettings, SettingsConfigDict

BASE_DIR = Path(__file__).resolve().parent


class Settings(BaseSettings):
    model_config = SettingsConfigDict(
        env_file=str(BASE_DIR / ".env"),
        env_file_encoding="utf-8",
        extra="ignore",
    )

    # --- HTTP-сервер ---
    host: str = "0.0.0.0"
    port: int = 8100

    # --- Очередь ---
    # Одновременно обрабатывается ровно один запрос (последовательный воркер),
    # чтобы не плодить браузерные сессии и не ловить бан Google.
    queue_max_size: int = 100
    request_timeout_s: float = 120.0

    # --- Playwright / Google ---
    # Если False — Google-путь (обзор от ИИ) пропускается целиком и запрос сразу
    # идёт в DuckDuckGo. Используется для второй параллельной «лани» (инстанс на
    # :8101), чтобы она НЕ дёргала Google с того же IP (иначе растёт риск капчи).
    google_enabled: bool = True
    headless: bool = True
    locale: str = "ru-RU"
    google_domain: str = "https://www.google.com"
    nav_timeout_ms: int = 30_000
    ai_overview_timeout_ms: int = 20_000
    # Клик по «Развернуть», чтобы раскрыть полный текст обзора.
    expand_overview: bool = True
    # Постоянный профиль браузера: сюда сохраняются cookie/consent, чтобы
    # не проходить капчу и согласие Google на каждый запрос. Если капчу один
    # раз решить руками в headed-режиме, дальше выдача открывается сама.
    user_data_dir: str = str(BASE_DIR / "profile")
    # channel="chrome" использует установленный Google Chrome (менее детектится,
    # чем встроенный Chromium). Пусто — встроенный Chromium.
    browser_channel: str = "chrome"
    # Зайти на главную google.com и принять consent перед поиском (по-человечески).
    warmup_homepage: bool = True
    user_agent: str = (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
        "(KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36"
    )
    # Каталог для отладочных дампов (скриншот + html), когда обзор не найден.
    debug_dumps: bool = False
    debug_dir: str = str(BASE_DIR / "debug")

    # --- Антидетект (чтобы Google не палил робота) ---
    # Человекоподобное поведение: случайные паузы, движения мыши, скролл,
    # вариативные User-Agent и viewport (выбираются случайно при запуске).
    human_like: bool = True

    # --- Ретраи получения «Обзора от ИИ» ---
    # Сколько раз пытаться получить обзор, прежде чем вернуть ошибку (502).
    # Помогает пережить моменты, когда обзор не отрендерился/выдача моргнула.
    overview_max_retries: int = 3
    # Базовая пауза между попытками, сек. Растёт линейно: delay * attempt
    # (даём выдаче и анти-боту Google передохнуть).
    overview_retry_delay_s: float = 5.0

    # --- Telegram-уведомления о капче ---
    # При капче сервис шлёт в тот же телеграм-канал, что и парсеры (общий
    # конфиг shared_storage/telegram_config.toml), чтобы человек зашёл по RDP
    # и решил капчу вручную. Пустой путь/нет конфига — уведомления выключены.
    telegram_config_path: str = str(BASE_DIR.parent / "shared_storage" / "telegram_config.toml")

    # --- Авто-восстановление при капче ---
    # При капче сервис сам открывает ВИДИМОЕ окно браузера на рабочем столе
    # сервера (DISPLAY), шлёт напоминание в Telegram раз в час и ЖДЁТ, пока
    # капчу решат руками; затем продолжает работу в headless.
    captcha_recovery_enabled: bool = True
    # Напоминание в Telegram раз в N секунд, пока капча не решена (по ТЗ — раз в час).
    captcha_reminder_interval_s: float = 3600.0
    # Как часто проверять, решена ли капча, сек.
    captcha_poll_interval_s: float = 15.0
    # Сколько ждать решения максимум, сек (0 — ждать бесконечно).
    captcha_recovery_timeout_s: float = 0.0
    # Запрос, который открываем в окне для решения капчи (нейтральный).
    captcha_probe_query: str = "погода ростов на неделю"

    # --- Резервный источник: DuckDuckGo AI Chat (duck.ai) ---
    # Без логина/капчи. Используется, когда Google не отдал «Обзор» (капча/нет
    # блока). Работает в том же браузере. Модель по умолчанию — быстрая.
    duckduckgo_enabled: bool = True
    # Сколько ждать ответа duck.ai (стрим), мс.
    duckduckgo_gen_timeout_ms: int = 90_000


settings = Settings()
