# Task List: Penyelarasan kit/api dari news/api (SlimeKit v2)

---

## Phase 1: Foundation & Dependencies Alignment

### Task 1: Composer & Dependencies Modernization ✅
**Description:** Memperbarui `composer.json` untuk menggantikan library usang `uma/dic` dengan `php-di/php-di`, menambahkan `monolog/monolog`, `rakit/validation`, `ramsey/uuid`, serta dependensi dev `phpunit/phpunit`. Menambahkan autoload dev `Tests\\` dan scripts pengujian.

**Acceptance criteria:**
- [x] `uma/dic` dihapus dari `composer.json`.
- [x] `php-di/php-di: ^7.1`, `monolog/monolog: ^3.10`, `rakit/validation: ^1.4`, `ramsey/uuid: ^4.7` ditambahkan ke `require`.
- [x] `phpunit/phpunit: ^11.0` ditambahkan ke `require-dev`.
- [x] `autoload-dev` memetakan `"Tests\\": "tests/"`.
- [x] Scripts `test`, `test:feature`, `test:unit`, `test:filter` tersedia di `composer.json`.

**Verification:**
- [x] Menjalankan `composer validate` memastikan sintaks `composer.json` valid.
- [x] Menjalankan `composer update` menginstal seluruh dependensi (`PHP-DI`, `Monolog`, `Rakit`, `Ramsey UUID`, `PHPUnit 11`).

**Dependencies:** None
**Files likely touched:**
- `composer.json`
**Estimated scope:** Small (1 file)

---

### Task 2: Service Registry, Dependency Injection Container & Library Overhaul ✅
**Description:** Membuat konfigurasi DI container terpusat (`config/services.php` dan `config/dependencies.php`) menggunakan PHP-DI. Memusatkan pembacaan `$_ENV` di `services.php` dan mendaftarkan service, logger, validator, DB, cache, storage, Google services, AI clients, dan helper ke container. Menyelaraskan seluruh 14 file library di `src/lib/` agar 100% identik dengan `news/api/src/lib/` (murni IoC & dependency injection, tanpa pembacaan `$_ENV` di dalam class library).

**Acceptance criteria:**
- [x] `src/config/services.php` dibuat, memetakan seluruh konfigurasi dari `$_ENV`.
- [x] `src/config/dependencies.php` dibuat, mendefinisikan LoggerInterface, Validator, Database, Valkey, Tokenize, Mailer, Storage, AI Clients, Google Services, dan General helper ke dalam `ContainerBuilder`.
- [x] Seluruh 14 file library di `src/lib/` diselaraskan 1:1 identik dengan `news/api/src/lib/`:
  - `Database.php`, `Valkey.php`, `Tokenize.php`, `CloudflareR2.php`, `Cloudinary.php`, `Mailer.php`, `Cronhooks.php`, `GoogleCloudStorage.php` (menghapus `GoogleCloud.php` usang).
  - `GeminiClient.php`, `OpenAIClient.php`, `TavilyClient.php`, `NextRevalidator.php`.
  - `GoogleAnalytics.php`, `GoogleApps.php`.
- [x] Dependensi `google/apiclient`, `google/apiclient-services`, dan `google/analytics-data` ditambahkan ke `composer.json`.

**Verification:**
- [x] `git diff --no-index src/lib ../../news/api/src/lib` menghasilkan 0 diff (identik 100%).
- [x] Seluruh 14 file library lulus uji sintaks `php -l`.
- [x] Container dapat di-instantiate tanpa syntax error melalui script evaluasi PHP.

**Dependencies:** Task 1
**Files likely touched:**
- `src/config/services.php` (baru)
- `src/config/dependencies.php` (baru)
- `src/lib/*.php` (14 file identik dengan `news/api/src/lib/`)
- `composer.json`
- `composer.lock`
**Estimated scope:** Medium (16 files)

---

### Task 3: Response Formatter, Error Handler & CORS Middleware ✅
**Description:** Mengimplementasikan class helper `ApiResponse` untuk standardisasi balikan JSON, `ErrorHandlerMiddleware` untuk menangkap unhandled exception dan logging ke Monolog, serta `CorsMiddleware` untuk penanganan CORS terprogram. Membersihkan header CORS statis di `.htaccess`.

