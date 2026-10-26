# SlimeKit v2 - Agent Guide & Project Context

Panduan dan instruksi resmi bagi AI Agent saat membaca, menganalisis, merefaktor, atau mengimplementasikan fitur di repositori **SlimeKit v2**.

---

## 📌 Ringkasan Proyek

**SlimeKit v2** adalah modern backend boilerplate dan **single-tenant micro-framework** berbasis **Slim Framework 4** yang dipadukan dengan **Illuminate Database (Eloquent ORM)**, **PHP-DI 7 auto-wiring**, dan caching terdistribusi **Valkey / Redis**.

SlimeKit dirancang sebagai *starter kit* yang bersih, modular, *type-safe*, dan berkinerja tinggi untuk membangun RESTful API siap produksi.

---

## 🛠️ Tech Stack & Ekosistem

- **Runtime & Bahasa:** PHP 8.4 (Minimum PHP 8.2) dengan *Strict Typing* dan *Constructor Property Promotion*.
- **HTTP Micro-Framework:** Slim Framework 4 (PSR-7 HTTP Message, PSR-15 Middleware).
- **Dependency Injection:** PHP-DI 7 dengan *auto-wiring* di `src/config/dependencies.php`.
- **Database & ORM:** PostgreSQL 14+ (ekstensi `pg_trgm`) / MySQL 8.0+ menggunakan **Illuminate Database (Eloquent Capsule)**.
- **Migration & Seeding:** Phinx (`phinx.php`, `src/migrations/`, `src/seeds/`).
- **Validation:** Rakit Validation (`src/validators/`).
- **Distributed Cache & Queue:** Valkey 7.x / Redis 6.2+ (`App\Lib\Valkey`).
- **Automated Testing:** PHPUnit 11 (`tests/Unit/`, `tests/Feature/`).

---

## 🧭 Peta Struktur Direktori

```text
src/
├── config/              # Inisialisasi Database, PHP-DI Container, dan konfigurasi services
│   ├── database.php     # Inisialisasi Eloquent Capsule
│   ├── dependencies.php # Definisi resolusi dependency injection container
│   └── services.php     # Konfigurasi Cloudinary, Valkey, Revalidator, dll.
├── controllers/         # HTTP Controller (Request handling, delegasi service, JSON response)
│   ├── Admin/           # Controller backoffice/admin (auth & permissions required)
│   ├── Client/          # Controller publik/frontend
│   └── Job/             # Controller background job & worker
├── services/            # Business logic layer (DB query, mutasi, cache, external API)
│   ├── Admin/
│   ├── Client/
│   └── JobService.php
├── validators/          # Lapisan validasi payload & query parameter (Rakit)
│   ├── Admin/
│   ├── Client/
│   └── JobValidator.php
├── models/              # Eloquent Models (Relasi, guarded/fillable, casts)
├── helpers/             # Helper fungsi utilitas (General, ApiResponse)
├── lib/                 # Integrasi external service clients (Valkey, Cloudinary, AI, Revalidator)
│   └── clients/         # GeminiClient, OpenAIClient, TavilyClient
├── middlewares/         # Middleware HTTP (Token/JWT, Auth/RBAC, RateLimit, CORS, Gzip)
├── migrations/          # File migrasi database Phinx
├── seeds/               # Database seeder Phinx
└── routes/
    └── Routes.php       # Sentralisasi definisi rute aplikasi
tests/
├── Feature/             # Endpoint & integration feature tests (App instance mockup)
└── Unit/                # Unit tests (Mocking class dependencies)
docs/
└── decisions/           # Architecture Decision Records (ADRs)
```

---

## ⚡ Perintah Penting (Commands)

