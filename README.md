# VeloriaCRM — Smart CRM for Beauty Professionals (Laravel, REST API)

VeloriaCRM — CRM для соло-мастеров и небольших салонов с модулем **Velory Agents**: набор ИИ-ассистентов, которые помогают вести клиентов и расписание, находить точки роста и снижать потери выручки. Всё работает через **REST API** (готово под мобильное приложение).

## Преимущества
- Снижает no-shows через напоминания и риск-оценку визитов
- Подсвечивает “пустые слоты” и предлагает сценарии заполнения
- Даёт аналитику маржа/час и динамику выручки
- Выявляет “сложных” клиентов и визиты (риск/задержки/возвраты)
- Рекомендует апсейлы и персональные предложения
- Встроенное микро-обучение и краткие саммари по трендам общения

## Что делает (value)
- Напоминания клиентам и снижение неявок
- Заполнение расписания и рекомендации по слотам
- Маржа/час и аналитика по визитам
- Риски по визитам и клиентам
- Апсейлы и персональные предложения
- Микро-обучение и саммари по сценариям общения

## Velory Agents (AI-модуль)
Набор ИИ-ассистентов, которые:
- дают рекомендации по клиентам и заказам прямо на дашборде
- формируют аналитические инсайты и короткие обучающие саммари
- работают через **OpenAI Chat Completions**

Доступность:
- **Pro/Elite**: AI-агенты включены
- **Free/Basic**: используются фоллбеки (правила/шаблоны/дефолтные подсказки)

## Архитектура
- Laravel backend (API-first)
- REST API как единственный интерфейс (web/mobile ready)
- AI-интеграция: OpenAI Chat Completions
- Paywall по тарифам (feature flags / access checks)

## API-first подход
- Авторизация и управление пользователями
- Клиенты, заказы/визиты, расписание
- Дашборд и аналитика
- AI endpoints (Pro/Elite only)

## Примечания
- Проект рассчитан на масштабирование под мобильное приложение
- AI-функции изолированы и корректно деградируют в фоллбеки при недоступности провайдера

# VeloriaCRM — Smart CRM for Beauty Professionals (Laravel, REST API)

VeloriaCRM is a CRM for solo pros and small beauty studios with **Velory Agents** — AI assistants that help manage clients and schedules, uncover growth opportunities, and reduce revenue leakage. Everything runs through a **REST API** (mobile-ready).

## Benefits
- Reduces no-shows via reminders and risk scoring
- Highlights “empty slots” and suggests fill strategies
- Profit-per-hour analytics and revenue trends
- Flags “difficult” clients and high-risk appointments
- Upsell and personalized offer recommendations
- Built-in micro-learning with short, actionable summaries

## What it does (value)
- Client reminders and fewer no-shows
- Schedule filling and slot recommendations
- Profit-per-hour and visit analytics
- Risk signals for clients and appointments
- Upsell and personalized offer suggestions
- Micro-learning and communication summaries

## Velory Agents (AI module)
Velory Agents:
- generate client/order recommendations on the dashboard
- produce analytics insights and concise learning summaries
- powered by **OpenAI Chat Completions**

Availability:
- **Pro/Elite**: AI agents enabled
- **Free/Basic**: fallbacks only (rules/templates/default hints)

## Architecture
- Laravel backend (API-first)
- REST API as the only interface (web/mobile ready)
- AI integration: OpenAI Chat Completions
- Paywall by plan tiers (feature flags / access checks)

## API-first approach
- Auth and user management
- Clients, orders/appointments, scheduling
- Dashboard and analytics
- AI endpoints (Pro/Elite only)

## Notes
- Designed for scale with a mobile client in mind
- AI features are isolated and gracefully degrade to fallbacks

## Telegram booking bot (polling)

The bot uses polling and starts working after you set `Settings -> Integrations -> Telegram -> Bot token`.

With Docker, polling is started automatically on `docker compose up` by the `telegram_polling` service.

Run polling locally (requires `php` in PATH):
```bash
php artisan telegram:poll-booking --timeout=10 --sleep=1
```

Run polling in Docker:
```bash
docker compose exec app php artisan telegram:poll-booking --timeout=10 --sleep=1
```

## Docker setup

Project includes Docker for local development with:
- `app` (PHP 8.3 FPM + Composer)
- `nginx` (HTTP server on `http://localhost:8080`)
- `db` (PostgreSQL 16)
- `redis` (Redis 7)
- `vite` (frontend dev server on `http://localhost:5173`)

### Quick start

1. Create env file:
```bash
cp .env.example .env
```
2. Start containers:
```bash
docker compose up -d --build
```
3. Run migrations:
```bash
docker compose exec app php artisan migrate
```
4. Generate app key (if needed):
```bash
docker compose exec app php artisan key:generate
```

### Useful commands

```bash
docker compose logs -f app
docker compose exec app php artisan test
docker compose exec app php artisan queue:work
docker compose down
```
