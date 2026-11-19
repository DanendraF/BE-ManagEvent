# MASTER AI KNOWLEDGE BASE & CONTEXT INJECTION (UII EVENT MANAGEMENT)
> **Dokumen Panduan Konteks AI untuk Tim Pengembang 5 Orang (UI/UX, Backend, Frontend, QA, dan System Analyst)**  
> **Versi Dokumen:** 1.0 (Master Release) &bull; **Tanggal Terbit:** 17 November 2026 &bull; **Klasifikasi:** Internal Engineering Prompt Context  
> **Lokasi Proyek:** `D:\AntigravityProjects\uii-event-management`

---

> **🔧 Pembaruan Stack (19 Nov 2026):** Backend = **Laravel + PostgreSQL**, Frontend = **HTML/CSS/JS vanilla** (fallback Blade/Vue). Kode contoh Node/Prisma/Axios/React/Vue di versi sebelumnya sudah diganti. Backend dev: baca juga `05`–`09`. Dokumen tech belum final.

## 📌 CARA PENGGUNAAN FILE INI OLEH ANGGOTA TIM

File Markdown ini dirancang khusus oleh **System Analyst** agar setiap anggota tim kamu (UI/UX, Backend, Frontend, QA) dapat langsung menyalin (*copy-paste*) bagian mereka ke dalam asisten AI masing-masing (seperti ChatGPT, Claude, Cursor, GitHub Copilot, Gemini, atau Antigravity).

*   **Rekan UI/UX Designer:** Salin **Bagian 1** + **Bagian 2** ke AI untuk membuat prompt wireframe, sistem desain Figma, dan tata letak layar.
*   **Rekan Backend Developer:** Salin **Bagian 1** + **Bagian 3** ke AI untuk menghasilkan kode migrasi database (DDL / Laravel migration), middleware auth, dan endpoint REST API. Detail lengkap: dokumen `05`–`09`.
*   **Rekan Frontend Developer:** Salin **Bagian 1** + **Bagian 4** ke AI untuk membuat *routing*, komponen halaman, penanganan validasi form, dan modul kamera web scanner.
*   **Rekan QA Engineer:** Salin **Bagian 1** + **Bagian 5** ke AI untuk membuat skrip otomatisasi uji E2E (Playwright), skrip uji beban k6, dan koleksi Postman.

---

# BAGIAN 1: GLOBAL PROJECT CONTEXT & CORE BUSINESS RULES
*(Wajib dibaca dan disertakan oleh seluruh anggota tim ke AI masing-masing)*

### 1.1 Visi & Identitas Proyek
*   **Nama Proyek:** UII Event Management Platform (Sistem Manajemen Kegiatan Mahasiswa Terpadu).
*   **Institusi:** Universitas Islam Indonesia (UII) &bull; Lembaga Eksekutif Mahasiswa (LEM) & Dewan Perwakilan Mahasiswa (DPM).
*   **Masalah Utama yang Diselesaikan:**
    1.  *Pendaftaran Manual:* Menggantikan Google Form manual dengan portal terpusat ber-auto-fill data NIM mahasiswa.
    2.  *Double Entry:* Menolak secara mutlak submisi ganda dari NIM yang sama pada event yang sama (Error 409 Conflict).
    3.  *Poster Tersebar:* Menyatukan info di satu katalog terkurasi + **Auto-Post Poster ke Akun Instagram Resmi `@uii.events` via Meta Graph API**.
    4.  *Ketidakhadiran (No-Show):* Otomasi pengingat jadwal via **WhatsApp Gateway (Fonnte/Wablas)** pada H-1 dan H-3 jam sebelum acara.
    5.  *Presensi Lambat & Sertifikat Hilang:* Presensi cepat via Web Scanner Kamera (&le; 2 detik) dan pembukaan otomatis hak unduh E-Sertifikat terverifikasi SKPI.

