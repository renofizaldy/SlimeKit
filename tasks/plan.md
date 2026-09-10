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

---

## Future Architectural Roadmap: Route Middleware Ergonomics

### Konteks & Latar Belakang
Saat ini di `src/routes/Routes.php`, seluruh Controller telah 100% menggunakan format class-string (`[$controller, 'method']`) yang di-auto-wire oleh container PHP-DI, dan middleware tanpa dependensi/argumen seperti `TokenMiddleware::class` juga telah menggunakan auto-wiring. Namun, middleware yang membutuhkan argumen dinamis per rute (`AuthMiddleware` dan `RateLimitMiddleware`) serta middleware kompresi masih ditulis menggunakan `new`:
```php
$group->get('/list', [$controller, 'list'])->add(new AuthMiddleware('admin', 'article:view'));
$group->post('/add', [$controller, 'add'])->add(new AuthMiddleware('admin', 'article:crud'))->add(new GzipDecoderMiddleware());
$app->group('/admin', ...)->add(new GzipEncoder())->add(new RateLimitMiddleware(100, 60));
```

Untuk meningkatkan keterbacaan (*readability*), kebersihan kode, dan standarisasi arsitektur, berikut 2 rencana tugas terpisah:

---

### Task 22 (Future): Route Middleware Ergonomics - Pilihan Pragmatis (Helper Closures)

**Tujuan:**
Menyederhanakan dan memperpendek penulisan rute di `src/routes/Routes.php` secara cepat dan pragmatis menggunakan *local helper closures / function factories* tanpa memodifikasi class middleware itu sendiri.

**File yang Harus Diubah:**
1. `src/routes/Routes.php` — Menambahkan helper closures di awal fungsi routing dan mengganti instansiasi `new AuthMiddleware` serta `new RateLimitMiddleware`.
2. `tests/Feature/RoutesTest.php` — Menyesuaikan pengujian integritas rute jika diperlukan.

**Alur Logika & Cara Kerja:**
1. **Definisi Factory Closure:** Di awal return closure `Routes.php`, didefinisikan arrow function ringkas:
   ```php
   $adminAuth = fn(string $perm) => new AuthMiddleware('admin', $perm);
   $rateLimit = fn(int $limit, int $period = 60) => new RateLimitMiddleware($limit, $period);
   ```
2. **Refactor Endpoint Auth:** Mengganti baris rute yang sebelumnya berulang-ulang menulis `new AuthMiddleware('admin', '...')` menjadi lebih ringkas:
   ```php
   $group->get('/list', [$controller, 'list'])->add($adminAuth('article:view'));
   $group->post('/add', [$controller, 'add'])->add($adminAuth('article:crud'))->add(GzipDecoderMiddleware::class);
   ```
3. **Refactor Rate Limiter & Gzip:**
   - Mengganti `new RateLimitMiddleware(100, 60)` menjadi `$rateLimit(100, 60)` pada grup admin.
   - Mengganti `new RateLimitMiddleware(60, 60)` menjadi `$rateLimit(60, 60)` pada grup client.
   - Mengganti `new GzipDecoderMiddleware()` dan `new GzipEncoder()` menjadi class-string `GzipDecoderMiddleware::class` dan `GzipEncoder::class` untuk resolusi otomatis via container.
4. **Karakteristik Pendekatan:** Sangat cepat diimplementasikan (hanya menyentuh 1 file rute), kode menjadi jauh lebih pendek, namun closure bersifat lokal pada file `Routes.php`.

---

### Task 23: Route Middleware Ergonomics - Pilihan Standar Enterprise (Named Constructors / Static Factory) ✅

**Tujuan:**
Menerapkan standar rekayasa perangkat lunak kelas enterprise Silicon Valley (*Domain-Driven Design / Clean OOP*) dengan menambahkan *Named Constructor / Static Factory Method* langsung pada class middleware. Membuat middleware bersifat *self-contained*, memiliki *IDE autocomplete* penuh, *type safety* tinggi, serta menjadikan file `Routes.php` deklaratif dan bersih dari keyword `new` maupun closure lokal.

**File yang Harus Diubah:**
1. `src/middlewares/AuthMiddleware.php` — Menambahkan static factory methods (`::admin()`, `::client()`, `::hasRole()`).
2. `src/middlewares/RateLimitMiddleware.php` — Menambahkan static preset factory methods (`::admin()`, `::client()`, `::custom()`).
3. `src/routes/Routes.php` — Mengadopsi static factory method pada seluruh pipeline rute.
4. `tests/Feature/RoutesTest.php` — Memvalidasi endpoint resolution dengan pipeline static factory baru.
5. `tests/Unit/AuthMiddlewareTest.php` (baru/update) — Unit test untuk memastikan instance yang dihasilkan static method valid.

**Alur Logika & Cara Kerja:**
1. **Peningkatan `AuthMiddleware`:**
   Menambahkan static constructor yang mengembalikan instance `self`:
   ```php
   public static function admin(string $permission): self
   {
       return new self('admin', $permission);
   }

   public static function client(string $permission): self
   {
       return new self('client', $permission);
   }

   public static function hasRole(string $role, string $permission): self
   {
       return new self($role, $permission);
   }
   ```
2. **Peningkatan `RateLimitMiddleware`:**
   Menambahkan preset konfigurasi standar:
   ```php
   public static function admin(): self
   {
       return new self(100, 60);
   }

   public static function client(): self
   {
       return new self(60, 60);
   }

   public static function custom(int $limit, int $period = 60): self
   {
       return new self($limit, $period);
   }
   ```
3. **Penyelarasan `Routes.php`:**
   Menghapus closure ad-hoc dan menulis route secara deklaratif:
   ```php
   $group->get('/list', [$controller, 'list'])->add(AuthMiddleware::admin('article:view'));
   $group->post('/add', [$controller, 'add'])->add(AuthMiddleware::admin('article:crud'))->add(GzipDecoderMiddleware::class);
   ```
   Dan di level grup:
   ```php
   $app->group('/admin', ...)
     ->add(GzipEncoder::class)
     ->add(RateLimitMiddleware::admin());
   ```
4. **Karakteristik Pendekatan:**
   - **High Cohesion:** Tanggung jawab pembuatan instance berada di class middleware itu sendiri.
   - **Reusability:** Jika route nantinya dipecah ke multiple file (misal `AdminRoutes.php`, `ClientRoutes.php`), method factory langsung dapat dipakai tanpa copy-paste closure.
   - **Audit Keamanan Eksplisit:** Developer dan auditor keamanan langsung membaca permission rute secara jelas tanpa perlu melacak definisi closure di atas.
   - **Autocomplete IDE Penuh:** Mengetik `AuthMiddleware::` langsung memunculkan saran method `admin()` atau `client()` beserta dokumentasi tipenya.
