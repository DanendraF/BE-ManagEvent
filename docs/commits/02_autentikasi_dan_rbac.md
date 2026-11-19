# Laporan Fitur 02: Autentikasi Sanctum & Role-Based Access Control (RBAC)

## 1. Identitas Fitur & Commit
- **Modul:** Autentikasi Sanctum & Role-Based Access Control Middleware
- **Status:** TERVERIFIKASI & SELESAI (Passed Testing by User)
- **Tanggal Verifikasi:** 19 November 2026
- **Endpoint Utama:**
  - `POST /api/v1/auth/register` (Public + Rate limited 5x/min)
  - `POST /api/v1/auth/login` (Public + Rate limited 5x/min)
  - `POST /api/v1/auth/logout` (Bearer token)
  - `GET /api/v1/auth/me` (Bearer token)
  - Middleware `role:STUDENT,ORGANIZER,SUPERADMIN`

---

## 2. Berkas Baru & Modifikasi

### Form Requests:
- `app/Http/Requests/Auth/RegisterRequest.php` (Validasi nama, email, password confirmed min 8, phone, nim, prodi, proteksi self-register superadmin)
- `app/Http/Requests/Auth/LoginRequest.php` (Validasi format email & password)

### Service & Controller:
- `app/Services/AuthService.php` (Business logic register, login, logout, getProfile)
- `app/Http/Controllers/Api/V1/AuthController.php` (Thin controller + `ApiResponse` trait)

### Resource & Middleware:
- `app/Http/Resources/UserResource.php` (Transformasi data pengguna & daftar peran ke JSON)
- `app/Http/Middleware/CheckRole.php` (Pencegahan IDOR/hak akses: mengunci endpoint berdasarkan peran pengguna dengan HTTP 403 `ERR_FORBIDDEN`)
- `bootstrap/app.php` (Pendaftaran alias middleware `'role' => CheckRole::class`)
- `routes/api.php` (Routing v1 auth dan route uji otorisasi peran)

### Pengujian Otomatis (Feature Test):
- `tests/Feature/AuthAndRbacTest.php` (8 skenario pengujian dengan 44 assertions, 100% lulus)

---

## 3. Panduan Pengujian oleh Pengguna (User Testing Guide)

### Langkah 1: Jalankan Automated Test Suite
Jalankan perintah berikut di terminal:
```bash
php artisan test --filter AuthAndRbacTest
```
*Ekspektasi:* Seluruh 8 test lulus (44 assertions, durasi ~1 detik).

### Langkah 2: Uji API via HTTP Client / Curl / Postman
*Pastikan local server aktif:* `php artisan serve`

#### A. Registrasi Mahasiswa Baru:
```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Budi Pratama",
    "email": "21523090@students.uii.ac.id",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "phone_number": "628123456789",
    "nim": "21523090",
    "faculty": "Fakultas Teknologi Industri",
    "study_program": "Informatika"
  }'
```
*Ekspektasi (201 Created):* Muncul respons envelope sukses dengan data user dan Bearer Token.

#### B. Login Akun Panitia yang Sudah Di-seed:
```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "lemfti@uii.ac.id",
    "password": "Password123!"
  }'
```
*Ekspektasi (200 OK):* Berhasil login dan mendapatkan token dengan peran `ORGANIZER`.

#### C. Ambil Profil Menggunakan Bearer Token:
```bash
curl -X GET http://127.0.0.1:8000/api/v1/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <GANTI_DENGAN_TOKEN_DARI_LOGIN>"
```
*Ekspektasi (200 OK):* Menampilkan data profil Sarah Nabila (LEM FTI UII).

#### D. Uji Proteksi Middleware Role (Hak Akses):
- Akses route khusus panitia `GET /api/v1/test/organizer-only` dengan token Mahasiswa:
  *Ekspektasi:* HTTP 403 Forbidden (`error_code: ERR_FORBIDDEN`).
- Akses route khusus panitia `GET /api/v1/test/organizer-only` dengan token Panitia:
  *Ekspektasi:* HTTP 200 OK.

---

## 4. Bukti Hasil Pengujian Internal
```
PASS Tests\Feature\AuthAndRbacTest
✓ user can register as student
✓ registration fails on validation errors
✓ user cannot self register as superadmin
✓ user can login with valid credentials
✓ login fails with invalid credentials
✓ profile requires authentication
✓ logout revokes token
✓ rbac role middleware access control

PASS Tests\Feature\DatabaseFoundationTest
✓ user roles and helpers work correctly
✓ event hierarchy and relations work correctly
✓ api 404 error response envelope

PASS Tests\Unit\ExampleTest
✓ that true is true

PASS Tests\Feature\ExampleTest
✓ the application returns a successful response

Tests: 13 passed (65 assertions)
Duration: 1.23s
```
