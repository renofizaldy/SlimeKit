# Todo: Penyelarasan SlimeKit v2.1 dari SlimeNews/api

Panduan eksekusi teknis terstruktur per-task dengan kriteria keberhasilan dan perintah verifikasi mandiri.

---

## Phase 1: Core Infrastructure Resilience & Security Hardening

### Task 1: Valkey Circuit Breaker & Connection Resilience

**Description:**
Menambahkan mekanisme *Circuit Breaker* pada client Valkey/Redis untuk mencegah *thread/connection stall* saat server Valkey sedang down. Ketika terjadi exception koneksi, file marker `valkey_down.marker` dibuat di direktori temp OS, dan permintaan berikutnya selama periode cooldown (30 detik) langsung menganggap koneksi offline tanpa mencoba menyambung ulang yang berakibat lambatnya waktu respon. Menambahkan juga method alias `incr()`.

**Acceptance criteria:**
- [x] `src/lib/Valkey.php` memiliki konstanta `DOWN_COOLDOWN = 30`.
- [x] Implementasi method `isCircuitBreakerOpen(): bool`, `markDown(?Exception $e): void`, `markUp(): void`, `getCircuitBreakerFile(): ?string`, dan `setCircuitBreakerFile(string $path): void`.
- [x] Operasi set/get/del/scan/flushdb otomatis memanggil `markDown()` saat terjadi Exception dan `markUp()` saat berhasil.
- [x] Method `incr($key)` tersedia sebagai alias untuk `increment($key)`.
- [x] Unit test `tests/Unit/ValkeyTest.php` disalin dari SlimeNews dan lulus 100%.

**Verification:**
- [x] Pemeriksaan sintaks: `php -l src/lib/Valkey.php`
- [x] Menjalankan test: `php vendor/bin/phpunit tests/Unit/ValkeyTest.php`

**Dependencies:** None
**Files likely touched:**
- `src/lib/Valkey.php`
- `tests/Unit/ValkeyTest.php`
**Estimated scope:** Small (2 files)

---

### Task 2: Database Connection & Pooler Hardening (PDO Emulate Prepares)

**Description:**
Menerapkan konfigurasi `PDO::ATTR_EMULATE_PREPARES => true` pada inisialisasi Capsule Eloquent ORM di `src/lib/Database.php` dan `src/config/services.php`. Konfigurasi ini krusial untuk mencegah kegagalan *prepared statements* saat SlimeKit dihubungkan ke connection pooler PostgreSQL tingkat produksi (seperti Supabase Connection Pooler / PgBouncer dalam mode transaksi).

**Acceptance criteria:**
- [x] `src/config/services.php` menambahkan opsi `\PDO::ATTR_EMULATE_PREPARES => true` di bawah kunci konfigurasi database.
- [x] `src/lib/Database.php` menggabungkan opsi konfigurasi PDO dengan default `ATTR_EMULATE_PREPARES => true` melalui `array_replace`.
- [x] Koneksi database Eloquent Capsule tetap berfungsi normal untuk semua operasi query.

**Verification:**
- [x] Pemeriksaan sintaks: `php -l src/lib/Database.php` dan `php -l src/config/services.php`
- [x] Menjalankan database test: `php vendor/bin/phpunit --filter RoutesTest`

**Dependencies:** None
**Files likely touched:**
- `src/lib/Database.php`
- `src/config/services.php`
**Estimated scope:** Small (2 files)

---

### Task 3: Auth & Token Hardening (Tokenize Custom Expiration & AuthMiddleware DI Fix)

**Description:**
Menyempurnakan library `Tokenize` agar mendukung kustomisasi durasi kedaluwarsa token JWT (`$customExp`) yang diperlukan untuk static job worker token. Memperbaiki method `verify_time` dengan blok `try-catch` defensif. Pada `AuthMiddleware.php`, hapus pembuatan instansiasi manual `$this->helper = new General;` yang melanggar kontrak DI container PHP-DI dan standarisasi ekstraksi kode status HTTP error.

