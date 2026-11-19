# 📌 Progress Pelaksanaan Pengembangan Backend
## UII Event Management Platform (Laravel 12 + PostgreSQL + Sanctum)

Dokumen ini memantau status pengerjaan fitur secara bertahap (*feature-by-feature*), verifikasi pengujian, dan riwayat commit. Setiap fitur yang selesai dikembangkan akan diuji terlebih dahulu oleh penguji/user, dan setelah dinyatakan valid, akan dibuatkan laporan commit di folder [`docs/commits/`](file:///d:/Internship/Sistem%20Management%20Event%20-%20BSI%20UII/backend/docs/commits) serta dilakukan git commit.

---

### 📊 Ringkasan Status Fitur

| No | Modul / Fitur | Status | File Laporan Commit | Tanggal Verifikasi |
|---|---|---|---|---|
| **01** | **Fondasi Arsitektur Database, Model Eloquent & RBAC**<br>• Migrasi PostgreSQL (UUID, 3NF, Partial Unique Index)<br>• HasUuids, Enums, Model Relations<br>• Standard API Response Envelope & Exception Handler<br>• Database Seeders (Roles, Demo Users, Categories) | 🟢 *Selesai* | [`docs/commits/01_fondasi_database_model_rbac.md`](docs/commits/01_fondasi_database_model_rbac.md) | 19 Nov 2026 |
| **02** | **Autentikasi & Akun (Sanctum Auth & Role Middleware)**<br>• Register Mahasiswa & Panitia<br>• Login dengan Rate Limiting (5 req/menit)<br>• Profile & Logout<br>• Middleware `CheckRole` (STUDENT, ORGANIZER, SUPERADMIN) | 🟢 *Selesai* | [`docs/commits/02_autentikasi_dan_rbac.md`](docs/commits/02_autentikasi_dan_rbac.md) | 19 Nov 2026 |
| **03** | **Katalog Acara Publik & Kategori**<br>• `GET /api/v1/events` (Filter kategori, tipe, search, paginasi)<br>• `GET /api/v1/events/{slug}`<br>• `GET /api/v1/categories` | 🟡 *In Progress* | `docs/commits/03_katalog_acara_publik.md` | - |
| **04** | **Manajemen Acara Panitia & Kurasi Super Admin**<br>• CRUD Event Panitia (Draft, Submit Approval)<br>• Review, Approval, & Rejection oleh Super Admin<br>• EventPolicy & IDOR Protection | ⚪ *Pending* | `docs/commits/04_manajemen_acara_dan_approval.md` | - |
| **05** | **Transaksi Pendaftaran Cerdas Jalur Peserta**<br>• Concurrency Locking (`lockForUpdate`) & Anti-Double Entry (409 Conflict)<br>• Penerbitan E-Tiket ber-QR Code dengan tanda tangan HMAC-SHA256<br>• Endpoint Riwayat Tiket Mahasiswa (`/my-tickets`) | ⚪ *Pending* | `docs/commits/05_pendaftaran_peserta_dan_etiket.md` | - |
| **06** | **Jalur Oprec Panitia (Committee Recruitment)**<br>• Pendaftaran Oprec dengan Pilihan Divisi & Hardened CV/KRS Upload (PDF magic bytes)<br>• Dashboard Seleksi Panitia & Pengumuman Tahap | ⚪ *Pending* | `docs/commits/06_oprec_panitia_dan_seleksi.md` | - |
| **07** | **Presensi QR Hari-H & Penerbitan E-Sertifikat Otomatis**<br>• Web Scanner Endpoint dengan `hash_equals()` timing-safe HMAC<br>• Pencegahan Double Check-in (`ERR_ALREADY_CHECKED_IN`)<br>• Otomasi Terbit E-Sertifikat & Verifikasi Publik `/verify/{hash}` | ⚪ *Pending* | `docs/commits/07_presensi_qr_dan_sertifikat.md` | - |
| **08** | **Otomasi Notifikasi & Pengingat (WhatsApp & Email)**<br>• Fallback Resilient: WhatsApp Gateway $\rightarrow$ SMTP Email UII<br>• Background Queue Jobs dengan Exponential Backoff & Idempotency<br>• Scheduler Pengingat H-1 dan H-3 Jam | ⚪ *Pending* | `docs/commits/08_notifikasi_dan_pengingat.md` | - |
| **09** | **Integrasi Auto-Post Instagram (Meta Graph API) & Rekap Dashboard**<br>• Auto-post poster acara approved ke Akun Instagram Kampus<br>• Ekspor Rekap Peserta & Statistik Kehadiran Panitia | ⚪ *Pending* | `docs/commits/09_autopost_ig_dan_rekap.md` | - |

---

### 📝 Format Laporan Commit
Setiap laporan fitur commit disimpan dalam [`docs/commits/`](file:///d:/Internship/Sistem%20Management%20Event%20-%20BSI%20UII/backend/docs/commits) dengan struktur:
1. **Identitas Commit & Fitur**
2. **Daftar Berkas Baru / Diubah**
3. **Detail Implementasi Arsitektur & Keamanan**
4. **Instruksi Pengujian (Testing Guide & Curl / Postman)**
5. **Bukti Hasil Pengujian (Test Results)**
