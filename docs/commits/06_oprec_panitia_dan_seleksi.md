# 📦 Laporan Fitur 06: Jalur Open Recruitment Panitia & Dashboard Seleksi

## 1. Ringkasan Fitur
Fitur 06 mengimplementasikan modul perekrutan panitia (*Committee Recruitment*) yang aman, terintegrasi, dan tahan terhadap manipulasi berkas:
- **Pendaftaran Oprec dengan Pilihan Divisi Terkonfigurasi:** Mahasiswa dapat mendaftar dengan memilih divisi utama (`division_pref_1`) dan divisi cadangan (`division_pref_2`) yang divalidasi langsung terhadap relasi divisi kegiatan (`event_divisions`).
- **Hardened File Upload (Magic Bytes Verification):** Validasi unggahan berkas CV/Portofolio tidak hanya bergantung pada ekstensi atau MIME type browser, melainkan memeriksa langsung header biner (*magic bytes* `%PDF` / `0x25 0x50 0x44 0x46`). File berbahaya atau skrip yang disamarkan sebagai PDF langsung ditolak dengan status HTTP 422 (`ERR_VALIDATION`).
- **Penyimpanan Berkas Terisolasi & Anti-Traversal:** Berkas CV disimpan di disk lokal privat dengan nama acak berbasis UUID (`recruitment_docs/{event_id}/{uuid}.pdf`), mencegah penamaan file ganda (*collision*) dan eksploitasi jalur penyimpanan (*path traversal*).
- **Proteksi IDOR Unduh Dokumen CV:** Dokumen CV hanya dapat diakses atau diunduh oleh mahasiswa pemilik berkas, panitia pemilik kegiatan, dan Superadmin. Pihak ketiga atau mahasiswa lain diblokir dengan status HTTP 403 (`ERR_FORBIDDEN`).
- **Dashboard Seleksi Panitia:** Panitia pemilik kegiatan dapat melihat daftar seluruh pelamar, menyaring berdasarkan divisi dan status seleksi (`SUBMITTED`, `INTERVIEW_SCHEDULED`, `ACCEPTED`, `REJECTED`), serta memperbarui tahapan seleksi peserta beserta catatan wawancara/kelulusan.
- **Riwayat Pelamar Mahasiswa:** Endpoint `/my-applications` memungkinkan mahasiswa memantau status lamaran kepanitiaannya secara real-time.

---

## 2. File-File Baru & Perubahan

| Aksi | File Path | Deskripsi |
|---|---|---|
| **Baru** | `app/Http/Requests/Recruitments/ApplyRecruitmentRequest.php` | FormRequest validasi divisi dan berkas PDF CV (max 5MB) |
| **Baru** | `app/Http/Requests/Recruitments/UpdateSelectionRequest.php` | FormRequest validasi perubahan status seleksi (`SelectionStatusEnum`) |
| **Baru** | `app/Services/RecruitmentService.php` | Service pendaftaran oprec, validasi magic bytes `%PDF`, query pelamar, update seleksi, & streaming CV |
| **Baru** | `app/Http/Controllers/Api/V1/RecruitmentController.php` | REST Controller oprec: apply, my-applications, applicants, updateSelection, downloadCv |
| **Baru** | `tests/Feature/RecruitmentTest.php` | 10 skenario automated feature test (magic bytes, IDOR, divisi valid, filter pelamar, update status) |
| **Modifikasi** | `app/Http/Resources/RegistrationResource.php` | Menambahkan data preferensi divisi, status seleksi, dan URL dokumen CV |
| **Modifikasi** | `bootstrap/app.php` | Menambahkan pemetaan `UnprocessableEntityHttpException` ke `ERR_VALIDATION` (422) |
| **Modifikasi** | `routes/api.php` | Menambahkan rute `/events/{id}/apply`, `/my-applications`, `/organizer/events/{id}/applicants`, `/organizer/registrations/{id}/selection`, `/registrations/{id}/cv` |
| **Modifikasi** | `app/Console/Commands/TestApiCommand.php` | Menambahkan skenario 21-24 (Apply Oprec, My Applications, Organizer Applicants, Update Selection) |
| **Modifikasi** | `public/api-tester.html` | Menambahkan panel tombol uji Fitur 06 (Daftar Oprec, Lamaran Saya, Dashboard Pelamar) |
| **Modifikasi** | `docs/COMMANDS_CHEAT_SHEET.md` | Memperbarui daftar 24 skenario pengujian CLI dan tabel filter pengujian |
| **Modifikasi** | `docs/api/API_DOCUMENTATION.md` | Menambahkan Bagian 7 (Jalur Oprec Panitia & Dashboard Seleksi) |
| **Modifikasi** | `PROGRESS.md` | Memperbarui status Fitur 06 menjadi Selesai |

---

## 3. Hasil Pengujian

### A. Pengujian PHPUnit Fitur Oprec
```bash
php artisan test --filter RecruitmentTest
```
- **Hasil:** `10 passed (46 assertions)`
- **Durasi:** ~1.4s

### B. Pengujian Seluruh Suite Pengujian
```bash
php artisan test
```
- **Hasil:** `45 passed (341 assertions)`
- **Status:** Seluruh 45 pengujian dari Fitur 01 hingga 06 lulus 100%.

### C. Pengujian API Internal CLI
```bash
php artisan api:test
```
- **Hasil:** `24 LULUS / 0 GAGAL (Total: 24 Skenario)`