**Acceptance criteria:**
- [x] `src/helpers/ApiResponse.php` dibuat dengan method `success()`, `paginated()`, `error()`, dan `handleException()`.
- [x] `handleException` otomatis mendeteksi error validasi dan mengembalikan status HTTP 422 dengan kode `VALIDATION_ERROR`.
- [x] `src/middlewares/ErrorHandlerMiddleware.php` dibuat dengan integrasi `Psr\Log\LoggerInterface`.
- [x] `src/middlewares/CorsMiddleware.php` dibuat dengan konfigurasi `ALLOWED_ORIGINS` dari environment.
- [x] Header CORS di `src/public/.htaccess` dibersihkan agar tidak bentrok.

**Verification:**
- [x] Response helper mengembalikan format JSON yang konsisten dengan HTTP status code yang tepat.

**Dependencies:** Task 2
**Files likely touched:**
- `src/helpers/ApiResponse.php` (baru)
- `src/middlewares/ErrorHandlerMiddleware.php` (baru)
- `src/middlewares/CorsMiddleware.php` (baru)
- `src/public/.htaccess`
**Estimated scope:** Medium (4 files)

---

### Task 4: Application Bootstrap Refactoring ✅
**Description:** Memperbarui file entri utama `src/public/index.php` untuk mengintegrasikan DI container PHP-DI, auto-binding ke Slim AppFactory, registrasi `CorsMiddleware`, dan `ErrorHandlerMiddleware`.

**Acceptance criteria:**
- [x] Slim `AppFactory::create(container: $container)` menerima container dari PHP-DI.
- [x] `CorsMiddleware` dan `ErrorHandlerMiddleware` terpasang di pipeline middleware aplikasi.
- [x] Inisialisasi `App\Lib\Database` dilakukan melalui container (`$container->get(App\Lib\Database::class)`).

**Verification:**
- [x] Server lokal dapat dinyalakan (`composer start`) dan merespons request tanpa fatal error.

**Dependencies:** Task 2, Task 3
**Files likely touched:**
- `src/public/index.php`
**Estimated scope:** Small (1 file)

---

## Checkpoint 1: Foundation & App Bootstrap ✅
- [x] `composer.json` valid dan dependensi terpasang bersih.
- [x] DI Container terkonfigurasi.
- [x] Middleware global terpasang.
- [x] Server Slim dapat di-bootstrap tanpa error.

---

## Phase 2: DevOps, OpenTofu Removal & Environment Setup

### Task 5: Penghapusan OpenTofu & Pembaruan CI/CD Workflow ✅
**Description:** Menghapus seluruh direktori dan artefak OpenTofu (`tofu/`) dari `kit/api`, serta memperbarui workflow GitHub Actions `deploy.yml` agar beralih ke deployment Docker dan Google Cloud Run standar tanpa OpenTofu.

**Acceptance criteria:**
- [x] Direktori `tofu/` beserta seluruh file (`*.tf`, `*.hcl`) dihapus.
- [x] `assets/.github/workflows/deploy.yml` diperbarui: menghapus step `tofu init` & `tofu apply`, menggunakan deployment Cloud Run via env vars / secret.

**Verification:**
- [x] Direktori `tofu/` tidak lagi ada di filesystem.
- [x] File `deploy.yml` bersih dari referensi OpenTofu.

**Dependencies:** None
**Files likely touched:**
- `tofu/` (dihapus)
- `assets/.github/workflows/deploy.yml`
- `.gitignore`
**Estimated scope:** Small (1 file + delete dir)

---

### Task 6: Dual Environment & Docker Configuration
**Description:** Menyiapkan konfigurasi dual environment `.env.dev` dan `.env.prod`, membuat `Dockerfile.dev` dan `Dockerfile.prod`, memperbarui `.env.example`, memperbarui `docker-compose.yml`, dan menghapus `Dockerfile` lama.

