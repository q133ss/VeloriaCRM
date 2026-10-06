# Промпт: перефразирование объявлений при парсинге (ai_service → Gemini)

Документ — готовый **системный + пользовательский промпт** для микросервиса
`ai_service`. При парсинге каждого объявления (Avito / CIAN / Yandex / Domclick)
мы берём уже разобранные поля из БД и просим ИИ **переписать текст в продающий**,
не выдумывая ни одного факта.

- Основной канал ответа — блок **«Обзор от ИИ» (Gemini)** из выдачи Google,
  снимаемый `ai_service/google_ai.py`.
- Fallback — `kie.ai` (Claude Haiku 4.5), `ai_service/fallback.py`.
- Точка входа — `POST /generate` (порт `8100`), поле `prompt`, **лимит 2000 символов**
  (см. `ai_service/models.py::GenerateRequest`). Промпт вместе с данными объявления
  **обязан укладываться в 2000 символов** — длинные описания усекаем (см. §5).

---

## 1. Откуда берём данные

Объявления лежат в SQLite `shared_listings.db`, таблица `listings`
(на сервере `217.26.28.45` — `/root/aviton/shared_listings.db`; всего 2311 записей:
yandex 2136, cian 174, own 1). Для перефразирования используем **только** эти поля:

| Поле в БД | Что это | В промпт |
|---|---|---|
| `title` | Заголовок | да |
| `description` | Описание | да, **после чистки** (см. §4) |
| `attributes_json` | Параметры (балкон, ремонт, потолки, мебель, площади, этаж…) | да |
| `price` + `currency` | Цена | да |
| `address_text` (fallback `location_text`) | Адрес | да |
| `url` / `section` | Тип сделки (аренда/продажа) — по слову `snyat`/`arenda`/`sale` | да, только чтобы выбрать «продаётся» / «сдаётся» |

**Всё остальное игнорируем.** `phone`, `seller_name`, `views_*`, `image_urls_json`,
`raw_*_json`, координаты и т. п. в текст объявления **не попадают**.

---

## 2. Реальные насыщенные объявления из БД (примеры)

Ниже — реальные записи из `listings` (сервер `217.26.28.45`), выбранные по
количеству заполненных параметров. Это те объявления, где ИИ есть из чего собрать
продающий текст, ничего не выдумывая.

> ⚠️ **Баг цен.** В части записей `price` сохранён некорректно (сотни млн ₽ за
> обычную квартиру — напр. `45300000`, `2516450000`). Это дефект парсинга цены,
> а не реальная стоимость. Для перефразирования цену лучше брать из чистого текста
> описания, а при явной аномалии — не выводить сумму вовсе (пусть модель опустит
> цену, а не печатает мусорное число). Баг стоит чинить отдельно в парсере.

### Пример A — CIAN, квартира (совпадает со структурой карточки в UI) ⭐

`external_id=331929617`, `kind=flat`. Богатые `apartment` + `house` и, что редкость,
**чистое авторское описание** (без блока «Похожие»). Эталонный вход для промпта.

```json
{
  "source": "cian",
  "external_id": "331929617",
  "url": "https://rostov.cian.ru/sale/flat/331929617/",
  "kind": "flat",
  "title": "Продается 1-комн. квартира, 30 м²",
  "price": 45300000,
  "currency": "RUB",
  "address_text": "Ростовская область, Ростов-на-Дону, р-н Пролетарский, мкр. Александровка, ул. Вересаева, 101/4",
  "attributes_json": {
    "apartment": {
      "Балкон/лоджия": "1 балкон", "Высота потолков": "2,8 м",
      "Жилая площадь": "14 м²", "Общая площадь": "30 м²",
      "Перепланировка": "Не было", "Площадь кухни": "7 м²",
      "Продаётся с мебелью": "Да", "Ремонт": "Евроремонт",
      "Санузел": "1 совмещенный", "Тип жилья": "Вторичка"
    },
    "house": {
      "Аварийность": "Нет", "Газоснабжение": "Центральное",
      "Год постройки": "2020", "Количество лифтов": "1 пассажирский, 1 грузовой",
      "Отопление": "Котел/Квартирное отопление", "Парковка": "Подземная",
      "Подъезды": "3", "Придомовая территория": "Шлагбаум",
      "Строительная серия": "Индивидуальный проект", "Тип дома": "Кирпичный",
      "Тип перекрытий": "Железобетонные"
    },
    "summary": { "price_per_meter": 176667 }
  },
  "description": "Готовое жильё в новом доме ЖК Вересаево… качественный евроремонт и развитая инфраструктура в новом доме 2020 года… общая площадь 30 кв.м, 14 кв.м жилой и 7 кв.м кухни… высокие потолки 2,8 м… балкон… евроремонт: светлые тона, аккуратный санузел… расположение окон во двор — тишина и приватность… подземная парковка… нет обременений, документы готовы, возможна ипотека… (полное описание в БД, 2703 симв.)"
}
```

