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

---

## 6. Transaksi Pendaftaran Peserta & E-Tiket QR (Fitur 05)

### 6.1 Pendaftaran Peserta Kegiatan
Mendaftarkan mahasiswa ke dalam kegiatan berstatus `PUBLISHED`. Menggunakan transaksi database dengan penguncian baris (`lockForUpdate`) untuk integritas kuota dan indeks unik parsial PostgreSQL untuk pencegahan double registration.
- **URL:** `POST /api/v1/events/{id}/register`
- **Akses:** Mahasiswa Terautentikasi (`STUDENT`)
- **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json`

**Request Body (Opsional):**
```json
{
  "custom_answers": {
    "dietary_preference": "Vegetarian",
    "tshirt_size": "XL",
    "info_source": "Instagram BSI UII"
  }
}
```

**Response Sukses (HTTP 201 Created):**
```json
{
  "status": "success",
  "message": "Pendaftaran kegiatan berhasil dikonfirmasi. E-Tiket ber-QR Code telah diterbitkan.",
  "data": {
    "id": "ticket-uuid",
    "ticket_code": "TKT-2026-X9K2PL8M",
    "qr_payload": "eyJ0aWNrZXRfY29kZSI6IlRLVC0yMDI2LVg5SzJQTDhNIiwicmVnX2lkIjoi...",
    "is_checked_in": false,
    "checked_in_at": null,
    "event": {
      "id": "event-uuid",
      "title": "Seminar Nasional AI & Karir Teknologi 2026",
      "slug": "seminar-nasional-ai-2026",
      "start_time": "2026-12-10T08:00:00Z",
      "end_time": "2026-12-10T12:00:00Z",
      "location": "Auditorium FTI Kampus Terpadu UII"
    },
    "registration": {
      "id": "registration-uuid",
      "registration_type": "PARTICIPANT",
      "status": "REGISTERED",
      "registered_at": "2026-11-19T06:22:55Z",
      "custom_answers": {
        "dietary_preference": "Vegetarian"
      }
    }
  },
  "meta": {
    "timestamp": "2026-11-19T06:22:55Z",
    "version": "v1.0"
  }
}
```

**Response Error Double Entry (HTTP 409 Conflict):**
```json
{
  "status": "error",
  "error_code": "ERR_ALREADY_REGISTERED",
  "message": "Anda sudah terdaftar pada kegiatan ini.",
  "errors": []
}
```

---

### 6.2 Daftar E-Tiket Aktif Mahasiswa
Mengambil seluruh tiket kegiatan aktif yang dimiliki oleh mahasiswa yang sedang login.
- **URL:** `GET /api/v1/my-tickets?page=1&limit=15`
- **Akses:** Mahasiswa Terautentikasi (`STUDENT`)
- **Headers:** `Authorization: Bearer <token>`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Daftar e-tiket aktif berhasil dimuat.",
  "data": [
    {
      "id": "ticket-uuid",
      "ticket_code": "TKT-2026-X9K2PL8M",
      "qr_payload": "eyJ0aWNrZXRfY29kZSI6IlRLVC0yMDI2LVg5SzJQTDhNIiwicmVnX2lkIjoi...",
      "is_checked_in": false,
      "event": {
        "id": "event-uuid",
        "title": "Seminar Nasional AI & Karir Teknologi 2026"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

### 6.3 Detail E-Tiket & Verifikasi QR
Mengambil spesifikasi tiket tunggal beserta tanda tangan kriptografi QR untuk ditampilkan pada aplikasi mobile atau dicetak.
- **URL:** `GET /api/v1/my-tickets/{ticket_code}`
- **Akses:** Mahasiswa Pemilik Tiket (`STUDENT`)
- **Headers:** `Authorization: Bearer <token>`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Detail e-tiket berhasil diverifikasi dan dimuat.",
  "data": {
    "id": "ticket-uuid",
    "ticket_code": "TKT-2026-X9K2PL8M",
    "qr_payload": "eyJ0aWNrZXRfY29kZSI6IlRLVC0yMDI2LVg5SzJQTDhNIiwicmVnX2lkIjoi...",
    "is_checked_in": false,
    "checked_in_at": null,
    "event": { ... }
  }
}
```

---