**Acceptance criteria:**
- [ ] `Dockerfile.dev` dibuat (menyalin `.env.dev` ke `.env` di container image).
- [ ] `Dockerfile.prod` dibuat (menyalin `.env.prod` ke `.env` di container image).
- [ ] `.env.dev` dan `.env.prod` template dibuat dengan variabel lengkap.
- [ ] `.env.example` diperbarui memuat seluruh konfigurasi (AI, NextRevalidate, Valkey, Logging, CORS).
- [ ] `docker-compose.yml` menunjuk ke `Dockerfile.dev`.
- [ ] File `Dockerfile` tunggal lama dihapus.

**Verification:**
- [ ] Perintah `docker-compose config` memvalidasi konfigurasi tanpa error.

**Dependencies:** Task 5
**Files likely touched:**
- `Dockerfile.dev` (baru)
- `Dockerfile.prod` (baru)
- `.env.dev` (baru)
- `.env.prod` (baru)
- `.env.example`
- `docker-compose.yml`
- `Dockerfile` (dihapus)
**Estimated scope:** Medium (6 files)

---

## Checkpoint 2: DevOps & Environments Verified
- [ ] OpenTofu telah bersih dihapus dari proyek.
- [ ] Dual environment `.env.dev` & `.env.prod` tersedia.
- [ ] Dockerfile.dev & Dockerfile.prod siap digunakan.

---

## Phase 3: Core Helper Expansion & Automated Testing Infrastructure

### Task 7: Reusable Helpers Expansion (`General.php`)
**Description:** Memperluas class `App\Helpers\General` dengan fungsi-fungsi esensial yang sudah teruji di `news/api`: pagination helpers (`paginateQuery`, `paginateArray`, `paginationRules`), user context & client IP extractor, GeoIP lookup via Valkey cache, audit logger helpers, Tavily context cleaner, slugify, dan datetime normalizer.

**Acceptance criteria:**
- [ ] Method paginasi (`paginateQuery`, `paginateArray`, `normalizePagination`, `paginationRules`) tersedia.
- [ ] Method `getUserContext`, `getClientIp`, dan `getGeoIp` tersedia.
- [ ] Method audit log (`addLog`, `normalizeLogRecord`, `logRecordChanges`, `logUpdateChanges`) tersedia.
- [ ] Method utilitas AI & data (`clean_tavily_context`, `normalize_datetime`, `slugify`, `getTopArticles`, `getCategoriesBySite`) tersedia.

**Verification:**
- [ ] File `General.php` dapat di-load tanpa error kompilasi/linter.

**Dependencies:** Task 2, Task 3
**Files likely touched:**
- `src/helpers/General.php`
**Estimated scope:** Medium (1 file besar)

---

### Task 8: Inisialisasi PHPUnit & Test Suite Harness
**Description:** Menyiapkan infrastruktur pengujian otomatis PHPUnit: konfigurasi `phpunit.xml`, basis class `tests/TestCase.php` (dengan helper `getAppInstance()`, `createRequest()`, `parseJson()`), penyesuaian middleware (`AuthMiddleware`, `TokenMiddleware`, `RateLimitMiddleware`) agar mengenali environment `TESTING`, serta unit test pertama untuk paginasi.

**Acceptance criteria:**
- [ ] File `phpunit.xml` dibuat dengan testsuite Feature dan Unit, serta env `ENVIRONMENT=TESTING`.
- [ ] `tests/TestCase.php` dibuat untuk bootstrapping aplikasi Slim dalam lingkungan uji.
- [ ] `TokenMiddleware` dan `AuthMiddleware` membypass autentikasi pada saat `ENVIRONMENT=TESTING`.
- [ ] `RateLimitMiddleware` membypass throttling pada saat `ENVIRONMENT=TESTING`.
- [ ] Unit tests `tests/Unit/PaginationHelperTest.php` dan `tests/Unit/PaginationValidatorTest.php` dibuat dan lulus 100%.

**Verification:**
- [ ] Perintah `composer test:unit` atau `vendor/bin/phpunit --testsuite Unit` lulus tanpa kegagalan.

**Dependencies:** Task 1, Task 4, Task 7
**Files likely touched:**
- `phpunit.xml` (baru)
- `tests/TestCase.php` (baru)
- `tests/Unit/PaginationHelperTest.php` (baru)
- `tests/Unit/PaginationValidatorTest.php` (baru)
- `src/middlewares/TokenMiddleware.php`
- `src/middlewares/AuthMiddleware.php`
- `src/middlewares/RateLimitMiddleware.php`
**Estimated scope:** Medium (7 files)

