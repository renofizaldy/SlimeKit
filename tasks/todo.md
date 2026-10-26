# Todo: Pembersihan Total Fitur Multi-Site (Pure Single-Tenant Architecture)

Daftar tugas terstruktur untuk mengeliminasi kolom, parameter, dan logika multi-site (`site`) dari SlimeKit.

---

## Phase 1: Database Schema, Models & NextRevalidator Simplification

### Task 1: Database Migration Schema Cleanup & Global Unique Slugs

**Description:**
Memperbarui file migrasi Phinx agar skema database SlimeKit murni single-tenant sejak awal. Pada file migrasi awal `20260109155110_create_tables.php`, hapus deklarasi kolom `site` dari tabel `tb_article`, `tb_article_category`, dan `tb_article_stats`. Tambahkan constraint `unique` pada kolom `slug` di `tb_article` dan `tb_article_category`, serta ubah indeks komposit `idx_article_stats_site_slug` menjadi indeks pada kolom `slug` saja. Pada file migrasi `20261008234500_add_trgm_and_performance_indexes_to_articles.php`, ubah indeks komposit `(site, status, slug)` menjadi `(status, slug)` dan `(site, status, publish)` menjadi `(status, publish)`.

**Acceptance criteria:**
- [x] `src/migrations/20260109155110_create_tables.php` tidak lagi memuat kolom `site` pada `tb_article`, `tb_article_category`, dan `tb_article_stats`.
- [x] Kolom `slug` pada `tb_article` dan `tb_article_category` memiliki indeks unik (`unique => true`).
- [x] `src/migrations/20261008234500_add_trgm_and_performance_indexes_to_articles.php` mendefinisikan indeks tanpa kolom `site`: `idx_tb_article_status_slug`, `idx_tb_article_status_publish`, dan `idx_tb_article_cat_status_slug`.

**Verification:**
- [x] Pemeriksaan sintaks: `php -l src/migrations/20260109155110_create_tables.php` dan `php -l src/migrations/20261008234500_add_trgm_and_performance_indexes_to_articles.php`

**Dependencies:** None
**Files likely touched:**
- `src/migrations/20260109155110_create_tables.php`
- `src/migrations/20261008234500_add_trgm_and_performance_indexes_to_articles.php`
**Estimated scope:** Small (2 files)

---

### Task 2: Models & Configuration Cleanup

**Description:**
Menghapus `'site'` dari properti `$fillable` pada Model Eloquent `Article`, `ArticleCategory`, dan `ArticleStats`. Memperbarui konfigurasi `src/config/services.php` dan file `.env.example` untuk menghapus variabel `ALLOWED_SITES` serta mengganti konfigurasi `next_revalidator` dari format `NEXT_REVALIDATE_ENDPOINTS` (mapping) menjadi single target `NEXT_REVALIDATE_URL`.

**Acceptance criteria:**
- [x] `Article.php`, `ArticleCategory.php`, dan `ArticleStats.php` tidak lagi menyertakan `'site'` di dalam `$fillable`.
- [x] `src/config/services.php` menggunakan `'url' => $_ENV['NEXT_REVALIDATE_URL'] ?? ''` pada kunci `'next_revalidator'`.
- [x] `.env.example` memuat `NEXT_REVALIDATE_URL=http://localhost:3000/api/revalidate` dan menghapus `ALLOWED_SITES` serta `NEXT_REVALIDATE_ENDPOINTS`.

**Verification:**
- [x] Pemeriksaan sintaks: `php -l src/models/Article.php`, `php -l src/models/ArticleCategory.php`, `php -l src/models/ArticleStats.php`, `php -l src/config/services.php`

**Dependencies:** Task 1
**Files likely touched:**
- `src/models/Article.php`
- `src/models/ArticleCategory.php`
- `src/models/ArticleStats.php`
- `src/config/services.php`
- `.env.example`
**Estimated scope:** Medium (5 files)

---

