# 📖 Dokumentasi Lengkap REST API — UII Event Management Platform
**Base URL:** `http://localhost:8000/api/v1`  
**Format Payload:** JSON (`Content-Type: application/json`, `Accept: application/json`)  
**Autentikasi:** Bearer Token (Laravel Sanctum)

---

## 1. Standar Respons Global (Response Envelope)

### Format Berhasil (HTTP 200 / 201)
```json
{
  "status": "success",
  "message": "Pesan deskripsi aksi berhasil.",
  "data": { ... },
  "meta": {
    "timestamp": "2026-11-19T10:00:00Z",
    "version": "v1.0"
  }
}
```

### Format Gagal (HTTP 400, 401, 403, 404, 409, 422, 500)
```json
{
  "status": "error",
  "error_code": "ERR_ALREADY_REGISTERED",
  "message": "Anda sudah terdaftar pada kegiatan ini.",
  "errors": [
    {
      "field": "user_id",
      "message": "NIM sudah terdaftar."
    }
  ]
}
```

### Kamus Kode Error (`error_code`):
- `ERR_VALIDATION` (422) — Input form tidak memenuhi aturan validasi.
- `ERR_UNAUTHENTICATED` (401) — Token tidak valid atau sesi berakhir.
- `ERR_FORBIDDEN` (403) — Tidak memiliki wewenang atau hak akses peran.
- `ERR_NOT_FOUND` (404) — Data tidak ditemukan.
- `ERR_ALREADY_REGISTERED` (409) — Duplikasi pendaftaran aktif (Double Entry Guard).
- `ERR_QUOTA_EXCEEDED` (400) — Kuota acara telah habis.
- `ERR_INVALID_QR` (400) — Tanda tangan kriptografi QR tidak cocok / palsu.
- `ERR_ALREADY_CHECKED_IN` (400) — Tiket telah dipindai sebelumnya.
- `ERR_RATE_LIMITED` (429) — Terlalu banyak permintaan (Spam/Brute force).
- `ERR_INTERNAL_SERVER` (500) — Kesalahan internal server.

---

## 2. Ringkasan Endpoint

| No | Method | Endpoint | Akses | Deskripsi |
|---|---|---|---|---|
| **Auth** | | | | |
| 1 | `POST` | `/auth/register` | Publik | Mendaftarkan akun mahasiswa baru |
| 2 | `POST` | `/auth/login` | Publik | Autentikasi user & penerbitan Bearer Token (Rate limited 5x/menit) |
| 3 | `POST` | `/auth/logout` | Auth | Revoke token aktif |
| 4 | `GET` | `/auth/me` | Auth | Mengambil detail profil user yang sedang login |
| **Events (Katalog Publik)** | | | | |
| 5 | `GET` | `/events` | Publik | Katalog daftar event aktif terpublikasi (filter & paginasi) |
| 6 | `GET` | `/events/{slug}` | Publik | Detail lengkap kegiatan |
| 7 | `GET` | `/categories` | Publik | Daftar kategori event (Seminar, Workshop, Lomba, dll.) |
| **Events (Panitia & Admin)** | | | | |
| 8 | `POST` | `/organizer/events` | Organizer | Membuat draf event baru |
| 9 | `PUT` | `/organizer/events/{id}` | Organizer | Mengubah draf event milik sendiri |
| 10 | `POST` | `/organizer/events/{id}/submit` | Organizer | Mengajukan event ke Super Admin untuk disetujui |
| 11 | `GET` | `/organizer/events` | Organizer | Daftar event yang dikelola panitia |
| 12 | `POST` | `/admin/events/{id}/approve` | Superadmin | Menyetujui event & memicu auto-post IG |
| 13 | `POST` | `/admin/events/{id}/reject` | Superadmin | Menolak pengajuan event dengan alasan |
| **Registrations & Tickets** | | | | |
| 14 | `POST` | `/events/{id}/register` | Student | Pendaftaran peserta (Concurrency-safe lock & E-tiket) |
| 15 | `POST` | `/events/{id}/apply-committee` | Student | Pendaftaran seleksi kepanitiaan (upload CV/KRS) |
| 16 | `GET` | `/my-tickets` | Student | Daftar e-tiket aktif milik mahasiswa |
| 17 | `GET` | `/my-tickets/{ticket_code}` | Student | Detail e-tiket ber-QR Code |
| 18 | `GET` | `/organizer/events/{id}/registrations` | Organizer | Rekap peserta event yang dikelola |
| **Presensi & Sertifikat** | | | | |
| 19 | `POST` | `/attendance/scan` | Organizer | Scanner presensi hari-H (HMAC verify & auto sertifikat) |
| 20 | `GET` | `/certificates/verify/{hash}` | Publik | Verifikasi keabsahan sertifikat publik SKPI |


