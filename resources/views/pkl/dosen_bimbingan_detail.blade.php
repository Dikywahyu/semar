@extends('layouts.app')

@section('content')
@push('styles')
<style>
    .pkl-roadmap {
        overflow-x: auto;
        margin-bottom: 1.5rem;
        padding: 1.5rem 1rem 1.25rem;
        border: 1px solid #e8edf4;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 5px 18px rgba(20, 33, 61, .045);
    }

    .pkl-roadmap-track {
        display: flex;
        min-width: 850px;
    }

    .pkl-roadmap-step {
        position: relative;
        z-index: 1;
        flex: 1;
        padding: 0 .5rem;
        text-align: center;
    }

    .pkl-roadmap-step:before {
        position: absolute;
        z-index: -1;
        top: 23px;
        right: 50%;
        width: 100%;
        height: 3px;
        background: #e2e8f0;
        content: "";
    }

    .pkl-roadmap-step:first-child:before {
        display: none;
    }

    .pkl-roadmap-icon {
        display: grid;
        width: 48px;
        height: 48px;
        margin: 0 auto .6rem;
        place-items: center;
        border: 3px solid #e2e8f0;
        border-radius: 50%;
        background: #fff;
        color: #94a3b8;
        font-size: 1.1rem;
    }

    .pkl-roadmap-step.is-passed:before,
    .pkl-roadmap-step.is-current:before {
        background: #93c5fd;
    }

    .pkl-roadmap-step.is-passed .pkl-roadmap-icon {
        border-color: #10b981;
        background: #10b981;
        color: #fff;
    }

    .pkl-roadmap-step.is-current .pkl-roadmap-icon {
        border-color: #3b82f6;
        background: #3b82f6;
        color: #fff;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, .14);
    }

    .pkl-roadmap-label {
        max-width: 125px;
        margin: auto;
        color: #64748b;
        font-size: .68rem;
        font-weight: 700;
        line-height: 1.35;
    }

    .pkl-roadmap-step.is-current .pkl-roadmap-label {
        color: #1e293b;
    }
</style>
@endpush

<div class="container-fluid py-4" style="background: #f5f7fb; min-height: 100vh;">
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-slate-800 mb-1">Detail Bimbingan & Logbook Mahasiswa</h3>
            <p class="text-secondary mb-0">Memantau aktivitas harian dan progres bimbingan Praktik Kerja Lapangan mahasiswa.</p>
        </div>
        <div>
            <a href="{{ $isAdmin ? route('pkl.admin.index') : route('pkl.dosen.index') }}" class="btn btn-light border text-secondary fw-semibold">
                <i class="ti ti-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>

    @php
    $roadmapStatuses = [
    'Draft / Pengajuan Survei',
    'Pengajuan & Verifikasi Admin',
    'Pelaksanaan PKL & Bimbingan',
    'Verifikasi Berkas Admin',
    'Dijadwalkan Ujian',
    'Ujian Seminar & Penilaian',
    'Revisi Laporan',
    'Selesai',
    ];
    $roadmapIndex = array_search($pengajuan->status, $roadmapStatuses, true);
    $roadmapIndex = $roadmapIndex === false ? 0 : $roadmapIndex;
    @endphp
    <section class="pkl-roadmap">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h6 class="fw-bold text-slate-700 mb-0"><i class="ti ti-route text-primary me-2"></i>Roadmap Posisi Tahapan Mahasiswa</h6>
            <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">Status saat ini: {{ $pengajuan->status }}</span>
        </div>
        <div class="pkl-roadmap-track">
            @foreach($roadmapStatuses as $index => $status)
            @php $passed = $index < $roadmapIndex; $current=$index===$roadmapIndex; @endphp
                <div class="pkl-roadmap-step {{ $passed ? 'is-passed' : '' }} {{ $current ? 'is-current' : '' }}">
                <div class="pkl-roadmap-icon"><i class="{{ $passed ? 'ti ti-check' : ($current ? 'ti ti-current-location' : 'ti ti-circle') }}"></i></div>
                <div class="pkl-roadmap-label">{{ $status }}</div>
        </div>
        @endforeach
</div>
</section>

