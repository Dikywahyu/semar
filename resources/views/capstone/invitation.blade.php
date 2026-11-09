<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Undangan Ujian Capstone Design</title>
    <style>
        body { font-family: Arial, sans-serif; color: #222; margin: 40px auto; max-width: 760px; line-height: 1.5; }
        h1 { font-size: 20px; text-align: center; text-transform: uppercase; }
        .center { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        td { padding: 6px; vertical-align: top; }
        .actions { margin-bottom: 24px; }
        @media print { .actions { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="actions"><button onclick="window.print()">Cetak / Simpan PDF</button></div>
    <h1>Undangan Ujian Capstone Design</h1>
    <p>Yth. Dosen Pembimbing dan Dosen Penguji,</p>
    <p>Dengan hormat, kami mengundang Bapak/Ibu untuk menghadiri ujian Capstone Design berikut:</p>
    <table>
        <tr><td width="180">Judul</td><td>: {{ $group->title ?: 'Belum ditetapkan' }}</td></tr>
        <tr><td>Kelompok</td><td>: {{ $group->leader?->name }}@foreach($group->members->where('status', 'accepted') as $member), {{ $member->student?->name }}@endforeach</td></tr>
        <tr><td>Bidang Keahlian</td><td>: {{ $group->expertise?->name }}</td></tr>
        <tr><td>Tempat Proyek</td><td>: {{ $group->project_location ?: '-' }}</td></tr>
        <tr><td>Hari / Tanggal</td><td>: {{ $group->exam_date }}</td></tr>
        <tr><td>Waktu</td><td>: {{ $group->exam_time }}</td></tr>
        <tr><td>Ruangan</td><td>: {{ $group->exam_room }}</td></tr>
        <tr><td>Pembimbing</td><td>: {{ $group->supervisor?->nama_dosen }}</td></tr>
        <tr><td>Penguji</td><td>: {{ $group->examiner?->nama_dosen }}</td></tr>
    </table>
    <p>Mohon hadir tepat waktu. Terima kasih atas perhatian dan kehadirannya.</p>
    <p class="center" style="margin-top: 60px;">Koordinator Capstone Design</p>
</body>
</html>