**Acceptance criteria:**
- [x] `Tokenize::encode(array $data = [], ?int $customExp = null)` mendukung durasi kedaluwarsa kustom.
- [x] `Tokenize::verify_time($token)` membungkus decode dalam `try-catch (\Throwable)` dan mengecek `isset($decode->exp)`.
- [x] `src/middlewares/AuthMiddleware.php` tidak lagi menginstansiasi `new General` secara manual.
- [x] Penanganan kode status HTTP pada exception di `AuthMiddleware` diverifikasi berada dalam rentang `100 - 599`.
- [x] Unit test `tests/Unit/TokenMiddlewareTest.php` disalin dan lulus 100%.

**Verification:**
- [x] Pemeriksaan sintaks: `php -l src/lib/Tokenize.php` dan `php -l src/middlewares/AuthMiddleware.php`
- [x] Menjalankan test: `php vendor/bin/phpunit tests/Unit/TokenMiddlewareTest.php` dan `php vendor/bin/phpunit tests/Feature/AdminAuthTest.php`

**Dependencies:** Task 1, Task 2
**Files likely touched:**
- `src/lib/Tokenize.php`
- `src/middlewares/AuthMiddleware.php`
- `tests/Unit/TokenMiddlewareTest.php`
**Estimated scope:** Small (3 files)

---

### Checkpoint: Foundation & Resilience (Tasks 1-3)
- [x] Seluruh file sintaks valid (`php -l`)
- [x] Unit test `ValkeyTest` dan `TokenMiddlewareTest` lulus
- [x] Tidak ada breaking change pada route admin auth eksisting

---

## Phase 2: Background Job Protection & Database Backup System

### Task 4: Static Token Protection & CLI Generator for `/job/*`

**Description:**
Mengamankan grup endpoint `/job/*` di `src/routes/Routes.php` dengan `TokenMiddleware` sehingga hanya dapat dipanggil oleh background worker berlisensi dengan Bearer JWT valid. Membuat skrip CLI generator `assets/generate_job_token.php` dan mendaftarkan shortcut `"generate:job-token"` di `composer.json` untuk menghasilkan token 10 tahun bagi scheduler. Menambahkan konfigurasi `JOB_STATIC_TOKEN` pada `services.php` dan `.env.example`.

**Acceptance criteria:**
- [x] Grup route `/job` di `src/routes/Routes.php` diproteksi dengan `->add(TokenMiddleware::class)`.
- [x] Skrip `assets/generate_job_token.php` dibuat dan berfungsi untuk minting static token JWT (10 tahun).
- [x] `composer.json` memuat script `"generate:job-token": "php assets/generate_job_token.php"`.
- [x] `src/config/services.php` dan `.env.example` memuat konfigurasi `job.token` dari `$_ENV['JOB_STATIC_TOKEN']`.

**Verification:**
- [x] Menjalankan CLI generator: `php assets/generate_job_token.php`
- [x] Menjalankan feature test job: `php vendor/bin/phpunit tests/Feature/JobTest.php`

**Dependencies:** Task 3
**Files likely touched:**
- `src/routes/Routes.php`
- `assets/generate_job_token.php`
- `composer.json`
- `src/config/services.php`
- `.env.example`
**Estimated scope:** Medium (5 files)

---

### Task 5: Cloud Storage & Multi-Provider Backup Support

**Description:**
Memperkaya library storage provider agar mendukung penyimpanan file backup database. Menambahkan method `put($key, $input, $contentType)` pada `CloudflareR2.php` untuk upload langsung stream/file. Memperbaiki penanganan path kredensial dan default bucket pada `GoogleCloudStorage.php`. Menambahkan konfigurasi `backup_provider` pada `src/config/services.php`.

**Acceptance criteria:**
- [x] `src/lib/CloudflareR2.php` memiliki method `put($key, $input, $contentType = 'application/gzip')`.
- [x] `src/lib/GoogleCloudStorage.php` mendukung penentuan bucket default dari konfigurasi dan resolusi path file credentials fleksibel.
- [x] `src/config/services.php` memuat blok konfigurasi `backup_provider` (`provider` => 'both'/'r2'/'gcs', `folder` => 'backup').
- [x] `src/config/dependencies.php` menginjeksikan dependency R2, GCS, dan servicesConfig ke `JobService`.

