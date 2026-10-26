@extends('layouts.app')

@section('content')
@php
    $mahasiswa = $mahasiswa ?? auth()->user()?->mahasiswaRegistrasi;
    $pkl = $pkl ?? null;
    $isAcademicViewer = auth()->user()?->hasAnyRole(['Super Admin', 'Kaprodi', 'Admin']) ?? false;
    $selectedView = request('tampilan', 'mahasiswa');
    $canActAsMahasiswa = auth()->user()?->hasAnyRole(['mahasiswa', 'Mahasiswa']) ?? false;
    $canActAsPembimbing = auth()->user()?->hasAnyRole(['dosen_pembimbing', 'Dosen']) ?? false;
    $canActAsPenguji = auth()->user()?->hasAnyRole(['dosen_penguji', 'Dosen']) ?? false;
    $showMahasiswa = $selectedView === 'mahasiswa' && ($canActAsMahasiswa || $isAcademicViewer);
    $showPembimbing = $selectedView === 'dosen_pembimbing' && ($canActAsPembimbing || $isAcademicViewer);
    $showPenguji = $selectedView === 'dosen_penguji' && ($canActAsPenguji || $isAcademicViewer);
    $canPerformPembimbingActions = $showPembimbing && $canActAsPembimbing;
    $canPerformPengujiActions = $showPenguji && $canActAsPenguji;
    $viewOptions = [
        'mahasiswa' => 'Mahasiswa',
        'dosen_pembimbing' => 'Dosen Pembimbing',
        'dosen_penguji' => 'Dosen Penguji',
    ];
    if (! array_key_exists($selectedView, $viewOptions)) {
        $selectedView = 'mahasiswa';
    }
    $statusAkademik = [
        'PKL' => $mahasiswa->status_pkl ?? 'Belum Ambil',
        'Capstone Design (SMT 7)' => $mahasiswa->status_capstone ?? 'Belum Ambil',
        'Sempro (SMT 7)' => $mahasiswa->status_sempro ?? 'Belum Ambil',
        'Semhas (SMT 7)' => $mahasiswa->status_semhas ?? 'Belum Ambil',
        'Tugas Akhir (SMT 7/8)' => $mahasiswa->status_ta ?? 'Belum Ambil',
    ];
    $dokumenTahap1 = [
        'proposal' => 'Berkas Proposal',
        'surat_jalan_pkl' => 'Surat Jalan PKL',
        'surat_balasan_instansi' => 'Surat Balasan Instansi',
        'acc_pembimbing_awal' => 'ACC Pembimbing Awal',
    ];
    $dokumenTahap2 = [
        'draft_laporan' => 'Draft Laporan',
        'kartu_bimbingan' => 'Kartu Konsultasi / Bimbingan',
        'transkrip_sementara' => 'Transkrip Sementara',
    ];
    $routeMapUpload = $pkl ? route('pkl.route-map.upload', ['pengajuan' => $pkl->getKey(), 'tahap' => 1]) : '#';
    $routeMapUpload2 = $pkl ? route('pkl.route-map.upload', ['pengajuan' => $pkl->getKey(), 'tahap' => 2]) : '#';
    $routeMapUpload3 = $pkl ? route('pkl.route-map.upload', ['pengajuan' => $pkl->getKey(), 'tahap' => 3]) : '#';
    $badgeClass = static function ($status) {
        return match (strtolower((string) $status)) {
            'valid', 'lulus', 'selesai', 'disetujui' => 'rmap-badge-success',
            'ditolak', 'gagal' => 'rmap-badge-danger',
            'berjalan', 'diproses' => 'rmap-badge-info',
            default => 'rmap-badge-muted',
        };
    };
@endphp

