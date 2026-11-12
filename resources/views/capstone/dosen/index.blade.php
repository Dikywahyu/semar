@extends('layouts.app')

@push('styles')
<style>
    .capstone-lecturer-page {
        --ink-primary: #0f172a;
        --ink-secondary: #475569;
        --surface-bg: #f8fafc;
        color: var(--ink-primary);
        background-color: var(--surface-bg);
    }

    .capstone-hero {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
        border-radius: 20px;
        padding: clamp(1.5rem, 3vw, 2.25rem);
        position: relative;
        overflow: hidden;
        box-shadow: 0 12px 30px rgba(49, 46, 129, 0.16);
    }

    .capstone-hero::after {
        content: "";
        position: absolute;
        right: -40px;
        top: -40px;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.35) 0%, transparent 70%);
        pointer-events: none;
    }

    .modern-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .modern-card:hover {
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    /* Roadmap Colored Cards */
    .roadmap-card {
        border-radius: 14px;
        padding: 1.1rem;
        height: 100%;
        border: 1px solid transparent;
        transition: transform 0.2s ease;
    }

    .roadmap-card:hover {
        transform: translateY(-2px);
    }

    .roadmap-step-1 {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .roadmap-step-2 {
        background: #ecfeff;
        border-color: #a5f3fc;
    }

    .roadmap-step-3 {
        background: #fffbeb;
        border-color: #fde68a;
    }

    .roadmap-step-4 {
        background: #ecfdf5;
        border-color: #a7f3d0;
    }

    /* Role Summary Cards */
    .role-stat-supervisor {
        background: linear-gradient(135deg, #eef2ff 0%, #ffffff 100%);
        border-left: 4px solid #4f46e5 !important;
    }

    .role-stat-examiner {
        background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%);
        border-left: 4px solid #f59e0b !important;
    }

    /* Compact Group Card Styling */
    .group-item-card {
        border-radius: 14px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-left-width: 4px;
        transition: all 0.2s ease;
    }

    .group-item-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.07);
    }

    .group-item-card.is-supervisor {
        border-left-color: #4f46e5;
    }

    .group-item-card.is-examiner {
        border-left-color: #f59e0b;
    }

    .section-divider-badge {
        font-size: 0.8rem;
        font-weight: 600;
        padding: 0.4rem 0.85rem;
        border-radius: 999px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4 min-vh-100 capstone-lecturer-page">
    <!-- Hero Banner -->
    <div class="capstone-hero text-white mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-25 rounded-pill px-3 py-1 mb-2">
                    <i class="ti ti-sparkles me-1"></i> Capstone Design Workspace
                </span>
                <h2 class="fw-bold text-white mb-1">Panel Dosen Capstone Design</h2>
                <p class="text-white-50 mb-0">Kelola kelompok bimbingan, pengujian sidang, penetapan judul, dan penilaian mahasiswa.</p>
            </div>
            <div class="text-md-end">
                <div class="small text-white-50">Dosen Aktif</div>
                <div class="fw-semibold fs-5">{{ $dosen->nama_dosen }}</div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('capstone.dosen.index') }}" class="modern-card p-3 mb-4">
        <div class="row align-items-end g-3">
            @if($isActingAsProfile)
            <div class="col-lg-5">
                <label class="form-label small fw-semibold text-secondary mb-1" for="profileFilter">Profil Dosen</label>
                <select id="profileFilter" name="id_dosen" class="form-select">
                    @foreach($availableProfiles as $profile)
                    <option value="{{ $profile->id_dosen }}" @selected($profile->id_dosen === $dosen->id_dosen)>{{ $profile->nama_dosen }}{{ $profile->nidn ? ' ('.$profile->nidn.')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-lg-5">
                <label class="form-label small fw-semibold text-secondary mb-1" for="semesterFilter">Periode Akademik</label>
                <select id="semesterFilter" name="semester_id" class="form-select">
                    <option value="">Semua periode</option>
                    @foreach($semesters as $semester)
                    <option value="{{ $semester->id }}" @selected((string) $selectedSemester===(string) $semester->id)>
                        {{ $semester->nama }}{{ $semester->aktif ? ' (Aktif)' : '' }}
                    </option>
                    @endforeach
                    <option value="unassigned" @selected($selectedSemester==='unassigned' )>Periode belum tercatat</option>
                </select>
            </div>
            <div class="col-lg-2 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1"><i class="ti ti-filter me-1"></i>Terapkan</button>
                <a class="btn btn-light border" href="{{ route('capstone.dosen.index', $isActingAsProfile ? ['id_dosen' => $dosen->id_dosen] : []) }}" title="Reset filter">
                    <i class="ti ti-refresh"></i>
                </a>
            </div>
        </div>
        @if($isActingAsProfile)
        <div class="small text-primary mt-2 pt-2 border-top">
            <i class="ti ti-info-circle me-1"></i>Mode bertindak sebagai: <strong>{{ $dosen->nama_dosen }}</strong> (dicatat atas akun {{ auth()->user()->name }}).
        </div>
        @endif
    </form>

    @php
    $supervisorGroups = $groups->filter(fn ($group) => $group->supervisor_id === $dosen->id_dosen)->values();
    $examinerGroups = $groups->filter(fn ($group) => $group->examiner_id === $dosen->id_dosen)->values();
    $agendaGroups = $groups
    ->filter(fn ($group) => $group->exam_date)
    ->sortBy(fn ($group) => $group->exam_date . ' ' . ($group->exam_time ?? '00:00'))
    ->values();
    $upcomingAgenda = $agendaGroups->filter(fn ($group) => \Carbon\Carbon::parse($group->exam_date)->endOfDay()->gte(now()));
    $studentCount = fn ($items) => $items
    ->flatMap(fn ($group) => collect([$group->leader_id])->merge($group->members->where('status', 'accepted')->pluck('student_id')))
    ->filter()
    ->unique()
    ->count();
    $registrationGroups = $groups->filter(fn ($group) => ! $group->supervisor_id || ! $group->title);
    $implementationGroups = $groups->filter(fn ($group) => $group->supervisor_id && $group->title && $group->kpi_approval !== 'approved' && $group->final_status !== 'selesai');
    $examGroups = $groups->filter(fn ($group) => $group->kpi_approval === 'approved' && $group->final_status !== 'selesai');
    $completedGroups = $groups->filter(fn ($group) => $group->final_status === 'selesai');
    @endphp

    <!-- Roadmap Capstone (4 Tahap Berwarna) -->
    <section class="modern-card mb-4 p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0"><i class="ti ti-route text-primary me-2"></i>Roadmap Capstone</h5>
                <span class="small text-muted">Progres seluruh kelompok pada periode terpilih</span>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-sm-6 col-xl-3">
                <div class="roadmap-card roadmap-step-1">
                    <span class="badge bg-secondary rounded-pill mb-2">01 · Pendaftaran</span>
                    <div class="fw-bold text-dark">Plotting & Judul</div>
                    <div class="text-secondary small mt-2 fw-medium">
                        <i class="ti ti-folders me-1"></i>{{ $registrationGroups->count() }} kelompok · {{ $studentCount($registrationGroups) }} mhs
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="roadmap-card roadmap-step-2">
                    <span class="badge bg-info text-dark rounded-pill mb-2">02 · Pelaksanaan</span>
                    <div class="fw-bold text-dark">Bimbingan & ACC KPI</div>
                    <div class="text-info-emphasis small mt-2 fw-medium">
                        <i class="ti ti-folders me-1"></i>{{ $implementationGroups->count() }} kelompok · {{ $studentCount($implementationGroups) }} mhs
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="roadmap-card roadmap-step-3">
                    <span class="badge bg-warning text-dark rounded-pill mb-2">03 · Sidang Ujian</span>
                    <div class="fw-bold text-dark">Jadwal & Penilaian</div>
                    <div class="text-warning-emphasis small mt-2 fw-medium">
                        <i class="ti ti-folders me-1"></i>{{ $examGroups->count() }} kelompok · {{ $studentCount($examGroups) }} mhs
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="roadmap-card roadmap-step-4">
                    <span class="badge bg-success rounded-pill mb-2">04 · Selesai</span>
                    <div class="fw-bold text-dark">Laporan Final ACC</div>
                    <div class="text-success-emphasis small mt-2 fw-medium">
                        <i class="ti ti-folders me-1"></i>{{ $completedGroups->count() }} kelompok · {{ $studentCount($completedGroups) }} mhs
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Ringkasan Peran (Pembimbing vs Penguji) -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="modern-card role-stat-supervisor p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 bg-primary text-white p-3 me-3 shadow-sm"><i class="ti ti-user-check fs-4"></i></div>
                        <div>
                            <div class="small fw-semibold text-primary text-uppercase">Dosen Pembimbing</div>
                            <div class="h3 fw-bold mb-0">{{ $supervisorGroups->count() }} <span class="fs-6 fw-normal text-muted">kelompok ({{ $studentCount($supervisorGroups) }} mahasiswa)</span></div>
                        </div>
                    </div>
                    <a href="#section-pembimbing" class="btn btn-sm btn-light text-primary rounded-pill px-3">Lihat <i class="ti ti-arrow-down ms-1"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="modern-card role-stat-examiner p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 bg-warning text-dark p-3 me-3 shadow-sm"><i class="ti ti-award fs-4"></i></div>
                        <div>
                            <div class="small fw-semibold text-warning-emphasis text-uppercase">Dosen Penguji</div>
                            <div class="h3 fw-bold mb-0">{{ $examinerGroups->count() }} <span class="fs-6 fw-normal text-muted">kelompok ({{ $studentCount($examinerGroups) }} mahasiswa)</span></div>
                        </div>
                    </div>
                    <a href="#section-penguji" class="btn btn-sm btn-light text-warning-emphasis rounded-pill px-3">Lihat <i class="ti ti-arrow-down ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Jadwal Ujian Mendatang -->
    <section class="modern-card mb-4 p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0"><i class="ti ti-calendar-time text-primary me-2"></i>Jadwal Ujian Mendatang</h5>
            <span class="badge bg-light text-dark border rounded-pill">{{ $upcomingAgenda->count() }} agenda</span>
        </div>
        @if($upcomingAgenda->isNotEmpty())
        <div class="row g-3">
            @foreach($upcomingAgenda as $group)
            @php
            $examDateTime = \Carbon\Carbon::parse($group->exam_date . ' ' . ($group->exam_time ?? '00:00'));
            $isSupAgenda = $group->supervisor_id === $dosen->id_dosen;
            $agendaRoles = [];
            if ($isSupAgenda) $agendaRoles[] = 'Pembimbing';
            if ($group->examiner_id === $dosen->id_dosen) $agendaRoles[] = 'Penguji';
            $agendaStudentCount = 1 + $group->members->where('status', 'accepted')->count();
            @endphp
            <div class="col-md-6 col-xl-4">
                <div class="h-100 p-3 rounded-3 border {{ $isSupAgenda ? 'bg-primary-subtle bg-opacity-25 border-primary-subtle' : 'bg-warning-subtle bg-opacity-25 border-warning-subtle' }}">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <span class="badge rounded-pill {{ $isSupAgenda ? 'bg-primary' : 'bg-warning text-dark' }}">{{ implode(' & ', $agendaRoles) }}</span>
                        <span class="badge bg-white text-dark border"><i class="ti ti-clock text-primary me-1"></i>{{ $examDateTime->format('H:i') }}</span>
                    </div>
                    <h6 class="fw-bold mb-1 text-truncate" title="{{ $group->title }}">{{ $group->title ?: 'Judul belum ditetapkan' }}</h6>
                    <div class="small text-muted mb-2">{{ $group->leader?->name ?? 'Mahasiswa' }} · {{ $agendaStudentCount }} mhs</div>
                    <div class="d-flex justify-content-between small pt-2 border-top border-secondary border-opacity-10">
                        <span><i class="ti ti-calendar-event text-primary me-1"></i>{{ $examDateTime->translatedFormat('d M Y') }}</span>
                        <span class="fw-medium"><i class="ti ti-map-pin text-danger me-1"></i>{{ $group->exam_room ?: 'Ruang TBD' }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-4 text-muted rounded-3 bg-light">
            <i class="ti ti-calendar-off fs-2 d-block mb-1"></i>Belum ada jadwal ujian Capstone mendatang.
        </div>
        @endif
    </section>

    <!-- SECTION 1: KELOMPOK BIMBINGAN -->
    <section id="section-pembimbing" class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-primary border-opacity-25">
            <div class="d-flex align-items-center gap-2">
                <span class="p-2 rounded-3 bg-primary text-white d-inline-flex"><i class="ti ti-user-check fs-5"></i></span>
                <h4 class="fw-bold mb-0 text-dark">Kelompok Bimbingan</h4>
            </div>
            <span class="section-divider-badge bg-primary-subtle text-primary">{{ $supervisorGroups->count() }} kelompok · {{ $studentCount($supervisorGroups) }} mahasiswa</span>
        </div>

        @if($supervisorGroups->isNotEmpty())
        <div class="row g-3">
            @foreach($supervisorGroups as $group)
            <div class="col-xl-6">
                @include('capstone.dosen.partials.group_card', ['group' => $group, 'dosen' => $dosen, 'role' => 'pembimbing', 'components' => $components])
            </div>
            @endforeach
        </div>
        @else
        <div class="modern-card p-4 text-center text-muted">Belum ada kelompok yang diplot kepada Anda sebagai dosen pembimbing pada periode ini.</div>
        @endif
    </section>

    <!-- SECTION 2: KELOMPOK PENGUJI -->
    <section id="section-penguji" class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-warning border-opacity-50">
            <div class="d-flex align-items-center gap-2">
                <span class="p-2 rounded-3 bg-warning text-dark d-inline-flex"><i class="ti ti-award fs-5"></i></span>
                <h4 class="fw-bold mb-0 text-dark">Kelompok Ujian sebagai Penguji</h4>
            </div>
            <span class="section-divider-badge bg-warning-subtle text-warning-emphasis">{{ $examinerGroups->count() }} kelompok · {{ $studentCount($examinerGroups) }} mahasiswa</span>
        </div>

        @if($examinerGroups->isNotEmpty())
        <div class="row g-3">
            @foreach($examinerGroups as $group)
            <div class="col-xl-6">
                @include('capstone.dosen.partials.group_card', ['group' => $group, 'dosen' => $dosen, 'role' => 'penguji', 'components' => $components])
            </div>
            @endforeach
        </div>
        @else
        <div class="modern-card p-4 text-center text-muted">Belum ada kelompok yang diplot kepada Anda sebagai dosen penguji pada periode ini.</div>
        @endif
    </section>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).on('submit', '.form-group-title', function(e) {
        e.preventDefault();
        const form = $(this);
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: res => Swal.fire('Berhasil', res.message, 'success').then(() => location.reload()),
            error: err => Swal.fire('Gagal', err.responseJSON?.message || 'Judul gagal disimpan.', 'error')
        });
    });
    $(document).on('submit', '.form-student-scores', function(e) {
        e.preventDefault();
        const form = $(this);
        $.post("{{ url('capstone/dosen/student-scores') }}/" + form.data('group'), form.serialize())
            .done(res => Swal.fire('Berhasil', res.message, 'success'))
            .fail(err => Swal.fire('Gagal', err.responseJSON?.message || 'Nilai gagal disimpan.', 'error'));
    });
    $(document).on('click', '.btn-final-approval', function() {
        const button = $(this);
        const approved = !Number(button.data('approved'));
        $.post("{{ url('capstone/dosen/final-approval') }}/" + button.data('id'), {
                approved: approved ? 1 : 0,
                _token: '{{ csrf_token() }}'
            }).done(res => Swal.fire('Berhasil', res.message, 'success').then(() => location.reload()))
            .fail(err => Swal.fire('Gagal', err.responseJSON?.message || 'ACC final gagal disimpan.', 'error'));
    });
</script>
@endpush
@endsection