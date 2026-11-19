<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>UII Event Management Platform — All-in-One Portal</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        uii: {
                            blue: '#0F2B5C',
                            dark: '#0A1D3F',
                            light: '#1E40AF',
                            gold: '#D97706',
                            goldlight: '#FBBF24',
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- QRCode.js CDN for dynamic QR generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .tab-btn.active {
            border-bottom: 3px solid #D97706;
            color: #0F2B5C;
            font-weight: 700;
        }
        .persona-btn.active {
            background-color: #0F2B5C;
            color: #FFFFFF;
            box-shadow: 0 4px 6px -1px rgba(15, 43, 92, 0.3);
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #F1F5F9; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col">

    <!-- ======================================================== -->
    <!-- 1. TOP HEADER & PERSONA SWITCHER BAR                     -->
    <!-- ======================================================== -->
    <header class="bg-uii-blue text-white shadow-lg sticky top-0 z-50">
        <!-- Top Info Strip -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex flex-wrap items-center justify-between border-b border-blue-900/50 text-xs">
            <div class="flex items-center space-x-3">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                    <i class="fa-solid fa-graduation-cap mr-1"></i> Universitas Islam Indonesia
                </span>
                <span class="text-blue-200 hidden sm:inline">DPM & LEM Kemahasiswaan • Single Portal Backend</span>
            </div>
            <div class="flex items-center space-x-3 sm:space-x-4 mt-1 sm:mt-0">
                <a href="/docs" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-[11px] transition shadow">
                    <i class="fa-solid fa-book-open"></i> API Docs (Swagger)
                </a>
                <div id="authStatusBadge" class="flex items-center space-x-2 bg-blue-900/60 px-2.5 py-1 rounded-full border border-blue-700/50">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span id="authStatusText" class="font-medium text-blue-100">Tamu Publik (Belum Login)</span>
                </div>
                <button onclick="handleLogout()" id="btnLogout" class="hidden text-rose-300 hover:text-white transition">
                    <i class="fa-solid fa-arrow-right-from-bracket mr-1"></i> Logout
                </button>
            </div>
        </div>

        <!-- Main Branding & Persona Quick-Switcher -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center space-x-3 cursor-pointer" onclick="switchPersona('GUEST')">
                <div class="w-10 h-10 rounded-xl bg-amber-500 flex items-center justify-center text-uii-dark font-black text-xl shadow-md shadow-amber-500/20">
                    U
                </div>
                <div>
                    <h1 class="text-lg font-extrabold tracking-tight text-white flex items-center gap-2">
                        UII Event Platform <span class="text-amber-400 text-xs px-2 py-0.5 bg-amber-400/10 rounded-full font-mono">v1.0</span>
                    </h1>
                    <p class="text-xs text-blue-200">Satu Pintu Manajemen & Publikasi Kegiatan Kampus Terpadu</p>
                </div>
            </div>

            <!-- Role Persona Quick Switcher (1-Click Switch Screen Persona) -->
            <div class="flex items-center bg-blue-950/70 p-1 rounded-xl border border-blue-800/60 text-xs font-semibold overflow-x-auto">
                <span class="text-blue-300 px-2.5 py-1 flex items-center gap-1.5 font-bold uppercase tracking-wider text-[10px]">
                    <i class="fa-solid fa-users-gear text-amber-400"></i> Mode:
                </span>
                <button onclick="switchPersona('GUEST')" id="btnPersonaGuest" class="persona-btn active px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 whitespace-nowrap">
                    <i class="fa-solid fa-globe"></i> Publik / Tamu
                </button>
                <button onclick="switchPersona('STUDENT')" id="btnPersonaStudent" class="persona-btn px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 whitespace-nowrap">
                    <i class="fa-solid fa-user-graduate"></i> Mahasiswa
                </button>
                <button onclick="switchPersona('ORGANIZER')" id="btnPersonaOrganizer" class="persona-btn px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 whitespace-nowrap">
                    <i class="fa-solid fa-clipboard-user"></i> Panitia (LEM)
                </button>
                <button onclick="switchPersona('SUPERADMIN')" id="btnPersonaAdmin" class="persona-btn px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 whitespace-nowrap">
                    <i class="fa-solid fa-shield-halved"></i> Superadmin (DPM)
                </button>
            </div>
        </div>

        <!-- Navigation Tabs Bar -->
        <nav class="bg-white border-b border-slate-200 text-slate-600 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex space-x-1 sm:space-x-4 overflow-x-auto text-xs sm:text-sm font-medium" id="navTabsContainer">
                <!-- Dynamically populated based on active persona -->
            </div>
        </nav>
    </header>

    <!-- ======================================================== -->
    <!-- 2. MAIN APPLICATION CONTENT (DYNAMIC SCREEN CONTAINER)  -->
    <!-- ======================================================== -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- Toast Notifications Floating Alert -->
        <div id="toastNotification" class="hidden fixed bottom-5 right-5 z-50 max-w-md bg-white border-l-4 rounded-xl shadow-2xl p-4 transition-all duration-300 transform translate-y-2">
            <div class="flex items-start">
                <div id="toastIcon" class="flex-shrink-0 text-xl mr-3 text-emerald-500">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="flex-grow">
                    <h4 id="toastTitle" class="text-sm font-bold text-slate-900">Operasi Berhasil</h4>
                    <p id="toastMessage" class="text-xs text-slate-600 mt-0.5 leading-relaxed">Pesan respons sistem.</p>
                </div>
                <button onclick="hideToast()" class="ml-3 text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- SCREEN 1: KATALOG ACARA PUBLIK (GUEST / ALL) -->
        <section id="screenPublicCatalog" class="screen-view">
            <!-- Hero Banner -->
            <div class="bg-gradient-to-r from-uii-blue via-blue-900 to-indigo-950 rounded-2xl p-6 sm:p-8 text-white mb-8 shadow-xl relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none text-9xl">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div class="max-w-2xl relative z-10">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-400 text-slate-950 mb-3">
                        <i class="fa-solid fa-fire mr-1.5"></i> Kalender Kegiatan Mahasiswa UII 2026
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight mb-2">Temukan Acara & Buka Potensi Prestasimu</h2>
                    <p class="text-xs sm:text-sm text-blue-200 leading-relaxed mb-6">
                        Pusat pendaftaran resmi seminar, workshop, kompetisi, dan perekrutan panitia (*oprec*) ormawa UII dengan verifikasi presensi hari-H dan e-sertifikat langsung.
                    </p>

                    <!-- Search & Filter Bar -->
                    <div class="bg-white/10 backdrop-blur-md p-2 rounded-xl flex flex-col sm:flex-row gap-2 border border-white/20">
                        <div class="relative flex-grow">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-blue-200 text-sm"></i>
                            <input type="text" id="catalogSearchInput" placeholder="Cari judul seminar, workshop, ormawa..." 
                                class="w-full bg-white/20 text-white placeholder-blue-200 text-xs sm:text-sm pl-9 pr-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-400">
                        </div>
                        <select id="catalogCategorySelect" class="bg-blue-950 text-white text-xs sm:text-sm px-3 py-2 rounded-lg border border-blue-700/60 focus:outline-none">
                            <option value="">Semua Kategori</option>
                        </select>
                        <select id="catalogActivityTypeSelect" class="bg-blue-950 text-white text-xs sm:text-sm px-3 py-2 rounded-lg border border-blue-700/60 focus:outline-none">
                            <option value="">Semua Tipe</option>
                            <option value="PARTICIPANT">Peserta Acara</option>
                            <option value="COMMITTEE_RECRUITMENT">Oprec Panitia</option>
                        </select>
                        <button onclick="loadPublicCatalog()" class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-4 py-2 rounded-lg text-xs sm:text-sm transition flex items-center justify-center gap-1.5 shadow">
                            <i class="fa-solid fa-sliders"></i> Filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Event Cards Grid Header -->
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-compass text-uii-blue"></i> Daftar Kegiatan Terverifikasi
                    </h3>
                    <p class="text-xs text-slate-500">Kegiatan yang telah disetujui resmi oleh DPM UII</p>
                </div>
                <div class="text-xs text-slate-500">
                    Menampilkan <span id="catalogCountDisplay" class="font-bold text-slate-800">0</span> kegiatan
                </div>
            </div>

            <!-- Event Grid Container -->
            <div id="catalogEventsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Dynamically loaded events -->
                <div class="col-span-full py-16 text-center text-slate-400">
                    <i class="fa-solid fa-spinner fa-spin text-3xl mb-3 text-uii-blue"></i>
                    <p class="text-sm">Memuat katalog acara terpublikasi...</p>
                </div>
            </div>
        </section>

        <!-- SCREEN 2: MAHASISWA — DOMPET E-TIKET SAYA (/my-tickets) -->
        <section id="screenStudentTickets" class="screen-view hidden">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-xs font-semibold px-2.5 py-1 bg-blue-100 text-blue-800 rounded-full">
                            <i class="fa-solid fa-ticket mr-1"></i> Dompet Tiket Mahasiswa
                        </span>
                        <h2 class="text-xl font-extrabold text-slate-900 mt-2">Daftar E-Tiket Saya</h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Tunjukkan QR Code tiket Anda ke meja panitia saat registrasi hari-H.
                        </p>
                    </div>
                    <button onclick="loadStudentTickets()" class="self-start sm:self-auto bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-right"></i> Refresh Tiket
                    </button>
                </div>
            </div>

            <div id="studentTicketsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Dynamically loaded student tickets -->
            </div>
        </section>

        <!-- SCREEN 3: MAHASISWA — RIWAYAT LAMARAN OPREC (/my-applications) -->
        <section id="screenStudentApplications" class="screen-view hidden">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-xs font-semibold px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full">
                            <i class="fa-solid fa-users-viewfinder mr-1"></i> Jalur Kepanitiaan (Oprec)
                        </span>
                        <h2 class="text-xl font-extrabold text-slate-900 mt-2">Riwayat Pendaftaran Panitia Saya</h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Pantau status seleksi berkas, jadwal wawancara, dan pengumuman kepanitiaan ormawa.
                        </p>
                    </div>
                    <button onclick="loadStudentApplications()" class="self-start sm:self-auto bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-right"></i> Refresh Status
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100/75 text-slate-700 uppercase font-semibold border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3.5">Nama Kegiatan</th>
                                <th class="px-5 py-3.5">Pilihan Divisi</th>
                                <th class="px-5 py-3.5">Waktu Mendaftar</th>
                                <th class="px-5 py-3.5">Status Seleksi</th>
                                <th class="px-5 py-3.5">Catatan / Undangan Panitia</th>
                                <th class="px-5 py-3.5 text-center">Berkas CV</th>
                            </tr>
                        </thead>
                        <tbody id="studentApplicationsTableBody" class="divide-y divide-slate-200">
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-400">
                                    <i class="fa-solid fa-spinner fa-spin text-xl mb-2"></i>
                                    <p>Memuat riwayat kepanitiaan...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- SCREEN 4: MAHASISWA — LEMARI E-SERTIFIKAT SAYA (/my-certificates) -->
        <section id="screenStudentCertificates" class="screen-view hidden">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-xs font-semibold px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full">
                            <i class="fa-solid fa-award mr-1"></i> Portofolio Prestasi Mahasiswa
                        </span>
                        <h2 class="text-xl font-extrabold text-slate-900 mt-2">Lemari E-Sertifikat Resmi</h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Sertifikat digital terbit otomatis pasca presensi hari-H dan dilengkapi hash verifikasi SHA-256 anti-pemalsuan.
                        </p>
                    </div>
                    <button onclick="loadStudentCertificates()" class="self-start sm:self-auto bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-right"></i> Refresh Sertifikat
                    </button>
                </div>
            </div>

            <div id="studentCertificatesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Dynamically loaded certificates -->
            </div>
        </section>

        <!-- SCREEN 5: PUBLIK — VERIFIKASI KEASLIAN E-SERTIFIKAT (/verify/{hash}) -->
        <section id="screenVerifyCertificate" class="screen-view hidden">
            <div class="max-w-2xl mx-auto">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-blue-100 text-uii-blue rounded-2xl flex items-center justify-center text-3xl mx-auto mb-3 shadow-inner">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h2 class="text-2xl font-extrabold text-slate-900">Verifikasi Keaslian E-Sertifikat UII</h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Masukkan kode hash kriptografis SHA-256 yang tercantum pada e-sertifikat untuk memeriksa validitas dan riwayat kehadiran resmi di UII.
                    </p>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 mb-6">
                    <div class="flex gap-2">
                        <input type="text" id="verifyHashInput" placeholder="Masukkan 64 karakter hash SHA-256 sertifikat..."
                            class="flex-grow text-xs sm:text-sm px-4 py-3 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-uii-blue font-mono">
                        <button onclick="handleVerifyCertificate()" class="bg-uii-blue hover:bg-uii-dark text-white font-bold px-6 py-3 rounded-xl text-xs sm:text-sm transition flex items-center gap-1.5 shadow">
                            <i class="fa-solid fa-check-double"></i> Verifikasi
                        </button>
                    </div>
                </div>

                <!-- Verification Result Display Box -->
                <div id="verifyResultContainer" class="hidden">
                    <!-- Populated via JS -->
                </div>
            </div>
        </section>

        <!-- SCREEN 6: PANITIA — DASHBOARD & MANAJEMEN ACARA -->
        <section id="screenOrganizerDashboard" class="screen-view hidden">
            <!-- Header Stats -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <span class="text-xs font-semibold px-2.5 py-1 bg-indigo-100 text-indigo-800 rounded-full">
                        <i class="fa-solid fa-id-badge mr-1"></i> Portal Panitia Penyelenggara
                    </span>
                    <h2 class="text-xl font-extrabold text-slate-900 mt-2">Daftar Kegiatan Ormawa / UKM Saya</h2>
                    <p class="text-xs text-slate-500 mt-1">Kelola proposal kegiatan, pantau status kurasi DPM, dan data pendaftar.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="showCreateEventModal()" class="bg-uii-blue hover:bg-uii-dark text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow">
                        <i class="fa-solid fa-plus"></i> Buat Acara Baru
                    </button>
                    <button onclick="loadOrganizerEvents()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold p-2.5 rounded-xl transition">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                </div>
            </div>

            <!-- Events Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100/75 text-slate-700 uppercase font-semibold border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3.5">Judul Kegiatan</th>
                                <th class="px-5 py-3.5">Kategori / Tipe</th>
                                <th class="px-5 py-3.5">Jadwal Acara</th>
                                <th class="px-5 py-3.5">Kuota Sisa</th>
                                <th class="px-5 py-3.5">Status Kurasi</th>
                                <th class="px-5 py-3.5 text-center">Aksi & Modul</th>
                            </tr>
                        </thead>
                        <tbody id="organizerEventsTableBody" class="divide-y divide-slate-200">
                            <!-- Dynamically loaded events -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- SCREEN 7: PANITIA — SCANNER PRESENSI HARI-H -->
        <section id="screenOrganizerScanner" class="screen-view hidden">
            <div class="max-w-2xl mx-auto">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 mb-6">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-3">
                            <i class="fa-solid fa-qrcode"></i>
                        </div>
                        <h2 class="text-xl font-extrabold text-slate-900">Pemindai Presensi QR Code Hari-H</h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Pindai QR code tiket atau tempelkan payload JSON tiket peserta untuk melakukan check-in instan dan menerbitkan e-sertifikat otomatis.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Payload / Raw String Tiket QR:</label>
                            <textarea id="scannerPayloadInput" rows="3" placeholder='{"ticket_code":"TKT-2026-XXXX","signature":"..."}' 
                                class="w-full text-xs font-mono p-3 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-uii-blue"></textarea>
                            <p class="text-[11px] text-slate-400 mt-1">
                                Atau masukkan kode tiket manual di bawah jika QR bermasalah:
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">Kode Tiket (Manual):</label>
                                <input type="text" id="scannerTicketCodeManual" placeholder="TKT-2026-..." 
                                    class="w-full text-xs p-2.5 border border-slate-300 rounded-lg font-mono uppercase">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">Signature Hash (Manual):</label>
                                <input type="text" id="scannerSignatureManual" placeholder="64 karakter hash..." 
                                    class="w-full text-xs p-2.5 border border-slate-300 rounded-lg font-mono">
                            </div>
                        </div>

                        <button onclick="handleScanCheckIn()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl text-sm transition shadow-md flex items-center justify-center gap-2">
                            <i class="fa-solid fa-check-to-slot"></i> Eksekusi Check-In Presensi
                        </button>
                    </div>
                </div>

                <!-- Live Check-In Feedback Banner -->
                <div id="scannerFeedbackBox" class="hidden"></div>
            </div>
        </section>

        <!-- SCREEN 8: SUPERADMIN (DPM UII) — KURASI PROPOSAL ACARA -->
        <section id="screenAdminCuration" class="screen-view hidden">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-xs font-semibold px-2.5 py-1 bg-purple-100 text-purple-800 rounded-full">
                            <i class="fa-solid fa-gavel mr-1"></i> Sidang Kurasi Dewan Perwakilan Mahasiswa (DPM UII)
                        </span>
                        <h2 class="text-xl font-extrabold text-slate-900 mt-2">Daftar Pengajuan Kegiatan Ormawa Kampus</h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Review proposal kegiatan mahasiswa, validasi kuota & anggaran, lalu setujui (otomatis terbit & auto-post ke Instagram kampus) atau tolak dengan alasan resmi.
                        </p>
                    </div>
                    <button onclick="loadAdminCurationList()" class="self-start sm:self-auto bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-right"></i> Refresh Antrean
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100/75 text-slate-700 uppercase font-semibold border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3.5">Nama Acara & Ormawa</th>
                                <th class="px-5 py-3.5">Kategori / Tipe</th>
                                <th class="px-5 py-3.5">Rencana Jadwal & Lokasi</th>
                                <th class="px-5 py-3.5">Total Kuota</th>
                                <th class="px-5 py-3.5">Status Pengajuan</th>
                                <th class="px-5 py-3.5 text-center">Keputusan DPM</th>
                            </tr>
                        </thead>
                        <tbody id="adminCurationTableBody" class="divide-y divide-slate-200">
                            <!-- Dynamically loaded events -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </main>

    <!-- ======================================================== -->
    <!-- 3. MODAL DIALOGS (EVENT DETAIL, REGISTRATION, RECAP)    -->
    <!-- ======================================================== -->

    <!-- MODAL: DETAIL ACARA & AKSI PENDAFTARAN -->
    <div id="modalEventDetail" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200">
            <div class="relative">
                <img id="modalDetailPoster" src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800" 
                    onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800';"
                    alt="Poster" class="w-full h-48 sm:h-64 object-cover">
                <button onclick="closeModal('modalEventDetail')" class="absolute top-4 right-4 bg-slate-950/70 hover:bg-slate-950 text-white w-9 h-9 rounded-full flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div class="absolute bottom-3 left-4">
                    <span id="modalDetailCategory" class="px-2.5 py-1 rounded-md text-xs font-bold bg-amber-400 text-slate-950 shadow">Kategori</span>
                </div>
            </div>

            <div class="p-6">
                <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                    <i class="fa-solid fa-building-columns text-uii-blue"></i>
                    <span id="modalDetailOrganizer">Penyelenggara</span>
                </div>
                <h3 id="modalDetailTitle" class="text-xl font-extrabold text-slate-900 mb-3">Judul Acara</h3>

                <!-- Key Metrics Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs mb-4">
                    <div>
                        <span class="text-slate-400 block text-[10px] font-semibold uppercase">Jadwal Mulai</span>
                        <span id="modalDetailStartTime" class="font-bold text-slate-800">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] font-semibold uppercase">Lokasi Acara</span>
                        <span id="modalDetailLocation" class="font-bold text-slate-800">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] font-semibold uppercase">Sisa Kuota</span>
                        <span id="modalDetailQuota" class="font-bold text-emerald-600">-</span>
                    </div>
                </div>

                <div class="mb-6">
                    <h4 class="text-xs font-bold text-slate-700 uppercase mb-1">Deskripsi Kegiatan:</h4>
                    <p id="modalDetailDescription" class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">-</p>
                </div>

                <!-- Division list if recruitment -->
                <div id="modalDetailDivisionsContainer" class="hidden mb-6">
                    <h4 class="text-xs font-bold text-slate-700 uppercase mb-2">Divisi Kepanitiaan yang Dibuka:</h4>
                    <div id="modalDetailDivisionsList" class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs"></div>
                </div>

                <!-- Action Button in Detail Modal -->
                <div id="modalDetailActionContainer" class="border-t border-slate-100 pt-4 flex justify-end gap-2">
                    <!-- Populated dynamically based on role and event type -->
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: REGISTRASI PESERTA MAHASISWA -->
    <div id="modalRegisterParticipant" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between mb-4 border-b pb-3">
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-ticket text-uii-blue"></i> Formulir Pendaftaran Peserta
                </h3>
                <button onclick="closeModal('modalRegisterParticipant')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form id="formRegisterParticipant" onsubmit="submitParticipantRegistration(event)">
                <input type="hidden" id="regEventId">
                <div class="space-y-4 text-xs">
                    <div class="bg-blue-50 p-3 rounded-xl border border-blue-100 text-blue-900">
                        <p class="font-bold mb-1" id="regEventTitleDisplay">Judul Acara</p>
                        <p class="text-[11px] text-blue-700">Tiket digital dengan tanda tangan QR HMAC-SHA256 akan otomatis diterbitkan pasca pendaftaran.</p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Motivasi Mengikuti Kegiatan / Catatan Khusus:</label>
                        <textarea id="regCustomMotivation" rows="2" placeholder="Contoh: Ingin memperdalam wawasan tentang Artificial Intelligence..."
                            class="w-full p-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-uii-blue"></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kebutuhan Diet / Konsumsi Khusus (Opsional):</label>
                        <input type="text" id="regCustomDiet" placeholder="Contoh: Vegetarian / Tidak ada alergi"
                            class="w-full p-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-uii-blue">
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button" onclick="closeModal('modalRegisterParticipant')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold">
                            Batal
                        </button>
                        <button type="submit" id="btnSubmitParticipantReg" class="px-5 py-2 bg-uii-blue hover:bg-uii-dark text-white rounded-lg font-bold flex items-center gap-1.5 shadow">
                            <i class="fa-solid fa-paper-plane"></i> Daftarkan Saya
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: PENDAFTARAN OPREC PANITIA MAHASISWA -->
    <div id="modalApplyRecruitment" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between mb-4 border-b pb-3">
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-users-viewfinder text-amber-600"></i> Pendaftaran Seleksi Panitia (Oprec)
                </h3>
                <button onclick="closeModal('modalApplyRecruitment')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form id="formApplyRecruitment" onsubmit="submitRecruitmentApplication(event)">
                <input type="hidden" id="recEventId">
                <div class="space-y-4 text-xs">
                    <div class="bg-amber-50 p-3 rounded-xl border border-amber-200 text-amber-900">
                        <p class="font-bold mb-1" id="recEventTitleDisplay">Judul Acara Oprec</p>
                        <p class="text-[11px] text-amber-700">Pastikan berkas CV yang diunggah berekstensi PDF murni (diverifikasi dengan header magic bytes).</p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pilihan Divisi Utama (Wajib):</label>
                        <select id="recDivisionPref1" required class="w-full p-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                            <option value="">-- Pilih Divisi Utama --</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pilihan Divisi Cadangan (Opsional):</label>
                        <select id="recDivisionPref2" class="w-full p-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                            <option value="">-- Tidak Memilih Cadangan --</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Unggah Berkas CV / Portofolio (PDF Maks. 2MB):</label>
                        <input type="file" id="recCvFileInput" accept=".pdf,application/pdf" required
                            class="w-full p-2 border border-slate-300 rounded-lg file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pengalaman Organisasi / Keahlian Relevan:</label>
                        <textarea id="recExperienceInput" rows="2" placeholder="Sebutkan kepanitiaan atau keahlian yang pernah Anda ikuti..."
                            class="w-full p-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea>
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button" onclick="closeModal('modalApplyRecruitment')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold">
                            Batal
                        </button>
                        <button type="submit" id="btnSubmitRecruitment" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 rounded-lg font-bold flex items-center gap-1.5 shadow">
                            <i class="fa-solid fa-upload"></i> Kirim Lamaran Oprec
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: BUAT / EDIT EVENT OLEH PANITIA -->
    <div id="modalCreateEvent" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between mb-4 border-b pb-3">
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-calendar-plus text-uii-blue"></i> Formulir Pengajuan Kegiatan Ormawa Baru
                </h3>
                <button onclick="closeModal('modalCreateEvent')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form id="formCreateEvent" onsubmit="submitCreateEvent(event)">
                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Judul Kegiatan:</label>
                        <input type="text" id="evTitle" required placeholder="Contoh: Seminar Nasional AI & Karir Teknologi 2026"
                            class="w-full p-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-uii-blue">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kategori Kegiatan:</label>
                            <select id="evCategory" required class="w-full p-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-uii-blue bg-white">
                                <!-- Populated dynamically -->
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tipe Kegiatan:</label>
                            <select id="evActivityType" required onchange="toggleDivisionsBuilder(this.value)" class="w-full p-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-uii-blue bg-white">
                                <option value="PARTICIPANT_EVENT">Peserta Acara (Tiket)</option>
                                <option value="COMMITTEE_RECRUITMENT">Perekrutan Panitia (Oprec)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Waktu Mulai:</label>
                            <input type="datetime-local" id="evStartTime" required class="w-full p-2.5 border border-slate-300 rounded-lg focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Waktu Selesai:</label>
                            <input type="datetime-local" id="evEndTime" required class="w-full p-2.5 border border-slate-300 rounded-lg focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Lokasi Kegiatan:</label>
                            <input type="text" id="evLocation" required placeholder="Contoh: Auditorium FTI UII Lt. 3" class="w-full p-2.5 border border-slate-300 rounded-lg focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Total Kuota:</label>
                            <input type="number" id="evQuota" required min="1" placeholder="Contoh: 150" class="w-full p-2.5 border border-slate-300 rounded-lg focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block font-bold text-slate-700">Gambar Poster Acara:</label>
                            <div class="inline-flex rounded-lg border border-slate-200 p-0.5 bg-slate-100 text-[11px]">
                                <button type="button" id="btnPosterModeUpload" onclick="setPosterInputMode('upload')" class="px-2.5 py-1 rounded-md font-semibold bg-white text-uii-blue shadow-sm">
                                    <i class="fa-solid fa-cloud-arrow-up mr-1"></i> Unggah Berkas
                                </button>
                                <button type="button" id="btnPosterModeUrl" onclick="setPosterInputMode('url')" class="px-2.5 py-1 rounded-md font-semibold text-slate-600 hover:text-slate-900">
                                    <i class="fa-solid fa-link mr-1"></i> Tautan URL
                                </button>
                            </div>
                        </div>

                        <!-- Opsi 1: Unggah Berkas Gambar Langsung -->
                        <div id="posterUploadContainer" class="border-2 border-dashed border-slate-300 hover:border-uii-blue rounded-xl p-3.5 transition bg-slate-50/50">
                            <div class="flex flex-col sm:flex-row items-center gap-3.5">
                                <div id="posterPreviewBox" class="w-24 h-24 rounded-lg bg-slate-200 border border-slate-300 overflow-hidden flex items-center justify-center flex-shrink-0 relative">
                                    <img id="posterPreviewImg" src="" alt="Pratinjau Poster" class="w-full h-full object-cover hidden">
                                    <span id="posterPreviewPlaceholder" class="text-slate-400 text-[11px] text-center px-1 flex flex-col items-center">
                                        <i class="fa-regular fa-image text-2xl mb-1 text-slate-400"></i>
                                        Pratinjau
                                    </span>
                                </div>
                                <div class="flex-grow text-center sm:text-left">
                                    <label for="evPosterFile" class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-uii-blue hover:bg-uii-dark text-white rounded-lg text-xs font-bold transition shadow-sm">
                                        <i class="fa-solid fa-upload"></i> Pilih Gambar dari Komputer
                                    </label>
                                    <input type="file" id="evPosterFile" accept="image/png,image/jpeg,image/jpg,image/webp" class="hidden" onchange="handlePosterFileChange(event)">
                                    <p class="text-[11px] text-slate-500 mt-1.5">Format: JPG, PNG, WebP (Maksimal 5MB).</p>
                                    <span id="evPosterFileName" class="text-[11px] font-mono text-uii-blue font-semibold block truncate mt-1"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Opsi 2: Input Tautan URL Eksternal -->
                        <div id="posterUrlContainer" class="hidden">
                            <input type="url" id="evPosterUrl" placeholder="https://images.unsplash.com/..." class="w-full p-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-uii-blue text-xs">
                            <p class="text-[11px] text-slate-400 mt-1">Gunakan URL gambar publik (misal: Unsplash atau CDN kampus)</p>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Deskripsi Lengkap Kegiatan:</label>
                        <textarea id="evDescription" rows="3" required placeholder="Jelaskan tujuan, pembicara, dan rangkaian kegiatan..." class="w-full p-2.5 border border-slate-300 rounded-lg focus:outline-none"></textarea>
                    </div>

                    <!-- Divisi Builder for Committee Recruitment -->
                    <div id="evDivisionsSection" class="hidden bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-slate-800">Daftar Divisi Kepanitiaan:</span>
                            <button type="button" onclick="addDivisionRow()" class="text-[11px] bg-uii-blue text-white px-2.5 py-1 rounded-md font-semibold">
                                + Tambah Divisi
                            </button>
                        </div>
                        <div id="evDivisionsListContainer" class="space-y-2"></div>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" id="evAutoPostIg" checked class="w-4 h-4 rounded text-uii-blue">
                        <label for="evAutoPostIg" class="font-medium text-slate-700">Auto-Post poster ke Instagram Resmi Kampus saat disetujui DPM</label>
                    </div>

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" onclick="closeModal('modalCreateEvent')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold">
                            Batal
                        </button>
                        <button type="submit" id="btnSubmitCreateEvent" class="px-5 py-2 bg-uii-blue hover:bg-uii-dark text-white rounded-lg font-bold flex items-center gap-1.5 shadow">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Draf Acara
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: REKAPITULASI & STATISTIK PANITIA -->
    <div id="modalEventRecap" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between mb-4 border-b pb-3">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-chart-pie text-uii-blue"></i> Rekapitulasi & Laporan Kegiatan
                    </h3>
                    <p id="recapEventTitle" class="text-xs text-slate-500 mt-0.5">Judul Acara</p>
                </div>
                <button onclick="closeModal('modalEventRecap')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div id="recapModalBody" class="space-y-6 text-xs">
                <!-- Dynamically loaded metrics and buttons -->
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- 4. FOOTER                                                -->
    <!-- ======================================================== -->
    <footer class="bg-slate-900 text-slate-400 text-xs py-8 mt-auto border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg bg-amber-500 text-slate-950 font-black flex items-center justify-center">U</div>
                <div>
                    <p class="font-bold text-slate-200">UII Event Management Platform</p>
                    <p class="text-[11px] text-slate-500">Badan Sistem Informasi (BSI) & Dewan Perwakilan Mahasiswa (DPM) UII</p>
                </div>
            </div>
            <div class="text-center sm:text-right text-[11px]">
                <p>Dikembangkan oleh <span class="text-amber-400 font-semibold">Danendra Farrel Adriansyah</span></p>
                <p class="text-slate-500 mt-0.5">Arsitektur Backend: Laravel 12 • PostgreSQL 15+ • Sanctum • HMAC QR Code</p>
            </div>
        </div>
    </footer>

    <!-- ======================================================== -->
    <!-- 5. JAVASCRIPT STATE ENGINE & REST API CLIENT             -->
    <!-- ======================================================== -->
    <script>
        // State Management
        const state = {
            currentRole: 'GUEST', // 'GUEST', 'STUDENT', 'ORGANIZER', 'SUPERADMIN'
            token: localStorage.getItem('uii_token') || null,
            user: JSON.parse(localStorage.getItem('uii_user') || 'null'),
            activeTab: 'catalog',
            categories: [],
            eventsCache: [],
            currentEventInModal: null,
        };

        // Standard Demo Accounts Credentials for 1-Click Persona Switch
        const DEMO_ACCOUNTS = {
            STUDENT: { email: '21523001@students.uii.ac.id', password: 'Password123!', label: 'Danendra Farrel Adriansyah (Mahasiswa)' },
            ORGANIZER: { email: 'lemfti@uii.ac.id', password: 'Password123!', label: 'LEM FTI UII (Panitia Acara)' },
            SUPERADMIN: { email: 'admin.dpm@uii.ac.id', password: 'Password123!', label: 'DPM UII (Super Admin)' },
        };

        // Navigation Tabs Definition per Role
        const ROLE_TABS = {
            GUEST: [
                { id: 'catalog', label: 'Katalog Kegiatan', icon: 'fa-compass', screen: 'screenPublicCatalog' },
                { id: 'verify', label: 'Verifikasi Sertifikat', icon: 'fa-shield-halved', screen: 'screenVerifyCertificate' },
            ],
            STUDENT: [
                { id: 'catalog', label: 'Katalog Acara', icon: 'fa-compass', screen: 'screenPublicCatalog' },
                { id: 'my-tickets', label: 'E-Tiket Saya', icon: 'fa-ticket', screen: 'screenStudentTickets' },
                { id: 'my-applications', label: 'Lamaran Oprec', icon: 'fa-users-viewfinder', screen: 'screenStudentApplications' },
                { id: 'my-certificates', label: 'Lemari Sertifikat', icon: 'fa-award', screen: 'screenStudentCertificates' },
                { id: 'verify', label: 'Verifikasi Sertifikat', icon: 'fa-shield-halved', screen: 'screenVerifyCertificate' },
            ],
            ORGANIZER: [
                { id: 'org-events', label: 'Dashboard Acara Panitia', icon: 'fa-list-check', screen: 'screenOrganizerDashboard' },
                { id: 'catalog', label: 'Katalog Publik', icon: 'fa-compass', screen: 'screenPublicCatalog' },
                { id: 'scanner', label: 'Pemindai Presensi QR', icon: 'fa-qrcode', screen: 'screenOrganizerScanner' },
                { id: 'verify', label: 'Verifikasi Sertifikat', icon: 'fa-shield-halved', screen: 'screenVerifyCertificate' },
            ],
            SUPERADMIN: [
                { id: 'admin-curation', label: 'Kurasi Proposal DPM', icon: 'fa-gavel', screen: 'screenAdminCuration' },
                { id: 'org-events', label: 'Kegiatan Ormawa', icon: 'fa-calendar-days', screen: 'screenOrganizerDashboard' },
                { id: 'catalog', label: 'Katalog Publik', icon: 'fa-compass', screen: 'screenPublicCatalog' },
                { id: 'scanner', label: 'Pemindai Presensi QR', icon: 'fa-qrcode', screen: 'screenOrganizerScanner' },
                { id: 'verify', label: 'Verifikasi Sertifikat', icon: 'fa-shield-halved', screen: 'screenVerifyCertificate' },
            ]
        };

        function highlightPersonaButton(role) {
            document.querySelectorAll('.persona-btn').forEach(btn => btn.classList.remove('active'));
            if (role === 'GUEST') document.getElementById('btnPersonaGuest')?.classList.add('active');
            if (role === 'STUDENT') document.getElementById('btnPersonaStudent')?.classList.add('active');
            if (role === 'ORGANIZER') document.getElementById('btnPersonaOrganizer')?.classList.add('active');
            if (role === 'SUPERADMIN') document.getElementById('btnPersonaAdmin')?.classList.add('active');
        }

        // Initialize App on DOM Ready
        document.addEventListener('DOMContentLoaded', async () => {
            if (state.token && state.user) {
                const userRole = (state.user.roles && state.user.roles.length > 0) 
                    ? state.user.roles[0].name 
                    : (state.user.role || 'GUEST');
                state.currentRole = userRole;
                highlightPersonaButton(userRole);
            } else {
                state.currentRole = 'GUEST';
                highlightPersonaButton('GUEST');
            }
            updateAuthStatusBadge();
            renderNavTabs();
            await fetchCategories();

            // Buka tab awal yang sesuai dengan role aktif
            const tabs = ROLE_TABS[state.currentRole] || ROLE_TABS.GUEST;
            const initialTab = tabs[0]?.id || 'catalog';
            switchTab(initialTab);
        });

        // ----------------------------------------------------
        // Persona Switcher Engine (Seamless 1-Click Login)
        // ----------------------------------------------------
        async function switchPersona(role) {
            highlightPersonaButton(role);

            if (role === 'GUEST') {
                state.currentRole = 'GUEST';
                state.token = null;
                state.user = null;
                localStorage.removeItem('uii_token');
                localStorage.removeItem('uii_user');
                highlightPersonaButton('GUEST');
                updateAuthStatusBadge();
                renderNavTabs();
                switchTab('catalog');
                showToast('Beralih ke mode Tamu Publik', 'info');
                return;
            }

            // Perform automatic silent login for the selected demo account
            const creds = DEMO_ACCOUNTS[role];
            try {
                showToast(`Mengautentikasi sebagai ${creds.label}...`, 'info');
                const res = await fetch('/api/v1/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: creds.email, password: creds.password })
                });
                const json = await res.json();
                if (res.ok && json.data) {
                    state.token = json.data.token;
                    state.user = json.data.user;

                    const resolvedRole = (json.data.user.roles && json.data.user.roles.length > 0)
                        ? json.data.user.roles[0].name
                        : (json.data.user.role || role);
                    const resolvedLabel = (json.data.user.roles && json.data.user.roles.length > 0)
                        ? (json.data.user.roles[0].label || json.data.user.roles[0].name)
                        : (json.data.user.role_label || resolvedRole);

                    state.currentRole = resolvedRole;
                    localStorage.setItem('uii_token', state.token);
                    localStorage.setItem('uii_user', JSON.stringify(state.user));

                    highlightPersonaButton(resolvedRole);
                    updateAuthStatusBadge();
                    renderNavTabs();

                    // Switch to default screen for that role
                    if (resolvedRole === 'STUDENT') switchTab('catalog');
                    if (resolvedRole === 'ORGANIZER') switchTab('org-events');
                    if (resolvedRole === 'SUPERADMIN') switchTab('admin-curation');

                    showToast(`Berhasil login sebagai ${json.data.user.name} (${resolvedLabel})`, 'success');
                } else {
                    showToast(json.message || 'Gagal login demo persona.', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Gagal menghubungi API autentikasi.', 'error');
            }
        }

        function handleLogout() {
            switchPersona('GUEST');
        }

        function updateAuthStatusBadge() {
            const badge = document.getElementById('authStatusText');
            const btnLogout = document.getElementById('btnLogout');
            if (state.token && state.user) {
                const roleLabel = (state.user.roles && state.user.roles.length > 0)
                    ? (state.user.roles[0].label || state.user.roles[0].name)
                    : (state.user.role_label || state.user.role || state.currentRole || 'Pengguna');
                badge.innerText = `${state.user.name} (${roleLabel})`;
                btnLogout.classList.remove('hidden');
            } else {
                badge.innerText = 'Tamu Publik (Belum Login)';
                btnLogout.classList.add('hidden');
            }
        }

        // ----------------------------------------------------
        // Dynamic Navigation Tab Rendering
        // ----------------------------------------------------
        function renderNavTabs() {
            const container = document.getElementById('navTabsContainer');
            const tabs = ROLE_TABS[state.currentRole] || ROLE_TABS.GUEST;
            container.innerHTML = tabs.map((tab, idx) => `
                <button onclick="switchTab('${tab.id}')" id="tabBtn_${tab.id}" 
                    class="tab-btn ${idx === 0 ? 'active' : ''} px-3 py-3 border-b-2 border-transparent hover:text-uii-blue transition flex items-center gap-1.5 whitespace-nowrap">
                    <i class="fa-solid ${tab.icon}"></i> ${tab.label}
                </button>
            `).join('');
        }

        function switchTab(tabId) {
            state.activeTab = tabId;
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            const activeBtn = document.getElementById(`tabBtn_${tabId}`);
            if (activeBtn) activeBtn.classList.add('active');

            // Hide all screens
            document.querySelectorAll('.screen-view').forEach(screen => screen.classList.add('hidden'));

            // Show target screen
            const tabs = ROLE_TABS[state.currentRole] || ROLE_TABS.GUEST;
            const target = tabs.find(t => t.id === tabId) || ROLE_TABS.GUEST[0];
            const screenEl = document.getElementById(target.screen);
            if (screenEl) screenEl.classList.remove('hidden');

            // Trigger data loader according to screen
            if (tabId === 'catalog') loadPublicCatalog();
            if (tabId === 'my-tickets') loadStudentTickets();
            if (tabId === 'my-applications') loadStudentApplications();
            if (tabId === 'my-certificates') loadStudentCertificates();
            if (tabId === 'org-events') loadOrganizerEvents();
            if (tabId === 'admin-curation') loadAdminCurationList();
        }

        // ----------------------------------------------------
        // API FETCH UTILITY WITH BEARER AUTH
        // ----------------------------------------------------
        async function apiFetch(endpoint, options = {}) {
            const headers = {
                'Accept': 'application/json',
                ...(options.headers || {})
            };
            if (state.token) {
                headers['Authorization'] = `Bearer ${state.token}`;
            }
            if (options.body && !(options.body instanceof FormData)) {
                headers['Content-Type'] = 'application/json';
            }
            return fetch(endpoint, { ...options, headers });
        }

        // ----------------------------------------------------
        // SCREEN 1: KATALOG ACARA PUBLIK
        // ----------------------------------------------------
        async function fetchCategories() {
            try {
                const res = await apiFetch('/api/v1/categories');
                const json = await res.json();
                if (res.ok && json.data) {
                    state.categories = json.data;
                    const catSelect = document.getElementById('catalogCategorySelect');
                    const evCatSelect = document.getElementById('evCategory');
                    const opts = json.data.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
                    if (catSelect) catSelect.innerHTML = '<option value="">Semua Kategori</option>' + opts;
                    if (evCatSelect) evCatSelect.innerHTML = opts;
                }
            } catch (e) { console.error('Categories load error', e); }
        }

        async function loadPublicCatalog() {
            const grid = document.getElementById('catalogEventsGrid');
            const search = document.getElementById('catalogSearchInput')?.value || '';
            const category = document.getElementById('catalogCategorySelect')?.value || '';
            const actType = document.getElementById('catalogActivityTypeSelect')?.value || '';

            grid.innerHTML = `
                <div class="col-span-full py-12 text-center text-slate-400">
                    <i class="fa-solid fa-spinner fa-spin text-2xl mb-2 text-uii-blue"></i>
                    <p class="text-xs">Memuat katalog acara...</p>
                </div>
            `;

            try {
                let url = `/api/v1/events?per_page=20`;
                if (search) url += `&search=${encodeURIComponent(search)}`;
                if (category) url += `&category_id=${encodeURIComponent(category)}`;
                if (actType) url += `&activity_type=${encodeURIComponent(actType)}`;

                const res = await apiFetch(url);
                const json = await res.json();

                if (res.ok && json.data) {
                    state.eventsCache = json.data;
                    document.getElementById('catalogCountDisplay').innerText = json.data.length;

                    if (json.data.length === 0) {
                        grid.innerHTML = `
                            <div class="col-span-full py-16 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
                                <i class="fa-solid fa-calendar-xmark text-4xl mb-3 text-slate-300"></i>
                                <p class="text-sm font-semibold text-slate-600">Tidak ada kegiatan yang cocok dengan kriteria pencarian.</p>
                                <p class="text-xs text-slate-400 mt-1">Coba sesuaikan filter atau kata kunci pencarian Anda.</p>
                            </div>
                        `;
                        return;
                    }

                    grid.innerHTML = json.data.map(ev => {
                        const isOprec = ev.activity_type === 'COMMITTEE_RECRUITMENT';
                        const startDate = ev.start_time ? new Date(ev.start_time).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
                        return `
                            <div class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition border border-slate-200 flex flex-col">
                                <div class="relative h-44 overflow-hidden bg-slate-100">
                                    <img src="${ev.poster_url || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600'}" 
                                        alt="${ev.title}" 
                                        onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600';"
                                        class="w-full h-full object-cover transition duration-300 hover:scale-105">
                                    <div class="absolute top-3 left-3">
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-400 text-slate-950 shadow">
                                            ${(typeof ev.category === 'object' ? ev.category?.name : ev.category) || 'Kegiatan'}
                                        </span>
                                    </div>
                                    <div class="absolute top-3 right-3">
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold ${isOprec ? 'bg-purple-600 text-white' : 'bg-emerald-600 text-white'} shadow">
                                            ${isOprec ? 'Oprec Panitia' : 'Peserta'}
                                        </span>
                                    </div>
                                </div>
                                <div class="p-5 flex-grow flex flex-col justify-between">
                                    <div>
                                        <p class="text-[11px] font-semibold text-uii-blue flex items-center gap-1.5 mb-1">
                                            <i class="fa-solid fa-building-columns"></i> ${ev.organizer_name || (typeof ev.organizer === 'object' ? ev.organizer?.name : ev.organizer) || 'Ormawa UII'}
                                        </p>
                                        <h4 class="text-base font-bold text-slate-900 leading-snug line-clamp-2 mb-2">${ev.title}</h4>
                                        <p class="text-xs text-slate-500 line-clamp-2 mb-4">${ev.description || 'Tidak ada deskripsi.'}</p>
                                    </div>
                                    <div>
                                        <div class="border-t border-slate-100 pt-3 flex items-center justify-between text-xs text-slate-500 mb-4">
                                            <span><i class="fa-regular fa-calendar mr-1"></i> ${startDate}</span>
                                            <span class="font-bold ${ev.quota_remaining <= 5 ? 'text-rose-600' : 'text-emerald-600'}">
                                                <i class="fa-solid fa-chair mr-1"></i> Sisa: ${ev.quota_remaining}/${ev.quota_total}
                                            </span>
                                        </div>
                                        <button onclick="openEventDetailModal('${ev.slug}')" 
                                            class="w-full bg-uii-blue hover:bg-uii-dark text-white font-bold py-2.5 rounded-xl text-xs transition shadow flex items-center justify-center gap-1.5">
                                            <i class="fa-solid fa-circle-info"></i> Detail & Pendaftaran
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                } else {
                    const msg = (json && json.message) ? json.message : 'Gagal memuat katalog kegiatan publik.';
                    grid.innerHTML = `<div class="col-span-full py-10 text-center text-rose-500 text-xs font-semibold"><i class="fa-solid fa-triangle-exclamation mr-1"></i> ${msg}</div>`;
                }
            } catch (err) {
                console.error(err);
                grid.innerHTML = `<div class="col-span-full py-10 text-center text-rose-500 text-xs">Gagal memuat katalog acara.</div>`;
            }
        }

        // ----------------------------------------------------
        // DETAIL MODAL & REGISTRATION ACTION
        // ----------------------------------------------------
        async function openEventDetailModal(slug) {
            try {
                const res = await apiFetch(`/api/v1/events/${slug}`);
                const json = await res.json();
                if (!res.ok || !json.data) {
                    showToast('Gagal memuat detail acara.', 'error');
                    return;
                }

                const ev = json.data;
                state.currentEventInModal = ev;

                document.getElementById('modalDetailPoster').src = ev.poster_url || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800';
                document.getElementById('modalDetailCategory').innerText = (typeof ev.category === 'object' ? ev.category?.name : ev.category) || 'Kegiatan';
                document.getElementById('modalDetailOrganizer').innerText = ev.organizer_name || (typeof ev.organizer === 'object' ? ev.organizer?.name : ev.organizer) || 'Ormawa UII';
                document.getElementById('modalDetailTitle').innerText = ev.title;
                document.getElementById('modalDetailLocation').innerText = ev.location || 'Daring / Online';
                document.getElementById('modalDetailQuota').innerText = `${ev.quota_remaining} / ${ev.quota_total} Kursi`;
                document.getElementById('modalDetailDescription').innerText = ev.description || 'Tidak ada deskripsi.';

                const startTimeFormatted = ev.start_time ? new Date(ev.start_time).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '-';
                document.getElementById('modalDetailStartTime').innerText = startTimeFormatted;

                // Divisions container for recruitment
                const divContainer = document.getElementById('modalDetailDivisionsContainer');
                const divList = document.getElementById('modalDetailDivisionsList');
                if (ev.activity_type === 'COMMITTEE_RECRUITMENT' && ev.divisions && ev.divisions.length > 0) {
                    divContainer.classList.remove('hidden');
                    divList.innerHTML = ev.divisions.map(d => `
                        <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                            <span class="font-bold text-slate-800 block">${d.name}</span>
                            <span class="text-[11px] text-slate-500">Kuota Divisi: ${d.quota} orang</span>
                        </div>
                    `).join('');
                } else {
                    divContainer.classList.add('hidden');
                }

                // Render Action Button
                const actionContainer = document.getElementById('modalDetailActionContainer');
                const isStudent = state.currentRole === 'STUDENT';
                const isRecruitment = ev.activity_type === 'COMMITTEE_RECRUITMENT';

                if (!state.token) {
                    actionContainer.innerHTML = `
                        <button onclick="switchPersona('STUDENT'); openEventDetailModal('${ev.slug}')" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-xl text-xs transition shadow flex items-center gap-1.5">
                            <i class="fa-solid fa-right-to-bracket"></i> Login Mahasiswa untuk Mendaftar
                        </button>
                    `;
                } else if (!isStudent) {
                    actionContainer.innerHTML = `
                        <span class="text-xs text-slate-500 italic flex items-center gap-1">
                            <i class="fa-solid fa-lock"></i> Pendaftaran hanya dapat dilakukan oleh akun peran Mahasiswa.
                        </span>
                    `;
                } else if (ev.quota_remaining <= 0) {
                    actionContainer.innerHTML = `
                        <button disabled class="px-5 py-2.5 bg-slate-300 text-slate-500 font-bold rounded-xl text-xs cursor-not-allowed">
                            <i class="fa-solid fa-ban mr-1"></i> Kuota Pendaftaran Habis
                        </button>
                    `;
                } else if (isRecruitment) {
                    actionContainer.innerHTML = `
                        <button onclick="openApplyRecruitmentModal('${ev.id}')" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl text-xs transition shadow flex items-center gap-1.5">
                            <i class="fa-solid fa-users-viewfinder"></i> Lamar Sebagai Calon Panitia
                        </button>
                    `;
                } else {
                    actionContainer.innerHTML = `
                        <button onclick="openRegisterParticipantModal('${ev.id}')" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition shadow flex items-center gap-1.5">
                            <i class="fa-solid fa-ticket"></i> Daftar Peserta & Dapatkan Tiket
                        </button>
                    `;
                }

                openModal('modalEventDetail');
            } catch (err) {
                console.error(err);
                showToast('Gagal memuat modal acara.', 'error');
            }
        }

        // Open Register Participant Modal
        function openRegisterParticipantModal(eventId) {
            closeModal('modalEventDetail');
            const ev = state.currentEventInModal;
            document.getElementById('regEventId').value = eventId;
            document.getElementById('regEventTitleDisplay').innerText = ev.title;
            openModal('modalRegisterParticipant');
        }

        async function submitParticipantRegistration(e) {
            e.preventDefault();
            const eventId = document.getElementById('regEventId').value;
            const motivation = document.getElementById('regCustomMotivation').value;
            const diet = document.getElementById('regCustomDiet').value;
            const btn = document.getElementById('btnSubmitParticipantReg');

            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-1"></i> Mendaftarkan...`;

            try {
                const res = await apiFetch(`/api/v1/events/${eventId}/register`, {
                    method: 'POST',
                    body: JSON.stringify({
                        custom_answers: {
                            motivasi: motivation || '-',
                            kebutuhan_khusus: diet || 'Tidak ada',
                        }
                    })
                });
                const json = await res.json();

                if (res.ok) {
                    closeModal('modalRegisterParticipant');
                    showToast('Selamat! Anda resmi terdaftar. Tiket digital telah diterbitkan.', 'success');
                    switchTab('my-tickets');
                } else {
                    showToast(json.message || 'Gagal mendaftar kegiatan.', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Terjadi kesalahan jaringan saat mendaftar.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = `<i class="fa-solid fa-paper-plane mr-1"></i> Daftarkan Saya`;
            }
        }

        // Open Apply Recruitment Modal
        function openApplyRecruitmentModal(eventId) {
            closeModal('modalEventDetail');
            const ev = state.currentEventInModal;
            document.getElementById('recEventId').value = eventId;
            document.getElementById('recEventTitleDisplay').innerText = ev.title;

            // Populate Division options
            const pref1 = document.getElementById('recDivisionPref1');
            const pref2 = document.getElementById('recDivisionPref2');
            const divs = ev.divisions || [];

            const opts1 = divs.map(d => `<option value="${d.name}">${d.name} (Kuota: ${d.quota})</option>`).join('');
            const opts2 = divs.map(d => `<option value="${d.name}">${d.name}</option>`).join('');

            pref1.innerHTML = '<option value="">-- Pilih Divisi Utama --</option>' + opts1;
            pref2.innerHTML = '<option value="">-- Tidak Memilih Cadangan --</option>' + opts2;

            openModal('modalApplyRecruitment');
        }

        async function submitRecruitmentApplication(e) {
            e.preventDefault();
            const eventId = document.getElementById('recEventId').value;
            const div1 = document.getElementById('recDivisionPref1').value;
            const div2 = document.getElementById('recDivisionPref2').value;
            const file = document.getElementById('recCvFileInput').files[0];
            const exp = document.getElementById('recExperienceInput').value;
            const btn = document.getElementById('btnSubmitRecruitment');

            if (!file) {
                showToast('Harap pilih berkas CV dalam format PDF.', 'error');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-1"></i> Mengunggah...`;

            const formData = new FormData();
            formData.append('division_pref_1', div1);
            if (div2) formData.append('division_pref_2', div2);
            formData.append('cv_file', file);
            formData.append('custom_answers[pengalaman]', exp || '-');

            try {
                const res = await apiFetch(`/api/v1/events/${eventId}/apply`, {
                    method: 'POST',
                    body: formData,
                });
                const json = await res.json();

                if (res.ok) {
                    closeModal('modalApplyRecruitment');
                    showToast('Lamaran kepanitiaan Anda berhasil dikirim ke panitia!', 'success');
                    switchTab('my-applications');
                } else {
                    showToast(json.message || 'Gagal mengirim berkas oprec.', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Terjadi kesalahan jaringan.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = `<i class="fa-solid fa-upload mr-1"></i> Kirim Lamaran Oprec`;
            }
        }

        // ----------------------------------------------------
        // SCREEN 2: MAHASISWA — DOMPET E-TIKET SAYA
        // ----------------------------------------------------
        async function loadStudentTickets() {
            const grid = document.getElementById('studentTicketsGrid');
            grid.innerHTML = `
                <div class="col-span-full py-12 text-center text-slate-400">
                    <i class="fa-solid fa-spinner fa-spin text-2xl mb-2 text-uii-blue"></i>
                    <p class="text-xs">Memuat dompet tiket Anda...</p>
                </div>
            `;

            try {
                const res = await apiFetch('/api/v1/my-tickets');
                const json = await res.json();

                if (res.ok && json.data) {
                    if (json.data.length === 0) {
                        grid.innerHTML = `
                            <div class="col-span-full py-16 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
                                <i class="fa-solid fa-ticket text-4xl mb-3 text-slate-300"></i>
                                <p class="text-sm font-semibold text-slate-600">Anda belum memiliki tiket aktif.</p>
                                <p class="text-xs text-slate-400 mt-1">Daftarkan diri Anda pada kegiatan di katalog acara.</p>
                            </div>
                        `;
                        return;
                    }

                    grid.innerHTML = json.data.map(ticket => {
                        const isCheckedIn = ticket.is_checked_in;
                        const qrId = `qrcode_${ticket.ticket_code.replace(/[^a-zA-Z0-9]/g, '_')}`;
                        return `
                            <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-200 flex flex-col justify-between">
                                <div class="bg-gradient-to-r from-uii-blue to-blue-900 text-white p-4">
                                    <div class="flex justify-between items-start">
                                        <span class="text-[10px] font-mono tracking-wider px-2 py-0.5 bg-white/20 rounded">
                                            ${ticket.ticket_code}
                                        </span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded ${isCheckedIn ? 'bg-emerald-500 text-white' : 'bg-amber-400 text-slate-950'}">
                                            ${isCheckedIn ? '✓ SUDAH CHECK-IN' : 'MENUNGGU PRESENSI'}
                                        </span>
                                    </div>
                                    <h4 class="font-bold text-sm mt-2 line-clamp-1">${ticket.event?.title || 'Acara Kampus'}</h4>
                                    <p class="text-[11px] text-blue-200">${ticket.event?.location || 'Lokasi Kampus'}</p>
                                </div>
                                <div class="p-5 flex flex-col items-center justify-center bg-slate-50 border-b border-slate-100">
                                    <div id="${qrId}" class="p-2 bg-white rounded-xl shadow-sm border border-slate-200 mb-2"></div>
                                    <p class="text-[10px] text-slate-400 font-mono tracking-widest uppercase">HMAC-SHA256 SIGNED</p>
                                </div>
                                <div class="p-4 bg-white flex justify-between items-center text-xs">
                                    <button onclick="copyTicketPayload('${encodeURIComponent(JSON.stringify(ticket.qr_code_payload))}')" 
                                        class="text-uii-blue font-semibold hover:underline flex items-center gap-1">
                                        <i class="fa-regular fa-copy"></i> Salin Payload
                                    </button>
                                    ${!isCheckedIn ? `
                                        <button onclick="cancelTicket('${ticket.ticket_code}')" class="text-rose-600 hover:text-rose-800 font-semibold flex items-center gap-1">
                                            <i class="fa-solid fa-ban"></i> Batalkan
                                        </button>
                                    ` : `
                                        <span class="text-emerald-600 font-semibold text-[11px]">
                                            <i class="fa-solid fa-check"></i> Hadir ${ticket.checked_in_at ? new Date(ticket.checked_in_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : ''}
                                        </span>
                                    `}
                                </div>
                            </div>
                        `;
                    }).join('');

                    // Render dynamic QR Codes
                    json.data.forEach(ticket => {
                        const qrId = `qrcode_${ticket.ticket_code.replace(/[^a-zA-Z0-9]/g, '_')}`;
                        const el = document.getElementById(qrId);
                        if (el) {
                            el.innerHTML = '';
                            new QRCode(el, {
                                text: JSON.stringify(ticket.qr_code_payload),
                                width: 120,
                                height: 120,
                                colorDark: "#0F2B5C",
                                colorLight: "#FFFFFF",
                                correctLevel: QRCode.CorrectLevel.H
                            });
                        }
                    });
                } else {
                    const msg = (json && json.message) ? json.message : 'Gagal memuat tiket mahasiswa.';
                    grid.innerHTML = `<div class="col-span-full py-10 text-center text-rose-500 text-xs font-semibold"><i class="fa-solid fa-triangle-exclamation mr-1"></i> ${msg}</div>`;
                }
            } catch (err) {
                console.error(err);
                grid.innerHTML = `<div class="col-span-full py-10 text-center text-rose-500 text-xs">Gagal memuat tiket.</div>`;
            }
        }

        function copyTicketPayload(encoded) {
            const raw = decodeURIComponent(encoded);
            navigator.clipboard.writeText(raw);
            showToast('Payload QR Code tiket disalin ke clipboard!', 'success');
        }

        async function cancelTicket(ticketCode) {
            if (!confirm(`Apakah Anda yakin ingin membatalkan tiket ${ticketCode}? Kuota acara akan dikembalikan.`)) return;
            try {
                const res = await apiFetch(`/api/v1/my-tickets/${ticketCode}/cancel`, { method: 'POST' });
                const json = await res.json();
                if (res.ok) {
                    showToast('Tiket berhasil dibatalkan dan kuota dikembalikan.', 'success');
                    loadStudentTickets();
                } else {
                    showToast(json.message || 'Gagal membatalkan tiket.', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Kesalahan jaringan saat membatalkan tiket.', 'error');
            }
        }

        // ----------------------------------------------------
        // SCREEN 3: MAHASISWA — RIWAYAT LAMARAN OPREC
        // ----------------------------------------------------
        async function loadStudentApplications() {
            const tbody = document.getElementById('studentApplicationsTableBody');
            tbody.innerHTML = `<tr><td colspan="6" class="px-5 py-8 text-center text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Memuat status lamaran...</td></tr>`;

            try {
                const res = await apiFetch('/api/v1/my-applications');
                const json = await res.json();
                if (res.ok && json.data) {
                    if (json.data.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Belum ada riwayat pendaftaran kepanitiaan.</td></tr>`;
                        return;
                    }

                    const statusBadges = {
                        SUBMITTED: 'bg-blue-100 text-blue-800',
                        SCREENING_PASSED: 'bg-amber-100 text-amber-800',
                        INTERVIEW_SCHEDULED: 'bg-purple-100 text-purple-800',
                        ACCEPTED: 'bg-emerald-100 text-emerald-800',
                        REJECTED: 'bg-rose-100 text-rose-800',
                    };

                    tbody.innerHTML = json.data.map(app => `
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5 font-bold text-slate-900">${app.event?.title || 'Kegiatan Oprec'}</td>
                            <td class="px-5 py-3.5">
                                <span class="block font-semibold text-uii-blue">${app.division_pref_1 || '-'}</span>
                                <span class="text-[11px] text-slate-400">${app.division_pref_2 ? 'Cadangan: ' + app.division_pref_2 : 'Tanpa cadangan'}</span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-500">${app.created_at ? new Date(app.created_at).toLocaleDateString('id-ID') : '-'}</td>
                            <td class="px-5 py-3.5">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold ${statusBadges[app.selection_status] || 'bg-slate-100 text-slate-700'}">
                                    ${app.selection_status}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600">${app.selection_notes || (app.custom_answers && (app.custom_answers.catatan_panitia || app.custom_answers.notes)) || '<span class="italic text-slate-400">Belum ada catatan</span>'}</td>
                            <td class="px-5 py-3.5 text-center">
                                <button onclick="downloadCvDocument('${app.id}')" class="text-uii-blue hover:text-uii-dark font-semibold">
                                    <i class="fa-solid fa-file-pdf mr-1 text-rose-600"></i> Unduh
                                </button>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    const msg = (json && json.message) ? json.message : 'Gagal memuat status lamaran.';
                    tbody.innerHTML = `<tr><td colspan="6" class="px-5 py-8 text-center text-rose-500 font-semibold"><i class="fa-solid fa-triangle-exclamation mr-1"></i> ${msg}</td></tr>`;
                }
            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="6" class="px-5 py-8 text-center text-rose-500">Gagal memuat status lamaran.</td></tr>`;
            }
        }

        async function downloadCvDocument(registrationId) {
            try {
                window.open(`/api/v1/registrations/${registrationId}/cv?token=${state.token}`, '_blank');
            } catch (e) {
                showToast('Gagal mengunduh berkas CV.', 'error');
            }
        }

        // ----------------------------------------------------
        // SCREEN 4: MAHASISWA — LEMARI E-SERTIFIKAT SAYA
        // ----------------------------------------------------
        async function loadStudentCertificates() {
            const grid = document.getElementById('studentCertificatesGrid');
            grid.innerHTML = `
                <div class="col-span-full py-12 text-center text-slate-400">
                    <i class="fa-solid fa-spinner fa-spin text-2xl mb-2 text-uii-blue"></i>
                    <p class="text-xs">Memuat koleksi sertifikat Anda...</p>
                </div>
            `;

            try {
                const res = await apiFetch('/api/v1/my-certificates');
                const json = await res.json();
                if (res.ok && json.data) {
                    if (json.data.length === 0) {
                        grid.innerHTML = `
                            <div class="col-span-full py-16 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
                                <i class="fa-solid fa-award text-4xl mb-3 text-slate-300"></i>
                                <p class="text-sm font-semibold text-slate-600">Belum ada e-sertifikat yang diperoleh.</p>
                                <p class="text-xs text-slate-400 mt-1">Sertifikat akan terbit otomatis saat tiket Anda dipindai di meja panitia hari-H.</p>
                            </div>
                        `;
                        return;
                    }

                    grid.innerHTML = json.data.map(cert => `
                        <div class="bg-gradient-to-br from-slate-900 via-uii-dark to-uii-blue text-white rounded-2xl p-6 shadow-md border border-slate-700 flex flex-col justify-between relative overflow-hidden">
                            <div class="absolute -right-6 -bottom-6 opacity-10 text-8xl pointer-events-none">
                                <i class="fa-solid fa-award"></i>
                            </div>
                            <div>
                                <div class="flex justify-between items-center mb-3">
                                    <span class="text-[10px] font-mono tracking-wider px-2 py-0.5 bg-amber-400 text-slate-950 font-bold rounded">
                                        RESMI TERVERIFIKASI
                                    </span>
                                    <span class="text-xs text-blue-200">UII Digital Certificate</span>
                                </div>
                                <h4 class="text-base font-bold text-amber-300 leading-snug mb-1">${cert.event?.title || cert.event_title || 'Sertifikat Kegiatan'}</h4>
                                <p class="text-xs text-slate-300 mb-3">Diberikan kepada: <span class="font-bold text-white">${cert.recipient?.name || cert.participant_name || state.user?.name || 'Mahasiswa'}</span> (${cert.recipient?.nim || cert.participant_nim || state.user?.nim || '-'})</p>
                            </div>
                            <div class="border-t border-blue-900/60 pt-3">
                                <div class="text-[11px] font-mono text-slate-400 truncate mb-2">
                                    No: ${cert.certificate_number}
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <button onclick="verifySpecificHash('${cert.verify_hash || cert.verification_hash}')" class="text-amber-400 hover:underline flex items-center gap-1 font-semibold">
                                        <i class="fa-solid fa-shield-check"></i> Cek Bukti SHA-256
                                    </button>
                                    <span class="text-[10px] text-slate-400">${cert.issued_at ? new Date(cert.issued_at).toLocaleDateString('id-ID') : '-'}</span>
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    const msg = (json && json.message) ? json.message : 'Gagal memuat sertifikat mahasiswa.';
                    grid.innerHTML = `<div class="col-span-full py-10 text-center text-rose-500 text-xs font-semibold"><i class="fa-solid fa-triangle-exclamation mr-1"></i> ${msg}</div>`;
                }
            } catch (err) {
                console.error(err);
                grid.innerHTML = `<div class="col-span-full py-10 text-center text-rose-500 text-xs">Gagal memuat sertifikat.</div>`;
            }
        }

        // ----------------------------------------------------
        // SCREEN 5: VERIFIKASI KEASLIAN E-SERTIFIKAT
        // ----------------------------------------------------
        async function handleVerifyCertificate() {
            const hash = document.getElementById('verifyHashInput').value.trim();
            if (!hash) {
                showToast('Harap masukkan hash verifikasi sertifikat.', 'error');
                return;
            }
            await verifySpecificHash(hash);
        }

        async function verifySpecificHash(hash) {
            switchTab('verify');
            document.getElementById('verifyHashInput').value = hash;
            const container = document.getElementById('verifyResultContainer');
            container.classList.remove('hidden');
            container.innerHTML = `
                <div class="bg-white p-6 rounded-2xl border border-slate-200 text-center">
                    <i class="fa-solid fa-spinner fa-spin text-2xl text-uii-blue mb-2"></i>
                    <p class="text-xs text-slate-500">Memverifikasi tanda tangan kriptografis sertifikat...</p>
                </div>
            `;

            try {
                const res = await apiFetch(`/api/v1/certificates/verify/${hash}`);
                const json = await res.json();

                if (res.ok && json.data) {
                    const c = json.data;
                    container.innerHTML = `
                        <div class="bg-emerald-50 border-2 border-emerald-500 rounded-2xl p-6 shadow-sm">
                            <div class="flex items-center gap-3 text-emerald-800 mb-4">
                                <i class="fa-solid fa-circle-check text-3xl text-emerald-600"></i>
                                <div>
                                    <h4 class="font-extrabold text-base">E-Sertifikat Asli & Terverifikasi</h4>
                                    <p class="text-xs text-emerald-700">Tercatat sah dalam pangkalan data kegiatan resmi UII.</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs bg-white p-4 rounded-xl border border-emerald-200">
                                <div><span class="text-slate-400 block text-[10px] font-semibold uppercase">Nama Peserta:</span><span class="font-bold text-slate-800">${c.recipient?.name || c.participant?.name || 'Peserta'} (${c.recipient?.nim || c.participant?.nim || '-'})</span></div>
                                <div><span class="text-slate-400 block text-[10px] font-semibold uppercase">Nomor Sertifikat:</span><span class="font-bold font-mono text-uii-blue">${c.certificate_number}</span></div>
                                <div><span class="text-slate-400 block text-[10px] font-semibold uppercase">Nama Kegiatan:</span><span class="font-bold text-slate-800">${c.event?.title}</span></div>
                                <div><span class="text-slate-400 block text-[10px] font-semibold uppercase">Penyelenggara:</span><span class="font-bold text-slate-800">${(typeof c.event?.organizer === 'object' ? c.event?.organizer?.name : c.event?.organizer) || c.event?.category || 'Universitas Islam Indonesia'}</span></div>
                            </div>
                            <div class="mt-3 text-[11px] font-mono text-slate-500 break-all">
                                SHA-256 Hash: ${c.verify_hash || c.verification_hash}
                            </div>
                        </div>
                    `;
                } else {
                    container.innerHTML = `
                        <div class="bg-rose-50 border-2 border-rose-400 rounded-2xl p-6 text-center">
                            <i class="fa-solid fa-triangle-exclamation text-3xl text-rose-600 mb-2"></i>
                            <h4 class="font-bold text-rose-900 text-sm">Sertifikat Tidak Ditemukan atau Palsu</h4>
                            <p class="text-xs text-rose-700 mt-1">Hash SHA-256 yang dimasukkan tidak cocok dengan basis data sertifikat UII.</p>
                        </div>
                    `;
                }
            } catch (err) {
                console.error(err);
                showToast('Gagal memverifikasi sertifikat.', 'error');
            }
        }

        // ----------------------------------------------------
        // SCREEN 6: PANITIA — DASHBOARD & CRUD ACARA
        // ----------------------------------------------------
        async function loadOrganizerEvents() {
            const tbody = document.getElementById('organizerEventsTableBody');
            tbody.innerHTML = `<tr><td colspan="6" class="px-5 py-8 text-center text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Memuat data acara panitia...</td></tr>`;

            try {
                const res = await apiFetch('/api/v1/organizer/events');
                const json = await res.json();

                if (res.ok && json.data) {
                    if (json.data.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Anda belum membuat proposal kegiatan. Klik "Buat Acara Baru" di atas.</td></tr>`;
                        return;
                    }

                    const statusBadges = {
                        DRAFT: 'bg-slate-100 text-slate-700',
                        PENDING_APPROVAL: 'bg-amber-100 text-amber-800',
                        PUBLISHED: 'bg-emerald-100 text-emerald-800',
                        REJECTED: 'bg-rose-100 text-rose-800',
                    };

                    tbody.innerHTML = json.data.map(ev => `
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5">
                                <span class="font-bold text-slate-900 block">${ev.title}</span>
                                <span class="text-[11px] text-slate-400">${ev.location || 'Lokasi Kampus'}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="block font-semibold text-uii-blue">${(typeof ev.category === 'object' ? ev.category?.name : ev.category) || '-'}</span>
                                <span class="text-[11px] text-slate-400">${ev.activity_type === 'COMMITTEE_RECRUITMENT' ? 'Oprec Panitia' : 'Peserta'}</span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600">${ev.start_time ? new Date(ev.start_time).toLocaleDateString('id-ID') : '-'}</td>
                            <td class="px-5 py-3.5 font-bold ${ev.quota_remaining <= 5 ? 'text-rose-600' : 'text-emerald-600'}">
                                ${ev.quota_remaining} / ${ev.quota_total}
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold ${statusBadges[ev.status] || 'bg-slate-100 text-slate-700'}">
                                    ${ev.status}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    ${ev.status === 'DRAFT' ? `
                                        <button onclick="submitDraftToDpm('${ev.id}')" title="Ajukan ke DPM" class="px-2 py-1 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded text-[11px]">
                                            <i class="fa-solid fa-paper-plane mr-1"></i> Ajukan
                                        </button>
                                    ` : ''}
                                    <button onclick="openOrganizerRecapModal('${ev.id}')" title="Buka Rekap & Laporan" class="px-2 py-1 bg-uii-blue hover:bg-uii-dark text-white rounded text-[11px]">
                                        <i class="fa-solid fa-chart-pie mr-1"></i> Rekap
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    const msg = (json && json.message) ? json.message : 'Gagal memuat kegiatan panitia.';
                    tbody.innerHTML = `<tr><td colspan="6" class="px-5 py-8 text-center text-rose-500 font-semibold"><i class="fa-solid fa-triangle-exclamation mr-1"></i> ${msg}</td></tr>`;
                }
            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="6" class="px-5 py-8 text-center text-rose-500">Gagal memuat acara panitia.</td></tr>`;
            }
        }

        let activePosterMode = 'upload';
        let selectedPosterFile = null;

        function setPosterInputMode(mode) {
            activePosterMode = mode;
            const btnUpload = document.getElementById('btnPosterModeUpload');
            const btnUrl = document.getElementById('btnPosterModeUrl');
            const boxUpload = document.getElementById('posterUploadContainer');
            const boxUrl = document.getElementById('posterUrlContainer');

            if (mode === 'upload') {
                btnUpload.className = 'px-2.5 py-1 rounded-md font-semibold bg-white text-uii-blue shadow-sm';
                btnUrl.className = 'px-2.5 py-1 rounded-md font-semibold text-slate-600 hover:text-slate-900';
                boxUpload.classList.remove('hidden');
                boxUrl.classList.add('hidden');
            } else {
                btnUrl.className = 'px-2.5 py-1 rounded-md font-semibold bg-white text-uii-blue shadow-sm';
                btnUpload.className = 'px-2.5 py-1 rounded-md font-semibold text-slate-600 hover:text-slate-900';
                boxUrl.classList.remove('hidden');
                boxUpload.classList.add('hidden');
            }
        }

        function handlePosterFileChange(e) {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                showToast('Ukuran berkas gambar melebihi batas 5MB.', 'error');
                e.target.value = '';
                return;
            }

            selectedPosterFile = file;
            document.getElementById('evPosterFileName').innerText = `${file.name} (${(file.size / 1024).toFixed(1)} KB)`;

            const reader = new FileReader();
            reader.onload = (event) => {
                const img = document.getElementById('posterPreviewImg');
                const placeholder = document.getElementById('posterPreviewPlaceholder');
                img.src = event.target.result;
                img.classList.remove('hidden');
                placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }

        function showCreateEventModal() {
            // Berikan default waktu yang valid (H+2) jika input masih kosong
            const startInput = document.getElementById('evStartTime');
            const endInput = document.getElementById('evEndTime');
            const posterInput = document.getElementById('evPosterUrl');

            if (!startInput.value) {
                const now = new Date();
                const startDate = new Date(now.getTime() + 48 * 60 * 60 * 1000); // H+2
                startDate.setHours(9, 0, 0, 0);
                const endDate = new Date(now.getTime() + 48 * 60 * 60 * 1000);
                endDate.setHours(16, 0, 0, 0);

                const toLocalISO = (d) => {
                    const pad = (n) => String(n).padStart(2, '0');
                    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
                };

                startInput.value = toLocalISO(startDate);
                endInput.value = toLocalISO(endDate);
            }

            if (!posterInput.value) {
                posterInput.value = 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800';
            }

            openModal('modalCreateEvent');
        }

        function toggleDivisionsBuilder(type) {
            const sec = document.getElementById('evDivisionsSection');
            if (type === 'COMMITTEE_RECRUITMENT') {
                sec.classList.remove('hidden');
                if (document.getElementById('evDivisionsListContainer').children.length === 0) {
                    addDivisionRow('Divisi Acara & Konsep', 10);
                    addDivisionRow('Divisi Publikasi & Dokumentasi', 5);
                }
            } else {
                sec.classList.add('hidden');
            }
        }

        function addDivisionRow(name = '', quota = 5) {
            const c = document.getElementById('evDivisionsListContainer');
            const div = document.createElement('div');
            div.className = 'flex gap-2 items-center';
            div.innerHTML = `
                <input type="text" placeholder="Nama Divisi" value="${name}" required class="div-name flex-grow p-2 border border-slate-300 rounded-lg text-xs">
                <input type="number" placeholder="Kuota" value="${quota}" min="1" required class="div-quota w-24 p-2 border border-slate-300 rounded-lg text-xs">
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 px-2">
                    <i class="fa-solid fa-trash"></i>
                </button>
            `;
            c.appendChild(div);
        }

        async function submitCreateEvent(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitCreateEvent');
            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-1"></i> Menyimpan...`;

            const type = document.getElementById('evActivityType').value;
            const divisions = [];
            if (type === 'COMMITTEE_RECRUITMENT') {
                const names = document.querySelectorAll('.div-name');
                const quotas = document.querySelectorAll('.div-quota');
                names.forEach((el, idx) => {
                    divisions.push({ name: el.value, quota: parseInt(quotas[idx].value) || 5 });
                });
            }

            let finalPosterUrl = '';
            try {
                if (activePosterMode === 'upload' && selectedPosterFile) {
                    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-1"></i> Mengunggah gambar...`;
                    const formData = new FormData();
                    formData.append('poster', selectedPosterFile);

                    const upRes = await apiFetch('/api/v1/organizer/upload-poster', {
                        method: 'POST',
                        body: formData,
                    });
                    const upJson = await upRes.json();
                    if (!upRes.ok || !upJson.data?.url) {
                        showToast(upJson.message || 'Gagal mengunggah gambar poster.', 'error');
                        btn.disabled = false;
                        btn.innerHTML = `<i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Draf Acara`;
                        return;
                    }
                    finalPosterUrl = upJson.data.url;
                } else {
                    finalPosterUrl = document.getElementById('evPosterUrl')?.value || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800';
                }
            } catch (err) {
                console.error('Poster upload error', err);
                showToast('Gagal memproses unggah gambar poster.', 'error');
                btn.disabled = false;
                btn.innerHTML = `<i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Draf Acara`;
                return;
            }

            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-1"></i> Menyimpan acara...`;

            const payload = {
                category_id: parseInt(document.getElementById('evCategory').value, 10),
                title: document.getElementById('evTitle').value,
                description: document.getElementById('evDescription').value,
                activity_type: type,
                poster_url: finalPosterUrl,
                quota_total: parseInt(document.getElementById('evQuota').value, 10) || 100,
                start_time: document.getElementById('evStartTime').value,
                end_time: document.getElementById('evEndTime').value,
                location: document.getElementById('evLocation').value,
                auto_post_ig: document.getElementById('evAutoPostIg').checked,
            };

            if (type === 'COMMITTEE_RECRUITMENT') {
                payload.divisions = divisions;
            }

            try {
                const res = await apiFetch('/api/v1/organizer/events', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const json = await res.json();

                if (res.ok) {
                    closeModal('modalCreateEvent');
                    showToast('Draf kegiatan baru berhasil disimpan.', 'success');
                    loadOrganizerEvents();
                } else {
                    let errMsg = json.message || 'Gagal menyimpan draf acara.';
                    if (json.errors) {
                        const allErrors = Object.values(json.errors).flat();
                        errMsg = allErrors.join(' | ');
                    }
                    showToast(errMsg, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Terjadi kesalahan jaringan.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = `<i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Draf Acara`;
            }
        }

        async function submitDraftToDpm(eventId) {
            if (!confirm('Ajukan proposal kegiatan ini ke DPM UII untuk kurasi persetujuan?')) return;
            try {
                const res = await apiFetch(`/api/v1/organizer/events/${eventId}/submit`, { method: 'POST' });
                const json = await res.json();
                if (res.ok) {
                    showToast('Proposal kegiatan resmi diajukan ke DPM UII.', 'success');
                    loadOrganizerEvents();
                } else {
                    showToast(json.message || 'Gagal mengajukan proposal.', 'error');
                }
            } catch (e) {
                showToast('Kesalahan jaringan saat submit ke DPM.', 'error');
            }
        }

        // Open Recap Modal
        async function openOrganizerRecapModal(eventId) {
            const body = document.getElementById('recapModalBody');
            body.innerHTML = `<div class="py-12 text-center text-slate-400"><i class="fa-solid fa-spinner fa-spin text-2xl mb-2 text-uii-blue"></i><p>Memuat rekapitulasi acara...</p></div>`;
            openModal('modalEventRecap');

            try {
                const res = await apiFetch(`/api/v1/organizer/events/${eventId}/recap`);
                const json = await res.json();
                if (!res.ok || !json.data) {
                    body.innerHTML = `<div class="text-rose-500 py-6 text-center">Gagal memuat rekap.</div>`;
                    return;
                }

                const d = json.data;
                document.getElementById('recapEventTitle').innerText = d.event.title;

                body.innerHTML = `
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                        <div class="bg-blue-50 p-3 rounded-xl border border-blue-200">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Total Terdaftar</span>
                            <span class="text-xl font-extrabold text-blue-900">${d.participants.total_registered}</span>
                        </div>
                        <div class="bg-emerald-50 p-3 rounded-xl border border-emerald-200">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Hadir Check-In</span>
                            <span class="text-xl font-extrabold text-emerald-800">${d.participants.attended_count}</span>
                        </div>
                        <div class="bg-amber-50 p-3 rounded-xl border border-amber-200">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Tingkat Kehadiran</span>
                            <span class="text-xl font-extrabold text-amber-700">${d.participants.attendance_rate_percent}%</span>
                        </div>
                        <div class="bg-purple-50 p-3 rounded-xl border border-purple-200">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Sertifikat Terbit</span>
                            <span class="text-xl font-extrabold text-purple-900">${d.certificates.total_issued}</span>
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <h4 class="font-bold text-slate-800 mb-2">Aksi Pengunduhan & Integrasi:</h4>
                        <div class="flex flex-wrap gap-2">
                            <a href="/api/v1/organizer/events/${eventId}/export/participants?token=${state.token}" target="_blank"
                                class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold flex items-center gap-1.5 shadow">
                                <i class="fa-solid fa-file-excel"></i> Ekspor CSV Peserta (BOM UTF-8)
                            </a>
                            <button onclick="triggerBroadcastReminder('${eventId}')" 
                                class="px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 rounded-lg font-bold flex items-center gap-1.5 shadow">
                                <i class="fa-solid fa-paper-plane"></i> Kirim Broadcast Pengingat H-1
                            </button>
                            <button onclick="triggerPublishInstagram('${eventId}')" 
                                class="px-3.5 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-bold flex items-center gap-1.5 shadow">
                                <i class="fa-brands fa-instagram"></i> Auto-Post Instagram (Meta API)
                            </button>
                        </div>
                    </div>
                `;
            } catch (err) {
                console.error(err);
                body.innerHTML = `<div class="text-rose-500 py-6 text-center">Gagal memuat rekap.</div>`;
            }
        }

        async function triggerBroadcastReminder(eventId) {
            try {
                const res = await apiFetch(`/api/v1/organizer/events/${eventId}/reminders`, {
                    method: 'POST',
                    body: JSON.stringify({ reminder_type: 'REMINDER_H1' })
                });
                const json = await res.json();
                if (res.ok) {
                    showToast('Pengingat berhasil dijadwalkan ke antrean WhatsApp & Email!', 'success');
                } else {
                    showToast(json.message || 'Gagal mengirim pengingat.', 'error');
                }
            } catch (e) {
                showToast('Kesalahan jaringan.', 'error');
            }
        }

        async function triggerPublishInstagram(eventId) {
            try {
                const res = await apiFetch(`/api/v1/organizer/events/${eventId}/publish-instagram`, { method: 'POST' });
                const json = await res.json();
                if (res.ok) {
                    showToast('Poster kegiatan dipublikasikan ke feed Instagram Resmi Kampus!', 'success');
                } else {
                    showToast(json.message || 'Gagal publish Instagram.', 'error');
                }
            } catch (e) {
                showToast('Kesalahan jaringan saat memanggil Meta API.', 'error');
            }
        }

        // ----------------------------------------------------
        // SCREEN 7: PANITIA — SCANNER PRESENSI HARI-H
        // ----------------------------------------------------
        async function handleScanCheckIn() {
            const rawPayload = document.getElementById('scannerPayloadInput').value.trim();
            const manualCode = document.getElementById('scannerTicketCodeManual').value.trim();
            const manualSig = document.getElementById('scannerSignatureManual').value.trim();
            const feedback = document.getElementById('scannerFeedbackBox');

            let payloadObj = null;
            if (rawPayload) {
                try {
                    payloadObj = JSON.parse(rawPayload);
                } catch (e) {
                    showToast('Format payload JSON tidak valid.', 'error');
                    return;
                }
            } else if (manualCode && manualSig) {
                payloadObj = { ticket_code: manualCode, signature: manualSig };
            } else {
                showToast('Harap masukkan payload JSON atau kode & signature manual.', 'error');
                return;
            }

            feedback.classList.remove('hidden');
            feedback.innerHTML = `<div class="p-4 bg-blue-50 border border-blue-200 text-blue-900 rounded-xl text-xs"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Memvalidasi tanda tangan QR & merekam presensi...</div>`;

            try {
                const res = await apiFetch('/api/v1/attendance/check-in', {
                    method: 'POST',
                    body: JSON.stringify(payloadObj)
                });
                const json = await res.json();

                if (res.ok && json.data) {
                    const d = json.data;
                    feedback.innerHTML = `
                        <div class="p-5 bg-emerald-50 border-2 border-emerald-500 text-emerald-950 rounded-2xl shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <i class="fa-solid fa-circle-check text-3xl text-emerald-600"></i>
                                <div>
                                    <h4 class="font-extrabold text-base">Presensi Sah! Mahasiswa Berhasil Check-In</h4>
                                    <p class="text-xs text-emerald-700">Waktu: ${new Date(d.checked_in_at).toLocaleTimeString('id-ID')}</p>
                                </div>
                            </div>
                            <div class="bg-white p-3.5 rounded-xl border border-emerald-200 text-xs grid grid-cols-2 gap-2">
                                <div><span class="text-slate-400 block text-[10px] uppercase font-bold">Nama Peserta:</span><span class="font-bold text-slate-800">${d.attendee?.name || d.participant?.name || 'Peserta'} (${d.attendee?.nim || d.participant?.nim || '-'})</span></div>
                                <div><span class="text-slate-400 block text-[10px] uppercase font-bold">Kode Tiket:</span><span class="font-mono font-bold text-uii-blue">${d.ticket_code}</span></div>
                                <div class="col-span-2 pt-2 border-t border-slate-100">
                                    <span class="text-slate-400 block text-[10px] uppercase font-bold">E-Sertifikat Otomatis Terbit:</span>
                                    <span class="font-bold text-emerald-700">${d.certificate?.certificate_number}</span>
                                </div>
                            </div>
                        </div>
                    `;
                    showToast(`Presensi berhasil untuk ${d.attendee?.name || d.participant?.name || 'Peserta'}!`, 'success');
                } else {
                    feedback.innerHTML = `
                        <div class="p-5 bg-rose-50 border-2 border-rose-400 text-rose-950 rounded-2xl">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-circle-xmark text-3xl text-rose-600"></i>
                                <div>
                                    <h4 class="font-bold text-sm">Gagal Presensi</h4>
                                    <p class="text-xs text-rose-700 mt-0.5">${json.message || 'Tanda tangan tiket tidak valid atau sudah check-in sebelumnya.'}</p>
                                </div>
                            </div>
                        </div>
                    `;
                    showToast(json.message || 'Gagal check-in presensi.', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Kesalahan jaringan pemindai.', 'error');
            }
        }

        // ----------------------------------------------------
        // SCREEN 8: SUPERADMIN (DPM UII) — KURASI PROPOSAL
        // ----------------------------------------------------
        async function loadAdminCurationList() {
            const tbody = document.getElementById('adminCurationTableBody');
            tbody.innerHTML = `<tr><td colspan="6" class="px-5 py-8 text-center text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Memuat antrean kurasi DPM...</td></tr>`;

            try {
                const res = await apiFetch('/api/v1/admin/events');
                const json = await res.json();

                if (res.ok && json.data) {
                    if (json.data.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Semua proposal kegiatan telah selesai dikurasi oleh DPM.</td></tr>`;
                        return;
                    }

                    const statusBadges = {
                        PENDING_APPROVAL: 'bg-amber-100 text-amber-800',
                        PUBLISHED: 'bg-emerald-100 text-emerald-800',
                        REJECTED: 'bg-rose-100 text-rose-800',
                    };

                    tbody.innerHTML = json.data.map(ev => `
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5">
                                <span class="font-bold text-slate-900 block">${ev.title}</span>
                                <span class="text-[11px] text-uii-blue font-semibold">${ev.organizer_name || (typeof ev.organizer === 'object' ? ev.organizer?.name : ev.organizer) || 'Ormawa UII'}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="block font-semibold text-slate-700">${(typeof ev.category === 'object' ? ev.category?.name : ev.category) || '-'}</span>
                                <span class="text-[11px] text-slate-400">${ev.activity_type === 'COMMITTEE_RECRUITMENT' ? 'Oprec Panitia' : 'Peserta Acara'}</span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600">
                                <div>${ev.start_time ? new Date(ev.start_time).toLocaleDateString('id-ID') : '-'}</div>
                                <div class="text-[11px] text-slate-400">${ev.location || 'Kampus UII'}</div>
                            </td>
                            <td class="px-5 py-3.5 font-bold text-slate-800">${ev.quota_total} Kuota</td>
                            <td class="px-5 py-3.5">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold ${statusBadges[ev.status] || 'bg-slate-100 text-slate-700'}">
                                    ${ev.status}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                ${ev.status === 'PENDING_APPROVAL' ? `
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button onclick="approveEventByDpm('${ev.id}')" title="Setujui Acara" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded text-xs transition shadow">
                                            <i class="fa-solid fa-check mr-1"></i> Setujui
                                        </button>
                                        <button onclick="rejectEventByDpm('${ev.id}')" title="Tolak Acara" class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded text-xs transition shadow">
                                            <i class="fa-solid fa-xmark mr-1"></i> Tolak
                                        </button>
                                    </div>
                                ` : `
                                    <span class="text-xs text-slate-400 italic">Selesai Dikurasi</span>
                                `}
                            </td>
                        </tr>
                    `).join('');
                } else {
                    const msg = (json && json.message) ? json.message : 'Gagal memuat antrean kurasi DPM.';
                    tbody.innerHTML = `<tr><td colspan="6" class="px-5 py-8 text-center text-rose-500 font-semibold"><i class="fa-solid fa-triangle-exclamation mr-1"></i> ${msg}</td></tr>`;
                }
            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="6" class="px-5 py-8 text-center text-rose-500">Gagal memuat antrean kurasi.</td></tr>`;
            }
        }

        async function approveEventByDpm(eventId) {
            if (!confirm('Apakah DPM UII menyetujui kegiatan ini? Acara akan langsung terpublikasi dan otomatis diposting ke Instagram resmi kampus.')) return;
            try {
                const res = await apiFetch(`/api/v1/admin/events/${eventId}/approve`, { method: 'POST' });
                const json = await res.json();
                if (res.ok) {
                    showToast('Kegiatan resmi disetujui (APPROVED & PUBLISHED)!', 'success');
                    loadAdminCurationList();
                } else {
                    showToast(json.message || 'Gagal menyetujui acara.', 'error');
                }
            } catch (e) {
                showToast('Kesalahan jaringan saat menyetujui acara.', 'error');
            }
        }

        async function rejectEventByDpm(eventId) {
            const reason = prompt('Masukkan alasan penolakan proposal kegiatan ini kepada panitia:');
            if (reason === null) return;
            if (!reason.trim()) {
                showToast('Alasan penolakan tidak boleh kosong.', 'error');
                return;
            }

            try {
                const res = await apiFetch(`/api/v1/admin/events/${eventId}/reject`, {
                    method: 'POST',
                    body: JSON.stringify({ rejection_reason: reason })
                });
                const json = await res.json();
                if (res.ok) {
                    showToast('Proposal kegiatan resmi ditolak dengan catatan evaluasi.', 'success');
                    loadAdminCurationList();
                } else {
                    showToast(json.message || 'Gagal menolak acara.', 'error');
                }
            } catch (e) {
                showToast('Kesalahan jaringan saat menolak acara.', 'error');
            }
        }

        // ----------------------------------------------------
        // UI HELPERS (MODALS & TOASTS)
        // ----------------------------------------------------
        function openModal(id) {
            const el = document.getElementById(id);
            if (el) el.classList.remove('hidden');
        }

        function closeModal(id) {
            const el = document.getElementById(id);
            if (el) el.classList.add('hidden');
        }

        let toastTimer = null;
        function showToast(message, type = 'info') {
            const toast = document.getElementById('toastNotification');
            const title = document.getElementById('toastTitle');
            const msg = document.getElementById('toastMessage');
            const icon = document.getElementById('toastIcon');

            clearTimeout(toastTimer);

            if (type === 'success') {
                toast.className = 'fixed bottom-5 right-5 z-50 max-w-md bg-white border-l-4 border-emerald-500 rounded-xl shadow-2xl p-4 transition-all duration-300';
                icon.className = 'flex-shrink-0 text-xl mr-3 text-emerald-500';
                icon.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
                title.innerText = 'Sukses';
            } else if (type === 'error') {
                toast.className = 'fixed bottom-5 right-5 z-50 max-w-md bg-white border-l-4 border-rose-500 rounded-xl shadow-2xl p-4 transition-all duration-300';
                icon.className = 'flex-shrink-0 text-xl mr-3 text-rose-500';
                icon.innerHTML = '<i class="fa-solid fa-circle-xmark"></i>';
                title.innerText = 'Peringatan / Error';
            } else {
                toast.className = 'fixed bottom-5 right-5 z-50 max-w-md bg-white border-l-4 border-blue-500 rounded-xl shadow-2xl p-4 transition-all duration-300';
                icon.className = 'flex-shrink-0 text-xl mr-3 text-blue-500';
                icon.innerHTML = '<i class="fa-solid fa-circle-info"></i>';
                title.innerText = 'Informasi Sistem';
            }

            msg.innerText = message;
            toast.classList.remove('hidden');

            toastTimer = setTimeout(() => {
                hideToast();
            }, 4500);
        }

        function hideToast() {
            const toast = document.getElementById('toastNotification');
            if (toast) toast.classList.add('hidden');
        }
    </script>
</body>
</html>
