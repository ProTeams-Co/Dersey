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
- أسماء الـ tokens في `theme.css` لازم تتبع namespaces بتاعة Tailwind v4 (`--color-*`, `--duration-*`, `--z-index-*`, `--ease-*`) — الاسم الدلالي لوحده مش كفاية، لو الـ namespace غلط الـ utility مش بتتولّد أصلًا والغلط بيعدي صامت. اتحقق دايمًا بفحص الـ CSS المولَّد فعليًا (`npm run build` + grep)، مش بالافتراض.
- ألوان `DEFAULT` الدلالية (`--color-primary`, `--color-accent`, `--color-success`, `--color-warning`, `--color-danger`) لازم تتكتب كقيمة hex مباشرة، مش `var(--color-{name}-{anchor})` — Tailwind محتاج يحسب قيمة اللون statically عشان يولّد utilities الشفافية (`bg-primary/10`)؛ الإحالة عبر `var()` بتخليها تفشل تتولّد بصمت. القيمة لازم تفضل مطابقة لدرجة الـ anchor بالحرف.

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

## 13. قواعد الـ JS و Ajax

- [`App\Support\Lang`](app/Support/Lang) مالوش علاقة بالقسم ده — الملف ده عن `resources/js` بس.
- `core/ajax.js` هو النقطة الوحيدة لأي طلب Ajax في المشروع — jQuery فقط، مفيش `fetch()` ولا `axios` (اتشالت من `package.json` في Batch 1.4 بعد ما `bootstrap.js` القديم اتشال).
- `GET /ajax/csrf-token` — endpoint دائم (مش مؤقت، مش TODO)، بيرجّع CSRF token جديد بعد استجابة 419 عشان `core/ajax.js` يعيد المحاولة مرة واحدة. محمي بـ `throttle:10,1` — من غير CSRF بتاعه (GET) فمحتاج rate limit مستقل يمنع استنزافه.
- المكتبات التقيلة (GSAP/Lenis) لازم تتحمّل بـ **dynamic import بعد `window.load`**، مش جوه الـ bundle الأساسي — وتتجنّب التحميل خالص (مش بس التجاهل بعد التحميل) لو `prefers-reduced-motion: reduce`. `Dersey.motion.enabled` نفسه لازم يتحسب فورًا (قراءة `matchMedia` مباشرة) من غير ما يستنى تحميل المكتبة.
- **بق `@json` في Blade:** الـ arrays المعقّدة (متداخلة + فيها استدعاء دوال زي `route()`) لازم تتبني كمتغيّر PHP الأول (`@php $data = [...]; @endphp`) وبعدين `@json($data)` — استدعاء `@json([...])` مباشر بتعبير متداخل بيتقطع عند أول قوس إغلاق `)` يلاقيه (اتكشف في Batch 1.3 مع `window.Dersey`). كمان: تجنّب كتابة `@directive(` بصيغتها الحرفية جوه أي Blade comment (`{{-- --}}`) — بيلخبط الـ compiler ويسرّب كود PHP لناتج الصفحة.
- أي `<script>` inline في Blade بيستخدم jQuery أو `window.Dersey` لازم يكون `type="module"`. الـ bundle بيتحمّل بـ `defer` ومش بيكون جاهز وقت تنفيذ السكريبتات العادية.

## 14. قواعد طبقة البيانات

- كل مبلغ فلوس `unsignedBigInteger` بالقروش + [`App\Casts\MoneyCast`](app/Casts/MoneyCast.php) — 🚫 ممنوع `decimal`/`float` لأي عمود فلوس في المشروع كله (زي قسم 3).
- كل حالة أو نوع = PHP Enum مدعوم بـ `string` (`App\Enums\*`) — 🚫 ممنوع تخزين string خام في العمود. كل enum بيطبّق `App\Enums\Contracts\HasEnumOption` (`label()` من `lang/{ar,en}/enums.php`، `color()` بيرجّع اسم variant من `x-ui.badge`).
- انتقالات حالة الطلب (`OrderStatus`) تمر **حصرًا** عبر `canTransitionTo()` — مفيش تعديل مباشر لعمود الحالة من غير المرور على الفحص ده. `isFinal()` بترجع `true` لـ `cancelled`/`returned` بس (الحالتين اللي مالهمش أي انتقال خارج منهم).
- **الحذف:**
  - `SoftDeletes`: الكتالوج، المستخدمين (`users`)، `admins`، `addresses`.
  - Hard delete: الجداول الرابطة والمؤقتة بس.
  - **مفيش حذف نهائي للسجلات المالية أبدًا** — الإلغاء **حالة** (`OrderStatus::Cancelled`، `PaymentStatus::Failed`...) مش حذف من الجدول.
  - **`reviews` بره الاتنين دول عمدًا — hard delete حقيقي، مفيهاش `SoftDeletes`** (Batch 2.5). حذف مراجعة بيمسحها نهائيًا من `reviews` ومن حساب `products.avg_rating`/`reviews_count` فورًا (عبر `ReviewObserver::deleted()`) — مفيش استرجاع لمراجعة اتمسحت. ده قرار مقصود (مراجعة مش سجل مالي ولا كتالوج)، مش سهو.
