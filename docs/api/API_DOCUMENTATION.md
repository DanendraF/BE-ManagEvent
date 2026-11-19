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


---

## 4. Spesifikasi Detail Endpoint Katalog Acara Publik

### 4.1 Ambil Daftar Kategori Kegiatan
- **URL:** `GET /api/v1/categories`
- **Akses:** Publik
- **Headers:** `Accept: application/json`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Daftar kategori kegiatan berhasil dimuat.",
  "data": [
    {
      "id": 1,
      "name": "Seminar",
      "slug": "seminar",
      "description": "Seminar akademik, talkshow inspiratif, dan simposium nasional."
    },
    {
      "id": 2,
      "name": "Workshop",
      "slug": "workshop",
      "description": "Pelatihan keterampilan praktis, hands-on coding, dan sertifikasi keahlian."
    }
  ],
  "meta": {
    "timestamp": "2026-11-19T05:30:00Z",
    "version": "1.0"
  }
}
```

---

### 4.2 Ambil Katalog Acara Terpublikasi (Dengan Filter & Paginasi)
- **URL:** `GET /api/v1/events`
- **Akses:** Publik
- **Headers:** `Accept: application/json`
- **Query Parameters:**
  - `search` *(string, opsional)*: Kata kunci judul / deskripsi acara (misal `artificial`).
  - `category` *(string|int, opsional)*: Slug kategori (misal `seminar`) atau ID kategori.
  - `type` *(string, opsional)*: `PARTICIPANT_EVENT` (Peserta) atau `COMMITTEE_RECRUITMENT` (Oprec).
  - `page` *(int, opsional, default: 1)*: Halaman aktif.
  - `limit` *(int, opsional, default: 15, max: 50)*: Jumlah item per halaman.

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Katalog kegiatan berhasil dimuat.",
  "data": [
    {
      "id": "789a-bc01-uuid",
      "title": "Seminar Nasional AI & Karir Teknologi 2026",
      "slug": "seminar-nasional-ai-2026",
      "category": "Seminar",
      "activity_type": "PARTICIPANT_EVENT",
      "organizer_name": "Sarah Nabila (LEM FTI UII)",
      "poster_url": "https://storage.uii-event.id/posters/ai-2026.jpg",
      "start_time": "2026-11-25T01:30:00.000000Z",
      "end_time": "2026-11-25T05:00:00.000000Z",
      "location": "Auditorium KH. Abdurrahman Wahid FTI UII",
      "quota_total": 250,
      "quota_remaining": 42,
      "is_quota_full": false
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 3,
    "last_page": 1
  }
}
```

---

### 4.3 Ambil Detail Acara Berdasarkan Slug
- **URL:** `GET /api/v1/events/{slug}`
- **Akses:** Publik
- **Headers:** `Accept: application/json`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Detail kegiatan berhasil dimuat.",
  "data": {
    "id": "789a-bc01-uuid",
    "title": "Seminar Nasional AI & Karir Teknologi 2026",
    "slug": "seminar-nasional-ai-2026",
    "description": "Membahas tren artificial intelligence masa depan dan kesiapan karir lulusan informatika.",
    "activity_type": "PARTICIPANT_EVENT",
    "poster_url": "https://storage.uii-event.id/posters/ai-2026.jpg",
    "status": "PUBLISHED",
    "start_time": "2026-11-25T01:30:00.000000Z",
    "end_time": "2026-11-25T05:00:00.000000Z",
    "location": "Auditorium KH. Abdurrahman Wahid FTI UII",
    "quota_total": 250,
    "quota_remaining": 42,
    "is_quota_full": false,
    "category": {
      "id": 1,
      "name": "Seminar",
      "slug": "seminar",
      "description": "Seminar akademik..."
    },
    "organizer": {
      "id": "user-uuid",
      "name": "Sarah Nabila (LEM FTI UII)",
      "email": "lemfti@uii.ac.id",
      "phone_number": "6282222222222",
      "faculty": "Fakultas Teknologi Industri"
    },
    "divisions": [],
    "created_at": "2026-11-19T05:00:00.000000Z"
  },
  "meta": {
    "timestamp": "2026-11-19T05:30:00Z",
    "version": "1.0"
  }
}
```
*(Catatan: Jika acara bertipe `COMMITTEE_RECRUITMENT`, properti `divisions` akan berisi array daftar divisi yang dibuka beserta kuota masing-masing).*

**Response Gagal — Draf / Tidak Ditemukan (HTTP 404 Not Found):**
```json
{
  "status": "error",
  "error_code": "ERR_NOT_FOUND",
  "message": "Kegiatan tidak ditemukan atau belum dipublikasikan.",
  "errors": []
}
```


---

## 5. Modul Manajemen Acara Panitia (Organizer) & Kurasi DPM (Super Admin)

### 5.1 Buat Draf Acara Baru (Panitia)
- **URL:** `POST /api/v1/organizer/events`
- **Akses:** Panitia (`ORGANIZER`), Superadmin (`SUPERADMIN`)
- **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json`

