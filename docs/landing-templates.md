# Landing templates and the click editor

A landing page can be edited in place: the owner opens `/l/{slug}?edit=1`, clicks a text or a
photo and changes it. The editor is one tool shared by every template, so a template only
declares what is editable. Nothing else is written per template: no JS, no API, no validation.

```
resources/landing-templates/<slug>.php     manifest: what the template is and which keys/photos it exposes
resources/views/landings/full/<slug>.blade.php   the page (full-page layouts)
public/landing-templates/<slug>/           css, js, img and the original LICENSE
app/Services/Landing/TemplateRegistry.php  finds manifests; the wizard and validation read it
app/Services/Landing/LandingContent.php    field catalog, reading values, validation, saving
app/Services/Landing/LandingImageStore.php photo upload (GD re-encode, 1600px, EXIF dropped)
resources/views/components/landing/        <x-landing.text>, <x-landing.image>
public/landing-editor/editor.{js,css}      the editor itself, loaded only for the owner
```

## Templates now available

- `salone`: warm golden tones (HTML Codex, CC BY 4.0).
- `pretty`: light page with a pink booking block (Colorlib via ThemeWagon, CC BY 3.0).

The former classic layout (`landings.templates.*`) was retired; pages that still pointed at it fall back
to the default template (`TemplateRegistry::DEFAULT_LAYOUT`) and a migration moved the stored values.

## Add a template

```bash
docker compose exec app php artisan landing:make-template spa-soft \
  --name="Spa Soft" --source=https://example.com/template --license="CC BY 4.0"
```

It creates the manifest, a Blade starter already wired to the booking form and the editor, and the
asset folder. Then:

1. Copy the template's css/js/img to `public/landing-templates/<slug>/` together with its `LICENSE`.
2. Move its sections into the Blade view. Replace:
   - a text with `<x-landing.text key="hero_title" tag="h1" class="…" :default="$landing->title" />`
   - a list item with `<x-landing.text key="proof_items_text" :index="$loop->index" tag="p" />`
   - a photo with `<x-landing.image key="hero_image_1" default="landing-templates/<slug>/img/a.jpg" class="…" alt="" />`
3. List every key and photo you used in the manifest (`fields`, `images`).
4. Add `preview.jpg` (480×316) and set `thumb` in the manifest. The wizard shows the layout by itself.
5. `php artisan test --filter=LandingEditorTest`. The smoke test renders every registered template
   for all landing types and checks that visitors get no editor markup and the owner does.

## Checklist for a third-party template

- **License.** Read it. If it needs attribution (CC BY), keep the credit link in the footer and the
  original `LICENSE` in the asset folder. Do not present the template as ours.
- **Cyrillic.** The fonts must cover Cyrillic; many free templates use Latin-only faces. Swap them.
- **No invented content.** Remove placeholder people, reviews and figures ("25 years, 999 clients").
  A master must not publish claims that are not hers.
- **Real data only.** Services, prices and durations come from the catalog. Contacts come from
  settings. Nothing is hard-coded.
- **The booking form** is a shared widget: include `landings.partials.request-script` with your element
  ids (form, button, message, service select, an empty `picker` element) and give the phone input
  `data-phone-mask`. It draws the master's free windows (from her schedule in settings, minus what is
  booked), books one through `POST /l/{slug}/book`, falls back to a plain request when no time is
  picked and shows the confirmation window. Name the form fields `client_name`, `client_phone`,
  `service_id`, `message`. Pass `accent` / `accentText` colors to match the template.
- **Mobile first.** Most visits come from a phone (Instagram, Telegram). Check 390 px.
- **Weight.** Keep the page light; load only the libraries the layout uses.
- Fields that only exist as icons (Telegram, WhatsApp) are not click-editable yet.

## Manifest reference

```php
return [
    'slug' => 'spa-soft',
    'name' => 'Spa Soft',                    // or a lang key
    'description' => 'One line for the wizard',
    'full_page' => true,                     // every template is a whole page with its own <html>
    'thumb' => 'landing-templates/spa-soft/preview.jpg',
    'templates' => ['general' => $view, 'promotion' => $view, /* … every type */],
    'images' => ['hero_image_1' => 'landing-templates/spa-soft/img/hero.jpg'],   // stock photo per slot
    'keys' => ['hero_text' => ['general' => 'subtitle']],   // optional: where a virtual key is stored
    'fields' => ['title', 'hero_title', 'hero_text', 'cta_label', 'phone', 'proof_items_text', 'hero_image_1'],
];
```

Field kinds and limits live in `LandingContent::FIELDS`. To make a **new kind of content** editable
add it there once (kind, max length) and every template can use it.

`hero_title` and `hero_text` are virtual: they resolve to `headline` / `description` /
`service_description` / `greeting` depending on the landing type, so a template does not have to
know. `title` is the landing's own title.

## How saving works

- `PATCH /api/v1/landings/{id}/content` with `{changes: [{key, value}]}`. Keys are limited to the
  template's manifest, values are validated by kind, `settings` are merged (never replaced),
  the changed keys are remembered in `settings._edited`.
- `POST /api/v1/landings/{id}/images` (`key`, `file`) and `DELETE …/images/{key}`. JPG, PNG or WebP up
  to 8 MB, at most 12 photos per landing. Files live in `public/uploads/landings/{id}` (disk
  `landing_media`, no `storage:link` needed), the path is in `settings.images.{key}`.
- Only the owner gets the editor (`?edit=1` is ignored for everybody else). Everything is escaped
  on output; the editor sends plain text only.

## Deploy notes

- The `app` image needs the `gd` and `exif` extensions (see `Dockerfile`). Rebuild it once:
  `docker compose build app && docker compose up -d app nginx`, then `docker compose restart nginx`
  so the new `client_max_body_size` is read.
- `public/uploads` must be writable by the PHP user and is git-ignored. Back it up with the database.