---

## Checkpoint 3: Helpers & Test Harness Functional
- [ ] Helper paginasi dan utilitas siap pakai.
- [ ] Test harness PHPUnit berjalan normal (`composer test:unit` green).

---

## Phase 4: Core Framework Modules Refactoring

### Task 9: Refactor Modul AdminAuth & SettingUser
**Description:** Merefaktor modul otentikasi admin (`AdminAuth`) dan manajemen user/roles (`AdminSettingUser`) ke arsitektur baru: PHP 8 Constructor Property Promotion (DI auto-wiring), `Rakit\Validation\Validator`, format respons `ApiResponse`, dan pembuatan feature test.

**Acceptance criteria:**
- [ ] `AdminAuthController`, `AdminAuthService`, `AdminAuthValidator` menggunakan standar baru.
- [ ] `AdminSettingUserController`, `AdminSettingUserService`, `AdminSettingUserValidator` menggunakan standar baru.
- [ ] Endpoint list user dan roles mendukung paginasi `{ items, pagination }`.
- [ ] Feature tests `tests/Feature/AdminAuthTest.php` dan `tests/Feature/AdminSettingUserTest.php` dibuat dan lulus.

**Verification:**
- [ ] `vendor/bin/phpunit tests/Feature/AdminAuthTest.php` lulus.
- [ ] `vendor/bin/phpunit tests/Feature/AdminSettingUserTest.php` lulus.

**Dependencies:** Task 8
**Files likely touched:**
- `src/controllers/admin/AdminAuthController.php`
- `src/services/admin/AdminAuthService.php`
- `src/validators/admin/AdminAuthValidator.php`
- `src/controllers/admin/AdminSettingUserController.php`
- `src/services/admin/AdminSettingUserService.php`
- `src/validators/admin/AdminSettingUserValidator.php`
- `tests/Feature/AdminAuthTest.php` (baru)
- `tests/Feature/AdminSettingUserTest.php` (baru)
**Estimated scope:** Medium-Large (8 files)

---

### Task 10: Refactor Modul Content (FAQ, Gallery, Contact, Team)
**Description:** Merefaktor modul konten bawaan untuk Admin dan Client (FAQ, Gallery, Contact, dan Team - Opsi A dipertahankan) ke standar baru: DI auto-wiring, Rakit validation, ApiResponse, paginasi pada endpoint list, dan feature tests.

**Acceptance criteria:**
- [ ] Modul Content FAQ (Admin & Client) direfaktor ke standar baru.
- [ ] Modul Content Gallery (Admin & Client) direfaktor ke standar baru.
- [ ] Modul Content Contact (Admin & Client) direfaktor ke standar baru.
- [ ] Modul Content Team (Admin & Client) direfaktor ke standar baru (sesuai Opsi A).
- [ ] Seluruh endpoint list mengembalikan format bersarang `{ items: [], pagination: {} }`.
- [ ] Feature tests untuk masing-masing modul dibuat dan lulus 100%.

**Verification:**
- [ ] `vendor/bin/phpunit tests/Feature/AdminContent*Test.php` dan `ClientContent*Test.php` lulus.

**Dependencies:** Task 8, Task 9
**Files likely touched:**
- `src/controllers/admin/AdminContent*Controller.php` (4 files)
- `src/services/admin/AdminContent*Service.php` (4 files)
- `src/validators/admin/AdminContent*Validator.php` (4 files)
- `src/controllers/client/ClientContent*Controller.php` (4 files)
- `src/services/client/ClientContent*Service.php` (4 files)
- `src/validators/client/ClientContent*Validator.php` (4 files)
- `tests/Feature/AdminContent*Test.php` & `ClientContent*Test.php` (test files)
**Estimated scope:** Large (akan dieksekusi bertahap per modul)

---

## Checkpoint 4: Core Framework Modules 100% Green
- [ ] Seluruh modul bawaan (Auth, User, FAQ, Gallery, Contact, Team) telah bermigrasi.
- [ ] Seluruh feature test modul dasar lulus hijau.