**Verification:**
- [x] Pemeriksaan sintaks: `php -l src/lib/CloudflareR2.php`, `php -l src/lib/GoogleCloudStorage.php`, `php -l src/config/services.php`

**Dependencies:** Task 4
**Files likely touched:**
- `src/lib/CloudflareR2.php`
- `src/lib/GoogleCloudStorage.php`
- `src/config/services.php`
- `src/config/dependencies.php`
**Estimated scope:** Medium (4 files)

---

### Task 6: Automated PostgreSQL Database Backup Endpoint (`/job/database_backup`)

**Description:**
Menerapkan endpoint background job `POST /job/database_backup` pada `JobController`, `JobService`, dan `JobValidator`. `JobService::databaseBackup` mengeksekusi `pg_dump` via `proc_open()` dengan proteksi kredensial environment, mengompresi stream secara on-the-fly ke file `.sql.gz` melalui chunking 64KB (`gzopen`/`gzwrite`), lalu mengunggahnya ke Cloudflare R2 dan/atau Google Cloud Storage, dan mencatat log eksekusi ke `tb_log`. Memperbarui `Dockerfile.dev` dan `Dockerfile.prod` dengan paket `postgresql-client`.

**Acceptance criteria:**
- [x] Method `databaseBackup(Request $request, Response $response)` ditambahkan di `JobController.php`.
- [x] Aturan validasi `database_backup` ditambahkan di `src/validators/JobValidator.php` (opsional `provider` in r2,gcs,both).
- [x] Method `databaseBackup()` dan helper private (`executePgDump()`, `uploadToProviders()`, `formatBytes()`) diimplementasikan di `src/services/JobService.php`.
- [x] Route `POST /job/database_backup` terdaftar di `src/routes/Routes.php`.
- [x] Paket `postgresql-client` ditambahkan ke `Dockerfile.dev` dan `Dockerfile.prod`.
- [x] Unit test `tests/Unit/JobServiceDatabaseBackupTest.php` disalin dan lulus 100%.

**Verification:**
- [x] Menjalankan test unit backup: `php vendor/bin/phpunit tests/Unit/JobServiceDatabaseBackupTest.php`
- [x] Menjalankan feature test job: `php vendor/bin/phpunit tests/Feature/JobTest.php`

**Dependencies:** Task 5
**Files likely touched:**
- `src/services/JobService.php`
- `src/controllers/JobController.php`
- `src/validators/JobValidator.php`
- `src/routes/Routes.php`
- `Dockerfile.dev`
- `Dockerfile.prod`
- `tests/Unit/JobServiceDatabaseBackupTest.php`
**Estimated scope:** Large (7 files)

---

### Checkpoint: Job & Backup Pipeline (Tasks 4-6)
- [x] Request ke `/job/database_backup` tanpa token memicu 401 Unauthorized
- [x] Request dengan valid static token mengeksekusi backup pipeline
- [x] Seluruh unit dan feature test pada grup `Job` berstatus hijau

---

## Phase 3: Communication & Media Platform Enhancements

### Task 7: Multi-Gateway WhatsApp Notification Service with Failover

**Description:**
Menyediakan integrasi gateway WhatsApp modern dengan failover otomatis. Menyalin `src/lib/WapitoClient.php` dan `src/lib/WappflyClient.php`. Mendaftarkan konfigurasi `wapito` dan `wappfly` di `services.php` dan DI container `dependencies.php`. Menambahkan method `General::sendWhatsApp($phone, $message, $user, $tableName, $recordId)` yang mencoba mengirim via Wapito, dan otomatis berpindah ke Wappfly jika gateway pertama gagal atau skipped.