### 1.2 Aturan Bisnis Inti (Non-Negotiable Business Rules)
1.  **Dual-Track Mode:** Sistem mendukung 2 jenis kegiatan: `PARTICIPANT_EVENT` (Seminar/Workshop/Konser) yang menghasilkan tiket QR, dan `COMMITTEE_RECRUITMENT` (Oprec Panitia) yang memuat pilihan divisi dan unggah berkas CV/KRS.
2.  **Anti-Double Entry Guard:** Kombinasi `(user_id, event_id)` wajib unik. Jika mahasiswa sudah memiliki pendaftaran berstatus aktif, sistem wajib menolak pendaftaran ulang dengan status `HTTP 409 Conflict`.
3.  **Atomic Quota Locking:** Pengurangan kuota kursi wajib dieksekusi secara atomik (`SELECT FOR UPDATE` atau decrement transaksional) agar tidak terjadi *race condition* atau kuota minus saat diperebutkan bersamaan.
4.  **HMAC Encrypted QR Code:** Barcode tiket wajib memuat tanda tangan kriptografi HMAC-SHA256 agar tidak bisa dipalsukan dengan barcode generator gratisan.
5.  **Single Scan Only:** Tiket yang telah berstatus `is_checked_in = TRUE` wajib ditolak jika di-scan ulang di meja registrasi (menampilkan layar MERAH dan bunyi alarm peringatan).
6.  **Attendance-Gated Certificate:** Tombol unduh E-Sertifikat **hanya boleh aktif** jika status kehadiran peserta bernilai `ATTENDED`.
7.  **Fallback Notification:** Jika pengiriman WhatsApp Gateway gagal/timeout, sistem secara otomatis mengalihkan pengiriman ke server SMTP Email Kampus UII.

---

# BAGIAN 2: MODUL SPESIALISASI UI/UX DESIGNER (FOR UI/UX AI)
*(Konteks khusus untuk rekan UI/UX dan asisten AI desainer)*

### 2.1 System Prompt untuk AI UI/UX
> *"Bertindaklah sebagai Senior Product Designer / Lead UI/UX. Desainlah antarmuka web responsif berstandar modern untuk UII Event Management Platform. Terapkan pendekatan Mobile-First untuk modul mahasiswa pendaftar (resolusi 360px–430px) dan Desktop-First untuk dashboard panitia dan admin kemahasiswaan."*

### 2.2 Design System Tokens & Style Guide
*   **Primary Color:** Deep Navy `#0F2C59` (Karakter resmi kampus, wibawa, terpercaya).
*   **Secondary / Brand Color:** Royal Cyan `#0284C7` / `#0066CC` (Elemen interaktif, link, tombol utama).
*   **Accent Color:** Deep Purple `#7C3AED` (Tagar, banner unggulan, indikator khusus).
*   **Success Color:** Emerald Green `#16A34A` (Status Hadir, Sukses, Tiket Valid).
*   **Danger / Warning:** Crimson Red `#DC2626` (Tiket Duplikat, Peringatan) & Amber `#D97706` (Waiting List).
*   **Neutral Palette:** Slate White `#F8FAFC`, Border `#CBD5E1`, Dark Slate `#1E293B`.
*   **Typography:** Inter / Segoe UI / System-UI (Clean, sans-serif, terbaca jelas di layar HP).

### 2.3 Inventaris 15 Layar Wajib (Screen Inventory)
1.  `SCR-PUB-01`: **Katalog Event Terpusat (Home)** &bull; Header, search bar real-time, filter chips (Seminar, Workshop, Lomba, Konser, Oprec), banner carousel, grid kartu event (poster, sisa kuota, badge LEM/UKM).
2.  `SCR-PUB-02`: **Detail Event & Poster HD** &bull; Preview poster fullscreen, metadata tanggal/jam/gedung, sisa kuota progress bar, sticky bottom button CTA ("Daftar Peserta" / "Daftar Kepanitiaan").
3.  `SCR-REG-01`: **Formulir Pendaftaran Peserta** &bull; Auto-fill box (NIM, Nama, Email, Prodi - disabled), input WhatsApp aktif, custom answers (ukuran kaos), banner validasi merah jika sudah terdaftar, tombol submit.
4.  `SCR-REG-02`: **Formulir Oprec Panitia** &bull; Dropdown Divisi 1 & 2, textarea motivasi, box drag-and-drop unggah CV/KRS (PDF only, max 5MB).
5.  `SCR-REG-03`: **Layar Konfirmasi Sukses** &bull; Ikon centang hijau besar, kode tiket, info box reminder WA terkirim, tombol "Lihat E-Tiket" dan tombol "Add to Google Calendar".
6.  `SCR-MHS-01`: **Event Saya & Riwayat** &bull; Tab: 1. Tiket Aktif, 2. Riwayat & Sertifikat, 3. Lamaran Oprec.
7.  `SCR-MHS-02`: **E-Tiket Digital** &bull; Kartu tiket elegan, QR Code besar terpusat, identitas peserta, tombol unduh PDF, tombol batalkan pendaftaran.
8.  `SCR-MHS-03`: **Brankas E-Sertifikat** &bull; Canvas preview sertifikat, nomor seri SKPI resmi, tombol download PDF HQ (aktif jika Hadir, disabled jika Tidak Hadir).
9.  `SCR-PAN-01`: **Dashboard Panitia LEM** &bull; Metrik kuota & pendaftar, tabel acara kelolaan, tombol "+ Buat Acara".
10. `SCR-PAN-02`: **Formulir Buat Acara (Multi-Step)** &bull; Step 1: Info Dasar; Step 2: Upload Poster (4:5); Step 3: Mode Peserta/Oprec; Step 4: Kuota & Form Builder.
11. `SCR-PAN-03`: **Rekap Peserta & Presensi** &bull; Tabel pendaftar lengkap, search NIM/Nama, filter status, tombol "Ekspor Excel/CSV", tombol "Buka Scanner".
12. `SCR-PAN-04`: **Seleksi Oprec Panitia** &bull; Filter divisi, link berkas CV, picker jadwal wawancara, tombol kirim jadwal via WA.
13. `SCR-PAN-05`: **Web Scanner Meja Registrasi** &bull; Viewfinder kamera laptop/HP, counter kehadiran real-time, Card HIJAU (Valid - Hadir), Card MERAH (Tiket Sudah Dipakai!).
14. `SCR-ADM-01`: **Kurasi & Approval DPM** &bull; Review draf acara masuk, toggle "Auto-Post ke Instagram @uii.events", tombol Approve / Revisi / Tolak.
15. `SCR-ADM-02`: **Monitoring Log Notifikasi** &bull; Log pengiriman WA Gateway & riwayat URL postingan Meta Instagram API.

