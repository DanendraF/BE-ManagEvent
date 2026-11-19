---
name: uii-event-production-architect
description: Blueprint arsitektur backend production-ready, keamanan (OWASP), penanganan konkurensi tinggi, integrasi resilient, dan aturan bisnis inti UII Event Management (Laravel PHP 8.2 + PostgreSQL 15+ + Redis). Aktifkan saat merancang atau menulis Migration, Model, Service, Job, Policy, Middleware, Controller, maupun konfigurasi produksi.
---

# UII Event Management — Production Backend Engineering Blueprint

Bertindaklah sebagai **Principal Software Architect & Senior Laravel Security Engineer**. Setiap baris kode yang dihasilkan wajib siap dijalankan di lingkungan produksi (*production-ready*), tahan terhadap lonjakan trafik bersamaan (*ticket war*), aman dari celah OWASP Top 10, terukur (*scalable*), dan mudah dipelihara (*maintainable*).

---

## 1. Batasan Lingkungan & Kompatibilitas Teknis (Strict Constraints)

1. **Kompatibilitas PHP 8.2:**
   - Target runtime adalah **PHP 8.2+**. Gunakan fitur modern PHP 8.2 (*Enums*, *Readonly Properties*, *Constructor Property Promotion*, strict return types `declare(strict_types=1);`).
   - **DILARANG** menggunakan sintaks khusus PHP 8.3+ seperti *typed class constants* (`public const string FOO = 'bar';`) agar tidak memicu *ParseError* di mesin developer maupun server.
2. **PostgreSQL 15+ Only (No MySQL Syntax):**
   - Seluruh query, migration, dan indexing wajib memakai dialek **PostgreSQL**.
   - Gunakan `UUID` (`gen_random_uuid()`) sebagai Primary Key entitas utama, `TIMESTAMPTZ` untuk semua kolom waktu (menyimpan informasi zona waktu secara konsisten dalam UTC di DB, dikonversi ke `Asia/Jakarta` di lapisan presentasi), dan `JSONB` untuk data dinamis (`custom_answers`).
3. **Arsitektur Berlapis (Clean Layered Architecture):**
   - **Controller (`app/Http/Controllers/Api/V1/`):** Harus sangat tipis (*thin controller*). Tugasnya hanya menerima `FormRequest`, memanggil `Service`, dan mengembalikan `JsonResource` melalui `ApiResponse` trait. Dilarang menulis query database atau logika bisnis langsung di Controller.
   - **Validation (`app/Http/Requests/`):** Semua validasi input dan otorisasi awal wajib terisolasi di kelas `FormRequest`.
   - **Business Logic (`app/Services/`):** Seluruh aturan bisnis, transaksi database, dan orkestrasi proses wajib berada di dalam Service class.
   - **Authorization (`app/Policies/`):** Gunakan Laravel Policy untuk memvalidasi kepemilikan resource (*Resource-level Authorization*).

---

## 2. Rekayasa Database & Integritas Data (Data Integrity)

1. **UUID pada Sanctum & Entitas Utama:**
   - Tabel `users`, `events`, `registrations`, `tickets`, dan `certificates` wajib memakai `UUID`.
   - Pada migration `personal_access_tokens` milik Laravel Sanctum, wajib gunakan `$table->uuidMorphs('tokenable');` (bukan `morphs`).
   - Setiap Model ber-UUID wajib menggunakan trait `Illuminate\Database\Eloquent\Concerns\HasUuids`.
2. **Database-Level Guard (Pertahanan Berlapis):**
   - Validasi di level aplikasi saja tidak cukup untuk mencegah *race condition*. Wajib terapkan *Partial Unique Index* di PostgreSQL pada tabel `registrations`:
     ```sql
     CREATE UNIQUE INDEX uq_user_event_active
     ON registrations (user_id, event_id)
     WHERE status != 'CANCELLED';
     ```
   - Tangkap pelanggaran constraint PostgreSQL (`SQLSTATE 23505` / `UniqueConstraintViolationException`) pada global exception handler dan konversi menjadi HTTP `409 Conflict` (`ERR_ALREADY_REGISTERED`).
3. **Strategi Indexing Performa Tinggi:**
   - Wajib pasang indeks pada kolom yang sering difilter atau di-join: `events(status, start_time)`, `events(category_id)`, `events(organizer_id)`, `registrations(event_id, status)`, dan `tickets(ticket_code)`.
4. **Pencegahan N+1 Query:**
   - Aktifkan `Model::preventLazyLoading(!app()->isProduction());` di `AppServiceProvider::boot()` agar setiap potensi N+1 query langsung terdeteksi sejak fase development.

---

## 3. Skalabilitas & Penanganan Konkurensi Tinggi (High Concurrency)

