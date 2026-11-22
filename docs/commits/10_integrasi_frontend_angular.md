# 📦 Laporan Fitur 10: Integrasi Frontend Angular (UIIEvent) & CORS Bridging

## 1. Ringkasan Fitur
Fitur 10 menghubungkan antarmuka Single Page Application (SPA) Angular modern (`UIIEvent`) dengan layanan REST API Laravel (`backend/`) secara menyeluruh dan aman:

- **Konfigurasi Cross-Origin Resource Sharing (CORS):**
  - Diterbitkan file konfigurasi `backend/config/cors.php`.
  - Mengizinkan origin frontend lokal: `http://localhost:4200` dan `http://127.0.0.1:4200`.
  - Mengizinkan pengiriman kredensial (`supports_credentials: true`) dan header `Authorization` (Bearer token Sanctum) serta `Accept: application/json`.
- **Fleksibilitas Identitas Login (NIM / Email):**
  - Memperbarui `LoginRequest` dan `AuthService` agar pendaftar dapat login menggunakan **NIM murni** (contoh: `23525055`, `21523001`) maupun **Email resmi UII** (`23525055@students.uii.ac.id`).
- **Sinkronisasi Akun Demo Seeder:**
  - Menambahkan akun persona default frontend (**Muhammad Raynor Athaillah** NIM `23525055`, **Panitia UII** NIM `20523120`, dan **Dr. Ir. Budi Hartono, M.T.** Direktur DPK UII) ke `UserSeeder.php` dengan password bawaan `password123`.
- **Frontend HTTP Interceptor & Reactive Data Mappers:**
  - `src/app/core/interceptors/auth.interceptor.ts`: Secara otomatis menyisipkan Bearer Token Sanctum pada setiap outgoing request.
  - `src/environments/environment.ts`: Menyediakan URL basis API terpusat (`http://127.0.0.1:8000/api/v1`).
  - `src/app/core/services/auth.service.ts`: Integrasi metode `login()` dan `register()` dengan API backend serta mekanisme *graceful offline fallback*.
  - `src/app/core/services/event.service.ts`: Integrasi sinkronisasi katalog kegiatan (`/events`), tiket (`/my-tickets`), sertifikat (`/my-certificates`), pendaftaran peserta/oprec, dan check-in meja registrasi scanner (`/attendance/check-in`).

---

## 2. File-File Baru & Perubahan

| Aksi | File Path | Deskripsi |
|---|---|---|
| **Baru** | `backend/config/cors.php` | Konfigurasi CORS resmi Laravel untuk origin `http://localhost:4200` |
| **Baru** | `backend/docs/commits/10_integrasi_frontend_angular.md` | Laporan dokumentasi commit integrasi frontend Angular & backend Laravel |
| **Baru** | `UIIEvent/src/environments/environment.ts` | Konfigurasi environment Angular untuk base URL API backend |
| **Baru** | `UIIEvent/src/app/core/interceptors/auth.interceptor.ts` | Functional HTTP Interceptor penempel token Bearer Sanctum |
| **Modifikasi** | `backend/app/Http/Requests/Auth/LoginRequest.php` | Validasi login fleksibel untuk input `email` atau `identifier` (NIM/Email) |
| **Modifikasi** | `backend/app/Http/Controllers/Api/V1/Auth/AuthController.php` | Menerima identifier dan meneruskannya ke AuthService |
| **Modifikasi** | `backend/app/Services/Auth/AuthService.php` | Pengecekan kredensial login via `where('email')` atau `orWhere('nim')` |
| **Modifikasi** | `backend/database/seeders/UserSeeder.php` | Sinkronisasi akun demo default frontend (Raynor, Panitia, Admin DPK) |
| **Modifikasi** | `backend/PROGRESS.md` | Menambahkan status penyelesaian Fitur 10 |
| **Modifikasi** | `backend/README.md` | Memperbarui daftar akun demo pasca-seeding dan catatan koneksi CORS |
| **Modifikasi** | `backend/docs/api/API_DOCUMENTATION.md` | Memperbarui dokumentasi request body endpoint `POST /auth/login` |
| **Modifikasi** | `UIIEvent/src/app/app.config.ts` | Mendaftarkan `provideHttpClient(withInterceptors([authInterceptor]))` |
| **Modifikasi** | `UIIEvent/src/app/core/services/auth.service.ts` | Implementasi async login/register ke API backend Laravel dengan fallback lokal |
| **Modifikasi** | `UIIEvent/src/app/core/services/event.service.ts` | Penambahan sync backend untuk events, my-tickets, sertifikat, dan absensi |
| **Modifikasi** | `UIIEvent/src/app/features/auth/login.component.ts` | Penyesuaian `onLoginSubmit()` dan `onRegisterSubmit()` ke pola asynchronous |

---

## 3. Instruksi Pengujian E2E (Frontend $\leftrightarrow$ Backend)

1. **Jalankan Backend Laravel:**
   ```bash
   cd backend
   php artisan serve
   ```
2. **Jalankan Frontend Angular:**
   ```bash
   cd UIIEvent
   npm start
   ```
3. **Pengujian Login:**
   - Buka `http://localhost:4200/login`.
   - Pilih persona **Mahasiswa** (NIM `23525055`, password `password123`) lalu klik Masuk.
   - Amati di Network Tab: Request `POST http://127.0.0.1:8000/api/v1/auth/login` mengembalikan HTTP 200 OK dengan token Bearer Sanctum.
4. **Pengujian Pendaftaran Kegiatan & E-Tiket:**
   - Navigasi ke `/catalog`, pilih salah satu kegiatan, lalu selesaikan pendaftaran.
   - Request `POST /api/v1/events/{id}/register` berhasil dikirim dan e-tiket langsung terbit di menu `/tickets`.
5. **Pengujian Meja Registrasi Scanner:**
   - Masuk sebagai **Panitia** (`panitia@uii.ac.id`, password `password123`), buka `/scanner`.
   - Lakukan scan atau input kode tiket.
   - Request `POST /api/v1/attendance/check-in` terverifikasi via HMAC timing-safe dan sertifikat otomatis diterbitkan.
