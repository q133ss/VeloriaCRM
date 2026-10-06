"""Резервный источник текста — DuckDuckGo AI Chat (duck.ai).

Без логина, без капчи, доступен с сервера (проверено). Работает поверх того же
браузера Playwright, что и Google: открываем duck.ai, вводим промпт, ждём ответ
и снимаем текст. Анти-бот токен DuckDuckGo (`x-vqd-hash`) вычисляет сама страница
в реальном браузере — поэтому эмуляция проходит, в отличие от чистого HTTP.

Селекторы (разведаны на проде 2026-07-17):
- поле ввода: ``textarea[name="user-prompt"]``
- отправка: кнопка «Запрос» (нажатие = принятие условий, отдельной модалки нет)
- индикатор генерации: кнопка «Остановить генерацию»
- ответ ассистента: последний ``div.space-y-4.whitespace-normal`` вне user-message
"""
from __future__ import annotations

import time

from loguru import logger
from playwright.async_api import Page

DUCK_URL = "https://duck.ai/chat?ia=chat"
_INPUT = "textarea[name='user-prompt']"
_SEND_LABELS = ("Запрос", "Отправить", "Send")
_STOP_LABELS = ("Остановить генерацию", "Stop")

# Достаём текст последнего ответа ассистента (прозовый контейнер сообщения),
# исключая блок пользовательского сообщения.
_ANSWER_JS = """() => {
  const proses = [...document.querySelectorAll('div.space-y-4.whitespace-normal')]
    .filter(n => !n.closest('[data-testid=\"user-message\"]'));
  if (proses.length) return proses[proses.length - 1].innerText || '';
  return '';
}"""

# Хвосты интерфейса, которые иногда попадают в innerText — вырезаем.
_JUNK_TAILS = (
    "Duck.ai лучше всего работает",
    "DuckDuckGo обеспечивает анонимность",
    "Все чаты конфиденциальны",
    "ИИ может допускать ошибки",
)


class DuckDuckGoError(RuntimeError):
    """DuckDuckGo AI Chat не вернул ответ."""


def _clean(text: str) -> str:
    lines: list[str] = []
    for raw in (text or "").splitlines():
        line = raw.strip()
        if any(j in line for j in _JUNK_TAILS):
            break  # начался футер интерфейса — дальше не наш текст
        lines.append(raw)
    return "\n".join(lines).strip()


async def fetch_duckduckgo_answer(
    page: Page,
    prompt: str,
    *,
    nav_timeout_ms: int = 40_000,
    gen_timeout_ms: int = 90_000,
) -> str:
    """Прогнать промпт через duck.ai и вернуть текст ответа (или бросить ошибку)."""
    await page.goto(DUCK_URL, wait_until="domcontentloaded", timeout=nav_timeout_ms)
    ta = page.locator(_INPUT)
    await ta.wait_for(timeout=20_000)
    await ta.fill(prompt)

    # Отправка: кнопка «Запрос», иначе Enter.
    clicked = False
    for label in _SEND_LABELS:
        try:
            btn = page.get_by_role("button", name=label, exact=False)
            if await btn.count() > 0:
                await btn.first.click(timeout=4_000)
                clicked = True
                break
        except Exception:  # noqa: BLE001
            continue
    if not clicked:
        await ta.press("Enter")

    # Дожидаемся, что генерация началась (кнопка «Остановить»), best-effort.
    stop = None
    for label in _STOP_LABELS:
        loc = page.get_by_role("button", name=label, exact=False)
        try:
            await loc.first.wait_for(timeout=12_000)
            stop = loc
            break
        except Exception:  # noqa: BLE001
            continue

    # Ждём завершения: кнопка «Остановить» исчезла и текст ответа стабилен.
    deadline = time.monotonic() + gen_timeout_ms / 1000
    last = ""
    stable = 0
    while time.monotonic() < deadline:
        await page.wait_for_timeout(1_500)
        gen = (await stop.count()) if stop is not None else 0
        try:
            txt = await page.evaluate(_ANSWER_JS)
        except Exception:  # noqa: BLE001
            txt = ""
        if txt and txt == last and gen == 0:
            stable += 1
            if stable >= 2:
                break
        else:
            stable = 0
        last = txt

    try:
        answer = _clean(await page.evaluate(_ANSWER_JS))
    except Exception as exc:  # noqa: BLE001
        raise DuckDuckGoError(f"не удалось снять ответ: {exc}") from exc
    if len(answer) < 20:
        raise DuckDuckGoError("DuckDuckGo вернул пустой/слишком короткий ответ")
    logger.info("DuckDuckGo: ответ получен ({} симв.)", len(answer))
    return answer
