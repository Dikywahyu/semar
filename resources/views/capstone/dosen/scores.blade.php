<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Nilai Capstone Design</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #555; padding: 8px; text-align: left; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Cetak / Simpan PDF</button>
    <a href="{{ route('capstone.dosen.scores.export', $group->id) }}">Unduh Excel</a>
    <h2>Rekap Nilai Capstone Design</h2>
    <p>Judul: {{ $group->title }}<br>Pembimbing: {{ $group->supervisor?->nama_dosen }}</p>
    <table>
        <thead><tr><th>Mahasiswa</th><th>Komponen</th><th>Kategori</th><th>Penilai</th><th>Nilai</th></tr></thead>
        <tbody>
        @forelse($group->studentScores as $score)
            <tr>
                <td>{{ $score->student?->name }}</td>
                <td>{{ $score->component?->name }}</td>
                <td>{{ ucfirst($score->component?->category ?? '') }}</td>
                <td>{{ $score->evaluator?->nama_dosen }}</td>
                <td>{{ number_format((float) $score->score, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Nilai belum tersedia.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
