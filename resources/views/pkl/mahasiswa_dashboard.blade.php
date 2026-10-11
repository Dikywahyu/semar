@extends('layouts.app')


@section('content')
<style>
    /* PKL dashboard — modern, responsive visual system */
    .pkl-page {
        --pkl-ink: #172033;
        --pkl-muted: #687386;
        --pkl-line: #e7ebf2;
        --pkl-primary: #3758d6;
        --pkl-surface: #ffffff;
        color: var(--pkl-ink);
        background: #f5f7fb;
        min-height: 100vh;
    }
    .pkl-page .pkl-hero {
        position: relative;
        overflow: hidden;
        isolation: isolate;
        background: linear-gradient(120deg, #172554 0%, #243b80 55%, #3758d6 100%) !important;
        border-radius: 22px !important;
        padding: clamp(1.35rem, 3vw, 2.25rem) !important;
        box-shadow: 0 14px 35px rgba(28, 47, 111, .15) !important;
    }
    .pkl-page .pkl-hero:after {
        content: "";
        position: absolute;
        width: 270px;
        height: 270px;
        border: 1px solid rgba(255,255,255,.13);
        border-radius: 50%;
        right: -75px;
        top: -130px;
        box-shadow: 0 0 0 28px rgba(255,255,255,.035), 0 0 0 58px rgba(255,255,255,.025);
        z-index: -1;
    }
    .pkl-page .pkl-hero h2 { font-size: clamp(1.35rem, 2.4vw, 1.9rem); letter-spacing: -.035em; }
    .pkl-page .pkl-hero p { max-width: 700px; line-height: 1.65; color: rgba(255,255,255,.76) !important; }
    .pkl-page .card {
        border: 1px solid rgba(226,232,240,.85) !important;
        border-radius: 18px !important;
        box-shadow: 0 5px 18px rgba(20, 33, 61, .045) !important;
        transition: box-shadow .2s ease, transform .2s ease;
    }
    .pkl-page .card .card { box-shadow: none !important; }
    .pkl-page .card h4, .pkl-page .card h5, .pkl-page .card h6 { letter-spacing: -.02em; color: #1e293b; }
    .pkl-page .form-label { color: #475569 !important; margin-bottom: .5rem; }
    .pkl-page .form-control, .pkl-page .input-group-text, .pkl-page .form-select {
        border-color: #dce3ed;
        border-radius: 10px;
        min-height: 44px;
        box-shadow: none;
    }
    /* File input: make the native chooser compact and visually consistent */
    .pkl-page input[type="file"].form-control {
        height: auto;
        min-height: 46px;
        padding: .35rem;
        overflow: hidden;
        font-size: .875rem;
        line-height: 1.5;
        background: #fff;
        border: 1px solid #dce3ed;
        border-radius: 10px;
        color: #64748b;
    }
    .pkl-page input[type="file"].form-control::file-selector-button {
        margin: -.35rem .75rem -.35rem -.35rem;
        padding: .72rem 1rem;
        border: 0;
        border-right: 1px solid #dce3ed;
        border-radius: 9px 0 0 9px;
        background: #f1f5f9;
        color: #334155;
        font-weight: 600;
        cursor: pointer;
        transition: background .18s ease;
    }
    .pkl-page input[type="file"].form-control::file-selector-button:hover {
        background: #e2e8f0;
    }
    .pkl-page .status-file-logbook,
    .pkl-page .status-file-kartu_bimbingan,
    .pkl-page [class^="status-file-"] {
        margin-top: .65rem !important;
        font-size: .85rem;
        line-height: 1.5;
    }
    .pkl-page [class^="status-file-"] a {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .3rem .65rem;
        border-radius: 7px;
        background: #eef4ff;
        color: #3156c9;
        font-weight: 600;
        text-decoration: none;
    }
    .pkl-page [class^="status-file-"] a:hover {
        background: #dfe9ff;
        text-decoration: underline;
    }
    .pkl-page textarea.form-control { min-height: auto; }
    .pkl-page .form-control:focus, .pkl-page .form-select:focus {
        border-color: #8ca1ff;
        box-shadow: 0 0 0 .22rem rgba(55,88,214,.11);
    }
    .pkl-page .input-group > .input-group-text { border-radius: 10px 0 0 10px; }
    .pkl-page .input-group > .form-control { border-radius: 0 10px 10px 0; }
    .pkl-page .btn { border-radius: 10px; transition: all .18s ease; }
    .pkl-page .btn-primary { background: #3758d6; border-color: #3758d6; box-shadow: 0 5px 12px rgba(55,88,214,.15); }
    .pkl-page .btn-primary:hover { background: #2948bf; border-color: #2948bf; transform: translateY(-1px); }
    .pkl-page .table > :not(caption) > * > * { padding: .8rem .5rem; }
    .pkl-page .table td:first-child { color: #718096 !important; font-size: .9rem; }
    .pkl-page .table td:last-child { overflow-wrap: anywhere; }
    .pkl-page .list-group-item { border-color: #edf0f5; }
    .pkl-page .badge { font-weight: 600; letter-spacing: .01em; }
    .pkl-page .timeline-roadmap {
        gap: 0;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
        padding-top: .25rem;
    }
    .pkl-page .timeline-roadmap:before {
        content: "";
        position: absolute;
        left: 6%;
        right: 6%;
        top: 23px;
        height: 2px;
        background: #e7ebf2;
        z-index: 0;
    }
    .pkl-page .timeline-roadmap > div { flex: 1 0 135px; }
    .pkl-page .timeline-roadmap .rounded-circle {
        width: 46px !important;
        height: 46px !important;
        border: 4px solid #fff;
        box-sizing: content-box;
        box-shadow: 0 0 0 1px #e7ebf2;
    }
    .pkl-page .timeline-roadmap .bg-primary { background: #3758d6 !important; box-shadow: 0 0 0 1px #cbd5ff, 0 5px 15px rgba(55,88,214,.2) !important; }
    .pkl-page .timeline-roadmap .bg-success { background: #159570 !important; }
    .pkl-page .timeline-roadmap .small { line-height: 1.45; max-width: 145px; margin-left: auto; margin-right: auto; }
    .pkl-page .border-start.border-4 { border-radius: 14px !important; }
    .pkl-page .modal-content { border-radius: 18px !important; overflow: hidden; }
    .pkl-page .modal-header, .pkl-page .modal-footer { background: #f8fafc !important; }
    @media (max-width: 767.98px) {
        .pkl-page { padding-left: .75rem; padding-right: .75rem; }
        .pkl-page .pkl-hero { border-radius: 17px !important; }
        .pkl-page .pkl-hero .d-flex { align-items: flex-start !important; gap: 1rem; flex-direction: column; }
        .pkl-page .pkl-hero .btn { align-self: flex-start; }
        .pkl-page .card.p-4 { padding: 1.15rem !important; }
        .pkl-page .timeline-roadmap { justify-content: flex-start !important; }
        .pkl-page .timeline-roadmap:before { left: 25px; right: auto; width: calc(100% - 50px); }
        .pkl-page .timeline-roadmap > div { flex: 0 0 135px; }
        .pkl-page .table td { display: block; width: 100% !important; padding: .35rem .25rem !important; }
        .pkl-page .table tr { display: block; padding: .55rem 0; border-bottom: 1px solid #edf0f5; }
        .pkl-page .table tr:last-child { border-bottom: 0; }
        .pkl-page .table td:last-child { padding-top: .15rem !important; }
        .pkl-page .table td:last-child:before { content: ""; }
        .pkl-page #btnSelesaiPkl { white-space: normal; }
    }
    @media (prefers-reduced-motion: reduce) {
        .pkl-page .card, .pkl-page .btn { transition: none; }
    }
</style>

<div class="container-fluid py-4 min-vh-100 pkl-page">
    <!-- Header Banner -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm rounded-4 bg-gradient text-white p-4 pkl-hero" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="fw-bold mb-1 text-white"><i class="ti ti-briefcase me-2 text-white"></i>Portal Praktik Kerja Lapangan (PKL)</h2>
                        <p class="text-white-50 mb-0">Kelola tahapan magang, pantau roadmap progres secara real-time, dan unggah berkas dengan mudah.</p>
                    </div>
                    @if($pengajuan &&$pengajuan->status == 'Pengajuan & Verifikasi Admin')
                    <div>
                        <button type="button" class="btn btn-outline-light btn-sm fw-semibold px-3 py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalEditPengajuan">
                            <i class="ti ti-edit me-1"></i> Edit Data Pengajuan
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @if(!$pengajuan)
            <!-- ========================================== -->
            <!-- KONDISI 1: FORM PENGAJUAN PKL MANDIRI      -->
            <!-- ========================================== -->
            <div class="col-md-12">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                        <div class="bg-primary-subtle text-primary p-3 rounded-4 me-3 fs-4">
                            <i class="ti ti-file-text"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold text-slate-800 mb-1">Formulir Pengajuan PKL Mandiri</h4>
                            <p class="text-muted small mb-0">Silakan isi data instansi tempat Anda melaksanakan PKL beserta dokumen surat balasan/proposal pendukung.</p>
                        </div>
                    </div>

                    <form action="{{ route('pkl.mahasiswa.store') }}" method="POST" enctype="multipart/form-data" id="formPengajuanPkl">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary small">Nama Instansi / Perusahaan <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="ti ti-building text-muted"></i></span>
                                    <input type="text" name="nama_instansi" class="form-control border-start-0 ps-0" placeholder="Cth: PT. Telkom Indonesia, Tbk" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary small">Bidang / Unit Kerja <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="ti ti-sitemap text-muted"></i></span>
                                    <input type="text" name="unit_kerja" class="form-control border-start-0 ps-0" placeholder="Cth: Divisi Software Engineering" required>
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold text-secondary small">Alamat Lengkap Instansi <span class="text-danger">*</span></label>
                                <textarea name="alamat_instansi" class="form-control" rows="2" placeholder="Cth: Jl. Raya Gubeng No. 46, Surabaya" required></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary small">Rencana Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_mulai" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary small">Rencana Tanggal Selesai <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_selesai" class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold text-secondary small">Unggah Surat Balasan / Proposal (PDF, Maks 5MB) <span class="text-danger">*</span></label>
                                <input type="file" name="berkas_proposal" class="form-control" accept=".pdf" required>
                            </div>
                        </div>
                        <div class="mt-4 text-end">
                            <button type="submit" id="btnSubmitPengajuan" class="btn btn-primary fw-semibold px-4 py-2 rounded-3 shadow-sm">
                                <span class="btn-text"><i class="ti ti-send me-1"></i> Kirim Pengajuan PKL</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <!-- ========================================== -->
            <!-- KONDISI 2: DASHBOARD & ROADMAP PROGRES     -->
            <!-- ========================================== -->
            
            @php
                $statusList = [
                    'Pengajuan & Verifikasi Admin',
                    'Pelaksanaan PKL & Bimbingan',
                    'Verifikasi Berkas Admin',
                    'Dijadwalkan Ujian',
                    'Ujian Seminar & Penilaian',
                    'Revisi Laporan',
                    'Selesai'
                ];

                $currentStatus =$pengajuan->status;
                $currentIndex = array_search($currentStatus,$statusList);
                if ($currentIndex === false)$currentIndex = 0;
            @endphp
            
            <!-- Modal Edit Pengajuan -->
            @if($pengajuan->status == 'Pengajuan & Verifikasi Admin')
            <div class="modal fade" id="modalEditPengajuan" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header border-bottom px-4 bg-light">
                            <h5 class="modal-title fw-bold text-slate-800">Edit Data Pengajuan PKL</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('pkl.mahasiswa.update', $pengajuan->id) }}" method="POST" enctype="multipart/form-data" id="formEditPengajuan">
                            @csrf
                            @method('PUT')
                            <div class="modal-body p-4 text-start">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold small">Nama Instansi / Perusahaan</label>
                                        <input type="text" name="nama_instansi" class="form-control" value="{{ $pengajuan->nama_instansi }}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold small">Bidang / Unit Kerja</label>
                                        <input type="text" name="unit_kerja" class="form-control" value="{{ $pengajuan->unit_kerja }}" required>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-semibold small">Alamat Lengkap Instansi</label>
                                        <textarea name="alamat_instansi" class="form-control" rows="2" required>{{ $pengajuan->alamat_instansi }}</textarea>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold small">Rencana Tanggal Mulai</label>
                                        <input type="date" name="tanggal_mulai" class="form-control" value="{{ $pengajuan->tanggal_mulai?->format('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold small">Rencana Tanggal Selesai</label>
                                        <input type="date" name="tanggal_selesai" class="form-control" value="{{ $pengajuan->tanggal_selesai?->format('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-semibold small">Ganti Proposal PKL (Opsional, PDF max 5MB)</label>
                                        <input type="file" name="berkas_proposal" class="form-control" accept=".pdf">
                                        @if($pengajuan->berkas_proposal)
                                            <small class="text-muted d-mt-1">Proposal saat ini: <a href="{{ asset('storage/' . $pengajuan->berkas_proposal) }}" target="_blank">Lihat File</a></small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-top px-4 py-3 bg-light">
                                <button type="button" class="btn btn-light border text-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-4">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            <!-- Roadmap Progres Visual Modern -->
            <div class="col-md-12 mb-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold text-slate-800 mb-4"><i class="ti ti-map-pins text-primary me-2"></i>Roadmap Progres Tahapan PKL</h5>
                    
                    <div class="timeline-roadmap d-flex justify-content-between position-relative overflow-x-auto pb-3 px-2">
                        @foreach($statusList as $index =>$step)
                            @php
                                $isPassed = $index <$currentIndex;
                                $isCurrent = $index ==$currentIndex;
                                
                                $badgeClass = 'bg-light text-muted border';$iconClass = 'ti ti-circle text-muted';
                                if ($isPassed) {
                                    $badgeClass = 'bg-success text-white shadow-sm';$iconClass = 'ti ti-check text-white';
                                } elseif ($isCurrent) {
                                    $badgeClass = 'bg-primary text-white shadow-lg animate-pulse';$iconClass = 'ti ti-loader text-white';
                                }
                            @endphp

                            <div class="text-center position-relative px-2" style="min-width: 150px; z-index: 2;">
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center {{ $badgeClass }}" style="width: 45px; height: 45px; font-size: 1.2rem;">
                                    <i class="{{ $iconClass }}"></i>
                                </div>
                                <div class="mt-2 small fw-bold {{ $isCurrent ? 'text-primary' : ($isPassed ? 'text-success' : 'text-muted') }}">
                                    {{ $step }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Detail Informasi PKL & Aksi Interaktif -->
            <div class="col-md-8">
                <!-- Informasi Instansi Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold mb-3 text-slate-800"><i class="ti ti-info-circle text-info me-2"></i>Informasi Instansi & Status</h5>
                    <table class="table table-borderless align-middle mb-0">
                        <tr>
                            <td class="text-muted w-25">Nama Instansi</td>
                            <td class="fw-semibold text-dark">: {{ $pengajuan->nama_instansi }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Unit / Bidang</td>
                            <td class="fw-semibold text-dark">: {{ $pengajuan->unit_kerja ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Alamat</td>
                            <td class="fw-semibold text-dark">: {{ $pengajuan->alamat_instansi ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Dosen Pembimbing</td>
                            <td class="fw-semibold text-dark">: <span class="badge bg-info-subtle text-info px-2 py-1">{{ $pengajuan->dosenPembimbing->nama_dosen ?? 'Belum Diploting' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status Saat Ini</td>
                            <td>: <span class="badge bg-primary-subtle text-primary px-3 py-2 fw-bold">{{ $pengajuan->status }}</span></td>
                        </tr>
                        @if($pengajuan->nilai_akhir)
                        <tr>
                            <td class="text-muted">Nilai Akhir</td>
                            <td>: <span class="badge bg-success-subtle text-success fs-6 px-3 py-1">{{ $pengajuan->nilai_akhir }} ({{$pengajuan->nilai_huruf }})</span></td>
                        </tr>
                        @endif
                    </table>
                </div>

                <!-- Aksi: Tandai PKL Selesai -->
                @if($pengajuan->status == 'Pelaksanaan PKL & Bimbingan')
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-gradient text-white mb-4" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="ti ti-clock-check me-1"></i> Pelaksanaan PKL Berlangsung</h5>
                            <p class="text-white-50 small mb-0">Jika masa magang di instansi sudah selesai, klik tombol di samping untuk lanjut ke pemberkasan seminar.</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-light text-success fw-bold px-4 py-2 rounded-pill shadow-sm" id="btnSelesaiPkl" data-id="{{ $pengajuan->id }}">
                                <i class="ti ti-circle-check me-1"></i> Tandai PKL Selesai
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Form Upload Revisi (Muncul otomatis saat tahap Ujian atau Revisi) -->
                @if(in_array($pengajuan->status, ['Ujian Seminar & Penilaian', 'Revisi Laporan']))
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4 border-start border-4 border-warning">
                    <h5 class="fw-bold mb-2 text-warning-emphasis"><i class="ti ti-file-upload me-1"></i> Unggah Berkas Revisi Laporan & Pengesahan</h5>
                    <p class="text-muted small">Unggah dokumen laporan akhir hasil revisi seminar beserta lembar pengesahan bertandatangan.</p>
                    
                    <form action="{{ route('pkl.mahasiswa.upload-revisi', $pengajuan->id) }}" method="POST" enctype="multipart/form-data" id="formUploadRevisi">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">File Laporan Revisi (PDF, Maks 5MB)</label>
                            <input type="file" name="berkas_laporan_revisi" class="form-control" accept=".pdf" required>
                            @if($pengajuan->berkas_laporan_revisi)
                                <small class="text-success"><i class="ti ti-check"></i> File sudah diunggah. (<a href="{{ url('pkl/view-file/' . $pengajuan->berkas_laporan_revisi) }}" target="_blank">Lihat</a>)</small>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">File Lembar Pengesahan (PDF/Gambar, Maks 3MB)</label>
                            <input type="file" name="berkas_lembar_pengesahan" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                            @if($pengajuan->berkas_lembar_pengesahan)
                                <small class="text-success"><i class="ti ti-check"></i> File sudah diunggah. (<a href="{{ url('pkl/view-file/' . $pengajuan->berkas_lembar_pengesahan) }}" target="_blank">Lihat</a>)</small>
                            @endif
                        </div>
                        <button type="submit" class="btn btn-warning fw-semibold text-dark px-4 py-2 rounded-3">Kirim Berkas Revisi</button>
                    </form>
                </div>
                @endif
            
                <!-- Form Upload 6 Persyaratan Seminar via AJAX Satuan -->
                @if($pengajuan->status == 'Verifikasi Berkas Admin')
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4 border-start border-4 border-primary">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-subtle text-primary p-2 rounded-3 me-2">
                            <i class="ti ti-files fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">Unggah Persyaratan Seminar Hasil (Semhas)</h5>
                            <p class="text-muted small mb-0">File tersimpan otomatis secara mandiri per berkas setelah tombol upload ditekan.</p>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        @php
                            $berkasList = [
                                'berkas_logbook'            => ['label' => '1. Logbook Kegiatan Selama PKL', 'value' => $pengajuan->berkas_logbook],
                                'berkas_kartu_bimbingan'    => ['label' => '2. Kartu Bimbingan (Min. 6 Kali)', 'value' => $pengajuan->berkas_kartu_bimbingan],
                                'berkas_draft_laporan'      => ['label' => '3. Draft Laporan PKL (PDF max 10MB)', 'value' => $pengajuan->berkas_draft_laporan],
                                'berkas_nilai_pamong'       => ['label' => '4. Lembar Penilaian dari Dosen Pamong', 'value' => $pengajuan->berkas_nilai_pamong],
                                'berkas_persetujuan_pamong' => ['label' => '5. Surat Persetujuan Semhas Pamong', 'value' => $pengajuan->berkas_persetujuan_pamong],
                            ];
                        @endphp
                
                        @foreach($berkasList as $field => $data)
                        <div class="col-md-12 border-bottom pb-3">
                            <label class="form-label fw-semibold small text-dark">{{ $data['label'] }} <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <input type="file" name="file" class="form-control input-berkas" data-field="{{ $field }}" data-id="{{ $pengajuan->id }}" accept=".pdf,.jpg,.jpeg,.png">
                                <button class="btn btn-primary btn-upload-satuan px-3" type="button" data-field="{{ $field }}">
                                    <i class="ti ti-upload me-1"></i> Upload
                                </button>
                            </div>
                            <div class="mt-1 status-file-{{ $field }}">
                                @if($data['value'])
                                    @php
                                        // $data['value'] langsung berisi path dari database: "uploads/pkl/seminar_.../file.jpg"
                                        $fileRouteUrl = $data['value'] ? url('pkl/view-file/' . $data['value']) : '#';
                                    @endphp 
                                    <small class="text-success fw-semibold"><i class="ti ti-circle-check"></i> Berhasil diunggah! ( <a href="{{ $fileRouteUrl }}" target="_blank"><i class="ti ti-eye me-1"></i> Lihat Dokumen</a> )</small>
                                @else
                                    <small class="text-muted fst-italic">Belum ada file diunggah.</small>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar Informasi Dosen Penguji & Catatan Admin -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h6 class="fw-bold mb-3 text-slate-800"><i class="ti ti-calendar-event text-primary me-2"></i>Jadwal & Dewan Penguji Seminar</h6>
                    
                    <!-- Informasi Waktu & Tempat Ujian -->
                    @if($pengajuan->tanggal_seminar)
                        @php
                            // Mapping hari dan bulan ke bahasa Indonesia agar akurat di server manapun
                            $days = [
                                'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 
                                'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
                            ];
                            $months = [
                                'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 
                                'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 
                                'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 
                                'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
                            ];
                
                            $dt = \Carbon\Carbon::parse($pengajuan->tanggal_seminar);
                            $namaHari = $days[$dt->format('l')] ?? $dt->format('l');
                            $namaBulan = $months[$dt->format('F')] ?? $dt->format('F');
                            $formattedDate = $dt->format('d') . ' ' . $namaBulan . ' ' . $dt->format('Y - H:i');
                        @endphp
                
                        <div class="p-3 mb-3 bg-primary-subtle rounded-3 border border-primary-subtle">
                            <div class="d-flex align-items-center mb-2">
                                <i class="ti ti-calendar text-primary fs-5 me-2"></i>
                                <span class="fw-bold text-dark">{{ $namaHari }}<br>{{ $formattedDate }} WIB</span>
                            </div>
                            <div class="d-flex align-items-center text-secondary small">
                                <i class="ti ti-map-pin fs-5 me-2"></i>
                                <span class="text-danger">Lokasi / Ruangan: <strong class="text-dark">{{ $pengajuan->ruangan_seminar ?? 'Belum ditentukan' }}</strong></span>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-light border text-muted small py-2 px-3 mb-3">
                            <i class="ti ti-info-circle me-1"></i> Jadwal seminar belum ditentukan oleh admin.
                        </div>
                    @endif
                
                    <!-- Daftar Dewan Penguji -->
                    <h6 class="fw-bold mb-2 text-slate-700 small text-uppercase tracking-wider">Daftar Penguji</h6>
                    <ul class="list-group list-group-flush">
                        @forelse($pengajuan->dosenPenguji as $penguji)
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center border-0">
                                <div>
                                    <div class="fw-semibold text-dark small">{{ $penguji->gelar_depan }}{{ $penguji->nama_dosen }}{{ $penguji->gelar_belakang }}</div>
                                    <span class="badge bg-light text-secondary border small">{{ $penguji->pivot->peran ?? 'Penguji' }}</span>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item px-0 text-muted small fst-italic border-0">Dewan penguji belum ditentukan.</li>
                        @endforelse
                    </ul>
                </div>

                @if($pengajuan->catatan_admin)
                <div class="card border-0 shadow-lg rounded-4 p-4 mb-4 position-relative overflow-hidden" style="background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%); border-left: 6px solid #e53e3e !important;">
                    <div class="position-absolute top-0 end-0 p-3 opacity-10">
                        <i class="ti ti-alert-triangle text-danger" style="font-size: 5rem;"></i>
                    </div>
                    <div class="d-flex align-items-start position-relative z-1">
                        
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h5 class="fw-bold text-danger mb-0">Catatan & Penolakan </h5> 
                            </div> 
                            
                            <div class="p-3 bg-white rounded-3 border border-danger-subtle shadow-sm mb-3">
                                <p class="text-dark fw-medium mb-0" style="white-space: pre-line;">{{ $pengajuan->catatan_admin }}</p>
                            </div>
                
                            <div class="text-end">
                                <button type="button" class="btn btn-danger fw-semibold px-4 py-2 rounded-3 shadow-sm btn-ajukan-ulang" data-id="{{ $pengajuan->id }}">
                                    <i class="ti ti-send me-1"></i> Saya Sudah Memperbaiki & Ajukan Ulang
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                @push('scripts')
                <script>
                    // Handler Tombol Ajukan Ulang Revisi
                    $(document).on('click', '.btn-ajukan-ulang', function() {
                        let id = $(this).data('id');
                
                        Swal.fire({
                            title: 'Ajukan Ulang Berkas?',
                            text: "Pastikan Anda telah mengunggah file perbaikan yang sesuai sebelum mengirimkannya kembali ke admin.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Kirim Sekarang',
                            cancelButtonText: 'Batal',
                            confirmButtonColor: '#e53e3e'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: "{{ url('pkl/mahasiswa/ajukan-ulang-revisi') }}/" + id,
                                    type: 'POST',
                                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                                    success: function(res) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Berhasil!',
                                            text: res.message,
                                            timer: 1500,
                                            showConfirmButton: false
                                        }).then(() => location.reload());
                                    },
                                    error: function(xhr) {
                                        let msg = xhr.responseJSON?.message || 'Terjadi kesalahan.';
                                        Swal.fire('Gagal', msg, 'error');
                                    }
                                });
                            }
                        });
                    });
                </script>
                @endpush
                @endif
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Tombol Tandai PKL Selesai
    $('#btnSelesaiPkl').on('click', function() {
        let id = $(this).data('id');
        
        Swal.fire({
            title: 'Nyatakan PKL Selesai?',
            text: "Pastikan seluruh kegiatan magang di instansi sudah benar-benar tuntas.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Selesai',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#059669'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('pkl/mahasiswa/selesai-pelaksanaan') }}/" + id,
                    type: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    success: function(res) {
                        Swal.fire({icon: 'success', title: 'Berhasil!', text: res.message, timer: 1500, showConfirmButton: false})
                            .then(() => location.reload());
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message || 'Terjadi kesalahan.';
                        Swal.fire('Gagal', msg, 'error');
                    }
                });
            }
        });
    });

    // AJAX Submit Pengajuan PKL Mandiri
    $('#formPengajuanPkl').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let btn = $('#btnSubmitPengajuan');

        btn.prop('disabled', true);
        btn.find('.btn-text').text('Mengirim...');
        btn.find('.spinner-border').removeClass('d-none');

        let formData = new FormData(this);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            success: function(res) {
                Swal.fire({icon: 'success', title: 'Berhasil!', text: res.message || 'Pengajuan berhasil dikirim.', timer: 1500, showConfirmButton: false})
                    .then(() => location.reload());
            },
            error: function(xhr) {
                let msg = xhr.responseJSON?.message || 'Terjadi kesalahan saat mengirim pengajuan.';
                Swal.fire('Gagal', msg, 'error');
                btn.prop('disabled', false);
                btn.find('.btn-text').text('Kirim Pengajuan PKL');
                btn.find('.spinner-border').addClass('d-none');
            }
        });
    });

    // AJAX Submit Edit Pengajuan
    $('#formEditPengajuan').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let formData = new FormData(this);
        formData.append('_method', 'PUT');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            success: function(res) {
                $('#modalEditPengajuan').modal('hide');
                Swal.fire({icon: 'success', title: 'Berhasil!', text: res.message, timer: 1500, showConfirmButton: false})
                    .then(() => location.reload());
            },
            error: function(xhr) {
                let msg = xhr.responseJSON?.message || 'Terjadi kesalahan.';
                Swal.fire('Gagal', msg, 'error');
            }
        });
    });

    // AJAX Upload Berkas Satuan (Seminar)
    $(document).on('click', '.btn-upload-satuan', function() {
        let field = $(this).data('field');
        let inputField = $('input[data-field="' + field + '"]')[0];
        let pengajuanId = inputField.dataset.id;

        if (inputField.files.length === 0) {
            Swal.fire('Peringatan', 'Pilih file dokumen terlebih dahulu.', 'warning');
            return;
        }

        let file = inputField.files[0];
        let formData = new FormData();
        formData.append('file', file);
        formData.append('field', field);

        let btn = $(this);
        let originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: "{{ url('pkl/mahasiswa/upload-berkas-satuan') }}/" + pengajuanId,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            success: function(res) {
                btn.prop('disabled', false).html(originalText);
                $('.status-file-' + field).html(`
                    <small class="text-success fw-semibold"><i class="ti ti-circle-check"></i> Berhasil diunggah! ( <a href="${res.file_url}" target="_blank">Lihat Dokumen</a> )</small>
                `);
                Swal.fire({icon: 'success', title: 'Berhasil', text: 'File berhasil diunggah', timer: 1000, showConfirmButton: false});
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalText);
                let msg = xhr.responseJSON?.message || 'Gagal mengunggah file.';
                Swal.fire('Gagal', msg, 'error');
            }
        });
    });

    // AJAX Upload Revisi
    $('#formUploadRevisi').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            success: function(res) {
                Swal.fire({icon: 'success', title: 'Berhasil', text: res.message, timer: 1500, showConfirmButton: false})
                    .then(() => location.reload());
            },
            error: function(xhr) {
                Swal.fire('Gagal', 'Terjadi kesalahan saat mengunggah berkas.', 'error');
            }
        });
    });
</script>
@endpush