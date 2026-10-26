# Implementation Plan: Penyelarasan SlimeKit v2.1 dari SlimeNews/api

## Overview
Proyek `/Users/renofizaldy/Code/SlimeGo/SlimeKit` adalah framework dasar (starter kit / boilerplate) berbasis Slim Framework 4 + Eloquent ORM. Rencana implementasi ini bertujuan untuk menyelaraskan SlimeKit dengan berbagai peningkatan infrastruktur, ketahanan produksi (*resilience*), keamanan, media pipeline, dan otomasi operasional yang telah teruji di `/Users/renofizaldy/Code/SlimeNews/api`.

Seluruh perbaikan generic akan diadopsi tanpa mengotori framework dengan kode domain bisnis spesifik (Reservasi/Voucher/Layanan Makeup, Keuangan/Jurnal Akuntansi, Customer Group, dan Kalkulator Weton/Zodiak). Modul `content_team` tetap dipertahankan sebagai referensi CRUD boilerplate yang bersih.

---

## Architecture Decisions & User Choices

1. **Full-Featured Headless Publishing & Ops Boilerplate:**
   - SlimeKit v2.1 memposisikan diri sebagai backend headless publishing engine siap produksi dengan kapabilitas operasional lengkap (caching resilience, multi-site validation, streaming database backup, media Google Discover, WhatsApp alert failover, dan AI agent).

2. **Infrastruktur & Resilience:**
   - **Valkey Circuit Breaker:** Menghentikan *cascading connection hang* jika server Redis/Valkey offline dengan memanfaatkan file marker cooldown 30 detik di direktori temporer OS (`sys_get_temp_dir()`).
   - **Database Connection Pooler Compatibility:** Menerapkan opsi `PDO::ATTR_EMULATE_PREPARES => true` pada koneksi Capsule Eloquent untuk kompatibilitas penuh dengan connection pooler PostgreSQL serverless (PgBouncer, Neon, Supabase).

3. **Background Worker & Security:**
   - **Standarisasi Route `/job/*`:** Mengamankan seluruh endpoint background worker menggunakan `TokenMiddleware`.
   - **Static JWT Generator:** Menyediakan CLI `assets/generate_job_token.php` (`composer generate:job-token`) untuk menghasilkan static JWT 10 tahun bagi scheduler/worker.
   - **Hardening Tokenize & Auth:** Mendukung `$customExp` pada `Tokenize::encode()`, menangani decode exception secara defensif, dan memperbaiki `AuthMiddleware` dengan menghapus instansiasi `new General()` serta standarisasi status code.

4. **Automated Database Backup:**
   - Menyediakan endpoint `POST /job/database_backup` dengan streaming proses `pg_dump | gzip` secara efisien (64KB chunks) tanpa membebani memory PHP, dan mengunggahnya langsung ke Cloudflare R2 dan/atau Google Cloud Storage.
   - Menambahkan paket `postgresql-client` pada Dockerfile.

5. **Media Pipeline & Multi-Site Guard:**
   - **Google Discover Image Standards:** Helper `formatCloudinaryUrl` berbasis regex untuk URL Cloudinary dan otomatis memancarkan varian `picture.large` (1200x675, `c_fill,w_1200,h_675,g_auto,q_auto,f_auto`) pada endpoint artikel.
   - **Site Parameter Whitelisting:** Sanitasi parameter site menggunakan regex `/^[a-zA-Z0-9_\-\.]+$/` dan whitelist via environment `ALLOWED_SITES` untuk mencegah cache key poisoning.

6. **Communication Services:**
   - Menyediakan `WapitoClient` dan `WappflyClient` dengan sistem failover otomatis di `General::sendWhatsApp()` untuk kebutuhan alert dan notifikasi transaksional. Kredensial bersifat opsional (skip secara mulus jika belum diatur).

7. **Clean Transaction Boundaries & Cache Coherence:**
   - Upload file pihak ketiga (Cloudinary) dieksekusi *sebelum* `DB::beginTransaction()` (dengan kompensasi penghapusan jika DB error).
   - Penghapusan aset cloud, pembersihan cache Valkey, dan Next.js ISR revalidation dieksekusi *setelah* `DB::commit()` untuk menjamin konsistensi data.

---

## Task List

### Phase 1: Core Infrastructure Resilience & Security Hardening
- [x] **Task 1: Valkey Circuit Breaker & Connection Resilience** (`src/lib/Valkey.php`)
- [x] **Task 2: Database Connection & Pooler Hardening (PDO Emulate Prepares)** (`src/lib/Database.php`, `src/config/services.php`)
- [x] **Task 3: Auth & Token Hardening (Tokenize Custom Expiration & AuthMiddleware DI Fix)** (`src/lib/Tokenize.php`, `src/middlewares/AuthMiddleware.php`, `src/middlewares/TokenMiddleware.php`)