### Пример B — CIAN, квартира (описание = мусор парсинга) ⚠️

`external_id=328986299`. Параметры полные (лоджия, косметический, раздельный
санузел, дом 1988 г., мусоропровод), **но `description` содержит только блок
«Похожие объявления»** — реальный текст продавца отсутствует. Показывает, зачем
нужна чистка (§4): здесь текст собираем из `title` + `attributes_json`.

```json
{
  "source": "cian",
  "external_id": "328986299",
  "url": "https://rostov.cian.ru/sale/flat/328986299/",
  "title": "Продается 2-комн. квартира, 54 м²",
  "price": 2516450000,
  "currency": "RUB",
  "address_text": "Ростовская область, Ростов-на-Дону, р-н Ворошиловский, мкр. проспект Ленина, просп. Ленина, 251",
  "attributes_json": {
    "apartment": {
      "Балкон/лоджия": "1 лоджия", "Высота потолков": "2,7 м",
      "Жилая площадь": "27,5 м²", "Общая площадь": "54 м²",
      "Площадь кухни": "8,4 м²", "Продаётся с мебелью": "Да",
      "Ремонт": "Косметический", "Санузел": "1 раздельный", "Тип жилья": "Вторичка"
    },
    "house": {
      "Аварийность": "Нет", "Год постройки": "1988", "Отопление": "Центральное",
      "Количество лифтов": "1 пассажирский, 1 грузовой",
      "О подъезде": "Есть мусоропровод", "Парковка": "Наземная",
      "Тип дома": "Кирпичный", "Тип перекрытий": "Железобетонные"
    },
    "summary": { "price_per_meter": 127254 }
  },
  "description": "Похожие объявления Похожие объявления Могут подойти Новые Вы смотрели Новостройки в этом районе 6 800 000 ₽ 1-комн.кв., 43 м²…"
}
```

### Пример C — Yandex, аренда квартиры (другая схема `attributes_json`)

`external_id=635559949382250792`. У Yandex параметры разложены иначе, зато
показательно наполнены: **балкон, мебель, техника, косметический ремонт,
высота потолков**. `description` — с эмодзи-спамом, тоже требует чистки.

```json
{
  "source": "yandex",
  "external_id": "635559949382250792",
  "url": "https://realty.yandex.ru/offer/635559949382250792/",
  "section": "rostovskaya_oblast/snyat/kvartira",
  "title": "34,5 м², 1-комнатная квартира",
  "price": 17000,
  "currency": "RUB",
  "address_text": "Ростов-на-Дону, переулок Андреева, 7",
  "description": "Сдаётся 1-комнатная квартира № 484150, 34.5 м², на 8 этаже 18-этажного дома. Собственник присутствует на показах. Коммунальные и счётчики — отдельно. Можно с детьми, без питомцев. Из техники: холодильник, стиральная… Подробнее",
  "attributes_json": {
    "building_features": [
      "Дом 2018 г.", "Панельное здание", "от 16 до 20 этажей",
      "432 квартиры", "4 подъезда", "2,7 м потолки", "Лифт",
      "Открытая парковка", "Мусоропровода нет"
    ],
    "details_features": [
      "Отделка — косметический ремонт", "Санузел совмещённый", "Балкон",
      "Вид из окон во двор", "Интернет", "Мебель", "Мебель на кухне",
      "Телевизор", "Стиральная машина", "Холодильник", "Кондиционер",
      "Встроенная техника", "Можно с детьми"
    ],
    "highlights": {
      "общая": "34,5 м²", "жилая": "17 м²", "кухня": "11 м²",
      "из 18": "8 этаж", "потолки": "2,7 м", "год постройки": "2018 год"
    }
  }
}
```

### Две схемы `attributes_json`

Промпт должен работать с любой — перечисляем то, что реально есть, и молчим о том,
чего нет:

- **CIAN** — плоские словари `apartment` (о квартире: балкон/лоджия, потолки,
  ремонт, санузел, площади, тип жилья…) и `house` (о доме: год, лифты, отопление,
  парковка, перекрытия, аварийность…) + служебный `summary.price_per_meter`.
- **Yandex** — списки `building_features` (о доме) и `details_features` (о квартире)
  + числа в `highlights` (площади/этаж/потолки/год). Плюс служебные `shortcuts`,
  `summary_tags` — их в текст не берём.

---

## 3. Итоговый промпт для Gemini

Формируется программно: подставляем поля объявления в шаблон и отправляем строкой
в `POST /generate`. Обе части (правила + данные) — в одном `prompt`.

### 3.1. Правила (неизменная часть)