**Request Body (Acara Peserta):**
```json
{
  "title": "Seminar Nasional Cyber Security 2026",
  "category_id": 1,
  "description": "Membahas keamanan siber dan perlindungan data pribadi di era AI.",
  "activity_type": "PARTICIPANT_EVENT",
  "poster_url": "https://storage.uii-event.id/posters/cyber.jpg",
  "quota_total": 150,
  "start_time": "2026-12-10T08:30:00Z",
  "end_time": "2026-12-10T12:00:00Z",
  "location": "Auditorium KH. Abdurrahman Wahid FTI UII",
  "auto_post_ig": true
}
```

**Request Body (Acara Open Recruitment Panitia):**
```json
{
  "title": "Oprec Panitia Pensi Kampus 2026",
  "category_id": 5,
  "description": "Perekrutan panitia pentas seni akbar tahunan UII.",
  "activity_type": "COMMITTEE_RECRUITMENT",
  "poster_url": "https://storage.uii-event.id/posters/pensi.jpg",
  "quota_total": 80,
  "start_time": "2026-12-15T08:00:00Z",
  "end_time": "2026-12-15T16:00:00Z",
  "location": "Student Center Kampus Terpadu UII",
  "divisions": [
    { "name": "Divisi Acara", "quota": 25, "description": "Konsep & rundown" },
    { "name": "Divisi Humas", "quota": 20, "description": "Kemitraan eksternal" }
  ]
}
```

**Response Sukses (HTTP 201 Created):**
```json
{
  "status": "success",
  "message": "Draf kegiatan berhasil dibuat. Silakan lengkapi data sebelum diajukan ke DPM.",
  "data": {
    "id": "event-uuid",
    "title": "Seminar Nasional Cyber Security 2026",
    "status": "DRAFT",
    "quota_total": 150,
    "quota_remaining": 150
  }
}
```

---

### 5.2 Ajukan Draf Kegiatan ke DPM untuk Disetujui (Panitia)
- **URL:** `POST /api/v1/organizer/events/{id}/submit`
- **Akses:** Panitia pemilik acara (`ORGANIZER`)
- **Headers:** `Authorization: Bearer <token>`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Kegiatan berhasil diajukan ke DPM untuk diverifikasi dan disetujui.",
  "data": {
    "id": "event-uuid",
    "status": "PENDING_APPROVAL"
  }
}
```

---

### 5.3 Persetujuan Acara Kampus (Super Admin DPM)
- **URL:** `POST /api/v1/admin/events/{id}/approve`
- **Akses:** Super Admin (`SUPERADMIN`)
- **Headers:** `Authorization: Bearer <token>`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Kegiatan resmi disetujui (APPROVED) dan kini telah terpublikasi untuk mahasiswa.",
  "data": {
    "id": "event-uuid",
    "status": "PUBLISHED"
  }
}
```

---

### 5.4 Penolakan Pengajuan Acara (Super Admin DPM)
- **URL:** `POST /api/v1/admin/events/{id}/reject`
- **Akses:** Super Admin (`SUPERADMIN`)
- **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json`

**Request Body:**
```json
{
  "rejection_reason": "Format poster tidak sesuai panduan visual resmi kampus dan surat izin ruangan belum dilampirkan."
}
```

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Pengajuan kegiatan telah ditolak. Catatan perbaikan telah dikirim ke panitia.",
  "data": {
    "id": "event-uuid",
    "status": "REJECTED"
  }
}
```
*(Catatan: Panitia dapat merevisi dan mengajukan kembali kegiatan yang berstatus `REJECTED`).*