- الجغرافيا (`governorates`, `cities`) على `addresses` بـ `restrict` مش `cascade` — حذف محافظة أو مدينة ما يمسحش عناوين العملاء المرتبطة بيها.
- نظام الترجمة هجين: جداول `{model}_translations` منفصلة (عبر [`App\Support\Traits\HasTranslations`](app/Support/Traits/HasTranslations.php)) للمحتوى المفهرس/القابل للبحث، و`spatie/laravel-translatable` (JSON column) لباقي البيانات المرجعية (زي أسماء المحافظات/المدن). الاسمين نفس بعض (`HasTranslations`) في namespace مختلف — لازم تنتبه لأي `use` غلط.
- **أي قراءة لحقل مترجَم لازم يسبقها `withCurrentTranslation()`** — لو اتنسيت، `Model::preventLazyLoading()` (مفعّل بره الإنتاج من Batch 1.1) هيرمي `LazyLoadingViolationException` صريح في التطوير/الاختبار **قبل** ما ينفّذ أي استعلام، بدل N+1 صامت يبان كبطء غامض بعدين. مُتحقَّق فعليًا: عدد الاستعلامات ثابت على 2 مهما زاد عدد الصفوف لما تستخدم الـ scope صح.
- **`migrate:fresh` ما بيمسحش Redis** — أي seeder بيعتمد على كاش (زي `spatie/laravel-permission`، اللي بيكاش الأدوار/الصلاحيات لمدة 24 ساعة) لازم يبطّل الكاش ده في أول الـ `run()` بتاعه (`app(PermissionRegistrar::class)->forgetCachedPermissions()`)، وإلا التشغيلة الثانية لـ `migrate:fresh --seed` ممكن تقرا IDs قديمة باظت مع الجداول اللي اتمسحت.
- **`kalnoy/nestedset` (النسخة المثبتة v6) مفيهاش عمود `depth` تاني** — بيوفر بس `_lft`/`_rgt` + `parent_id`، والمستوى (depth) بيتحسب وقت الاستعلام بس عبر `->withDepth()`، مش عمود متخزّن. اتأكد من ده بقراءة الكود مباشرة (`vendor/kalnoy/nestedset`)، مش افتراض. لو محتاج المستوى في نتيجة استعلام، استخدم `withDepth()` بدل ما تضيف عمود `depth` يدوي (هيبقى مصدر حقيقة تاني ممكن يتفرقع عن `_lft`/`_rgt`).
- **`Category::descendants()` و `descendantProductsCount()` بيقروا `_lft`/`_rgt` من نسخة الموديل اللي في الذاكرة، مش من قاعدة البيانات كل مرة** — لو عدّلت الشجرة (ضفت/نقلت/مسحت تصنيف) في نفس الـ request على instance موجود عندك بالفعل، لازم تعمل `$category->refresh()` قبل ما تنادي `descendants()`/`descendantProductsCount()` عليه تاني، وإلا هترجع نتيجة قديمة (الحدود اللي كانت قبل التعديل). موديل مجلوب طازة (زي route model binding في كنترولر) مش متأثر بالمشكلة دي.
- `Category::canBeDeleted(): bool` و `Category::deletionBlockers(): array` (بترجع translation keys من `lang/{ar,en}/errors.php`, مش نص خام) — الواجهة الإدارية لازم تستخدمهم عشان تعطّل زرار الحذف بدل ما تسيب المستخدم يضغط وياخد خطأ. أي منطق منع حذف جديد (زي منتجات مرتبطة بتصنيف) لازم يتضاف هنا وفي [`App\Observers\CategoryObserver`](app/Observers/CategoryObserver.php) مع بعض، مش في مكان واحد بس، عشان الفحص في الواجهة والفحص الفعلي في الحذف ميختلفوش عن بعض.
- **`order_number` بيتولّد من `id` الطلب نفسه بعد الإدراج** (`ORD-{year}-{id:06d}`)، مش من `max(id) + 1` — الإدراج بيحصل الأول برقم مؤقت (`PENDING-{uuid}`) عشان يستوفي `NOT NULL`/`UNIQUE`، وبعدين بيتحدّث بالرقم الحقيقي المشتق من الـ auto-increment اللي MySQL نفسه ضامن تفرّده تحت التزامن. أي توليد رقم طلب جديد لازم يتبع نفس المنطق، مش يخترع sequence منفصلة.
- **حماية `coupons.used_count` من التزامن بـ `lockForUpdate()` وقت التأكيد** (`CouponService::recordUsage()`) — مُتحقَّق فعليًا بـ 5 عمليات نظام تشغيل متوازية بتضرب نفس الصف على MySQL حقيقي (مش محاكاة): كوبون بحد أقصى 3، 3 نجحوا و2 اترفضوا بـ `CouponLimitReachedException`.
- **صلاحية الكوبون على السلة لازم تتفحّص بـ `CouponService::validate()`، مش بس وجوده** — وجود `cart->coupon` وحده مش دليل إنه صالح (ممكن يبقى منتهي أو تحت `min_order_amount`). أي كود بيتفاعل مع كوبون مطبّق على سلة (الخصم، الشحن المجاني، تسجيل الاستخدام) لازم يعتمد على نتيجة `validate()` مش على مجرد `!= null`.
- **مدة صلاحية السلة: يوم للضيوف، 7 أيام للمستخدمين المسجّلين** (`CartService::findOrCreateForGuest()`/`findOrCreateForUser()`) — `ReleaseExpiredCartsJob` هو اللي بيفك الحجز ويمسح السلة بعد انتهاء الصلاحية، مش أي كود تاني.
- **منتج اتباع فعليًا (عدّى على `InventoryService::commit()`) ما ينفعش يتحذف نهائيًا** — `inventory_movements.variant_id` بـ `restrictOnDelete()` (حماية سجل التدقيق، من Batch 2.3)، و`product_variants.product_id` بـ `cascadeOnDelete()` من المنتج، فمحاولة `forceDelete()` على منتج اتباع بتترفض على مستوى الداتابيز. ده قرار متعمّد، مش خطأ — أي اختبار أو seeder محتاج يثبت "الطلب بيصمد بعد حذف منتجه" لازم يستخدم `variant` لسه ما اتباعش (مفيهوش `inventory_movements`)، مش الـ variant الحقيقي اللي في الطلب.
- **`InventoryService::commit()` بيسجّل `InventoryMovementType::Out` دايمًا** — العدّاد ده بيغطي دلوقتي كل حالة "خرج من المخزون فعليًا" (بيع عادي)، ومفيش case منفصل للمرتجعات لسه. لما المرتجعات تتضاف (المفروض Batch 2.4)، التفريق بين "خرج للعميل" و"رجع من عميل" لازم يتم عن طريق `reference_type`/`reference_id` (polymorphic) على `inventory_movements`، مش بإضافة enum case جديد بيغيّر معنى `Out` نفسه.
- **`ProductVariant::available_quantity` accessor محسوب (`stock_quantity - reserved_quantity`)، مش عمود** — 🚫 ممنوع تستخدمه في `WHERE`/`ORDER BY` مباشر (`->where('available_quantity', ...)` هيفشل، العمود مش موجود في الجدول أصلًا). أي فلترة SQL على "المتاح بس" (زي فلاتر التصنيف في Batch 4) لازم تستخدم `whereRaw('stock_quantity - reserved_quantity > 0')` (أو المكافئ) بدل الاعتماد على الـ accessor.
- **⚠️ `Product::seoPath()` و `Category::seoPath()` هما الاصطلاح المؤقت الوحيد لبناء مسار الـ SEO** (Batch 2.5) — `ProductTranslationObserver`/`CategoryTranslationObserver` بيستخدموهم (عبر `Product::seoPath(...)`/`Category::seoPath(...)` كـ first-class callable) عشان يولّدوا صفوف `redirects` تلقائيًا لما الـ slug يتغيّر، بدل ما يبنوا المسار بنفسهم. القيمة الحالية placeholder موسومة بـ `TODO(4.x)` جوه الموديلين (`/{locale}/products/{slug}`, `/{locale}/categories/{slug}`) ومش مطابقة لخطة الـ SEO الحقيقية (مسار مسطّح `/{locale}/{category-slug}/{product-slug}`). **لما الـ routes الحقيقية تتبني في Phase 4: `seoPath()` هي الملف الوحيد اللي المفروض يتعدّل** (مش الـ Observers ولا أي مكان تاني)، ولازم كمان **backfill** لكل صفوف `redirects` الموجودة اللي اتولّدت بالاصطلاح القديم (`to_path`/`from_path` بتاعتها هتبقى بتاعت مسارات مش موجودة).

