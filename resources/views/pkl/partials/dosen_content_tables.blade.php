<!-- WIDGET RINGKASAN DOSEN -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="rounded-3 bg-primary-subtle p-3 text-primary me-3">
                    <i class="ti ti-users fs-2"></i>
                </div>
                <div>
                    <h6 class="text-secondary mb-1 small fw-semibold">Mahasiswa Bimbingan Aktif</h6>
                    <h3 class="fw-bold mb-0 text-slate-800">{{ $totalBimbingan ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="rounded-3 bg-warning-subtle p-3 text-warning me-3">
                    <i class="ti ti-award fs-2"></i>
                </div>
                <div>
                    <h6 class="text-secondary mb-1 small fw-semibold">Jadwal Penguji Seminar</h6>
                    <h3 class="fw-bold mb-0 text-slate-800">{{ $totalPenguji ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TABEL 1: MAHASISWA BIMBINGAN -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
    <div class="card-body p-4">
        <h5 class="fw-bold text-slate-800 mb-3"><i class="ti ti-user-check text-primary me-2"></i>Daftar Mahasiswa Bimbingan PKL</h5>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase text-secondary small">
                    <tr>
                        <th>No</th>
                        <th>Mahasiswa (NIM)</th>
                        <th>Instansi Tujuan</th>
                        <th>Tahapan PKL</th>
                        <th>Aksi Bimbingan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bimbinganList as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <div class="fw-bold text-dark">{{ $item->mahasiswaRegistrasi->nama_mahasiswa ?? '-' }}</div>
                            <small class="text-muted">{{ $item->mahasiswaRegistrasi->nim ?? '-' }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $item->nama_instansi }}</div>
                            <small class="text-muted">{{ $item->unit_kerja ?? '-' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">{{ $item->status }}</span>
                        </td>
                        <td>
                            <a href="{{ route('pkl.dosen.bimbingan.detail', $item->id) }}" class="btn btn-sm btn-outline-primary fw-semibold">
                                <i class="ti ti-file-text me-1"></i> Detail & Logbook
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted fst-italic">Belum ada mahasiswa bimbingan yang terploting pada periode ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- TABEL 2: DEWAN PENGUJI SEMINAR -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
    <div class="card-body p-4">
        <h5 class="fw-bold text-slate-800 mb-3"><i class="ti ti-calendar-event text-success me-2"></i>Jadwal & Penilaian Dewan Penguji Seminar</h5>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase text-secondary small">
                    <tr>
                        <th>No</th>
                        <th>Mahasiswa (NIM)</th>
                        <th>Instansi</th>
                        <th>Jadwal & Ruangan</th>
                        <th>Peran Penguji</th>
                        <th>Aksi Penilaian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pengujiList as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <div class="fw-bold text-dark">{{ $item->mahasiswaRegistrasi->nama_mahasiswa ?? '-' }}</div>
                            <small class="text-muted">{{ $item->mahasiswaRegistrasi->nim ?? '-' }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $item->nama_instansi }}</div>
                        </td>
                        <td>
                            <div class="small fw-bold text-dark"><i class="ti ti-calendar me-1"></i> {{ $item->tanggal_seminar ? \Carbon\Carbon::parse($item->tanggal_seminar)->format('d M Y, H:i') : '-' }}</div>
                            <small class="text-muted"><i class="ti ti-map-pin me-1"></i> {{ $item->ruangan_seminar ?? 'Online' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-warning-subtle text-warning-emphasis border">{{ $item->pivot->peran ?? 'Penguji' }}</span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-success fw-semibold btn-nilai-seminar" data-id="{{ $item->id }}">
                                <i class="ti ti-award me-1"></i> Beri Nilai
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted fst-italic">Belum ada jadwal seminar yang menugaskan Anda sebagai penguji pada periode ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>