---

## Phase 5: Database Schema Alignment (Multi-site, Article Agent, Article Stats)

### Task 11: Database Migrations untuk Multi-site, Article Agent & Article Stats
**Description:** Menyelaraskan skema database untuk mendukung fungsionalitas artikel modern: menambahkan kolom `site` pada `tb_article` dan `tb_article_category` (Opsi A), menambahkan tabel `tb_article_stats`, dan menambahkan migrasi tabel `tb_article_agent`.

**Acceptance criteria:**
- [ ] Migrasi `20260109155110_create_tables.php` diperbarui untuk menyertakan kolom `site` (limit 250, default `'default'`) pada `tb_article` dan `tb_article_category`.
- [ ] Tabel `tb_article_stats` didefinisikan (dengan field `site`, `slug`, `ip_address`, `user_agent`, `country`, `country_code`, dsb) beserta indeksnya.
- [ ] File migrasi `src/migrations/20260120143000_create_article_agent_table.php` ditambahkan untuk tabel `tb_article_agent`.

**Verification:**
- [ ] `composer migrate:status` atau validasi migrasi Phinx berhasil mengenali skema baru.

**Dependencies:** Task 1
**Files likely touched:**
- `src/migrations/20260109155110_create_tables.php`
- `src/migrations/20260120143000_create_article_agent_table.php` (baru)
**Estimated scope:** Small (2 files)

---

### Task 12: Eloquent Models Alignment
**Description:** Memperbarui model Eloquent yang ada (`Article`, `ArticleCategory`) dan menambahkan model baru (`ArticleAgent`, `ArticleStats`) dengan relasi dan casting yang tepat.

**Acceptance criteria:**
- [ ] `src/models/Article.php` diperbarui dengan relasi `category`, `picture`, `authorUser`, `seoMeta`, `articleAgent`, `cronhooks`, dan cast `featured` array.
- [ ] `src/models/ArticleCategory.php` diperbarui.
- [ ] `src/models/ArticleAgent.php` dibuat dengan casts `brainstorming_options` dan `selected_option_data`.
- [ ] `src/models/ArticleStats.php` dibuat.

**Verification:**
- [ ] Semua model Eloquent dapat di-load tanpa error.

**Dependencies:** Task 11
**Files likely touched:**
- `src/models/Article.php`
- `src/models/ArticleCategory.php`
- `src/models/ArticleAgent.php` (baru)
- `src/models/ArticleStats.php` (baru)
**Estimated scope:** Small (4 files)

---

## Checkpoint 5: Database & Models Aligned
- [ ] Skema database Phinx terdefinisi dengan rapi.
- [ ] Seluruh Model Eloquent tersinkronisasi.

---

## Phase 6: Article & Article Category System v2

### Task 13: Porting Modul Admin Article & Article Category v2
**Description:** Mengganti modul Admin Article dan Article Category di `kit/api` dengan versi modern dari `news/api`: DI auto-wiring, Rakit validation, ApiResponse, paginasi dengan caching Valkey, sorting (`orderBy`, `orderDir`), filter status/site/featured/category, method `statusChange`, `publishChange`, integrasi `NextRevalidator`, dan pembuatan feature tests.

**Acceptance criteria:**
- [ ] `AdminArticleController`, `AdminArticleService`, `AdminArticleValidator` mengimplementasikan seluruh fitur v2.
- [ ] `AdminArticleCategoryController`, `AdminArticleCategoryService`, `AdminArticleCategoryValidator` mengimplementasikan seluruh fitur v2.
- [ ] Cache key Valkey memuat parameter paginasi dan filter secara dinamis.
- [ ] Feature tests `tests/Feature/AdminArticleTest.php` dan `tests/Feature/AdminArticleCategoryTest.php` dibuat dan lulus.

**Verification:**
- [ ] `vendor/bin/phpunit tests/Feature/AdminArticleTest.php` lulus.
- [ ] `vendor/bin/phpunit tests/Feature/AdminArticleCategoryTest.php` lulus.

