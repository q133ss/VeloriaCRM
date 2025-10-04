# Модуль Velory Agents (ИИ-ассистенты)

## 1. Назначение модуля
Velory Agents — набор ИИ-ассистентов, которые помогают мастеру принимать решения в CRM. Модуль покрывает:

- персональные рекомендации по клиентам (рестайлинг, удержание, след. шаги);
- рекомендации по конкретной записи/заказу (апсейл, прогноз длительности, риски);
- сводки и подсказки на дашборде (приоритеты дня, совет);
- обучающие саммари по плану развития мастера;
- аналитические инсайты по выручке и клиентам.

Вся логика строится поверх OpenAI Chat Completions API и завязана на тарифы Pro/Elite: для тарифов без доступа модуль отдает фоллбеки.

## 2. Архитектура и сущности

### Сервисный слой
| Класс | Ответственность | Ключевые методы |
| --- | --- | --- |
| `App\Services\OpenAIService` | Обертка над HTTP-клиентом Laravel для вызова OpenAI. Формирует payload, нормализует сообщения, кидает `HttpResponseException` при ошибках. | `respond()`, `createChatCompletion()`, `buildMessages()`, `client()` 【F:app/Services/OpenAIService.php†L15-L120】【F:app/Services/OpenAIService.php†L200-L239】 |
| `App\Services\DashboardAiService` | Генерация AI-подсказок на главном дашборде. Кэширует ответы на 45/12 часов, имеет фоллбеки. | `suggestions()`, `dailyTip()` 【F:app/Services/DashboardAiService.php†L17-L126】【F:app/Services/DashboardAiService.php†L128-L216】 |
| `App\Services\AIService` | Сводки по обучению (learning plan). Делегирует `OpenAIService`, подставляет локализацию и фоллбеки. | `summarizeLearningPlan()` 【F:app/Services/AIService.php†L15-L79】 |

### Контроллеры API
- `Api\V1\ClientController` — выдаёт клиентские рекомендации и аналитику, строит контекст из `clients`, `orders`, `services`, `settings`, использует кэш и фоллбеки. Гейт по тарифам и наличию `OPENAI_API_KEY`. 【F:app/Http/Controllers/Api/V1/ClientController.php†L24-L1529】
- `Api\V1\OrderController` — аналогичные рекомендации и аналитика в контексте заказа/записи (использует JSON-поле `orders.recommended_services`). 【F:app/Http/Controllers/Api/V1/OrderController.php†L104-L1701】
- `Api\V1\AnalyticsController` — готовит агрегаты и выдает AI-инсайты в блоках `financial`/`clients`. Кэширует ключевые куски и строит fallback. 【F:app/Http/Controllers/Api/V1/AnalyticsController.php†L32-L918】
- `Api\V1\Learning\LearningPlanController` — подключает `AIService` для резюме обучения. 【F:app/Http/Controllers/Api/V1/Learning/LearningPlanController.php†L16-L103】
- `DashboardController` (web) — собирает данные для Blade-дэшборда, вызывает `DashboardAiService`. 【F:app/Http/Controllers/DashboardController.php†L20-L209】

### Модели и таблицы
| Модель | Таблица | Поля/связи, используемые агентами |
| --- | --- | --- |
| `Client` | `clients` | `loyalty_level`, `tags`, `preferences`, `allergies`, `notes`, `last_visit_at`. Отношение `belongsTo(User)`. 【F:app/Models/Client.php†L9-L60】【F:database/migrations/2025_09_13_211408_create_clients_table.php†L13-L33】 |
| `Order` | `orders` | JSON `services`, метрики длительности, `recommended_services` (JSON из AI). `master_id`→User, `client_id`→User. 【F:app/Models/Order.php†L9-L138】【F:database/migrations/2025_09_23_010311_create_orders_table.php†L13-L54】 |
| `Appointment` | `appointments` | `risk_no_show`, `fit_score`, `service_ids` для контекста дашборда. 【F:database/migrations/2025_09_13_211414_create_appointments_table.php†L13-L30】 |
| `LearningRecommendation`/`LearningTask` | `learning_recommendations`, `learning_tasks` | Локализованные JSON-поля, приоритеты, прогресс. 【F:database/migrations/2025_10_06_000200_create_learning_recommendations_table.php†L11-L27】【F:database/migrations/2025_10_06_000300_create_learning_tasks_table.php†L9-L24】 |
| `Setting` | `settings` | `reminder_message`, `notification_prefs`, интеграции; используется в контекстах и мета. 【F:app/Models/Setting.php†L9-L48】 |
| `Plan`/pivot `plan_user` | `plans`, `plan_user` | Проверка Pro/Elite доступа для AI (`userHasProAccess`). 【F:app/Http/Controllers/Api/V1/ClientController.php†L1506-L1528】 |

