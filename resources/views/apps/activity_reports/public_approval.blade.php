<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara - {{ $report->activity->title ?? 'Pengesahan Digital' }}</title>

    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image/png" href="../../assets/UNITRI.png" />


    <!-- Bootstrap 5 CSS & Iconify -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

    <style>
        body {
            background-color: #e9ecef;
            font-family: "Times New Roman", Times, serif;
            /* Standar Surat/Dokumen Resmi */
            color: #000;
        }

        /* Container Ukuran A4 Presisi */
        .page-a4 {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm 20mm 20mm 20mm;
            margin: 20px auto;
            background: #ffffff;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.15);
            position: relative;
            box-sizing: border-box;
        }

        /* Header Kop Surat (Jika diperlukan) */
        .kop-surat {
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .table-detail td {
            padding: 4px 8px;
            vertical-align: top;
            font-size: 14px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
            margin-top: 15px;
            margin-bottom: 8px;
        }

        .content-text {
            font-size: 14px;
            line-height: 1.6;
            text-align: justify;
        }

        /* Styling khusus untuk list/numbering dari Summernote agar rapi di dokumen resmi */
        .content-text ul,
        .content-text ol {
            padding-left: 20px;
            margin-bottom: 0;
        }

        /* Area Tanda Tangan / Pengesahan */
        .signature-box {
            margin-top: 40px;
            float: right;
            width: 250px;
            text-align: center;
        }

        /* CSS Khusus Mode Cetak / PDF */
        @media print {
            body {
                background: none;
            }

            .page-a4 {
                margin: 0;
                box-shadow: none;
                width: 100%;
                min-height: auto;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    {{-- Tombol Aksi Melayang (Floating Toolbar) --}}
    <div class="position-fixed top-0 start-50 translate-middle-x mt-3 no-print z-3">
        <div class="bg-dark text-white p-2 px-4 rounded-pill shadow-lg d-flex align-items-center gap-3">
            <span class="small">Dokumen Lembar Pengesahan (A4)</span>
            <button onclick="window.print()" class="btn btn-sm btn-light d-flex align-items-center gap-1">
                <iconify-icon icon="solar:printer-line-duotone"></iconify-icon> Cetak PDF
            </button>
        </div>
    </div>

    {{-- DOKUMEN A4 --}}
    <div class="page-a4">

        {{-- KOP DOKUMEN --}}
        <div class="kop-surat text-center">
            <h4 class="fw-bold text-uppercase mb-1" style="letter-spacing: 1px;">BERITA ACARA KEGIATAN</h4>
            @if(!empty($report->document_number))
            <div class="text-muted" style="font-size: 14px;">Nomor: {{ $report->document_number }}</div>
            @endif
        </div>

        {{-- NOTIFIKASI SUKSES --}}
        @if(session('success'))
        <div class="alert alert-success border-0 text-center py-2 mb-3 no-print rounded-3 small">
            🎉 <strong>{{ session('success') }}</strong>
        </div>
        @endif

        {{-- INFORMASI KEGIATAN --}}
        <table class="w-100 table-detail mb-3">
            <tr>
                <td width="160"><strong>Judul Kegiatan</strong></td>
                <td width="10">:</td>
                <td>{{ $report->activity->title ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Tipe Kegiatan</strong></td>
                <td>:</td>
                <td>{{ $report->activity->activityType->name ?? 'Internal' }}</td>
            </tr>
            <tr>
                <td><strong>Lokasi / Tempat</strong></td>
                <td>:</td>
                <td>{{ $report->activity->location ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Tanggal Dibuat</strong></td>
                <td>:</td>
                <td>{{ \Carbon\Carbon::parse($report->created_at)->translatedFormat('d F Y') }}</td>
            </tr>
        </table>

        {{-- RINGKASAN KEGIATAN --}}
        <div class="section-title">I. Ringkasan Pelaksanaan</div>
        <div class="content-text mb-3">
            {!! nl2br(e($report->summary ?? 'Pelaksanaan kegiatan berjalan sesuai jadwal yang telah ditentukan.')) !!}
        </div>

        {{-- HASIL PEMBAHASAN & KEPUTUSAN --}}
        <div class="section-title">II. Hasil Pembahasan & Keputusan</div>
        <div class="content-text mb-3">
            {!! $report->decisions !!}
        </div>

        {{-- RENCANA TINDAK LANJUT --}}
        @if($report->action_items)
        <div class="section-title">III. Rencana Tindak Lanjut (Action Items)</div>
        <div class="content-text mb-3">
            {!! $report->action_items !!}
        </div>
        @endif

        {{-- AREA TANDA TANGAN / PENGESAHAN --}}
        <div class="d-flex justify-content-between align-items-end mt-5">
            {{-- QR Code / Token Validator (Kiri) --}}
            <div class="text-center small text-muted">
                <p class="mb-1">Validasi Digital Dokumen:</p>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ urlencode(request()->fullUrl()) }}" alt="QR Validation" class="border p-1 bg-white mb-1" width="80">
                <div style="font-size: 10px; font-family: monospace;">Token: {{ substr($report->digital_signature_token, 0, 12) }}...</div>
            </div>

            {{-- Kotak TTD Pejabat (Kanan) --}}
            <div class="signature-box">
                <p class="mb-1" style="font-size: 14px;">Surabaya, {{ \Carbon\Carbon::parse($report->approved_at ?? now())->translatedFormat('d F Y') }}</p>
                <p class="fw-bold mb-2" style="font-size: 14px;">{{ $report->approved_by_position ?? 'Pejabat Pengesah' }}</p>

                {{-- Status Pengesahan --}}
                <div class="my-3" style="min-height: 60px;">
                    @if($report->approval_status === 'approved')
                    <div class="border border-success text-success p-2 rounded text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">
                        <iconify-icon icon="solar:verified-check-bold"></iconify-icon><br>
                        DISAHKAN SECARA DIGITAL<br>
                        <span style="font-size: 9px; font-weight: normal;">pada {{ \Carbon\Carbon::parse($report->approved_at)->format('d/m/Y H:i') }} WIB</span>
                    </div>
                    @else
                    <div class="no-print my-2">
                        <form action="{{ route('activity_reports.public_approval_submit', $report->digital_signature_token) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2 shadow-sm" onclick="return confirm('Apakah Anda yakin ingin menyetujui dan mengesahkan Berita Acara ini?')">
                                <iconify-icon icon="solar:pen-2-line-duotone"></iconify-icon> Klik Untuk Mengesahkan
                            </button>
                        </form>
                    </div>
                    <div class="text-muted italic print-only my-4" style="font-size: 12px;">(Belum Disahkan)</div>
                    @endif
                </div>

                <p class="fw-bold mb-0 text-decoration-underline" style="font-size: 14px;">{{ $report->approved_by_name ?? '................................' }}</p>
                <p class="mb-0 text-muted" style="font-size: 12px;">{{ $report->approved_by_identifier ? 'NIP/NIDN: '.$report->approved_by_identifier : '' }}</p>
            </div>
        </div>

    </div>

</body>

</html>