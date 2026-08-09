# Dersey

## 1. وصف المشروع

**Dersey** متجر أزياء إلكتروني B2C، بائع واحد، بيع نهائي (مفيش إرجاع/تبديل داخل النظام)، السوق المصري بعملة واحدة: الجنيه المصري (EGP).

Stack: Laravel 12 · PHP 8.2+ · MySQL 8 (`utf8mb4_unicode_ci`) · Redis · Tailwind CSS v4 · jQuery · Ajax.
بوابة الدفع: Paymob. الواجهة بلغتين (`ar`/`en`) عبر `mcamara/laravel-localization`. لوحة التحكم عربي فقط.

## 2. القيود التقنية

- ❌ ممنوع نهائيًا: Livewire · Filament · Inertia · Vue · React · Svelte · **Alpine.js** (حتى كـ dependency غير مباشر).
- ✅ كل التفاعلات والـ Ajax عبر **jQuery فقط**. أي مكتبة JS تانية لازم تكون Vanilla JS.
- قبل تركيب أي package جديد: تأكد إنه مبيجرش Livewire/Alpine/Vue/React كـ dependency (`composer why <package>` / `npm ls`). لو جرّ حاجة زي كده، بلّغ المستخدم قبل التنصيب.
- `laravel/pulse` **متشالة عمدًا** من المشروع لأنها بتفرض `livewire/livewire` كـ dependency أساسي مش اختياري — تتعارض مباشرة مع القاعدة اللي فوق. لو احتجنا Pulse لاحقًا لازم قرار صريح بقبول Livewire في vendor فقط (بدون استخدامه في أي كود تطبيقي).
- `laravel/horizon` متثبتة كـ config بس — مش هتشتغل فعليًا على Windows (بتعتمد على `ext-pcntl`/`ext-posix` مش موجودين في نسخ PHP على Windows). الـ worker الفعلي لازم يشتغل على Linux/WSL/Docker في الإنتاج ومحليًا وقت الحاجة.

## 3. قواعد الفلوس (Money)

- كل مبلغ في قاعدة البيانات يتخزن كـ `unsignedBigInteger` **بالقروش**. ممنوع `float`/`decimal` لأي مبلغ في المشروع كله.
- كل العمليات الحسابية على الفلوس تمر حصرًا عبر [`App\Support\Money`](app/Support/Money.php):
  - `Money::fromMinor(int $minor)` / `Money::fromMajor(string $major)` للإنشاء.
  - `add()` / `subtract()` / `multiply(int $qty)` / `percentage(float $rate)` — الكل immutable، بيرجع instance جديد.
  - **التقريب بيحصل في مكان واحد بس**: جوه `Money::percentage()` بـ `intval(round())`.
  - `->minor()` للقيمة اللي تتبعت لـ Paymob كـ `amount_cents`. `->format(string $locale)` للعرض.
- الأعمدة اللي بتخزن فلوس في الموديلات لازم تستخدم [`App\Casts\MoneyCast`](app/Casts/MoneyCast.php).
- للعرض في Blade استخدم الـ helper `money($value, ?string $locale = null)` ([app/Support/Helpers/money.php](app/Support/Helpers/money.php)) — ممنوع تنسيق فلوس يدوي في الـ views.

## 4. قواعد الكاش

