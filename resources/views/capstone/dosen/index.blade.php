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
</style>
@endpush

@section('content')
<div class="container-fluid py-4 min-vh-100 capstone-lecturer-page">
    @if($isActingAsProfile)
    <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><strong>Mode bertindak sebagai dosen:</strong> {{ $dosen->nama_dosen }}{{ $dosen->nidn ? ' ('.$dosen->nidn.')' : '' }}. Aksi dicatat atas akun {{ auth()->user()->name }}.</div>
        <form method="GET" action="{{ route('capstone.dosen.index') }}" class="d-flex gap-2">
            <select name="id_dosen" class="form-select form-select-sm" required>
                @foreach($availableProfiles as $profile)
                <option value="{{ $profile->id_dosen }}" @selected($profile->id_dosen === $dosen->id_dosen)>{{ $profile->nama_dosen }}{{ $profile->nidn ? ' ('.$profile->nidn.')' : '' }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-primary">Ganti Profil</button>
        </form>
    </div>
    @endif

    <div class="card border-0 text-white mb-4 capstone-hero">
        <h2 class="fw-bold text-white mb-1"><i class="ti ti-chalkboard text-info me-2"></i>Panel Dosen Capstone Design</h2>
        <p class="text-white-50 mb-0">Kelola kelompok bimbingan dan pengujian, jadwal ujian, judul, dan penilaian Capstone.</p>
    </div>

    @php
        $supervisorGroups = $groups->filter(fn ($group) => $group->supervisor_id === $dosen->id_dosen)->values();
        $examinerGroups = $groups->filter(fn ($group) => $group->examiner_id === $dosen->id_dosen)->values();
        $agendaGroups = $groups
            ->filter(fn ($group) => $group->exam_date)
            ->sortBy(fn ($group) => $group->exam_date . ' ' . ($group->exam_time ?? '00:00'))
            ->values();
        $upcomingAgenda = $agendaGroups->filter(fn ($group) => \Carbon\Carbon::parse($group->exam_date)->endOfDay()->gte(now()));
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-primary-subtle p-3 text-primary me-3"><i class="ti ti-user-check fs-2"></i></div>
                    <div><div class="small fw-semibold text-secondary">Kelompok Dosen Pembimbing</div><div class="h3 fw-bold mb-0">{{ $supervisorGroups->count() }}</div></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-warning-subtle p-3 text-warning me-3"><i class="ti ti-award fs-2"></i></div>
                    <div><div class="small fw-semibold text-secondary">Kelompok Dosen Penguji</div><div class="h3 fw-bold mb-0">{{ $examinerGroups->count() }}</div></div>
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
                            <div><i class="ti ti-user-check text-secondary me-1"></i>Pembimbing: {{ $group->supervisor?->nama_dosen ?? 'Belum diplot' }}</div>
                            <div><i class="ti ti-users text-secondary me-1"></i>Penguji: {{ $group->examiner?->nama_dosen ?? 'Belum diplot' }}</div>
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
            <span class="badge bg-primary-subtle text-primary">{{ $supervisorGroups->count() }} kelompok</span>
        </div>
        @forelse($supervisorGroups as $group)
            @include('capstone.dosen.partials.group_card', ['group' => $group, 'dosen' => $dosen, 'role' => 'pembimbing', 'components' => $components])
        @empty
        <div class="alert alert-light border text-muted">Belum ada kelompok yang diplot kepada Anda sebagai dosen pembimbing.</div>
        @endforelse
    </section>

    <section class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="fw-bold mb-0"><i class="ti ti-award text-warning me-2"></i>Kelompok Ujian sebagai Penguji</h4>
            <span class="badge bg-warning-subtle text-warning-emphasis">{{ $examinerGroups->count() }} kelompok</span>
        </div>
        @forelse($examinerGroups as $group)
            @include('capstone.dosen.partials.group_card', ['group' => $group, 'dosen' => $dosen, 'role' => 'penguji', 'components' => $components])
        @empty
        <div class="alert alert-light border text-muted">Belum ada kelompok yang diplot kepada Anda sebagai dosen penguji.</div>
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