```
Ты — редактор объявлений о недвижимости. Перепиши текст ниже в продающий,
грамотный и приятный для чтения вариант на русском языке.

СТРОГИЕ ПРАВИЛА:
1. Используй ТОЛЬКО факты из блока ДАННЫЕ (заголовок, описание, параметры,
   цена, адрес). Ничего не добавляй от себя: не придумывай метро, ремонт,
   мебель, инфраструктуру, расстояния, скидки, акции — ничего, чего нет в данных.
2. Если какого-то факта в данных нет — просто не упоминай его. Не пиши «уточняйте»,
   «возможно», «предположительно».
3. Убери мусор: контакты и телефоны, названия и рекламу агентств, слова «Подробнее»,
   «Показать телефон», «Только на Циан», блоки «Другие предложения», «Похожие»,
   «Недавно вы смотрели», списки других квартир и любые ссылки.
4. Сохрани числа точно как в данных: площадь, этаж, высоту потолков, год, цену.
   Цену выведи с валютой; для аренды добавь «в месяц».
5. Объём — 350–700 символов. Тон деловой и располагающий, без CAPS, без спама
   восклицательных знаков (максимум один), без эмодзи.
6. Структура: 1–2 абзаца связного текста. Разрешён короткий список «ключевых
   преимуществ» (до 5 пунктов), собранный ТОЛЬКО из параметров.

ФОРМАТ ОТВЕТА — СТРОГО:
Верни ТОЛЬКО готовый текст объявления. Без вступлений, пояснений, заголовков,
кавычек, markdown, без вариантов на выбор и без комментариев после текста.
Первый символ ответа — первое слово объявления.
```

### 3.2. Данные (подставляются на каждое объявление)

```
=== ДАННЫЕ ===
Тип сделки: {deal_type}            // «продажа» или «аренда» — из section/url
Заголовок: {title}
Адрес: {address}                   // address_text, иначе location_text
Цена: {price} {currency}           // напр. «17 000 RUB»
Параметры:
{attributes_lines}                 // по одному «Ключ: значение» на строку
Описание (сырое, вычищай мусор): {description_trunc}
=== КОНЕЦ ДАННЫХ ===
```

---

## 4. Чистка `description` — обязательна

Колонка `description` содержит **шум парсинга**, который нельзя тащить в текст.
Реальный пример (CIAN, `external_id=302826506`) — в `description` попал блок
«Другие предложения» с чужими квартирами:

```
"Другие предложения Домиан - офис Первые Соловушки 7 000 000 ₽ 1-этаж. дом 95 м²
улица Мира, Ленинаван… 3 600 000 ₽ 1-этаж. дом 100 м²… 5 800 000 ₽…"
```

При этом настоящий текст продавца лежал в `raw_listing_json.text`:
«ПРОДАЕТСЯ!!! Новый красивый дом 100 м² … толщина стен полтора кирпича с
утеплителем … тёплый пол, высокие потолки … коммуникации на меже (газ, свет, вода)».

Поэтому:

1. Если `description` начинается с «Другие предложения», «Похожие», «Недавно вы
   смотрели» или содержит перечисление чужих цен/адресов — предпочти чистый текст
   из `raw_listing_json.text` (или `raw_detail_json.body_text`), либо собери
   объявление из `title` + `attributes_json`, если чистого описания нет.
2. Правило №3 из промпта (см. §3.1) — вторая линия защиты: даже если мусор
   просочился, модель обязана его выкинуть.

---

## 5. Лимит 2000 символов

`GenerateRequest.prompt` ограничен 2000 символами. Правила (§3.1) занимают ~1.1 тыс.
Значит на данные остаётся ~800–900 символов:

- `{description_trunc}` усекай до ~500 символов по границе слова;
- в `{attributes_lines}` бери только осмысленные пары (для Yandex — `details_features`
  + числа из `highlights`; для CIAN — содержимое `apartment`/`house`), служебные
  вроде `price_per_meter`, `shortcuts`, `summary_tags` пропускай;
- если не влезаешь — сначала режь описание, параметры оставляй.

---

## 6. Полный пример собранного `prompt` (по объявлению из §2)

