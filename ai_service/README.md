# ai_service — микросервис генерации текста объявлений

Принимает строку-запрос и возвращает готовый текст. Основной источник —
блок **«Обзор от ИИ»** (AI Overview) из выдачи Google, снимаемый через
Playwright + stealth. Если обзор получить не удалось (капча, нет блока,
таймаут) — сервис делает несколько **повторных попыток** (ретраев) с
нарастающей паузой, а затем падает в **резерв — DuckDuckGo AI Chat**
(`source=duckduckgo`, без логина/капчи, в том же браузере). Все запросы идут
через **последовательную очередь**, поэтому браузер обслуживает по одному
запросу за раз.

```
HTTP POST /generate  ──►  очередь  ──►  воркер
      └─ Google «Обзор от ИИ» (source=google_ai_overview), ретраи ×OVERVIEW_MAX_RETRIES
           └─ не вышло/капча ──► DuckDuckGo AI Chat (source=duckduckgo)
                                   └─ и он не смог ──► 502
```

## Установка

```bash
# из корня репозитория, в общий .venv
.venv/Scripts/python -m pip install -r ai_service/requirements.txt
.venv/Scripts/python -m playwright install chromium   # если не стоит

cp ai_service/.env.example ai_service/.env
# при необходимости подправьте OVERVIEW_MAX_RETRIES / паузы в ai_service/.env
```

## Запуск

```bash
.venv/Scripts/python -m ai_service.main
# сервис на http://0.0.0.0:8100
```

## API

### `POST /generate`
```json
{ "prompt": "Создай продающий текст объявления о продаже квартиры в Ростове" }
```
Ответ:
```json
{
  "text": "Продаю просторную 2-комнатную квартиру ...",
  "source": "google_ai_overview",
  "elapsed_ms": 2741,
  "attempts": 1
}
```
Коды: `200` — успех; `422` — пустой prompt; `502` — обзор не получен за
`OVERVIEW_MAX_RETRIES` попыток; `504` — превышен `REQUEST_TIMEOUT_S`.

### `GET /health`
```json
{ "status": "ok", "queue_depth": 0, "headless": true, "overview_max_retries": 3 }
```

Пример:
```bash
curl -X POST http://localhost:8100/generate \
  -H "Content-Type: application/json" \
  -d '{"prompt":"Создай продающий текст объявления о продаже квартиры в Ростове"}'
```

## Как обходится защита Google

Google отдаёт капчу автоматизированным браузерам. Чтобы стабильно получать
обзор от ИИ, сервис:

- использует **постоянный профиль** (`USER_DATA_DIR`) — cookie и согласие
  сохраняются между запросами и перезапусками;
- запускает **настоящий Google Chrome** (`BROWSER_CHANNEL=chrome`), а не
  встроенный Chromium (меньше детектится);
- предварительно ставит cookie согласия и по-человечески заходит на главную;
- применяет `playwright-stealth`.

**Если Google всё же показал капчу** — один раз запустите с `HEADLESS=false`,
решите капчу руками в открывшемся окне (профиль тот же), после чего выдача
открывается сама и в headless-режиме. На VPS (XFCE + xrdp) headed-режим
доступен через удалённый рабочий стол.

### Авто-восстановление при капче

Чтобы капча не останавливала конвейер, при её обнаружении сервис
**сам запускает восстановление** (`CAPTCHA_RECOVERY_ENABLED=true`):

1. открывает **видимое окно браузера** на рабочем столе сервера (`DISPLAY=:10`,
   xrdp) — на том же профиле, что и сервис, чтобы человек прошёл капчу руками;
2. шлёт напоминание в **Telegram** (тот же канал, что и парсеры — общий
   `shared_storage/telegram_config.toml`) событием `captcha_detected` — и
   повторяет его **раз в час** (`CAPTCHA_REMINDER_INTERVAL_S`), пока не решат;
3. **ждёт** решения, проверяя каждые `CAPTCHA_POLL_INTERVAL_S` секунд
   (до `CAPTCHA_RECOVERY_TIMEOUT_S`, `0` — бесконечно);