### 6.4 Pembatalan Pendaftaran & Pemulihan Kuota
Membatalkan tiket kegiatan. Status pendaftaran diubah menjadi `CANCELLED`, e-tiket dinonaktifkan, dan sisa kuota kegiatan otomatis dipulihkan (+1). Mahasiswa diperkenankan mendaftar kembali di kemudian hari jika kuota masih tersedia.
- **URL:** `POST /api/v1/my-tickets/{ticket_code}/cancel`
- **Akses:** Mahasiswa Pemilik Tiket (`STUDENT`)
- **Headers:** `Authorization: Bearer <token>`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Pendaftaran kegiatan berhasil dibatalkan. Kuota tiket telah dikembalikan ke sistem.",
  "data": {
    "ticket_code": "TKT-2026-X9K2PL8M",
    "status": "CANCELLED"
  }
}
```

---

### 6.5 Daftar Peserta Terdaftar (Akses Panitia)
Panitia penyelenggara dapat mengunduh atau memeriksa daftar lengkap peserta yang terdaftar pada kegiatan miliknya. Dilindungi EventPolicy IDOR.
- **URL:** `GET /api/v1/organizer/events/{id}/registrations?status=REGISTERED`
- **Akses:** Panitia Penyelenggara Pemilik Acara (`ORGANIZER`)
- **Headers:** `Authorization: Bearer <token>`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Daftar pendaftar kegiatan berhasil dimuat.",
  "data": [
    {
      "id": "registration-uuid",
      "registration_type": "PARTICIPANT",
      "status": "REGISTERED",
      "registered_at": "2026-11-19T06:22:55Z",
      "user": {
        "id": "user-uuid",
        "name": "Ahmad Fauzan",
        "nim": "21523001",
        "email": "21523001@students.uii.ac.id",
        "faculty": "Fakultas Teknologi Industri"
      },
      "ticket": {
        "ticket_code": "TKT-2026-X9K2PL8M",
        "is_checked_in": false
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

---

## 7. Jalur Oprec Panitia (Committee Recruitment) & Dashboard Seleksi (Fitur 06)

### 7.1 Pendaftaran Open Recruitment Panitia
Mendaftarkan mahasiswa ke dalam kegiatan jalur kepanitiaan (`activity_type = 'COMMITTEE_RECRUITMENT'`). Wajib mengunggah berkas CV format PDF asli (tervalidasi header magic bytes `%PDF` untuk mencegah *file spoofing* atau unggahan skrip berbahaya).
- **URL:** `POST /api/v1/events/{id}/apply`
- **Akses:** Mahasiswa Terautentikasi (`STUDENT`)
- **Headers:** `Authorization: Bearer <token>`, `Content-Type: multipart/form-data`

**Request Body (Multipart Form):**
- `division_pref_1` (string, required) — Nama divisi pilihan utama (harus terdaftar pada divisi kegiatan).
- `division_pref_2` (string, optional) — Nama divisi pilihan kedua (harus berbeda dari pilihan utama).
- `cv_file` (file binary, required) — Berkas CV format PDF asli (maksimal 5MB, header `%PDF`).
- `custom_answers[motivation]` (string, optional) — Alasan/motivasi mendaftar.
- `custom_answers[portfolio_url]` (string, optional) — Tautan portofolio GitHub/LinkedIn.

**Response Sukses (HTTP 201 Created):**
```json
{
  "status": "success",
  "message": "Pendaftaran open recruitment panitia berhasil dikirim.",
  "data": {
    "id": "registration-uuid",
    "registration_type": "COMMITTEE",
    "division_pref_1": "Divisi Acara & Konsep",
    "division_pref_2": "Divisi Media Kreatif",
    "has_cv": true,
    "cv_file_url": "recruitment_docs/event-uuid/unique-file-id.pdf",
    "selection_status": "SUBMITTED",
    "status": "REGISTERED",
    "registered_at": "2026-11-19T06:33:14Z",
    "custom_answers": {
      "motivation": "Ingin mengembangkan soft skill kepemimpinan.",
      "portfolio_url": "https://linkedin.com/in/fauzan-student"
    },
    "user": {
      "id": "user-uuid",
      "name": "Ahmad Fauzan",
      "nim": "21523001",
      "email": "21523001@students.uii.ac.id"
    }
  }
}
```

---

### 7.2 Riwayat Lamaran Kepanitiaan Mahasiswa
Mengambil seluruh pendaftaran kepanitiaan yang diajukan oleh mahasiswa yang sedang login beserta status seleksi terkini.
- **URL:** `GET /api/v1/my-applications?page=1&limit=15`
- **Akses:** Mahasiswa Terautentikasi (`STUDENT`)
- **Headers:** `Authorization: Bearer <token>`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Daftar riwayat pendaftaran open recruitment berhasil dimuat.",
  "data": [
    {
      "id": "registration-uuid",
      "registration_type": "COMMITTEE",
      "division_pref_1": "Divisi Acara & Konsep",
      "division_pref_2": "Divisi Media Kreatif",
      "selection_status": "INTERVIEW_SCHEDULED",
      "registered_at": "2026-11-19T06:33:14Z",
      "custom_answers": {
        "selection_notes": "Wawancara dijadwalkan hari Sabtu, 28 Nov via Zoom."
      },
      "event": {
        "id": "event-uuid",
        "title": "Open Recruitment Panitia Ospek Kampus & Welcoming Day 2026",
        "slug": "oprec-panitia-ospek-2026",
        "activity_type": "COMMITTEE_RECRUITMENT",
        "status": "PUBLISHED"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

---

### 7.3 Dashboard Calon Anggota Panitia (Akses Panitia Penyelenggara)
Panitia pemilik acara dapat meninjau, menyaring, dan mencari pelamar oprec berdasarkan divisi dan status seleksi. Dilindungi IDOR.
- **URL:** `GET /api/v1/organizer/events/{id}/applicants?division=Divisi Acara & Konsep&status=SUBMITTED&search=Fauzan`
- **Akses:** Panitia Penyelenggara Acara (`ORGANIZER`)
- **Headers:** `Authorization: Bearer <token>`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Daftar calon panitia (pendaftar oprec) berhasil dimuat.",
  "data": [
    {
      "id": "registration-uuid",
      "registration_type": "COMMITTEE",
      "division_pref_1": "Divisi Acara & Konsep",
      "division_pref_2": "Divisi Media Kreatif",
      "has_cv": true,
      "selection_status": "SUBMITTED",
      "user": {
        "id": "user-uuid",
        "name": "Ahmad Fauzan",
        "nim": "21523001",
        "faculty": "Fakultas Teknologi Industri"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

---

### 7.4 Unduh Dokumen CV Pelamar Secara Aman
Mengalirkan (*streaming*) file PDF CV pelamar. Hanya dapat diakses oleh mahasiswa pelamar itu sendiri atau panitia pemilik kegiatan (terproteksi IDOR).
- **URL:** `GET /api/v1/registrations/{id}/cv`
- **Akses:** Mahasiswa Pemilik Berkas (`STUDENT`) atau Panitia Pemilik Acara (`ORGANIZER`)
- **Headers:** `Authorization: Bearer <token>`

**Response Sukses (HTTP 200 OK):**
- `Content-Type: application/pdf`
- `Content-Disposition: inline; filename="CV_{user_id}.pdf"`
- Payload berkas biner PDF.

---

### 7.5 Pembaruan Status Seleksi Pelamar (Panitia Penyelenggara)
Panitia dapat memproses tahapan seleksi calon anggota panitia dari tahap pendaftaran hingga diterima.
- **URL:** `PATCH /api/v1/organizer/registrations/{id}/selection`
- **Akses:** Panitia Penyelenggara Acara (`ORGANIZER`)
- **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json`

