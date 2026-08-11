<p align="center">
  <img src="public/assets/logos/logo-green.svg" width="180" alt="Dersey logo">
</p>

<h1 align="center">Dersey</h1>

<p align="center">A single-vendor, bilingual (AR/EN) fashion e-commerce platform for the Egyptian market.</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/MySQL-8-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL 8">
  <img src="https://img.shields.io/badge/Tailwind_CSS-v4-38BDF8?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind CSS v4">
  <img src="https://img.shields.io/badge/License-MIT-blue?style=flat-square" alt="License MIT">
</p>

## About

Dersey is a B2C fashion storefront for a single vendor, selling final-sale items (no returns/exchange workflow) in the Egyptian market. It's bilingual (Arabic/English) with full RTL support, priced exclusively in Egyptian Pounds (EGP), and integrates Paymob for payments. The storefront and a custom admin panel share the same codebase, with an architecture built around SEO from the start.

## Key Features

- Bilingual & RTL-native — Arabic and English throughout, with logical CSS properties instead of hardcoded left/right
- SEO-first — structured metadata and sitemap generation are part of the architecture, not an afterthought
- Variant-driven catalog — products are modeled around variants (size/color/etc.), not flat SKUs
- Paymob payments in EGP — card and wallet integrations, no other currencies or gateways
- Custom admin panel — hand-built, not a scaffolded admin package
- Motion by design — GSAP/Lenis/Swiper power intentional, considered animation rather than default framework transitions

## Stack

| Layer | Technology | Version |
|---|---|---|
| Backend | Laravel | 12.65.0 |
| Backend | PHP | 8.2.12 (8.2+ required) |
| Backend | MySQL | 8 |
| Backend | Redis (via predis) | 3.5.1 |
| Frontend | Tailwind CSS | v4 (4.3.3) |
| Frontend | jQuery | 4.0.0 |
| Motion | GSAP | 3.15.0 |
| Motion | Lenis | 1.3.26 |
| Motion | Swiper | 14.1.0 |
| Admin | FilePond (+ image preview/crop/type-validate plugins) | 4.32.12 |
| Admin | Tom Select | 2.6.2 |
| Admin | CKEditor 5 | 48.4.0 |
| Admin | Chart.js | 4.5.1 |
| Admin | SortableJS | 1.15.7 |
| Admin | Flatpickr | 4.6.13 |
| Storage | Cloudflare R2 (via league/flysystem-aws-s3-v3) | 3.35.2 |
| Storage | spatie/laravel-medialibrary | 11.23.3 |
| Monitoring | Sentry (sentry/sentry-laravel) | 4.27.0 |

## Architectural Decisions

| Decision | Why |
|---|---|
| Custom admin panel | No Filament/Livewire allowed — the admin UI is hand-built with Blade + jQuery instead of a scaffolded admin package |
| Hybrid translations | `spatie/laravel-translatable` for database content (product names, descriptions), Laravel lang files for UI strings, `mcamara/laravel-localization` for locale-aware routing |
| Mandatory product variants | The catalog is modeled around variants from the start — no separate "simple product" code path to maintain |
| CKEditor 5 (GPL) | Installed as the npm package (not the deprecated CDN builds), with `licenseKey: 'GPL'` required from v44 onward |
| Cloudflare R2 for storage | S3-compatible object storage via `league/flysystem-aws-s3-v3`, avoiding AWS egress costs |
| Redis versioned cache | Cache keys embed a per-tag version number bumped on model changes — invalidation is always targeted, `Cache::flush()` is never used |
| Sentry for monitoring | Single error-tracking service, with mandatory PII scrubbing (`before_send`) before any event leaves the app |
| Integer minor units for money | All amounts are stored as `unsignedBigInteger` piasters through a `Money` value object — no `float`/`decimal` for money anywhere |
| Single-vendor B2C | One seller, one currency (EGP), final sale — no multi-vendor or returns/exchange workflow in scope |
| No JS framework except jQuery | All interactivity and Ajax go through jQuery; every other JS library in use (GSAP, Swiper, etc.) is a vanilla-JS utility, never a reactive framework |

## Internationalization

- `/ar` and `/en` are both prefixed — there is no unprefixed default locale. The bare `/` 302-redirects (never 301, since the destination depends on `Accept-Language`/cookie state, and a permanently cached 301 would trap a visitor on the wrong locale) to the visitor's resolved locale.
- RTL and LTR share a single stylesheet — CSS uses logical properties (`ps-`/`pe-`/`ms-`/`me-`/`start-`/`end-`) exclusively, never `left`/`right`.
- `hreflang` alternates, `x-default` (always the Arabic URL), and an absolute `canonical` are generated automatically per page by `SeoService` — not hand-written per view.
- `/admin` sits entirely outside the locale system — fixed Arabic, no `/ar`/`/en` prefix, no locale middleware.

