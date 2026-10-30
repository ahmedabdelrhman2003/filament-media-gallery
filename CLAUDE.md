# filament-media-gallery — Claude Code Reference

## Project Overview

**Package:** `ahmed-abdelrhman/filament-media-gallery`
**Type:** Filament plugin (library)
**Purpose:** Renders a [Spatie Laravel Media Library](https://spatie.be/docs/laravel-medialibrary) collection as a grid gallery inside a Filament **Infolist** entry, with a built-in Alpine.js lightbox for images and direct-link cards for PDFs/other files.

- **PHP:** ^8.2
- **Filament:** ^3.0 || ^4.0 || ^5.0
- **Spatie Media Library:** ^10.0 | ^11.0
- **No extra JS.** Ships a precompiled Tailwind stylesheet (`resources/dist/media-gallery.css`) registered via `FilamentAsset`; users run `php artisan filament:assets`. Filament's own CSS does **not** contain this plugin's utility classes (see GitHub issue #1).

---

## Directory Structure

```
filament-media-gallery/
├── composer.json                                    # Package metadata & autoloading
├── package.json / tailwind.config.js                # Dev-only: builds resources/dist CSS (npm run build)
├── README.md                                        # End-user documentation
├── LICENSE
├── src/
│   ├── FilamentMediaGalleryPlugin.php               # Filament Plugin contract implementation
│   ├── FilamentMediaGalleryServiceProvider.php      # View + component + CSS asset registration
│   ├── ImageGenerators/
│   │   ├── PdfThumbnail.php                         # Optional Spatie generator: PDF page 1 → JPEG via pdftoppm/gs
│   │   └── OfficeThumbnail.php                      # Optional: Office → PDF via soffice, then PdfThumbnail (untested)
│   └── Infolists/
│       └── Components/
│           └── MediaGalleryEntry.php                # Main infolist component (public API)
└── resources/
    └── views/
        ├── infolist-entries/
        │   └── media-gallery.blade.php              # Thin wrapper rendered by Filament
        └── components/
            └── media-viewer.blade.php               # Full gallery UI + Alpine.js lightbox
```

---

## Architecture & Data Flow

```
Filament Infolist schema
  → MediaGalleryEntry::make('field')        [src/Infolists/Components/MediaGalleryEntry.php]
      → renders view: media-gallery.blade.php
          → calls $entry->getMediaItems()   [loads via Spatie getMedia($collection)]
          → passes items + config to <x-media-gallery-viewer>
              → media-viewer.blade.php      [renders grid, lightbox, PDF cards]
                  → Alpine.js handles all client-side interactions (no server calls)
```

---

## Key Files & Roles

### `src/FilamentMediaGalleryPlugin.php`
Implements `Filament\Contracts\Plugin`. Required for explicit panel registration.
- `make()` — static factory
- `getId()` — returns `'filament-media-gallery'`
- `register(Panel $panel)` / `boot(Panel $panel)` — empty hooks, available for future panel-level config

### `src/FilamentMediaGalleryServiceProvider.php`
Standard Laravel service provider, auto-discovered.
- Loads views from `resources/views` under namespace `media-gallery`
- Registers Blade component alias: `<x-media-gallery-viewer>` → `media-gallery::components.media-viewer`
- Registers `Css::make('media-gallery', resources/dist/media-gallery.css)` under package `ahmed-abdelrhman/filament-media-gallery`
- Publishes views via `--tag=media-gallery-views`

### `src/Infolists/Components/MediaGalleryEntry.php`
The **main API class** users interact with. Extends `Filament\Infolists\Components\Entry`.

| Property | Type | Default | Description |
|---|---|---|---|
| `$collection` | string | — | Spatie collection name (required) |
| `$relation` | string\|null | null | Related model name for media loading |
| `$size` | int\|null | 250 | Fixed card size in px; null = responsive |
| `$rounded` | bool | false | `rounded-full` vs `rounded-lg` |

**`getMediaItems()`** — core loader:
1. Gets the infolist record
2. If `fromRelation()` set, navigates to that relation first
3. Calls `$model->getMedia($collection)` (Spatie)
4. Returns `Collection` of media items (empty if none)

### `resources/views/infolist-entries/media-gallery.blade.php`
Thin Filament wrapper using `<x-dynamic-component :component="$getEntryWrapperView()">`. Calls `$entry->getMediaItems()` and delegates rendering to `<x-media-gallery-viewer>`. Do not add logic here.

### `resources/views/components/media-viewer.blade.php`
The full UI component — the most complex file in the project.

**Props:** `$media` (Collection), `$size` (int|null), `$rounded` (bool)

Rendering logic:
1. `$describe()` classifies each item: image | video | pdf | word | excel | powerpoint | file
2. Images, videos and PDFs are appended to `$lightboxItems[]` (`{type, src, thumb, name?}`), passed once via `@js` into `x-data`
3. Computes `$cardStyle` (inline fixed dimensions), `$cardClass` (aspect-square if responsive), `$roundedClass`
4. Images: rendered with thumbnail → preview → full URL priority; magnifier icon on hover; click opens lightbox
5. Videos: muted `<video preload=metadata>` preview + play icon; lightbox renders `<video controls autoplay>` inside `x-if` so it's destroyed on navigate/close
6. PDFs: red icon card → lightbox renders `<iframe src=url#toolbar=0>` (native viewer, scrollable) with an open-in-new-tab button
7. Office/other documents: coloured icon per type + extension badge, opens in new tab on click
6. Shows "No files uploaded." if collection empty

**Alpine.js lightbox state** (defined in `x-data`):
```js
{
  lightboxOpen: false,
  lightboxItems: [...],   // {type:'image'|'video'|'pdf', src, thumb, name?}
  lightboxIndex: 0,
  get current(),
  openLightbox(index),    // locks body scroll
  closeLightbox(),
  goTo(index), nextImage(), prevImage(),   // circular
  scrollThumb(index),     // uses [data-thumb] — NOT children[i] (template element is child 0)
}
```
- ESC key closes lightbox
- Clicking image or backdrop closes lightbox
- Prev/Next buttons and counter badge only appear when 2+ images

---

## Public API

```php
MediaGalleryEntry::make('gallery')          // field name (for label)
    ->collection('photos')                  // REQUIRED: Spatie collection name
    ->fromRelation('user')                  // optional: load from $record->user->getMedia(...)
    ->size(200)                             // optional: fixed px size (null = responsive)
    ->rounded()                             // optional: circular cards
    ->label('Gallery')                      // inherited from Filament Entry
```

---

## Adding New Features — Where to Touch

| What you want to add | Files to change |
|---|---|
| New config option (e.g. `->columns(3)`) | 1. Add property + getter/setter to `MediaGalleryEntry.php` <br>2. Pass as prop in `media-gallery.blade.php` <br>3. Use prop in `media-viewer.blade.php` |
| Support a new file type | Add a match arm to `$describe` in `media-viewer.blade.php` (kind, label, icon, colours) |
| Any class change in a blade file | Run `npm run build` and commit `resources/dist/media-gallery.css`, or the class won't exist at runtime |
| Change lightbox behaviour | Edit Alpine `x-data` object in `media-viewer.blade.php` |
| Panel-level plugin config | Add to `FilamentMediaGalleryPlugin.php` (register/boot) |
| Change how media is loaded | Edit `getMediaItems()` in `MediaGalleryEntry.php` |

---

## Development Notes

- **No routes, no config files, no migrations** — purely view/component based.
- **Publish views for customisation:**
  ```bash
  php artisan vendor:publish --tag=media-gallery-views
  ```
  Views land in `resources/views/vendor/media-gallery/`.
- **Version compatibility** is handled by Filament's stable `Entry` and `Plugin` contracts — zero version-switch code is needed. The composer constraint `^3.0 || ^4.0 || ^5.0` covers all.
- **Thumbnail priority** in `media-viewer.blade.php` (all types): `thumbnail` conversion → `preview` conversion → original (images only) → null (icon card). Non-image thumbnails exist only when the host app registers an image generator (`src/ImageGenerators/*`). If you add new Spatie conversions, update this priority chain.
- **Video seeking** needs HTTP Range support on the file server; `php artisan serve` lacks it (scrubber snaps back). Not a plugin bug.
- **Dark mode** is supported throughout via Tailwind `dark:` variants — keep this in mind when editing blade files.