### Конфигурация
`config/openai.php` описывает ключ, модель, таймаут и формат ответа. Отсутствие ключа => `OpenAIService::client()` бросает исключение; контроллеры поэтому проверяют `filled(config('openai.api_key'))`. 【F:config/openai.php†L4-L52】【F:app/Services/OpenAIService.php†L208-L239】

## 3. API и маршруты
Основные эндпоинты под `Route::middleware('auth:sanctum')` в `routes/api.php`:

| Метод | URL | Описание |
| --- | --- | --- |
| `GET` | `/api/v1/clients/{client}/recommendations` | Возвращает массив до 3 рекомендаций (`service`, `insight`, `action`, `confidence`). 403, если клиент не принадлежит пользователю или нет Pro/Elite. Кэшируется по сигнатуре сервисов+истории. 【F:app/Http/Controllers/Api/V1/ClientController.php†L1009-L1146】 |
| `GET` | `/api/v1/clients/{client}/analytics` | Инсайты и краткая аналитика по клиенту: `summary`, `risk_flags`, `recommendations`, метрики посещений. Fallback если AI недоступен. 【F:app/Http/Controllers/Api/V1/ClientController.php†L814-L973】 |
| `GET` | `/api/v1/orders/{order}` | В ответе сразу прилетают `recommended_services` (сохранённые/сгенерированные). 【F:app/Http/Controllers/Api/V1/OrderController.php†L94-L207】 |
| `GET` | `/api/v1/orders/{order}/analytics` | AI-анализ конкретного клиента/заказа (зеркалит клиентский, но контекст строится из заказа). 【F:app/Http/Controllers/Api/V1/OrderController.php†L1370-L1548】 |
| `GET` | `/api/v1/analytics/overview` | Глобальный дашборд + AI-инсайты `financial.insights`, `clients.insights`, `ai.summary/forecast`. Допускает query `from`, `to`, `grouping`. 【F:app/Http/Controllers/Api/V1/AnalyticsController.php†L54-L227】【F:app/Http/Controllers/Api/V1/AnalyticsController.php†L836-L918】 |
| `GET` | `/api/v1/learning/plan` | Возвращает `ai`-саммари, `insights`, `plan.tasks`. 【F:app/Http/Controllers/Api/V1/Learning/LearningPlanController.php†L19-L73】 |

Ответы стандартизированы JSON-структурами; ошибки прокидываются через `BaseService::throwError()` либо через Laravel `abort()`.

## 4. Логика работы (бизнес-процессы)

### Рекомендации по клиенту (`ClientController::recommendations`)
1. Проверка владения и тарифа (`ensureClientBelongsToCurrentUser`, `userHasProAccess`).
2. Сбор контекста: профиль клиента (`clients`), доступные услуги (`services`), последние заказы (`orders`). 【F:app/Http/Controllers/Api/V1/ClientController.php†L1049-L1098】
3. Генерация кэш-ключа по хэшу услуг/истории. Если кэш найден — возврат без API-запроса. 【F:app/Http/Controllers/Api/V1/ClientController.php†L1102-L1125】
4. Построение prompt с требованиями по JSON-схеме и вызов `OpenAIService::respond` c `response_format=json_schema`. 【F:app/Http/Controllers/Api/V1/ClientController.php†L1127-L1172】
5. Постобработка: фильтрация, сопоставление с каталогом услуг, нормализация confidence, fallbacks если пусто. 【F:app/Http/Controllers/Api/V1/ClientController.php†L1174-L1239】
6. Запись в кэш и выдача.

### Клиентская аналитика (`ClientController::analytics`)
- Счётчики визитов и финансов формируются из заказов.
- Если AI доступен, строится контекст и вызывается `OpenAIService` с JSON-схемой `client_analytics`. Ответ разбирается в `summary`, `risk_flags`, `recommendations`. Ошибки пишутся в лог и возвращается fallback (минимальный набор метрик). 【F:app/Http/Controllers/Api/V1/ClientController.php†L860-L973】

### Рекомендации по заказу (`OrderController`)
- Роуты `show`/`update`/`complete` используют `buildRecommendedServices`. Контекст шире: профиль клиента (`Client`), история заказов (`fetchClientHistory`), доступные услуги. Результат сериализуется в поле `orders.recommended_services`. 【F:app/Http/Controllers/Api/V1/OrderController.php†L312-L551】【F:app/Http/Controllers/Api/V1/OrderController.php†L802-L1154】
- Если AI недоступен, подставляются эвристические рекомендации (top услуги + новости). 【F:app/Http/Controllers/Api/V1/OrderController.php†L1408-L1486】

### Дашборд (`DashboardController` + `DashboardAiService`)
- Контроллер собирает расписание, финансы, топ-услуги и «сигналы» (свободные слоты, дни рождения, сложные визиты). 【F:app/Http/Controllers/DashboardController.php†L41-L178】
- `DashboardAiService::suggestions` превращает контекст в список приоритетов (JSON schema) с кэшем на 45 минут; при ошибках — фоллбек на основе сигналов. 【F:app/Services/DashboardAiService.php†L19-L126】【F:app/Services/DashboardAiService.php†L218-L304】
- `dailyTip` — отдельный prompt + кэш на 12 часов. 【F:app/Services/DashboardAiService.php†L128-L216】

