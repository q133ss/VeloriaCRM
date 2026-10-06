"""Ручное прохождение капчи Google для ai_service.

Зачем: сервис ходит в Google под postоянным профилем (`USER_DATA_DIR`) в
headless-режиме. Google иногда помечает профиль и требует решить капчу/consent
руками. Headless это сделать не даёт — нужен ВИДИМЫЙ браузер на том же профиле.

Как это работает:
  1. останавливаем systemd-сервис `orion-ai-service` (он держит профиль занятым);
  2. открываем настоящий Chrome (headed) на ТОМ ЖЕ профиле;
  3. вы вручную проходите капчу/consent в открывшемся окне;
  4. скрипт проверяет, что «Обзор от ИИ» снова доступен, и возвращает сервис.

ВАЖНО: запускать НА РАБОЧЕМ СТОЛЕ СЕРВЕРА (через RDP/xrdp), иначе браузеру
некуда открыть окно (нет DISPLAY). Пример:

    cd /root/aviton
    ./venv/bin/python -m ai_service.solve_captcha

Флаги:
    --no-service   не трогать systemd (если сервис уже остановлен вручную)
    --query "..."  свой поисковый запрос для проверки
"""
from __future__ import annotations

import argparse
import asyncio
import os
import subprocess
import sys
from pathlib import Path
from urllib.parse import quote_plus

from .config import settings

SERVICE = "orion-ai-service"
DEFAULT_QUERY = (
    "Кратко опиши объявление: двухкомнатная квартира 54 кв.м, 5 этаж, "
    "Ростов-на-Дону, цена 5200000 RUB."
)


def _systemctl(action: str) -> None:
    try:
        subprocess.run(["systemctl", action, SERVICE], check=False, timeout=30)
        print(f"[systemctl] {action} {SERVICE}")
    except Exception as exc:  # noqa: BLE001
        print(f"[systemctl] не удалось {action} {SERVICE}: {exc}")


async def _solve(query: str) -> None:
    from playwright.async_api import async_playwright

    s = settings
    Path(s.user_data_dir).mkdir(parents=True, exist_ok=True)
    launch_kwargs: dict = dict(
        user_data_dir=s.user_data_dir,
        headless=False,  # ВИДИМОЕ окно — иначе капчу не решить
        locale=s.locale,
        args=[
            "--disable-blink-features=AutomationControlled",
            "--no-sandbox",
            "--disable-dev-shm-usage",
            "--start-maximized",
        ],
    )
    if s.browser_channel:
        launch_kwargs["channel"] = s.browser_channel

    async with async_playwright() as pw:
        try:
            ctx = await pw.chromium.launch_persistent_context(**launch_kwargs)
        except Exception as exc:  # канал chrome не установлен — откат на chromium
            print(f"channel={s.browser_channel} недоступен ({exc}); беру Chromium")
            launch_kwargs.pop("channel", None)
            ctx = await pw.chromium.launch_persistent_context(**launch_kwargs)

        await ctx.add_cookies([
            {"name": "SOCS", "value": "CAI", "domain": ".google.com", "path": "/"},
            {"name": "CONSENT", "value": "YES+", "domain": ".google.com", "path": "/"},
        ])
        page = await ctx.new_page()
        url = f"{s.google_domain}/search?q={quote_plus(query)}&hl=ru&gl=ru&pws=0"
        await page.goto(url, wait_until="domcontentloaded", timeout=s.nav_timeout_ms)

        print("\n" + "=" * 68)
        print("В открывшемся окне Chrome:")
        print("  1. Пройдите капчу / нажмите «Принять всё» (consent), если просят.")
        print("  2. Дождитесь, что выдача Google открывается нормально (без капчи).")
        print("  3. Вернитесь сюда и нажмите Enter — я проверю и верну сервис.")
        print("=" * 68 + "\n")
        try:
            await asyncio.get_event_loop().run_in_executor(None, input, "Готово? Enter: ")
        except (EOFError, KeyboardInterrupt):
            pass

        # Проверка: капча ушла?
        await page.goto(url, wait_until="domcontentloaded", timeout=s.nav_timeout_ms)
        html = (await page.content()).lower()
        blocked = "/sorry/" in page.url or any(
            m in html for m in ("recaptcha", "captcha-form", "unusual traffic",
                                 "необычный трафик", "our systems have detected")
        )
        await ctx.close()  # ВАЖНО: закрыть, иначе профиль останется занят
        if blocked:
            print("\n⚠️  Похоже, капча ещё показывается. Запустите скрипт снова и "
                  "убедитесь, что выдача открывается без капчи, прежде чем жать Enter.")
        else:
            print("\n✅ Капча/consent пройдены — cookies сохранены в профиле. "
                  "Сервис снова сможет получать «Обзор от ИИ» в headless.")


def main() -> None:
    ap = argparse.ArgumentParser(description="Ручное прохождение капчи Google для ai_service")
    ap.add_argument("--no-service", action="store_true",
                    help="не останавливать/запускать systemd (сервис уже остановлен)")
    ap.add_argument("--query", default=DEFAULT_QUERY, help="поисковый запрос для проверки")
    args = ap.parse_args()

    if not os.environ.get("DISPLAY"):
        print("⚠️  Нет переменной DISPLAY — браузеру некуда открыть окно.\n"
              "    Запустите скрипт НА РАБОЧЕМ СТОЛЕ сервера через RDP/xrdp,\n"
              "    в терминале внутри графической сессии.")
        sys.exit(1)

    manage = not args.no_service
    if manage:
        _systemctl("stop")
    try:
        asyncio.run(_solve(args.query))
    finally:
        if manage:
            _systemctl("start")
            print(f"[systemctl] {SERVICE} снова запущен.")


if __name__ == "__main__":
    main()