## 15. قواعد التسمية

- جداول الداتابيز: جمع (`products`, `order_items`).
- الموديلات: مفرد (`Product`, `OrderItem`).
- الـ routes: kebab-case (`/best-sellers`, `/order-confirmation`).

## 16. بنية المجلدات

```
app/
├── Casts/                    # MoneyCast
├── Enums/                    # 10 domain enums + Contracts/HasEnumOption
├── Http/
│   ├── Controllers/{Front,Admin}/
│   ├── Middleware/            # AssignRequestId + غيرها
│   ├── Requests/{Front,Admin}/
│   └── Resources/
├── Models/                    # Model.php + Translation.php (أساسيين) · User, Admin, Address, Governorate, City, Setting · Concerns/
├── Repositories/Contracts/
├── Services/{Cart,Order,Payment,Inventory,Media,Seo}/  # + SettingsService.php
├── Support/
│   ├── Money.php              # Value Object
│   ├── Cache/                 # CacheKeys, VersionedCache, HasVersionedCache
│   ├── Traits/                 # HasTranslations
│   └── Helpers/                # money()
├── Observers/                 # SettingObserver, AddressObserver
├── Policies/
├── Jobs/
└── View/Components/

resources/
├── css/{app.css,admin.css}
├── js/
│   ├── app.js / admin.js
│   ├── core/                  # ajax.js, toast.js, loader.js, form.js (jQuery)
│   ├── modules/
│   └── admin/                 # table.js, media.js, editor.js
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

## 17. قواعد لوحة التحكم

- **`App\Support\Admin\AdminTable`** هي المحرك الوحيد لأي جدول في اللوحة — الترتيب/البحث/الفلترة/الترقيم/التصدير كلهم من نفس الاستعلام (`filteredQuery()`)، سواء الاستجابة JSON (Ajax) أو HTML (أول تحميل عبر `x-admin.table`). أي جدول جديد لازم يرث منها، مش يبني منطق بحث/ترتيب مستقل.
  - **البحث في الحقول المترجمة**: `translatedSearchColumns()` بيدوّر عبر علاقة `translations()` (من `HasTranslations`)، وبيطبّق نسخة SQL من قواعد `App\Support\Search\ArabicNormalizer` (توحيد الألف/الياء/التاء المربوطة عبر `REPLACE()` متداخلة) على العمود، ومصطلح البحث بيتطبّع فعليًا بنفس الكلاس قبل المقارنة. تجريد التشكيل مش متطبّق على العمود عمدًا (النصوص المخزنة عمليًا من غير تشكيل).
  - **تصدير CSV**: مباشر (streamed) لو عدد الصفوف ≤ 1000، وإلا `ExportAdminTableJob` (queued) بيكتب الملف على قرص `local` (مش `private` — `private` مبني على S3/R2 وبيانات الاعتماد فاضية في التطوير المحلي؛ لما R2 يتظبط في الإنتاج القرار ده محتاج مراجعة).
- **`App\Http\Controllers\Admin\AdminController`** — base class لأي resource controller جديد (index/create/store/edit/update/destroy/bulkDestroy)، route params بالـ id مباشر مش route-model-binding عشان يفضل عام. أي domain exception بتتطلع من `destroy()` (زي `CategoryHasDependentsException`) **من غير try/catch** — الـ exception بترندر نفسها.
- ⚠️ **Authorization في اللوحة لازم يمر على `AdminController::authorize()`**، مش `AuthorizesRequests` الافتراضي مباشرة — الافتراضي بيقرا المستخدم من الـ guard الافتراضي (`web`) مش `admin`، فهيرفض كل حاجة صامت. لو عملت controller برّه `AdminController` واحتجت authorize، استخدم `Gate::forUser(Auth::guard('admin')->user())` بنفس المنطق.
- ⚠️ **`spatie/laravel-activitylog`**: الـ causer الافتراضي بيتحل بنفس مشكلة الـ guard بالظبط. `AppServiceProvider::boot()` فيه `CauserResolver::resolveUsing()` بيفحص `admin` الأول وبعدين `web` — أي كود تاني بيسجّل activity يدوي (زي `LoginController`'s الفشل في الدخول) لازم يعتمد على الـ resolver ده، ممنوع `causedBy(Auth::user())` مباشر (هيرجع null على طلبات اللوحة).
- ⚠️ **`App\Models\Concerns\HasDefaultActivityLog` لازم يفضل فيه `->logFillable()`** — من غيرها `logAttributes` بتفضل فاضية و`attributesToBeLogged()` بترجع `[]` دايمًا، يعني التسجيل التلقائي (`created`/`updated`/`deleted`) بيبقى no-op صامت تمامًا بغض النظر عن `logOnlyDirty()`. ده كان باگ حقيقي من Batch 2.1 اتصلح في Batch 3.0 — لو عدّلت الـ trait ده لازم تتأكد إن `logFillable()`/`logAll()`/`logOnly()` واحد منهم موجود دايمًا.
- **القائمة الجانبية** بتتبني من `config('admin-menu')` (مصفوفة PHP، مش HTML) عبر `App\Support\Admin\Menu::visible()` — بتفلتر حسب صلاحية كل عنصر وبتتأكد إن الـ route موجود فعلاً (`Route::has()`) قبل ما تعمل لينك؛ عنصر مسارَه لسه مش مُسجّل بيتعرض معطّل بـ"قريبًا" بدل ما يكسر الصفحة.
- **حالة طي القائمة الجانبية متخزنة في كوكي** (`admin_sidebar_collapsed`, سنة) — بيتقرا server-side في `layouts/admin.blade.php` عشان الحالة تتطبّق من أول render من غير flash، وJS بيحدّثه عند التبديل.
- **الحقول المترجمة في أي فورم (`x-admin.translatable-tabs` + `x-admin.form :translatable="true"`) لازم تتسمّى `translations[{locale}][{field}]`** — ده الاتفاق اللي `admin/form.js` بيعتمد عليه عشان يفتح التاب الصح تلقائيًا لما يجي خطأ 422 على لغة مش ظاهرة حاليًا.
- **FilePond و CKEditor 5 لازم يتحمّلوا بـ dynamic import**، مش جوه `admin.js` الأساسي — نفس قاعدة GSAP/Lenis في المتجر (قسم 13) بالظبط. الحزمة الأساسية لازم تفضل صغيرة (~38KB gzip)، والمكتبات التقيلة (FilePond ~43KB، CKEditor ~525KB gzip) تتحمّل بس لما `[data-media-picker]`/`[data-editor]` موجودين في الصفحة.
- **رفع الملفات المؤقت** (`MediaUploadController`, قبل ما أي resource يرتبط بيه فعليًا) بيتحقق من النوع الحقيقي (`image`/`mimes:` — فحص محتوى حقيقي عبر fileinfo مش امتداد الملف)، وبيتخزن على قرص `local` تحت `tmp-uploads/` — مش `media`/R2، لحد ما resource حقيقي يرتبط بالملف المرفوع.
- **إنشاء حساب أدمن جديد**: من داخل اللوحة لسه مش موجود (index-only دلوقتي، Batch 3.0) — الطريقة الوحيدة هي `php artisan admin:create` (تفاعلي أو بـ `--name`/`--email`/`--password`/`--role`). ممنوع تسجيل حساب ذاتي من أي مكان.