**Dependencies:** Task 12
**Files likely touched:**
- `src/controllers/admin/AdminArticleController.php`
- `src/services/admin/AdminArticleService.php`
- `src/validators/admin/AdminArticleValidator.php`
- `src/controllers/admin/AdminArticleCategoryController.php`
- `src/services/admin/AdminArticleCategoryService.php`
- `src/validators/admin/AdminArticleCategoryValidator.php`
- `tests/Feature/AdminArticleTest.php` (baru)
- `tests/Feature/AdminArticleCategoryTest.php` (baru)
**Estimated scope:** Medium (8 files)

---

### Task 14: Porting Modul Client Article & Article Category v2
**Description:** Mengganti modul Client Article dan Article Category di `kit/api` dengan versi modern: DI auto-wiring, ApiResponse, paginasi `{ items, pagination }`, filter pencarian, filter kategori & featured, endpoint `similar` (artikel terkait), caching Valkey, dan feature tests.

**Acceptance criteria:**
- [ ] `ClientArticleController`, `ClientArticleService`, `ClientArticleValidator` mengimplementasikan fitur v2 (termasuk method `similar`).
- [ ] Endpoint `/client/article/publish` lama dilepas (karena dipindahkan ke `JobController`).
- [ ] `ClientArticleCategoryController`, `ClientArticleCategoryService`, `ClientArticleCategoryValidator` mengimplementasikan fitur v2.
- [ ] Feature tests `tests/Feature/ClientArticleTest.php` dan `tests/Feature/ClientArticleCategoryTest.php` dibuat dan lulus.

**Verification:**
- [ ] `vendor/bin/phpunit tests/Feature/ClientArticleTest.php` lulus.
- [ ] `vendor/bin/phpunit tests/Feature/ClientArticleCategoryTest.php` lulus.

**Dependencies:** Task 13
**Files likely touched:**
- `src/controllers/client/ClientArticleController.php`
- `src/services/client/ClientArticleService.php`
- `src/validators/client/ClientArticleValidator.php`
- `src/controllers/client/ClientArticleCategoryController.php`
- `src/services/client/ClientArticleCategoryService.php`
- `src/validators/client/ClientArticleCategoryValidator.php`
- `tests/Feature/ClientArticleTest.php` (baru)
- `tests/Feature/ClientArticleCategoryTest.php` (baru)
**Estimated scope:** Medium (8 files)

---

## Checkpoint 6: Article & Category Modules Functional & Tested
- [ ] Seluruh endpoint Admin & Client Article bekerja dengan pagination, caching, dan filtering.
- [ ] Automated tests Article & Category 100% green.

---

## Phase 7: Article Agent (AI Writer) System

### Task 15: Integrasi AI Clients di `src/lib`
**Description:** Memindahkan library klien AI dari `news/api` ke `kit/api`: `GeminiClient.php` (Google Gemini API), `OpenAIClient.php` (OpenAI API), dan `TavilyClient.php` (Tavily Search API).

**Acceptance criteria:**
- [ ] `src/lib/GeminiClient.php` dibuat dan mendukung generasi teks AI.
- [ ] `src/lib/OpenAIClient.php` dibuat dan mendukung generasi teks AI.
- [ ] `src/lib/TavilyClient.php` dibuat dan mendukung web search kontekstual.
- [ ] Klien-klien AI terdaftar dalam container `src/config/dependencies.php`.

**Verification:**
- [ ] Class library dapat di-instantiate via container PHP-DI.

**Dependencies:** Task 2
**Files likely touched:**
- `src/lib/GeminiClient.php` (baru)
- `src/lib/OpenAIClient.php` (baru)
- `src/lib/TavilyClient.php` (baru)
- `src/config/dependencies.php`
**Estimated scope:** Small (4 files)

---

### Task 16: Porting Modul Admin Article Agent
**Description:** Mengimplementasikan modul `AdminArticleAgent` lengkap (Controller, Service, Validator) untuk alur kerja pembuatan artikel berbasis AI (3 langkah: brainstorming topik dengan strategi SEO gap/cluster/formula, riset web Tavily dengan sanitasi otomatis, dan penulisan artikel lengkap) beserta feature tests.

