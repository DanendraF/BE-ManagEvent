# 🛠️ Panduan Lengkap Perintah CLI (Commands Cheat-Sheet)
## UII Event Management Platform — Backend Engineering

Dokumen ini disusun sebagai **buku panduan operasional dan bahan belajar (learning material)** bagi pengembang, penguji (QA), maupun kolaborator yang bekerja pada repositori backend ini.

---

## 📑 Daftar Isi
1. [Menjalankan Server & Layanan Lokal](#1-menjalankan-server--layanan-lokal)
2. [Pengujian Otomatis (PHPUnit & Feature Tests)](#2-pengujian-otomatis-phpunit--feature-tests)
3. [Alat Pengujian Cepat Bawaan Sistem (Custom API Tester)](#3-alat-pengujian-cepat-bawaan-sistem-custom-api-tester)
4. [Manajemen Basis Data PostgreSQL & Seeding](#4-manajemen-basis-data-postgresql--seeding)
5. [Eksplorasi Data & Debugging Interaktif (Laravel Tinker)](#5-eksplorasi-data--debugging-interaktif-laravel-tinker)
6. [Inspeksi Routing & Pembersihan Cache](#6-inspeksi-routing--pembersihan-cache)
7. [Standar Alur Kerja Git (Feature-by-Feature Workflow)](#7-standar-alur-kerja-git-feature-by-feature-workflow)

---

## 1. Menjalankan Server & Layanan Lokal

### Menjalankan Server HTTP API Lokal
```bash
php artisan serve
```
*Fungsi:* Mengaktifkan development web server Laravel di `http://127.0.0.1:8000`. Digunakan saat menguji API melalui Browser, Postman, Frontend Vue/React, atau Web Tester.

### Menjalankan Worker Antrean Background (Queue Worker)
```bash
php artisan queue:listen --tries=3 --timeout=30
```
*Fungsi:* Mendengarkan dan memproses tugas asinkron di antrean (*jobs*), seperti pengiriman pesan WhatsApp Gateway, SMTP email, dan integrasi Meta Instagram API.

### Menjalankan Penjadwal Otomatis (Scheduler / Cron Simulator)
```bash
php artisan schedule:work
```
*Fungsi:* Mensimulasikan cron job server setiap menit. Menjalankan command pengingat jadwal acara (H-1 pukul 09.00 WIB dan H-3 jam sebelum acara dimulai).

---

## 2. Pengujian Otomatis (PHPUnit & Feature Tests)

Sistem ini menganut standar *Test-Driven Quality*. Setiap fitur wajib memiliki automated test dengan skenario positif dan negatif.

### Menjalankan Seluruh Test Suite (Unit & Feature)
```bash
php artisan test
```
*Fungsi:* Menjalankan semua pengujian yang ada di direktori `tests/`. Menampilkan status pass/fail, durasi eksekusi, dan jumlah assertion.

### Menjalankan Pengujian per Modul / Fitur (Menggunakan `--filter`)

| Kategori Fitur | Perintah Eksekusi | Yang Diuji |
|---|---|---|
| **Fondasi Database & Model** | `php artisan test --filter DatabaseFoundationTest` | Migrasi PostgreSQL, relasi HasUuids, User helper (`isStudent`, `isOrganizer`), dan respons envelope 404 |
| **Autentikasi & RBAC** | `php artisan test --filter AuthAndRbacTest` | Registrasi mahasiswa, proteksi brute-force login, Sanctum Bearer token, endpoint `/auth/me`, logout, dan isolasi role middleware |
| **Katalog Acara Publik** | `php artisan test --filter EventCatalogTest` | Master kategori, katalog acara publik, filter pencarian judul/deskripsi, filter kategori/type, detail acara, dan proteksi draft |
| **Manajemen Acara & Kurasi** | `php artisan test --filter EventLifecycleTest` | Pembuatan draf acara panitia, proteksi IDOR kepemilikan acara, submit ke DPM, review approval DPM, dan rejection loop |
| **Pendaftaran & E-Tiket** | `php artisan test --filter RegistrationTest` | Concurrency lock kuota, database partial unique index anti-double entry, dan penerbitan tiket QR HMAC-SHA256 |
| **Presensi & Sertifikat** | `php artisan test --filter AttendanceTest` | Scan presensi hari-H dengan `hash_equals()`, anti-double check-in, dan e-sertifikat otomatis |

### Opsi Tambahan yang Sangat Berguna Saat Debugging:
```bash
# Berhenti langsung begitu ada 1 test yang gagal (sangat hemat waktu)
php artisan test --stop-on-failure

# Menampilkan output error secara detail / verbose
php artisan test --filter EventLifecycleTest -v
```

---

## 3. Alat Pengujian Cepat Bawaan Sistem (Custom API Tester)

Selain PHPUnit, sistem ini dilengkapi tool siap pakai tanpa instalasi tambahan:

### A. Pengujian Otomatis CLI Tanpa Membuka Server
```bash
php artisan api:test
```
*Fungsi:* Command internal yang menjalankan 14+ skenario HTTP internal end-to-end:
1. Login akun Panitia LEM FTI
2. Validasi login password salah (422)
3. Blokir request tanpa token (401)
4. Pengambilan profil terautentikasi (200)
5. Akses rute panitia diizinkan (200)
6. Akses rute superadmin diblokir untuk panitia (403)
7. Pencabutan sesi token logout (200)
8. Pemuatan master kategori kegiatan (200)
9. Katalog acara terpublikasi (200)
10. Detail acara lengkap (200)
11. Verifikasi isolasi draf tertutup dari publik (404)
12. Pembuatan draf baru oleh panitia (201)
13. Pengajuan draf ke DPM (200)
14. Persetujuan resmi (approval) oleh Superadmin DPM (200)

### B. Interactive Web UI Tester (Browser)
1. Jalankan `php artisan serve`
2. Buka di browser: `http://127.0.0.1:8000/api-tester.html`
3. Menghadirkan tombol cepat Login Panitia, Superadmin DPM, Mahasiswa, pemanggilan API profil, pengujian RBAC, katalog publik, dan pembuatan draf.

### C. Postman Collection
- Lokasi berkas: `docs/api/UII_Event_Platform.postman_collection.json`
- Tinggal di-import ke aplikasi Postman / Thunder Client di VS Code.

---

## 4. Manajemen Basis Data PostgreSQL & Seeding

### Memeriksa Status Migrasi Tabel
```bash
php artisan migrate:status
```
*Fungsi:* Menampilkan tabel migrasi mana saja yang sudah dijalankan (`Ran: Yes`) atau yang masih tertunda (`Ran: No`).

### Menjalankan Migrasi Baru
```bash
php artisan migrate
```

### Mengulang Basis Data dari Awal (Fresh Database)
```bash
# HATI-HATI: Menghapus seluruh tabel dan membuat ulang dari nol
php artisan migrate:fresh

# Reset total sekaligus mengisi seluruh data master dan akun demo
php artisan migrate:fresh --seed
```

### Menjalankan Seeder Tambahan Tanpa Menghapus Data
```bash
# Menjalankan seluruh seeder
php artisan db:seed

# Menjalankan seeder tertentu secara spesifik
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=EventCategorySeeder
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=EventSeeder
```

---

## 5. Eksplorasi Data & Debugging Interaktif (Laravel Tinker)

Tinker adalah konsol REPL interaktif berbasis PsySH untuk mengeksekusi kode PHP dan query Eloquent langsung ke basis data.

### Membuka Konsol Tinker
```bash
php artisan tinker
```

### Cuplikan Perintah Berguna di Dalam Tinker:

```php
// 1. Melihat daftar seluruh pengguna beserta role masing-masing
\App\Models\User::with('roles')->get()->map(fn($u) => [$u->name, $u->email, $u->roles->pluck('name')->toArray()]);

// 2. Melihat seluruh acara yang aktif terpublikasi
\App\Models\Event::where('status', 'PUBLISHED')->get(['title', 'slug', 'quota_remaining', 'quota_total']);

// 3. Menghitung jumlah kuota yang tersisa pada acara tertentu
$event = \App\Models\Event::where('slug', 'seminar-nasional-ai-2026')->first();
echo "Sisa Kuota: " . $event->quota_remaining . " dari " . $event->quota_total;

// 4. Melihat token aktif milik suatu akun
$user = \App\Models\User::where('email', 'lemfti@uii.ac.id')->first();
$user->tokens;

// 5. Keluar dari Tinker
exit;
```

---

## 6. Inspeksi Routing & Pembersihan Cache

### Melihat Seluruh Endpoint API yang Terdaftar
```bash
php artisan route:list --path=api
```
*Fungsi:* Menampilkan tabel lengkap HTTP Method, URI (`/api/v1/...`), Nama Route, Controller Action, dan Middleware yang melindungi endpoint tersebut.

### Membersihkan Berbagai Cache Framework (Saat Ada Perubahan Konfigurasi)
```bash
# Membersihkan cache konfigurasi .env
php artisan config:clear

# Membersihkan cache rute URL
php artisan route:clear

# Membersihkan cache aplikasi
php artisan cache:clear

# Membersihkan cache view template
php artisan view:clear

# Membersihkan seluruh cache sekaligus
php artisan optimize:clear
```

---

## 7. Standar Alur Kerja Git (Feature-by-Feature Workflow)

Proyek ini menggunakan konvensi commit semantik (*Conventional Commits*) dan prinsip verifikasi pengujian sebelum melakukan commit:

1. **Selesaikan Kode Fitur** (Model, Service, Controller, Resource, Route).
2. **Jalankan Pengujian Otomatis:**
   ```bash
   php artisan test --filter NamaFeatureTest
   ```
3. **Catat Laporan Pengujian** di berkas `.md` pada folder `docs/commits/`.
4. **Perbarui Status Proyek** di `PROGRESS.md`.
5. **Stage & Commit:**
   ```bash
   git status
   git add .
   git commit -m "feat(modul): deskripsi fitur yang dikerjakan secara singkat dan jelas"
   ```
6. **Push ke Repositori Utama:**
   ```bash
   git push origin main
   ```

---

*Buku panduan ini diperbarui berkala seiring bertambahnya fitur baru pada Sistem Manajemen Event BSI UII.*
