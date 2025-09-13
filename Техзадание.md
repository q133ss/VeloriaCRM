# VeloriaCRM — Codex Техническое задание (API‑first, Multi‑plan)

Дата: 2025-09-13  
Версия: **MVP → MLP**  
Автор: продукт/архитектор

---

## 0. Кратко (для Codex)
- **Что:** умная CRM для **частных бьюти‑мастеров** с ИИ‑ассистентом (*Veloria*). Вся работа строго через **REST API** (готово к мобильному приложению).  
- **Стек:** **Laravel 12**, **PostgreSQL 17**, **Redis**, **Laravel Sanctum**, Horizon (очереди), Scheduler (крон).  
- **UI:** **Materialize HTML Admin Template** (только как набор статических ассетов; всё общение с бэком — через API).  
- **ИИ:** **ChatGPT (OpenAI API)** для генерации подсказок/текстов/эвристик; (опц.) **Whisper** для STT (только в Elite, можно отключить).  
- **Платежи:** **ЮКасса** (YooKassa).  
- **Сообщения:** **sms‑aero**, **smsc** (SMS), **Telegram Bot API**, **Email** (по умолчанию **Mailgun**, допускается SMTP).  
- **Важно:** **Нет Docker. Нет WhatsApp.**  
- **Локализация:** мультиязычность (RU/EN из коробки, расширяемо).  
- **Планы:** **Ivory** (Free, без ИИ), **Veloria** (Pro, ИИ v1), **Imperium** (Elite, максимум ИИ).

> Цель: запустить MVP максимально быстро, оставив место для масштабирования (MLP/Pro).

---

## 1. Общая архитектура
- **API‑first**: `https://{domain}/api/v1/*` — единственная точка взаимодействия.  
- **Frontend**: Materialize как статические HTML/CSS/JS; состояние — в JS; API — через `fetch/Axios`.  
- **Auth:** Laravel **Sanctum** (SPA cookies + CSRF) и **Personal Access Tokens** (мобильное/интеграции).  
- **Multi‑tenant** (single owner на MVP, расширяемо): `tenants` + scope через глобальные скоупы/Policies.  
- **Очереди/крон:** Redis + Horizon; напоминания, отправка сообщений, вебхуки платежей.  
- **Документация:** OpenAPI 3.1 (JSON + Swagger UI `/docs`).  
- **Хранение файлов:** локально/облако (конфигурируемо), подписанные URL.  
- **Логирование:** Monolog → файл/Logtail/ELK; PII маскирование.  
- **I18n:** Laravel JSON Lang, `Accept-Language` + `locale` в профиле пользователя/клиента.  
- **Версионирование:** `/api/v1` (подготовка к `/v2`).

---

## 2. Планы и доступность модулей

### 2.1 Имена планов
- **Ivory** — бесплатный, без ИИ.  
- **Veloria** — Pro, ИИ‑ассистент v1.  
- **Imperium** — Elite, максимум ИИ и автоматизаций.