### Обучение (`LearningPlanController`)
- Забирает `LearningRecommendation`/`LearningTask`, собирает инсайты.
- `AIService::summarizeLearningPlan` формирует JSON-ответ `headline/description/tips`, иначе использует шаблонные переводы. 【F:app/Services/AIService.php†L15-L79】

### Аналитика (`AnalyticsController::overview`)
- Агрегирует данные по платежам/заказам, формирует метрики, тренды, сегменты.
- Для AI-инсайтов собирает контекст (выручка, дельты, retention, риск-клиенты), вызывает `generateAiInsights` с JSON Schema. Результат (`summary`, `forecast`, `recommendations`) возвращается в payload и используется фронтом. 【F:app/Http/Controllers/Api/V1/AnalyticsController.php†L712-L918】

## 5. Взаимодействие фронтенда и бэкенда

### Дашборд
Blade-шаблон `resources/views/dashboard.blade.php` рендерит AI-данные на сервере: списки `aiSuggestions` и `dailyTip` выводятся без дополнительного JS. 【F:resources/views/dashboard.blade.php†L198-L280】【F:resources/views/dashboard.blade.php†L410-L438】

### Клиентская карточка
`resources/views/clients/show.blade.php` содержит SPA-подобный JS:
- При загрузке получает `/api/v1/clients/{id}` и выводит профиль.
- По кнопке «Аналитика клиента» вызывает `/api/v1/clients/{id}/analytics`, отображает `summary`, `risk_flags`, `recommendations`. 【F:resources/views/clients/show.blade.php†L636-L715】
- Авто-запрос рекомендаций `/api/v1/clients/{id}/recommendations`, отображение карточек с confidence. 【F:resources/views/clients/show.blade.php†L698-L733】

### Карточка записи/заказа
`resources/views/orders/show.blade.php`:
- При первичном fetch `/api/v1/orders/{order}` получает `recommended_services` и отрисовывает блок «Рекомендации ИИ». 【F:resources/views/orders/show.blade.php†L301-L341】
- Отдельная кнопка «Аналитика клиента» бьёт в `/api/v1/orders/{order}/analytics`. 【F:resources/views/orders/show.blade.php†L432-L470】

### Аналитика
`resources/views/analytics/index.blade.php` динамически тянет `/api/v1/analytics/overview`, отображает AI-инсайты в блоках «Financial insights» и «Client insights». Использует Chart.js для графиков. 【F:resources/views/analytics/index.blade.php†L824-L881】

### Обучение
SPA-запрос `/api/v1/learning/plan` (через fetch в отдельном JS-модуле) возвращает `ai`-саммари, которое отображается в `resources/views/learning/index.blade.php`. 【F:app/Http/Controllers/Api/V1/Learning/LearningPlanController.php†L19-L73】【F:resources/views/learning/index.blade.php†L37-L89】

## 6. Возможные улучшения / TODO
- **Безопасность данных в кэше.** Cache-ключи строятся через `json_encode` полного контекста (вкл. PII). Имеет смысл добавлять `Hash::make`/`sha1` от нормализованных структур без персоналий либо отключать кеширование для приватных полей. 【F:app/Http/Controllers/Api/V1/ClientController.php†L1102-L1125】【F:app/Services/DashboardAiService.php†L232-L304】
- **Унификация проверки тарифов.** `userHasProAccess()` дублируется в нескольких контроллерах и полагается на строковые сравнения `['pro','Pro',...]`. Стоит вынести в `User` (enum уровня плана, кэш). 【F:app/Http/Controllers/Api/V1/ClientController.php†L1506-L1528】【F:app/Http/Controllers/Api/V1/OrderController.php†L1194-L1234】
- **Обработка сбоев OpenAI.** Сейчас `OpenAIService::client()` бросает исключение, и если проверка `aiAvailable()` пропущена, API вернёт 500. Добавить глобальные фичи-тогглы/health-check, мок-режим для тестов. 【F:app/Services/OpenAIService.php†L208-L239】
- **Локализация промптов.** Большинство промптов зашито на русском. Для EN-пользователей стоит добавлять динамический язык и переводы фоллбеков. 【F:app/Http/Controllers/Api/V1/ClientController.php†L904-L973】【F:app/Services/AIService.php†L43-L74】
- **Хранение AI-результатов.** Сейчас рекомендации кэшируются в памяти и теряются после ttl. Можно складывать в отдельную таблицу/JSON-поле с audit (`recommended_services` уже есть у orders) и учитывать дату генерации.
- **Тесты.** Нет юнит-/фиче-тестов для AI-слоя: добавить моки HTTP и проверить фоллбеки/валидацию схем.