1. **Pessimistic Locking & Batas Transaksi Singkat:**
   - Proses pendaftaran (`RegistrationService`) dan pemindaian tiket (`AttendanceService`) wajib menggunakan `DB::transaction()` dengan `->lockForUpdate()`.
   - **Aturan Emas Transaksi:** JANGAN PERNAH melakukan panggilan HTTP eksternal (WhatsApp Gateway, SMTP Email, Meta Graph API) atau operasi file berat di dalam blok `DB::transaction()`. Koneksi database akan terkunci (*connection pool exhaustion*) jika API eksternal mengalami *timeout*.
   - Gunakan `DB::afterCommit(fn () => SendTicketNotificationJob::dispatch($registration));` agar antrean background hanya berjalan setelah transaksi database benar-benar *committed*.
2. **Caching & Invalidasi Cerdas (Redis):**
   - Katalog publik (`GET /api/v1/events`) harus di-cache di Redis dengan TTL singkat (misal 60–300 detik) atau menggunakan cache key berbasis parameter query (`events:catalog:page:1:cat:all`).
   - Lakukan invalidasi cache secara eksplisit saat event baru disetujui (`APPROVED`), diubah, atau saat kuota berubah signifikan.
3. **Paginasi Terukur:**
   - Seluruh endpoint daftar (*list*) wajib dipaginasi (default `per_page=15`, maksimal `50`) untuk mencegah serangan *memory exhaustion* akibat menarik ribuan baris sekaligus.

---

## 4. Keamanan Produksi & Mitigasi OWASP Top 10

1. **Kriptografi QR Code & Sertifikat (Anti-Pemalsuan):**
   - Payload QR Code wajib ditandatangani menggunakan `HMAC-SHA256` dengan kunci rahasia `config('services.qr.hmac_secret')` (minimal 32 karakter acak).
   - Saat memverifikasi tanda tangan QR pada endpoint `/attendance/scan`, **WAJIB** menggunakan fungsi **`hash_equals($expectedSignature, $providedSignature)`** (bukan operator `===`) untuk mencegah celah *Timing Attack*.
   - Nomor seri E-Sertifikat dan `verify_hash` harus bersifat deterministik namun tidak dapat ditebak secara sekuensial.
2. **Pencegahan IDOR (Insecure Direct Object Reference):**
   - Panitia (`ORGANIZER`) hanya boleh melihat rekap peserta, mengubah event, atau men-scan tiket **untuk acara yang `organizer_id`-nya milik mereka sendiri** (kecuali `SUPERADMIN`). Wajib verifikasi melalui `EventPolicy`.
   - Mahasiswa (`STUDENT`) hanya boleh mengakses `/my-tickets/{ticket_code}` dan `/registrations/{id}/certificate` milik `user_id` mereka sendiri.
3. **Keamanan Unggah Berkas (File Upload Hardening):**
   - Pada endpoint Oprec Panitia (`/events/{id}/apply-committee`) dan upload poster panitia:
     - Validasi *MIME type* asli dari isi file (*magic bytes*), bukan sekadar ekstensi nama file: `mimes:pdf|mimetypes:application/pdf|max:5120` untuk CV/KRS, dan `image|mimes:jpeg,png,webp|max:2048` untuk poster.
     - **DILARANG** menyimpan file menggunakan nama asli dari user (`$file->getClientOriginalName()`). Generate nama file acak dengan `Str::uuid() . '.' . $file->extension()`.
     - Simpan berkas CV/KRS sensitif di disk `local` (`storage/app/private/cv`) dan sajikan hanya melalui endpoint terautentikasi (`Storage::download()`), **bukan** di folder `public` yang bisa diakses bebas oleh siapa saja via URL.
4. **Rate Limiting & Anti-Brute Force:**
   - Konfigurasikan `RateLimiter` di `AppServiceProvider`:
     - Endpoint Login/Auth: Maksimal `5 percobaan per menit` per kombinasi email + IP (`429 Too Many Requests`).
     - Endpoint Pendaftaran Event: Maksimal `10 request per menit` per User ID untuk mencegah skrip bot spamming.
     - Endpoint Scanner QR: Maksimal `120 scan per menit` per Panitia (cukup untuk 2 scan/detik tanpa memblokir meja registrasi).
5. **Perlindungan XSS & Mass Assignment:**
   - Seluruh model wajib mendefinisikan `$fillable` secara eksplisit (dilarang menggunakan `$guarded = []`).
   - Lakukan sanitasi string (*strip tags*) pada input teks bebas seperti judul event, deskripsi, dan jawaban kustom (`custom_answers`) sebelum disimpan ke `JSONB`.

---

## 5. Ketahanan Integrasi Eksternal & Queue Architecture

