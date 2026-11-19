# 📦 Laporan Fitur 05: Transaksi Pendaftaran Cerdas Jalur Peserta & E-Tiket Ber-QR Code

## 1. Ringkasan Fitur
Fitur 05 mengimplementasikan alur pendaftaran peserta ke kegiatan terpublikasi dengan jaminan integritas tingkat enterprise:
- **Proteksi Concurrency Tingkat Tinggi (Ticket War Safety):** Menggunakan `DB::transaction()` dengan `->lockForUpdate()` pada entitas `Event` untuk mengunci kuota secara pesimistik dari *race condition* dan *overbooking*.
- **Anti-Double Entry Guard:** Validasi lapis ganda — di tingkat aplikasi memeriksa status pendaftaran aktif, dan di tingkat basis data dikunci secara permanen melalui *PostgreSQL Partial Unique Index* (`uq_user_event_active` pada kolom `user_id, event_id` untuk `status != 'CANCELLED'`).
- **Penerbitan E-Tiket Ber-QR Kriptografis:** Setiap tiket dibuat dengan kode acak alfanumerik unik (`TKT-YYYY-XXXXXXXX`) dan payload QR terenkode Base64 yang memuat tanda tangan digital `HMAC-SHA256` (`hash_hmac('sha256', ..., config('app.key'))`) untuk mencegah pemalsuan tiket pada saat presensi.
- **Siklus Hidup Pembatalan & Pemulihan Kuota (Self-Cancellation):** Mahasiswa dapat membatalkan tiketnya sendiri, mengubah status pendaftaran menjadi `CANCELLED`, dan mengembalikan kuota kegiatan secara otomatis (+1). Mahasiswa diizinkan mendaftar kembali secara sah setelah pembatalan.
- **Monitoring Peserta oleh Panitia:** Endpoint terproteksi IDOR bagi panitia untuk melihat dan memfilter daftar peserta yang terdaftar pada kegiatan miliknya.

---

## 2. File-File Baru & Perubahan

| Aksi | File Path | Deskripsi |
|---|---|---|
| **Baru** | `app/Http/Requests/Registrations/RegisterParticipantRequest.php` | FormRequest validasi custom answers pendaftaran peserta |
| **Baru** | `app/Http/Resources/TicketResource.php` | API Resource formatting tiket, event detail, dan QR signature |
| **Baru** | `app/Http/Resources/RegistrationResource.php` | API Resource formatting pendaftaran, profil mahasiswa, dan tiket |
| **Baru** | `app/Services/RegistrationService.php` | Service transaksi pendaftaran, pessimistic lock, sign HMAC QR, cancel, & restore |
| **Baru** | `app/Http/Controllers/Api/V1/RegistrationController.php` | REST Controller pendaftaran, my tickets, cancel, dan attendee list |
| **Baru** | `tests/Feature/RegistrationTest.php` | 8 automated feature test cases mencakup concurrency, anti-double entry, cancel, dan IDOR |
| **Modifikasi** | `database/migrations/2026_11_19_040006_create_tickets_table.php` | Mengubah tipe kolom `qr_payload` menjadi `text` untuk menampung JSON payload bertanda tangan |
| **Modifikasi** | `bootstrap/app.php` | Menangani `ConflictHttpException` agar mengembalikan kode error `ERR_ALREADY_REGISTERED` (409) |
| **Modifikasi** | `routes/api.php` | Menambahkan rute `/events/{id}/register`, `/my-tickets`, `/my-tickets/{code}/cancel`, `/organizer/events/{id}/registrations` |
| **Modifikasi** | `app/Console/Commands/TestApiCommand.php` | Menambahkan skenario 15-20 untuk verifikasi registrasi, tiket QR, double entry, dan pembatalan |
| **Modifikasi** | `public/api-tester.html` | Menambahkan kartu pengujian pendaftaran dan pembatalan tiket di web UI tester |
| **Modifikasi** | `docs/COMMANDS_CHEAT_SHEET.md` | Memperbarui daftar 20 skenario CLI test |
| **Modifikasi** | `docs/api/API_DOCUMENTATION.md` | Menambahkan Bagian 6 (Pendaftaran Peserta & E-Tiket) |

---

## 3. Hasil Pengujian

### A. Pengujian Fitur PHPUnit
```bash
php artisan test --filter RegistrationTest
```
- **Hasil:** `8 passed (50 assertions)`
- **Waktu Eksekusi:** ~2.37s

### B. Pengujian Seluruh Test Suite
```bash
php artisan test
```
- **Hasil:** `35 passed (295 assertions)`

### C. Pengujian API Internal CLI
```bash
php artisan api:test
```
- **Hasil:** `20 LULUS / 0 GAGAL (Total: 20 Skenario)`
