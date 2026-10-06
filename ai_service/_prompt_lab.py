"""Сравнение форматов ответа на уже поднятом ai_service (POST /generate).

Три варианта промпта (A — текущий JSON-контракт booking_intent, B — голый
номер, C — укороченный JSON {"client": N}) гоняются на одних и тех же
синтетических фразах, результат печатается построчно для сравнения руками.

Ничего не пишет в Laravel/БД — чистый HTTP к уже запущенному сервису.

Запуск (сервис должен уже слушать :8100):
    ai_service/.venv/bin/python -m ai_service._prompt_lab
"""
from __future__ import annotations

import json
import re
import time
import urllib.error
import urllib.request

URL = "http://127.0.0.1:8100/generate"

CLIENTS = ["Марина Белова", "Юлия Титова", "Оксана Лаврова"]

PHRASES = [
    "марина завтра маникюр в 3",
    "юля ногти завтра в 15:00",
    "света стрижка в пятницу в 11",  # никого похожего нет — ожидаем "не найдено"
]

JSON_SKELETON = (
    "{\n"
    '  "understood": true или false,\n'
    '  "client_id": число, строка или null,\n'
    '  "client_candidate_ids": [число],\n'
    '  "client_query": строка или null,\n'
    '  "service_ids": [число],\n'
    '  "service_query": строка или null,\n'
    '  "note": строка или null,\n'
    '  "confidence": число или null\n'
    "}"
)

BASE_PROMPT = (
    "Ты помогаешь мастеру бьюти-салона разобрать фразу о новой записи.\n"
    "Дату и время уже разобрала программа — про время НИЧЕГО не определяй.\n\n"
    "Клиентка. Если в списке clients есть однозначное совпадение — верни его id "
    "в client_id. Если подходящих нет — client_id оставь пустым, а имя из "
    "фразы запиши в client_query в именительном падеже.\n\n"
    "Никогда не выдумывай id, которых нет в списке."
)


def numbered_clients() -> str:
    return "\n".join(f"{i}. {name}" for i, name in enumerate(CLIENTS, start=1))


def prompt_a(phrase: str) -> str:
    clients_json = json.dumps(
        [{"id": i, "name": name} for i, name in enumerate(CLIENTS, start=1)],
        ensure_ascii=False,
    )
    return (
        BASE_PROMPT
        + "\n\nДанные:\nphrase: " + phrase
        + "\nclients: " + clients_json
        + "\n\nВерни ТОЛЬКО JSON, без пояснений, без markdown и без текста вокруг."
        + "\nСтрого такой структуры:\n" + JSON_SKELETON
    )


def prompt_b(phrase: str) -> str:
    return (
        "Вот список клиентов мастера:\n" + numbered_clients()
        + f'\n\nКлиентка сказала мастеру: «{phrase}»\n\n'
        "Если среди списка есть эта клиентка — ответь ОДНИМ числом, её номером "
        "(1, 2 или 3).\nЕсли точно никого похожего нет — ответь 0.\n"
        "Ничего больше не пиши: ни слов, ни пояснений, ни знаков препинания."
    )


def prompt_c(phrase: str) -> str:
    return (
        "Вот список клиентов мастера:\n" + numbered_clients()
        + f'\n\nКлиентка сказала мастеру: «{phrase}»\n\n'
        'Ответь строго в формате {"client": N} где N — номер клиентки из '
        "списка или 0, если никого похожего нет. Никакого другого текста."
    )


def call(prompt: str) -> dict:
    data = json.dumps({"prompt": prompt}).encode("utf-8")
    req = urllib.request.Request(
        URL, data=data, headers={"Content-Type": "application/json"}, method="POST"
    )
    started = time.monotonic()
    try:
        with urllib.request.urlopen(req, timeout=130) as resp:
            body = json.loads(resp.read().decode("utf-8"))
            body["_wall_ms"] = int((time.monotonic() - started) * 1000)
            return body
    except urllib.error.HTTPError as exc:
        return {
            "error": f"HTTP {exc.code}",
            "detail": exc.read().decode("utf-8", "replace"),
            "_wall_ms": int((time.monotonic() - started) * 1000),
        }
    except Exception as exc:  # noqa: BLE001
        return {"error": str(exc), "_wall_ms": int((time.monotonic() - started) * 1000)}


def extract_json(text: str) -> dict | None:
    text = text.strip()
    text = re.sub(r"^`{3}(?:json)?\s*|\s*`{3}$", "", text, flags=re.MULTILINE)
    start = text.find("{")
    if start == -1:
        return None
    depth = 0
    in_string = False
    escaped = False
    for i in range(start, len(text)):
        ch = text[i]
        if escaped:
            escaped = False
            continue
        if ch == "\\":
            escaped = True
            continue
        if ch == '"':
            in_string = not in_string
            continue
        if in_string:
            continue
        if ch == "{":
            depth += 1
        elif ch == "}":
            depth -= 1
            if depth == 0:
                try:
                    return json.loads(text[start:i + 1])
                except json.JSONDecodeError:
                    return None
    return None


def parse_a(text: str) -> str:
    decoded = extract_json(text)
    if decoded is None:
        return "PARSE FAIL (нет валидного JSON)"
    missing = [k for k in ("understood",) if k not in decoded]
    if missing:
        return f"PARSE FAIL (нет обязательных полей: {missing})"
    return f"OK client_id={decoded.get('client_id')!r} client_query={decoded.get('client_query')!r}"


def parse_b(text: str) -> str:
    stripped = text.strip()
    if re.fullmatch(r"[0-3]", stripped):
        n = int(stripped)
        return f"OK -> {'никого' if n == 0 else CLIENTS[n - 1]}"
    return f"PARSE FAIL (не голое число: {stripped[:80]!r})"


def parse_c(text: str) -> str:
    decoded = extract_json(text)
    if decoded is None or "client" not in decoded:
        return "PARSE FAIL (нет {'client': N})"
    n = decoded["client"]
    if isinstance(n, int) and 0 <= n <= len(CLIENTS):
        return f"OK -> {'никого' if n == 0 else CLIENTS[n - 1]}"
    return f"PARSE FAIL (client вне диапазона: {n!r})"


VARIANTS = [
    ("A (текущий JSON)", prompt_a, parse_a),
    ("B (голый номер)", prompt_b, parse_b),
    ("C (короткий JSON)", prompt_c, parse_c),
]


def main() -> None:
    for phrase in PHRASES:
        print("=" * 70)
        print(f"Фраза: «{phrase}»")
        for label, build_prompt, parse in VARIANTS:
            prompt = build_prompt(phrase)
            result = call(prompt)
            print(f"\n-- {label} --")
            if "error" in result:
                print(f"  HTTP ERROR: {result['error']} {result.get('detail', '')[:200]}")
                continue
            text = result.get("text", "")
            print(f"  source={result.get('source')} elapsed_ms={result.get('elapsed_ms')} "
                  f"wall_ms={result.get('_wall_ms')}")
            print(f"  raw: {text[:300]!r}")
            print(f"  parsed: {parse(text)}")
        print()


if __name__ == "__main__":
    main()