## Design System

- 4 self-hosted webfonts (Clash Display, Satoshi, Alexandria, IBM Plex Sans Arabic), subsetted per-glyph-set with `pyftsubset`: **653KB → 238KB** across the weights actually shipped.
- Colors are OKLCH tokens (`resources/css/theme.css`), verified against WCAG AA contrast — no hardcoded hex anywhere in the codebase.
- Token names must match the namespace Tailwind v4 actually generates utilities from (`--color-*`, `--duration-*`, `--z-index-*`, `--width-*`), not just read as semantically correct — a mismatched namespace (e.g. `--z-*` instead of `--z-index-*`) silently produces no utility at all, verified by building and grepping compiled output rather than assumed.
- Font preloading is locale-conditional: an Arabic page preloads only the Arabic faces, an English page only the Latin faces — never both.
- Production CSS bundles (`npm run build`): `app.css` 52.2 kB (9.5 kB gzip), `admin.css` 50.3 kB (9.3 kB gzip) — both grew with the Batch 1.6 component library, since Tailwind's content scan isn't scoped per-bundle and any class used anywhere is available to both.

## Frontend Architecture

- **jQuery only** — no JS framework (Vue/React/Alpine/Livewire) anywhere in the storefront or admin bundles. Every interactive piece is a small, selectively-initialized module in `resources/js/modules/`, wired up only when its root element is present on the page.
- **Unified Ajax layer** (`core/ajax.js`) — every request in the app goes through one wrapper: automatic CSRF-token refresh-and-retry on a 419, consistent handling for 422/429/500, and duplicate in-flight requests sharing a key cancel each other (`.abort()`) instead of racing.
- **Layered overlay manager** (`core/modal.js`) — modals, drawers, and the search overlay share one open/close stack instead of each reimplementing focus-trap/scroll-lock logic: `Escape` closes only the top-most layer, and page-scroll lock is reference-counted so it's released only once every open layer has closed, not on the first one.
- **Motion is deferred, not skipped** — GSAP and Lenis are dynamically imported after `window.load`, and only when `prefers-reduced-motion` is not set; under reduced motion, that bundle is never requested at all.
- **Bundle sizes** (`npm run build`, gzip): the initial JS payload (jQuery, the Ajax/overlay/toast core, and every storefront interface module — header, mega menu, mobile nav, cart drawer, search) is **~37.3 kB**; the deferred motion bundle (GSAP + Lenis) is **~50.3 kB**, fetched only when actually needed.

## Component Library