### 2.2 Матрица модулей по планам
| Модуль | Ivory | Veloria (Pro)                                       | Imperium (Elite) |
|---|---|-----------------------------------------------------|---|
| Дашборд KPI | ✔︎ базовый | ✔︎ + Velory‑подсказки                               | ✔︎ + Profit Map, динамические подсказки цены |
| Календарь/Записи | ✔︎ ручные | ✔︎ + No‑Show Shield эвристика, Smart Waitlist v1    | ✔︎ + авто‑заполнение, конфликт‑резолвер |
| Клиенты/Профиль | ✔︎ | ✔︎ + Fit Score (эвристика), автотеги                | ✔︎ + планы ухода, Fit Score PRO |
| Услуги/Прайс | ✔︎ | ✔︎ + рекомендации длительности/пакеты               | ✔︎ + динамические подсказки цены/длительности |
| Инвойсы/Платежи | ✔︎ (ЮКасса) | ✔︎ + предоплата/холд                                | ✔︎ + частичные/отложенные, авто‑возвраты |
| Сообщения | ✔︎ (SMS/Email/TG — вручную) | ✔︎ + триггеры, автонапоминания, Velory Reception (TG) | ✔︎ + многошаговые сценарии, A/B, throttling |
| Маркетинг/Реактивации | — | ✔︎ Reactivation Flows + A/B на шаблонах             | ✔︎ PRO (RFM сегменты, контрольные группы) |
| Онлайн‑запись/Витрина | ✔︎ базовая | ✔︎ расширенный брендинг + пиксели                   | ✔︎ белый лейбл, домен, конструктор |
| Портфолио | ✔︎ ручное | ✔︎ Portfolio Curator v1                             | ✔︎ PRO (авто‑подбор + лёгкая ретушь) |
| Отзывы | ✔︎ ручные ссылки | ✔︎ Review Maestro v1                                | ✔︎ PRO (перехват негатива, виджеты) |
| Аналитика | ✔︎ базовая | ✔︎ + маржа/час, повторные, эффект Velory              | ✔︎ Когорты, каналы, BI‑экспорт |
| Обучение/Тренды | — | ✔︎ Micro‑Lessons, Trend Scout базовый               | ✔︎ Skill Radar PRO, Trend Scout PRO |
| Voice‑to‑Notes (STT) | — | —                                                   | ✔︎ (OpenAI Whisper, можно отключить) |
| Автоматизации | — | ✔︎ триггеры (события/давности)                      | ✔︎ конструктор правил (IFTTT) |
| Интеграции | ✔︎ Email/SMS/TG/ЮКасса | ✔︎ + GCal (1‑way)                                   | ✔︎ GCal 2‑way, вебхуки, dev console |
| Безопасность | ✔︎ базовая | ✔︎ + 2FA (опц.)                                     | ✔︎ + аудит логов, резервные копии |

> WhatsApp: **исключён** по требованию. Используем **Telegram**, **SMS**, **Email**.

---

## 3. Модули (описание и логика)

> Для каждого модуля: назначение, данные, API, фоновые задачи, ошибки, метрики. Полная OpenAPI‑схема формируется при разработке.

### 3.1 Auth & Users
- **Назначение:** учётка мастера, вход/выход, PAT для мобильного.  
- **Данные:** `users(id, tenant_id, name, email, phone, password_hash, timezone, locale, created_at)`; `tenants(id, plan, status)`.  
- **API:**  
  - `POST /auth/register` • `POST /auth/login` • `POST /auth/logout`  
  - `GET /auth/me` • `POST /auth/token` (PAT) • восстановление пароля  
- **Логика:** Sanctum, Policies по tenant.  
- **Ошибки:** 429 rate limit; 401/403.  
- **Метрики:** успешные/неуспешные логины, активные сессии.

### 3.2 Clients (клиенты)
- **Назначение:** база клиентов, согласия, аллергии, предпочтения.  
- **Данные:** `clients(name, phone, email, birthday, tags jsonb, allergies jsonb, preferences jsonb, notes, last_visit_at, loyalty_level)`; `consents`.  
- **API:** CRUD, поиск; `GET /clients/<built-in function id>/history`; `POST /clients/<built-in function id>/consents`.  
- **Логика:** автотэги в Veloria/Imperium; локаль клиента для сообщений.  
- **Ошибки:** уникальность phone/email в пределах tenant.  
- **Метрики:** активные клиенты, R/F/M.

### 3.3 Services & Pricing
- **Назначение:** каталог услуг, длительность, себестоимость, пакеты.  
- **Данные:** `services(category, name, base_price, cost, duration_min, upsell_suggestions jsonb)`.  
- **API:** CRUD; топ‑услуги (analytics).  
- **Логика:** подсказки длительностей (эвристика), пакеты/комбо (Veloria+).