4. как только капча решена — шлёт `captcha_resolved`, возвращается в headless и
   продолжает работу.

Если открыть окно нельзя (нет `DISPLAY`) — сервис всё равно ждёт и раз в час
пингует Telegram, пока капча не спадёт. Нет конфига/бота — Telegram просто
выключен, на восстановление не влияет. Зайти и решить капчу вручную можно и
скриптом `python -m ai_service.solve_captcha` (см. `RDP.md`).

Чтобы не детектиться как бот, сервис ведёт себя человекоподобно (случайные
паузы, движения мыши и скролл, вариативные UA/viewport — см. `human_like` в
`config.py`), а вызывающая сторона (воркер `rephrase_worker`) держит редкий
темп с джиттером. Обзор недоступен — сервис делает ретраи и возвращает 502.

## Конфигурация (`.env`)

| Переменная | По умолчанию | Назначение |
|---|---|---|
| `HOST` / `PORT` | `0.0.0.0` / `8100` | адрес HTTP-сервера |
| `QUEUE_MAX_SIZE` | `100` | размер очереди |
| `REQUEST_TIMEOUT_S` | `120` | таймаут ожидания задачи |
| `HEADLESS` | `true` | режим браузера |
| `USER_DATA_DIR` | `./ai_service/profile` | постоянный профиль браузера |
| `BROWSER_CHANNEL` | `chrome` | `chrome` или пусто (Chromium) |
| `WARMUP_HOMEPAGE` | `true` | зайти на google.com перед поиском |
| `AI_OVERVIEW_TIMEOUT_MS` | `20000` | ожидание блока обзора |
| `DEBUG_DUMPS` | `false` | сохранять скриншот+html при неудаче |
| `OVERVIEW_MAX_RETRIES` | `3` | попыток получить обзор, иначе 502 |
| `OVERVIEW_RETRY_DELAY_S` | `5` | базовая пауза между попытками (× номер попытки) |
| `HUMAN_LIKE` | `true` | человекоподобное поведение (антидетект) |
| `TELEGRAM_CONFIG_PATH` | `./shared_storage/telegram_config.toml` | конфиг телеграм-уведомлений о капче (общий с парсерами) |
| `CAPTCHA_RECOVERY_ENABLED` | `true` | при капче открывать окно, ждать ручного решения |
| `CAPTCHA_REMINDER_INTERVAL_S` | `3600` | напоминание в Telegram раз в N секунд, пока капча не решена |
| `CAPTCHA_POLL_INTERVAL_S` | `15` | как часто проверять, решена ли капча |
| `CAPTCHA_RECOVERY_TIMEOUT_S` | `0` | максимум ожидания решения (0 — бесконечно) |
| `CAPTCHA_PROBE_QUERY` | `погода ростов на неделю` | запрос, открываемый в окне для решения капчи |
| `DUCKDUCKGO_ENABLED` | `true` | резерв DuckDuckGo AI Chat, когда Google не отдал обзор |
| `DUCKDUCKGO_GEN_TIMEOUT_MS` | `90000` | таймаут ожидания ответа duck.ai |

## Ручная проверка извлечения (без HTTP)

```bash
.venv/Scripts/python -m ai_service._livetest          # headless
.venv/Scripts/python -m ai_service._livetest --headed # с окном браузера
```

## Структура

```
ai_service/
  main.py          FastAPI: /generate, /health, lifespan
  queue_worker.py  очередь + воркер + ретраи получения обзора
  google_ai.py     Playwright+stealth, человекоподобность, извлечение «Обзора от ИИ»
  duckduckgo_ai.py резерв: DuckDuckGo AI Chat (duck.ai) в том же браузере
  notifier.py      уведомления о капче в Telegram (поверх shared_storage)
  config.py        настройки из .env
  models.py        pydantic-схемы запроса/ответа
  _livetest.py     ручной тест извлечения
```