- 23 reusable Blade components in three groups: `resources/views/components/form/` (8 — input, textarea, select, checkbox, radio, toggle, quantity, file), `resources/views/components/ui/` (14 — button, badge, chip, alert, spinner, skeleton, tooltip, card, breadcrumb, pagination, empty-state, rating, tabs, accordion), and `product-card.blade.php` on its own (product data doesn't belong in a generic UI namespace).
- Form components always use `border-interactive`, never `border-line`, and wire up `aria-invalid`/`aria-describedby` automatically whenever an error is passed in.
- A permanent `/design-system` preview page shows every component in every state — local environment only, the route doesn't exist at all outside it (verified by checking `php artisan route:list` under `APP_ENV=production`, not just reading the guard condition). It sits outside the locale system like `/admin`, and its RTL/LTR toggle switches the actual locale too, not just the `dir` attribute, so each direction is tested with real matching-language content.
- `--z-index-tooltip` (1500) sits above every other layer, including `modal`/`drawer` (1300/1200) — a tooltip is always anchored to a trigger that can itself be inside one of them.

## Data Layer

- **Hybrid translations** — separate `{model}_translations` tables (`App\Support\Traits\HasTranslations`) for indexed/searchable content, `spatie/laravel-translatable` (JSON columns) for everything else, like governorate/city names. `withCurrentTranslation()` is mandatory before reading a translated attribute: `Model::preventLazyLoading()` (on outside production since Batch 1.1) turns a forgotten eager-load into an immediate `LazyLoadingViolationException` in development instead of a silent N+1 — confirmed at exactly 2 queries regardless of row count when the scope is used, and 0 queries before the exception otherwise.
- **Money stays integer** — every amount is `unsignedBigInteger` piasters through the `Money` value object, no exceptions.
- **10 domain enums** (`App\Enums\*`), each backed by `string` and implementing `HasEnumOption` (`label()` from `lang/enums.php`, `color()` mapping to an `x-ui.badge` variant). `OrderStatus` is the one with real state-machine behavior: `canTransitionTo()` is the only legal way to move between statuses (a delivered order can never go back to pending), `isFinal()` for `cancelled`/`returned`.
- **Three-tier delete strategy** — soft deletes for the catalog and people (`users`, `admins`, `addresses`); hard deletes for pivot/temporary tables only; **financial records are never deleted at all** — cancellation is a status (`OrderStatus::Cancelled`), not a row removal.
- **27 Egyptian governorates + 165 cities**, seeded with bilingual names (`GovernorateSeeder`). Addresses reference geography with `restrict`, not `cascade` — deleting a governorate with addresses on it fails outright.
- **Settings load once into Redis**, keyed via the existing `CacheKeys::settings()`/`VersionedCache` machinery, invalidated automatically by `SettingObserver` on every write. `migrate:fresh` does not clear Redis — `RolePermissionSeeder` explicitly forgets spatie/permission's own cache at the start of its run for exactly this reason.
- **Catalog is nested-set and variants-ready** — `categories` uses `kalnoy/nestedset` (root → sub-category → leaf, 3 levels deep in seed data), with attributes split by `is_variant` (size/color generate purchasable variants in a later batch; material/season are filter-only). Deleting a category with live children or products still directly attached to it is blocked outright (`CategoryHasDependentsException`, `Category::canBeDeleted()`), never silently cascaded.
- **Real Arabic slugs** — `category_translations`/`brand_translations`/`product_translations` use `UNIQUE(slug, locale)`, not `UNIQUE(slug)`: the same slug string can exist once per language, and Arabic slugs keep native Arabic characters (`Str::slug($text, '-', null)`) instead of being transliterated to Latin, for better local SEO.
- **Arabic FULLTEXT search** — `product_translations` has a `FULLTEXT` index on `name`/`description`, verified against real Arabic text on the local MariaDB instance; skipped automatically on SQLite (the test suite's driver has no `fullText()` support at all), so MySQL/production always gets the real index.

## ⚠️ Engineering Constraints

These are hard constraints, not preferences:

- **No Livewire, Filament, Inertia, Vue, React, or Alpine.js** — anywhere, including as indirect dependencies
- **jQuery is the only library responsible for interactivity and Ajax**
- **All monetary amounts are integers (piasters)** through the `Money` value object — `float`/`decimal` for money is never allowed
- **`Cache::flush()` is never used**, and `stock_quantity` is never cached
- **CSS uses logical properties only** (`ps-`, `pe-`, `ms-`, `me-`, `start-`, `end-`) — no `left`/`right`
- **No hardcoded text in Blade views** — everything comes from language files

See [CLAUDE.md](CLAUDE.md) for the full set of project conventions.

## Getting Started

### Requirements

- PHP 8.2+
- MySQL 8
- Redis
- Node 20+
- Composer 2
- `pyftsubset` (`pip install fonttools brotli`) — only needed to run `scripts/subset-fonts.sh` when adding or changing a font weight; not required for day-to-day development

### Installation

```bash
git clone <repository-url> dersey
cd dersey

composer install
npm install

cp .env.example .env
php artisan key:generate

# configure DB, Redis and other credentials in .env, then:
php artisan migrate:fresh --seed

npm run build   # or `npm run dev` for local development
php artisan serve
```

Seeding creates the 27 governorates/165 cities, base settings, and the admin roles/permissions. The default super-admin account is **not** created unless `SUPER_ADMIN_EMAIL`/`SUPER_ADMIN_PASSWORD` are set in `.env` first (see `.env.example` — no real credentials are ever committed); `RolePermissionSeeder` skips account creation and prints a warning if either is left empty.

### Environment Variables

Key names only — see `.env.example` for the full list, all sensitive values ship empty.

| Group | Keys |
|---|---|
| App | `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`, `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_TIMEZONE` |
| Database | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| Cache / Session / Queue (Redis) | `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION`, `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`, `REDIS_CACHE_DB`, `REDIS_SESSION_DB`, `REDIS_QUEUE_DB` |
| Storage (Cloudflare R2) | `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, `R2_BUCKET_PRIVATE`, `R2_ENDPOINT`, `R2_PUBLIC_URL` |
| Payments (Paymob) | `PAYMOB_PUBLIC_KEY`, `PAYMOB_SECRET_KEY`, `PAYMOB_HMAC_SECRET`, `PAYMOB_INTEGRATION_CARD`, `PAYMOB_INTEGRATION_WALLET`, `PAYMOB_BASE_URL`, `PAYMOB_MODE` |
| Monitoring | `SENTRY_LARAVEL_DSN`, `SENTRY_TRACES_SAMPLE_RATE`, `SENTRY_PROFILES_SAMPLE_RATE`, `SENTRY_ENVIRONMENT`, `LOG_SLACK_WEBHOOK_URL` |
| Backup | `BACKUP_ARCHIVE_PASSWORD`, `BACKUP_DISK` |
| Store | `STORE_CURRENCY`, `STORE_PHONE`, `STORE_EMAIL` |
| Super-admin seed | `SUPER_ADMIN_NAME`, `SUPER_ADMIN_EMAIL`, `SUPER_ADMIN_PASSWORD` |

## Project Status

| Phase | Status |
|---|---|
| Phase 1 — Project & Infrastructure Foundation (Batch 1.1) | ✅ Complete |
| Phase 1 — Design System & Fonts (Batch 1.2) | ✅ Complete |
| Phase 1 — Internationalization (Batch 1.3) | ✅ Complete |
| Phase 1 — JavaScript & Ajax Infrastructure (Batch 1.4) | ✅ Complete |
| Phase 1 — Storefront Interface Shell (Batch 1.5) | ✅ Complete |
| Phase 1 — Component Library (Batch 1.6) | ✅ Complete |
| **Phase 1 — Foundation** | **✅ Complete** |
| Phase 2 — Data Layer Foundation (Batch 2.1) | ✅ Complete |
| Phase 2 — Product Catalog (Batch 2.2) | ✅ Complete |
| Phase 2 — remaining batches | Planned |
| **Phase 2** | **🔄 In Progress** |
| Phase 3+ | Planned |

Current test suite: **57 tests, 158 assertions** passing (`php artisan test`). Repository history: **84 commits**.

## Known Issues / Environment Notes

- **PHP 8.2.12 locally** — this is the minimum version Laravel 12 accepts, but **production must run PHP 8.3+**.
- **Local MySQL is actually MariaDB** — the local XAMPP install reports `MariaDB 10.4.32`, not MySQL 8. It works via the same `mysql` driver, but don't assume MySQL-8-only SQL features work locally without checking against a real MySQL 8 instance.
- **Horizon does not run on Windows** — it requires the `ext-pcntl` and `ext-posix` PHP extensions, which don't exist in Windows PHP builds. Locally, use `php artisan queue:work`; Horizon is for the VPS only.
- **Laravel Pulse was removed** — it forces `livewire/livewire` as a hard dependency, which directly conflicts with the "no Livewire" constraint. Monitoring currently relies on **Sentry only**; the Pulse decision is deferred to Phase 8.
- **`FILESYSTEM_DISK=local`** currently — this switches to `media` (Cloudflare R2) at deploy time; the storage layer is written against the same disk interface either way.
- **`/admin` has a temporary ping route** (`TODO(3.0)`) proving it sits outside the locale system — it returns a bare 200 with no real admin UI yet, and is replaced by the actual admin routes in Batch 3.0.
- **`resources/fonts/_source/` is not in the repo** — it's the build input for `scripts/subset-fonts.sh` (gitignored, ~1.2MB of full font families). Source downloads: Clash Display and Satoshi from Fontshare, Alexandria and IBM Plex Sans Arabic from Google Fonts.
- **Footer payment-method badges are plain text** (Visa/Mastercard/meeza/Fawry), not real logos — no payment-network brand assets exist in the project yet.
- **The mobile navigation drawer has no cart entry point of its own** — its backdrop correctly blocks pointer events to whatever is behind it, including the header's cart icon, so once the cart is functional the drawer will need a direct link to it.
- **Product-card color swatches are placeholders** drawn from the existing semantic tokens (primary/accent/ink/...), not real per-product colors — there is no Color model yet; real swatch data needs one, planned for Phase 2.
- **`migrate:fresh` does not clear Redis** — cached data (like spatie/permission's role/permission cache) survives a table drop and can point at now-invalid IDs. `RolePermissionSeeder` already handles this; any new seeder that reads from a cache needs to account for it too.
- **Permissions exist for resources with no tables yet** (`products.*`, `orders.*`, ...) — deliberately seeded ahead of the tables/UI that will actually enforce them, which land in later Phase 2 batches.
- **`kalnoy/nestedset` defines `parent_id` as 32-bit (`unsignedInteger`) while `categories.id` is 64-bit (`unsignedBigInteger`)** — the package's own `nestedSet()` macro caused a real FK failure (`errno 150`) on `categories.parent_id`; `_lft`/`_rgt`/`parent_id` are defined by hand in the migration instead, with `parent_id` matching `id`'s width.
- **The installed `kalnoy/nestedset` version has no `depth` column** — only `_lft`/`_rgt`, with depth computed via `->withDepth()` at query time; confirmed by reading the package source directly, not assumed from its docs.
- **SQLite has no `fullText()` index support** — the test suite's `DB_CONNECTION` (`phpunit.xml`) is SQLite in-memory, so `product_translations`' FULLTEXT index is skipped there (`Schema::getConnection()->getDriverName() !== 'sqlite'`); MySQL/production is unaffected.

## Documentation

- [CLAUDE.md](CLAUDE.md) — project conventions and technical constraints for contributors