---

# BAGIAN 3: MODUL SPESIALISASI BACKEND DEVELOPER (FOR BACKEND AI)
*(Konteks khusus untuk rekan Backend dan asisten AI backend)*

### 3.1 System Prompt untuk AI Backend
> *"Bertindaklah sebagai Senior Laravel Backend Engineer & Database Architect. Gunakan Laravel (PHP 8.3+) dan PostgreSQL 15+ (bukan MySQL: wajib partial unique index). Rancanglah migration 3NF, transaksi penguncian kuota atomik (`lockForUpdate`), middleware otorisasi berbasis role dengan Laravel Sanctum (Bearer token), controller RESTful API dengan response envelope seragam, Laravel Queue (Redis) + Scheduler untuk reminder WA/email, serta integrasi Meta Graph API untuk UII Event Management Platform."*

### 3.2 Skema Relasi Database DDL (PostgreSQL)
> *MySQL tidak mendukung partial unique index yang dipakai di bawah. Versi migration Laravel: `06_Backend_Database_and_Models.md`.*
```sql
-- 1. TABEL USERS & ROLES
CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(30) UNIQUE NOT NULL -- 'STUDENT', 'ORGANIZER', 'SUPERADMIN'
);

CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nim VARCHAR(20) UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    faculty VARCHAR(80),
    study_program VARCHAR(80),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_roles (
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    role_id INT REFERENCES roles(id) ON DELETE CASCADE,
    PRIMARY KEY (user_id, role_id)
);

-- 2. TABEL EVENTS & KATEGORI
CREATE TABLE event_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

CREATE TABLE events (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organizer_id UUID REFERENCES users(id),
    category_id INT REFERENCES event_categories(id),
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(180) UNIQUE NOT NULL,
    activity_type VARCHAR(30) NOT NULL, -- 'PARTICIPANT_EVENT' / 'COMMITTEE_RECRUITMENT'
    poster_url VARCHAR(255) NOT NULL,
    quota_total INT NOT NULL,
    quota_remaining INT NOT NULL,
    start_time TIMESTAMP WITH TIME ZONE NOT NULL,
    end_time TIMESTAMP WITH TIME ZONE NOT NULL,
    location VARCHAR(150) NOT NULL,
    status VARCHAR(30) DEFAULT 'DRAFT', -- 'DRAFT', 'PENDING_APPROVAL', 'PUBLISHED', 'REJECTED', 'COMPLETED'
    auto_post_ig BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 3. TABEL REGISTRATIONS (KUNCI ANTI-DOUBLE ENTRY)
CREATE TABLE registrations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES users(id),
    event_id UUID REFERENCES events(id),
    registration_type VARCHAR(30) NOT NULL, -- 'PARTICIPANT' / 'COMMITTEE'
    custom_answers JSONB,
    division_pref_1 VARCHAR(50),
    division_pref_2 VARCHAR(50),
    cv_file_url VARCHAR(255),
    selection_status VARCHAR(30), -- 'SUBMITTED', 'INTERVIEW_SCHEDULED', 'ACCEPTED', 'REJECTED'
    status VARCHAR(30) DEFAULT 'REGISTERED', -- 'REGISTERED', 'ATTENDED', 'CANCELLED'
    registered_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- UNIQUE CONSTRAINT MUTLAK PENCEGAH DATA GANDA
CREATE UNIQUE INDEX uq_user_event_active 
ON registrations (user_id, event_id) 
WHERE status != 'CANCELLED';

-- 4. TABEL TICKETS & QR CODE
CREATE TABLE tickets (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    registration_id UUID UNIQUE REFERENCES registrations(id) ON DELETE CASCADE,
    ticket_code VARCHAR(50) UNIQUE NOT NULL,
    qr_payload VARCHAR(255) UNIQUE NOT NULL,
    is_checked_in BOOLEAN DEFAULT FALSE,
    checked_in_at TIMESTAMP WITH TIME ZONE,
    checked_in_by UUID REFERENCES users(id)
);

-- 5. TABEL CERTIFICATES
CREATE TABLE certificates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    registration_id UUID UNIQUE REFERENCES registrations(id) ON DELETE CASCADE,
    certificate_number VARCHAR(60) UNIQUE NOT NULL,
    verify_hash VARCHAR(64) UNIQUE NOT NULL,
    issued_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 6. TABEL NOTIFICATION LOGS
CREATE TABLE notification_logs (
    id BIGSERIAL PRIMARY KEY,
    event_id UUID REFERENCES events(id),
    channel VARCHAR(20) NOT NULL, -- 'WHATSAPP', 'EMAIL', 'INSTAGRAM'
    recipient VARCHAR(100) NOT NULL,
    delivery_status VARCHAR(20) NOT NULL, -- 'SENT', 'FAILED', 'PENDING'
    response_payload TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### 3.3 Logika Transaksi Concurrency Quota (`lockForUpdate`)
```php
// app/Services/RegistrationService.php — Laravel 11/12 + PostgreSQL
// Versi lengkap (validasi, tiket, job konfirmasi): 07_Backend_API_Implementation.md
public function register(User $user, Event $event, array $data): Registration
{
    return DB::transaction(function () use ($user, $event, $data) {
        // 1. Kunci baris event (SELECT ... FOR UPDATE)
        $locked = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

        if ($locked->status !== EventStatus::Published) {
            throw new ApiException('ERR_EVENT_NOT_AVAILABLE', 'Acara tidak tersedia.', 404);
        }

        // 2. Anti-Double Entry (cek ramah; guard utama = partial unique index di DB)
        $exists = Registration::where('user_id', $user->id)
            ->where('event_id', $locked->id)
            ->where('status', '!=', RegistrationStatus::Cancelled->value)
            ->exists();
        if ($exists) {
            throw new ApiException('ERR_ALREADY_REGISTERED', 'Anda sudah terdaftar di acara ini.', 409);
        }

        // 3. Cek & potong kuota
        if ($locked->quota_remaining <= 0) {
            throw new ApiException('ERR_QUOTA_EXCEEDED', 'Maaf, kuota acara sudah habis.', 400);
        }
        $locked->decrement('quota_remaining');

        // 4. Buat pendaftaran + 5. terbitkan tiket ber-HMAC
        $reg = Registration::create([ /* user_id, event_id, registration_type, whatsapp_number, custom_answers */ ]);
        app(TicketService::class)->issue($reg);

        return $reg;
    });
}
```

### 3.4 Standar Format Kontrak REST API
*   **Format Envelope Seragam:**
    *   Sukses: `{"status": "success", "message": "...", "data": {...}, "meta": {...}}`
    *   Gagal: `{"status": "error", "error_code": "ERR_CODE", "message": "...", "errors": [...]}`
*   **Daftar Endpoint Kunci:**
    *   `GET /api/v1/events` &bull; Katalog publik dengan query: `search`, `category`, `page`.
    *   `POST /api/v1/events/:id/register` &bull; Pendaftaran peserta (Balasan: 201 Created vs 409 Conflict).
    *   `POST /api/v1/events/:id/apply-committee` &bull; Submisi Oprec panitia multipart/form-data.
    *   `POST /api/v1/attendance/scan` &bull; Payload `{ qr_payload, event_id }` (Balasan: 200 OK vs 400 ALREADY_CHECKED_IN).
    *   `PATCH /api/v1/admin/events/:id/review` &bull; Body `{ decision: 'APPROVED', trigger_instagram_post: true }`.

> Daftar lengkap endpoint (auth, organizer, admin, verify, dll.): `07_Backend_API_Implementation.md`.

---

# BAGIAN 4: MODUL SPESIALISASI FRONTEND DEVELOPER (FOR FRONTEND AI)
*(Konteks khusus untuk rekan Frontend dan asisten AI frontend)*

### 4.1 System Prompt untuk AI Frontend
> *"Bertindaklah sebagai Senior Frontend Engineer yang memakai HTML5, CSS3, dan JavaScript vanilla (ES Modules, tanpa framework, tanpa build step; fallback Blade/Vue bila perlu). Bangunlah antarmuka web client yang cepat, elegan, ramah mobile, memiliki validasi form instan di sisi klien, terhubung dengan kontrak REST API lewat satu modul `api.js`, merender input pengguna hanya via `textContent` (anti-XSS), serta memiliki modul pemindai barcode kamera HTML5 untuk meja registrasi."*

### 4.2 Struktur Routing Halaman Web
> *[disesuaikan] Frontend vanilla multi-page: tiap URL di bawah dilayani Laravel (`routes/web.php`) ke file HTML di `public/app/`; JS membaca slug/ID dari `location.pathname`. Aset wajib dirujuk dengan path absolut `/app/...`. Lihat `05`.*
*   `/` &bull; Halaman Katalog & Landing Page (`SCR-PUB-01`).
*   `/events/:slug` &bull; Detail Acara & Deskripsi Poster (`SCR-PUB-02`).
*   `/events/:slug/register` &bull; Form Pendaftaran Peserta (`SCR-REG-01`).
*   `/events/:slug/apply-committee` &bull; Form Pendaftaran Oprec Panitia (`SCR-REG-02`).
*   `/my-events` &bull; Portal Mahasiswa (Tab Tiket Aktif, Riwayat, Oprec) (`SCR-MHS-01`).
*   `/my-tickets/:ticket_code` &bull; Tampilan E-Tiket QR Digital (`SCR-MHS-02`).
*   `/organizer/events` &bull; Dashboard Panitia LEM/UKM (`SCR-PAN-01`).
*   `/organizer/events/:id/scanner` &bull; Web Scanner Meja Registrasi Kamera (`SCR-PAN-05`).
*   `/admin/curation` &bull; Dashboard Kurasi & Approval Super Admin DPM (`SCR-ADM-01`).

### 4.3 Penanganan Error (409 dkk.) di `api.js` (fetch wrapper)
```javascript
// public/app/js/api.js — satu-satunya tempat fetch ke backend (same-origin, base /api/v1)
import { showToast } from '/app/js/ui.js';

