# Laporan Fitur 04: Manajemen Acara Panitia & Kurasi Super Admin (DPM)

## 1. Identitas Fitur & Commit
- **Modul:** Siklus Hidup Acara (Event Lifecycle), IDOR Protection Policy, & Approval DPM
- **Status:** TERVERIFIKASI & SELESAI (Passed Testing by User)
- **Tanggal Verifikasi:** 19 November 2026
- **Endpoint Utama:**
  - `GET /api/v1/organizer/events` (Panitia: Daftar acara yang dikelola sendiri)
  - `POST /api/v1/organizer/events` (Panitia: Buat draf acara baru, otomatis menyertakan divisi jika Oprec)
  - `GET /api/v1/organizer/events/{id}` (Panitia: Detail acara sendiri)
  - `PUT /api/v1/organizer/events/{id}` (Panitia: Ubah data acara status DRAFT / REJECTED)
  - `POST /api/v1/organizer/events/{id}/submit` (Panitia: Ajukan draf ke DPM $\rightarrow$ status `PENDING_APPROVAL`)
  - `DELETE /api/v1/organizer/events/{id}` (Panitia: Hapus draf acara sendiri)
  - `GET /api/v1/admin/events` (Super Admin: Daftar seluruh pengajuan acara kampus)
  - `POST /api/v1/admin/events/{id}/approve` (Super Admin: Setujui acara $\rightarrow$ status `PUBLISHED`)
  - `POST /api/v1/admin/events/{id}/reject` (Super Admin: Tolak acara dengan catatan alasan $\rightarrow$ status `REJECTED`)

---

## 2. Berkas Baru & Modifikasi

### Kebijakan Otorisasi & Pencegahan IDOR:
- `app/Policies/EventPolicy.php` (Memvalidasi kepemilikan acara; mencegah Panitia A mengedit acara Panitia B dengan HTTP 403 `ERR_FORBIDDEN`, mengunci edit/delete saat acara sedang `PENDING_APPROVAL`, serta memastikan hanya Super Admin DPM yang dapat melakukan `approve` dan `reject`).
- `app/Providers/AppServiceProvider.php` (Mendaftarkan binding policy `Gate::policy(Event::class, EventPolicy::class)`).
- `app/Http/Controllers/Controller.php` (Menambahkan trait `AuthorizesRequests`).

### Form Requests:
- `app/Http/Requests/Events/CreateEventRequest.php` (Validasi skema draf acara, tanggal mulai masa depan, validasi divisi wajib jika Oprec).
- `app/Http/Requests/Events/UpdateEventRequest.php` (Validasi perubahan data acara).
- `app/Http/Requests/Events/RejectEventRequest.php` (Validasi alasan penolakan wajib minimal 5 karakter).

### Service Layer & Controller:
- `app/Services/OrganizerEventService.php` (Logika pembuatan slug unik, transaksi database simpan divisi, update selisih kuota aman, submit approval, dan delete draf).
- `app/Services/AdminEventService.php` (Logika transisi status acara `PUBLISHED` dan `REJECTED`).
- `app/Http/Controllers/Api/V1/OrganizerEventController.php` (Thin controller manajemen panitia).
- `app/Http/Controllers/Api/V1/AdminEventController.php` (Thin controller kurasi superadmin).

### Testing & Dokumentasi:
- `tests/Feature/EventLifecycleTest.php` (6 skenario komprehensif lifecycle, IDOR check, rejection loop, dan pembatasan mahasiswa; 38 assertions, 100% lulus).
- `app/Console/Commands/TestApiCommand.php` (Menambahkan skenario 12, 13, 14 pengujian lifecycle pada `php artisan api:test`).
- `public/api-tester.html` (Menambahkan tombol pengujian manajemen panitia & kurasi DPM).
- `docs/api/UII_Event_Platform.postman_collection.json` (Menambahkan Folder 3: Organizer Events & Folder 4: Admin Approval).
- `docs/api/API_DOCUMENTATION.md` (Dokumentasi lengkap Section 5).

---

## 3. Panduan Pengujian oleh Pengguna (User Testing Guide)

### Cara 1: Jalankan Automated Test Suite
```bash
php artisan test --filter EventLifecycleTest
```
*Ekspektasi:* Seluruh 6 test suite lulus 100% (38 assertions).

### Cara 2: Jalankan Artisan API Tester CLI
```bash
php artisan api:test
```
*Ekspektasi:* Seluruh 14 skenario pengujian lulus (termasuk skenario 12 buat draf, 13 ajukan submit, dan 14 approval superadmin DPM).

### Cara 3: Uji via Web Browser Tester (Opsional)
1. Jalankan `php artisan serve`
2. Buka `http://127.0.0.1:8000/api-tester.html`
3. Alur Pengujian Cepat:
   - Klik **Login Panitia (LEM FTI)** $\rightarrow$ Token panitia aktif.
   - Klik **POST /organizer/events (Buat Draf Baru)** $\rightarrow$ Draf kegiatan baru terbentuk dengan status `DRAFT`.
   - Klik **GET /organizer/events** $\rightarrow$ Menampilkan daftar acara yang dikelola LEM FTI.
   - Klik **Login Superadmin (DPM)** $\rightarrow$ Token DPM aktif.
   - Klik **GET /admin/events (Daftar Pengajuan DPM)** $\rightarrow$ Menampilkan seluruh daftar pengajuan acara kampus.

---

## 4. Bukti Hasil Pengujian Internal
```
PASS Tests\Feature\EventLifecycleTest
✓ organizer can create draft event
✓ organizer can create recruitment event with divisions
✓ organizer cannot update other organizers event (IDOR Protected)
✓ event approval lifecycle to published
✓ event rejection workflow
✓ student forbidden from organizer and admin endpoints

Tests: 6 passed (38 assertions)
Duration: 1.84s
```
Total pengujian seluruh aplikasi: **27 passed (245 assertions)**.
