# 🎓 UII Event Management Platform — Backend API Service

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15%2B-316192?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Sanctum](https://img.shields.io/badge/Sanctum-Token%20Auth-red?style=for-the-badge)](https://laravel.com/docs/sanctum)
[![License](https://img.shields.io/badge/License-MIT-blue.svg?style=for-the-badge)](LICENSE)

> **Universitas Islam Indonesia (UII)** — Dewan Perwakilan Mahasiswa (DPM) & Lembaga Eksekutif Mahasiswa (LEM)  
> *Sistem Informasi Manajemen & Publikasi Kegiatan Terpadu Mahasiswa Kampus*

---

## 📌 Tentang Proyek

**UII Event Management Platform** adalah sistem satu pintu (*Single Source of Truth*) untuk merancang, mengurasi, mendaftarkan, menyiarkan, dan mengarsipkan kegiatan kemahasiswaan di lingkungan Universitas Islam Indonesia. 

Backend ini dibangun dengan standar rekayasa backend siap produksi (*production-ready*) untuk menyelesaikan 4 problem utama ekosistem kegiatan kampus:
1. **Pendaftaran Manual & Fragmentasi** $\rightarrow$ Digantikan oleh portal terpadu dengan auto-fill NIM dan validasi relasional real-time.
2. **Insiden Double Entry Data** $\rightarrow$ Dikunci secara mutlak pada level mesin PostgreSQL menggunakan *Partial Unique Index*.
3. **Poster Liar & Tidak Terpusat** $\rightarrow$ Kurasi izin kegiatan resmi satu pintu + Auto-Post ke Instagram Resmi Kampus via Meta Graph API.
4. **Tingginya Angka Ketidakhadiran (*No-Show*)** $\rightarrow$ Notifikasi pengingat otomatis terjadwal via WhatsApp Gateway & SMTP Email Kampus pada H-1 dan H-3 Jam.

---

## 🏛️ Arsitektur Rekayasa & Standar Keamanan

Layanan backend menerapkan pola **Clean Layered Architecture** dengan prinsip isolasi tanggung jawab yang ketat:

```
[ HTTP Request ]
       ↓
[ RateLimiter ] ──────→ Maksimal 5x/menit (Auth) & 10x/menit (Pendaftaran)
       ↓
[ FormRequest ] ──────→ Validasi skema input & otorisasi awal
       ↓
[ Controller ] ───────→ Thin Controller (hanya orkestrasi & HTTP response)
       ↓
[ Service Layer ] ────→ Logika bisnis, DB::transaction() + lockForUpdate()
       ↓
[ Model / Eloquent ] ─→ PostgreSQL 15+ Dialect (UUIDv4, TIMESTAMPTZ, JSONB)
       ↓
[ API Resource ] ─────→ Serialisasi data konsisten
       ↓
[ Response Envelope ] → Standard RFC-Compliant JSON (status, message, data, meta)
```

### Keunggulan Teknis:
- **Zero Double Entry Guarantee:** Menggunakan PostgreSQL Partial Unique Index:
  ```sql
  CREATE UNIQUE INDEX uq_user_event_active ON registrations (user_id, event_id) WHERE status != 'CANCELLED';
  ```
- **Concurrency-Safe Registrations:** Pendaftaran dibungkus dalam `DB::transaction()` dengan pessimistic locking `lockForUpdate()` untuk mencegah *overbooking* saat lonjakan trafik (*ticket war*).
- **Anti-Forgery E-Ticket QR:** Payload QR Code ditandatangani menggunakan `HMAC-SHA256` dan diverifikasi dengan timing-safe comparator `hash_equals()`.
- **Role-Based Access Control (RBAC):** Middleware `CheckRole` melindungi resource antar peran: `STUDENT`, `ORGANIZER`, dan `SUPERADMIN`.
- **N+1 Query Detection:** `Model::preventLazyLoading(!app()->isProduction())` aktif secara default untuk mencegah inefisiensi query sejak tahap pengembangan.

---

## 🚀 Kebutuhan Sistem & Dependensi

- **PHP:** `^8.2` (dengan ekstensi `pdo_pgsql`, `openssl`, `mbstring`, `curl`, `json`)
- **Database:** PostgreSQL `15+`
- **Cache / Session / Queue:** Redis / Database Driver
- **Composer:** `^2.x`

---

## ⚙️ Panduan Instalasi Cepat

### 1. Clone Repository & Masuk ke Folder
```bash
git clone https://github.com/DanendraF/BE-ManagEvent.git
cd BE-ManagEvent
```

### 2. Install Dependensi PHP
```bash
composer install
```

### 3. Konfigurasi Lingkungan (`.env`)
Salin berkas contoh konfigurasi:
```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan parameter koneksi basis data PostgreSQL di `.env`:
```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=uii_event_db
DB_USERNAME=postgres
DB_PASSWORD=rahasia_anda
```

### 4. Eksekusi Migrasi & Data Seeder Awal
```bash
php artisan migrate --seed
```

### 5. Jalankan Server Pengembangan
```bash
php artisan serve
```
Aplikasi backend akan aktif di: `http://127.0.0.1:8000`

---

## 👥 Akun Demo Uji Coba (Tersedia Pasca Seeding)

Semua akun demo di-seed dengan password bawaan: **`Password123!`**

| Role Sistem | Nama Pengguna | Alamat Email | Keterangan Akses |
|---|---|---|---|
| **SUPERADMIN** | Admin DPM UII | `admin.dpm@uii.ac.id` | Hak penuh kurasi, approval acara, & audit log kampus |
| **ORGANIZER** | Sarah Nabila (LEM FTI) | `lemfti@uii.ac.id` | Pengelola & konseptor kegiatan ormawa / UKM |
| **STUDENT** | Ahmad Fauzan (NIM: 21523001) | `21523001@students.uii.ac.id` | Mahasiswa FTI (Pendaftar peserta & tiket) |
| **STUDENT** | Siti Rahmawati (NIM: 21523002) | `21523002@students.uii.ac.id` | Mahasiswa FBE (Pendaftar peserta & sertifikat) |

---

## 🧪 Alat Pengujian API (API Testing Tools)

Proyek ini dilengkapi dengan 3 alternatif alat pengujian API:

### 1. Artisan CLI API Tester (Instan di Terminal)
Uji seluruh alur auth dan proteksi otorisasi secara instan tanpa perlu menjalankan `php artisan serve`:
```bash
php artisan api:test
```

### 2. Interactive Web API Tester (UI Browser)
Jalankan `php artisan serve`, lalu buka di browser Anda:
👉 **`http://127.0.0.1:8000/api-tester.html`**  
Fitur: One-click Login, token auto-storage, tombol test endpoint, dan penampil live response status HTTP.

### 3. Postman Collection v2.1
Import berkas berikut langsung ke aplikasi Postman / Thunder Client:  
📄 `docs/api/UII_Event_Platform.postman_collection.json`

### 4. Automated Feature & Unit Test (PHPUnit)
```bash
php artisan test
```

---

## 📑 Struktur Dokumentasi Proyek

- 📊 [PROGRESS.md](PROGRESS.md) — Pelacak kemajuan pengembangan fitur per fitur.
- 📖 [docs/api/API_DOCUMENTATION.md](docs/api/API_DOCUMENTATION.md) — Spesifikasi kontrak REST API, payload request/response, dan kamus error.
- 📝 [docs/commits/](docs/commits/) — Laporan resmi verifikasi pengujian sebelum setiap commit Git dilakukan.
- 📐 [docs/01_Master_PRD.md](docs/01_Master_PRD.md) — Master Product Requirement Document (PRD).
- ⚙️ [docs/02_System_Specification_SRS.md](docs/02_System_Specification_SRS.md) — Spesifikasi Kebutuhan Perangkat Lunak (SRS).
- 🗄️ [docs/03_Database_and_API_Contract.md](docs/03_Database_and_API_Contract.md) — Desain ERD & Kamus Data 3NF.

---

## 📄 Lisensi
Hak Cipta © 2026 Danendra Farrel Adriansyah. Berlisensi di bawah (LICENSE).
