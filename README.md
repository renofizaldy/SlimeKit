<p align="center">
  <img src="https://res.cloudinary.com/enterz/image/upload/w_550/f_auto,q_auto/logo_nbw_sxjvux.png" alt="SlimeKit Logo"/>
</p>

# SlimeKit v2
💚 *Not the monster you think. Just a better Slim.*

SlimeKit v2 adalah modern backend boilerplate & micro-framework berbasis **Slim Framework 4** yang dipadukan dengan **Illuminate Database (Eloquent ORM)**, **PHP-DI 7 auto-wiring**, dan arsitektur modular berlapis berstandar industri.

Dirancang untuk pengembang yang menginginkan kecepatan, performa ringan, dan kesederhanaan Slim Framework—tetapi membutuhkan struktur kode yang rapi, *type-safe*, mudah diuji (*testable*), serta siap untuk *production* berskala besar.

> **Slim Framework 4 + Eloquent ORM + Dependency Injection + Developer Happiness = SlimeKit v2 🚀**

---

## 🌟 Arsitektur & Keunggulan SlimeKit v2

- **Modern PHP (PHP 8.2 - 8.4):** Memanfaatkan fitur modern seperti *Constructor Property Promotion*, *Match Expressions*, *Strict Typing*, dan *Attributes*.
- **Dependency Injection & Auto-Wiring (PHP-DI 7):** Seluruh Controller, Service, Validator, dan Helper diinjeksi secara otomatis melalui container di `src/config/dependencies.php`. Tidak ada lagi *hardcoded* `new Class()` manual di controller.
- **Illuminate Database (Eloquent ORM):** Manajemen database yang intuitif dan ekspresif menggunakan Eloquent Capsule, relasi antar tabel, mutator, accessor, query scopes, serta transaksi database `DB::beginTransaction()`.
- **Database Migration & Seeding (Phinx):** Skema database dan data awal dikelola secara terstruktur dan terversi melalui Phinx.
- **Standardized API Response (`ApiResponse`):** Format output JSON standar untuk seluruh endpoint: status HTTP teratur, error formatting otomatis, penanganan validasi, dan sanitasi error exception produksi.
- **Rakit Validation:** Validasi input request yang kuat dan fleksibel, terintegrasi mulus dengan error handler JSON.
- **AI Article Agent System:** Integrasi pipeline AI multi-langkah (*Topic Ideation*, *Topic Selection*, *Deep Web Research* via **Tavily**, dan *Article Drafting & Polishing* via **Google Gemini** / **OpenAI**).
- **Job Automation & ISR Revalidator:** Endpoint background job untuk automasi publish artikel terjadwal, sinkronisasi metrik analytics, dan *On-Demand ISR Revalidation* untuk frontend Next.js (`NextRevalidator`).
- **Distributed Caching & Rate Limiting:** Didukung oleh **Valkey / Redis** dengan dukungan TLS (Upstash, Aiven, Redis Cloud) untuk caching performa tinggi dan perlindungan rate limiting (`RateLimitMiddleware`).
- **Storage Multi-Cloud:** Terintegrasi dengan **Cloudinary** (media gambar/video) dan **Cloudflare R2** (S3-compatible bucket storage).
- **Security & RBAC Middleware:**
  - `TokenMiddleware`: Autentikasi JWT berbasis simetrik key (`SYM_KEY`).
  - `AuthMiddleware`: Otorisasi berbasis role dan granular permissions (misal: `article:view`, `article:crud`).
  - `CorsMiddleware`: Kontrol header CORS dinamis dari environment.
  - `GzipDecoderMiddleware` & `GzipEncoder`: Kompresi dan dekompresi payload otomatis untuk menghemat bandwidth.
- **Comprehensive Automated Tests:** Dilengkapi lebih dari **246+ unit dan feature tests** (PHPUnit 11) yang mencakup 100% fungsionalitas inti, endpoint, middleware, dan container resolution.

---

