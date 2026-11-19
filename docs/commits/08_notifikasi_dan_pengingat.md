# 📦 Laporan Fitur 08: Otomasi Notifikasi & Pengingat (WhatsApp & Email)

## 1. Ringkasan Fitur
Fitur 08 mengimplementasikan modul notifikasi dan pengingat terotomasi berketahanan tinggi (*resilient*), terisolasi dari proses sinkron database (*non-blocking* via background queue jobs), serta dilengkapi mekanisme pencegahan spam (*idempotency*):
- **Pola Resilient Automatic Fallback (WhatsApp Gateway $\rightarrow$ SMTP Email UII):** Sistem memprioritaskan pengiriman melalui WhatsApp Gateway dengan batas waktu ketat (`Http::timeout(10)`). Jika gateway mengalami gangguan, koneksi *timeout*, token belum disetel, atau mengembalikan respon non-2xx, sistem mencatat status `FAILED` di log dan secara otomatis beralih (*fallback*) ke SMTP Email Kampus UII dalam alur yang sama tanpa kehilangan pesan.
- **Proteksi Idempotency:** Tabel `notification_logs` memantau riwayat pengiriman unik berdasarkan tipe pesan (`TICKET_ISSUED`, `REMINDER_H1`, `REMINDER_H3`). Eksekusi berulang atau restart worker antrean tidak akan mengirimkan pesan ganda kepada peserta.
- **Background Queue Jobs & Retry Backoff:** Pengiriman tiket dan reminder dijalankan secara asinkron di antrean background (`SendTicketNotificationJob`, `SendEventReminderJob`) dengan `tries = 3`, `backoff = [10, 60, 300]` (10 detik, 1 menit, 5 menit), dan `timeout = 30` detik. Dipicu melalui `DB::afterCommit(...)` saat pendaftaran transaksi selesai dikomit.
- **Scheduler Pengingat Acara Otomatis:** Perintah `php artisan events:send-reminders`:
  - Memindai kegiatan berstatus `PUBLISHED` untuk **Pengingat H-1** (23–25 jam sebelum acara dimulai).
  - Memindai kegiatan berstatus `PUBLISHED` untuk **Pengingat H-3 Jam** (2.5–3.5 jam sebelum acara dimulai).
  - Terjadwal di `routes/console.php` dengan `->everyFifteenMinutes()->withoutOverlapping()->onOneServer()`.
- **Dashboard Monitoring & Broadcast Panitia:**
  - `GET /api/v1/organizer/events/{id}/notifications`: Riwayat lengkap log pesan beserta metrik ringkasan (`total`, `sent`, `failed`, `whatsapp_count`, `email_count`).
  - `POST /api/v1/organizer/events/{id}/reminders`: *Broadcast* pengingat manual ke antrean seluruh peserta terdaftar.

---

## 2. File-File Baru & Perubahan

| Aksi | File Path | Deskripsi |
|---|---|---|
| **Baru** | `app/Services/NotificationService.php` | Service logika bisnis notifikasi: pengiriman tiket, pengingat, fallback WA ke email, idempotency, dan metrik |
| **Baru** | `app/Jobs/SendTicketNotificationJob.php` | Background Queue Job pengiriman e-tiket dengan retry backoff 10s, 60s, 300s |
| **Baru** | `app/Jobs/SendEventReminderJob.php` | Background Queue Job pengiriman pengingat jadwal dengan retry backoff |
| **Baru** | `app/Mail/EventTicketMail.php` | Mailable resmi penerbitan e-tiket dengan subject dan template HTML responsif |
| **Baru** | `app/Mail/EventReminderMail.php` | Mailable pengingat kegiatan H-1 dan H-3 Jam |
| **Baru** | `resources/views/emails/ticket.blade.php` | Template Blade email e-tiket resmi kampus UII |
| **Baru** | `resources/views/emails/reminder.blade.php` | Template Blade email pengingat acara kampus UII |
| **Baru** | `app/Console/Commands/SendEventRemindersCommand.php` | Command artisan `events:send-reminders` untuk memindai jadwal acara dan mendispatch job |
| **Baru** | `app/Http/Controllers/Api/V1/NotificationController.php` | REST Controller log notifikasi dan broadcast manual panitia |
| **Baru** | `app/Http/Resources/NotificationLogResource.php` | API Resource format data log pengiriman notifikasi |
| **Baru** | `tests/Feature/NotificationTest.php` | 10 skenario automated feature test (WA success, fallback email, token missing, idempotency, scheduler, organizer logs, IDOR) |
| **Modifikasi** | `config/services.php` | Menambahkan konfigurasi gateway WhatsApp (`endpoint`, `token`, `timeout`) |
| **Modifikasi** | `app/Services/RegistrationService.php` | Memanggil `SendTicketNotificationJob::dispatch()` di dalam `DB::afterCommit(...)` |
| **Modifikasi** | `routes/api.php` | Menambahkan rute `/organizer/events/{id}/notifications` dan `/organizer/events/{id}/reminders` |
| **Modifikasi** | `routes/console.php` | Menjadwalkan `events:send-reminders` setiap 15 menit secara aman |
| **Modifikasi** | `app/Console/Commands/TestApiCommand.php` | Menambahkan skenario 30-34 (pengiriman tiket fallback, idempotency, log panitia, broadcast, simulasi scheduler) |
| **Modifikasi** | `public/api-tester.html` | Menambahkan Tab Navigasi `🔔 Notifikasi & Pengingat`, tombol aksi preset, dan template di Playground |
| **Modifikasi** | `docs/COMMANDS_CHEAT_SHEET.md` | Menambahkan panduan queue worker, scheduler CLI, dan 34 skenario pengujian CLI |
| **Modifikasi** | `docs/api/API_DOCUMENTATION.md` | Menambahkan Bagian 9 (Otomasi Notifikasi & Pengingat) |
| **Modifikasi** | `PROGRESS.md` | Memperbarui status Fitur 08 menjadi Selesai |

---

## 3. Hasil Pengujian

### A. Pengujian PHPUnit Fitur Notifikasi & Pengingat
```bash
php artisan test --filter NotificationTest
```
- **Hasil:** `10 passed (46 assertions)`
- **Durasi:** ~2.8s

### B. Pengujian Seluruh Suite Pengujian
```bash
php artisan test
```
- **Hasil:** `64 passed (431 assertions)`
- **Status:** Seluruh 64 pengujian dari Fitur 01 hingga 08 lulus 100%.

### C. Pengujian API Internal CLI
```bash
php artisan api:test
```
- **Hasil:** `34 LULUS / 0 GAGAL (Total: 34 Skenario)`
