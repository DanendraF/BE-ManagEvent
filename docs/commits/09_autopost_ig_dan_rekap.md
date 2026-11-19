# 📦 Laporan Fitur 09: Integrasi Auto-Post Instagram (Meta Graph API) & Rekap Dashboard

## 1. Ringkasan Fitur
Fitur 09 menyempurnakan siklus hidup acara dengan modul integrasi publikasi otomatis ke media sosial kampus dan pelaporan menyeluruh bagi panitia maupun Super Admin (DPM UII):
- **Auto-Post Instagram via Meta Graph API (2-Step Publishing Flow):**
  - Menggunakan alur resmi 2 tahap Meta Graph API:
    1. `POST /{ig-account-id}/media`: Membuat kontainer media dengan URL poster kegiatan dan takarir (*caption*) lengkap (waktu, lokasi, kuota, deskripsi, penyelenggara, hashtag kampus).
    2. `POST /{ig-account-id}/media_publish`: Mempublikasikan kontainer media secara resmi ke *feed* Instagram.
  - Dipicu otomatis di antrean latar belakang (`PostToInstagramJob`) saat Super Admin DPM menyetujui kegiatan (`AdminEventService::approveEvent`) via `DB::afterCommit(...)`.
  - **Proteksi Idempotency:** Memeriksa log di tabel `notification_logs` (saluran `INSTAGRAM`) untuk mencegah duplikasi unggahan postingan jika job dijalankan ulang.
  - Endpoint manual Panitia / DPM: `POST /api/v1/organizer/events/{id}/publish-instagram`.
- **Rekapitulasi Komprehensif Dashboard Kegiatan:**
  - Endpoint `GET /api/v1/organizer/events/{id}/recap` menyajikan statistik multi-dimensi dalam satu panggilan:
    - **Peserta:** Total pendaftar aktif, jumlah hadir, belum hadir, persentase tingkat kehadiran (`attendance_rate_percent`), dan tiket dibatalkan.
    - **Perekrutan Panitia (Oprec):** Total pelamar, rincian status seleksi (`SUBMITTED`, `INTERVIEW_SCHEDULED`, `ACCEPTED`, `REJECTED`).
    - **E-Sertifikat:** Jumlah sertifikat resmi yang telah diterbitkan.
    - **Notifikasi:** Status pengiriman pesan WhatsApp, Email, dan posting Instagram.
- **Ekspor Laporan Resmi Berformat CSV (Excel-Ready):**
  - Dihasilkan langsung dari memori stream (`php://temp`) dan diawali dengan byte **UTF-8 BOM (`\xEF\xBB\xBF`)** agar karakter teks bahasa Indonesia langsung terbaca rapi tanpa *glitch encoding* saat dibuka di Microsoft Excel.
  - `GET /api/v1/organizer/events/{id}/export/participants`: Berkas `rekap-peserta-{slug}.csv` (Nomor, Kode Tiket, NIM, Nama, Email, No HP, Fakultas, Prodi, Status Registrasi, Waktu Daftar, Status Presensi, Waktu Check-In, Nomor E-Sertifikat, Jawaban Kustom).
  - `GET /api/v1/organizer/events/{id}/export/applicants`: Berkas `rekap-pelamar-{slug}.csv` (Nomor, NIM, Nama, Email, No HP, Fakultas, Prodi, Divisi 1 & 2, Status Seleksi, Catatan Panitia, Waktu Daftar, Nama File CV).
- **Proteksi IDOR & Otorisasi RBAC:**
  - Panitia hanya dapat melihat rekap dan mengunduh berkas CSV untuk acara miliknya sendiri (atau oleh Super Admin DPM). Pihak lain diblokir dengan HTTP 403 `ERR_FORBIDDEN`.

---

## 2. File-File Baru & Perubahan

| Aksi | File Path | Deskripsi |
|---|---|---|
| **Baru** | `app/Services/InstagramPublishingService.php` | Service integrasi Meta Graph API 2-step publishing, caption formatting, & idempotency |
| **Baru** | `app/Jobs/PostToInstagramJob.php` | Background Queue Job auto-post poster ke Instagram dengan retry backoff |
| **Baru** | `app/Services/EventReportingService.php` | Service agregasi metrik dashboard & generator CSV ber-BOM UTF-8 |
| **Baru** | `app/Http/Controllers/Api/V1/EventReportingController.php` | REST Controller rekapitulasi, ekspor peserta, ekspor pelamar, & publish Instagram |
| **Baru** | `tests/Feature/InstagramAndReportingTest.php` | 9 skenario automated feature test (approval job dispatch, Meta Graph API, idempotency, error handling, recap, CSV export, IDOR) |
| **Modifikasi** | `app/Services/AdminEventService.php` | Menjalankan `PostToInstagramJob::dispatch()` di dalam `DB::afterCommit(...)` saat event diapprove |
| **Modifikasi** | `config/services.php` | Menambahkan konfigurasi Instagram (`account_id`, `access_token`, `graph_version`, `base_url`) |
| **Modifikasi** | `routes/api.php` | Menambahkan 4 endpoint rute panitia (`/recap`, `/export/participants`, `/export/applicants`, `/publish-instagram`) |
| **Modifikasi** | `app/Console/Commands/TestApiCommand.php` | Menambahkan skenario 35, 36, 37 |
| **Modifikasi** | `public/api-tester.html` | Menambahkan tab navigasi `📊 Rekap & Instagram`, tombol preset, dan template di Playground |
| **Modifikasi** | `docs/COMMANDS_CHEAT_SHEET.md` | Memperbarui daftar 37 skenario CLI dan panduan filter test |
| **Modifikasi** | `docs/api/API_DOCUMENTATION.md` | Menambahkan Bagian 10 (Auto-Post Instagram & Rekap Dashboard) |
| **Modifikasi** | `PROGRESS.md` | Memperbarui status Fitur 09 menjadi Selesai |

---

## 3. Hasil Pengujian

### A. Pengujian PHPUnit Fitur Instagram & Rekap Dashboard
```bash
php artisan test --filter InstagramAndReportingTest
```
- **Hasil:** `9 passed (40 assertions)`
- **Durasi:** ~3.2s

### B. Pengujian Seluruh Suite Pengujian
```bash
php artisan test
```
- **Hasil:** `73 passed (471 assertions)`
- **Status:** Seluruh 73 pengujian dari Fitur 01 hingga 09 lulus 100%.

### C. Pengujian API Internal CLI
```bash
php artisan api:test
```
- **Hasil:** `37 LULUS / 0 GAGAL (Total: 37 Skenario)`