const BASE = '/api/v1';

export class ApiError extends Error {
  constructor(status, body) {
    super(body?.message || 'Terjadi kesalahan.');
    this.status = status;
    this.code = body?.error_code;
    this.errors = body?.errors ?? [];
  }
}

async function request(method, path, body) {
  const headers = { Accept: 'application/json' };
  const token = sessionStorage.getItem('token');
  if (token) headers.Authorization = `Bearer ${token}`;

  let payload;
  if (body instanceof FormData) {
    payload = body; // multipart (oprec): biarkan browser set Content-Type
  } else if (body) {
    headers['Content-Type'] = 'application/json';
    payload = JSON.stringify(body);
  }

  const res = await fetch(BASE + path, { method, headers, body: payload });
  const json = await res.json().catch(() => null);

  if (!res.ok) {
    if (res.status === 401) {
      sessionStorage.removeItem('token');
      location.href = '/login';
    } else if (res.status === 409 && json?.error_code === 'ERR_ALREADY_REGISTERED') {
      showToast('Peringatan: Anda sudah terdaftar di acara ini!', 'error');
      location.href = '/my-events';
    }
    throw new ApiError(res.status, json);
  }
  return json; // { status, message, data, meta }
}

export const api = {
  get: (p) => request('GET', p),
  post: (p, b) => request('POST', p, b),
  patch: (p, b) => request('PATCH', p, b),
  del: (p) => request('DELETE', p),
};
```
> Token di `sessionStorage` rentan jika ada XSS: karena itu semua data pengguna dirender via `textContent`, bukan `innerHTML`.

### 4.4 Implementasi Web Scanner Kamera Presensi (HTML5-QRCode)
```javascript
// Contoh Integrasi Modul Scanner Kamera Panitia (SCR-PAN-05)
// Vanilla: muat html5-qrcode via <script> (self-host di public/app/vendor/ agar lolos CSP); Html5QrcodeScanner tersedia global.
// import { api } from '/app/js/api.js';

