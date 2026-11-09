<p>Yth. Bapak/Ibu Dosen Pembimbing dan Penguji,</p>
<p>Dengan hormat, kami mengundang Bapak/Ibu untuk menghadiri {{ $examType }} mahasiswa berikut:</p>
<ul>
    <li>Mahasiswa: {{ $application->student?->name }}</li>
    <li>Tema: {{ $application->expertise_theme }}</li>
    <li>Judul: {{ $application->title ?: 'Belum ditentukan' }}</li>
    <li>Tanggal dan waktu: {{ $examType === 'Sidang Tugas Akhir' ? $application->ta_exam_date.' '.$application->ta_exam_time : $application->sempro_exam_date.' '.$application->sempro_exam_time }}</li>
    <li>Ruangan: {{ $examType === 'Sidang Tugas Akhir' ? $application->ta_exam_room : $application->sempro_exam_room }}</li>
</ul>
<p>Terima kasih.</p>