**Acceptance criteria:**
- [x] `src/lib/WapitoClient.php` dan `src/lib/WappflyClient.php` tersedia di SlimeKit.
- [x] `src/config/services.php` memuat konfigurasi `wapito` dan `wappfly`.
- [x] `src/config/dependencies.php` menginjeksi kedua client ke class `General`.
- [x] `General::sendWhatsApp` melakukan failover secara mulus dan mencatat kegagalan ke log jika semua provider gagal.
- [x] Unit tests `WapitoClientTest`, `WappflyClientTest`, dan `GeneralWhatsAppFailoverTest` disalin dan lulus 100%.

**Verification:**
- [x] Menjalankan test: `php vendor/bin/phpunit tests/Unit/GeneralWhatsAppFailoverTest.php tests/Unit/WapitoClientTest.php tests/Unit/WappflyClientTest.php`

**Dependencies:** Task 1, Task 2
**Files likely touched:**
- `src/lib/WapitoClient.php`
- `src/lib/WappflyClient.php`
- `src/helpers/General.php`
- `src/config/services.php`
- `src/config/dependencies.php`
- `tests/Unit/GeneralWhatsAppFailoverTest.php`
- `tests/Unit/WapitoClientTest.php`
- `tests/Unit/WappflyClientTest.php`
**Estimated scope:** Large (8 files)

---

### Task 8: Cloudinary Hardening & Google Discover 16:9 Image Variant

**Description:**
Menggantikan manipulasi URL Cloudinary rapuh `strpos(..., "/v")` dengan fungsi `formatCloudinaryUrl(string $url, string $transformation): string` berbasis regex aman di `General.php`. Memperbarui respon upload dan query artikel di `AdminArticleService.php` dan `ClientArticleService.php` (`list`, `detail`, `similar`) agar secara retroaktif memancarkan varian `picture.large` berukuran 1200x675 (`c_fill,w_1200,h_675,g_auto,q_auto,f_auto`) yang merupakan standar kartu Google Discover.

**Acceptance criteria:**
- [x] Helper `formatCloudinaryUrl` aman terhadap URL Cloudinary dengan atau tanpa versi, serta mengembalikan URL asli jika bukan domain Cloudinary.
- [x] Respon endpoint `ClientArticleController` (`list`, `detail`, `similar`) memancarkan field `large` pada objek `picture`.
- [x] Respon endpoint `AdminArticleController` (`detail`, `add`, `edit`) memancarkan field `large`.
- [x] Unit test `tests/Unit/GeneralCloudinaryTest.php` dan `tests/Unit/ArticleLargeImageTest.php` lulus 100%.

**Verification:**
- [x] Menjalankan test: `php vendor/bin/phpunit tests/Unit/GeneralCloudinaryTest.php tests/Unit/ArticleLargeImageTest.php`
- [x] Menjalankan feature test artikel: `php vendor/bin/phpunit tests/Feature/ClientArticleTest.php`

**Dependencies:** None
**Files likely touched:**
- `src/helpers/General.php`
- `src/services/admin/AdminArticleService.php`
- `src/services/client/ClientArticleService.php`
- `tests/Unit/GeneralCloudinaryTest.php`
- `tests/Unit/ArticleLargeImageTest.php`
**Estimated scope:** Medium (5 files)

---

### Task 9: Multi-Site Sanitization & Whitelist Security

**Description:**
Menerapkan sistem validasi dan whitelisting parameter `site` di `src/helpers/General.php` melalui method `getAllowedSites(): array` dan `isValidSite(?string $site): bool`. Parameter site disanitasi terhadap regex `/^[a-zA-Z0-9_\-\.]+$/` untuk mencegah cache key poisoning atau karakter berbahaya masuk ke pola prefix Valkey. Menambahkan variabel `ALLOWED_SITES` di `.env.example`.

**Acceptance criteria:**
- [x] Method `General::getAllowedSites()` membaca environment `ALLOWED_SITES` dan mengembalikan array nama site yang bersih.
- [x] Method `General::isValidSite(?string $site)` menolak string kosong, karakter ilegal, atau site di luar whitelist.
- [x] `.env.example` mendokumentasikan variabel `ALLOWED_SITES=default,site1,site2`.
- [x] Unit test `tests/Unit/GeneralSiteValidationTest.php` disalin dan lulus 100%.

