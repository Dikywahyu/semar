<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Undangan Ujian PKL - {{ $pengajuan->mahasiswaRegistrasi?->nama_mahasiswa }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #172033; margin: 0; background: #f3f5f8; }
        main { max-width: 760px; margin: 32px auto; padding: 48px; background: #fff; border: 1px solid #d8dee8; }
        h1 { text-align: center; font-size: 22px; margin: 0 0 8px; }
        .subtitle { text-align: center; margin: 0 0 32px; }
        .detail { margin: 24px 0; }
        .detail div { display: grid; grid-template-columns: 180px 1fr; padding: 8px 0; border-bottom: 1px solid #e5e9f0; }
        .recipients { margin-top: 28px; }
        .actions { max-width: 760px; margin: 16px auto; text-align: right; }
        @media print { body { background: white; } main { margin: 0 auto; border: 0; } .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions"><button type="button" onclick="window.print()">Cetak Undangan</button></div>
    <main>
        <h1>UNDANGAN UJIAN PRAKTIK KERJA LAPANGAN</h1>
        <p class="subtitle">SEMAR UNITRI</p>
        <p>Dengan hormat, kami mengundang Bapak/Ibu untuk menghadiri ujian PKL mahasiswa berikut:</p>
        <div class="detail">
            <div><strong>Nama</strong><span>{{ $pengajuan->mahasiswaRegistrasi?->nama_mahasiswa ?? '-' }}</span></div>
            <div><strong>NIM</strong><span>{{ $pengajuan->mahasiswaRegistrasi?->nim ?? '-' }}</span></div>
            <div><strong>Tempat PKL</strong><span>{{ $pengajuan->nama_instansi }}</span></div>
            <div><strong>Tanggal & waktu</strong><span>{{ $pengajuan->tanggal_seminar->format('d F Y, H:i') }} WIB</span></div>
            <div><strong>Ruang ujian</strong><span>{{ $pengajuan->ruangan_seminar }}</span></div>
        </div>
        <div class="recipients">
            <strong>Undangan ditujukan kepada:</strong>
            <ul>
                @if($pengajuan->dosenPembimbing)
                    <li>{{ $pengajuan->dosenPembimbing->nama_dosen }} — Dosen Pembimbing / Penguji</li>
                @endif
                @foreach($pengajuan->dosenPenguji as $penguji)
                    <li>{{ $penguji->nama_dosen }} — {{ $penguji->pivot->peran ?? 'Dosen Penguji' }}</li>
                @endforeach
            </ul>
        </div>
        <p>Demikian undangan ini disampaikan. Atas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.</p>
    </main>
</body>
</html>