### Task 3: NextRevalidator & General Helper Simplification

**Description:**
Menyederhanakan library `NextRevalidator` agar langsung menembak single URL tujuan webhook ISR Next.js tanpa memecah string dengan format `site=url`. Pada `src/helpers/General.php`, hapus seluruh method `getAllowedSites()` dan `isValidSite()`. Hapus juga filter parameter `$site` dari method `getTopArticles()`, `getCategories()`, dan `getRecentArticles()`.

**Acceptance criteria:**
- [x] `src/lib/NextRevalidator.php` menyimpan properti tunggal `$this->url` dan method `revalidateArticle(string $slug, ?string $oldSlug = null): bool` tidak lagi menerima argumen `$site`.
- [x] Method `getAllowedSites()` dan `isValidSite()` dihapus dari `src/helpers/General.php`.
- [x] Method `getTopArticles($limit = 50)` tidak lagi memfilter `where('site', ...)`.
- [x] Method `getCategories()` dan `getRecentArticles(int $limit = 75)` tidak lagi menerima `$site`.

**Verification:**
- [x] Pemeriksaan sintaks: `php -l src/lib/NextRevalidator.php` dan `php -l src/helpers/General.php`

**Dependencies:** Task 2
**Files likely touched:**
- `src/lib/NextRevalidator.php`
- `src/helpers/General.php`
**Estimated scope:** Small (2 files)

---

### Checkpoint: Foundation, Schema & Helpers (Tasks 1-3)
- [x] Seluruh file migrasi, model, helper, dan NextRevalidator bebas sintaks error
- [x] Konfigurasi services dan environment bersih dari variabel multi-site

---

## Phase 2: Article & Category Modules Purge (Admin & Client)

### Task 4: Admin & Client Article Validators & Services Cleanup

**Description:**
Menghilangkan parameter `site` dari lapisan validasi dan service artikel. Pada `AdminArticleValidator` dan `ClientArticleValidator`, hapus aturan `'site' => 'required'` dan hapus `'site'` dari opsi `orderBy`. Pada `AdminArticleService`, hapus parameter `site` dari method `checkSlug`, `list`, `detail`, `add`, `edit`, dan `drop`. Sederhanakan pola cache key Valkey tanpa segmen `site=%s:`, serta perbarui pemanggilan `revalidateArticle($slug, $oldSlug)`. Pada `ClientArticleService`, hapus klausa `where('site', ...)` pada query `list`, `detail`, dan `similar`, serta sederhanakan cache key Valkey.

**Acceptance criteria:**
- [x] `AdminArticleValidator.php` dan `ClientArticleValidator.php` tidak lagi mewajibkan parameter `site`.
- [x] `AdminArticleService.php` memvalidasi keunikan slug secara global di `tb_article` (`where('slug', $input['slug'])` tanpa filter site).
- [x] Cache key `article:list:`, `article:detail:`, dan `article:similar:` tidak lagi memuat format `site=%s:`.
- [x] `ClientArticleService.php` melayani list, detail, dan similar artikel tanpa memerlukan input `site`.

**Verification:**
- [x] Pemeriksaan sintaks: `php -l src/validators/admin/AdminArticleValidator.php`, `php -l src/validators/client/ClientArticleValidator.php`, `php -l src/services/admin/AdminArticleService.php`, `php -l src/services/client/ClientArticleService.php`

**Dependencies:** Task 3
**Files likely touched:**
- `src/validators/admin/AdminArticleValidator.php`
- `src/validators/client/ClientArticleValidator.php`
- `src/services/admin/AdminArticleService.php`
- `src/services/client/ClientArticleService.php`
**Estimated scope:** Medium (4 files)

---

### Task 5: Category & Stats Services & Validators Cleanup