---

## 3. Spesifikasi Detail Endpoint Autentikasi

### 3.1 Registrasi Akun Mahasiswa
- **URL:** `POST /api/v1/auth/register`
- **Akses:** Publik (Rate limit: 5x/menit)
- **Headers:** `Content-Type: application/json`, `Accept: application/json`

**Request Body:**
```json
{
  "name": "Ahmad Fauzan",
  "email": "21523001@students.uii.ac.id",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "phone_number": "6281234567890",
  "nim": "21523001",
  "faculty": "Fakultas Teknologi Industri",
  "study_program": "Informatika",
  "role": "STUDENT"
}
```

**Response Sukses (HTTP 201 Created):**
```json
{
  "status": "success",
  "message": "Registrasi akun berhasil. Selamat datang di UII Event Platform.",
  "data": {
    "user": {
      "id": "9c148da7-10ca-4d54-b17e-1e5743a049f6",
      "nim": "21523001",
      "name": "Ahmad Fauzan",
      "email": "21523001@students.uii.ac.id",
      "phone_number": "6281234567890",
      "faculty": "Fakultas Teknologi Industri",
      "study_program": "Informatika",
      "roles": [
        {
          "id": 1,
          "name": "STUDENT",
          "label": "Mahasiswa UII"
        }
      ],
      "created_at": "2026-11-19T05:00:00.000000Z"
    },
    "token": "1|sanctum_plain_text_token_string",
    "token_type": "Bearer"
  },
  "meta": {
    "timestamp": "2026-11-19T05:00:00Z",
    "version": "1.0"
  }
}
```

---

### 3.2 Login Pengguna
- **URL:** `POST /api/v1/auth/login`
- **Akses:** Publik (Rate limit: 5x/menit)
- **Headers:** `Content-Type: application/json`, `Accept: application/json`

**Request Body:**
```json
{
  "email": "21523001@students.uii.ac.id",
  "password": "Password123!"
}
```

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Login berhasil.",
  "data": {
    "user": {
      "id": "9c148da7-10ca-4d54-b17e-1e5743a049f6",
      "nim": "21523001",
      "name": "Ahmad Fauzan",
      "email": "21523001@students.uii.ac.id",
      "phone_number": "6281234567890",
      "faculty": "Fakultas Teknologi Industri",
      "study_program": "Informatika",
      "roles": [
        {
          "id": 1,
          "name": "STUDENT",
          "label": "Mahasiswa UII"
        }
      ],
      "created_at": "2026-11-19T05:00:00.000000Z"
    },
    "token": "2|sanctum_plain_text_token_string",
    "token_type": "Bearer"
  },
  "meta": {
    "timestamp": "2026-11-19T05:00:00Z",
    "version": "1.0"
  }
}
```

---

### 3.3 Ambil Profil Pengguna Saat Ini
- **URL:** `GET /api/v1/auth/me`
- **Akses:** Memerlukan Autentikasi (`Bearer Token`)
- **Headers:** `Authorization: Bearer <token>`, `Accept: application/json`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Data profil berhasil diambil.",
  "data": {
    "id": "9c148da7-10ca-4d54-b17e-1e5743a049f6",
    "nim": "21523001",
    "name": "Ahmad Fauzan",
    "email": "21523001@students.uii.ac.id",
    "phone_number": "6281234567890",
    "faculty": "Fakultas Teknologi Industri",
    "study_program": "Informatika",
    "roles": [
      {
        "id": 1,
        "name": "STUDENT",
        "label": "Mahasiswa UII"
      }
    ],
    "created_at": "2026-11-19T05:00:00.000000Z"
  },
  "meta": {
    "timestamp": "2026-11-19T05:00:00Z",
    "version": "1.0"
  }
}
```

---

### 3.4 Logout (Pencabutan Token)
- **URL:** `POST /api/v1/auth/logout`
- **Akses:** Memerlukan Autentikasi (`Bearer Token`)
- **Headers:** `Authorization: Bearer <token>`, `Accept: application/json`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Logout berhasil. Sesi token telah dicabut.",
  "data": null,
  "meta": {
    "timestamp": "2026-11-19T05:00:00Z",
    "version": "1.0"
  }
}
```