### 3.4 Appointments & Calendar
- **Назначение:** записи, статусы, переносы, предоплаты.  
- **Данные:** `appointments(client_id, service_id, starts_at, ends_at, status, deposit_amount, risk_no_show, fit_score, meta jsonb)`.  
- **API:** `GET /appointments?from=&to=`, `POST /appointments`, `PATCH /appointments/<built-in function id>`, confirm/cancel/complete, attach‑media.  
- **Логика:** проверка коллизий; напоминания T‑24/T‑3 (Veloria+ авто).  
- **Метрики:** заполненность, p95 создания/переноса.

### 3.5 Waitlist (лист ожидания)
- **Назначение:** заполнение освободившихся окон.  
- **Данные:** `waitlist_entries(client_id, service_id, preferred_slots jsonb, priority, status)`.  
- **API:** CRUD; `POST /waitlist/match`.  
- **Логика:** Veloria — эвристическая сортировка; Imperium — авто‑рассылка и бронирование с таймером.  
- **Фоновые задачи:** рассылка кандидатов, таймер подтверждения.

### 3.6 Invoices & Payments (ЮКасса)
- **Назначение:** счета, предоплаты/оплаты, возвраты.  
- **Данные:** `invoices(number, total, discount, payable, status, currency='RUB')`, `payments(provider='yookassa', provider_payment_id, amount, status, metadata)`.  
- **API:** `POST /invoices`, `GET /invoices/<built-in function id>`, `POST /invoices/<built-in function id>/send`, `POST /invoices/<built-in function id>/refund`; вебхук: `POST /payments/yookassa/webhook`.  
- **Логика:** создание оплаты (payment link), ожидание вебхука `succeeded/canceled`, идемпотентность через `Idempotency-Key`.  
- **ENV:** `YOOKASSA_SHOP_ID`, `YOOKASSA_SECRET_KEY`.  
- **Метрики:** конверсия оплаты, доля предоплат, средний чек.

### 3.7 Messaging (SMS/Email/Telegram)
- **Назначение:** коммуникации и уведомления.  
- **Данные:** `messages(channel='sms'|'email'|'telegram', direction, content, template_id, status, cost, scheduled_at, meta)`, `message_templates`.  
- **API:** `POST /messages` (одноразовое), `POST /messages/schedule`, `GET /messages`, `GET /message-templates`, CRUD шаблонов.  
- **Провайдеры:**  
  - **sms‑aero**, **smsc**: отправка + статусы (если поддерживаются), входящие не гарантированы.  
  - **Email**: Mailgun (по умолчанию) или SMTP.  
  - **Telegram**: Bot API, входящие сообщения через вебхук `/webhooks/telegram`.  
- **Логика:** планировщик напоминаний; троттлинг; локализация шаблонов.  
- **Метрики:** delivery/reply/opt‑out rate, стоимость.

### 3.8 Marketing — Reactivation Flows
- **Назначение:** возврат «давно не были».  
- **Данные:** `flows(spec jsonb)`, `flow_runs`, `flow_events`.  
- **API:** `POST /flows`, `/flows/<built-in function id>/activate|pause`, `/flows/<built-in function id>/stats`, `/flows/<built-in function id>/simulate`.  
- **Логика:** сегментация по давности/услуге/RFM; A/B (Veloria), контрольные группы/прогноз (Imperium).  
- **Каналы:** SMS, Email, Telegram (без WhatsApp).  
- **Метрики:** reply/book/paid deposit rate, uplift vs control.

### 3.9 Portfolio & Reviews
- **Назначение:** до/после, витрины, запрос отзывов.  
- **Данные:** `media(type=photo_before|photo_after)`, `reviews(source, rating, content, status)`.  
- **API:** загрузка медиа, сбор отзывов ссылкой, публикация виджетов (Imperium).  
- **Логика:** Curator v1 (Veloria): выбор лучших, подписи (ChatGPT). PRO (Imperium): авто‑подбор/ретушь лёгкая.  
- **Метрики:** CTR витрин, конверсия отзывов.

