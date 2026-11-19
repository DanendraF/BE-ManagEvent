# Laporan Fitur 01: Fondasi Arsitektur Database, Model Eloquent & RBAC

## 1. Identitas Fitur & Commit
- **Modul:** Arsitektur Basis Data, Model Eloquent, RBAC & Response Envelope
- **Status:** TERVERIFIKASI & SELESAI (Passed Testing by User)
- **Tanggal Verifikasi:** 19 November 2026
- **Target Database:** PostgreSQL 15+ (`uii_event_db`)
- **PHP Version:** PHP 8.2+ (Strict Types, Enums, HasUuids)

---

## 2. Berkas Baru & Modifikasi

### Migrasi Basis Data (PostgreSQL 15+):
- `database/migrations/0001_01_01_000000_create_users_table.php` (UUID PK, NIM, Nama, Email Kampus, No WA, Fakultas, Prodi)
- `database/migrations/2026_11_19_034327_create_personal_access_tokens_table.php` (Sanctum dengan `uuidMorphs`)
- `database/migrations/2026_11_19_040001_create_roles_and_user_roles_tables.php` (Tabel Role & Pivot M-to-M `user_roles`)
- `database/migrations/2026_11_19_040002_create_event_categories_table.php` (Master Kategori Acara)
- `database/migrations/2026_11_19_040003_create_events_table.php` (UUID PK, FK organizer & category, quota lock, timestamptz, index)
- `database/migrations/2026_11_19_040004_create_event_divisions_table.php` (Divisi untuk kegiatan Oprec)
- `database/migrations/2026_11_19_040005_create_registrations_table.php` (UUID PK, JSONB custom_answers, **PostgreSQL Partial Unique Index** `uq_user_event_active` anti-double entry)
- `database/migrations/2026_11_19_040006_create_tickets_table.php` (UUID PK, 1-to-1 registration, ticket_code, qr_payload, is_checked_in)
- `database/migrations/2026_11_19_040007_create_certificates_table.php` (UUID PK, 1-to-1 registration, cert_number, verify_hash)
- `database/migrations/2026_11_19_040008_create_notification_logs_table.php` (Audit trail pengiriman WhatsApp, Email, & Instagram)

### Model & Enums (PHP 8.2 Clean Architecture):
- `app/Enums/RoleEnum.php` (`STUDENT`, `ORGANIZER`, `SUPERADMIN`)
- `app/Enums/EventStatusEnum.php` (`DRAFT`, `PENDING_APPROVAL`, `PUBLISHED`, `REJECTED`, `COMPLETED`)
- `app/Enums/ActivityTypeEnum.php` (`PARTICIPANT_EVENT`, `COMMITTEE_RECRUITMENT`)
- `app/Enums/RegistrationStatusEnum.php` (`REGISTERED`, `ATTENDED`, `CANCELLED`, `WAITING_LIST`)
- `app/Enums/SelectionStatusEnum.php` (`SUBMITTED`, `INTERVIEW_SCHEDULED`, `ACCEPTED`, `REJECTED`)
- `app/Enums/NotificationChannelEnum.php` (`WHATSAPP`, `EMAIL`, `INSTAGRAM`)
- `app/Models/User.php` (`HasUuids`, helper `hasRole()`, `isStudent()`, `isOrganizer()`, `isSuperAdmin()`, relasi lengkap)
- `app/Models/Role.php`, `EventCategory.php`, `Event.php`, `EventDivision.php`, `Registration.php`, `Ticket.php`, `Certificate.php`, `NotificationLog.php`

### Arsitektur Response & Keamanan:
- `app/Traits/ApiResponse.php` (Format Envelope seragam: `status`, `message`, `data`, `meta`)
- `bootstrap/app.php` (Global Exception Handler: RFC-compliant JSON untuk `ERR_VALIDATION`, `ERR_UNAUTHENTICATED`, `ERR_FORBIDDEN`, `ERR_NOT_FOUND`, `ERR_ALREADY_REGISTERED`, `ERR_RATE_LIMITED`)
- `app/Providers/AppServiceProvider.php` (Pencegahan N+1 Query `preventLazyLoading`, RateLimiter untuk auth, registrations, dan scanner)

### Seeders:
- `database/seeders/RoleSeeder.php`
- `database/seeders/EventCategorySeeder.php`
- `database/seeders/UserSeeder.php` (Akun demo: Superadmin DPK, Panitia LEM FTI, 2 Mahasiswa)

---

## 3. Data Akun Pengujian (Seeded Users)

| Role | Nama | Email | Password | Keterangan |
|---|---|---|---|---|
| **SUPERADMIN** | Dr. Ir. Budi Hartono (DPK UII) | `admin.dpk@uii.ac.id` | `Password123!` | Pengelola Kemahasiswaan Kampus |
| **ORGANIZER** | Sarah Nabila (LEM FTI UII) | `lemfti@uii.ac.id` | `Password123!` | Penyelenggara Kegiatan |
| **STUDENT** | Ahmad Fauzan (NIM: 21523001) | `21523001@students.uii.ac.id` | `Password123!` | Mahasiswa Informatika FTI |
| **STUDENT** | Siti Rahmawati (NIM: 21523002) | `21523002@students.uii.ac.id` | `Password123!` | Mahasiswa Manajemen FBE |

---

## 4. Panduan Pengujian oleh Pengguna (User Testing Guide)

### Langkah 1: Jalankan Automated Unit & Feature Test
Jalankan perintah berikut di terminal:
```bash
php artisan test
```
*Ekspektasi:* Seluruh test (5 passed, 21 assertions) lulus 100%.

### Langkah 2: Verifikasi Status Migrasi & Seeder di PostgreSQL
Jalankan perintah berikut di terminal:
```bash
php artisan migrate:status
```
*Ekspektasi:* Seluruh migrasi berstatus `Ran: Yes`.

### Langkah 3: Verifikasi Data Seeder via Tinker
Jalankan `php artisan tinker` dan ketik perintah berikut:
```php
\App\Models\User::with('roles')->get()->map(fn($u) => [$u->name, $u->email, $u->roles->pluck('name')->toArray()]);
```
*Ekspektasi:* Muncul 4 user (Superadmin, Organizer, 2 Mahasiswa) dengan role masing-masing.

---

## 5. Bukti Hasil Pengujian Internal
```
PASS Tests\Unit\ExampleTest
✓ that true is true

PASS Tests\Feature\DatabaseFoundationTest
✓ user roles and helpers work correctly
✓ event hierarchy and relations work correctly
✓ api 404 error response envelope

PASS Tests\Feature\ExampleTest
✓ the application returns a successful response

Tests: 5 passed (21 assertions)
Duration: 0.92s
```