**Acceptance criteria:**
- [ ] `AdminArticleAgentController` dibuat dengan endpoint `step1_inputTopic`, `step1_inputSelection`, `step2_doResearch`, `step3_doWrite`, dan `detail`.
- [ ] `AdminArticleAgentService` dibuat dengan integrasi Gemini, OpenAI, dan Tavily.
- [ ] `AdminArticleAgentValidator` dibuat dengan aturan validasi tiap step via Rakit Validator.
- [ ] Feature test `tests/Feature/AdminArticleAgentTest.php` dibuat dan lulus.

**Verification:**
- [ ] `vendor/bin/phpunit tests/Feature/AdminArticleAgentTest.php` lulus.

**Dependencies:** Task 12, Task 15
**Files likely touched:**
- `src/controllers/admin/AdminArticleAgentController.php` (baru)
- `src/services/admin/AdminArticleAgentService.php` (baru)
- `src/validators/admin/AdminArticleAgentValidator.php` (baru)
- `tests/Feature/AdminArticleAgentTest.php` (baru)
**Estimated scope:** Medium (4 files)

---

## Checkpoint 7: AI Article Agent Operational
- [ ] Library klien AI siap pakai.
- [ ] Alur 3-step Article Agent berfungsi dan teruji via automated test.

---

## Phase 8: Stats System & Job Automation

### Task 17: Porting Modul Admin Stats v2
**Description:** Memperbarui modul `AdminStats` (Controller, Service, Validator) di `kit/api` dengan seluruh kapabilitas v2: log audit dengan paginasi dan formatting diff perubahan data, `articleCount`, `listCronArticle` (jadwal cron yang tertunda), `listArticleVisits` (grafik kunjungan harian dengan zero-filling), dan `listArticleVisitsBySlug` (peringkat kunjungan per slug artikel), serta feature tests.

**Acceptance criteria:**
- [ ] `AdminStatsController` mengimplementasikan method `listLog`, `dropLog`, `articleCount`, `listCronArticle`, `listArticleVisits`, dan `listArticleVisitsBySlug`.
- [ ] `AdminStatsService` menerapkan paginasi dan kalkulasi date range zero-filling.
- [ ] `AdminStatsValidator` memvalidasi parameter tanggal dan input query via Rakit.
- [ ] Feature test `tests/Feature/AdminStatsTest.php` dibuat dan lulus.

**Verification:**
- [ ] `vendor/bin/phpunit tests/Feature/AdminStatsTest.php` lulus.

**Dependencies:** Task 12
**Files likely touched:**
- `src/controllers/admin/AdminStatsController.php`
- `src/services/admin/AdminStatsService.php`
- `src/validators/admin/AdminStatsValidator.php`
- `tests/Feature/AdminStatsTest.php` (baru)
**Estimated scope:** Medium (4 files)

---

### Task 18: Job Automation System (`JobController` & `JobService`)
**Description:** Mengimplementasikan modul background job dan webhook callback: `NextRevalidator` (invalidation cache frontend ISR Next.js), `JobController`, `JobService`, dan `JobValidator` untuk memproses auto-publish artikel dari Cronhooks, sinkronisasi antrean kunjungan Valkey ke tabel `tb_article_stats`, dan pelacakan kunjungan via antrean Valkey.

**Acceptance criteria:**
- [ ] `src/lib/NextRevalidator.php` dibuat untuk revalidasi cache Next.js.
- [ ] `src/controllers/JobController.php`, `src/services/JobService.php`, dan `src/validators/JobValidator.php` dibuat.
- [ ] Endpoint `/job/article_publish`, `/job/article_sync_stats`, dan `/job/article_track_stats` tersedia.
- [ ] Feature test `tests/Feature/JobTest.php` dibuat dan lulus.

**Verification:**
- [ ] `vendor/bin/phpunit tests/Feature/JobTest.php` lulus.

**Dependencies:** Task 12, Task 17
**Files likely touched:**
- `src/lib/NextRevalidator.php` (baru)
- `src/controllers/JobController.php` (baru)
- `src/services/JobService.php` (baru)
- `src/validators/JobValidator.php` (baru)
- `tests/Feature/JobTest.php` (baru)
**Estimated scope:** Medium (5 files)

