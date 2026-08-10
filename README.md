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
- Production CSS bundles (`npm run build`): `app.css` 42.3 kB (8.0 kB gzip), `admin.css` 40.4 kB (7.8 kB gzip).

## Frontend Architecture

- **jQuery only** — no JS framework (Vue/React/Alpine/Livewire) anywhere in the storefront or admin bundles. Every interactive piece is a small, selectively-initialized module in `resources/js/modules/`, wired up only when its root element is present on the page.
- **Unified Ajax layer** (`core/ajax.js`) — every request in the app goes through one wrapper: automatic CSRF-token refresh-and-retry on a 419, consistent handling for 422/429/500, and duplicate in-flight requests sharing a key cancel each other (`.abort()`) instead of racing.
- **Layered overlay manager** (`core/modal.js`) — modals, drawers, and the search overlay share one open/close stack instead of each reimplementing focus-trap/scroll-lock logic: `Escape` closes only the top-most layer, and page-scroll lock is reference-counted so it's released only once every open layer has closed, not on the first one.
- **Motion is deferred, not skipped** — GSAP and Lenis are dynamically imported after `window.load`, and only when `prefers-reduced-motion` is not set; under reduced motion, that bundle is never requested at all.
- **Bundle sizes** (`npm run build`, gzip): the initial JS payload (jQuery, the Ajax/overlay/toast core, and every storefront interface module — header, mega menu, mobile nav, cart drawer, search) is **~37.3 kB**; the deferred motion bundle (GSAP + Lenis) is **~50.3 kB**, fetched only when actually needed.

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
php artisan migrate

npm run build   # or `npm run dev` for local development
php artisan serve
```

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

## Project Status

| Phase | Status |
|---|---|
| Phase 1 — Project & Infrastructure Foundation (Batch 1.1) | ✅ Complete |
| Phase 1 — Design System & Fonts (Batch 1.2) | ✅ Complete |
| Phase 1 — Internationalization (Batch 1.3) | ✅ Complete |
| Phase 1 — JavaScript & Ajax Infrastructure (Batch 1.4) | ✅ Complete |
| Phase 1 — Storefront Interface Shell (Batch 1.5) | ✅ Complete |
| Phase 1 — remaining batches | Planned |
| Phase 2+ | Planned |

Current test suite: **30 tests, 78 assertions** passing (`php artisan test`). Repository history: **62 commits**.

## Known Issues / Environment Notes

- **PHP 8.2.12 locally** — this is the minimum version Laravel 12 accepts, but **production must run PHP 8.3+**.
- **Local MySQL is actually MariaDB** — the local XAMPP install reports `MariaDB 10.4.32`, not MySQL 8. It works via the same `mysql` driver, but don't assume MySQL-8-only SQL features work locally without checking against a real MySQL 8 instance.
- **Horizon does not run on Windows** — it requires the `ext-pcntl` and `ext-posix` PHP extensions, which don't exist in Windows PHP builds. Locally, use `php artisan queue:work`; Horizon is for the VPS only.
- **Laravel Pulse was removed** — it forces `livewire/livewire` as a hard dependency, which directly conflicts with the "no Livewire" constraint. Monitoring currently relies on **Sentry only**; the Pulse decision is deferred to Phase 8.
- **`FILESYSTEM_DISK=local`** currently — this switches to `media` (Cloudflare R2) at deploy time; the storage layer is written against the same disk interface either way.
- **`/admin` has a temporary ping route** (`TODO(3.0)`) proving it sits outside the locale system — it returns a bare 200 with no real admin UI yet, and is replaced by the actual admin routes in Batch 3.0.
- **`resources/fonts/_source/` is not in the repo** — it's the build input for `scripts/subset-fonts.sh` (gitignored, ~1.2MB of full font families). Source downloads: Clash Display and Satoshi from Fontshare, Alexandria and IBM Plex Sans Arabic from Google Fonts.
- **`/design-test` and `/js-test` are temporary pages** (`TODO(1.6)`) verifying the design-token and JS-infrastructure batches respectively — both views and their routes are removed in Batch 1.6.
- **Footer payment-method badges are plain text** (Visa/Mastercard/meeza/Fawry), not real logos — no payment-network brand assets exist in the project yet.
- **The mobile navigation drawer has no cart entry point of its own** — its backdrop correctly blocks pointer events to whatever is behind it, including the header's cart icon, so once the cart is functional the drawer will need a direct link to it.

## Documentation

- [CLAUDE.md](CLAUDE.md) — project conventions and technical constraints for contributors