### 3.10 Analytics
- **Назначение:** показатели бизнеса.  
- **Данные:** `analytics_daily(date, revenue, hours_booked, repeat_rate, margin_per_hour, no_show_count)`.  
- **API:** `GET /analytics/overview`, `/analytics/services/top`, `/analytics/clients/segments`.  
- **Imperium:** когорты, каналы атрибуции, экспорт CSV.  
- **Метрики:** p95 запросов, корректность сумм.

### 3.11 Velory (ИИ)
- **Назначение:** подсказки/решения.  
- **API:**  
  - `POST /ai/no-show-score` → риск (0..1) + действия (Veloria+)  
  - `POST /ai/waitlist/suggest` → кандидаты (Veloria+)  
  - `POST /ai/upsell` → доп‑услуги/тексты (Veloria+)  
  - `POST /ai/voice-notes` (Imperium, опц. Whisper)  
- **Интеграция:** OpenAI Chat Completions; ключи в ENV; аудит запросов/стоимости.  
- **Безопасность:** PII минимизировать; prompt‑темплейты в коде/БД.

### 3.12 Online Booking (Витрина)
- **Назначение:** запись клиентов онлайн.  
- **Функции:** выбор услуги/времени, ввод контактных данных, предоплата (ЮКасса).  
- **Брендинг:** Ivory — базовый; Veloria — расширенный; Imperium — домен/белый лейбл.  
- **API:** публичные эндпоинты с капчей/скоростью.

### 3.13 Settings & Billing
- **Назначение:** часы работы, политики отмен/депозитов, каналы, бренд‑настройки, тариф.  
- **Данные:** `settings(business_hours, cancel_policy, deposit_policy, notification_prefs, branding)`.  
- **API:** `GET/PUT /settings/*`, `/billing/plan` (смена тарифа, ограничения).

### 3.14 Voice‑to‑Notes (Imperium)
- **Назначение:** запись аудио → транскрипт → структурированные заметки.  
- **API:** `POST /voice-notes` (multipart), `GET /voice-notes/<built-in function id>`, `POST /voice-notes/<built-in function id>/attach`.  
- **Провайдер:** OpenAI Whisper (опционально), можно отключить фичу флагом.  
- **Логика:** сущности (услуги/аллергии), summary, автозадачи, локализация.

---

## 4. Данные и ERD (текстово)
Ключевые таблицы: `users`, `tenants`, `clients`, `services`, `appointments`, `waitlist_entries`, `invoices`, `invoice_items`, `payments`, `messages`, `message_templates`, `consents`, `media`, `analytics_daily`, `flows`, `flow_runs`, `flow_events`, `ai_recommendations`, `settings`, `webhooks`, `audit_logs`.

> Полные поля и индексы — см. миграции; используем **jsonb** для гибких структур (предпочтения, шаблоны, мета). Индексы по `tenant_id`, `starts_at`, `status`, `client_id`.

---

## 5. API конвенции
- JSON; ошибки: `{ "error": { "code": "...", "message": "...", "fields": {...} } }`.  
- Пагинация: cursor‑based. Фильтры: `?filter[field]=...`; сортировка: `?sort=-created_at`.  
- Аутентификация: Bearer PAT **или** Sanctum SPA cookies.  
- Идемпотентность: заголовок `Idempotency-Key` — для POST с побочными эффектами (платежи/записи).  
- Rate limiting: per IP/token.  
- Вебхуки: подпись HMAC, повторные доставки, DLQ.

---

## 6. I18n/L10n
- **Языки:** RU (default), EN. Добавление других — через JSON‑файлы `lang/*.json`.  
- **API:** заголовок `Accept-Language`; сохранение `locale` в `users` и `clients`.  
- **Шаблоны сообщений/писем:** версии по локали; плейсхолдеры `{name}`, `{datetime}`.  
- **Форматы:** ISO8601 для дат/времени; валюта — `RUB` по умолчанию.