```
Ты — редактор объявлений о недвижимости. Перепиши текст ниже в продающий,
грамотный и приятный для чтения вариант на русском языке.

СТРОГИЕ ПРАВИЛА:
1. Используй ТОЛЬКО факты из блока ДАННЫЕ (заголовок, описание, параметры,
   цена, адрес). Ничего не добавляй от себя: не придумывай метро, ремонт,
   мебель, инфраструктуру, расстояния, скидки, акции — ничего, чего нет в данных.
2. Если какого-то факта в данных нет — просто не упоминай его.
3. Убери мусор: телефоны, рекламу агентств, «Подробнее», «Показать телефон»,
   «Только на Циан», блоки «Другие предложения»/«Похожие», ссылки.
4. Сохрани числа точно: площадь, этаж, высоту потолков, год, цену. Цену — с
   валютой; для аренды добавь «в месяц».
5. Объём 350–700 символов, без CAPS, без эмодзи, максимум один «!».
6. 1–2 абзаца; допустим короткий список преимуществ (до 5 пунктов) из параметров.

ФОРМАТ ОТВЕТА — СТРОГО: верни ТОЛЬКО текст объявления, без вступлений, пояснений,
заголовков, кавычек и markdown. Первый символ ответа — первое слово объявления.

=== ДАННЫЕ ===
Тип сделки: аренда
Заголовок: 34,5 м², 1-комнатная квартира
Адрес: Ростов-на-Дону, переулок Андреева, 7
Цена: 17 000 RUB в месяц
Параметры:
Общая площадь: 34,5 м²
Жилая: 17 м², кухня: 11 м²
Этаж: 8 из 18
Высота потолков: 2,7 м
Год постройки: 2018
Отделка: косметический ремонт
Санузел совмещённый, балкон, вид во двор
Мебель, встроенная техника, холодильник, стиральная машина, телевизор, кондиционер, интернет
Можно с детьми, без животных
Описание (сырое, вычищай мусор): Сдаётся 1-комнатная квартира, 34,5 м², на 8 этаже 18-этажного дома. Собственник присутствует на показах. Коммунальные платежи и счётчики — отдельно. Можно с детьми, без питомцев.
=== КОНЕЦ ДАННЫХ ===
```

### Ожидаемый ответ модели (пример допустимого результата)

```
Сдаётся уютная 1-комнатная квартира 34,5 м² на 8 этаже современного панельного
дома 2018 года в Ростове-на-Дону, переулок Андреева, 7. Просторная кухня 11 м²,
высокие потолки 2,7 м, косметический ремонт, совмещённый санузел, балкон и
приятный вид во двор.

Квартира полностью готова к заселению:
— мебель и встроенная техника;
— холодильник, стиральная машина, телевизор, кондиционер;
— интернет.

Можно с детьми. Стоимость — 17 000 ₽ в месяц, коммунальные услуги и счётчики
оплачиваются отдельно.
```

Здесь нет ни одного выдуманного факта: все цифры, ремонт, мебель, потолки, этаж
и цена взяты из данных объявления.

---

## 7. Как вызвать сервис

```bash
curl -X POST http://localhost:8100/generate \
  -H "Content-Type: application/json" \
  -d '{"prompt": "<собранный по §3 текст>"}'
```

Ответ:

```json
{ "text": "<готовый текст объявления>", "source": "google_ai_overview", "elapsed_ms": 2741 }
```

Замечания по интеграции:

- **Пост-проверка на выходе.** Даже со строгим форматом обрежьте у `text` возможные
  обёртки: ведущие кавычки, префиксы вида «Вот текст объявления:», markdown-заголовки.
- **Строгий формат надёжнее держит fallback-канал** (`kie.ai`, чат с system-промптом),
  чем «Обзор от ИИ» из поиска. Правила из §3.1 можно продублировать в
  `KIE_SYSTEM_PROMPT` (`ai_service/.env`), тогда в `prompt` останутся только данные —
  это экономит символы и повышает стабильность формата.
- **Кэшируйте результат** по `source+external_id`: перефразируем один раз при первом
  парсинге, не гоняем модель на каждую переактуализацию.

---

## 8. ТЗ: авто-перефразирование при сохранении объявления

Требование: **при каждом сохранении нового объявления после парсинга сразу
перефразировать текст, при этом оригинал обязательно хранить в БД.**

### 8.0. Архитектура — асинхронно

Все парсеры (Avito/CIAN/Yandex/Domclick) сохраняют объявления одним методом —
`SharedListingRepository.upsert(record)` в `shared_storage/sqlite_repository.py`.
Очередь `ai_service` последовательная (один браузер Google, ~3–20 сек на объект),
поэтому синхронный вызов ИИ прямо в `upsert` сериализовал бы парсинг за очередью и
растянул проход по 2000+ объявлений на часы. Решение:

```
upsert()  ──►  строка сохранена, description_ai_status='pending'   (мгновенно)
                                   │
                       (отдельный процесс)
                                   ▼
        rephrase_worker  ──►  берёт pending пачками  ──►  POST /generate
                                   │
                                   ▼
                 UPDATE listings: description_ai, status='ok', source, at
```

- Оригинал описания **никогда не перезаписывается** — остаётся в `description`.
- Перефразированный текст пишется в **новую колонку `description_ai`**.
- Детекторы (`_detect_flat_type`, `_detect_rooms`, …) продолжают читать `description`
  (оригинал) — их логику не трогаем.
- Сайт показывает `description_ai`, если он есть, иначе — `description`.

### 8.1. Хранение — новые колонки

Добавить в таблицу `listings`:

| Колонка | Тип | Назначение |
|---|---|---|
| `description` | TEXT | **оригинал** (уже есть, не трогаем) |
| `description_ai` | TEXT | перефразированный ИИ текст |
| `description_ai_status` | TEXT | `pending` / `ok` / `failed` / `skipped` (NULL — старые записи) |
| `description_ai_source` | TEXT | `google_ai_overview` / `kie_fallback` |
| `description_ai_at` | TEXT | ISO-время генерации |
| `description_ai_attempts` | INTEGER | счётчик попыток (ограничить ретраи) |