## 🏗️ Struktur Direktori Proyek

```text
slimekit/api/
├── assets/                  # Helper CLI generator, konfigurasi Apache, dan asset
│   ├── make_model.php       # Generator Eloquent Model
│   ├── make_module.php      # Unified Generator Modul (Admin & Client)
│   └── apache/              # Konfigurasi Apache untuk Docker
├── src/
│   ├── config/              # Inisialisasi database & DI container
│   │   └── dependencies.php # Definisi dependensi PHP-DI
│   ├── controllers/         # HTTP Controllers (Admin, Client, Job)
│   ├── helpers/             # Helper umum (General, ApiResponse, dll.)
│   ├── lib/                 # Service client pihak ketiga (Valkey, Cloudinary, AI Clients, Revalidator)
│   │   ├── clients/         # GeminiClient, OpenAIClient, TavilyClient
│   │   ├── Cloudinary.php
│   │   ├── Database.php
│   │   ├── NextRevalidator.php
│   │   └── Valkey.php
│   ├── middlewares/         # Pipeline middleware (Auth, Token, RateLimit, CORS, Gzip)
│   ├── models/              # Eloquent Models
│   ├── routes/              # Routing definitions (Routes.php)
│   ├── services/            # Business logic layer (Admin & Client)
│   └── validators/          # Input validation layer (Rakit)
├── tests/                   # Automated PHPUnit Test Suite
│   ├── Feature/             # Endpoint & integration feature tests
│   └── Unit/                # Service & helper unit tests
├── db/                      # Phinx migrations & seeds
├── Dockerfile.dev           # Docker image untuk development
├── Dockerfile.prod          # Docker image teroptimasi untuk production
└── docker-compose.yml       # Orchestrator lokal Docker
```

---

## 📋 Requirements