---

## 7. Материалы UI (Materialize)
- Подключить Materialize ассеты, построить layout с левым сайдбаром.  
- Компоненты: таблицы, модалки, тосты, дата‑пикеры, FullCalendar.  
- Все формы → `fetch` в `/api/v1`.  
- Блокировка скрытых по плану пунктов (см. §8).

---

## 8. План‑гейтинг и фича‑флаги
- В `tenants.plan` хранить `ivory|Veloria|imperium`.  
- Middleware `EnsurePlanAllows:feature` + карта **feature → min plan**.  
- UI скрывает недоступные пункты; API возвращает `403 feature_not_allowed`.

Пример карты:
```json
{
  "ai.no_show": "Veloria",
  "ai.upsell": "Veloria",
  "ai.voice_notes": "imperium",
  "marketing.flows": "Veloria",
  "marketing.flows.pro": "imperium",
  "booking.whitelabel": "imperium"
}
```

---

## 9. Интеграции
- **ЮКасса**: платежные ссылки, статусы, фискализация (при необходимости), вебхуки.  
- **SMS:** драйверы **sms‑aero**, **smsc**; статусы доставок; альяс‑имя отправителя.  
- **Email:** **Mailgun** (по умолчанию) или SMTP.  
- **Telegram Bot:** вебхук для входящих сообщений (реактивации/автозапись).  
- **Google Calendar:** Veloria — 1‑way; Imperium — 2‑way (P1).  
- **Без WhatsApp.**

ENV (пример):
```
APP_ENV=prod
APP_KEY=...
DB_CONNECTION=pgsql
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

QUEUE_CONNECTION=redis
CACHE_STORE=redis
BROADCAST_DRIVER=log

MAIL_MAILER=mailgun
MAILGUN_DOMAIN=...
MAILGUN_SECRET=...

SMS_PROVIDER=smsaero|smsc
SMSAERO_API_KEY=...
SMSC_LOGIN=...

TELEGRAM_BOT_TOKEN=...
TELEGRAM_WEBHOOK_SECRET=...

YOOKASSA_SHOP_ID=...
YOOKASSA_SECRET_KEY=...

OPENAI_API_KEY=...
```

---

## 10. Нефункциональные требования
- **Производительность:** p95 API < 300 мс на основные маршруты.  
- **Надёжность:** ретраи, очереди, DLQ, идемпотентность.  
- **Безопасность:** OWASP Top‑10, CSRF (SPA), CORS, 2FA (Veloria+), аудит (Imperium).  
- **Конфиденциальность:** PII минимально, доступ к медиа по подписанным URL.  
- **Трассировка:** request‑id, корреляция сообщений/вебхуков.

---

## 11. Критерии приёмки (MVP)
- Регистрация/логин, создание клиента, услуга, запись, напоминание, счёт, оплата через ЮКасса, статус обновился по вебхуку.  
- Reactivation Flow (Veloria): создан, симуляция аудитории, отправка в SMS/TG/Email, есть ответы/записи.  
- Аналитика Overview заполняется, цифры совпадают с событиями.  
- Velory v1: no‑show score, upsell — возвращают объяснимые подсказки.  
- I18n: RU/EN переключение, шаблоны сообщений локализованы.  
- Планы: гейтинг работает, недоступные фичи закрыты и в UI, и в API.

---

## 12. Спринты (ускоренные)
- **S0 (1–2 д):** проект, базовые миграции, Sanctum, OpenAPI скелет, Materialize подключить.  
- **S1 (5–6 д):** Clients, Services, Appointments (+календарь), Settings, Messaging (основа).  
- **S2 (5–6 д):** Invoices/ЮКасса + вебхуки, Reminders scheduler, Analytics Overview.  
- **S3 (4–5 д):** Velory v1 (no‑show/upsell/waitlist), Reactivation Flows (Veloria), полиш, QA.  
- **S4 (Elite P1):** Voice‑to‑Notes (опц.), Profit Map, белый лейбл витрины.