### 8.2. Миграция + пометка `pending` (`shared_storage/sqlite_repository.py`)

**(а)** В `_init_db`, в список ALTER-миграций (после `... ADD COLUMN kind TEXT`):

```python
"ALTER TABLE listings ADD COLUMN description_ai TEXT",
"ALTER TABLE listings ADD COLUMN description_ai_status TEXT",
"ALTER TABLE listings ADD COLUMN description_ai_source TEXT",
"ALTER TABLE listings ADD COLUMN description_ai_at TEXT",
"ALTER TABLE listings ADD COLUMN description_ai_attempts INTEGER DEFAULT 0",
```

И индекс для выборки воркером (рядом с другими `CREATE INDEX`):

```python
cursor.execute(
    "CREATE INDEX IF NOT EXISTS idx_listings_ai_status "
    "ON listings (description_ai_status)"
)
```

**(б)** В `upsert`, в SQL `INSERT`: добавить 5 колонок в список и литералы в `VALUES`
(эти поля НЕ из payload — задаём константами):

```sql
    ..., kind,
    description_ai, description_ai_status, description_ai_source,
    description_ai_at, description_ai_attempts
) VALUES (
    ..., :kind,
    NULL, 'pending', NULL, NULL, 0
)
```

**(в)** В блоке `ON CONFLICT(source, external_id) DO UPDATE SET` — в конец добавить
логику: если оригинал описания **не изменился**, сохраняем уже сгенерированный
ИИ-текст; если **изменился** — сбрасываем в `pending` (перегенерировать):

```sql
    ..., kind = excluded.kind,
    description_ai = CASE
        WHEN listings.description IS excluded.description
        THEN listings.description_ai ELSE NULL END,
    description_ai_status = CASE
        WHEN listings.description IS excluded.description
        THEN listings.description_ai_status ELSE 'pending' END,
    description_ai_source = CASE
        WHEN listings.description IS excluded.description
        THEN listings.description_ai_source ELSE NULL END,
    description_ai_at = CASE
        WHEN listings.description IS excluded.description
        THEN listings.description_ai_at ELSE NULL END,
    description_ai_attempts = CASE
        WHEN listings.description IS excluded.description
        THEN listings.description_ai_attempts ELSE 0 END
```

> `IS` в SQLite — NULL-безопасное сравнение (`NULL IS NULL` → true), поэтому
> переактуализация без смены описания (например, `is_active`=0 от актуализатора)
> сохранит ИИ-текст.

### 8.3. Клиент + билдер промпта — новый файл `shared_storage/rephrase.py`

Реализует §3–§5 этого документа: чистит описание (§4), разворачивает
`attributes_json` обеих схем (CIAN `apartment`/`house`, Yandex
`details_features`/`highlights`), собирает промпт под 2000 символов, вызывает
`POST /generate`, чистит ответ от обёрток.

