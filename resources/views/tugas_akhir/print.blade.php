<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ strtoupper(str_replace('-', ' ', $type)) }} Tugas Akhir</title>
    <style>
        body { font: 14px Arial, sans-serif; max-width: 800px; margin: 36px auto; color: #222; line-height: 1.5; }
        h1, .center { text-align: center; }
        h1 { font-size: 19px; text-transform: uppercase; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #444; padding: 8px; text-align: left; }
        .signatures { display: flex; justify-content: space-around; text-align: center; margin-top: 60px; }
        .signatures div { min-width: 150px; }
        @media print { .print-button { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">Cetak / Simpan PDF</button>
    <h1>
        @switch($type)
            @case('sk-sempro') Surat Keputusan Pembimbing dan Penguji Seminar Proposal @break
            @case('ba-sempro') Berita Acara Seminar Proposal @break
            @case('ba-komprehensif') Berita Acara Ujian Komprehensif @break
            @case('ba-sidang-ta') Berita Acara Sidang Tugas Akhir @break
        @endswitch
    </h1>
    <p class="center">Tahun Akademik {{ $application->semester?->nama ?? '-' }}</p>
    <table>
        <tr><th>Mahasiswa</th><td>{{ $application->student?->name }} ({{ $application->student?->identifier_number }})</td></tr>
        <tr><th>Tema Keahlian</th><td>{{ $application->expertise_theme }}</td></tr>
        <tr><th>Judul</th><td>{{ $application->title ?? '-' }}</td></tr>
        <tr><th>Pembimbing 1</th><td>{{ $application->p1?->nama_dosen ?? '-' }}</td></tr>
        <tr><th>Pembimbing 2</th><td>{{ $application->p2?->nama_dosen ?? '-' }}</td></tr>
        <tr><th>Penguji</th><td>{{ $application->examiner?->nama_dosen ?? '-' }}</td></tr>
        @if($type !== 'sk-sempro')
            @php
                $examDate = $type === 'ba-sidang-ta' ? $application->ta_exam_date : $application->sempro_exam_date;
                $examTime = $type === 'ba-sidang-ta' ? $application->ta_exam_time : $application->sempro_exam_time;
                $examRoom = $type === 'ba-sidang-ta' ? $application->ta_exam_room : $application->sempro_exam_room;
            @endphp
            <tr><th>Tanggal / Waktu Ujian</th><td>{{ $examDate ?? '-' }} {{ $examTime ?? '' }}</td></tr>
            <tr><th>Ruangan</th><td>{{ $examRoom ?? '-' }}</td></tr>
            @php
                $assessment = $type === 'ba-komprehensif' ? 'sempro_komprehensif' : ($type === 'ba-sidang-ta' ? 'nilai_ta_final' : 'sempro_pelaksanaan');
                $scores = $application->scores->where('assessment_type', $assessment);
                $average = $scores->count() === 3 ? number_format((float) $scores->avg('score'), 2) : 'Belum lengkap';
            @endphp
            <tr><th>Nilai P1</th><td>{{ $scores->firstWhere('evaluator_role', 'P1')?->score ?? '-' }}</td></tr>
            <tr><th>Nilai P2</th><td>{{ $scores->firstWhere('evaluator_role', 'P2')?->score ?? '-' }}</td></tr>
            <tr><th>Nilai Penguji</th><td>{{ $scores->firstWhere('evaluator_role', 'PU')?->score ?? '-' }}</td></tr>
            <tr><th>Nilai Rata-rata</th><td>{{ $average }}</td></tr>
        @endif
    </table>
    @if($type === 'sk-sempro')
    <p>Menetapkan dosen Pembimbing 1, Pembimbing 2, dan Penguji sesuai data plotting terbaru dalam sistem.</p>
    @else
    <p>Catatan / hasil: ___________________________________________________________</p>
    @endif
    <div class="signatures"><div>Pembimbing 1<br><br><br><br>{{ $application->p1?->nama_dosen }}</div><div>Pembimbing 2<br><br><br><br>{{ $application->p2?->nama_dosen }}</div><div>Penguji<br><br><br><br>{{ $application->examiner?->nama_dosen }}</div></div>
</body>
</html>