**Description:**
Menghilangkan dependensi parameter `site` pada modul kategori artikel dan statistik dashboard. Hapus aturan `'site' => 'required'` pada `AdminArticleCategoryValidator`, `ClientArticleCategoryValidator`, dan `AdminStatsValidator`. Pada `AdminArticleCategoryService` dan `ClientArticleCategoryService`, hapus filter dan input `site` serta format cache key `site=%s:`. Pada `AdminStatsService`, hapus filter `where('site', ...)` pada query `totalArticle`, `listCronArticle`, `listArticleVisits`, dan `listArticleVisitsBySlug`.

**Acceptance criteria:**
- [x] Validator kategori (`AdminArticleCategoryValidator`, `ClientArticleCategoryValidator`) dan `AdminStatsValidator` tidak lagi mewajibkan parameter `site`.
- [x] Keunikan slug kategori di `AdminArticleCategoryService::checkSlug()` bersifat global.
- [x] Cache key kategori `article_category:list:` bersih dari parameter site.
- [x] Method statistik di `AdminStatsService` menghitung metrik secara global tanpa pemisahan site.

**Verification:**
- [x] Pemeriksaan sintaks: `php -l src/validators/admin/AdminArticleCategoryValidator.php`, `php -l src/validators/client/ClientArticleCategoryValidator.php`, `php -l src/validators/admin/AdminStatsValidator.php`, `php -l src/services/admin/AdminArticleCategoryService.php`, `php -l src/services/client/ClientArticleCategoryService.php`, `php -l src/services/admin/AdminStatsService.php`

**Dependencies:** Task 4
**Files likely touched:**
- `src/validators/admin/AdminArticleCategoryValidator.php`
- `src/validators/client/ClientArticleCategoryValidator.php`
- `src/validators/admin/AdminStatsValidator.php`
- `src/services/admin/AdminArticleCategoryService.php`
- `src/services/client/ClientArticleCategoryService.php`
- `src/services/admin/AdminStatsService.php`
**Estimated scope:** Large (6 files)

---

### Task 6: AI Article Agent & Job Background Services Cleanup

**Description:**
Menghapus parameter `site` dari modul AI Article Agent dan Background Job. Pada `AdminArticleAgentValidator` dan `JobValidator`, hapus aturan `'site' => 'required'`. Pada `AdminArticleAgentService::brainstorm()`, hapus penerimaan `$input['site']`, panggil `$this->helper->getCategories()` dan `$this->helper->getRecentArticles(75)` tanpa argumen site. Pada `JobService`, hapus penanganan `site` pada `articlePublish()` dan `articleTrackStats()`, sederhanakan method `invalidateArticleCache()` untuk membersihkan cache global, dan panggil `revalidateArticle($check->slug)` tanpa parameter site.

**Acceptance criteria:**
- [x] `AdminArticleAgentValidator.php` dan `JobValidator.php` tidak lagi mewajibkan parameter `site`.
- [x] `AdminArticleAgentService::brainstorm()` berjalan secara global untuk konten situs tanpa filter site.
- [x] `JobService::articlePublish()` dan `JobService::articleTrackStats()` berhasil memproses publikasi dan pencatatan statistik tanpa parameter `site`.
- [x] `JobService::invalidateArticleCache()` menghapus cache prefix `article:list:`, `article:detail:`, `article:similar:`, dan `article:admin`.

**Verification:**
- [x] Pemeriksaan sintaks: `php -l src/validators/admin/AdminArticleAgentValidator.php`, `php -l src/validators/JobValidator.php`, `php -l src/services/admin/AdminArticleAgentService.php`, `php -l src/services/JobService.php`

**Dependencies:** Task 5
**Files likely touched:**
- `src/validators/admin/AdminArticleAgentValidator.php`
- `src/validators/JobValidator.php`
- `src/services/admin/AdminArticleAgentService.php`
- `src/services/JobService.php`
**Estimated scope:** Medium (4 files)

---

### Checkpoint: Business Layer & Endpoints (Tasks 4-6)
- [x] Seluruh controller, service, dan validator bebas dari parameter dan logika `site`
- [x] Seluruh file PHP valid sintaks tanpa exception