- ❌ **`Cache::flush()` ممنوع نهائيًا** — الإبطال دايمًا مُستهدَف عبر versioning.
- ❌ **`stock_quantity` ممنوع يتكاش أبدًا** — يُقرأ من الداتابيز مباشرة دايمًا.
- ❌ ممنوع كاش لـ: السلة، الـ checkout، أي بيانات مرتبطة بمستخدم بعينه.
- كل مفاتيح الكاش تتولّد حصرًا من [`App\Support\Cache\CacheKeys`](app/Support/Cache/CacheKeys.php) — ممنوع كتابة string مفتاح كاش مباشر في أي مكان تاني.
- أي موديل قابل للكاش يستخدم trait [`App\Support\Cache\HasVersionedCache`](app/Support/Cache/HasVersionedCache.php) — أي تعديل (save/delete) بيزوّد رقم النسخة في [`App\Support\Cache\VersionedCache`](app/Support/Cache/VersionedCache.php)، وده بيبطل كل المفاتيح المرتبطة بيه دفعة واحدة تلقائيًا (المفتاح نفسه بيتغيّر بمجرد ما النسخة تتغيّر).
- استخدم `Cache::flexible($key, [$stale, $expire], $callback)` للمفاتيح التقيلة (stale-while-revalidate).
- Redis متقسّم لثلاث قواعد منفصلة (`config/database.php`): `cache` (DB 0), `session` (DB 1), `queue` (DB 2) — عشان `cache:clear` أو أي عملية على الكاش ما تلمسش جلسات العملاء ولا الوظائف المعلّقة.

## 5. قواعد التخزين (Storage)

أقراص `config/filesystems.php`:

| القرص | الاستخدام |
|---|---|
| `media` | صور المنتجات/البانرات — عام، عبر CDN (Cloudflare R2) |
| `private` | الفواتير وإيصالات الدفع — خاص، Signed URLs فقط |
| `local` | ملفات مؤقتة أثناء المعالجة |

- **قاعدة أمنية إلزامية:** أي مستند فيه بيانات عميل (فواتير، إيصالات) **ممنوع يتحط على قرص عام**. الوصول لازم يكون عبر route محمي بـ Policy يولّد `temporaryUrl` صالح 5 دقايق بس (`config('dersey.signed_url_ttl')`).
- ممنوع تقديم الصورة الأصلية للزائر — دايمًا نسخة مُحوَّلة (conversion). كل `<img>` لازم يكون له `srcset` + `width`/`height` + `aspect-ratio`.
- إعدادات تحويل الصور (المقاسات، الصيغ، إزالة EXIF) مركزية في `config('dersey.media')` — أي موديل بيستخدم `HasMedia` يرجّع لنفس الإعدادات دي بدل ما يكررها.
- الـ conversions بتتولّد على الـ **queue** دايمًا (`config/media-library.php` → `queue_conversions_by_default`), مش synchronously.
- `FILESYSTEM_DISK` في `.env` مضبوط على `local` للتطوير المحلي — الكود لازم يشتغل بنفس الـ interface من غير أي تغيير لما القرص الافتراضي يتبدّل لـ `media`/`private`.

## 6. قواعد الأمان

- ممنوع إرسال أي مفاتيح أو أسرار أو بيانات عملاء لـ Sentry. الـ scrubbing إلزامي وموجود في `config/sentry.php` (`before_send`) — بيشيل أي مفتاح فيه `paymob|secret|hmac|token|key|password|address|phone`، وكل الـ cookies. المسموح فقط: `user_id`. **ممنوع تعديل أو إضعاف الـ callback ده**.
- كل استعلام على بيانات محمية (مستخدم، طلب، مستند) لازم يمر على Policy.
- كل request بياخد `request_id` فريد (`App\Http\Middleware\AssignRequestId`) — بيتحط في الـ logs وفي هيدر `X-Request-Id` عشان نربط Sentry بالـ log وبالـ activity log لنفس الطلب.
- قناة لوج مخصصة لـ Paymob (`config('logging.channels.paymob')`, احتفاظ 90 يوم) — أي تعامل مع Paymob (webhooks، callbacks) يتسجل فيها، منفصلة عن باقي اللوجات.
- `/horizon` محمي بـ Gate باسم `viewHorizon` في `HorizonServiceProvider::gate()` — حاليًا `false` دايمًا لحد ما يترتبط بدور الأدمن الحقيقي في Phase 2.

## 7. قواعد CSS

