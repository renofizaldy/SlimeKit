# Implementation Plan: Penyelarasan kit/api dari news/api (SlimeKit v2)

## Overview
Proyek `kit/api` adalah framework dasar (starter kit / boilerplate) untuk pembangunan RESTful API masa depan. Rencana implementasi ini bertujuan untuk menyelaraskan `kit/api` dengan peningkatan arsitektur masif yang telah terbukti di `news/api` (Task 1 s/d 13 pada `todo.md` news), mengintegrasikan sistem Article modern, Article Agent (AI content writer), Analytics/Stats, Job Automation, menghapus OpenTofu (`tofu/`), dan menerapkan dual environment `.env.dev` serta `.env.prod`.

Modul yang bersifat spesifik bisnis (Makeup Reservasi, Keuangan, Customer Group, dan Weton Jodoh) dikecualikan agar framework tetap bersih, modular, dan terfokus sebagai starter kit.

---

## Architecture Decisions & User Choices

1. **Dependency Injection & Auto-Wiring:**
   - Menggantikan `uma/dic` dengan `php-di/php-di: ^7.1`.
   - Menggunakan PHP 8 Constructor Property Promotion di seluruh Controller, Service, dan Validator.
   - Sentralisasi konfigurasi `$_ENV` di `src/config/services.php` dan registrasi dependencies di `src/config/dependencies.php`.

2. **Standardisasi Format Respons & Error Semantics:**
   - Menggunakan `App\Helpers\ApiResponse` untuk semua endpoint:
     - `success($response, $data, $message, $status)`
     - `paginated($response, $items, $total, $page, $pageSize, $message, $status)`
     - `error($response, $message, $status, $errors, $errorCode)`
     - `handleException($response, $e)` (otomatis mendeteksi error validasi dan mengembalikan status HTTP 422 dengan format flat machine-readable `VALIDATION_ERROR`).

3. **Validasi Terpusat:**
   - Menggantikan validasi manual di `General.php` dengan library standar industri `rakit/validation: ^1.4`.

4. **Logging & Observability:**
   - Mengintegrasikan `monolog/monolog: ^3.10` dengan dukungan environment variable `LOG_CHANNEL` (`file` -> `logs/app.log` atau `stderr`).
   - `ErrorHandlerMiddleware` secara otomatis mencatat exception 5xx ke logger.

5. **Penghapusan OpenTofu & Dual Environment:**
   - Menghapus seluruh folder `tofu/`.
   - Menerapkan pendekatan `.env.dev` dan `.env.prod` dengan `Dockerfile.dev` dan `Dockerfile.prod`.
   - Memperbarui `docker-compose.yml` untuk development container.

6. **Keputusan User (3 Poin Opsi A):**
   - **ContentTeam:** Tetap dipertahankan di `kit/api` sebagai modul standar company profile / starter kit, namun direfaktor ke pola arsitektur baru (PHP-DI + Rakit + ApiResponse).
   - **Multi-Site (`site` parameter):** Disertakan pada `tb_article`, `tb_article_category`, dan `tb_article_stats` dengan nilai default (`'default'`), sehingga siap dipakai untuk multi-site maupun single-site.
   - **GitHub Actions (`deploy.yml`):** Menghapus langkah-langkah OpenTofu dan menggantinya dengan build Docker & deployment Cloud Run berbasis environment variables.

---

## Phase Breakdown

### Phase 1: Foundation & Dependencies Alignment
Membangun fondasi baru: instalasi package, DI container PHP-DI, ApiResponse, ErrorHandlerMiddleware, CorsMiddleware, dan refactor `index.php`.

### Phase 2: DevOps, OpenTofu Removal & Environment Setup
Pembersihan OpenTofu (`tofu/`), pembuatan `Dockerfile.dev`, `Dockerfile.prod`, template `.env.dev`, `.env.prod`, pembaruan `.env.example` dan `docker-compose.yml`.

### Phase 3: Core Helper Expansion & Automated Testing Infrastructure
Ekspansi method di `General.php` (paginasi, GeoIP, context, slugify, sanitasi AI, dsb), penyiapan `phpunit.xml`, `tests/TestCase.php`, adaptasi middleware untuk environment `TESTING`, serta unit tests paginasi.

### Phase 4: Core Framework Modules Refactoring
Refactor modul bawaan `kit/api` (Auth, Setting User, FAQ, Gallery, Contact, dan Team) ke arsitektur baru beserta automated feature tests.

### Phase 5: Database Schema Alignment
Penambahan kolom `site` pada artikel dan kategori, migrasi `tb_article_agent` dan `tb_article_stats`, serta update Eloquent Models.

### Phase 6: Article & Article Category System v2
Porting Admin & Client Article serta Category dengan paginasi, Valkey caching, status/publish change, dan feature tests.

### Phase 7: Article Agent (AI Writer) System
Porting AI Clients (`GeminiClient`, `OpenAIClient`, `TavilyClient`) dan modul `AdminArticleAgent` (3-step workflow) beserta feature tests.

### Phase 8: Stats System & Job Automation
Porting modul `AdminStats` (log, totalArticle, visit charts, top visits, GeoIP) dan `Job` (Cronhooks publish callback, Valkey visit queue sync, track stats, NextRevalidator ISR) beserta feature tests.

### Phase 9: Routing, Code Generator CLI & Final Polish
Refactor `Routes.php` (auto-wiring class strings), penyelarasan CLI generator `make_module.php`, verifikasi test suite 100% green, dan dokumentasi `README.md`.

---

## Risks & Mitigations

| Risiko | Dampak | Mitigasi |
| :--- | :--- | :--- |
| **Breaking perubahan route / format respons** | Sedang | Semua controller menggunakan `ApiResponse` yang terstandarisasi. Seluruh route diverifikasi dengan Feature Tests otomatis. |
| **Ketergantungan ke API Pihak ke-3 (Gemini, OpenAI, Tavily, Valkey)** | Sedang | Pengecekan koneksi dilakukan aman dengan error handling yang jelas. Pada unit/feature test, service eksternal di-mocking melalui container. |
| **Penghapusan OpenTofu merusak workflow deploy** | Rendah | Mengganti file `deploy.yml` dengan pipeline Docker Cloud Run standar yang bersih dari dependensi Terraform/Tofu. |
| **Regresi pada modul bawaan (ContentTeam, Auth)** | Rendah | Setiap modul dibuatkan feature tests (HTTP simulation) sebelum dinyatakan selesai. |