**Request Body:**
```json
{
  "selection_status": "INTERVIEW_SCHEDULED",
  "notes": "Wawancara dijadwalkan hari Sabtu, 28 Nov via Zoom. Link meeting telah dikirim."
}
```
*(Nilai valid untuk `selection_status`: `SUBMITTED`, `INTERVIEW_SCHEDULED`, `ACCEPTED`, `REJECTED`)*

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Status seleksi pendaftar berhasil diperbarui menjadi INTERVIEW_SCHEDULED.",
  "data": {
    "id": "registration-uuid",
    "selection_status": "INTERVIEW_SCHEDULED",
    "custom_answers": {
      "selection_notes": "Wawancara dijadwalkan hari Sabtu, 28 Nov via Zoom. Link meeting telah dikirim."
    }
  }
}
```

---

## 8. Presensi QR Hari-H & Penerbitan E-Sertifikat Otomatis (Fitur 07)

### 8.1 Pemindaian Presensi QR Code Hari-H (Scanner Panitia)
Panitia memindai QR Code e-tiket peserta di lokasi acara. Memvalidasi tanda tangan kriptografi HMAC-SHA256 dengan perbandingan aman (*timing-safe* `hash_equals()`). Saat presensi sah, status pendaftaran otomatis berubah menjadi `ATTENDED` dan sistem secara instan menerbitkan E-Sertifikat resmi ber-hash unik.
- **URL:** `POST /api/v1/attendance/check-in`
- **Akses:** Panitia Penyelenggara Pemilik Acara (`ORGANIZER`) atau Super Admin (`SUPERADMIN`)
- **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json`
- **Rate Limit:** 120 scans/menit (`throttle:scanner`)

