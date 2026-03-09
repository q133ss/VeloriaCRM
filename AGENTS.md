# Veloria CRM Agents Guide

## Project Snapshot
Veloria CRM is a Laravel + Blade CRM for beauty professionals. The product is being actively simplified for inexperienced users: dense admin-like screens are being redesigned into calmer, task-first flows.

Current UI direction:
- one clear primary action per screen
- less always-visible secondary data
- lighter right rails and fewer persistent side panels
- stronger empty states and safer wording
- validate both light and dark themes for any layout work

## Stack
- Laravel web app with Blade views
- API under `/api/v1/*`
- Blade pages use fetch-heavy client-side rendering for many modules
- Docker is the default runtime for verification
- YooKassa is used for subscription payments
- OpenAI-backed assistant features exist behind paid plans

## Important Current Product State
The old `learning` module has been replaced by `trends`.

Current state:
- web route `/learning` redirects to `/trends`
- API route `/api/v1/trends/overview` replaces the old learning overview flows
- old learning view/controllers were removed
- learning data models still exist and are reused as the content source for trends

If you touch docs, routes, menus, dashboard promos, or feature descriptions, keep this replacement consistent.

## Core Areas
### Scheduling and Orders
- calendar page: `resources/views/calendar/index.blade.php`
- orders list: `resources/views/orders/index.blade.php`
- order create: `resources/views/orders/create.blade.php`
- order show: `resources/views/orders/show.blade.php`
- order APIs: `app/Http/Controllers/Api/V1/OrderController.php`

Key recent UX decisions:
- creating an order from calendar uses a popup, not a hard redirect
- “new client” can be created inline from the order popup/form
- order screens were simplified to reduce cognitive load

### Clients
- clients list: `resources/views/clients/index.blade.php`
- create/edit/show: `resources/views/clients/*.blade.php`
- API: `app/Http/Controllers/Api/V1/ClientController.php`

Key recent UX decisions:
- clients should feel like a list of people, not a technical CRM table
- client profile should emphasize quick understanding, not dense analytics
- advanced/secondary sections should be collapsible when possible

### Services
- services page: `resources/views/services/index.blade.php`
- goal: compact catalog of services by category, not a heavy registry

### Analytics
- page: `resources/views/analytics/index.blade.php`
- API: `app/Http/Controllers/Api/V1/AnalyticsController.php`

Key recent backend/UI decisions:
- first screen shows only overview data
- hidden analytics sections are lazily loaded
- avoid shipping hidden heavy blocks in the initial API payload

### Trends
- page: `resources/views/trends/index.blade.php`
- API: `app/Http/Controllers/Api/V1/TrendsController.php`

Purpose:
- show niche-relevant trend cards, articles, and ready-to-use scripts
- keep the experience inspirational and lightweight, not evaluative or academic

### Subscription
- page: `resources/views/subscription/index.blade.php`
- API: `app/Http/Controllers/Api/V1/SubscriptionController.php`

Key recent UX decisions:
- page starts with plan choice, not cancellation fear
- empty billing/transaction states should not dominate the screen
- cancellation copy must clearly state that data is preserved

### Integrations / Settings / Help / Marketing / Landings
These sections were also simplified recently. When editing them:
- preserve the calmer hierarchy
- avoid reintroducing large always-open forms
- hide secondary or advanced actions until needed

## AI / Agents Layer
Primary AI-related backend pieces:
- `app/Services/OpenAIService.php`
- `app/Services/DashboardAiService.php`
- `app/Http/Controllers/Api/V1/ClientController.php`
- `app/Http/Controllers/Api/V1/OrderController.php`
- `app/Http/Controllers/Api/V1/AnalyticsController.php`
- `app/Http/Controllers/Api/V1/TrendsController.php`

Current responsibilities:
- client recommendations and analytics
- order recommendations and analytics
- dashboard suggestions and daily tip
- analytics insights/forecast
- trend content assembly for the trends screen

Paid-plan behavior matters:
- many AI features are intended for Pro/Elite access
- non-paid flows should degrade gracefully with fallbacks
- avoid introducing hard failures when OpenAI is unavailable

## Working Rules for This Repo
### UI verification
For Blade/UI work:
1. verify in Docker at `http://localhost:8080`
2. if local PHP is unavailable, use Docker commands
3. for Blade changes, typically run:
   - `docker compose exec app php artisan view:clear`
4. verify both light and dark themes before finishing layout work

### Browser checks
When validating UI changes, prefer checking:
- first meaningful paint / loaded state
- no JS console errors
- empty state
- active state with realistic data
- mobile-ish narrow width when the layout is sensitive

### Editing expectations
- keep Blade + fetch flows readable
- avoid pushing too much logic into giant inline string templates if a simpler structure is possible
- if API payload and Blade rendering drift apart, fix both in the same task
- if a section is hidden by default, consider whether the API should stop sending that data eagerly

## Architecture Notes That Matter
### Frontend pattern
Many pages follow this pattern:
- Blade renders the shell
- JS fetches `/api/v1/*`
- data is rendered client-side into placeholders
- page-specific state is managed inline in the Blade script

When changing a screen, always inspect both:
- the Blade view
- the API controller that feeds it

### Auth and ownership
Most business APIs are user-scoped. Before changing responses:
- preserve ownership checks
- preserve plan access checks
- preserve graceful fallback behavior

### Data sources reused across modules
The new trends module reuses content tables originally created for learning. Do not assume “Learning*” models mean the feature is still learning-facing in product terms.

## Known Technical Risks
- plan access checks are duplicated across controllers
- some AI cache keys are derived from rich context and deserve privacy review
- AI failure handling is not fully unified
- automated tests around AI/fallback behavior are still thin
- several screens rely on large inline scripts, so regressions often happen in Blade + API coupling rather than in isolated components

## Preferred Documentation Posture
When you update docs for this repo:
- describe the current product, not historical modules that were removed
- prefer actionable repo guidance over long architectural prose
- call out deprecated/replaced modules explicitly
- keep UI rules aligned with the current “simplify for inexperienced users” direction

## Quick Commands
- clear Blade views: `docker compose exec app php artisan view:clear`
- inspect routes: `docker compose exec app php artisan route:list`
- search repo text: `rg "pattern"`
- search files: `rg --files`

## Deprecated / Replaced
- `learning` product module -> replaced by `trends`
- old learning Blade/API flow -> removed from active routing
- subscription page no longer centers the UX around cancellation