```bash
# Development Server (Port 8787)
composer start

# Menjalankan Seluruh Automated Tests
composer test

# Menjalankan Kategori Test Tertentu
composer test:unit       # Unit tests saja
composer test:feature    # Feature tests saja
composer test:filter <Name> # Filter test spesifik (misal: JobTest)

# Migrasi Database (Phinx)
composer migrate         # Jalankan migrasi baru
composer migrate:status  # Periksa status migrasi
composer migrate:rollback# Rollback migrasi terakhir
composer make:migration <Name> # Buat file migrasi baru

# Seeding Database
composer seed            # Jalankan database seeder
composer make:seed <Name># Buat file seeder baru

# Generator Kode (Scaffolding)
composer make:module:admin <Name>  # Buat Controller, Service, Validator Admin
composer make:module:client <Name> # Buat Controller, Service, Validator Client
composer make:model <Name> <table_name> # Buat Eloquent Model baru

# Composer Autoload Refresh
composer dump-autoload -o
```

---

## 🔒 Aturan Arsitektur & Batasan (Non-Negotiable Rules)

Setiap agent AI yang bekerja pada codebase ini **WAJIB** mematuhi aturan berikut:

### 1. Pure Single-Tenant Architecture (SANGAT PENTING)
- **DILARANG KERAS** menambahkan kolom `site`, parameter request `site`, query filter `where('site', ...)`, atau cache key bertingkat dengan segmen `site=%s:`.
- SlimeKit telah dimigrasi secara penuh ke arsitektur *Pure Single-Tenant* ([Lihat ADR-001](docs/decisions/ADR-001-pure-single-tenant-architecture.md)).
- Kolom `slug` pada tabel artikel dan kategori bersifat **unik secara global**.
- Revalidasi frontend Next.js menggunakan single target: `NextRevalidator::revalidateArticle(string $slug, ?string $oldSlug = null)`.

### 2. Dependency Injection & Inversi Kontrol (PHP-DI 7)
- **DILARANG** melakukan instansiasi class manual di Controller (misal: `new MyService()`, `new Cloudinary()`).
- Gunakan *PHP 8 Constructor Property Promotion* di Controller, Service, dan Helper.
- Daftarkan dependensi baru di `src/config/dependencies.php`.

### 3. Response JSON Standar (`ApiResponse`)
- Seluruh Controller **harus** mengembalikan output JSON melalui helper `ApiResponse`:
  ```php
  // Sukses
  return $this->response->success($response, $data, 'Ok', 200);

  // Error
  return $this->response->error($response, $message, 400);
  ```
- Jangan melakukan `echo json_encode(...)` atau `die()` di dalam Controller/Service.

### 4. Transaksi Database pada Operasi Mutasi
- Setiap method Service yang melakukan manipulasi data (INSERT, UPDATE, DELETE) **wajib** menggunakan blok transaksi Eloquent:
  ```php
  DB::beginTransaction();
  try {
      // Operasi mutasi data
      DB::commit();
  } catch (\Throwable $e) {
      DB::rollBack();
      throw $e;
  }
  ```

### 5. Flat & Direct Valkey Cache
- Pola cache key Valkey harus datar dan jelas:
  - List: `article:list:order_by=%s:order_type=%s:page=%d:pageSize=%d:...`
  - Detail: `article:detail:slug=%s`
  - Similar: `article:similar:slug=%s`
- Cache invalidation dilakukan secara global:
  ```php
  $this->valkey->deleteByPrefix("{$this->cacheKey}:list:");
  $this->valkey->deleteByPrefix("{$this->cacheKey}:detail:");
  $this->valkey->deleteByPrefix("{$this->cacheKey}:similar:");
  $this->valkey->deleteByPrefix("{$this->cacheKey}:admin");
  ```

### 6. Standar Modul CRUD Referensi
- Gunakan modul **`ContentTeam`** (`src/controllers/Admin/AdminContentTeamController.php`, `src/services/Admin/AdminContentTeamService.php`, `src/validators/Admin/AdminContentTeamValidator.php`) sebagai standar acuan baku pembuatan modul CRUD baru.

---

## 🧪 Protokol Verifikasi Kode

Sebelum menyelesaikan tugas atau membuat commit:
1. Jalankan linter sintaks PHP pada seluruh file yang dimodifikasi (`php -l <path>`).
2. Jalankan test suite:
   ```bash
   composer test
   ```
3. Pastikan **100% tes berstatus hijau (0 failures, 0 errors)** sebelum menyerahkan hasil kepada user.
