# Software Requirements & Functional Specification (SRS) — UII Event Management Platform

> **Universitas Islam Indonesia (UII)** • Lembaga Eksekutif Mahasiswa
> Sistem Manajemen Kegiatan Mahasiswa — Dokumen Spesifikasi Kebutuhan Sistem, Inventaris Layar (Screen Inventory), Alur Pengguna (User Flow), & Panduan Handover Tim UI/UX, Backend, Frontend, serta QA

**Ringkasan Eksekutif:** Cetak biru resmi yang merinci pemecahan 4 masalah utama kegiatan kampus (Pendaftaran Manual, Double Entry, Poster Tersebar, dan Ketiadaan Reminder) melalui integrasi pendaftaran cerdas, modul E-Tiket ber-QR Code, otomasi pengingat via WhatsApp & Email, kurasi acara terpusat, serta fitur Auto-Post ke Akun Instagram Resmi.

| Atribut | Nilai |
|---|---|
| Nomor Dokumen | DOC-SRS-UII-EVT-2026-V1.0 |
| Tanggal Rilis | 17 November 2026 |
| Peran Penyusun | System Analyst (Lead SA) |
| Status Dokumen | APPROVED FOR DEVELOPMENT |
| Tim Pengembang | 5 orang (SA, UI/UX, Backend, Frontend, QA) |
| Platform Target | Web Responsive (Mobile-First untuk mahasiswa, Desktop untuk organizer) |
| Klasifikasi | Dokumen Internal Rekayasa Perangkat Lunak |

## Daftar Isi