**Verification:**
- [x] Menjalankan test: `php vendor/bin/phpunit tests/Unit/GeneralSiteValidationTest.php`

**Dependencies:** None
**Files likely touched:**
- `src/helpers/General.php`
- `.env.example`
- `tests/Unit/GeneralSiteValidationTest.php`
**Estimated scope:** Small (3 files)

---

### Checkpoint: Media, Communication & Security (Tasks 7-9)
- [x] WhatsApp notification failover terverifikasi
- [x] URL gambar artikel memenuhi standar Google Discover 16:9
- [x] Whitelist site memproteksi integritas cache key

---

## Phase 4: Publishing Engine, Anti-Duplication & Transaction Cleanliness

### Task 10: PostgreSQL Trigram Similarity & Composite Indexes Migration

**Description:**
Membuat file migrasi Phinx `AddTrgmAndPerformanceIndexesToArticles` untuk mengaktifkan ekstensi `pg_trgm`, menambahkan GIN index pada kolom judul `tb_article` (`USING gin (title gin_trgm_ops)`), serta menambahkan composite B-Tree index pada `(site, status, slug)` dan `(site, status, publish)`. Memperbarui query pencarian artikel serupa di `ClientArticleService::similar()` agar memanfaatkan operator similarity trigram `(title % ? OR similarity(title, ?) > 0.2)`.

**Acceptance criteria:**
- [x] File migrasi baru dibuat di `src/migrations/` dengan deklarasi indeks trigram dan komposit.
- [x] `ClientArticleService::similar()` memperbarui query dengan sintaks similarity yang teroptimasi.
- [x] Phinx status dan rollback bekerja dengan baik.

**Verification:**
- [x] Pemeriksaan sintaks: `php -l src/services/client/ClientArticleService.php`
- [x] Menjalankan test: `php vendor/bin/phpunit tests/Feature/ClientArticleTest.php`

**Dependencies:** Task 8
**Files likely touched:**
- `src/migrations/20261008234500_add_trgm_and_performance_indexes_to_articles.php`
- `src/services/client/ClientArticleService.php`
**Estimated scope:** Small (2 files)

---

### Task 11: Transaction Boundaries, Search Filtering & Cache Invalidation Coherence

**Description:**
Merefaktor `AdminArticleService.php` dan `AdminArticleCategoryService.php` untuk memisahkan batasan transaksi database secara ketat: upload media Cloudinary dilakukan sebelum `DB::beginTransaction()` (dengan kompensasi delete jika error), penghapusan aset cloud dilakukan setelah `DB::commit()`, serta penghapusan cache Valkey dan Next.js revalidation dilakukan setelah commit. Menambahkan fitur filter pencarian teks `search` pada `AdminArticleService::list()` dan membuat method terpusat `invalidateArticleCache()` serta `invalidateCategoryAndArticleCache()`.

**Acceptance criteria:**
- [x] Upload gambar berada di luar transaksi DB; jika DB gagal, file Cloudinary di-rollback/dihapus.
- [x] Penghapusan cloud dan pembersihan cache dieksekusi setelah `DB::commit()`.
- [x] Method `AdminArticleService::list()` mendukung parameter query `search` (mencari pada `title`, `author`, `slug`) dan memasukkannya ke dalam cache key.
- [x] Unit tests `AdminArticleCacheInvalidationTest` dan `AdminArticleCategoryCacheInvalidationTest` disalin dan lulus 100%.

**Verification:**
- [x] Menjalankan test: `php vendor/bin/phpunit tests/Unit/AdminArticleCacheInvalidationTest.php tests/Unit/AdminArticleCategoryCacheInvalidationTest.php`
- [x] Menjalankan feature tests: `php vendor/bin/phpunit tests/Feature/AdminArticleTest.php tests/Feature/AdminArticleCategoryTest.php`