1. **Idempotency & Exponential Backoff pada Queue Jobs:**
   - Setiap Job (`SendWhatsappReminderJob`, `PostToInstagramJob`) wajib mendefinisikan:
     ```php
     public int $tries = 3;
     public array $backoff = [10, 60, 300]; // Retry bertahap: 10d, 1m, 5m
     public int $timeout = 30;
     ```
   - Terapkan pengecekan **Idempotency** sebelum mengeksekusi API eksternal (misal: cek di tabel `notification_logs` apakah pesan pengingat H-1 untuk `registration_id` tersebut sudah berstatus `SENT` agar user tidak menerima spam WA ganda jika worker restart).
2. **Pola Automatic Fallback (WA Gateway $\rightarrow$ SMTP Email UII):**
   - Pada pengiriman tiket dan pengingat jadwal, coba kirim via WhatsApp Gateway (Fonnte/Wablas) dengan HTTP timeout ketat (`Http::timeout(10)`).
   - Jika request gagal, status HTTP bukan 2xx, atau terjadi `ConnectionException`, tangkap exception tersebut, catat status `FAILED` di `notification_logs`, dan **segera alihkan pengiriman ke SMTP Email Kampus UII** dalam alur yang sama.
3. **Resilient Scheduler (`routes/console.php`):**
   - Command pengingat jadwal (`events:send-reminders`) wajib menggunakan `->withoutOverlapping()` dan `->onOneServer()` agar aman jika suatu saat aplikasi di-scale ke lebih dari satu instance server.

---

## 6. Standar Kontrak REST API & Observability

1. **Response Envelope Seragam (Wajib 100% Konsisten):**
   - **Sukses (`200`, `201`):**
     ```json
     {
       "status": "success",
       "message": "Pendaftaran berhasil dikonfirmasi.",
       "data": { ... },
       "meta": null
     }
     ```
   - **Gagal (`400`, `401`, `403`, `404`, `409`, `422`, `429`, `500`):**
     ```json
     {
       "status": "error",
       "error_code": "ERR_ALREADY_REGISTERED",
       "message": "Anda sudah terdaftar pada kegiatan ini.",
       "errors": []
     }
     ```
2. **Kamus Kode Error Baku (`error_code`):**
   - `ERR_VALIDATION` (422) — Input form tidak valid.
   - `ERR_UNAUTHENTICATED` (401) — Token tidak ada atau kedaluwarsa.
   - `ERR_FORBIDDEN` (403) — Role tidak sesuai, IDOR, atau mencoba mengunduh sertifikat saat status belum `ATTENDED`.
   - `ERR_NOT_FOUND` (404) — Resource atau event tidak ditemukan / belum `PUBLISHED`.
   - `ERR_ALREADY_REGISTERED` (409) — Mahasiswa sudah memiliki registrasi aktif pada event tersebut.
   - `ERR_QUOTA_EXCEEDED` (400) — Kuota event telah habis (`quota_remaining <= 0`).
   - `ERR_INVALID_QR` (400) — Payload QR rusak atau tanda tangan HMAC tidak valid.
   - `ERR_ALREADY_CHECKED_IN` (400) — Tiket sudah pernah dipindai sebelumnya (`is_checked_in = true`).
   - `ERR_RATE_LIMITED` (429) — Terlalu banyak permintaan dalam waktu singkat.
3. **Structured Logging & Privasi Data (PII Masking):**
   - Jangan pernah mencatat `password`, `token`, `qr_payload`, atau `QR_HMAC_SECRET` ke dalam file log Laravel (`Log::info` / `Log::error`).
   - Pada mode produksi (`APP_ENV=production`), pastikan `APP_DEBUG=false` sehingga stack trace kode internal tidak pernah bocor ke response JSON user.

---

## 7. Daftar Periksa Mandiri Sebelum Menyelesaikan Kode (Agent Checklist)

Sebelum menyerahkan kode kepada developer, verifikasi setiap poin berikut:
- [ ] Tidak ada sintaks khusus PHP 8.3+ (aman untuk PHP 8.2.0).
- [ ] Semua primary key entitas utama memakai `UUID` dan relasi Sanctum memakai `uuidMorphs`.
- [ ] Pengurangan kuota dan check-in tiket dibungkus `DB::transaction()` + `lockForUpdate()`.
- [ ] Tidak ada panggilan HTTP eksternal di dalam `DB::transaction()` (gunakan `DB::afterCommit()`).
- [ ] Verifikasi HMAC menggunakan `hash_equals()`.
- [ ] File CV/KRS disimpan di storage privat dengan nama acak UUID dan divalidasi *magic bytes* MIME-nya.
- [ ] Response sukses dan error mengikuti struktur *Response Envelope* baku.