**Request Body (Salah Satu Wajib Diisi):**
```json
{
  "qr_payload": "eyJ0aWNrZXRfY29kZSI6IlRLVC0yMDI2LUFaOThLUDdNIiwidXNlcl9pZCI6Ii4uLiJ9"
}
```
*(Fallback manual: dapat mengirimkan `"ticket_code": "TKT-2026-AZ98KP7M"` jika lensa kamera terkendala).*

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Presensi kehadiran hari-H berhasil dikonfirmasi. E-Sertifikat otomatis diterbitkan untuk peserta.",
  "data": {
    "ticket_code": "TKT-2026-AZ98KP7M",
    "is_checked_in": true,
    "checked_in_at": "2026-11-19T07:03:49Z",
    "checked_in_by": {
      "id": "organizer-uuid",
      "name": "Sarah Nabila (LEM FTI UII)"
    },
    "attendee": {
      "id": "student-uuid",
      "name": "Ahmad Fauzan",
      "nim": "21523001",
      "email": "21523001@students.uii.ac.id",
      "faculty": "Fakultas Teknologi Industri"
    },
    "event": {
      "id": "event-uuid",
      "title": "Seminar Nasional AI & Karir Teknologi 2026",
      "slug": "seminar-nasional-ai-2026"
    },
    "certificate": {
      "certificate_number": "CERT/UII/2026/JWJF5TGH",
      "verify_hash": "2a29b41732f25c45e03b7b8a9c1f6d3e...",
      "verify_url": "http://localhost:8000/api/v1/certificates/verify/2a29b41732f25c45e03b7b8a9c1f6d3e...",
      "issued_at": "2026-11-19T07:03:49Z",
      "status": "VALID"
    }
  }
}
```

**Response Error Double Check-in (HTTP 409 Conflict):**
```json
{
  "status": "error",
  "error_code": "ERR_ALREADY_CHECKED_IN",
  "message": "Peringatan: Tiket TKT-2026-AZ98KP7M telah dipindai sebelumnya pada 19 Nov 2026 14:03:49 WIB.",
  "errors": []
}
```

**Response Error QR Signature Tampered (HTTP 400 Bad Request):**
```json
{
  "status": "error",
  "error_code": "ERR_INVALID_QR",
  "message": "Tanda tangan digital QR Code tidak valid. Tiket dicurigai palsu atau telah dimanipulasi.",
  "errors": []
}
```

---

### 8.2 Rekapitulasi Presensi & Metrik Kehadiran Kegiatan (Akses Panitia)
Menampilkan daftar kehadiran peserta secara terperinci beserta kalkulasi otomatis statistik kehadiran (*total registered*, *total attended*, *attendance rate percentage*).
- **URL:** `GET /api/v1/organizer/events/{id}/attendance?is_checked_in=true&search=Fauzan`
- **Akses:** Panitia Penyelenggara Pemilik Acara (`ORGANIZER`)
- **Headers:** `Authorization: Bearer <token>`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Data rekapitulasi presensi kegiatan berhasil dimuat.",
  "data": [
    {
      "ticket_code": "TKT-2026-AZ98KP7M",
      "is_checked_in": true,
      "checked_in_at": "2026-11-19T07:03:49Z",
      "attendee": {
        "id": "student-uuid",
        "name": "Ahmad Fauzan",
        "nim": "21523001"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "statistics": {
      "total_registered": 100,
      "total_attended": 85,
      "total_absent": 15,
      "attendance_rate_percent": 85.0
    }
  }
}
```