**Dependencies:** Task 1, Task 8
**Files likely touched:**
- `src/services/admin/AdminArticleService.php`
- `src/services/admin/AdminArticleCategoryService.php`
- `src/services/JobService.php`
- `tests/Unit/AdminArticleCacheInvalidationTest.php`
- `tests/Unit/AdminArticleCategoryCacheInvalidationTest.php`
**Estimated scope:** Medium (5 files)

---

### Task 12: AI Content Agent Anti-Duplication & Negative Context

**Description:**
Menambahkan helper `General::getRecentArticlesBySite(string $site, int $limit)` untuk mengambil daftar artikel terbaru beserta target keyword-nya. Memperbarui `AdminArticleAgentService.php` agar secara dinamis menyuntikkan daftar artikel eksisting sebagai *negative context* (anti-kanibalisasi topik) ke dalam prompt LLM OpenAI / Gemini saat brainstorming, serta menambahkan dynamic angles pool dan relevansi periode bulan/tahun terkini.

**Acceptance criteria:**
- [x] Method `General::getRecentArticlesBySite()` tersedia dan mengembalikan array title & keyword.
- [x] `AdminArticleAgentService::brainstorm()` menyuntikkan negative context dan dynamic angle pool ke prompt saat mode `auto` + `free`.
- [x] Konfigurasi model AI OpenAI diperbarui untuk kompatibilitas model mutakhir.
- [x] Unit tests `GeneralRecentArticlesTest` dan `AdminArticleAgentServiceBrainstormTest` disalin dan lulus 100%.

**Verification:**
- [x] Menjalankan test: `php vendor/bin/phpunit tests/Unit/GeneralRecentArticlesTest.php tests/Unit/AdminArticleAgentServiceBrainstormTest.php`
- [x] Menjalankan feature test: `php vendor/bin/phpunit tests/Feature/AdminArticleAgentTest.php`

**Dependencies:** Task 7, Task 9
**Files likely touched:**
- `src/helpers/General.php`
- `src/services/admin/AdminArticleAgentService.php`
- `tests/Unit/GeneralRecentArticlesTest.php`
- `tests/Unit/AdminArticleAgentServiceBrainstormTest.php`
**Estimated scope:** Medium (4 files)

---

### Task 13: Content Team Module Refinement & Full Suite Verification

**Description:**
Melakukan audit dan penyesuaian pada modul boilerplate `content_team` (`AdminContentTeamService.php`, `ClientContentTeamService.php`, dan `AdminContentTeamTest.php`) agar selaras dengan konvensi arsitektur baru (upload gambar di luar transaksi DB, commit sebelum pembersihan cache). Menjalankan keseluruhan test suite PHPUnit untuk memastikan seluruh endpoint, middleware, dan service di SlimeKit lulus 100% tanpa regresi.

**Acceptance criteria:**
- [x] `AdminContentTeamService` menerapkan pola transaksi baru yang aman.
- [x] Modul `content_team` tetap berfungsi sebagai referensi CRUD developer.
- [x] Tidak ada berkas/kode peninggalan domain Makeup, Finansial, ataupun Tools Weton di SlimeKit.
- [x] Perintah `composer test` menjalankan seluruh feature dan unit tests dengan status 100% Pass (hijau).

**Verification:**
- [x] Menjalankan seluruh test suite: `composer test`
- [x] Menjalankan feature tests: `composer test:feature`
- [x] Menjalankan unit tests: `composer test:unit`

**Dependencies:** Tasks 1-12
**Files likely touched:**
- `src/services/admin/AdminContentTeamService.php`
- `tests/Feature/AdminContentTeamTest.php`
- `tests/Feature/ClientContentTeamTest.php`
**Estimated scope:** Medium (3 files)

---

### Checkpoint: Complete Verification
- [x] 13 Task selesai dan ditandai `[x]`
- [x] Seluruh unit dan feature tests berstatus hijau (`composer test`)
- [x] SlimeKit v2.1 siap dipakai sebagai starter kit produksi yang tangguh dan bersih
