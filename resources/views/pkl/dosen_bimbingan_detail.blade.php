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
            <a href="{{ route('pkl.dosen.index') }}" class="btn btn-light border text-secondary fw-semibold">
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
@endsection