---

## Checkpoint 8: Stats & Job System Verified
- [ ] Statistik kunjungan dan audit log bekerja dengan paginasi.
- [ ] Sistem job publish dan sinkronisasi stats berjalan dengan baik dan teruji.

---

## Phase 9: Routing, Code Generator CLI & Final Polish

### Task 19: Routes Modernization (`Routes.php`)
**Description:** Memperbarui `src/routes/Routes.php` untuk memanfaatkan auto-wiring penuh (menggunakan class-string `[AdminArticleController::class, 'method']` alih-alih instantiasi manual `$controller = new ...`), mendaftarkan rute baru untuk `article_agent`, `job`, rute baru `stats`, serta memastikan rute `content_team` tetap terintegrasi secara rapi.

**Acceptance criteria:**
- [ ] Semua controller di `Routes.php` dipanggil dengan format class string (`Controller::class`).
- [ ] Rute grup `/admin/article_agent` didaftarkan.
- [ ] Rute grup `/job` didaftarkan.
- [ ] Rute grup `/admin/content_team` dan `/client/content_team` menggunakan standar baru.
- [ ] `TokenMiddleware::class` digunakan untuk auto-wiring.

**Verification:**
- [ ] Seluruh endpoint dalam route map dapat di-resolve oleh router Slim tanpa error.

**Dependencies:** Task 4, Task 10, Task 14, Task 16, Task 18
**Files likely touched:**
- `src/routes/Routes.php`
**Estimated scope:** Medium (1 file)

---

### Task 20: Unified CLI Generator (`assets/make_module.php`)
**Description:** Menyatukan generator modul CLI lama (`make_module_admin.php` dan `make_module_client.php`) menjadi satu script generator modern `assets/make_module.php` yang menghasilkan template Controller, Service, dan Validator sesuai standar arsitektur baru (PHP 8 constructor promotion, DI auto-wiring, Rakit validator, dan ApiResponse).

**Acceptance criteria:**
- [ ] `assets/make_module.php` dibuat, mendukung argumen `admin` dan `client`.
- [ ] `composer.json` mengarahkan `make:module:admin` dan `make:module:client` ke `make_module.php`.
- [ ] File generator lama (`make_module_admin.php`, `make_module_client.php`) dihapus.

**Verification:**
- [ ] Perintah `php assets/make_module.php admin TestDummy` menghasilkan file berarsitektur modern tanpa error sintaks.

**Dependencies:** Task 1, Task 10
**Files likely touched:**
- `assets/make_module.php` (baru)
- `assets/make_module_admin.php` (dihapus)
- `assets/make_module_client.php` (dihapus)
- `composer.json`
**Estimated scope:** Small (4 files)

---

### Task 21: Full Test Suite Verification & Dokumentasi SlimeKit v2
**Description:** Menjalankan seluruh rangkaian tes otomatis (Unit & Feature tests) untuk memvalidasi bahwa tidak ada regresi atau kegagalan. Memperbarui `README.md` dengan dokumentasi resmi arsitektur SlimeKit v2, panduan Dockerfile.dev/prod, konfigurasi environment, AI Article Agent, dan panduan CLI.

**Acceptance criteria:**
- [ ] Perintah `composer test` (atau `vendor/bin/phpunit`) berjalan dan **100% test lulus** (0 failure, 0 error).
- [ ] File `README.md` diperbarui mencerminkan arsitektur SlimeKit v2 dan petunjuk penggunaan terperinci.
- [ ] Workspace bersih dari file sementara atau artefak yang tidak diinginkan.

**Verification:**
- [ ] Seluruh automated test suite lulus hijau.
- [ ] Status git clean dan siap untuk commit versi rilis.

**Dependencies:** Task 1 s/d Task 20
**Files likely touched:**
- `README.md`
**Estimated scope:** Small (1 file)

---

## Final Checkpoint: SlimeKit v2 Complete & Verified ✅
- [ ] Seluruh 21 Task terselesaikan.
- [ ] Rangkaian test suite otomatis 100% green.
- [ ] Framework siap digunakan sebagai fondasi proyek-proyek masa depan.
