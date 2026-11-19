# 📦 Laporan Fitur 07: Presensi QR Hari-H & Penerbitan E-Sertifikat Otomatis

## 1. Ringkasan Fitur
Fitur 07 mengimplementasikan modul presensi kehadiran hari-H berbasis verifikasi tanda tangan kriptografi QR Code dan penerbitan e-sertifikat instan yang terintegrasi secara otomatis:
- **Verifikasi Tanda Tangan Kriptografis Timing-Safe:** Verifikasi payload QR tiket (`ticket_code`, `registration_id`, `event_id`, `user_id`, `issued_at`, `signature`) menggunakan algoritma HMAC-SHA256 dengan perbandingan aman `hash_equals()` untuk mencegah celah eksploitasi serangan berbasis waktu (*timing attack*).
- **Pencegahan Tiket Palsu / Manipulasi Data:** Payload QR yang dimodifikasi atau memiliki tanda tangan tidak valid akan langsung ditolak dengan status HTTP 400 (`ERR_INVALID_QR`).
- **Pencegahan Double Check-in (Pessimistic Locking):** Menggunakan `DB::transaction()` dan `lockForUpdate()` pada entitas registrasi dan tiket saat pemindaian. Jika tiket sudah pernah digunakan (*checked-in*), sistem langsung menolak dengan status HTTP 409 (`ERR_ALREADY_CHECKED_IN`).
- **Penerbitan E-Sertifikat Otomatis:** Saat presensi berhasil dicatat (`Attendance`), sistem secara otomatis menerbitkan e-sertifikat resmi (`certificates`) untuk peserta dengan nomor unik berformat `CERT/UII/{YYYY}/{RANDOM}` dan hash verifikasi SHA-256 sepanjang 64 karakter (`verify_hash`).
- **Verifikasi Sertifikat Publik (Unauthenticated):** Endpoint publik `GET /api/v1/certificates/verify/{hash}` memungkinkan siapa saja (perusahaan, instansi, atau kampus) memvalidasi keaslian sertifikat digital secara instan tanpa perlu login.
- **Riwayat E-Sertifikat Mahasiswa:** Endpoint `GET /api/v1/my-certificates` memudahkan mahasiswa melihat dan mengunduh sertifikat digital dari seluruh acara yang telah dihadirinya.
- **Dashboard Rekapitulasi Presensi Panitia:** Endpoint `GET /api/v1/organizer/events/{id}/attendance` menyajikan data lengkap peserta yang hadir disertai metrik rekapitulasi real-time: total pendaftar, jumlah hadir, jumlah belum hadir, dan persentase tingkat kehadiran (*attendance rate*).

---

## 2. File-File Baru & Perubahan

| Aksi | File Path | Deskripsi |
|---|---|---|
| **Baru** | `app/Exceptions/InvalidQrSignatureException.php` | Exception khusus untuk payload QR tidak valid / manipulasi (HTTP 400 `ERR_INVALID_QR`) |
| **Baru** | `app/Exceptions/AlreadyCheckedInException.php` | Exception khusus untuk tiket yang sudah pernah dipindai (HTTP 409 `ERR_ALREADY_CHECKED_IN`) |
| **Baru** | `app/Http/Requests/Attendance/CheckInRequest.php` | FormRequest validasi data payload QR Code atau tiket manual |
| **Baru** | `app/Http/Resources/AttendanceResource.php` | API Resource format data kehadiran, waktu check-in, dan data sertifikat |
| **Baru** | `app/Http/Resources/CertificateResource.php` | API Resource format data sertifikat, nama penerima, nama acara, nomor, dan URL verifikasi |
| **Baru** | `app/Services/AttendanceService.php` | Service logika bisnis check-in, verifikasi HMAC, penerbitan sertifikat, dan metrik kehadiran |
| **Baru** | `app/Http/Controllers/Api/V1/AttendanceController.php` | Controller presensi: `checkIn` (scan QR) dan `eventAttendance` (dashboard panitia) |
| **Baru** | `app/Http/Controllers/Api/V1/CertificateController.php` | Controller sertifikat: `verify` (publik) dan `myCertificates` (mahasiswa) |
| **Baru** | `tests/Feature/AttendanceTest.php` | 9 skenario automated feature test (HMAC valid, fake signature, double check-in, auto-cert, verify hash, organizer metrics) |
| **Modifikasi** | `bootstrap/app.php` | Pemetaan exception handler `InvalidQrSignatureException` dan `AlreadyCheckedInException` |
| **Modifikasi** | `routes/api.php` | Menambahkan rute `/attendance/check-in`, `/organizer/events/{id}/attendance`, `/certificates/verify/{hash}`, `/my-certificates` |
| **Modifikasi** | `app/Console/Commands/TestApiCommand.php` | Menambahkan skenario 25-29 (Check-in QR, Double Check-in, Recap Panitia, Verify Sertifikat, My Certificates) |
| **Modifikasi** | `public/api-tester.html` | Desain ulang antarmuka tanpa scroll panjang: Tab navigasi kategori, preset presensi & sertifikat, dan fitur Custom Request Playground |
| **Modifikasi** | `docs/COMMANDS_CHEAT_SHEET.md` | Menambahkan 29 skenario CLI, filter test presensi, dan petunjuk operasional |
| **Modifikasi** | `docs/api/API_DOCUMENTATION.md` | Menambahkan Bagian 8 (Presensi QR Hari-H & Penerbitan E-Sertifikat) |
| **Modifikasi** | `PROGRESS.md` | Memperbarui status Fitur 07 menjadi Selesai |

---

## 3. Hasil Pengujian

### A. Pengujian PHPUnit Fitur Presensi & Sertifikat
```bash
php artisan test --filter AttendanceTest
```
- **Hasil:** `9 passed (44 assertions)`
- **Durasi:** ~1.4s

### B. Pengujian Seluruh Suite Pengujian
```bash
php artisan test
```
- **Hasil:** `54 passed (385 assertions)`
- **Status:** Seluruh 54 pengujian dari Fitur 01 hingga 07 lulus 100%.

### C. Pengujian API Internal CLI
```bash
php artisan api:test
```
- **Hasil:** `29 LULUS / 0 GAGAL (Total: 29 Skenario)`