---

### 8.3 Verifikasi Publik Keaslian E-Sertifikat (Tanpa Login)
Halaman verifikasi terbuka (*public verification*) yang dapat diakses siapa saja untuk mengonfirmasi keaslian sertifikat yang diterbitkan oleh sistem UII Event Management Platform.
- **URL:** `GET /api/v1/certificates/verify/{hash}`
- **Akses:** Publik (Terbuka / Unauthenticated)
- **Headers:** `Accept: application/json`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Sertifikat resmi terverifikasi dan dinyatakan VALID oleh Universitas Islam Indonesia.",
  "data": {
    "id": "certificate-uuid",
    "certificate_number": "CERT/UII/2026/JWJF5TGH",
    "verify_hash": "2a29b41732f25c45e03b7b8a9c1f6d3e...",
    "issued_at": "2026-11-19T07:03:49Z",
    "status": "VALID",
    "recipient": {
      "name": "Ahmad Fauzan",
      "nim": "21523001",
      "faculty": "Fakultas Teknologi Industri",
      "study_program": "Informatika"
    },
    "event": {
      "title": "Seminar Nasional AI & Karir Teknologi 2026",
      "category": "Seminar",
      "start_time": "2026-12-10T08:00:00Z"
    }
  }
}
```

**Response Sertifikat Tidak Ditemukan (HTTP 404 Not Found):**
```json
{
  "status": "error",
  "error_code": "ERR_NOT_FOUND",
  "message": "Sertifikat dengan kode verifikasi tersebut tidak ditemukan atau tidak valid.",
  "errors": []
}
```

---

### 8.4 Daftar E-Sertifikat Mahasiswa
Mengambil seluruh e-sertifikat yang diperoleh mahasiswa dari kegiatan yang telah dihadirinya.
- **URL:** `GET /api/v1/my-certificates?page=1&limit=15`
- **Akses:** Mahasiswa Terautentikasi (`STUDENT`)
- **Headers:** `Authorization: Bearer <token>`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Daftar e-sertifikat kegiatan berhasil dimuat.",
  "data": [
    {
      "id": "certificate-uuid",
      "certificate_number": "CERT/UII/2026/JWJF5TGH",
      "verify_hash": "2a29b41732f25c45e03b7b8a9c1f6d3e...",
      "verify_url": "http://localhost:8000/api/v1/certificates/verify/2a29b41732f25c45e03b7b8a9c1f6d3e...",
      "issued_at": "2026-11-19T07:03:49Z",
      "status": "VALID",
      "event": {
        "title": "Seminar Nasional AI & Karir Teknologi 2026"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

---

## 9. Otomasi Notifikasi & Pengingat (WhatsApp & Email)

Modul notifikasi dirancang dengan prinsip **High Resiliency (Ketahanan Tinggi)** dan **Non-Blocking Architecture**:
1. **Fallback Resilient Otomatis:** Sistem memprioritaskan WhatsApp Gateway (`services.whatsapp`). Jika gateway down, token belum diatur, terjadi timeout, atau mengembalikan error non-2xx, pengiriman **otomatis beralih ke SMTP Email Kampus UII** dalam alur yang sama tanpa kehilangan pesan.
2. **Proteksi Idempotency:** Tabel `notification_logs` memantau riwayat pengiriman unik berdasarkan tipe pesan (`TICKET_ISSUED`, `REMINDER_H1`, `REMINDER_H3`). Worker yang me-restart antrean tidak akan mengirimkan pesan ganda ke user.
3. **Queue Jobs & Retry Backoff:** Pengiriman tiket dan reminder dijalankan di antrean latar belakang (`SendTicketNotificationJob`, `SendEventReminderJob`) dengan `tries = 3`, `backoff = [10, 60, 300]`, dan batas timeout 30 detik.
4. **Artisan Scheduler:** Perintah `php artisan events:send-reminders` dijalankan secara berkala untuk memindai kegiatan H-1 dan H-3 Jam.

---

### 9.1 Riwayat & Ringkasan Log Notifikasi Acara
Mengambil seluruh log riwayat pengiriman pesan (WhatsApp dan Email) beserta metrik agregat keberhasilan untuk suatu kegiatan.
- **URL:** `GET /api/v1/organizer/events/{id}/notifications?page=1&per_page=15&channel=WHATSAPP&status=SENT`
- **Akses:** Panitia Penyelenggara Kegiatan atau Super Admin (`ORGANIZER`, `SUPERADMIN`)
- **Headers:** `Authorization: Bearer <token>`
- **Query Parameters (Opsional):**
  - `channel`: `WHATSAPP` | `EMAIL`
  - `status`: `SENT` | `FAILED` | `PENDING`
  - `per_page`: Default `15`, Maksimal `50`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Log riwayat notifikasi kegiatan berhasil diambil.",
  "data": {
    "summary": {
      "total_notifications": 48,
      "sent_count": 46,
      "failed_count": 2,
      "whatsapp_count": 30,
      "email_count": 18
    },
    "notifications": [
      {
        "id": 1,
        "event_id": "event-uuid",
        "registration_id": "registration-uuid",
        "participant_name": "Fauzan Mahasiswa UII",
        "channel": "EMAIL",
        "recipient": "21523001@students.uii.ac.id",
        "delivery_status": "SENT",
        "payload": {
          "type": "TICKET_ISSUED",
          "notes": "Fallback otomatis dari WhatsApp ke SMTP Email Kampus UII"
        },
        "created_at": "2026-11-19T07:25:03+00:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 4,
      "per_page": 15,
      "total": 48
    }
  },
  "meta": {
    "timestamp": "2026-11-19T07:25:03Z",
    "version": "v1.0"
  }
}
```

