@extends('layouts.app')

@section('content')
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

    <!-- Informasi Mahasiswa & Instansi -->
    <div class="row g-4 mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="fw-bold text-slate-800 mb-3"><i class="ti ti-user text-primary me-2"></i>Informasi Mahasiswa</h5>
                        <table class="table table-borderless align-middle mb-0">
                            <tr>
                                <td class="text-muted w-35">Nama Mahasiswa</td>
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
                        <h5 class="fw-bold text-slate-800 mb-3"><i class="ti ti-building text-success me-2"></i>Informasi Instansi PKL</h5>
                        <table class="table table-borderless align-middle mb-0">
                            <tr>
                                <td class="text-muted w-35">Instansi Tujuan</td>
                                <td class="fw-semibold text-dark">: {{ $pengajuan->nama_instansi }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Unit / Bidang</td>
                                <td class="fw-semibold text-dark">: {{ $pengajuan->unit_kerja ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status PKL</td>
                                <td>: <span class="badge bg-primary-subtle text-primary px-3 py-1">{{ $pengajuan->status }}</span></td>
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
                        @isset($pengajuan->logbooks)
                            @forelse($pengajuan->logbooks as $log)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold text-dark">{{ \Carbon\Carbon::parse($log->tanggal ?? $log->created_at)->format('d M Y - H:i') }} WIB</td>
                                <td>{{ $log->aksi ?? $log->aksi ?? '-' }}</td>
                                <td class="text-secondary">{{ $log->deskripsi ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-success-subtle text-success">Tercatat</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted fst-italic">Belum ada riwayat logbook yang diunggah oleh mahasiswa.</td>
                            </tr>
                            @endforelse
                        @else
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted fst-italic">Data logbook belum tersedia.</td>
                            </tr>
                        @endisset
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection