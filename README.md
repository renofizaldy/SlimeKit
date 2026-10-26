<p align="center">
  <img src="https://res.cloudinary.com/enterz/image/upload/w_550/f_auto,q_auto/logo_nbw_sxjvux.png" alt="SlimeKit Logo"/>
</p>

# SlimeKit v2
💚 *Not the monster you think. Just a better Slim.*

SlimeKit v2 adalah modern backend boilerplate dan **single-tenant micro-framework** berbasis **Slim Framework 4** yang dipadukan dengan **Illuminate Database (Eloquent ORM)**, **PHP-DI 7 auto-wiring**, dan arsitektur modular berlapis berstandar industri.

Dirancang untuk pengembang yang menginginkan kecepatan kilat, performa ringan, dan kesederhanaan Slim Framework—tetapi membutuhkan struktur kode yang rapi, *type-safe*, mudah diuji (*testable*), serta siap untuk lingkungan *production* berskala besar (Cloud Run, Docker, VPS).

> **Slim Framework 4 + Eloquent ORM + Dependency Injection + Developer Happiness = SlimeKit v2 🚀**

---

## 🌟 Arsitektur & Keunggulan

- **Pure Single-Tenant Architecture:** Skema database bersih, slug unik secara global, payload API ringkas, dan performa query maksimal tanpa *overhead* multi-tenancy ([Lihat ADR-001](docs/decisions/ADR-001-pure-single-tenant-architecture.md)).
- **Modern PHP (PHP 8.2 - 8.4):** Mengadopsi fitur modern PHP seperti *Constructor Property Promotion*, *Match Expressions*, *Strict Typing*, dan *Attributes*.
- **Dependency Injection & Auto-Wiring (PHP-DI 7):** Seluruh Controller, Service, Validator, dan Helper diinjeksi otomatis melalui container di `src/config/dependencies.php`. Bebas instansiasi manual `new Class()`.
- **Illuminate Database (Eloquent ORM):** Manajemen database ekspresif dengan relasi, mutator, accessor, query scopes, dan transaksi ACID `DB::beginTransaction()`.
- **Database Migration & Seeding (Phinx):** Skema database dan seeding data awal terstruktur dan terversi rapi.
- **Standardized API Response (`ApiResponse`):** Format output JSON terpadu untuk seluruh endpoint: status HTTP seragam, penanganan validasi, dan sanitasi pesan error produksi.
- **Rakit Validation:** Validasi input request yang kuat dan fleksibel, terintegrasi langsung dengan response handler JSON.
- **AI Content Agent System:** Pipeline pembuatan konten bertenaga AI multi-langkah (*Topic Ideation*, *Topic Selection*, *Deep Web Research* via **Tavily**, dan *Article Drafting & Polishing* via **Google Gemini** / **OpenAI**).
- **Background Jobs & Worker Protection:** Otomatisasi penerbitan artikel terjadwal, sinkronisasi statistik kunjungan, backup database otomatis, dan otentikasi token Bearer statis (`JOB_STATIC_TOKEN`).
- **On-Demand ISR Revalidation:** Terintegrasi langsung dengan frontend Next.js melalui `NextRevalidator` single webhook target.
- **Distributed Caching & Rate Limiting:** Didukung oleh **Valkey / Redis** dengan dukungan TLS (Upstash, Aiven, Redis Cloud) untuk caching performa tinggi dan perlindungan rate limiting (`RateLimitMiddleware`).
- **Multi-Cloud Storage & WhatsApp Failover:** Terintegrasi dengan **Cloudinary** (media gambar/video dengan varian 16:9 Google Discover), **Cloudflare R2** / **Google Cloud Storage** (backup database), serta multi-gateway WhatsApp notification (**Wapito** dengan failover otomatis ke **Wappfly**).
- **Comprehensive Automated Tests:** Dilengkapi lebih dari **293 unit & feature tests (847 assertions)** menggunakan **PHPUnit 11** dengan kelulusan 100% hijau.

---

## 📋 Spesifikasi & Kebutuhan Sistem

### 1. Spesifikasi Hardware (Server Specs)

| Komponen | Spesifikasi Minimum | Rekomendasi (Production) | Keterangan |
| :--- | :--- | :--- | :--- |
| **RAM** | 512 MB | 1 GB - 2 GB | Cukup untuk traffic sedang & serverless container |
| **CPU** | 0.5 vCPU / 1 Core | 1 vCPU - 2 vCPU | Optimal untuk proses kompresi gzip & AI payload |
| **Storage** | 1 GB free space | 5 GB+ SSD / NVMe | Ruang untuk log dan file kompresi backup database |
| **Network** | Akses internet keluar | Koneksi broadband stabil | Diperlukan untuk third-party API (Valkey, Cloudinary, AI) |

### 2. Spesifikasi Software & Runtime

- **PHP Runtime:** PHP `>= 8.2` (Sangat direkomendasikan **PHP 8.4**).
- **Ekstensi PHP Wajib:**
  - `pdo`, `pdo_pgsql` (untuk PostgreSQL) atau `pdo_mysql` (untuk MySQL)
  - `json`, `mbstring`, `openssl`, `curl`
- **Ekstensi PHP Direkomendasikan (Performance & Features):**
  - `intl` (format lokalisasi angka/tanggal)
  - `zip` & `zlib` (kompresi arsip dan gzip encoding)
  - `gmp` (kalkulasi kriptografi cepat)
  - `gd` (manipulasi gambar lokal jika diperlukan)
  - `apcu` & `opcache` (akselerasi performa eksekusi bytecode PHP di production)
- **Database Server:**
  - **PostgreSQL 14+** (Sangat direkomendasikan via [Neon](https://neon.tech), [Supabase](https://supabase.com), atau [Aiven](https://aiven.io)). Pastikan ekstensi `pg_trgm` aktif untuk fitur *similarity search* dan performa trigram.
  - Alternatif: **MySQL 8.0+** / **MariaDB 10.5+**.
- **In-Memory Cache & Queue:**
  - **Valkey 7.x** / **Redis 6.2+** (Sangat direkomendasikan [Upstash Redis](https://upstash.com) untuk serverless, atau Aiven / Redis Cloud).
- **Package Manager:**
  - **Composer 2.2+**
- **Utilitas CLI Tambahan (Opsional):**
  - `pg_dump` dan `gzip` terinstal di environment OS/container jika menggunakan fitur pencadangan database otomatis (`POST /job/database_backup`).

---

## 🔑 Penjelasan Lengkap Environment Variables (`.env`)

Seluruh konfigurasi SlimeKit dikelola melalui environment variable di file `.env`. Salin `.env.example` ke `.env` saat memulai proyek:

```bash
cp .env.example .env
```

Berikut adalah rincian lengkap fungsi dan kegunaan setiap key:

### 1. Konfigurasi Aplikasi & Keamanan Inti

| Key | Status | Contoh / Default | Penjelasan Fungsi |
| :--- | :---: | :--- | :--- |
| `ENVIRONMENT` | **Wajib** | `DEVELOPMENT` | Lingkungan aplikasi: `DEVELOPMENT`, `STAGING`, atau `PRODUCTION`. Pada mode `PRODUCTION`, pesan error mentah dan stack trace disanitasi demi keamanan. |
| `BASE_PATH` | **Wajib** | `/` | Base URL path routing. Gunakan `/` jika domain langsung mengarah ke `public/`, atau sesuaikan (misal: `/api`) jika di dalam subfolder. |
| `APP_NAME` | Opsional | `SlimeKit` | Nama aplikasi/layanan yang digunakan untuk identifier email, header, dan log. |
| `SYM_KEY` | **Wajib** | `CvTVi4^Y4FFdA6Rj4T@K+XeQJBU931hY` | Kunci simetris rahasia (minimal 32 karakter) untuk enkripsi & verifikasi JWT token pada `TokenMiddleware`. **Jangan gunakan kunci default di production!** |

### 2. Koneksi Database (Eloquent ORM & Phinx Migration)

| Key | Status | Contoh / Default | Penjelasan Fungsi |
| :--- | :---: | :--- | :--- |
| `DB_CLIENT` | **Wajib** | `pgsql` | Driver database yang digunakan: `pgsql` (PostgreSQL) atau `mysql` (MySQL). |
| `DB_HOST` | **Wajib** | `127.0.0.1` | Hostname atau alamat IP database server (misal: host Neon/Supabase). |
| `DB_PORT` | **Wajib** | `5432` | Port database (default `5432` untuk PostgreSQL, `3306` untuk MySQL). |
| `DB_NAME` | **Wajib** | `slimekit_db` | Nama database yang digunakan aplikasi. |
| `DB_USER` | **Wajib** | `neondb_owner` | Username akun autentikasi database. |
| `DB_PASS` | **Wajib** | `your-db-password` | Password akun autentikasi database. |

### 3. Media Storage (Cloudinary)

| Key | Status | Contoh / Default | Penjelasan Fungsi |
| :--- | :---: | :--- | :--- |
| `CLOUDINARY_URL` | Kondisional | `cloudinary://key:secret@cloud` | URL koneksi SDK Cloudinary resmi. Digunakan untuk upload gambar artikel/galeri dan auto-formatting varian 16:9 Google Discover (`w_1200,h_675`). Wajib jika menggunakan upload media. |

### 4. Layanan Pengiriman Email (SMTP / Mailjet)

| Key | Status | Contoh / Default | Penjelasan Fungsi |
| :--- | :---: | :--- | :--- |
| `SMTP_HOST` | Opsional | `in-v3.mailjet.com` | Host server SMTP untuk pengiriman email transaksional. |
| `SMTP_PORT` | Opsional | `587` | Port koneksi SMTP (`587` untuk TLS/STARTTLS, `465` untuk SSL). |
| `SMTP_USER` | Opsional | `your-api-key` | Username atau API key akun SMTP. |
| `SMTP_PASS` | Opsional | `your-secret-key` | Password atau Secret key akun SMTP. |
| `MAIL_FROM` | Opsional | `no-reply@domain.com` | Alamat email default pengirim notifikasi. |
| `MAIL_FROM_NAME` | Opsional | `SlimeKit Notification` | Nama tampilan yang muncul di inbox penerima. |

### 5. Cloud Object Storage (Cloudflare R2 & GCS)

| Key | Status | Contoh / Default | Penjelasan Fungsi |
| :--- | :---: | :--- | :--- |
| `R2_REGION` | Opsional | `auto` | Region Cloudflare R2 bucket (default `auto`). |
| `R2_ENDPOINT` | Kondisional | `https://<id>.r2.cloudflarestorage.com` | S3-compatible API endpoint untuk Cloudflare R2. Wajib jika menyimpan aset/backup di R2. |
| `R2_ACCESS_KEY_ID` | Kondisional | `your-access-key-id` | Kredensial R2 Access Key ID. |
| `R2_SECRET_ACCESS_KEY`| Kondisional | `your-secret-access-key` | Kredensial R2 Secret Access Key. |
| `R2_BUCKET` | Kondisional | `my-backup-bucket` | Nama bucket target penyimpanan di Cloudflare R2. |
| `GCS_BUCKET` | Kondisional | `my-gcs-bucket` | Nama bucket Google Cloud Storage untuk backup database. (Kredensial Service Account diletakkan di `assets/gcs-service-account.json`). |

### 6. Distributed Cache & Queue (Valkey / Redis)

| Key | Status | Contoh / Default | Penjelasan Fungsi |
| :--- | :---: | :--- | :--- |
| `VALKEY_SCHEME` | Opsional | `tcp` | Skema koneksi: `tcp` (koneksi standar) atau `tls` (koneksi terenkripsi SSL, wajib untuk Upstash / cloud Redis). |
| `VALKEY_HOST` | Opsional | `127.0.0.1` | Hostname Valkey / Redis instance. |
| `VALKEY_PORT` | Opsional | `6379` | Port Valkey / Redis (biasanya `6379`). |
| `VALKEY_USERNAME` | Opsional | `default` | Username autentikasi Redis ACL (default `default`). |
| `VALKEY_PASSWORD` | Opsional | `your-redis-pass` | Password autentikasi Redis instance. |

### 7. Background Jobs, Scheduler & Worker Security

| Key | Status | Contoh / Default | Penjelasan Fungsi |
| :--- | :---: | :--- | :--- |
| `JOB_STATIC_TOKEN` | **Wajib** | `sec-static-token-123` | Token rahasia otentikasi Bearer statis untuk mengamankan seluruh rute `/job/*` (mencegah eksekusi liar dari luar). |
| `BACKUP_PROVIDER` | Opsional | `both` | Target provider backup database: `r2`, `gcs`, atau `both` (default `both`). |
| `BACKUP_FOLDER` | Opsional | `backup` | Nama subdirektori di dalam bucket penyimpanan file cadangan SQL. |
| `CRONHOOKS_API_KEY` | Opsional | `your-cronhooks-token` | API Key layanan penjadwal [Cronhooks.io](https://cronhooks.io). |
| `CRONHOOKS_BASE_URL`| Opsional | `https://api.cronhooks.io` | Base URL endpoint API Cronhooks. |
| `CRONHOOKS_CALLBACK`| Opsional | `https://api.domain.com/job/article_publish` | URL webhook callback aplikasi Anda yang akan dipanggil Cronhooks saat jadwal tayang tiba. |

### 8. Frontend ISR Revalidation (Next.js)

| Key | Status | Contoh / Default | Penjelasan Fungsi |
| :--- | :---: | :--- | :--- |
| `NEXT_REVALIDATE_SECRET` | Kondisional | `your-secure-secret` | Token rahasia yang dikirimkan via header `x-revalidate-secret` atau query param ke frontend Next.js untuk otentikasi webhook ISR. |
| `NEXT_REVALIDATE_URL` | Kondisional | `http://localhost:3000/api/revalidate` | URL endpoint webhook revalidasi frontend Next.js. Dipicu otomatis saat artikel dibuat, diedit, dipublikasikan, atau dihapus. |

### 9. WhatsApp Notification Gateway (Multi-Gateway Failover)

| Key | Status | Contoh / Default | Penjelasan Fungsi |
| :--- | :---: | :--- | :--- |
| `WAPITO_API_TOKEN` | Opsional | `your-wapito-token` | Token API gateway WhatsApp utama (**Wapito**). |
| `WAPITO_BASE_URL` | Opsional | `https://api.wapito.com` | Base URL API Wapito. |
| `WAPPFLY_API_TOKEN` | Opsional | `your-wappfly-token` | Token API gateway cadangan (**Wappfly**). Otomatis aktif saat Wapito mengalami kegagalan / down. |
| `WAPPFLY_BASE_URL` | Opsional | `https://wappfly.com/api` | Base URL API Wappfly. |

### 10. AI Services Pipeline (Article Agent)

| Key | Status | Contoh / Default | Penjelasan Fungsi |
| :--- | :---: | :--- | :--- |
| `GEMINI_API_KEY` | Kondisional | `AIzaSy...` | API Key Google Gemini (model `gemini-1.5-flash` / `gemini-2.0-flash`) untuk drafting artikel & copywriting. |
| `OPENAI_API_KEY` | Kondisional | `sk-proj-...` | API Key OpenAI (model `gpt-4o-mini`) untuk AI brainstorming ide topik dan dynamic angles. |
| `TAVILY_API_KEY` | Kondisional | `tvly-...` | API Key Tavily Search Engine untuk *deep web research*, pengumpulan fakta, dan sitasi akurat. |

### 11. Logging & CORS Security

| Key | Status | Contoh / Default | Penjelasan Fungsi |
| :--- | :---: | :--- | :--- |
| `LOG_CHANNEL` | Opsional | `file` | Handler log Monolog (default `file` yang disimpan di `logs/app.log`). |
| `ALLOWED_ORIGINS` | **Wajib** | `http://localhost:3000` | Daftar origin domain frontend yang diizinkan mengakses API (CORS), dipisahkan tanda koma jika lebih dari satu. |

---

## 🏗️ Struktur Direktori Proyek

```text
slimekit/
├── assets/                  # Helper CLI generator, konfigurasi Apache, dan template CI/CD
│   ├── make_model.php       # Generator Eloquent Model
│   ├── make_module.php      # Unified Generator Modul (Admin & Client)
│   ├── apache/              # Konfigurasi Apache untuk container Docker
│   └── .github/workflows/   # Template GitHub Actions workflow
├── docs/                    # Dokumentasi arsitektur & keputusan teknis
│   └── decisions/           # Architecture Decision Records (ADRs)
│       └── ADR-001-pure-single-tenant-architecture.md
├── src/
│   ├── config/              # Inisialisasi database, dependencies & services
│   │   ├── dependencies.php # Definisi dependency injection container (PHP-DI 7)
│   │   └── services.php     # Konfigurasi provider pihak ketiga
│   ├── controllers/         # HTTP Controllers (Admin, Client, Job)
│   ├── helpers/             # Helper umum (General, ApiResponse, dll.)
│   ├── lib/                 # Integrasi external client (Valkey, Cloudinary, NextRevalidator, AI Clients)
│   │   ├── clients/         # GeminiClient, OpenAIClient, TavilyClient
│   │   ├── Cloudinary.php
│   │   ├── Database.php
│   │   ├── NextRevalidator.php
│   │   └── Valkey.php
│   ├── middlewares/         # Pipeline middleware (Auth, Token, RateLimit, CORS, Gzip)
│   ├── migrations/          # File migrasi database Phinx
│   ├── models/              # Eloquent Models
│   ├── routes/              # Definisi routing API (src/routes/Routes.php)
│   ├── seeds/               # Database seeder Phinx
│   ├── services/            # Business logic layer (Admin, Client, Job)
│   └── validators/          # Input validation layer (Rakit)
├── tests/                   # Automated PHPUnit Test Suite (293 tests)
│   ├── Feature/             # Endpoint & integration feature tests
│   └── Unit/                # Service, validator & helper unit tests
├── Dockerfile.dev           # Docker image untuk development
├── Dockerfile.prod          # Docker image teroptimasi multi-stage untuk production
├── docker-compose.yml       # Orchestrator lokal Docker
└── phinx.php                # Konfigurasi Phinx Migration
```

---

## ⚙️ Panduan Mulai Cepat (Quick Start)

### 1. Clone & Instalasi Dependensi
```bash
git clone <repository-url> slimekit-api
cd slimekit-api
composer install
```

### 2. Setup Environment
```bash
cp .env.example .env
# Edit file .env dan sesuaikan kredensial database (DB_HOST, DB_USER, DB_PASS, DB_NAME)
```

### 3. Migrasi & Seed Database
```bash
composer migrate
composer seed
```

### 4. Menjalankan Server Lokal
```bash
composer start
```
Aplikasi akan berjalan di `http://localhost:8787`.

---

## 🛠️ Generator CLI Modul & Model

SlimeKit menyediakan generator otomatis yang menghasilkan kode rapi berstandar *PHP 8 constructor promotion*, *Dependency Injection*, dan *ApiResponse*:

```bash
# 1. Membuat Modul Admin (Controller, Service, Validator)
composer make:module:admin Product

# 2. Membuat Modul Client (Controller, Service, Validator)
composer make:module:client Product

# 3. Membuat Eloquent Model
composer make:model Product products

# 4. Membuat File Migrasi Baru
composer make:migration CreateProductsTable
```

---

## 🧪 Pengujian Otomatis (Automated Testing)

SlimeKit mengimplementasikan pengujian otomatis menyeluruh menggunakan **PHPUnit 11**:

```bash
# Menjalankan seluruh test suite (Unit & Feature)
composer test

# Menjalankan Feature Tests saja
composer test:feature

# Menjalankan Unit Tests saja
composer test:unit

# Menjalankan test tertentu dengan filter
composer test:filter AdminArticleTest
composer test:filter JobTest
```

**Status Uji Saat Ini:**
```text
OK: Tests: 293, Assertions: 847, Failures: 0, Errors: 0
```

---

## ☁️ Deployment ke Google Cloud Run

SlimeKit siap di-deploy secara *serverless* ke **Google Cloud Run** menggunakan Docker container:

```bash
# 1. Build image Docker production (platform linux/amd64)
docker build --platform linux/amd64 -f Dockerfile.prod -t asia-southeast1-docker.pkg.dev/<PROJECT_ID>/<REPO>/slimekit-api:latest .

# 2. Push image ke Google Artifact Registry
docker push asia-southeast1-docker.pkg.dev/<PROJECT_ID>/<REPO>/slimekit-api:latest

# 3. Deploy ke Cloud Run
gcloud run deploy slimekit-api \
  --image=asia-southeast1-docker.pkg.dev/<PROJECT_ID>/<REPO>/slimekit-api:latest \
  --region=asia-southeast1 \
  --platform=managed \
  --allow-unauthenticated
```

CI/CD otomatis menggunakan GitHub Actions juga telah tersedia di `assets/.github/workflows/deploy.yml`.

---

## 📄 Lisensi
SlimeKit v2 dilisensikan di bawah [MIT License](LICENSE).