- **Logical properties فقط**: `ps-`/`pe-`/`ms-`/`me-`/`start-`/`end-`. ممنوع `left`/`right` في أي مكان — المشروع بلغتين (ar/en) وده بيكسر الـ RTL.

## 8. قواعد الخطوط

- الخطوط self-hosted فقط — ممنوع أي CDN خارجي (Google Fonts أو غيره).
- أي خط جديد لازم يعدّي `scripts/subset-fonts.sh` قبل ما يدخل `resources/fonts/`.
- الأصول الكاملة غير المُصغّرة في `resources/fonts/_source/` — متجاهلة من git لأنها build inputs
  حجمها ~415KB والسكريبت بيعيد توليد المُصغّرة منها. مصادر التحميل:
  - **Clash Display** · **Satoshi** → Fontshare (fontshare.com) — مجاني للاستخدام التجاري
  - **Alexandria** · **IBM Plex Sans Arabic** → Google Fonts — رخصة OFL
  لإضافة وزن جديد: نزّل الأصل في `_source/`، شغّل السكريبت، وضيف `@font-face` في الـ partial المناسب.
- **`pyftsubset`** (من حزمة `fonttools`) لازم يكون منصّب لتشغيل سكريبت الخطوط —
  ثبّت بـ: `pip install fonttools brotli`
  (`brotli` مطلوبة لإخراج `woff2` وبتفشل بصمت من غيرها)
- **Satoshi:** أوزان 400 · 500 · 700 فقط — مفيش 600 حقيقي في الخط، والمتصفح بيرجع لـ 700 تلقائيًا عبر CSS font matching.
- `unicode-range` إلزامي في كل `@font-face` — يفصل العربي عن اللاتيني عشان صفحة بلغة واحدة ما تحمّلش خط اللغة التانية.

## 9. قواعد الألوان

- ممنوع نهائيًا كتابة قيمة hex مباشرة في أي ملف (Blade، CSS، JS) — كل الألوان من tokens `resources/css/theme.css` بس.
- ممنوع تعديل أو "تحسين" أي قيمة لون موجودة — الجدول الحالي ناتج 6 جولات مراجعة (توليد OKLCH، gamut mapping، تباين WCAG، تحقق CIEDE2000).
- `--color-neutral-950` (ink) قيمة مستقلة مقصودة، مش متولّدة من باقي المقياس — مش خطأ، ممنوع "تصحيحها".
- أي خلفية بلون دلالي (`bg-primary`, `bg-accent`, إلخ) لازم يترفق بـ `-foreground` المقابل (`text-primary-foreground`) — بعض الألوان (accent, warning) نصها الصحيح `ink` مش أبيض.
- حقول الإدخال والعناصر التفاعلية تستخدم `border-interactive` — ممنوع `border-line` (اللي هو للفواصل الزخرفية بس، تباينه أقل من 3:1).

## 10. قواعد الـ preload

- الـ preload مشروط بلغة الصفحة الفعلية (عبر الـ locale في الرابط بعد Batch 1.3).
- ممنوع preload لخطوط عربي ولاتيني مع بعض في نفس الصفحة — الزائر بيستخدم لغة واحدة بس.

## 11. قواعد الترجمة

- مفيش نص مكتوب مباشرة (hardcoded) في أي Blade view — كل نص من ملفات الترجمة (`resources/lang/ar`, `resources/lang/en`).
- لوحة التحكم عربي فقط، لكن لازم برضه تستخدم ملفات ترجمة (مش نص مباشر) عشان سهولة التعديل والمراجعة.

## 12. قواعد التدويل (i18n)