```python
"""Клиент перефразирования объявлений через микросервис ai_service."""
from __future__ import annotations

import json
import os
import re
from dataclasses import dataclass

import httpx

AI_SERVICE_URL = os.environ.get("AI_SERVICE_URL", "http://127.0.0.1:8100")

_PROMPT_LIMIT = 2000   # лимит prompt в ai_service (models.py::GenerateRequest)
_DESC_LIMIT = 500      # максимум символов сырого описания в промпте

_JUNK_PREFIXES = (
    "другие предложения", "похожие объявления", "похожие", "могут подойти",
    "недавно вы смотрели", "вы смотрели", "новостройки в этом районе",
)
_JUNK_SUBSTRINGS = ("показать телефон", "подробнее", "только на циан")

_RULES = (
    "Ты — редактор объявлений о недвижимости. Перепиши текст ниже в продающий, "
    "грамотный и приятный для чтения вариант на русском языке.\n\n"
    "СТРОГИЕ ПРАВИЛА:\n"
    "1. Используй ТОЛЬКО факты из блока ДАННЫЕ (заголовок, описание, параметры, "
    "цена, адрес). Ничего не добавляй от себя: не придумывай метро, ремонт, "
    "мебель, инфраструктуру, расстояния, скидки, акции — ничего, чего нет в данных.\n"
    "2. Если какого-то факта в данных нет — просто не упоминай его. Не пиши "
    "«уточняйте», «возможно», «предположительно».\n"
    "3. Убери мусор: телефоны, названия и рекламу агентств, слова «Подробнее», "
    "«Показать телефон», «Только на Циан», блоки «Другие предложения»/«Похожие», "
    "списки других квартир и любые ссылки.\n"
    "4. Сохрани числа точно как в данных: площадь, этаж, высоту потолков, год, "
    "цену. Цену выведи с валютой; для аренды добавь «в месяц».\n"
    "5. Объём 350–700 символов. Тон деловой и располагающий, без CAPS, без спама "
    "восклицательных знаков (максимум один), без эмодзи.\n"
    "6. Структура: 1–2 абзаца связного текста; допустим короткий список ключевых "
    "преимуществ (до 5 пунктов) ТОЛЬКО из параметров.\n\n"
    "ФОРМАТ ОТВЕТА — СТРОГО: верни ТОЛЬКО готовый текст объявления. Без вступлений, "
    "пояснений, заголовков, кавычек, markdown, без вариантов на выбор и без "
    "комментариев после текста. Первый символ ответа — первое слово объявления.\n"
)


class RephraseError(RuntimeError):
    """Сервис недоступен, вернул ошибку или пустой ответ."""


@dataclass(slots=True)
class RephraseResult:
    text: str
    source: str          # google_ai_overview | kie_fallback
    elapsed_ms: int


def _deal_type(section: str | None, url: str | None) -> str:
    blob = f"{section or ''} {url or ''}".lower()
    if any(h in blob for h in ("snyat", "arend", "rent", "аренд", "сдам", "сдаю")):
        return "аренда"
    return "продажа"


def _raw_text(raw_listing_json: str | None) -> str:
    try:
        data = json.loads(raw_listing_json or "{}")
    except (ValueError, TypeError):
        return ""
    return (data.get("text") or "").strip() if isinstance(data, dict) else ""


def _clean_description(description: str | None, raw_listing_json: str | None) -> str:
    """Чистый текст продавца или '' — если в описании только мусор парсинга."""
    desc = (description or "").strip()
    low = desc.lower()
    if any(low.startswith(p) for p in _JUNK_PREFIXES) or not desc:
        raw = _raw_text(raw_listing_json)
        if raw and not any(raw.lower().startswith(p) for p in _JUNK_PREFIXES):
            desc = raw
    if any(desc.lower().startswith(p) for p in _JUNK_PREFIXES):
        return ""   # чистого описания нет — соберём из параметров
    for junk in _JUNK_SUBSTRINGS:
        desc = re.sub(junk, "", desc, flags=re.IGNORECASE)
    desc = re.sub(r"\s+", " ", desc).strip()
    if len(desc) > _DESC_LIMIT:
        desc = desc[:_DESC_LIMIT].rsplit(" ", 1)[0] + "…"
    return desc


_SKIP_ATTR_KEYS = {"price_per_meter", "shortcuts", "summary_tags", "summary"}


def _format_attributes(attributes_json: str | None) -> str:
    """attributes_json обеих схем (CIAN и Yandex) → строки «ключ: значение»."""
    try:
        attrs = json.loads(attributes_json or "{}")
    except (ValueError, TypeError):
        return ""
    if not isinstance(attrs, dict):
        return ""
    lines: list[str] = []
    for group in ("apartment", "house"):          # CIAN — плоские словари
        section = attrs.get(group)
        if isinstance(section, dict):
            for key, value in section.items():
                if key in _SKIP_ATTR_KEYS or value in (None, "", "Нет информации"):
                    continue
                lines.append(f"{key}: {value}")
    highlights = attrs.get("highlights")           # Yandex — числа
    if isinstance(highlights, dict):
        for key, value in highlights.items():
            if value:
                lines.append(f"{key}: {value}")
    for group in ("details_features", "building_features"):   # Yandex — списки
        feats = attrs.get(group)
        if isinstance(feats, list):
            useful = [str(f).strip() for f in feats if str(f).strip()]
            if useful:
                lines.append("; ".join(useful))
    return "\n".join(lines)


def _format_price(price: int | None, currency: str | None, deal: str) -> str:
    if not price:
        return ""
    if price > 500_000_000:      # защита от бага цен — аномалию не печатаем
        return ""
    formatted = f"{price:,}".replace(",", " ")
    tail = " в месяц" if deal == "аренда" else ""
    return f"{formatted} {currency or 'RUB'}{tail}"


def build_prompt(row: dict) -> str:
    """Собрать полный prompt (правила + данные) под лимит 2000 символов."""
    deal = _deal_type(row.get("section"), row.get("url"))
    title = (row.get("title") or "").strip()
    address = (row.get("address_text") or row.get("location_text") or "").strip()
    price = _format_price(row.get("price"), row.get("currency"), deal)
    attrs = _format_attributes(row.get("attributes_json"))
    desc = _clean_description(row.get("description"), row.get("raw_listing_json"))

    data_lines = [
        "=== ДАННЫЕ ===",
        f"Тип сделки: {deal}",
        f"Заголовок: {title}",
        f"Адрес: {address}",
    ]
    if price:
        data_lines.append(f"Цена: {price}")
    if attrs:
        data_lines.append("Параметры:")
        data_lines.append(attrs)
    if desc:
        data_lines.append(f"Описание (сырое, вычищай мусор): {desc}")
    data_lines.append("=== КОНЕЦ ДАННЫХ ===")

    prompt = _RULES + "\n" + "\n".join(data_lines)
    if len(prompt) > _PROMPT_LIMIT and desc:      # сначала режем описание
        overflow = len(prompt) - _PROMPT_LIMIT
        trimmed = desc[: max(0, len(desc) - overflow - 1)].rsplit(" ", 1)[0]
        data_lines[-2] = f"Описание (сырое, вычищай мусор): {trimmed}…"
        prompt = _RULES + "\n" + "\n".join(data_lines)
    if len(prompt) > _PROMPT_LIMIT:               # всё ещё длинно — без описания
        data_lines = [l for l in data_lines if not l.startswith("Описание (сырое")]
        prompt = _RULES + "\n" + "\n".join(data_lines)
    return prompt[:_PROMPT_LIMIT]


def has_content_to_rephrase(row: dict) -> bool:
    """Есть ли из чего перефразировать (иначе воркер помечает 'skipped')."""
    desc = _clean_description(row.get("description"), row.get("raw_listing_json"))
    return bool(desc or _format_attributes(row.get("attributes_json")))


_OUTPUT_PREFIXES = re.compile(
    r"^\s*(вот|готовый|текст объявления|объявление|результат)[^:\n]{0,40}:\s*",
    re.IGNORECASE,
)


def _clean_output(text: str) -> str:
    text = (text or "").strip()
    text = _OUTPUT_PREFIXES.sub("", text).strip().strip("`").strip()
    if len(text) >= 2 and text[0] in "«\"'“" and text[-1] in "»\"'”":
        text = text[1:-1].strip()
    return re.sub(r"^#{1,6}\s*", "", text).strip()