- **PHP:** >= 8.2 (Direkomendasikan PHP 8.4)
- **Ekstensi PHP Wajib:** `pdo`, `pdo_pgsql` atau `pdo_mysql`, `intl`, `zip`, `gmp`, `gd`, `apcu`, `opcache`
- **Composer:** >= 2.x
- **Database:** PostgreSQL (Direkomendasikan via [Aiven](https://aiven.io) / [Supabase](https://supabase.com) / [Neon](https://neon.tech)) atau MySQL
- **Valkey / Redis:** (Opsional, direkomendasikan [Upstash](https://upstash.com) untuk serverless Redis)
- **Storage:** [Cloudinary](https://cloudinary.com) atau [Cloudflare R2](https://www.cloudflare.com/developer-platform/r2/)
- **Docker & Docker Compose:** (Opsional, untuk containerized environment)

---

## ⚙️ Instalasi & Setup Lingkungan

### 1. Clone Repositori
```bash
git clone <repository-url> slimekit-api
cd slimekit-api
```

### 2. Install Dependensi Composer
```bash
composer install
```
*(Gunakan `composer update --ignore-platform-reqs` jika ada perbedaan modul lokal spesifik).*

### 3. Konfigurasi Environment (`.env`)
Salin file template `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```

Sesuaikan variabel konfigurasi di dalam `.env`:
```env
# APLIKASI
ENVIRONMENT=DEVELOPMENT
BASE_PATH=/
APP_NAME=SlimeKit
SYM_KEY=CvTVi4^Y4FFdA6Rj4T@K+XeQJBU931hY

# DATABASE (Eloquent ORM)
DB_HOST=127.0.0.1
DB_PORT=5432
DB_USER=postgres
DB_PASS=password
DB_NAME=slimekit_db
DB_CLIENT=pgsql

# CLOUDINARY (Media Storage)
CLOUDINARY_URL=cloudinary://<key>:<secret>@<cloud_name>

# MAILJET (SMTP Email)
SMTP_HOST=in-v3.mailjet.com
SMTP_PORT=587
SMTP_USER=your-smtp-user
SMTP_PASS=your-smtp-pass
MAIL_FROM=no-reply@example.com
MAIL_FROM_NAME="SlimeKit"

# CLOUDFLARE R2 (S3-Compatible Object Storage)
R2_REGION=auto
R2_ENDPOINT=https://<account_id>.r2.cloudflarestorage.com
R2_ACCESS_KEY_ID=your-access-key-id
R2_SECRET_ACCESS_KEY=your-secret-access-key
R2_BUCKET=slimekit-bucket

# VALKEY / REDIS (Cache & Rate Limiting)
VALKEY_SCHEME=tcp
VALKEY_HOST=127.0.0.1
VALKEY_PORT=6379
VALKEY_USERNAME=default
VALKEY_PASSWORD=

# CRONHOOKS (Scheduled Webhook Triggers)
CRONHOOKS_API_KEY=
CRONHOOKS_BASE_URL=https://api.cronhooks.io
CRONHOOKS_CALLBACK=https://api.example.com/job/article_publish

# NEXT.JS ISR REVALIDATION
NEXT_REVALIDATE_SECRET=your-secure-revalidate-secret
NEXT_REVALIDATE_ENDPOINTS=default=http://localhost:3000/api/revalidate

# AI CLIENTS (Article Agent)
GEMINI_API_KEY=your-gemini-api-key
OPENAI_API_KEY=your-openai-api-key
TAVILY_API_KEY=your-tavily-api-key

# LOGGING & CORS
LOG_CHANNEL=file
ALLOWED_ORIGINS=http://localhost:3000,http://localhost:8080
```

---

## 🚀 Menjalankan Aplikasi

### Menggunakan PHP Built-in Server (Local Development)
```bash
composer start
```
Aplikasi akan aktif di `http://localhost:8787`.

### Menggunakan Docker & Docker Compose
SlimeKit v2 menyediakan `Dockerfile.dev` untuk pengembangan lokal dan `Dockerfile.prod` untuk produksi:
```bash
# Menjalankan container di background
docker-compose up -d

# Melihat log container
docker-compose logs -f

# Menghentikan container
docker-compose down
```
Aplikasi berjalan pada port `http://localhost:8080`.

### Menggunakan Laragon / XAMPP
Pastikan ekstensi PHP yang dibutuhkan aktif di `php.ini`. Atur `BASE_PATH` pada file `.env` jika aplikasi berada di dalam subfolder, contoh:
```env
BASE_PATH=/local/slimekit/api
```

---

## 🛠️ CLI & Code Generator

SlimeKit v2 dilengkapi generator CLI modern yang otomatis menerapkan arsitektur *Dependency Injection*, PHP 8 constructor promotion, dan *ApiResponse*.

### 1. Scaffold Modul Baru
Gunakan perintah terpadu untuk membuat Controller, Service, dan Validator:
```bash
# Membuat modul Admin (Controllers/Admin, Services/Admin, Validators/Admin)
composer make:module:admin Product

# Membuat modul Client (Controllers/Client, Services/Client, Validators/Client)
composer make:module:client Product
```
Atau via script langsung:
```bash
php assets/make_module.php admin Product
php assets/make_module.php client Product
```

### 2. Scaffold Eloquent Model Baru
```bash
composer make:model Product products
```
Perintah ini akan membuat file model Eloquent di `src/models/Product.php` yang terhubung ke tabel `products`.

### 3. Database Migration & Seeding (Phinx)
```bash
# Jalankan seluruh migrasi
composer migrate

# Buat file migrasi baru
composer make:migration CreateProductsTable

# Cek status migrasi
composer migrate:status

# Rollback migrasi terakhir
composer migrate:rollback

# Jalankan seeder database
composer seed

# Buat file seeder baru
composer make:seed UserSeeder
```

### 4. Refresh Autoload
```bash
composer dump-autoload -o
```

---

## 🤖 AI Article Agent & Job Automation

SlimeKit v2 menyertakan sistem pembuatan artikel bertenaga AI dan automasi background job:

### AI Article Agent Pipeline (`/admin/article_agent`)
1. **`POST /admin/article_agent/step1_input_topic`**: Mengirimkan topik awal, sistem menghasilkan variasi sudut pandang dan ide artikel.
2. **`POST /admin/article_agent/step1_input_selection`**: Menyimpan pilihan sudut pandang topik yang akan dikembangkan.
3. **`POST /admin/article_agent/step2_do_research`**: Menjalankan riset mendalam berbasis web menggunakan **Tavily API** untuk mengumpulkan data, fakta, dan sumber terkini.
4. **`POST /admin/article_agent/step3_do_write`**: Menghasilkan draf artikel lengkap, meta deskripsi SEO, dan struktur konten menggunakan **Google Gemini** atau **OpenAI**.
5. **`GET /admin/article_agent/detail`**: Mengambil progres dan hasil draf artikel.

### Job Automation Endpoints (`/job`)
- **`POST /job/article_publish`**: Mengecek artikel yang memiliki jadwal tayang dan mempublikasikannya, serta memicu revalidasi cache frontend Next.js secara instan melalui `NextRevalidator`.
- **`POST /job/article_sync_stats`**: Menyinkronkan agregasi metrik kunjungan artikel dari cache Valkey ke tabel database permanen.
- **`POST /job/article_track_stats`**: Merekam data statistik kunjungan real-time.

---

## 🧪 Automated Testing

SlimeKit v2 dibangun dengan fondasi pengujian otomatis yang komprehensif menggunakan **PHPUnit 11**:

```bash
# Menjalankan seluruh test suite (Unit & Feature)
composer test
# atau
vendor/bin/phpunit

# Menjalankan Feature Tests saja
composer test:feature

# Menjalankan Unit Tests saja
composer test:unit

# Menjalankan pengujian tertentu dengan filter nama
composer test:filter RoutesTest
composer test:filter JobTest
composer test:filter AdminArticleAgentTest
```

Seluruh pengujian dirancang untuk memverifikasi:
- Resolusi dependensi container PHP-DI tanpa error instansiasi.
- Integritas seluruh rute pada `src/routes/Routes.php`.
- Autentikasi JWT token dan otorisasi permission middleware.
- Validasi input Rakit untuk format valid dan payload tidak valid.
- Transaksi database rollback otomatis setelah pengujian selesai.

---

## ☁️ Deployment ke Google Cloud Run (via OpenTofu)

Berikut adalah panduan lengkap menyiapkan infrastruktur *serverless container* di Google Cloud Run menggunakan OpenTofu:

### 1. Install OpenTofu
Unduh dan pasang OpenTofu melalui panduan resmi di [opentofu.org](https://opentofu.org/docs/intro/install/).

### 2. Konfigurasi Variabel OpenTofu Lokal
- Masuk ke direktori `tofu/`.
- Salin `tofu/terraform.tfvars.example` menjadi `tofu/terraform.tfvars`.
- Isi seluruh variabel kredensial yang dibutuhkan.

### 3. Otomatisasi GitHub Actions
- Salin file `.github/workflows/deploy.yml` ke repositori root: `.github/workflows/deploy.yml`.
- Sesuaikan konfigurasi build dan target Cloud Run.

### 4. Setup Google Cloud CLI
```bash
# Login ke Google Cloud
gcloud auth login

# Buat Repository Artifact Registry untuk Docker image
gcloud artifacts repositories create <NAMA-REPO> \
  --repository-format=docker \
  --location=asia-southeast1 \
  --description="SlimeKit API Image Repository" \
  --project=<PROJECT-ID>

# Autentikasi Docker ke GCP Artifact Registry
gcloud auth configure-docker asia-southeast1-docker.pkg.dev

# Buat Google Cloud Storage Bucket untuk remote state OpenTofu
gcloud storage buckets create gs://<NAMA-BUCKET-UNIK> \
  --project=<PROJECT-ID> \
  --default-storage-class=STANDARD \
  --location=us-west1 \
  --uniform-bucket-level-access

# Aktifkan versioning pada bucket
gcloud storage buckets update gs://<NAMA-BUCKET-UNIK> --versioning
```

### 5. Atur Lifecycle Rule Google Cloud Storage
Tambahkan aturan *Lifecycle Management* agar versi `terraform.tfstate` lama dibersihkan secara otomatis:
1. Buka Bucket di GCP Console > tab **Lifecycle**.
2. Klik **Add a rule** > Action: **Delete Object**.
3. Conditions: atur batas versi pada **Number of newer versions** (misal: 10).
4. Klik **Create**.

### 6. Konfigurasi GitHub Repository Secrets & Variables
Tambahkan kredensial di **Settings > Secrets and variables > Actions**:

#### Secrets:
- `GCP_CREDENTIALS`: Isi dengan isi file Service Account JSON Key.
- `IMAGE_TAG`: Tag image Docker (contoh: `latest`).
- `SYM_KEY`: JWT Symmetric key rahasia.
- `CLOUDINARY_URL`: URL konfigurasi Cloudinary.
- `SMTP_USER` & `SMTP_PASS`: Kredensial SMTP Mailjet.
- `R2_ACCESS_KEY_ID` & `R2_SECRET_ACCESS_KEY`: Kredensial Cloudflare R2.
- `VALKEY_USERNAME` & `VALKEY_PASSWORD`: Kredensial Valkey / Upstash.
- `CRONHOOKS_API_KEY`: API Key Cronhooks.
- `GEMINI_API_KEY`: Google Gemini API Key.
- `TAVILY_API_KEY`: Tavily Search API Key.
- `OPENAI_API_KEY`: OpenAI API Key.

#### Variables:
- `GCP_PROJECT_ID`: ID Proyek Google Cloud.
- `GCP_REGION`: Region Cloud Run (misal: `asia-southeast1`).
- `GCS_BUCKET_NAME`: Nama bucket GCS untuk OpenTofu remote state.
- `REPO_NAME`: Nama repository Artifact Registry.
- `CLOUD_RUN_NAME`: Nama service Cloud Run (misal: `slimekit-api`).
- `ENVIRONMENT`: `production`.
- `BASE_PATH`: `/`.
- `APP_NAME`: `SlimeKit`.
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_CLIENT`: Koneksi database produksi.

### 7. Build, Push & Deploy Image Manual
```bash
# Build image Docker (Gunakan argumen platform untuk kompabilitas Cloud Run)
docker build --platform linux/amd64 -f Dockerfile.prod -t asia-southeast1-docker.pkg.dev/<PROJECT-ID>/<NAMA-REPO>/<NAMA-IMAGE>:latest .

# Push image ke Artifact Registry
docker push asia-southeast1-docker.pkg.dev/<PROJECT-ID>/<NAMA-REPO>/<NAMA-IMAGE>:latest

# Eksekusi OpenTofu
cd tofu
tofu init
tofu plan
tofu apply
```

### 8. Konfigurasi Service Account Google Cloud
1. Masuk ke **IAM & Admin > Service Accounts** di Google Cloud Console.
2. Buat Service Account baru (misal: `github-actions-deployer`).
3. Berikan permission (Roles) berikut:
   - **Cloud Run Admin**: Mengelola deployment dan revisi service Cloud Run.
   - **Service Account User**: Menjalankan service dengan identitas service account.
   - **Artifact Registry Administrator**: Mengunggah dan mengelola image container.
   - **Storage Object Admin**: Membaca dan memperbarui `terraform.tfstate` pada Google Cloud Storage.
4. Pada tab **Keys**, klik **Add Key > Create new key > JSON**, unduh dan simpan nilainya ke GitHub Secret `GCP_CREDENTIALS`.

---

## 📄 Lisensi
SlimeKit v2 dilisensikan di bawah [MIT License](LICENSE).