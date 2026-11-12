@extends('layouts.app')

@push('styles')
<style>
    .capstone-lecturer-page {
        --capstone-ink: #172033;
        color: var(--capstone-ink);
        background: #f5f7fb;
        min-height: 100vh;
    }

    .capstone-lecturer-page .capstone-hero {
        background: linear-gradient(120deg, #172554 0%, #243b80 55%, #3758d6 100%);
        border-radius: 22px;
        padding: clamp(1.35rem, 3vw, 2.25rem);
        box-shadow: 0 14px 35px rgba(28, 47, 111, .15);
    }

    .capstone-lecturer-page .card {
        border: 1px solid rgba(226, 232, 240, .85);
        border-radius: 18px;
        box-shadow: 0 5px 18px rgba(20, 33, 61, .045);
    }

    .capstone-roadmap-step {
        border-top: 3px solid #dbe3f0;
        height: 100%;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4 min-vh-100 capstone-lecturer-page">
    <div class="card border-0 text-white mb-4 capstone-hero">
        <h2 class="fw-bold text-white mb-1"><i class="ti ti-chalkboard text-info me-2"></i>Panel Dosen Capstone Design</h2>
        <p class="text-white-50 mb-0">Kelola kelompok bimbingan dan pengujian, jadwal ujian, judul, dan penilaian Capstone.</p>
    </div>

    <form method="GET" action="{{ route('capstone.dosen.index') }}" class="card bg-white p-3 mb-4">
        <div class="row align-items-end g-3">
            @if($isActingAsProfile)
                <div class="col-lg-5">
                    <label class="form-label fw-semibold" for="profileFilter">Profil dosen</label>
                    <select id="profileFilter" name="id_dosen" class="form-select">
                        @foreach($availableProfiles as $profile)
                            <option value="{{ $profile->id_dosen }}" @selected($profile->id_dosen === $dosen->id_dosen)>{{ $profile->nama_dosen }}{{ $profile->nidn ? ' ('.$profile->nidn.')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-lg-5">
                <label class="form-label fw-semibold" for="semesterFilter">Periode akademik</label>
                <select id="semesterFilter" name="semester_id" class="form-select">
                    <option value="">Semua periode</option>
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected((string) $selectedSemester === (string) $semester->id)>
                            {{ $semester->nama }}{{ $semester->aktif ? ' (Aktif)' : '' }}
                        </option>
                    @endforeach
                    <option value="unassigned" @selected($selectedSemester === 'unassigned')>Periode belum tercatat</option>
                </select>
                <div class="form-text">Kelompok baru mengikuti periode aktif; data lama dapat difilter sebagai belum tercatat.</div>
            </div>
            <div class="col-lg-2 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1"><i class="ti ti-filter me-1"></i>Terapkan</button>
                <a class="btn btn-outline-secondary" href="{{ route('capstone.dosen.index', $isActingAsProfile ? ['id_dosen' => $dosen->id_dosen] : []) }}" title="Hapus filter">
                    <i class="ti ti-x"></i>
                </a>
            </div>
        </div>
        @if($isActingAsProfile)
            <div class="small text-info-emphasis mt-2">Mode bertindak sebagai dosen: {{ $dosen->nama_dosen }}{{ $dosen->nidn ? ' ('.$dosen->nidn.')' : '' }}. Aksi dicatat atas akun {{ auth()->user()->name }}.</div>
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

    <section class="card bg-white mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <h5 class="fw-bold mb-1"><i class="ti ti-route text-primary me-2"></i>Roadmap Capstone</h5>
            <p class="small text-muted mb-0">Jumlah mahasiswa dihitung dari ketua dan anggota yang telah menerima undangan, sesuai periode terpilih.</p>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="row g-3">
                <div class="col-sm-6 col-xl-3">
                    <div class="capstone-roadmap-step rounded-3 bg-light p-3">
                        <span class="badge bg-secondary mb-2">01 · Pendaftaran & Plotting</span>
                        <div class="fw-semibold">Plot dosen dan tetapkan judul</div>
                        <div class="text-muted small mt-2">{{ $registrationGroups->count() }} kelompok · {{ $studentCount($registrationGroups) }} mahasiswa</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="capstone-roadmap-step rounded-3 bg-light p-3">
                        <span class="badge bg-info text-dark mb-2">02 · Pelaksanaan</span>
                        <div class="fw-semibold">Konsultasi, draf, dan persetujuan KPI</div>
                        <div class="text-muted small mt-2">{{ $implementationGroups->count() }} kelompok · {{ $studentCount($implementationGroups) }} mahasiswa</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="capstone-roadmap-step rounded-3 bg-light p-3">
                        <span class="badge bg-warning text-dark mb-2">03 · Sidang Ujian</span>
                        <div class="fw-semibold">Jadwal, ruang, undangan, dan penilaian</div>
                        <div class="text-muted small mt-2">{{ $examGroups->count() }} kelompok · {{ $studentCount($examGroups) }} mahasiswa</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="capstone-roadmap-step rounded-3 bg-light p-3">
                        <span class="badge bg-success mb-2">04 · Laporan Final</span>
                        <div class="fw-semibold">ACC Koordinator, Pembimbing, dan KPS</div>
                        <div class="text-muted small mt-2">{{ $completedGroups->count() }} kelompok · {{ $studentCount($completedGroups) }} mahasiswa</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-primary-subtle p-3 text-primary me-3"><i class="ti ti-user-check fs-2"></i></div>
                    <div>
                        <div class="small fw-semibold text-secondary">Kelompok Dosen Pembimbing</div>
                        <div class="h3 fw-bold mb-0">{{ $supervisorGroups->count() }} <span class="fs-6 fw-normal text-muted">kelompok</span></div>
                        <div class="small text-muted">{{ $studentCount($supervisorGroups) }} mahasiswa</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-warning-subtle p-3 text-warning me-3"><i class="ti ti-award fs-2"></i></div>
                    <div>
                        <div class="small fw-semibold text-secondary">Kelompok Ujian sebagai Penguji</div>
                        <div class="h3 fw-bold mb-0">{{ $examinerGroups->count() }} <span class="fs-6 fw-normal text-muted">kelompok</span></div>
                        <div class="small text-muted">{{ $studentCount($examinerGroups) }} mahasiswa</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="card bg-white mb-4 overflow-hidden">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <h5 class="fw-bold mb-0"><i class="ti ti-calendar-time text-primary me-2"></i>Jadwal Ujian Capstone</h5>
        </div>
        <div class="card-body p-4">
            @if($upcomingAgenda->isNotEmpty())
                <div class="row g-3">
                    @foreach($upcomingAgenda as $group)
                        @php
                            $examDateTime = \Carbon\Carbon::parse($group->exam_date . ' ' . ($group->exam_time ?? '00:00'));
                            $agendaRoles = [];
                            if ($group->supervisor_id === $dosen->id_dosen) $agendaRoles[] = 'Pembimbing';
                            if ($group->examiner_id === $dosen->id_dosen) $agendaRoles[] = 'Penguji';
                            $agendaStudentCount = 1 + $group->members->where('status', 'accepted')->count();
                        @endphp
                        <div class="col-md-6 col-xl-4">
                            <article class="h-100 p-3 border rounded-3 border-start border-4 {{ in_array('Pembimbing', $agendaRoles, true) ? 'border-primary bg-primary-subtle' : 'border-warning bg-warning-subtle' }}">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                    <span class="badge {{ in_array('Pembimbing', $agendaRoles, true) ? 'bg-primary' : 'bg-warning text-dark' }}">Sebagai {{ implode(' & ', $agendaRoles) }}</span>
                                    <strong class="small"><i class="ti ti-clock me-1"></i>{{ $examDateTime->format('H:i') }}</strong>
                                </div>
                                <h6 class="fw-bold mb-1">{{ $group->title ?: 'Judul belum ditetapkan' }}</h6>
                                <div class="small text-muted mb-3">{{ $group->leader?->name ?? 'Mahasiswa' }} · {{ $group->expertise?->name ?? 'Bidang belum ditentukan' }}</div>
                                <div class="small border-top pt-2">
                                    <div><i class="ti ti-calendar-event text-primary me-1"></i>{{ $examDateTime->translatedFormat('d M Y') }}</div>
                                    <div><i class="ti ti-map-pin text-danger me-1"></i>{{ $group->exam_room ?: 'Ruangan belum ditetapkan' }}</div>
                                    <div><i class="ti ti-users text-secondary me-1"></i>{{ $agendaStudentCount }} mahasiswa</div>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-muted border border-dashed rounded-3 bg-light">
                    <i class="ti ti-calendar-off fs-1 d-block mb-2"></i>Belum ada jadwal ujian Capstone mendatang.
                </div>
            @endif
        </div>
    </section>

    <section class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="fw-bold mb-0"><i class="ti ti-user-check text-primary me-2"></i>Kelompok Bimbingan</h4>
            <span class="badge bg-primary-subtle text-primary">{{ $supervisorGroups->count() }} kelompok · {{ $studentCount($supervisorGroups) }} mahasiswa</span>
        </div>
        @forelse($supervisorGroups as $group)
            @include('capstone.dosen.partials.group_card', ['group' => $group, 'dosen' => $dosen, 'role' => 'pembimbing', 'components' => $components])
        @empty
            <div class="alert alert-light border text-muted">Belum ada kelompok yang diplot kepada Anda sebagai dosen pembimbing pada periode ini.</div>
        @endforelse
    </section>

    <section class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="fw-bold mb-0"><i class="ti ti-award text-warning me-2"></i>Kelompok Ujian sebagai Penguji</h4>
            <span class="badge bg-warning-subtle text-warning-emphasis">{{ $examinerGroups->count() }} kelompok · {{ $studentCount($examinerGroups) }} mahasiswa</span>
        </div>
        @forelse($examinerGroups as $group)
            @include('capstone.dosen.partials.group_card', ['group' => $group, 'dosen' => $dosen, 'role' => 'penguji', 'components' => $components])
        @empty
            <div class="alert alert-light border text-muted">Belum ada kelompok yang diplot kepada Anda sebagai dosen penguji pada periode ini.</div>
        @endforelse
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