class RephraseClient:
    def __init__(self, base_url: str | None = None, timeout_s: float = 130.0):
        self.base_url = (base_url or AI_SERVICE_URL).rstrip("/")
        self.timeout_s = timeout_s

    def rephrase_row(self, row: dict) -> RephraseResult:
        return self.rephrase_prompt(build_prompt(row))

    def rephrase_prompt(self, prompt: str) -> RephraseResult:
        try:
            resp = httpx.post(f"{self.base_url}/generate",
                              json={"prompt": prompt}, timeout=self.timeout_s)
            resp.raise_for_status()
            data = resp.json()
        except httpx.HTTPStatusError as exc:
            raise RephraseError(
                f"ai_service {exc.response.status_code}: {exc.response.text[:300]}"
            ) from exc
        except httpx.HTTPError as exc:
            raise RephraseError(f"ai_service недоступен: {exc}") from exc
        text = _clean_output(data.get("text", ""))
        if not text:
            raise RephraseError("ai_service вернул пустой текст")
        return RephraseResult(text=text, source=data.get("source", "unknown"),
                              elapsed_ms=int(data.get("elapsed_ms", 0)))

    def health(self) -> bool:
        try:
            resp = httpx.get(f"{self.base_url}/health", timeout=5.0)
            return resp.status_code == 200 and resp.json().get("status") == "ok"
        except httpx.HTTPError:
            return False
```

### 8.4. Методы репозитория для воркера (`sqlite_repository.py`)

Добавить в `SharedListingRepository` (после `count`):

```python
_MAX_ATTEMPTS = 3

def fetch_pending_rephrase(self, limit: int = 20, include_legacy: bool = False,
                           retry_failed: bool = False) -> list[dict]:
    """Строки, ожидающие перефразирования."""
    statuses = ["'pending'"]
    if retry_failed:
        statuses.append("'failed'")
    where = f"description_ai_status IN ({','.join(statuses)})"
    if include_legacy:   # старые записи (status IS NULL) — режим бэкфилла
        where = f"({where} OR description_ai_status IS NULL)"
    sql = (f"SELECT * FROM listings WHERE {where} "
           f"AND description_ai_attempts < ? "
           f"ORDER BY last_parsed_at DESC LIMIT ?")
    with self._connect() as connection:
        rows = connection.execute(sql, (self._MAX_ATTEMPTS, limit)).fetchall()
        return [dict(r) for r in rows]

def mark_rephrased(self, source: str, external_id: str, text: str,
                   ai_source: str) -> None:
    from .models import utcnow_iso
    with self._connect() as connection:
        connection.execute(
            "UPDATE listings SET description_ai=?, description_ai_status='ok', "
            "description_ai_source=?, description_ai_at=?, "
            "description_ai_attempts=description_ai_attempts+1 "
            "WHERE source=? AND external_id=?",
            (text, ai_source, utcnow_iso(), source, external_id))
        connection.commit()