- بنية الروابط: **الاتنين بـ prefix** — `/ar/...` و `/en/...`، مفيش لغة من غير prefix.
- اللغة الافتراضية `ar`. الجذر `/` بيعمل redirect **302** (مش 301) — الوجهة بتتغيّر حسب `Accept-Language`/الكوكي، والـ 301 بيتكاش عند الزائر بشكل دائم وبيكسر التبديل بين اللغتين.
- `x-default` (hreflang) بيشاور دايمًا على النسخة العربية.
- `locale` مش معروف (`/xx/...`) → **404** — ممنوع أي redirect (بيعمل soft-404 والـ crawler بيفهر روابط وهمية).
- `/admin` **بره نظام الـ locale تمامًا** — عربي ثابت، مفيش `/ar/admin` ولا `/en/admin`.
- الـ `canonical` لازم يكون **مطلق** وبالـ locale الحالي — canonical لنسخة بلغة تانية بيحذف صفحات كاملة من الفهرس.
- مفاتيح الترجمة إنجليزي وصفي (`common.add_to_cart`) — ممنوع اختصارات زي `btn1`.
- الجمع العربي بـ **6 صيغ** (صفر/مفرد/مثنى/قليل/كثير/غيره) عبر `trans_choice` بمدى واضح — مش صيغتين بس.
- **الأرقام الغربية (`0123456789`) في اللغتين**، مش هندية — أنسب لمتجر مصري رقمي، وبيقلل الاحتكاك البصري وقت تبديل اللغة.
- كل مفتاح ترجمة في `ar` لازم يكون له مقابل في `en` والعكس — متحقَّق تلقائيًا باختبار عبر [`App\Support\Lang\TranslationParityChecker`](app/Support/Lang/TranslationParityChecker.php).

## 13. قواعد التسمية

- جداول الداتابيز: جمع (`products`, `order_items`).
- الموديلات: مفرد (`Product`, `OrderItem`).
- الـ routes: kebab-case (`/best-sellers`, `/order-confirmation`).

## 14. بنية المجلدات

```
app/
├── Casts/                    # MoneyCast
├── Enums/                    # OrderStatus, PaymentStatus, ProductStatus, Gender...
├── Http/
│   ├── Controllers/{Front,Admin}/
│   ├── Middleware/            # AssignRequestId + غيرها
│   ├── Requests/{Front,Admin}/
│   └── Resources/
├── Models/
├── Repositories/Contracts/
├── Services/{Cart,Order,Payment,Inventory,Media,Seo}/
├── Support/
│   ├── Money.php              # Value Object
│   ├── Cache/                 # CacheKeys, VersionedCache, HasVersionedCache
│   └── Helpers/                # money()
├── Observers/
├── Policies/
├── Jobs/
└── View/Components/

resources/
├── css/{app.css,admin.css}
├── js/
│   ├── app.js / admin.js
│   ├── core/                  # ajax.js, toast.js, loader.js (jQuery)
│   ├── modules/
│   └── admin/                 # table.js, form.js, media.js, editor.js
├── fonts/
├── views/{layouts,components,front,admin,emails}/
└── lang/{ar,en}/

routes/
├── web.php
├── admin.php                  # prefix: admin, middleware: web
└── ajax.php                   # prefix: ajax, middleware: web
```

الفولدرات الفاضية دلوقتي بيها `.gitkeep` وهتتملى تدريجيًا في Phase 2 حسب الموديلات والفيتشرز الفعلية — ممنوع نعمل abstractions/كلاسات لموديلات لسه مش موجودة.

### صيغة Git

- Branch أساسي: `main`. Branch تطوير: `dev`.
- صيغة البرانشات: `abdallah/feature/branch-name`.
- صيغة الـ commits: `type: description` (`feat`, `fix`, `chore`, `refactor`, `style`, `docs`) — commit منفصل لكل وحدة منطقية.
- رسائل الـ commit لازم تتبع `type: description`. الريبو فيه commit واحدة قديمة (`8721e21 remaining files`) من Batch 1.1 مخالفة للقاعدة — متروكة عمدًا (إعادة كتابة التاريخ مخاطرة أكبر من الفايدة) ومش سابقة يُقاس عليها.
- `pint.json` بمعيار `laravel`. شغّل `vendor/bin/pint` قبل أي commit.
