<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Nilai PKL</title>
    <style>
        body { font-family: Arial, sans-serif; color: #172033; margin: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccd3de; padding: 8px; text-align: left; }
        th { background: #eef2f7; }
        .actions { display: flex; justify-content: flex-end; gap: 8px; }
        @media print { .actions { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="actions">
        <a href="{{ route('pkl.admin.rekap-nilai.export', request()->query()) }}">Export CSV</a>
        <button type="button" onclick="window.print()">Cetak</button>
    </div>
    <h1>Rekap Nilai PKL</h1>
    <table>
        <thead>
            <tr><th>NIM</th><th>Nama Mahasiswa</th><th>Instansi</th><th>Semester</th><th>Nilai Akhir</th><th>Huruf</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse($submissions as $pengajuan)
                @php($score = $pengajuan->nilai_rekap)
                <tr>
                    <td>{{ $pengajuan->mahasiswaRegistrasi?->nim ?? '-' }}</td>
                    <td>{{ $pengajuan->mahasiswaRegistrasi?->nama_mahasiswa ?? '-' }}</td>
                    <td>{{ $pengajuan->nama_instansi }}</td>
                    <td>{{ $pengajuan->id_semester ?? '-' }}</td>
                    <td>{{ $score === null ? '-' : number_format($score, 2) }}</td>
                    <td>{{ $score === null ? ($pengajuan->nilai_huruf ?? '-') : match(true) { $score >= 80 => 'A', $score >= 75 => 'B+', $score >= 70 => 'B', $score >= 65 => 'C+', $score >= 60 => 'C', $score >= 50 => 'D', default => 'E' } }}</td>
                    <td>{{ $pengajuan->status }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Belum ada data PKL untuk filter ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