@if($pengajuan->status === 'Pelaksanaan PKL & Bimbingan')
<section class="card border-0 shadow-sm rounded-4 p-4 bg-success text-white mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h5 class="fw-bold mb-1 text-white"><i class="ti ti-circle-check me-1"></i>Pelaksanaan PKL</h5>
            <p class="mb-0 text-white-50 small">Setelah kegiatan lapangan selesai, pembimbing dapat meneruskan mahasiswa ke verifikasi berkas ujian.</p>
        </div>
        <button type="button" class="btn btn-light text-success fw-bold" id="btnDosenSelesaiPkl" data-id="{{ $pengajuan->id }}">
            <i class="ti ti-check me-1"></i> Tandai PKL Selesai
        </button>
    </div>
</section>
@endif

<!-- Informasi Mahasiswa & Instansi -->
<div class="row g-4 mb-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="row g-4">
                <div class="col-md-6">
                    <h5 class="fw-bold text-slate-800 mb-3"><i class="ti ti-user text-primary me-2"></i>Informasi Mahasiswa</h5>
                    <table class="table table-borderless align-middle mb-0">
                        <tr>
                            <td class="text-muted" style="width: 35%;">Nama Mahasiswa</td>
                            <td class="fw-semibold text-dark">: {{ $pengajuan->mahasiswaRegistrasi->nama_mahasiswa ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">NIM</td>
                            <td class="fw-semibold text-dark">: {{ $pengajuan->mahasiswaRegistrasi->nim ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Program Studi</td>
                            <td class="fw-semibold text-dark">: {{ $pengajuan->mahasiswaRegistrasi->nama_program_studi ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5 class="fw-bold text-slate-800 mb-3"><i class="ti ti-building text-success me-2"></i>Informasi Instansi & Ujian PKL</h5>
                    <table class="table table-borderless align-middle mb-0">
                        <tr>
                            <td class="text-muted" style="width: 35%;">Instansi Tujuan</td>
                            <td class="fw-semibold text-dark">: {{ $pengajuan->nama_instansi }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Unit / Bidang</td>
                            <td class="fw-semibold text-dark">: {{ $pengajuan->unit_kerja ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Jadwal Seminar</td>
                            <td class="fw-semibold text-dark">:
                                @if($pengajuan->tanggal_seminar)
                                <span class="text-primary">{{ \Carbon\Carbon::parse($pengajuan->tanggal_seminar)->translatedFormat('d M Y, H:i') }} WIB</span>
                                <small class="text-muted">({{ $pengajuan->ruangan_seminar ?? 'Online' }})</small>
                                @else
                                <span class="text-muted fst-italic fw-normal">Belum dijadwalkan</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Berkas Dokumen PKL Mahasiswa -->
@php
    $genericDocuments = $pengajuan->berkasSeminar->keyBy('jenis_berkas');
    $allDocs = [
        ['label' => 'Proposal ke Tempat PKL', 'path' => $pengajuan->berkas_proposal ?: $genericDocuments->get('proposal')?->path_berkas],
        ['label' => 'Surat Jalan PKL', 'path' => $genericDocuments->get('surat_jalan_pkl')?->path_berkas],
        ['label' => 'Surat Balasan Instansi', 'path' => $genericDocuments->get('surat_balasan_instansi')?->path_berkas],
        ['label' => 'Draft Laporan PKL', 'path' => $pengajuan->berkas_draft_laporan ?: $genericDocuments->get('draft_laporan')?->path_berkas],
        ['label' => 'Kartu Konsultasi / Bimbingan', 'path' => $pengajuan->berkas_kartu_bimbingan ?: $genericDocuments->get('kartu_konsultasi')?->path_berkas],
        ['label' => 'Transkrip Sementara', 'path' => $genericDocuments->get('transkrip_sementara')?->path_berkas],
        ['label' => 'Kartu Rencana Studi (KRS)', 'path' => $genericDocuments->get('krs')?->path_berkas],
        ['label' => 'Lampiran Gambar Kerja', 'path' => $genericDocuments->get('lampiran_gambar_kerja')?->path_berkas],
        ['label' => 'Lampiran RAB', 'path' => $genericDocuments->get('lampiran_rab')?->path_berkas],
        ['label' => 'Laporan Revisi Final', 'path' => $pengajuan->berkas_laporan_revisi ?: $genericDocuments->get('laporan_final')?->path_berkas],
        ['label' => 'Lembar Pengesahan', 'path' => $pengajuan->berkas_lembar_pengesahan ?: $genericDocuments->get('berkas_lembar_pengesahan')?->path_berkas],
    ];
@endphp
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4 p-4">
    <h5 class="fw-bold text-slate-800 mb-3"><i class="ti ti-folders text-primary me-2"></i>Berkas & Dokumen PKL Mahasiswa</h5>
    <div class="row g-2">
        @php $hasDoc = false; @endphp
        @foreach($allDocs as $doc)
            @if($doc['path'])
            @php $hasDoc = true; @endphp
            <div class="col-md-4 col-sm-6">
                <div class="p-2 border rounded-3 bg-light d-flex justify-content-between align-items-center">
                    <span class="small fw-semibold text-truncate me-2" title="{{ $doc['label'] }}">{{ $doc['label'] }}</span>
                    <a href="{{ url('pkl/view-file/' . $doc['path']) }}" target="_blank" class="btn btn-sm btn-outline-primary px-2 py-1 flex-shrink-0" style="font-size: 11px;">
                        <i class="ti ti-eye me-1"></i> Lihat
                    </a>
                </div>
            </div>
            @endif
        @endforeach
        @if(!$hasDoc)
            <div class="col-12 text-muted small fst-italic">Belum ada berkas yang diunggah oleh mahasiswa.</div>
        @endif
    </div>
</div>

<!-- Tabel Daftar Logbook / Aktivitas Bimbingan -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
    <div class="card-body p-4">
        <h5 class="fw-bold text-slate-800 mb-3"><i class="ti ti-file-text text-info me-2"></i>Riwayat Logbook & Catatan Bimbingan</h5>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase text-secondary small">
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Aktivitas / Kegiatan</th>
                        <th>Catatan / Keterangan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $riwayatBimbingan = collect();
                    if ($pengajuan->bimbingan) {
                    foreach ($pengajuan->bimbingan as $sesi) {
                    $riwayatBimbingan->push((object) [
                    'tanggal' => $sesi->created_at,
                    'aksi' => $sesi->kategori_bimbingan . ' · ' . $sesi->judul_topik,
                    'deskripsi' => 'Status: ' . $sesi->status,
                    'status' => $sesi->status,
                    ]);
                    if ($sesi->tanggapan) {
                    foreach ($sesi->tanggapan as $catatan) {
                    $riwayatBimbingan->push((object) [
                    'tanggal' => $catatan->created_at,
                    'aksi' => 'Catatan ' . ($catatan->pengirim?->name ?? 'bimbingan'),
                    'deskripsi' => $catatan->pesan,
                    'status' => 'Tercatat',
                    ]);
                    }
                    }
                    }
                    }
                    if ($pengajuan->logAktivitas) {
                    foreach ($pengajuan->logAktivitas as $logAktivitas) {
                    $riwayatBimbingan->push((object) [
                    'tanggal' => $logAktivitas->created_at,
                    'aksi' => $logAktivitas->aksi,
                    'deskripsi' => trim(($logAktivitas->aktor?->name ? $logAktivitas->aktor->name . ' · ' : '') . ($logAktivitas->deskripsi ?? '')),
                    'status' => 'Tercatat',
                    ]);
                    }
                    }
                    $riwayatBimbingan = $riwayatBimbingan->sortByDesc('tanggal');
                    @endphp
                    @forelse($riwayatBimbingan as $log)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="fw-semibold text-dark">{{ \Carbon\Carbon::parse($log->tanggal)->format('d M Y - H:i') }} WIB</td>
                        <td>{{ $log->aksi ?? '-' }}</td>
                        <td class="text-secondary">{{ $log->deskripsi ?? '-' }}</td>
                        <td>
                            <span class="badge {{ ($log->status ?? 'Tercatat') === 'Revisi' ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }}">
                                {{ $log->status ?? 'Tercatat' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted fst-italic">Belum ada riwayat logbook yang diunggah oleh mahasiswa.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
    $(document).on('click', '#btnDosenSelesaiPkl', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Tandai PKL selesai?',
            text: 'Status pengajuan akan diteruskan ke verifikasi berkas ujian.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Selesaikan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#059669'
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ url('pkl/dosen/selesai-pelaksanaan') }}/" + id,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message || 'Status PKL berhasil diperbarui.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                },
                error: function(xhr) {
                    Swal.fire('Gagal', xhr.responseJSON?.message || 'Status PKL tidak dapat diubah.', 'error');
                }
            });
        });
    });
</script>
@endpush

@if($canAccSeminar)
    <section class="card border-0 shadow-sm rounded-4 p-4 mb-4 border-start border-4 {{ $pengajuan->is_acc_seminar ? 'border-success' : 'border-warning' }} bg-white">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="fw-bold mb-1"><i class="ti ti-check-check text-{{ $pengajuan->is_acc_seminar ? 'success' : 'warning' }} me-2"></i>ACC Pembimbing (Pra-Ujian / Seminar)</h5>
                @if($pengajuan->is_acc_seminar)
                    <p class="text-success mb-0 small">
                        <i class="ti ti-circle-check me-1"></i>Berkas telah <strong>di-ACC</strong> oleh <strong>{{ $pengajuan->seminarApprover?->name ?? 'Dosen Pembimbing' }}</strong> ({{ $pengajuan->seminarApprover?->getRoleNames()->implode(', ') ?: 'Dosen' }}) pada {{ $pengajuan->acc_seminar_at?->format('d M Y, H:i') }} WIB.
                    </p>
                @else
                    <p class="text-muted mb-0 small">
                        Persetujuan (ACC) dari Dosen Pembimbing / Kaprodi / Admin menandakan berkas praujian mahasiswa telah disetujui untuk penjadwalan ujian seminar PKL.
                    </p>
                @endif
            </div>
            <div>
                @if($pengajuan->is_acc_seminar)
                    <form action="{{ route('pkl.dosen.acc-pembimbing', $pengajuan->id) }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="revoke" value="1">
                        <button type="submit" class="btn btn-outline-danger btn-sm fw-semibold" onclick="return confirm('Apakah Anda yakin ingin mencabut ACC Pembimbing ini?')">
                            <i class="ti ti-x me-1"></i> Cabut ACC Pembimbing
                        </button>
                    </form>
                @else
                    <form action="{{ route('pkl.dosen.acc-pembimbing', $pengajuan->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success fw-semibold shadow-sm">
                            <i class="ti ti-check me-1"></i> Berikan ACC Pembimbing
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </section>
@endif

@if($pengajuan->tanggal_seminar && $pengajuan->ruangan_seminar)
    <section class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="fw-bold mb-1">Undangan Ujian PKL</h5>
                <p class="text-muted mb-0">{{ $pengajuan->tanggal_seminar->format('d M Y, H:i') }} · {{ $pengajuan->ruangan_seminar }}</p>
            </div>
            <a href="{{ route('pkl.invitation', $pengajuan->id) }}" target="_blank" rel="noopener" class="btn btn-outline-primary">
                <i class="ti ti-printer me-1"></i> Cetak Undangan
            </a>
        </div>
    </section>
@endif

@if($canApproveFinal && !$pengajuan->final_approved_at)
    <section class="card border-0 shadow-sm rounded-4 p-4 mb-4 border-start border-4 border-success">
        <h5 class="fw-bold mb-1">ACC Final Kelulusan PKL</h5>
        <p class="text-muted small">ACC final hanya dapat diproses setelah seluruh dokumen akhir tersedia dan nilai pembimbing sudah tersimpan.</p>
        <form action="{{ route('pkl.dosen.final-approval', $pengajuan->id) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-success fw-semibold">ACC Final & Tetapkan Lulus</button>
        </form>
    </section>
@elseif($pengajuan->final_approved_at)
    <div class="alert alert-success">
        ACC Final oleh {{ $pengajuan->finalApprover?->name ?? 'Dosen Pembimbing' }} ({{ $pengajuan->finalApprover?->getRoleNames()->implode(', ') ?: 'Dosen Pembimbing' }})
        pada {{ $pengajuan->final_approved_at->format('d M Y H:i') }}.
    </div>
@endif

@endsection