---

### 9.2 Broadcast Pengingat Peserta Manual
Memungkinkan panitia penyelenggara menjadwalkan pengiriman pengingat acara ke seluruh peserta yang terdaftar aktif secara serentak melalui sistem antrean.
- **URL:** `POST /api/v1/organizer/events/{id}/reminders`
- **Akses:** Panitia Penyelenggara Kegiatan atau Super Admin (`ORGANIZER`, `SUPERADMIN`)
- **Headers:** `Authorization: Bearer <token>`
- **Request Body (JSON):**
```json
{
  "reminder_type": "REMINDER_H1"
}
```
*Catatan `reminder_type`:* `REMINDER_H1` (Pengingat H-1), `REMINDER_H3` (Pengingat H-3 Jam), atau `REMINDER_MANUAL`.

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Pengingat berhasil dijadwalkan ke dalam antrean untuk 45 peserta terdaftar.",
  "data": {
    "event_id": "event-uuid",
    "reminder_type": "REMINDER_H1",
    "dispatched_count": 45
  },
  "meta": {
    "timestamp": "2026-11-19T07:25:03Z",
    "version": "v1.0"
  }
}
```

---

## 10. Integrasi Auto-Post Instagram (Meta Graph API) & Rekap Dashboard

Modul ini melengkapi siklus hidup acara dengan otomatisasi publikasi media sosial dan pelaporan menyeluruh:
1. **Auto-Post Instagram (Meta Graph API 2-Step Publishing):**
   - Saat kegiatan resmi disetujui (APPROVED) oleh Super Admin (DPM), sistem secara otomatis memasukkan job `PostToInstagramJob` ke dalam antrean background melalui `DB::afterCommit(...)`.
   - Menggunakan alur resmi 2 tahap Meta Graph API:
     - Tahap 1: Pembuatan kontainer media (`POST /{ig-account-id}/media`) dengan `image_url` poster dan takarir (*caption*) terformat lengkap.
     - Tahap 2: Publikasi kontainer ke *feed* Instagram (`POST /{ig-account-id}/media_publish`).
   - Terdapat proteksi **Idempotency** di tabel `notification_logs` sehingga tidak terjadi duplikasi unggahan postingan.
2. **Rekapitulasi Komprehensif Dashboard:**
   - Menyajikan metrik multi-dimensi dalam 1 kali panggilan API: metrik peserta (total, hadir, persen kehadiran), metrik oprec (pelamar, status seleksi), metrik e-sertifikat, dan status pengiriman notifikasi/Instagram.
3. **Ekspor Laporan CSV Ber-BOM UTF-8:**
   - Berkas CSV dihasilkan langsung dalam memori stream (`php://temp`) lengkap dengan byte header UTF-8 BOM (`\xEF\xBB\xBF`) agar karakter bahasa Indonesia dan aksen langsung rapi saat dibuka di Microsoft Excel.

