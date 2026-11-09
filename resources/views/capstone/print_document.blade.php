<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $type === 'berita-acara' ? 'Berita Acara Ujian' : ($type === 'sk' ? 'Surat Keputusan' : 'Kesepakatan Bersama') }} Capstone Design</title>
    <style>
        body { font-family: Arial, sans-serif; color: #222; margin: 36px auto; max-width: 800px; line-height: 1.5; }
        h1 { text-align: center; font-size: 19px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        td, th { border: 1px solid #444; padding: 8px; }
        .signatures { display: flex; justify-content: space-around; text-align: center; margin-top: 60px; }
        .signatures div { min-width: 180px; }
        @media print { .actions { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="actions"><button onclick="window.print()">Cetak / Simpan PDF</button></div>
    <h1>{{ $type === 'berita-acara' ? 'Berita Acara Ujian' : ($type === 'sk' ? 'Surat Keputusan' : 'Kesepakatan Bersama') }}<br>Capstone Design</h1>
    @if($type === 'sk')
    <p>Nomor: ______________________________</p>
    <p>Menetapkan kelompok Capstone Design Semester 7 sebagai berikut:</p>
    @else
    <p>Program Studi: ______________________________</p>
    @endif
    <table>
        <tr><th>Judul</th><td>{{ $group->title ?: 'Belum ditetapkan' }}</td></tr>
        <tr><th>Ketua Kelompok</th><td>{{ $group->leader?->name }}</td></tr>
        <tr><th>Anggota</th><td>@foreach($group->members->where('status', 'accepted') as $member){{ $member->student?->name }}@if(!$loop->last), @endif @endforeach</td></tr>
        <tr><th>Bidang Keahlian</th><td>{{ $group->expertise?->name }}</td></tr>
        <tr><th>Tempat Proyek</th><td>{{ $group->project_location ?: '-' }}</td></tr>
        <tr><th>Tanggal / Waktu</th><td>{{ $group->exam_date }} / {{ $group->exam_time }}</td></tr>
        <tr><th>Ruangan</th><td>{{ $group->exam_room }}</td></tr>
        @if($type === 'berita-acara')
        <tr><th>Hasil Ujian</th><td>Lulus / Revisi / Tidak Lulus</td></tr>
        <tr><th>Catatan</th><td style="height: 80px"></td></tr>
        @elseif($type === 'kesepakatan')
        <tr><th>Kesepakatan / Target Revisi</th><td style="height: 100px"></td></tr>
        <tr><th>Batas Pengumpulan</th><td>______________________________</td></tr>
        @endif
    </table>
    <div class="signatures">
        <div>Dosen Pembimbing<br><br><br><br>{{ $group->supervisor?->nama_dosen }}</div>
        <div>Dosen Penguji<br><br><br><br>{{ $group->examiner?->nama_dosen }}</div>
    </div>
</body>
</html>