def mark_rephrase_status(self, source: str, external_id: str,
                         status: str) -> None:
    """status: 'failed' | 'skipped'. Инкрементит счётчик попыток."""
    with self._connect() as connection:
        connection.execute(
            "UPDATE listings SET description_ai_status=?, "
            "description_ai_attempts=description_ai_attempts+1 "
            "WHERE source=? AND external_id=?",
            (status, source, external_id))
        connection.commit()
```

### 8.5. Воркер — новый файл `shared_storage/rephrase_worker.py`

```python
"""Фоновый воркер: перефразирует объявления со статусом pending.

Запуск:
  python -m shared_storage.rephrase_worker --db shared_listings.db          # демон
  python -m shared_storage.rephrase_worker --db shared_listings.db --once   # один проход
  python -m shared_storage.rephrase_worker --db ... --once --backfill        # + старые записи
"""
from __future__ import annotations

import argparse
import time

from loguru import logger

from .rephrase import RephraseClient, RephraseError, has_content_to_rephrase
from .sqlite_repository import SharedListingRepository


def run_once(repo: SharedListingRepository, client: RephraseClient,
             batch: int, backfill: bool, retry_failed: bool) -> int:
    rows = repo.fetch_pending_rephrase(limit=batch, include_legacy=backfill,
                                       retry_failed=retry_failed)
    for row in rows:
        src, ext = row["source"], row["external_id"]
        if not has_content_to_rephrase(row):
            repo.mark_rephrase_status(src, ext, "skipped")
            logger.info("skip {}:{} — нет данных для текста", src, ext)
            continue
        try:
            res = client.rephrase_row(row)
            repo.mark_rephrased(src, ext, res.text, res.source)
            logger.info("ok {}:{} ({}, {} симв., {} мс)",
                        src, ext, res.source, len(res.text), res.elapsed_ms)
        except RephraseError as exc:
            repo.mark_rephrase_status(src, ext, "failed")
            logger.warning("fail {}:{} — {}", src, ext, exc)
    return len(rows)


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--db", required=True)
    ap.add_argument("--once", action="store_true", help="один проход и выход")
    ap.add_argument("--backfill", action="store_true", help="брать и старые записи (status IS NULL)")
    ap.add_argument("--retry-failed", action="store_true")
    ap.add_argument("--batch", type=int, default=20)
    ap.add_argument("--interval", type=float, default=15.0, help="пауза между проходами, сек")
    args = ap.parse_args()

    repo = SharedListingRepository(args.db)
    client = RephraseClient()
    if not client.health():
        logger.warning("ai_service недоступен на старте — жду, буду ретраить")

    while True:
        processed = run_once(repo, client, args.batch, args.backfill, args.retry_failed)
        if args.once:
            break
        time.sleep(args.interval if processed == 0 else 1.0)


if __name__ == "__main__":
    main()
```

### 8.6. Запуск на VPS (`217.26.28.45`)

`ai_service` уже крутится на `:8100`. Воркер — отдельная systemd-служба (или таймер),
рядом с `orion-web.service` (память [[project_orion_deploy]]):

```ini
# /etc/systemd/system/orion-rephrase.service
[Unit]
Description=aviton rephrase worker
After=network.target
[Service]
WorkingDirectory=/root/aviton
ExecStart=/root/aviton/.venv/bin/python -m shared_storage.rephrase_worker \
          --db /root/aviton/shared_listings.db
Environment=AI_SERVICE_URL=http://127.0.0.1:8100
Restart=always
RestartSec=10
[Install]
WantedBy=multi-user.target
```

Разовый прогон старых 2000+ объявлений: `--once --backfill` (или таймером ночью).

### 8.7. UI: показывать перефразированный текст

`web/orion/services/listing_normalizer.py`, функция `normalize_row` — строка

```python
description=row.get("description"),
```

заменить на (ИИ-текст, если готов; иначе оригинал):

```python
description=row.get("description_ai") or row.get("description"),
```

`fetch_feed`/`fetch_one` делают `SELECT *`, так что новая колонка приедет в `row`
автоматически — больше в вебе править ничего не нужно.

### 8.8. Порядок внедрения и проверка

1. Правки §8.2 → при первом старте любого парсера/веба колонки и индекс создадутся
   автоматически (идемпотентный `_init_db`). Проверка:
   `PRAGMA table_info(listings)` — есть `description_ai*`.
2. Добавить `shared_storage/rephrase.py` (§8.3) и методы репозитория (§8.4).
3. Добавить `shared_storage/rephrase_worker.py` (§8.5), поднять службу (§8.6).
4. Правка UI (§8.7).
5. Смоук-тест: спарсить 1–2 объявления → `description_ai_status='pending'` →
   воркер → `status='ok'`, `description_ai` заполнен, `description` (оригинал) цел.
   На карточке сайта виден продающий текст.

> **Инварианты:** оригинал в `description` не перезаписывается никогда; при смене
> оригинала ИИ-текст перегенерируется; падение `ai_service` не роняет ни парсинг,
> ни сохранение — объявление просто ждёт в `pending`/`failed` и ретраится.