---

### 10.1 Rekapitulasi Lengkap Dashboard Panitia
Mengambil ringkasan metrik statistik acara untuk panitia penyelenggara dan Super Admin.
- **URL:** `GET /api/v1/organizer/events/{id}/recap`
- **Akses:** Panitia Penyelenggara Kegiatan atau Super Admin (`ORGANIZER`, `SUPERADMIN`)
- **Headers:** `Authorization: Bearer <token>`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Rekapitulasi lengkap kegiatan kampus berhasil dimuat.",
  "data": {
    "event": {
      "id": "event-uuid",
      "title": "Seminar Nasional AI & Karir Teknologi 2026",
      "slug": "seminar-nasional-ai-2026",
      "status": "PUBLISHED",
      "category": "Seminar",
      "organizer": "Sarah Nabila (LEM FTI UII)",
      "start_time": "2026-12-10T08:00:00+00:00",
      "end_time": "2026-12-10T12:00:00+00:00",
      "location": "Auditorium FTI UII",
      "quota_total": 250,
      "quota_remaining": 42
    },
    "participants": {
      "total_registered": 208,
      "attended_count": 185,
      "not_attended_count": 23,
      "attendance_rate_percent": 88.9,
      "cancelled_count": 6
    },
    "recruitment": null,
    "certificates": {
      "total_issued": 185
    },
    "notifications": {
      "total_logs": 220,
      "whatsapp_sent": 190,
      "whatsapp_failed": 18,
      "email_sent": 208,
      "email_failed": 0,
      "instagram_sent": 1,
      "instagram_failed": 0
    }
  },
  "meta": {
    "timestamp": "2026-11-19T07:36:18Z",
    "version": "v1.0"
  }
}
```

---

### 10.2 Ekspor Rekap Peserta & Kehadiran (CSV)
Mengunduh berkas tabular CSV berisi data seluruh peserta terdaftar, status presensi hari-H, waktu check-in, dan nomor e-sertifikat resmi.
- **URL:** `GET /api/v1/organizer/events/{id}/export/participants`
- **Akses:** Panitia Penyelenggara Kegiatan atau Super Admin (`ORGANIZER`, `SUPERADMIN`)
- **Headers:** `Authorization: Bearer <token>`
- **Response Header:**
  - `Content-Type: text/csv; charset=UTF-8`
  - `Content-Disposition: attachment; filename="rekap-peserta-{slug}.csv"`

---

### 10.3 Ekspor Rekap Pelamar Oprec Panitia (CSV)
Mengunduh berkas CSV daftar pelamar panitia (*committee recruitment*), divisi pilihan utama & cadangan, catatan seleksi, dan status kelulusan.
- **URL:** `GET /api/v1/organizer/events/{id}/export/applicants`
- **Akses:** Panitia Penyelenggara Kegiatan atau Super Admin (`ORGANIZER`, `SUPERADMIN`)
- **Headers:** `Authorization: Bearer <token>`
- **Response Header:**
  - `Content-Type: text/csv; charset=UTF-8`
  - `Content-Disposition: attachment; filename="rekap-pelamar-{slug}.csv"`

---

### 10.4 Auto-Post Poster ke Instagram Resmi Kampus
Memicu pengiriman manual poster dan deskripsi acara resmi ke Instagram kampus melalui Meta Graph API.
- **URL:** `POST /api/v1/organizer/events/{id}/publish-instagram`
- **Akses:** Panitia Penyelenggara Kegiatan atau Super Admin (`ORGANIZER`, `SUPERADMIN`)
- **Headers:** `Authorization: Bearer <token>`

**Response Sukses (HTTP 200 OK):**
```json
{
  "status": "success",
  "message": "Poster kegiatan berhasil dipublikasikan secara otomatis ke Instagram Resmi Kampus.",
  "data": {
    "status": "SENT",
    "post_id": "17923482910384729"
  },
  "meta": {
    "timestamp": "2026-11-19T07:36:18Z",
    "version": "v1.0"
  }
}
```

```