<style>
    .route-map { --rm-ink:#26364b; --rm-muted:#7d8da2; --rm-line:#e7edf5; --rm-primary:#5b7cff; color:var(--rm-ink); background:linear-gradient(135deg,#f6f8fd 0%,#f0f4fa 100%); min-height:100vh; padding:1.5rem!important; }
    .route-map .rmap-card { background:#fff; border:1px solid rgba(226,232,240,.7); border-radius:1.1rem; box-shadow:0 5px 16px rgba(34,55,90,.055); }
    .route-map .rmap-hero { border-radius:1rem; background:linear-gradient(110deg,#192958 0%,#3157c8 100%); padding:1.5rem 1.75rem; color:#fff; box-shadow:0 10px 24px rgba(37,71,159,.15); }
    .route-map .rmap-pipeline { display:grid; grid-template-columns:repeat(7,minmax(110px,1fr)); gap:.5rem; overflow-x:auto; padding:1.1rem .4rem .3rem; }
    .route-map .rmap-pipeline-step { position:relative; text-align:center; min-width:110px; }
    .route-map .rmap-pipeline-step:not(:last-child):after { content:""; height:3px; background:#dfe6f0; position:absolute; top:1rem; left:calc(50% + 1.2rem); right:calc(-50% + 1.2rem); }
    .route-map .rmap-pipeline-step.is-done:after { background:#36c9a3; }
    .route-map .rmap-pipeline-icon { position:relative; z-index:1; margin:auto; display:flex; width:2.2rem; height:2.2rem; border:2px solid #dbe3ef; background:white; color:#7890af; border-radius:50%; align-items:center; justify-content:center; font-weight:800; }
    .route-map .rmap-pipeline-step.is-current .rmap-pipeline-icon { border-color:#ff9d00; color:#f08a00; }
    .route-map .rmap-pipeline-step.is-done .rmap-pipeline-icon { border-color:#17c9a5; color:#0eae8d; }
    .route-map .rmap-table th { background:#f5f8fc; color:#35445b; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
    .route-map .rmap-table td { border-color:#edf1f6; vertical-align:middle; }
    .route-map .rmap-badge { display:inline-flex; align-items:center; border-radius:9999px; padding:.25rem .65rem; font-size:.72rem; font-weight:700; white-space:nowrap; }
    .route-map .rmap-badge-success { background:#dcfce7; color:#166534; }
    .route-map .rmap-badge-danger { background:#fee2e2; color:#991b1b; }
    .route-map .rmap-badge-info { background:#dbeafe; color:#1e40af; }
    .route-map .rmap-badge-muted { background:#f1f5f9; color:#475569; }
    .route-map .rmap-input { display:block; width:100%; border:1px solid #cbd5e1; border-radius:.65rem; padding:.65rem .8rem; color:#0f172a; background:#fff; }
    .route-map .rmap-input:focus { outline:2px solid #c7d2fe; border-color:#6366f1; }
    .route-map .rmap-btn { display:inline-flex; align-items:center; justify-content:center; border:0; border-radius:.65rem; padding:.65rem 1rem; font-weight:700; cursor:pointer; }
    .route-map .rmap-btn-primary { color:white; background:#4f46e5; }
    .route-map .rmap-btn-secondary { color:#334155; background:#e2e8f0; }
    .route-map .rmap-step { position:relative; flex:1; min-width:150px; }
    .route-map .rmap-step:not(:last-child):after { content:""; position:absolute; left:3rem; right:-50%; top:1.1rem; height:2px; background:#cbd5e1; }
    .route-map .rmap-step.is-done:not(:last-child):after { background:#6366f1; }
    .route-map .rmap-step-dot { position:relative; z-index:1; display:flex; width:2.25rem; height:2.25rem; align-items:center; justify-content:center; border:2px solid #cbd5e1; border-radius:50%; background:white; color:#64748b; font-weight:800; }
    .route-map .rmap-step.is-done .rmap-step-dot,.route-map .rmap-step.is-active .rmap-step-dot { color:white; border-color:#4f46e5; background:#4f46e5; }
    @media(max-width:640px) { .route-map .rmap-step { min-width:110px; } .route-map .rmap-step:not(:last-child):after { right:-35%; } }
</style>

<div class="route-map col-12">
    @if ($isAcademicViewer)
        <section class="rmap-card mb-6 p-5 sm:p-6">
            <div class="mb-4">
                <span class="rmap-badge rmap-badge-info mb-2">AKSES AKADEMIK</span>
                <h2 class="mb-1 text-lg font-bold">Pilih tampilan peran</h2>
                <p class="mb-0 text-sm text-slate-500">Super Admin dan Kaprodi dapat meninjau alur PKL dari sudut pandang mahasiswa, pembimbing, atau penguji.</p>
            </div>
            <nav class="flex flex-wrap gap-2" aria-label="Pilihan tampilan peran">
                @foreach ($viewOptions as $view => $label)
                    <a href="{{ request()->fullUrlWithQuery(['tampilan' => $view]) }}"
                       class="rmap-btn {{ $selectedView === $view ? 'rmap-btn-primary' : 'rmap-btn-secondary' }}"
                       @if ($selectedView === $view) aria-current="page" @endif>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
            <p class="mb-0 mt-3 text-xs text-slate-500">Tampilan: <strong>{{ $viewOptions[$selectedView] }}</strong></p>
            <form action="{{ route('pkl.route-map') }}" method="GET" class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto]">
                <input type="hidden" name="tampilan" value="{{ $selectedView }}">
                <label class="block text-xs font-semibold text-slate-600">Pilih pengajuan mahasiswa
                    <select class="rmap-input mt-1" name="pengajuan" onchange="this.form.submit()">
                        <option value="">Pengajuan terbaru</option>
                        @foreach (($daftarPengajuan ?? collect()) as $item)
                            <option value="{{ $item->getKey() }}" @selected((string) request('pengajuan') === (string) $item->getKey())>
                                {{ $item->mahasiswaRegistrasi?->nim ?? 'Tanpa NIM' }} — {{ $item->mahasiswaRegistrasi?->nama_mahasiswa ?? 'Mahasiswa' }} ({{ $item->status }})
                            </option>
                        @endforeach
                    </select>
                </label>
                <button class="rmap-btn rmap-btn-secondary self-end" type="submit">Tampilkan</button>
            </form>
        </section>
    @endif

    @if (! $pkl)
        <div class="rmap-card mb-6 p-6 text-center">
            <h2 class="mb-2 text-lg font-bold">Belum ada pengajuan PKL</h2>
            <p class="mb-0 text-sm text-slate-500">Data tahapan akan tampil setelah mahasiswa mengirim pengajuan PKL.</p>
        </div>
    @endif

    <header class="rmap-hero mb-4">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
            <div>
                <h1 class="mb-2 text-2xl font-bold sm:text-3xl">Dashboard & Monitoring PKL</h1>
                <p class="mb-0 max-w-2xl text-sm text-indigo-100">Kelola pengajuan, pantau progres tahapan, verifikasi berkas, dan jadwal seminar mahasiswa.</p>
            </div>
            <div class="rounded-xl border border-white/20 bg-white/10 px-4 py-3">
                <div class="text-xs text-indigo-100">Status PKL</div>
                <div class="mt-1 text-lg font-bold">{{ $statusAkademik['PKL'] }}</div>
            </div>
        </div>
    </header>

    <div class="rmap-card mb-6 p-5 sm:p-6">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-2">
            <div><h2 class="mb-1 text-lg font-bold">Pipeline Perjalanan Mahasiswa & Tugas Admin</h2><p class="mb-0 text-sm text-slate-500">Ringkasan jumlah mahasiswa pada setiap tahapan PKL.</p></div>
            <span class="rmap-badge {{ $badgeClass($pkl->status ?? 'Belum Ambil') }}">{{ $pkl->status ?? 'Belum Ambil' }}</span>
        </div>
        <div class="flex gap-3 overflow-x-auto pb-2">
            @foreach ([1 => 'Pendaftaran PKL', 2 => 'Pasca PKL & Pra-Seminar', 3 => 'Ujian & Kelulusan'] as $step => $label)
                @php $stepStatus = (int) ($pkl->tahap_aktif ?? 1); @endphp
                <div class="rmap-step {{ $step < $stepStatus ? 'is-done' : ($step === $stepStatus ? 'is-active' : '') }}">
                    <div class="rmap-step-dot">{{ $step < $stepStatus ? '✓' : $step }}</div>
                    <div class="mt-2 text-xs font-bold {{ $step === $stepStatus ? 'text-indigo-700' : 'text-slate-600' }}">{{ $label }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Pengajuan Awal', $stats['pengajuan'] ?? 0, 'ti ti-file-description'], ['Pelaksanaan PKL', $stats['pelaksanaan'] ?? 0, 'ti ti-briefcase'], ['Verifikasi Berkas', $stats['verifikasi'] ?? 0, 'ti ti-file-alert'], ['Dijadwalkan Ujian', $stats['ujian'] ?? 0, 'ti ti-calendar-event']] as [$label, $count, $icon])
            <div class="rmap-card flex items-center gap-3 p-4"><span class="grid h-11 w-11 place-items-center rounded-xl bg-indigo-50 text-lg text-indigo-500"><i class="{{ $icon }}"></i></span><div><div class="text-xs font-semibold text-slate-500">{{ $label }}</div><div class="text-2xl font-bold">{{ $count }}</div></div></div>
        @endforeach
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($statusAkademik as $label => $status)
            <div class="rmap-card p-4">
                <div class="mb-2 text-xs font-semibold text-slate-500">{{ $label }}</div>
                <span class="rmap-badge {{ $badgeClass($status) }}">{{ $status }}</span>
            </div>
        @endforeach
    </div>

    @if ($isAcademicViewer)
        <section class="rmap-card mb-6 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 p-5">
                <div><h2 class="mb-1 text-lg font-bold">Daftar Pengajuan & Progress PKL Mahasiswa</h2><p class="mb-0 text-sm text-slate-500">Pilih mahasiswa untuk membuka peta tahapan dan detail dokumennya.</p></div>
                <form action="{{ route('pkl.route-map') }}" method="GET" class="flex gap-2"><input type="hidden" name="tampilan" value="{{ $selectedView }}"><input class="rmap-input" name="keyword" value="{{ request('keyword') }}" placeholder="Cari nama / NIM"><button class="rmap-btn rmap-btn-primary" type="submit">Cari</button></form>
            </div>
            <div class="table-responsive px-4 pb-4">
                <table class="table rmap-table mb-0 align-middle">
                    <thead><tr><th>No</th><th>Mahasiswa (NIM)</th><th>Instansi Tujuan</th><th>Dosen Pembimbing</th><th>Status Tahapan</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($submissions as $index => $item)
                            <tr>
                                <td>{{ $submissions->firstItem() + $index }}</td>
                                <td><strong>{{ $item->mahasiswaRegistrasi?->nama_mahasiswa ?? 'Mahasiswa' }}</strong><div class="small text-secondary">{{ $item->mahasiswaRegistrasi?->nim ?? '—' }}</div></td>
                                <td>{{ $item->nama_instansi }}</td>
                                <td>{{ $item->dosenPembimbing?->nama_dosen ?? 'Belum ditentukan' }}</td>
                                <td><span class="rmap-badge {{ $badgeClass($item->status) }}">{{ $item->status }}</span></td>
                                <td><a class="rmap-btn rmap-btn-secondary py-2" href="{{ route('pkl.route-map', ['pengajuan' => $item->getKey(), 'tampilan' => $selectedView]) }}">Lihat detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-5 text-center text-secondary">Belum ada pengajuan PKL.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 pb-4">{{ $submissions->links() }}</div>
        </section>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <section class="rmap-card p-5 sm:p-6 xl:col-span-2">
            <div class="mb-5 flex items-start justify-between gap-3">
                <div><span class="rmap-badge rmap-badge-info mb-2">TAHAP 1 · SMT 6 AWAL</span><h2 class="mb-1 text-xl font-bold">Pendaftaran PKL</h2><p class="mb-0 text-sm text-slate-500">Pengumpulan dan verifikasi dokumen awal.</p></div>
            </div>
            @foreach ($dokumenTahap1 as $key => $label)
        @php
            $berkas = $pkl?->berkasSeminar?->firstWhere('jenis_berkas', $key);
            $legacyPath = $key === 'proposal' ? $pkl?->berkas_proposal : null;
            $doc = $berkas?->path_berkas ?? $legacyPath;
            $docStatus = $berkas?->status_verifikasi ?? ($legacyPath ? 'Valid' : 'Pending');
        @endphp
                <div class="mb-4 rounded-xl border border-slate-200 p-4">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2"><h3 class="mb-0 text-sm font-bold">{{ $label }}</h3><span class="rmap-badge {{ $badgeClass($docStatus) }}">{{ $docStatus }}</span></div>
                    @if ($showMahasiswa)
                        <form action="{{ $routeMapUpload }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            @csrf
                            <input type="hidden" name="jenis_dokumen" value="{{ $key }}">
                <input type="hidden" name="field" value="{{ $key === 'proposal' ? 'berkas_proposal' : $key }}">
                <input class="rmap-input" type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
                            <button class="rmap-btn rmap-btn-primary shrink-0" type="submit">Unggah</button>
                        </form>
                    @else
                        <div class="flex flex-wrap items-center gap-3 text-sm">
                            @if ($doc)<a class="rmap-btn rmap-btn-primary shadow-sm hover:-translate-y-0.5 hover:shadow-md transition" href="{{ route('pkl.file', ['path' => $doc]) }}" target="_blank" rel="noopener"><i class="ti ti-eye mr-1"></i>Lihat berkas</a>@else<span class="text-slate-500">Berkas belum tersedia</span>@endif
                            @if (auth()->user()?->hasAnyRole(['admin', 'Admin', 'Super Admin', 'Kaprodi']))
                                @if($berkas)
                                <form action="{{ route('pkl.route-map.verify', ['pengajuan' => $pkl?->getKey()]) }}" method="POST" class="grid w-full grid-cols-1 gap-2 sm:grid-cols-[1fr_auto_auto]">
                                    @csrf<input type="hidden" name="berkas_id" value="{{ $berkas?->id }}">
                                    <input class="rmap-input" name="catatan" value="{{ $berkas?->catatan_verifikator }}" placeholder="Catatan perbaikan" {{ $berkas ? '' : 'disabled' }}>
                                    <button class="rmap-btn rmap-btn-primary" name="status" value="Valid" type="submit" {{ $berkas ? '' : 'disabled' }}>Validasi</button>
                                    <button class="rmap-btn rmap-btn-secondary" name="status" value="Ditolak" type="submit" {{ $berkas ? '' : 'disabled' }}>Minta perbaikan</button>
                                </form>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
            @if ($showPembimbing || $showPenguji)
                <p class="mb-0 text-xs text-slate-500">Mode baca saja: dosen dapat melihat dan mengunduh ringkasan berkas di atas.</p>
            @endif
        </section>

        <aside class="rmap-card p-5 sm:p-6">
            <span class="rmap-badge rmap-badge-info mb-2">REKAP NILAI</span>
            <h2 class="mb-4 text-xl font-bold">Ringkasan Akhir</h2>
            <div class="mb-3 flex justify-between border-b border-slate-100 py-3 text-sm"><span class="text-slate-500">Nilai akhir</span><strong>{{ $pkl->nilai_akhir ?? '—' }}</strong></div>
            <div class="mb-3 flex justify-between border-b border-slate-100 py-3 text-sm"><span class="text-slate-500">Nilai huruf</span><strong>{{ $pkl->nilai_huruf ?? '—' }}</strong></div>
            <div class="flex justify-between py-3 text-sm"><span class="text-slate-500">Status kelulusan</span><span class="rmap-badge {{ $badgeClass($pkl->status ?? 'Belum Ambil') }}">{{ $pkl->status ?? 'Belum Ambil' }}</span></div>
        </aside>
    </div>

    <section class="rmap-card mt-6 p-5 sm:p-6">
        <div class="mb-5"><span class="rmap-badge rmap-badge-info mb-2">TAHAP 2 · PASCA PKL</span><h2 class="mb-1 text-xl font-bold">Pasca PKL & Pra-Seminar</h2><p class="mb-0 text-sm text-slate-500">Siapkan dokumen seminar, persetujuan pembimbing, dan jadwal ujian.</p></div>
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div>
                @foreach ($dokumenTahap2 as $key => $label)
                    @php
                        $kind = match ($key) { 'draft_laporan' => 'draft_laporan', 'kartu_bimbingan' => 'kartu_konsultasi', default => $key };
                        $berkas = $pkl?->berkasSeminar?->firstWhere('jenis_berkas', $kind);
                        $legacyField = $key === 'draft_laporan' ? 'berkas_draft_laporan' : ($key === 'kartu_bimbingan' ? 'berkas_kartu_bimbingan' : null);
                        $doc = $berkas?->path_berkas ?? ($legacyField ? $pkl?->{$legacyField} : null);
                    @endphp
                    <div class="mb-4 rounded-xl border border-slate-200 p-4"><div class="mb-3 flex items-center justify-between gap-2"><h3 class="mb-0 text-sm font-bold">{{ $label }}</h3><span class="rmap-badge {{ $badgeClass(data_get($pkl, 'verifikasi.' . $key, 'Pending')) }}">{{ data_get($pkl, 'verifikasi.' . $key, 'Pending') }}</span></div>
                        @if ($showMahasiswa)
                        <form action="{{ $routeMapUpload2 }}" method="POST" enctype="multipart/form-data" class="flex gap-2">@csrf<input type="hidden" name="jenis_dokumen" value="{{ $key }}"><input class="rmap-input" type="file" name="{{ $key }}" required><button class="rmap-btn rmap-btn-primary" type="submit">Unggah</button></form>
                        @else
                            @if ($doc)<a class="rmap-btn rmap-btn-primary text-sm shadow-sm hover:-translate-y-0.5 hover:shadow-md transition" href="{{ route('pkl.file', ['path' => $doc]) }}" target="_blank" rel="noopener"><i class="ti ti-eye mr-1"></i>Lihat berkas</a>@else<span class="text-sm text-slate-500">Berkas belum tersedia</span>@endif
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="space-y-4">
                <div class="rounded-xl bg-slate-50 p-4"><h3 class="mb-3 text-sm font-bold">ACC Pembimbing & Usulan Jadwal</h3>
                    @if ($canPerformPembimbingActions)
                        <form action="{{ route('pkl.route-map.acc', ['pengajuan' => $pkl?->getKey()]) }}" method="POST" class="mb-4">@csrf<input type="hidden" name="is_acc_seminar" value="1"><button class="rmap-btn rmap-btn-primary" type="submit">Berikan ACC Ujian Seminar</button></form>
                    @endif
                    @if ($showMahasiswa && $pkl)
                        <form action="{{ route('pkl.route-map.schedule.propose', ['pengajuan' => $pkl?->getKey()]) }}" method="POST" class="space-y-3">@csrf
                            <label class="block text-xs font-semibold text-slate-600">Usulan dosen pembimbing</label><select class="rmap-input" name="dosen_pembimbing_id"><option value="">Pertahankan dosen sekarang</option>@foreach(\App\Models\Dosen\Dosen::where('aktif', true)->orderBy('nama_dosen')->get() as $dosenPilihan)<option value="{{ $dosenPilihan->id_dosen }}" @selected($pkl->dosen_pembimbing_id === $dosenPilihan->id_dosen)>{{ $dosenPilihan->nama_dosen }}</option>@endforeach</select>
                            <label class="block text-xs font-semibold text-slate-600">Usulan tanggal & waktu ujian</label><input class="rmap-input" type="datetime-local" name="tanggal_seminar" value="{{ $pkl->tanggal_seminar?->format('Y-m-d\TH:i') }}" required>
                            <label class="block text-xs font-semibold text-slate-600">Usulan ruangan</label><input class="rmap-input" name="ruangan_seminar" value="{{ $pkl->ruangan_seminar }}" placeholder="Contoh: Ruang Sidang 2" required>
                            <button class="rmap-btn rmap-btn-primary" type="submit">Ajukan usulan untuk admin</button>
                            <p class="mb-0 text-xs text-slate-500">Jadwal resmi ditetapkan admin.</p>
                        </form>
                    @elseif ($showMahasiswa)
                        <p class="mb-0 text-xs text-slate-500">Ajukan PKL terlebih dahulu untuk mengusulkan jadwal seminar.</p>
                    @endif
                    @if ($showPembimbing || $showPenguji || auth()->user()?->hasAnyRole(['admin', 'Admin', 'Super Admin', 'Kaprodi']))<p class="mb-0 text-sm text-slate-600">ACC seminar: <strong>{{ ($pkl->is_acc_seminar ?? false) ? 'Sudah' : 'Belum' }}</strong></p>@endif
                </div>
                @if ($pkl && auth()->user()?->hasAnyRole(['admin', 'Admin', 'Super Admin', 'Kaprodi']))
                    <div class="rounded-xl bg-indigo-50/70 p-4"><h3 class="mb-3 text-sm font-bold">Penetapan Jadwal & Ruangan</h3>
                        <form action="{{ route('pkl.route-map.schedule.set', ['pengajuan' => $pkl?->getKey()]) }}" method="POST" class="space-y-3">@csrf
                            <input class="rmap-input" type="datetime-local" name="tanggal_seminar" value="{{ $pkl->tanggal_seminar ?? '' }}" required><input class="rmap-input" name="ruangan_seminar" value="{{ $pkl->ruangan_seminar ?? '' }}" placeholder="Ruangan seminar" required>
                            <button class="rmap-btn rmap-btn-primary" type="submit">Tetapkan & kirim undangan</button>
                        </form>
                        <div class="my-4 border-t border-indigo-100"></div><h4 class="mb-2 text-xs font-bold uppercase text-slate-600">Revisi / Penjadwalan Ulang</h4>
                        <form action="{{ route('pkl.route-map.schedule.set', ['pengajuan' => $pkl?->getKey()]) }}" method="POST" class="space-y-3">@csrf<input type="hidden" name="penjadwalan_ulang" value="1">
                            <input class="rmap-input" type="datetime-local" name="tanggal_seminar_baru" required><input class="rmap-input" name="ruangan_seminar_baru" placeholder="Ruangan baru" required><textarea class="rmap-input" name="alasan" rows="2" placeholder="Catatan perubahan"></textarea><button class="rmap-btn rmap-btn-secondary" type="submit">Simpan jadwal baru & kirim undangan</button>
                        </form>
                    </div>
                @endif
                @if ($showPenguji && $pkl)
                    <div class="rounded-xl border border-slate-200 p-4"><h3 class="mb-3 text-sm font-bold">Jadwal Seminar</h3><p class="mb-1 text-sm">{{ $pkl->tanggal_seminar ?? 'Belum ditetapkan' }}</p><p class="mb-3 text-sm text-slate-500">{{ $pkl->ruangan_seminar ?? 'Ruangan belum ditetapkan' }}</p>@if($pkl->berkas_draft_laporan)<a class="text-sm font-semibold text-indigo-700 underline" href="{{ route('pkl.file', ['path' => $pkl->berkas_draft_laporan]) }}" target="_blank" rel="noopener">Unduh draft laporan</a>@endif</div>
                @endif
            </div>
        </div>
    </section>

    <section class="rmap-card mt-6 p-5 sm:p-6">
        <div class="mb-5"><span class="rmap-badge rmap-badge-info mb-2">TAHAP 3 · UJIAN & KELULUSAN</span><h2 class="mb-1 text-xl font-bold">Tahap Akhir, Ujian & Kelulusan</h2><p class="mb-0 text-sm text-slate-500">Unduh formulir, unggah revisi, input penilaian, dan validasi kelulusan.</p></div>
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-200 p-4"><h3 class="mb-3 text-sm font-bold">Dokumen Hub</h3>
                    @foreach ([['Formulir Pendaftaran Ujian','formulir_pendaftaran_ujian'],['Berita Acara Ujian','berita_acara_ujian'],['Formulir Penilaian','formulir_nilai']] as [$label, $file])
                        @if (!empty($dokumenFormulir[$file]))<a class="mb-2 flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm font-semibold text-indigo-700" href="{{ route('pkl.route-map.document', ['dokumen' => $file]) }}"><span>{{ $label }}</span><span>↓</span></a>@else<div class="mb-2 flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-500"><span>{{ $label }}</span><span>Belum tersedia</span></div>@endif
                    @endforeach
            </div>
            <div class="rounded-xl border border-slate-200 p-4"><h3 class="mb-3 text-sm font-bold">Unggah Berkas Akhir</h3>
                @if ($showMahasiswa)
                    @foreach ([['formulir_pendaftaran_ujian','Formulir Pendaftaran Ujian'],['berita_acara_ujian','Berita Acara Ujian'],['formulir_nilai','Formulir Nilai'],['permohonan_revisi','Formulir Permohonan Revisi'],['laporan_final','Laporan Final'],['lampiran_teknis','Lampiran Teknis (AutoCAD / RAB)'],['acc_pembimbing_akhir','ACC Pembimbing Akhir'],['rekap_nilai','Rekap Nilai']] as [$key,$label])
                        @php $doc = $pkl?->berkasSeminar?->firstWhere('jenis_berkas', $key); @endphp
                        <form action="{{ $routeMapUpload3 }}" method="POST" enctype="multipart/form-data" class="mb-3 rounded-lg border border-slate-200 p-3">@csrf<input type="hidden" name="jenis_dokumen" value="{{ $key }}">
                            <label class="mb-2 block text-xs font-semibold text-slate-600">{{ $label }}<input class="rmap-input mt-1" type="file" name="{{ $key }}" accept=".pdf,.jpg,.jpeg,.png" required></label>
                            <div class="flex items-center justify-between gap-2"><span class="text-xs text-slate-500">{{ $doc?->status_verifikasi ?? 'Belum diunggah' }}</span><div class="flex gap-2">@if($doc)<a class="rmap-btn rmap-btn-secondary py-2" href="{{ route('pkl.file', ['path' => $doc->path_berkas]) }}" target="_blank">Lihat</a>@endif<button class="rmap-btn rmap-btn-primary py-2" type="submit">Unggah</button></div></div>
                        </form>
                    @endforeach
                @else
                    @foreach ([['berkas_laporan_revisi','Laporan Revisi'],['berkas_lembar_pengesahan','Lembar Pengesahan']] as [$key,$label])@if($pkl?->{$key})<a class="mb-2 block text-sm font-semibold text-indigo-700 underline" href="{{ route('pkl.file', ['path' => $pkl->{$key}]) }}" target="_blank" rel="noopener">{{ $label }}</a>@endif @endforeach
                @endif
            </div>
            <div class="rounded-xl border border-slate-200 p-4"><h3 class="mb-3 text-sm font-bold">Nilai & Validasi Revisi</h3>
                @if ($pkl && ($canPerformPembimbingActions || $canPerformPengujiActions))
                    <form action="{{ route('pkl.route-map.grade', ['pengajuan' => $pkl?->getKey()]) }}" method="POST" class="space-y-3">@csrf<input type="hidden" name="kategori_penilai" value="{{ $showPembimbing ? 'Pembimbing' : 'Penguji' }}">
                        @php
                            $kategoriNilai = $showPembimbing ? 'Pembimbing' : 'Penguji';
                            $komponenUntukDosen = ($komponen_penilaian_pkl ?? collect())->filter(fn ($item) => in_array(strtolower($item->penilai ?? ''), [strtolower($kategoriNilai), 'dosen ' . strtolower($kategoriNilai)], true));
                        @endphp
                        <h4 class="mb-2 text-sm font-bold">Komponen {{ $kategoriNilai === 'Pembimbing' ? 'Dosen Pembimbing' : 'Dosen Penguji' }}</h4>
                        @forelse($komponenUntukDosen as $komponen)<label class="block text-xs font-semibold text-slate-600">{{ $komponen->nama_komponen }}<input class="rmap-input mt-1" type="number" name="nilai[{{ $komponen->id }}]" min="0" max="100" step="0.01" value="{{ $pkl->detailNilai->firstWhere('komponen_penilaian_pkl_id', $komponen->id)?->nilai_angka }}"></label>@empty<label class="block text-xs font-semibold text-slate-600">Belum ada komponen untuk kategori ini.</label>@endforelse
                        <textarea class="rmap-input" name="catatan_evaluasi" rows="3" placeholder="Catatan evaluasi">{{ $pkl->catatan_evaluasi ?? '' }}</textarea><button class="rmap-btn rmap-btn-primary" type="submit">Simpan penilaian</button>
                    </form>
                    @if($showPembimbing)<form action="{{ route('pkl.route-map.revision.validate', ['pengajuan' => $pkl?->getKey()]) }}" method="POST" class="mt-3">@csrf<button class="rmap-btn rmap-btn-secondary" type="submit">Validasi revisi pasca-ujian</button></form>@endif
                @else
                    <p class="mb-0 text-sm text-slate-600">Nilai akhir: <strong>{{ $pkl->nilai_akhir ?? '—' }}</strong> · Nilai huruf: <strong>{{ $pkl->nilai_huruf ?? '—' }}</strong></p>
                @endif
            </div>
        </div>
        @if ($pkl && (auth()->user()?->hasAnyRole(['admin', 'Admin', 'Super Admin', 'Kaprodi']) || $canPerformPembimbingActions || $showMahasiswa))
            <form action="{{ route('pkl.route-map.complete', ['pengajuan' => $pkl?->getKey()]) }}" method="POST" class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-emerald-50 p-4">@csrf<input type="hidden" name="status_pkl" value="Lulus"><div><h3 class="mb-1 text-sm font-bold text-emerald-900">Kelulusan Akhir</h3><p class="mb-0 text-xs text-emerald-800">Tandai proses PKL mahasiswa sebagai Lulus / Selesai.</p></div><button class="rmap-btn" style="background:#059669;color:#fff" type="submit">Tetapkan Lulus</button></form>
        @endif
    </section>
</div>
@endsection
