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

<!-- WIDGET AGENDA UJIAN TERDEKAT DENGAN FILTER PERAN -->
@php
$agendaList = collect();

if(isset($bimbinganList)) {
foreach($bimbinganList as $bimbingan) {
if($bimbingan->tanggal_seminar) {
$clone = clone $bimbingan;
$clone->role_dosen = 'Pembimbing';
$clone->kategori_filter = 'pembimbing';
$agendaList->push($clone);
}
}
}

if(isset($pengujiList)) {
foreach($pengujiList as $penguji) {
if($penguji->tanggal_seminar) {
$clone = clone $penguji;
$clone->role_dosen = $penguji->pivot->peran ?? 'Penguji';
$clone->kategori_filter = 'penguji';
$agendaList->push($clone);
}
}
}

$agendaList = $agendaList->sort(function($a, $b) {
$dateA = \Carbon\Carbon::parse($a->tanggal_seminar);
$dateB = \Carbon\Carbon::parse($b->tanggal_seminar);
return $dateA->timestamp <=> $dateB->timestamp;
    })->filter(function($item) {
    return \Carbon\Carbon::parse($item->tanggal_seminar)->startOfDay()->greaterThanOrEqualTo(\Carbon\Carbon::today());
    });

    $countAll = $agendaList->count();
    $countPembimbing = $agendaList->where('kategori_filter', 'pembimbing')->count();
    $countPenguji = $agendaList->where('kategori_filter', 'penguji')->count();
    @endphp

    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <h5 class="fw-bold text-slate-800 mb-0">
                <i class="ti ti-calendar-time text-primary me-2"></i>Agenda Ujian Seminar Terdekat
            </h5>

            @if($countAll > 0)
            <div class="btn-group btn-group-sm bg-light p-1 rounded-3 border" role="group" aria-label="Filter Agenda">
                <button type="button" class="btn btn-sm rounded-2 fw-semibold px-3 btn-filter-agenda active btn-primary text-white" data-filter="all">
                    Semua <span class="badge bg-white text-primary ms-1">{{ $countAll }}</span>
                </button>
                <button type="button" class="btn btn-sm rounded-2 fw-semibold px-3 btn-filter-agenda text-secondary" data-filter="pembimbing">
                    Pembimbing <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $countPembimbing }}</span>
                </button>
                <button type="button" class="btn btn-sm rounded-2 fw-semibold px-3 btn-filter-agenda text-secondary" data-filter="penguji">
                    Penguji <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $countPenguji }}</span>
                </button>
            </div>
            @endif
        </div>

        <div class="card-body p-4">
            @if($countAll > 0)
            <div class="row g-3" id="agendaContainer">
                @foreach($agendaList as $agenda)
                <div class="col-md-6 col-xl-4 agenda-card-item" data-role="{{ $agenda->kategori_filter }}">
                    <div class="p-3 border rounded-3 border-start border-4 shadow-none {{ $agenda->kategori_filter == 'pembimbing' ? 'border-primary bg-primary-subtle' : 'border-warning bg-warning-subtle' }} h-100">

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge {{ $agenda->kategori_filter == 'pembimbing' ? 'bg-primary' : 'bg-warning text-dark' }} small shadow-sm">
                                Sebagai {{ $agenda->role_dosen }}
                            </span>
                            <small class="text-dark fw-bold">
                                <i class="ti ti-clock me-1"></i>{{ \Carbon\Carbon::parse($agenda->tanggal_seminar)->format('H:i') }} WIB
                            </small>
                        </div>

                        <h6 class="fw-bold text-dark mb-1 text-truncate" title="{{ $agenda->mahasiswaRegistrasi->nama_mahasiswa ?? '-' }}">
                            {{ $agenda->mahasiswaRegistrasi->nama_mahasiswa ?? '-' }}
                        </h6>
                        <div class="small text-muted mb-3">
                            {{ $agenda->mahasiswaRegistrasi->nim ?? '-' }}
                        </div>

                        <div class="d-flex align-items-center justify-content-between border-top pt-2">
                            <div class="text-secondary small fw-semibold text-truncate">
                                <i class="ti ti-calendar-event me-1 text-primary"></i> {{ \Carbon\Carbon::parse($agenda->tanggal_seminar)->format('d M Y') }}
                            </div>
                            <div class="text-secondary small fw-semibold text-truncate text-end">
                                <i class="ti ti-map-pin me-1 text-danger"></i> {{ $agenda->ruangan_seminar ?? 'Online' }}
                            </div>
                        </div>

                    </div>
                </div>
                @endforeach
            </div>

            <div id="emptyFilterAgenda" class="text-center py-4 text-muted border border-dashed rounded-3 bg-light d-none">
                <i class="ti ti-calendar-off fs-1 text-secondary opacity-50 d-block mb-2"></i>
                Tidak ada jadwal ujian untuk peran yang dipilih.
            </div>
            @else
            <div class="text-center py-4 text-muted border border-dashed rounded-3 bg-light">
                <i class="ti ti-calendar-off fs-1 text-secondary opacity-50 d-block mb-2"></i>
                Belum ada jadwal ujian seminar dalam waktu dekat.
            </div>
            @endif
        </div>
    </div>

    <!-- TABEL 1: MAHASISWA BIMBINGAN (PEMBIMBING) -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold text-slate-800 mb-3"><i class="ti ti-user-check text-primary me-2"></i>Daftar Mahasiswa Bimbingan PKL</h5>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase text-secondary small">
                        <tr>
                            <th>No</th>
                            <th>Mahasiswa (NIM)</th>
                            <th>Instansi & Berkas Mahasiswa</th>
                            <th>Jadwal Ujian Seminar</th>
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
                                <small class="text-muted">{{ $item->mahasiswaRegistrasi->nim ?? '-' }}</small><br>
                                <small class="text-danger">({{ $item->mahasiswaRegistrasi->nama_program_studi ?? '-' }})</small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $item->nama_instansi }}</div>
                                <small class="text-muted">{{ $item->unit_kerja ?? '-' }}</small>

                                <div class="mt-2 d-flex flex-wrap gap-1">
                                    @if($item->berkas_surat_balasan)
                                    <button type="button" class="btn btn-sm badge bg-primary-subtle text-primary border border-primary-subtle btn-view-berkas"
                                        data-url="{{ url('pkl/view-file/' . $item->berkas_surat_balasan) }}" style="font-size: 10px; cursor: pointer;">
                                        <i class="ti ti-file-text me-1"></i> Surat Persetujuan Fakultas
                                    </button>
                                    @endif
                                    @if($item->berkas_proposal)
                                    <button type="button" class="btn btn-sm badge bg-primary-subtle text-primary border border-primary-subtle btn-view-berkas"
                                        data-url="{{ url('pkl/view-file/' . $item->berkas_proposal) }}" style="font-size: 10px; cursor: pointer;">
                                        <i class="ti ti-file-text me-1"></i> Berkas Proposal
                                    </button>
                                    @endif
                                    @if($item->berkas_logbook)
                                    <button type="button" class="btn btn-sm badge bg-info-subtle text-info border border-info-subtle btn-view-berkas"
                                        data-url="{{ url('pkl/view-file/' . $item->berkas_logbook) }}" style="font-size: 10px; cursor: pointer;">
                                        <i class="ti ti-book me-1"></i> Logbook
                                    </button>
                                    @endif
                                    @if($item->berkas_kartu_bimbingan)
                                    <button type="button" class="btn btn-sm badge bg-warning-subtle text-warning-emphasis border border-warning-subtle btn-view-berkas"
                                        data-url="{{ url('pkl/view-file/' . $item->berkas_kartu_bimbingan) }}" style="font-size: 10px; cursor: pointer;">
                                        <i class="ti ti-files me-1"></i> Kartu Bimbingan
                                    </button>
                                    @endif
                                    @if($item->berkas_draft_laporan)
                                    <button type="button" class="btn btn-sm badge bg-success-subtle text-success border border-success-subtle btn-view-berkas"
                                        data-url="{{ url('pkl/view-file/' . $item->berkas_draft_laporan) }}" style="font-size: 10px; cursor: pointer;">
                                        <i class="ti ti-file-check me-1"></i> Draft Laporan
                                    </button>
                                    @endif
                                    @if($item->berkas_nilai_pamong)
                                    <button type="button" class="btn btn-sm badge bg-danger-subtle text-danger border border-danger-subtle btn-view-berkas"
                                        data-url="{{ url('pkl/view-file/' . $item->berkas_nilai_pamong) }}" style="font-size: 10px; cursor: pointer;">
                                        <i class="ti ti-award me-1"></i> Nilai Pamong
                                    </button>
                                    @endif
                                    @if($item->berkas_persetujuan_pamong)
                                    <button type="button" class="btn btn-sm badge bg-secondary-subtle text-dark border btn-view-berkas"
                                        data-url="{{ url('pkl/view-file/' . $item->berkas_persetujuan_pamong) }}" style="font-size: 10px; cursor: pointer;">
                                        <i class="ti ti-circle-check me-1"></i> Persetujuan Semhas Pamong
                                    </button>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($item->tanggal_seminar)
                                <div class="small fw-bold text-dark mb-1">
                                    <i class="ti ti-calendar me-1 text-primary"></i>
                                    {{ \Carbon\Carbon::parse($item->tanggal_seminar)->translatedFormat('d M Y, H:i') }} WIB
                                </div>
                                <div class="small text-muted mb-2">
                                    <i class="ti ti-map-pin me-1 text-danger"></i>
                                    {{ $item->ruangan_seminar ?? 'Online / Belum diisi' }}
                                </div>
                                <div class="pt-1 border-top" style="font-size: 0.8rem;">
                                    <div class="fw-semibold text-secondary mb-1">
                                        <i class="ti ti-users me-1"></i> Dosen Penguji:
                                    </div>
                                    @forelse($item->dosenPenguji as $penguji)
                                    <div class="text-dark lh-sm mb-1">
                                        • {{ $penguji->gelar_depan }}{{ $penguji->nama_dosen }}{{ $penguji->gelar_belakang ? ', ' . $penguji->gelar_belakang : '' }}
                                        @if(isset($penguji->pivot->peran))
                                        <span class="badge bg-light text-secondary border px-1 py-0 ms-1" style="font-size: 0.7rem;">
                                            {{ $penguji->pivot->peran }}
                                        </span>
                                        @endif
                                    </div>
                                    @empty
                                    <span class="text-muted fst-italic" style="font-size: 0.75rem;">Belum diploting</span>
                                    @endforelse
                                </div>
                                @else
                                <span class="text-muted small fst-italic">- Belum ada jadwal ujian -</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">{{ $item->status }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <a href="{{ route('pkl.dosen.bimbingan.detail', $item->id) }}" class="btn btn-sm btn-outline-primary fw-semibold" title="Detail Bimbingan">
                                        <i class="ti ti-file-text me-1"></i> Detail
                                    </a>

                                    <button type="button" class="btn btn-sm btn-warning fw-semibold text-dark btn-nilai-pembimbing"
                                        data-id="{{ $item->id }}"
                                        data-nama="{{ $item->mahasiswaRegistrasi->nama_mahasiswa ?? '-' }}"
                                        data-nim="{{ $item->mahasiswaRegistrasi->nim ?? '-' }}" title="Input Nilai Bimbingan">
                                        <i class="ti ti-award me-1"></i> Nilai
                                    </button>

                                    @if($item->status == 'Pelaksanaan PKL & Bimbingan')
                                    <button type="button" class="btn btn-sm btn-success fw-semibold btn-selesai-pkl-dosen" data-id="{{ $item->id }}" title="Tandai Selesai">
                                        <i class="ti ti-circle-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger fw-semibold btn-tolak-pkl-dosen" data-id="{{ $item->id }}" title="Tolak / Minta Revisi">
                                        <i class="ti ti-x"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted fst-italic">Belum ada mahasiswa bimbingan yang terploting pada periode ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TABEL 2: DEWAN PENGUJI SEMINAR -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h5 class="fw-bold text-slate-800 mb-1"><i class="ti ti-calendar-event text-success me-2"></i>Jadwal & Penilaian Dewan Penguji Seminar</h5>
                    <p class="text-secondary small mb-0">Jadwal diurutkan berdasarkan waktu terdekat. Ujian yang telah lewat otomatis berada di urutan akhir.</p>
                </div>

                <div class="btn-group shadow-sm" role="group" aria-label="View Switcher">
                    <button type="button" class="btn btn-outline-primary btn-sm px-3 active" id="btnViewCard" onclick="switchView('card')">
                        <i class="ti ti-layout-grid me-1"></i> Tampilan Card
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm px-3" id="btnViewTable" onclick="switchView('table')">
                        <i class="ti ti-table me-1"></i> Tampilan Tabel
                    </button>
                </div>
            </div>

            @php
            $sortedPengujiList = collect($pengujiList)->sort(function ($a, $b) {
            $dateA = $a->tanggal_seminar ? \Carbon\Carbon::parse($a->tanggal_seminar) : null;
            $dateB = $b->tanggal_seminar ? \Carbon\Carbon::parse($b->tanggal_seminar) : null;

            $isPastA = !$dateA || $dateA->isPast();
            $isPastB = !$dateB || $dateB->isPast();

            if ($isPastA !== $isPastB) {
            return $isPastA ? 1 : -1;
            }
            return optional($dateA)->timestamp <=> optional($dateB)->timestamp;
                });
                @endphp

                @if(count($sortedPengujiList) > 0)
                <!-- 1. TAMPILAN CARD GRID -->
                <div id="seminarCardView" class="row g-3">
                    @foreach($sortedPengujiList as $item)
                    @php
                    $tgl = $item->tanggal_seminar ? \Carbon\Carbon::parse($item->tanggal_seminar) : null;
                    $isPast = !$tgl || $tgl->isPast();
                    @endphp
                    <div class="col-md-6 col-xl-4">
                        <div class="card h-100 border {{ $isPast ? 'border-secondary-subtle bg-light opacity-75' : 'border-success-subtle shadow-sm' }} rounded-4 p-3 position-relative">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge {{ $isPast ? 'bg-secondary text-white' : 'bg-success text-white' }} small px-2 py-1">
                                    {{ $isPast ? 'Sudah Lewat' : 'Akan Datang' }}
                                </span>
                                <span class="badge bg-warning-subtle text-warning-emphasis border">{{ $item->pivot->peran ?? 'Penguji' }}</span>
                            </div>

                            <div class="mb-3">
                                <h6 class="fw-bold text-dark mb-0">{{ $item->mahasiswaRegistrasi->nama_mahasiswa ?? '-' }}</h6>
                                <small class="text-muted font-monospace">NIM: {{ $item->mahasiswaRegistrasi->nim ?? '-' }}</small>
                            </div>

                            <div class="mb-3 p-2 bg-white rounded-3 border small">
                                <span class="text-muted d-block" style="font-size: 11px;">Instansi Tujuan:</span>
                                <strong class="text-dark">{{ $item->nama_instansi }}</strong>
                            </div>

                            <div class="mb-3 text-secondary small">
                                <div class="mb-1"><i class="ti ti-calendar text-primary me-1"></i> <strong class="text-dark">{{ $tgl ? $tgl->format('d M Y, H:i') : 'Belum dijadwalkan' }}</strong> WIB</div>
                                <div><i class="ti ti-map-pin text-danger me-1"></i> Ruangan: <strong class="text-dark">{{ $item->ruangan_seminar ?? 'Online / Belum ditentukan' }}</strong></div>
                            </div>

                            <div class="mt-auto pt-2 border-top">
                                <button type="button" class="btn btn-sm btn-success fw-semibold w-100 btn-nilai-seminar" data-id="{{ $item->id }}">
                                    <i class="ti ti-award me-1"></i> Beri Nilai Ujian
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- 2. TAMPILAN TABEL -->
                <div id="seminarTableView" class="table-responsive d-none">
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
                            @foreach($sortedPengujiList as $item)
                            @php
                            $tgl = $item->tanggal_seminar ? \Carbon\Carbon::parse($item->tanggal_seminar) : null;
                            $isPast = !$tgl || $tgl->isPast();
                            @endphp
                            <tr class="{{ $isPast ? 'table-light text-muted' : '' }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $item->mahasiswaRegistrasi->nama_mahasiswa ?? '-' }}</div>
                                    <small class="text-muted">{{ $item->mahasiswaRegistrasi->nim ?? '-' }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $item->nama_instansi }}</div>
                                </td>
                                <td>
                                    <div class="small fw-bold text-dark"><i class="ti ti-calendar me-1"></i> {{ $tgl ? $tgl->format('d M Y, H:i') : '-' }}</div>
                                    <small class="text-muted"><i class="ti ti-map-pin me-1"></i> {{ $item->ruangan_seminar ?? 'Online' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border">{{ $item->pivot->peran ?? 'Penguji' }}</span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success fw-semibold btn-nilai-seminar" data-id="{{ $item->id }}">
                                        <i class="ti ti-award me-1"></i> Beri Nilai Ujian
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted fst-italic">
                    Belum ada jadwal seminar yang menugaskan Anda sebagai penguji pada periode ini.
                </div>
                @endif
        </div>
    </div>