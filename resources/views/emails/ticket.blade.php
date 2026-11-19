<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>E-Tiket Kegiatan UII</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 24px; color: #1f2937;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 32px 24px; text-align: center; color: #ffffff;">
            <h1 style="margin: 0; font-size: 24px; font-weight: 700;">Universitas Islam Indonesia</h1>
            <p style="margin: 8px 0 0 0; font-size: 14px; opacity: 0.9;">UII Event Management Platform</p>
        </div>
        
        <div style="padding: 32px 24px;">
            <div style="text-align: center; margin-bottom: 24px;">
                <span style="display: inline-block; background-color: #dbeafe; color: #1e40af; font-size: 12px; font-weight: 600; padding: 6px 16px; border-radius: 9999px; text-transform: uppercase;">
                    Pendaftaran Dikonfirmasi
                </span>
                <h2 style="margin: 16px 0 8px 0; color: #111827; font-size: 20px;">Halo, {{ $registration->user?->name }}!</h2>
                <p style="margin: 0; color: #4b5563; font-size: 14px; line-height: 1.5;">
                    Selamat, pendaftaran Anda untuk kegiatan resmi kampus di bawah ini telah berhasil diverifikasi oleh sistem.
                </p>
            </div>

            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                <h3 style="margin: 0 0 12px 0; color: #1e3a8a; font-size: 16px;">{{ $registration->event?->title }}</h3>
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; width: 120px;">Kode Tiket:</td>
                        <td style="padding: 6px 0; font-weight: 700; color: #111827; font-family: monospace;">{{ $registration->ticket?->ticket_code ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Kategori:</td>
                        <td style="padding: 6px 0; font-weight: 600; color: #374151;">{{ $registration->event?->category?->name ?? 'Kegiatan Umum' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Waktu Mulai:</td>
                        <td style="padding: 6px 0; font-weight: 600; color: #374151;">{{ $registration->event?->start_time?->format('d M Y, H:i') }} WIB</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Lokasi / Venue:</td>
                        <td style="padding: 6px 0; font-weight: 600; color: #374151;">{{ $registration->event?->location ?? 'Daring / Online' }}</td>
                    </tr>
                </table>
            </div>

            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 4px; margin-bottom: 24px;">
                <p style="margin: 0; color: #92400e; font-size: 13px; line-height: 1.4;">
                    <strong>Penting:</strong> Tunjukkan Kode Tiket atau QR Code digital Anda pada meja presensi panitia saat menghadiri acara. E-Sertifikat kehadiran akan otomatis terbit sesaat setelah presensi Anda tercatat.
                </p>
            </div>
        </div>

        <div style="background-color: #f9fafb; border-top: 1px solid #e5e7eb; padding: 20px 24px; text-align: center; font-size: 12px; color: #6b7280;">
            <p style="margin: 0 0 4px 0;">Pesan otomatis dikirim oleh Sistem Informasi Manajemen Acara Universitas Islam Indonesia.</p>
            <p style="margin: 0;">Badan Sistem Informasi (BSI) & Dewan Perwakilan Mahasiswa (DPM) UII.</p>
        </div>
    </div>
</body>
</html>
