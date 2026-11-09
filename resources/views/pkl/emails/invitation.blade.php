<p>Yth. {{ $recipientName }},</p>
<p>Anda diundang untuk menghadiri ujian PKL mahasiswa berikut:</p>
<ul>
    <li>Nama: {{ $pengajuan->mahasiswaRegistrasi?->nama_mahasiswa ?? '-' }}</li>
    <li>NIM: {{ $pengajuan->mahasiswaRegistrasi?->nim ?? '-' }}</li>
    <li>Tempat PKL: {{ $pengajuan->nama_instansi }}</li>
    <li>Tanggal dan waktu: {{ $pengajuan->tanggal_seminar->format('d F Y, H:i') }} WIB</li>
    <li>Ruang ujian: {{ $pengajuan->ruangan_seminar }}</li>
</ul>
<p>Undangan cetak: <a href="{{ route('pkl.invitation', $pengajuan->id) }}">{{ route('pkl.invitation', $pengajuan->id) }}</a></p>
<p>Undangan ini dibuat oleh SEMAR UNITRI. Silakan menghubungi admin apabila jadwal perlu direvisi.</p>