---

## 13. Примеры API (вырезки)

### Создать запись
```http
POST /api/v1/appointments
Idempotency-Key: 8b1f...

{
  "clientId": 123,
  "serviceId": 45,
  "startsAt": "2025-09-15T12:00:00+03:00",
  "durationMin": 90,
  "depositAmount": 1500
}
```

### Запустить Reactivation Flow
```http
POST /api/v1/flows
{
  "name": "45 дней без визитов",
  "spec": {
    "audience": {"recencyDays": 45},
    "channels": ["sms", "email", "telegram"],
    "schedule": {"type": "one_off", "sendAt": "2025-09-16T11:00:00+03:00"},
    "offer": {"type": "value_add","text":"Комплимент-уход при записи до пятницы"}
  }
}
```

### AI: Upsell
```http
POST /api/v1/ai/upsell
{
  "clientId": 123,
  "appointmentId": 987
}
```

---

## 14. Замечания для реализации
- Везде использовать **Policies** с привязкой к `tenant_id`.  
- Валидация — **FormRequest**; ошибки в едином формате.  
- Платёжные вебхуки — проверка подписи/таймстемпа; повторная доставка с защитой от дублей.  
- Сообщения — единый интерфейс провайдера; статусы «queued/sent/failed/delivered/read».  
- I18n — перевод строк интерфейса и шаблонов сообщений.  
- Лёгкий фейлбек каналов: если Telegram недоступен — SMS/Email (если разрешено).  
- Журнал аудита — для критичных действий (оплаты, статусы записей).

---

## 15. Меню (Materialize) по планам

### Ivory
- Дашборд · Календарь · Записи · Клиенты · Услуги · Инвойсы · Сообщения · Витрина · Портфолио · Отзывы · Аналитика (базовая) · Интеграции · Настройки · Биллинг · Помощь

### Veloria
- Дашборд (Velory) · Календарь (No‑Show/Waitlist) · Записи · Клиенты (Fit Score) · Услуги (рекомендации) · Инвойсы/Оплаты · Сообщения (триггеры) · Маркетинг (Reactivation) · Витрина+Пиксели · Портфолио (Curator) · Отзывы (Maestro) · Аналитика (маржа/час) · Обучение/Тренды · Интеграции (+GCal 1‑way) · Velory Studio · Настройки · Биллинг · Помощь

### Imperium
- Дашборд (Profit Map, динамическое ценообразование) · Календарь (автозаполнение) · Записи (правила) · Клиенты (планы ухода) · Услуги (динамика цен/времени) · Инвойсы (расшир.) · Сообщения (A/B, сценарии) · Маркетинг PRO · Витрина (домен/белый лейбл) · Портфолио PRO · Отзывы PRO · Аналитика PRO · Обучение PRO · Интеграции PRO (вебхуки, GCal 2‑way) · Автоматизации (IFTTT) · Velory Studio PRO · Настройки (расшир.) · Безопасность/Аудит · Биллинг · Помощь

---

## 16. Глоссарий
- **Velory** — ИИ‑ассистент (ChatGPT).  
- **Reactivation Flow** — сценарий возврата неактивных клиентов.  
- **Profit Map** — тепловая карта маржи/час.  
- **Fit Score** — эвристический индекс сложности/маржи визитов.

---

**Готово для Codex:** реализовывать по модулям, начиная с Auth/Clients/Services/Appointments, затем Payments (ЮКасса), Messaging, Analytics, AI v1, Reactivation. Все взаимодействия — через `/api/v1`. Не использовать WhatsApp и Docker. Учесть мультиязычность.