---

## Phase 3: Test Suite Alignment & Full Green Verification

### Task 7: Obsolete Multi-Site Test Files Removal & Unit Tests Alignment

**Description:**
Menghapus file unit test yang secara spesifik menguji validasi multi-site yang kini tidak lagi ada: `tests/Unit/GeneralSiteValidationTest.php` dan `tests/Unit/BlokbacaCacheAndInvalidationWorkflowTest.php`. Menyesuaikan unit test yang sebelumnya menyertakan parameter `site`: `tests/Unit/GeneralRecentArticlesTest.php`, `tests/Unit/AdminArticleAgentServiceBrainstormTest.php`, `tests/Unit/AdminArticleCacheInvalidationTest.php`, `tests/Unit/AdminArticleCategoryCacheInvalidationTest.php`, dan `tests/Unit/ArticleLargeImageTest.php`.

**Acceptance criteria:**
- [x] File `tests/Unit/GeneralSiteValidationTest.php` dan `tests/Unit/BlokbacaCacheAndInvalidationWorkflowTest.php` dihapus.
- [x] Seluruh unit test artikel, invalidasi cache, dan agent brainstorm disesuaikan dengan signature baru tanpa parameter site.
- [x] Seluruh unit test lulus: `composer test:unit`.

**Verification:**
- [x] Menjalankan test: `composer test:unit`

**Dependencies:** Task 6
**Files likely touched:**
- `tests/Unit/GeneralSiteValidationTest.php` (dihapus)
- `tests/Unit/BlokbacaCacheAndInvalidationWorkflowTest.php` (dihapus)
- `tests/Unit/GeneralRecentArticlesTest.php`
- `tests/Unit/AdminArticleAgentServiceBrainstormTest.php`
- `tests/Unit/AdminArticleCacheInvalidationTest.php`
- `tests/Unit/AdminArticleCategoryCacheInvalidationTest.php`
- `tests/Unit/ArticleLargeImageTest.php`
**Estimated scope:** Large (7 files)

---

### Task 8: Feature Test Suite Alignment & Full Pass (`composer test`)

**Description:**
Memperbarui seluruh Feature Test di direktori `tests/Feature/` yang sebelumnya mengirimkan parameter `site` pada request query atau body payload. Endpoint kini tidak lagi mengharapkan `'site'`, sehingga dummy request diuji secara murni dengan payload single-tenant. Jalankan keseluruhan test suite PHPUnit untuk memastikan seluruh endpoint bekerja sempurna tanpa regresi.

**Acceptance criteria:**
- [x] Feature test `AdminArticleTest`, `ClientArticleTest`, `AdminArticleCategoryTest`, `ClientArticleCategoryTest`, `AdminStatsTest`, `AdminArticleAgentTest`, dan `JobTest` diperbarui tanpa parameter `site`.
- [x] Menjalankan `composer test:feature` menghasilkan status 100% Pass (hijau).
- [x] Menjalankan `composer test` menghasilkan status 100% Pass (hijau).

**Verification:**
- [x] Menjalankan feature tests: `composer test:feature`
- [x] Menjalankan full suite: `composer test`

**Dependencies:** Task 7
**Files likely touched:**
- `tests/Feature/AdminArticleTest.php`
- `tests/Feature/ClientArticleTest.php`
- `tests/Feature/AdminArticleCategoryTest.php`
- `tests/Feature/ClientArticleCategoryTest.php`
- `tests/Feature/AdminStatsTest.php`
- `tests/Feature/AdminArticleAgentTest.php`
- `tests/Feature/JobTest.php`
**Estimated scope:** Large (7 files)

---

### Checkpoint: Complete Verification
- [x] 8 Task selesai dan ditandai `[x]`
- [x] Seluruh unit dan feature tests berstatus hijau (`composer test`)
- [x] Repositori SlimeKit murni single-tenant dan bersih dari segala jejak multi-site
