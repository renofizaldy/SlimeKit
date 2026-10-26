# Implementation Plan: Pembersihan Total Fitur Multi-Site (Pure Single-Tenant Architecture)

## Overview
Rencana implementasi ini bertujuan untuk menghapus seluruh konsep multi-site (`site`) yang masih tersisa pada modul Article, Category, Stats, Job, dan AI Agent di SlimeKit (`/Users/renofizaldy/Code/SlimeGo/SlimeKit`). Dengan pembersihan ini, SlimeKit akan beroperasi murni sebagai *single-tenant micro-framework*, menyederhanakan payload API, menghilangkan parameter `site` dari query/validator, merampingkan cache key Valkey, menyederhanakan Next.js ISR revalidator menjadi single URL, dan memberlakukan keunikan slug secara global di database.

---

## Architecture Decisions & User Choices

1. **Full Purge (Pembersihan Total):**
   - Menghapus kolom `site` dari skema tabel database (`tb_article`, `tb_article_category`, `tb_article_stats`).
   - Tidak ada lagi kolom `site` dummy atau nilai default `'default'`.

2. **Skema Database & Indeks Bersih:**
   - Memperbarui langsung file migrasi awal `create_tables.php` dan migrasi indeks trigram agar SlimeKit sebagai template boilerplate baru memiliki skema yang bersih dan murni single-tenant sejak awal.
   - Kolom `slug` pada `tb_article` dan `tb_article_category` diberi constraint `unique` global.
   - Indeks komposit disederhanakan dari `(site, status, slug)` menjadi `(status, slug)` dan `(status, publish)`.

3. **Penyederhanaan Cache Key Valkey:**
   - Menghilangkan segmen `site=%s:` dari seluruh pola key Valkey.
   - Contoh format baru yang flat:
     - `article:list:status=%s:featured=%s:category=%s:search=%s:page=%d:pageSize=%d:orderBy=%s:orderDir=%s`
     - `article:detail:slug=%s`
     - `article:similar:slug=%s`
     - `article_category:list:page=%d:pageSize=%d:orderBy=%s:orderDir=%s`
   - Pembersihan cache `invalidateArticleCache()` dan `invalidateCategoryAndArticleCache()` langsung menghapus prefix global `article:list:`, `article:detail:`, `article:similar:`, dan `article:admin`.

4. **Satu URL Next.js ISR Revalidation:**
   - Mengubah konfigurasi `NEXT_REVALIDATE_ENDPOINTS` (yang sebelumnya berupa format mapping `default=http://...`) menjadi variabel tunggal yang sederhana: `NEXT_REVALIDATE_URL=http://localhost:3000/api/revalidate`.
   - `NextRevalidator::revalidateArticle(string $slug, ?string $oldSlug = null)` hanya memerlukan slug tanpa parameter site.

5. **Pembersihan Helper & Uji Multi-Site:**
   - Menghapus method `getAllowedSites()` dan `isValidSite()` dari `General.php` serta variabel `ALLOWED_SITES` dari `.env.example`.
   - Menghapus test unit yang murni menguji validasi multi-site (`GeneralSiteValidationTest.php` dan `BlokbacaCacheAndInvalidationWorkflowTest.php`).

---

## Task List

### Phase 1: Database Schema, Models & NextRevalidator Simplification
- [x] **Task 1: Database Migration Schema Cleanup & Global Unique Slugs** (`src/migrations/`)
- [x] **Task 2: Models & Configuration Cleanup** (`src/models/`, `src/config/services.php`, `.env.example`)
- [x] **Task 3: NextRevalidator & General Helper Simplification** (`src/lib/NextRevalidator.php`, `src/helpers/General.php`)

### Checkpoint 1: Foundation, Schema & Helpers Verified
- [x] Validasi sintaks PHP (`php -l`)
- [x] Pengujian helper dan library dasar

---

### Phase 2: Article & Category Modules Purge (Admin & Client)
- [x] **Task 4: Admin & Client Article Validators & Services Cleanup** (`src/validators/`, `src/services/`)
- [x] **Task 5: Category & Stats Services & Validators Cleanup** (`src/validators/`, `src/services/`)
- [x] **Task 6: AI Article Agent & Job Background Services Cleanup** (`src/validators/`, `src/services/`)

### Checkpoint 2: Business Layer & Endpoints Purged of Multi-Site
- [x] Parameter `site` tidak lagi diterima/dibutuhkan pada seluruh endpoint
- [x] Cache invalidation berjalan global tanpa prefix site

---

### Phase 3: Test Suite Alignment & Full Green Verification
- [ ] **Task 7: Obsolete Multi-Site Test Files Removal & Unit Tests Alignment** (`tests/Unit/`)
- [ ] **Task 8: Feature Test Suite Alignment & Full Pass (`composer test`)** (`tests/Feature/`)

### Checkpoint 3: End-to-End Single-Tenant Test Suite Green
- [ ] Perintah `composer test` lulus 100% (semua feature dan unit test hijau)
- [ ] Tidak ada lagi sisa referensi `site` pada request payload pengujian

---

## Risks and Mitigations

| Risiko | Dampak | Mitigasi |
| :--- | :---: | :--- |
| **Konflik Slug pada data eksisting jika sebelumnya ada multi-site** | Sedang | Karena SlimeKit adalah boilerplate dasar untuk project baru, migrasi dibuat bersih untuk instalasi baru. Pada database dev, slug unik divalidasi via skema. |
| **Kegagalan ISR Revalidator karena perubahan variabel env** | Rendah | Menggunakan default `NEXT_REVALIDATE_URL` dengan fallback aman (`empty check`), sehingga tidak melempar fatal exception jika belum diisi. |
| **Cache Valkey lama masih menyisakan prefix site** | Rendah | Format key baru otomatis mengabaikan key lama, dan perintah flush cache dapat dipanggil saat deployment. |

---

## Open Questions
- *Tidak ada pertanyaan terbuka.* Seluruh opsi arsitektur telah disepakati melalui interview sebelumnya.