function onScanSuccess(decodedText, decodedResult) {
  // 1. Tembak Payload QR ke Backend
  api.post('/attendance/scan', {
    event_id: currentEventId,
    qr_payload: decodedText
  })
  .then((res) => {
    // 2. Feedback HIJAU & Audio Beep Sukses
    playSuccessBeep();
    showGreenNotification(`VALID: ${res.data.participant_name} - Hadir!`);
  })
  .catch((err) => {
    // 3. Feedback MERAH & Audio Alarm Error
    playErrorAlarm();
    showRedNotification(err.message || "Tiket Sudah Pernah Di-scan!");
  });
}
```

---

# BAGIAN 5: MODUL SPESIALISASI QA ENGINEER (FOR QA AI)
*(Konteks khusus untuk rekan Quality Assurance dan asisten AI tester)*

### 5.1 System Prompt untuk AI QA Engineer
> *"Bertindaklah sebagai Senior QA Automation & Lead Quality Engineer. Susunlah skrip pengujian E2E (Playwright), skrip uji beban kuota konkuren (k6), skenario uji penetrasi OWASP, serta koleksi verifikasi API Postman berdasarkan Matriks Ketelusuran Kebutuhan (RTM) UII Event Management."*

### 5.2 Matriks 5 Skenario Uji Kritis Wajib Lulus (Must-Pass Test Cases)
1.  **TC-REG-01 (Happy Path Pendaftaran):** Pendaftaran user pertama kali &rarr; Kuota berkurang 1, HTTP 201 Created, tiket QR terbit.
2.  **TC-REG-02 (Anti-Double Entry):** User yang sama submit ulang pada event yang sama &rarr; Wajib ditolak dengan **HTTP 409 Conflict** (`ERR_ALREADY_REGISTERED`), tidak ada baris tiket baru di DB.
3.  **TC-REG-03 (Race Condition Kuota 1):** 100 Virtual Users k6 menembak bersamaan pada sisa kuota 1 &rarr; Tepat 1 user sukses (201 Created), 99 user lainnya ditolak (400 Quota Full), kuota akhir tepat 0.
4.  **TC-SCN-01 & 02 (Scanner Meja Registrasi):** Pemindaian pertama menghasilkan status `ATTENDED` (Layar Hijau) &rarr; Pemindaian kedua pada QR yang sama wajib memicu **Layar Merah & Alarm Peringatan Duplikat**.
5.  **TC-CRT-02 (Gate Sertifikat):** Akun yang tidak pernah hadir (ABSENT) wajib terkunci tombol unduh sertifikatnya (HTTP 403 Forbidden).

### 5.3 Skrip Uji Beban k6 Konkurensi Kuota (Siap Dijalankan)
```javascript
// Simpan di: tests/load/quota_rush.js
import http from 'k6/http';
import { check } from 'k6';

export const options = {
  vus: 50, // 50 Mahasiswa serentak
  iterations: 50,
  thresholds: {
    http_req_duration: ['p(95)<400'], // 95% request di bawah 400ms
  },
};

export default function () {
  const res = http.post(
    'http://localhost:8080/api/v1/events/evt-seminar-ai/register' // stack Nginx+PHP-FPM (lihat 09); ganti evt-seminar-ai dengan ID event,
    JSON.stringify({ whatsapp_number: '08123456789' }),
    { headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${__ENV.TEST_TOKEN}` } }
  );

  check(res, {
    'Status 201 ATAU 409/400 (Kedap)': (r) => [201, 400, 409].includes(r.status),
  });
}
```

---

## 🚀 KATA PENUTUP & PROTOKOL KOLABORASI TIM

Seluruh anggota tim diwajibkan untuk:
1.  Menjadikan dokumen ini sebagai **acuan spesifikasi tertinggi** (*Single Source of Truth*).
2.  Tidak mengubah nama kolom database atau penamaan endpoint API secara sepihak tanpa persetujuan **System Analyst**.
3.  Melaporkan setiap kejanggalan logika bisnis kepada System Analyst untuk penyesuaian matriks RTM.
