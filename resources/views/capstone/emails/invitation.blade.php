<p>Yth. Dosen Pembimbing dan Dosen Penguji,</p>
<p>Berikut undangan ujian Capstone Design:</p>
<ul>
    <li>Judul: {{ $group->title ?: 'Belum ditetapkan' }}</li>
    <li>Ketua: {{ $group->leader?->name }}</li>
    <li>Tempat proyek: {{ $group->project_location ?: '-' }}</li>
    <li>Tanggal dan waktu: {{ $group->exam_date }} {{ $group->exam_time }}</li>
    <li>Ruangan: {{ $group->exam_room }}</li>
</ul>
<p>Undangan dapat dicetak melalui portal SEMAR UNITRI.</p>