1. [Pendahuluan & Dekomposisi Masalah](#bab-1-pendahuluan--dekomposisi-masalah)
2. [Pemetaan Aktor & Hak Akses (RBAC)](#bab-2-pemetaan-aktor--hak-akses-rbac)
3. [Spesifikasi Fitur & Prioritas MoSCoW](#bab-3-spesifikasi-fitur-lengkap--prioritas-moscow)
4. [Pemodelan Alur Proses Bisnis](#bab-4-pemodelan-alur-proses-bisnis-bpmn-flow)
5. [Inventaris Layar (Screen Inventory)](#bab-5-inventaris-layar-screen-inventory-untuk-uiux)
6. [Panduan Handover Tim & Skenario QA](#bab-6-panduan-handover-tim--skenario-pengujian-qa)

## Glosarium Istilah Kunci

| Istilah | Definisi |
|---|---|
| **LEM & UKM** | Lembaga Eksekutif Mahasiswa (tingkat universitas/fakultas) dan Unit Kegiatan Mahasiswa di UII sebagai pihak penyelenggara acara. |
| **Oprec Panitia** | Open Recruitment kepanitiaan mahasiswa untuk divisi acara, humas, logistik, publikasi/medkraf, dll. |
| **Anti-Double Entry** | Mekanisme integritas data yang mencegah satu NIM mendaftar lebih dari sekali pada acara yang sama. |
| **Omnichannel Reminder** | Mesin otomasi pengingat jadwal acara via dua saluran: WhatsApp Gateway API dan SMTP Email Service. |
| **Auto-Post Instagram** | Integrasi Meta Graph API untuk mempublikasikan poster dan caption acara otomatis ke akun resmi Instagram kampus begitu disetujui admin. |
| **SKPI** | Surat Keterangan Pendamping Ijazah; memerlukan arsip sertifikat keikutsertaan kegiatan mahasiswa yang terverifikasi keasliannya. |

---

## BAB 1: Pendahuluan & Dekomposisi Masalah

### 1.1 Latar Belakang & Konteks Ekosistem UII

Kegiatan kemahasiswaan di UII sangat dinamis. Setiap semester, puluhan organisasi mahasiswa (LEM Universitas, LEM Fakultas, DPM, serta UKM minat-bakat) rutin menyelenggarakan seminar nasional, workshop keahlian, kompetisi/lomba, hingga konser kampus tahunan.

Namun operasional pendaftaran dan penyebaran informasi masih mengandalkan formulir publik (seperti Google Forms terpisah) dan broadcast media sosial. Akibatnya pengalaman pengguna buruk bagi pendaftar dan beban administrasi berat bagi panitia.

### 1.2 Analisis 4 Masalah Utama & Solusi Rekayasa Sistem

| No | Permasalahan Lapangan | Akar Masalah | Solusi Teknis yang Dibangun |
|---|---|---|---|
| 1 | Pendaftaran Manual & Repot | Panitia membuat Google Forms terpisah per event, verifikasi manual, dan rekap manual di Excel yang rawan salah rekap. | Portal Pendaftaran Terpadu dengan validasi input otomatis, auto-fill profil NIM, dan sistem kuota real-time *concurrency lock*. |
| 2 | Sering Terjadi Double Entry | Tidak ada pengecekan duplikasi; mahasiswa lupa pernah mendaftar lalu submit ulang, sehingga kuota cepat habis palsu. | Aturan Integritas Data Unik: kunci unik komposit `UNIQUE(user_id, event_id)` di database; tombol pendaftaran otomatis terkunci jika user sudah terdaftar. |
| 3 | Poster Acara Tidak Terpusat | Info tersebar di Story Instagram pribadi panitia, grup WhatsApp angkatan, atau mading; arsip acara sulit dicari. | Katalog Single Portal dengan filter kategori + Auto-Post ke Akun Instagram Resmi Kampus begitu event disetujui. |
| 4 | Peserta Tidak Dapat Reminder | Panitia kewalahan broadcast satu per satu; mahasiswa lupa jadwal sehingga kehadiran rendah. | Automated Scheduler (Cron Job): pengingat via WhatsApp Gateway & Email pada H-1 dan H-3 jam sebelum acara. |

### 1.3 Batasan Ruang Lingkup (Scope Management)

Untuk menjamin prototipe selesai oleh tim 5 orang dalam durasi tes magang, diputuskan secara sadar:

- **In-Scope (Fokus Utama):** Manajemen pembuatan acara, kurasi admin kampus, pendaftaran 2 jalur (Peserta & Oprec Panitia), auto-post Instagram, pengingat WhatsApp/Email, E-Tiket QR Code, web scanner presensi hari-H, dan riwayat sertifikat mahasiswa.
- **Out-of-Scope (Dikeluarkan):** Modul pelaporan LPJ keuangan mendalam (terlalu melebar ke sistem akuntansi ormawa) serta integrasi Payment Gateway perbankan (difokuskan pada acara gratis dan transfer manual jika berbayar).

---

## BAB 2: Pemetaan Aktor & Hak Akses (RBAC)

### 2.1 Profil 3 Aktor Sistem

1. **Mahasiswa (Peserta / Pendaftar)** — Mahasiswa aktif UII yang mencari info kegiatan, mendaftar sebagai peserta atau panitia, mengakses tiket, serta mengunduh e-sertifikat.
2. **Panitia LEM / UKM (Organisasi Penyelenggara)** — Pengurus lembaga kemahasiswaan yang merancang draf kegiatan, memantau pendaftar, menyeleksi calon panitia, melakukan presensi scan QR hari-H, dan menyiarkan pengumuman darurat.
3. **Super Admin (Dewan Perwakilan Mahasiswa / DPM UII)** — Pengelola tingkat universitas sebagai kurator/penyetuju legalitas kegiatan, pemegang akun resmi Instagram, dan pengawas lalu lintas pengiriman pengingat.

### 2.2 Matriks Hak Akses (RBAC)

Pedoman bagi Backend (Middleware/Guards otorisasi) dan UI/UX (membedakan menu navigasi).

| Fungsi / Fitur Sistem | Mahasiswa | Panitia (LEM/UKM) | Super Admin |
|---|---|---|---|
| Melihat Katalog & Detail Event Publik | BISA (READ) | BISA (READ) | BISA (READ) |
| Mendaftar Event Peserta / Oprec Panitia | BISA (CREATE) | TIDAK | TIDAK |
| Mengakses E-Tiket QR & Brankas Sertifikat | BISA (READ/DOWNLOAD) | TIDAK | TIDAK |
| Membuat & Mengedit Draf Acara | TIDAK | BISA (CRUD ACARA SENDIRI) | BISA (ALL ACARA) |
| Memantau & Ekspor Rekap Data Pendaftar | TIDAK | BISA (ACARA SENDIRI) | BISA (SELURUH KAMPUS) |
| Seleksi Berkas Oprec & Kirim Jadwal Wawancara | TIDAK | BISA (KELOLA PELAMAR) | TIDAK |
| Operasional Web Scanner Presensi Hari-H | TIDAK | BISA (SCAN TIKET) | TIDAK |
| Approval / Penolakan Pengajuan Acara Kampus | TIDAK | TIDAK | BISA (HAK MUTLAK) |
| Memicu / Mengatur Auto-Post Instagram API | TIDAK | TIDAK | BISA (SAAT APPROVE) |
| Monitoring Log Pengiriman WhatsApp Gateway | TIDAK | TIDAK | BISA (AUDIT TRAIL) |

---

## BAB 3: Spesifikasi Fitur Lengkap & Prioritas MoSCoW

### 3.1 Pendaftaran Cerdas 2 Jalur (Peserta vs Oprec Panitia)

Arsitektur pendaftaran adaptif melalui atribut `activity_type`:

- **Jalur Peserta (Event Attendee):** Untuk seminar, workshop, webinar, lomba, dan konser. Mengutamakan pemesanan kuota cepat, pencegahan entri ganda, dan langsung menerbitkan E-Tiket ber-QR Code begitu formulir tervalidasi.
- **Jalur Oprec Panitia (Committee Recruitment):** Untuk perekrutan staf kepanitiaan acara besar (misal Ospek Kampus/PKKMB). Form menyajikan pilihan Divisi (Pilihan 1 & 2), unggah berkas (CV & KRS PDF), dan menyalurkan data ke dashboard seleksi (status: Menunggu Review → Wawancara → Diterima/Ditolak).

### 3.2 Omnichannel Reminder Engine (WhatsApp + Email)

- **Trigger H-1 Acara:** Dikirim pukul 09.00 WIB kepada seluruh peserta terdaftar berisi rincian lokasi gedung, waktu kumpul, dan link cepat membuka E-Tiket.
- **Trigger H-3 Jam Acara:** Pesan WhatsApp singkat pengingat pembukaan meja registrasi ulang.
- **Trigger Jadwal Wawancara Panitia:** Otomatis terkirim ke WhatsApp pelamar saat panitia menetapkan slot waktu dan ruangan/link Google Meet.

### 3.3 Auto-Post Poster ke Akun Instagram Resmi

Memanfaatkan Meta Graph API. Begitu Super Admin klik **Approve** pada pengajuan kegiatan LEM/UKM, sistem otomatis memposting ke akun resmi **@uii.events** lengkap dengan poster dan caption terstruktur (Tanggal, Lokasi, Penyelenggara, dan tautan bio menuju portal pendaftaran).

### 3.4 E-Tiket & Web Scanner Presensi Cepat Hari-H

Setiap pendaftar sah memperoleh E-Tiket digital berisi QR Code unik terenkripsi. Pada hari-H, panitia membuka kamera laptop/HP lewat menu scanner untuk memindai tiket. Feedback visual (Hijau = Hadir, Merah = Duplikat/Palsu) serta suara bip instan, tanpa aplikasi tambahan.

### 3.5 Event Saya & Brankas E-Sertifikat (Certificate Vault)

Setiap mahasiswa punya dashboard riwayat pribadi berisi seluruh e-tiket dan arsip sertifikat. Tombol unduh e-sertifikat **hanya aktif** jika presensi tervalidasi "Hadir" oleh panitia, dan sertifikat dilengkapi tautan verifikasi keaslian digital yang mendukung pengajuan SKPI.

### 3.6 Matriks Prioritas Kerja Tim (MoSCoW)

| Klasifikasi | Daftar Modul & Fitur | Catatan untuk Tim |
|---|---|---|
| **MUST-HAVE (WAJIB)** | 1. Katalog Event Terpusat<br>2. Form Pendaftaran & Anti-Double Entry<br>3. Pilihan Mode: Peserta vs Oprec Panitia<br>4. E-Tiket & Web Scanner QR Presensi<br>5. Dashboard Panitia & Approval Admin | Pondasi utama (Core MVP). Wajib selesai dan bebas bug sebelum deadline demo tes magang. |
| **SHOULD-HAVE (PENTING)** | 1. Reminder WhatsApp & Email Otomatis<br>2. Brankas E-Sertifikat Digital<br>3. Ekspor Data Peserta ke Excel/CSV | Fitur pembeda utama yang membuktikan sistem menyelesaikan keluhan nyata pengguna. |
| **COULD-HAVE (NILAI PLUS)** | 1. Auto-Post ke Instagram Resmi via API<br>2. Sistem Waiting List Otomatis<br>3. Sinkronisasi Add to Google Calendar (.ics) | Wow-factor di depan penguji. Jika integrasi API riil terbentur kuota, siapkan rancangan arsitektur dan simulasi mock response. |

---

## BAB 4: Pemodelan Alur Proses Bisnis (BPMN Flow)

### 4.1 Alur 4 Tahap dari Hulu ke Hilir

**Tahap 1 — Pembuatan Draf Acara & Kurasi Izin Kampus**
Panitia LEM/UKM mengisi formulir kegiatan (poster, kuota, tanggal, tipe pendaftaran: Peserta/Oprec) → sistem menyimpan status `PENDING_APPROVAL` → Super Admin meninjau draf → jika disetujui, status menjadi `PUBLISHED`, tayang di katalog publik, dan sekaligus memicu Auto-Post ke Instagram.

**Tahap 2 — Pendaftaran Peserta & Validasi Integritas Data**
Mahasiswa login akun UII → memilih acara → mengisi formulir → validasi sistem: cek ketersediaan kuota dan cek pasangan unik `(user_id, event_id)` → jika lolos, kuota dikunci (decrement), E-Tiket diterbitkan, dan email konfirmasi otomatis dikirim. Jika NIM sudah terdaftar, sistem menolak dengan pesan ramah.

**Tahap 3 — Eksekusi Pengingat Otomatis (Scheduler Engine)**
Background Cron Job berjalan setiap jam → mengidentifikasi acara yang berjarak H-1 dan H-3 jam dari waktu mulai → memanggil WhatsApp Gateway API & SMTP Server → mengirim pesan pengingat dan link E-Tiket kepada seluruh pendaftar secara simultan.

**Tahap 4 — Hari Pelaksanaan Acara (Presensi & Sertifikasi)**
Peserta menunjukkan QR Code di HP → panitia memindai lewat Web Scanner Kamera → sistem mencocokkan hash tiket → jika valid, status menjadi `ATTENDED` → tombol unduh E-Sertifikat di akun mahasiswa otomatis aktif.

### 4.2 Penanganan Kondisi Gagal & Skenario Edge Cases

| Kondisi Edge Case | Potensi Masalah Lapangan | Mekanisme Penanganan Sistem |
|---|---|---|
| Dua user submit bersamaan di kuota terakhir | Race condition atau over-capacity melebihi kapasitas ruangan. | Database Atomic Transaction / Pessimistic Locking pada baris event saat pengurangan kuota; user kedua dialihkan ke antrean Waiting List. |
| QR tiket di-scan dua kali | Peserta membagikan screenshot tiket kepada teman untuk masuk gratis. | Scanner langsung mengeluarkan bunyi peringatan merah: "Tiket ini sudah pernah di-scan pada pukul 08.15 WIB oleh Panitia A!" |
| Koneksi internet panitia terputus saat scan | Antrean peserta di pintu masuk macet. | Scanner dilengkapi pencarian manual NIM/Nama secara offline-cached untuk mencentang presensi darurat. |

---

## BAB 5: Inventaris Layar (Screen Inventory) untuk UI/UX

Setiap layar punya kode identifikasi unik untuk memudahkan pelacakan implementasi.

### 5.1 Modul Publik & Pendaftaran Mahasiswa (Mobile-First)

#### SCR-PUB-01: Katalog Event Terpusat (Home) — *Akses: Publik / Mahasiswa*

- **Tujuan:** Menampilkan seluruh acara resmi yang sedang aktif di kampus UII.
- **Komponen UI wajib:** Header logo UII Event, search bar, filter kategori (Seminar, Workshop, Lomba, Konser, Oprec Panitia), banner carousel acara unggulan, kartu event (poster, judul, badge ormawa, tanggal, status kuota).
- **Interaksi & validasi:** Filter interaktif tanpa reload halaman; klik kartu event langsung membuka SCR-PUB-02.

#### SCR-PUB-02: Detail Event & Poster Lengkap — *Akses: Publik / Mahasiswa*

- **Tujuan:** Deskripsi lengkap kegiatan sebelum mahasiswa memutuskan mendaftar.
- **Komponen UI wajib:** Poster HD (klik perbesar), badge kategori, tanggal & jam, lokasi gedung (atau link meeting jika online), nama pembicara/guest star, indikator sisa kuota, tombol mengambang (sticky CTA) "Daftar Peserta" atau "Daftar Kepanitiaan".
- **Interaksi & validasi:** Jika kuota = 0, tombol berubah menjadi "Masuk Waiting List"; jika periode pendaftaran lewat, tombol nonaktif (disabled).

#### SCR-REG-01: Formulir Pendaftaran Peserta — *Akses: Mahasiswa Terautentikasi*

- **Tujuan:** Mengumpulkan data kepesertaan secara cepat.
- **Komponen UI wajib:** Kotak profil otomatis (NIM, Nama Lengkap, Email UII, Prodi — readonly), input nomor WhatsApp aktif, kolom pertanyaan kustom (misal ukuran kaos konser), checkbox persetujuan syarat kegiatan, tombol "Konfirmasi Pendaftaran".
- **Interaksi & validasi Anti-Double Entry:** Sistem otomatis mengecek NIM; jika sudah pernah mendaftar, munculkan banner merah: *"Anda sudah terdaftar di event ini. Silakan buka menu Event Saya untuk melihat E-Tiket Anda."* Tombol submit dinonaktifkan.

#### SCR-REG-02: Formulir Pendaftaran Oprec Panitia — *Akses: Mahasiswa Terautentikasi*

- **Tujuan:** Menampung berkas lamaran mahasiswa yang ingin menjadi panitia.
- **Komponen UI wajib:** Data diri (auto-fill), dropdown Divisi 1 (wajib) & Divisi 2 (opsional), area teks alasan melamar & pengalaman organisasi, kotak unggah CV & KRS (PDF, maks 5MB), tombol "Kirim Berkas Lamaran".
- **Interaksi & validasi:** Hanya menerima `.pdf`; progress bar saat unggah.

#### SCR-REG-03: Halaman Sukses Pendaftaran & Bukti Tiket — *Akses: Mahasiswa Terautentikasi*

- **Tujuan:** Kepastian psikologis bahwa pendaftaran terekam sempurna.
- **Komponen UI wajib:** Ikon centang hijau besar, ID Pendaftaran unik, nama acara, teks "E-Tiket dan jadwal pengingat telah dikirimkan ke WhatsApp & Email Anda", tombol utama "Lihat E-Tiket Saya", tombol sekunder "Tambahkan ke Google Calendar".

### 5.2 Modul Event Saya & Brankas Sertifikat (Mahasiswa Portal)

#### SCR-MHS-01: Dashboard Event Saya & Riwayat — *Akses: Mahasiswa*

- **Tujuan:** Satu tempat mengelola seluruh kegiatan yang sedang dan pernah diikuti.
- **Komponen UI wajib:** Tab (1. Tiket Aktif / Upcoming, 2. Riwayat & Sertifikat / Past, 3. Lamaran Kepanitiaan / Oprec), daftar kartu kegiatan sesuai tab, ilustrasi *empty state*.
- **Interaksi:** Tab Tiket Aktif punya tombol pintas "Buka QR Tiket"; Tab Riwayat menampilkan status kehadiran (Hadir / Tidak Hadir) dan tombol unduh sertifikat.

#### SCR-MHS-02: Tampilan E-Tiket Digital & Dynamic QR Code — *Akses: Mahasiswa*

- **Tujuan:** Kartu identitas sah peserta untuk masuk lokasi pada hari-H.
- **Komponen UI wajib:** Kartu tiket elegan (branding UII Event), QR Code presensi besar di tengah, nama lengkap, NIM, tanggal & jam acara, denah/lokasi gedung, tombol "Unduh E-Tiket PDF", dan tombol "Batalkan Tiket" (hanya aktif hingga H-1).
- **Interaksi keamanan:** QR Code memiliki timestamp kedaluwarsa dengan refresh otomatis untuk mencegah tiket palsu hasil screenshot.

#### SCR-MHS-03: Brankas E-Sertifikat Digital (Certificate Vault) — *Akses: Mahasiswa*

- **Tujuan:** Melihat dan mengunduh sertifikat resmi untuk portofolio dan SKPI.
- **Komponen UI wajib:** Pratinjau sertifikat resolusi tinggi, nomor seri resmi kampus (misal `UII-EVT-2026-X091`), tautan verifikasi keaslian publik, tombol "Download PDF Kualitas Cetak".
- **Validasi akses:** Tombol unduh hanya aktif jika status presensi = Hadir. Jika tidak hadir, dinonaktifkan dengan pesan: *"Sertifikat tidak diterbitkan karena peserta tidak hadir pada kegiatan."*

### 5.3 Modul Panitia LEM / UKM (Desktop-First Dashboard)

#### SCR-PAN-01: Dashboard Ringkasan Acara Panitia — *Akses: Panitia LEM / UKM*

- **Tujuan:** Pusat kendali memantau performa acara dan pendaftar.
- **Komponen UI wajib:** Kartu metrik (Total Acara, Total Pendaftar, Sisa Kuota), tabel acara organisasi (Judul, Tanggal, Status: Draf, Pending Review, Published, Selesai), tombol "+ Buat Acara Baru".

#### SCR-PAN-02: Formulir Pembuatan Acara Baru (Multi-Step Form) — *Akses: Panitia LEM / UKM*

- **Tujuan:** Memandu panitia menginput rincian acara terstruktur.
- **Langkah input:**
  - **Step 1 (Info Dasar):** Judul, kategori, tipe (Offline/Online/Hybrid), lokasi gedung/link Zoom, jadwal mulai & selesai.
  - **Step 2 (Media):** Upload poster resmi (rasio 4:5 atau 1:1, maks 2MB).
  - **Step 3 (Mode Pendaftaran):** Radio button: Peserta Acara atau Open Recruitment Panitia.
  - **Step 4 (Kuota & Form Tambahan):** Batas kuota, tanggal batas daftar, pengaturan pertanyaan khusus.
- **Tombol aksi:** "Simpan Draf" dan "Ajukan Persetujuan ke Admin Kampus".

#### SCR-PAN-03: Manajemen & Rekap Peserta Kegiatan — *Akses: Panitia LEM / UKM*

- **Tujuan:** Mengelola data seluruh peserta terdaftar dan kehadiran.
- **Komponen UI wajib:** Pencarian Nama/NIM, filter status kehadiran (Semua, Hadir, Belum Hadir, Batal), tabel pendaftar (No, NIM, Nama, Prodi, No. WhatsApp, Waktu Daftar, Status), tombol "Ekspor ke Excel/CSV", dan tombol "Buka Web Scanner".

#### SCR-PAN-04: Seleksi Oprec & Penjadwalan Wawancara — *Akses: Panitia LEM / UKM*

- **Tujuan:** Memproses berkas pelamar panitia dan menjadwalkan wawancara.
- **Komponen UI wajib:** Filter divisi (Acara, Humas, Logistik, dll), tabel pelamar (Nama, NIM, Pilihan Divisi, tombol buka berkas CV/KRS, status lamaran).
- **Aksi penjadwalan:** Input tanggal, jam, ruangan/link Meet → klik "Kirim Jadwal via WhatsApp" untuk mengirim pesan otomatis ke pelamar.

#### SCR-PAN-05: Web Scanner Presensi QR Hari-H — *Akses: Panitia LEM / UKM (Meja Registrasi)*

- **Tujuan:** Memindai tiket masuk peserta berkecepatan tinggi di pintu masuk gedung.
- **Komponen UI wajib:** Viewfinder kamera (webcam laptop/kamera HP), toggle kamera depan/belakang, counter kehadiran real-time (contoh: Hadir: 84 / 100).
- **Indikator validasi:**
  - 🟩 **HIJAU:** "VALID: [Nama Mahasiswa] - [NIM] - Presensi Tercatat!" (bip sukses).
  - 🟥 **MERAH:** "PERINGATAN: Tiket sudah pernah digunakan pada pukul 08.15 WIB!" (nada peringatan).

### 5.4 Modul Super Admin Kampus (Dewan Perwakilan Mahasiswa UII)

#### SCR-ADM-01: Kurasi & Persetujuan Acara (Approval & Auto-Post IG) — *Akses: Super Admin*

- **Tujuan:** Meninjau legalitas dan kelayakan acara sebelum dipublikasikan.
- **Komponen UI wajib:** Tabel pengajuan berstatus `PENDING_APPROVAL`, panel detail draf (poster, ormawa penanggung jawab, kuota, tujuan acara).
- **Aksi kunci:**
  - **"Approve & Publish":** Menyetujui, menayangkan ke katalog, dan memicu Auto-Post ke Instagram @uii.events via API.
  - **"Minta Revisi":** Mengembalikan draf ke panitia disertai catatan koreksi.
  - **"Tolak":** Menolak pengajuan dengan alasan resmi universitas.

#### SCR-ADM-02: Monitoring Notifikasi & Status Gateway — *Akses: Super Admin*

- **Tujuan:** Memantau keandalan pengiriman pengingat WhatsApp dan sinkronisasi Instagram.
- **Komponen UI wajib:** Widget status kuota WhatsApp Gateway API, log pengiriman pengingat (Terkirim / Gagal), log Auto-Post Instagram (URL posting terbit), tombol retry untuk pesan yang gagal akibat kendala jaringan.

---

## BAB 6: Panduan Handover Tim & Skenario Pengujian QA

### 6.1 Catatan Teknis untuk UI/UX Designer

- **Prinsip Desain Mahasiswa:** Mobile-First Responsive. Tombol aksi mengambang (sticky bottom button) pada SCR-PUB-02 dan SCR-REG-01 harus mudah ditekan satu jempol.
- **Prinsip Desain Panitia:** Desktop-First dengan tabel padat informasi (*data-dense table*) dan pagination jelas.
- **Desain State Lengkap:** Setiap layar punya 4 kondisi visual: Normal, Loading/Skeleton, Empty State (belum ada data tiket), dan Error/Validation State.

### 6.2 Catatan Teknis untuk Backend Developer

- **Integritas Anti-Double Entry:** Pasang batasan unik tingkat database:
  ```sql
  CREATE UNIQUE INDEX idx_user_event ON registrations(user_id, event_id);
  ```
- **Penanganan Kuota Concurrency:** Gunakan transaksi database berbasis baris saat pendaftaran agar tidak terjadi overselling saat diserbu ribuan mahasiswa:
  ```sql
  SELECT quota FROM events WHERE id = X FOR UPDATE;
  ```
- **Pekerja Latar Belakang (Worker/Cron):** Pisahkan mesin pengirim WhatsApp dan email dari server web utama menggunakan queue (misal Redis / RabbitMQ / BullMQ) agar pendaftaran tetap cepat dan tidak terhambat proses kirim pesan eksternal.

### 6.3 Catatan Teknis untuk Frontend Developer

- **Form Validation Cepat:** Validasi awal di sisi klien (format email `@students.uii.ac.id`, nomor WhatsApp diawali `08` atau `62`, ukuran file < 5MB) sebelum request dikirim.
- **Penanganan Web Scanner:** Gunakan library kamera barcode HTML5 (seperti `html5-qrcode` atau `zxing-js`) dengan frame rate optimal agar tidak membebani baterai perangkat panitia.

### 6.4 Skenario Pengujian Kunci untuk QA Engineer

| Kode Test | Fitur / Skenario | Langkah Pengujian | Hasil yang Diharapkan |
|---|---|---|---|
| TC-REG-01 | Validasi Pencegahan Double Entry | 1. Login Akun Mahasiswa A.<br>2. Buka Event Seminar Nasional.<br>3. Submit pendaftaran hingga sukses.<br>4. Kembali ke halaman event yang sama & klik Daftar lagi. | Pesan error "Anda sudah terdaftar di event ini" dan tombol submit nonaktif. |
| TC-REG-02 | Batas Kuota Penuh (Zero Quota) | 1. Atur sisa kuota event = 0.<br>2. Buka detail event dengan akun Mahasiswa B.<br>3. Amati tombol aksi. | Tombol berubah menjadi "Masuk Waiting List" dan status acara "Kuota Penuh". |
| TC-REM-01 | Pemicu Pengingat Otomatis H-1 | 1. Daftarkan nomor WhatsApp penguji.<br>2. Set waktu event = Besok jam 09.00 WIB.<br>3. Jalankan scheduler cron pengingat. | Pesan WhatsApp masuk ke ponsel penguji berisi rincian acara dan link tiket QR dalam < 30 detik. |
| TC-SCN-01 | Pencegahan Tiket Digunakan Berulang | 1. Buka scanner presensi panitia.<br>2. Scan QR Tiket Peserta C (sukses masuk).<br>3. Scan kembali QR yang sama untuk kedua kalinya. | Scan pertama: indikator hijau & status Hadir. Scan kedua: layar merah & peringatan "Tiket sudah pernah di-scan!" |
| TC-CRT-01 | Kunci Hak Unduh E-Sertifikat | 1. Buka menu Riwayat dengan akun Peserta D berstatus "Tidak Hadir".<br>2. Klik tombol unduh sertifikat. | Tombol tidak dapat diklik (disabled) dan muncul keterangan bahwa sertifikat hanya untuk peserta yang hadir di lokasi. |

### Pernyataan Kesiapan Sistem (Sign-Off)

Dokumen spesifikasi ini telah memenuhi seluruh kriteria kelayakan rekayasa perangkat lunak untuk diimplementasikan oleh tim pengembang UII Event Management. Seluruh anggota tim diinstruksikan berpedoman penuh pada standar ID layar, model bisnis, dan matriks hak akses yang telah ditetapkan di atas.

---
*© 2026 Tim Pengembang Sistem Informasi Kemahasiswaan • UII — Klasifikasi: Dokumen Internal Rekayasa Perangkat Lunak*