### Checkpoint 1: Core Infrastructure & Auth
- [x] Validasi syntax PHP (`php -l`)
- [x] Unit test `ValkeyTest` dan `TokenMiddlewareTest` lulus 100%

---

### Phase 2: Background Job Protection & Database Backup System
- [x] **Task 4: Static Token Protection & CLI Generator for `/job/*`** (`src/routes/Routes.php`, `assets/generate_job_token.php`, `composer.json`, `.env.example`)
- [x] **Task 5: Cloud Storage & Multi-Provider Backup Support** (`src/lib/CloudflareR2.php`, `src/lib/GoogleCloudStorage.php`, `src/config/services.php`)
- [x] **Task 6: Automated PostgreSQL Database Backup Endpoint (`/job/database_backup`)** (`src/services/JobService.php`, `src/controllers/JobController.php`, `src/validators/JobValidator.php`, `Dockerfile.dev`, `Dockerfile.prod`)

### Checkpoint 2: Job Protection & Backup Pipeline
- [x] Endpoint `/job/*` menolak request tanpa Bearer Token valid (HTTP 401)
- [x] CLI `composer generate:job-token` menghasilkan JWT 10 tahun
- [x] Unit test `JobServiceDatabaseBackupTest` dan `JobTest` lulus 100%

---

### Phase 3: Communication & Media Platform Enhancements
- [x] **Task 7: Multi-Gateway WhatsApp Notification Service with Failover** (`src/lib/WapitoClient.php`, `src/lib/WappflyClient.php`, `src/helpers/General.php`, `src/config/dependencies.php`)
- [x] **Task 8: Cloudinary Hardening & Google Discover 16:9 Image Variant** (`src/helpers/General.php`, `src/services/admin/AdminArticleService.php`, `src/services/client/ClientArticleService.php`)
- [x] **Task 9: Multi-Site Sanitization & Whitelist Security** (`src/helpers/General.php`, `.env.example`)

### Checkpoint 3: Media, Notifications & Multi-Site Guard
- [x] WhatsApp failover berpindah otomatis dari Wapito ke Wappfly jika gateway pertama gagal
- [x] Respon API artikel (`list`, `detail`, `similar`) menyertakan objek `picture.large` 1200x675
- [x] Unit tests `GeneralCloudinaryTest`, `GeneralWhatsAppFailoverTest`, `WapitoClientTest`, `WappflyClientTest`, dan `GeneralSiteValidationTest` lulus 100%

---

### Phase 4: Publishing Engine, Anti-Duplication & Transaction Cleanliness
- [ ] **Task 10: PostgreSQL Trigram Similarity & Composite Indexes Migration** (`src/migrations/`, `src/services/client/ClientArticleService.php`)
- [ ] **Task 11: Transaction Boundaries, Search Filtering & Cache Invalidation Coherence** (`src/services/admin/AdminArticleService.php`, `src/services/admin/AdminArticleCategoryService.php`)
- [ ] **Task 12: AI Content Agent Anti-Duplication & Negative Context** (`src/helpers/General.php`, `src/services/admin/AdminArticleAgentService.php`)
- [ ] **Task 13: Content Team Module Refinement & Full Suite Verification** (`src/services/admin/AdminContentTeamService.php`, `tests/`)

### Checkpoint 4: End-to-End Test Suite Green
- [ ] Seluruh unit dan feature tests di SlimeKit lulus tanpa kegagalan: `composer test`
- [ ] Framework bersih dari sisa modul bisnis domain

---

## Risks and Mitigations

| Risiko | Dampak | Mitigasi |
| :--- | :---: | :--- |
| **Ketergantungan CLI `pg_dump` pada Docker/Host** | Sedang | `proc_open` mengecek ketersediaan resource dan melempar Exception terdeskripsi jika binary belum terpasang. Dockerfile diperbarui dengan paket `postgresql-client`. |
| **Kegagalan Koneksi Redis/Valkey memperlambat response time** | Tinggi | Circuit breaker aktif otomatis saat terjadi error koneksi pertama, mengalihkan request berikutnya selama 30 detik tanpa mencoba koneksi ulang yang timeout. |
| **Kredensial WhatsApp/R2/GCS belum diisi developer baru** | Rendah | Menggunakan *graceful fallback* / `skipped` flag sehingga aplikasi tetap berfungsi normal tanpa konfigurasi tersebut. |
| **Regresi pada modul bawaan `content_team`** | Rendah | `content_team` tetap diuji dengan PHPUnit (`AdminContentTeamTest` & `ClientContentTeamTest`) dan diselaraskan ke pola transaksi baru. |

---

## Open Questions
- *Tidak ada pertanyaan terbuka.* Seluruh cakupan arah arsitektur telah dikonfirmasi dan disetujui.
