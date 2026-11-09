@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h3 class="fw-bold">Panel Dosen Capstone Design</h3>
        <p class="text-secondary">Tetapkan judul, input nilai kelompok, cetak rekap, dan berikan ACC final sebagai dosen pembimbing.</p>
    </div>
    @forelse($groups as $group)
    @php
        $isSupervisor = $group->supervisor_id === $dosen->id_dosen;
        $category = $isSupervisor ? 'pembimbing' : 'penguji';
        $groupStudents = collect([$group->leader])->merge($group->members->where('status', 'accepted')->pluck('student'))->filter();
        $groupComponents = $components->where('category', $category);
    @endphp
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-2">
            <div>
                <h5 class="fw-bold">{{ $group->title ?: 'Judul belum ditetapkan' }}</h5>
                <div class="small text-muted">{{ $group->expertise?->name }} · {{ $group->exam_date }} {{ $group->exam_time }} · {{ $group->exam_room }}</div>
            </div>
            <a class="btn btn-outline-primary btn-sm align-self-start" href="{{ route('capstone.dosen.scores.show', $group->id) }}" target="_blank" rel="noopener">Cetak / Unduh Nilai</a>
        </div>
        @if($isSupervisor)
        <form class="form-group-title mt-3" data-id="{{ $group->id }}">
            @csrf
            <label class="form-label">Judul resmi Capstone Design</label>
            <div class="input-group">
                <input name="title" class="form-control" value="{{ $group->title }}" required maxlength="255">
                <button class="btn btn-outline-secondary">Simpan Judul</button>
            </div>
        </form>
        @endif
        <hr>
        <h6 class="fw-bold">Input Nilai ({{ ucfirst($category) }})</h6>
        @forelse($groupStudents as $student)
        <form class="form-student-scores mb-3" data-group="{{ $group->id }}">
            @csrf
            <h6>{{ $student->name }}</h6>
            @forelse($groupComponents as $component)
            @php
                $score = $group->studentScores->first(fn ($item) => $item->student_id === $student->id && $item->component_id === $component->id && $item->evaluator_id === $dosen->id_dosen);
            @endphp
            <div class="row align-items-center mb-2">
                <label class="col-md-5 col-form-label">{{ $component->name }} ({{ $component->percentage }}%)</label>
                <div class="col-md-3">
                    <input type="hidden" name="scores[{{ $loop->index }}][student_id]" value="{{ $student->id }}">
                    <input type="hidden" name="scores[{{ $loop->index }}][component_id]" value="{{ $component->id }}">
                    <input type="number" step="0.01" min="0" max="100" name="scores[{{ $loop->index }}][score]" value="{{ $score?->score }}" class="form-control" placeholder="0–100" required>
                </div>
            </div>
            @empty
            <p class="text-muted">Komponen nilai kategori {{ $category }} belum diatur.</p>
            @endforelse
            @if($groupComponents->isNotEmpty())
            <button class="btn btn-primary btn-sm">Simpan Nilai {{ $student->name }}</button>
            @endif
        </form>
        @empty
        <p class="text-muted">Tidak ada mahasiswa aktif pada kelompok ini.</p>
        @endforelse
        @if($isSupervisor)
        <div class="d-flex flex-wrap gap-2 mt-3">
            <button type="button" class="btn btn-{{ $group->acc_supervisor ? 'outline-danger' : 'success' }} btn-sm btn-final-approval" data-id="{{ $group->id }}" data-approved="{{ $group->acc_supervisor ? 1 : 0 }}">
                {{ $group->acc_supervisor ? 'Cabut ACC Final Pembimbing' : 'ACC Final Pembimbing' }}
            </button>
            <span class="small text-muted align-self-center">Status: {{ $group->supervisor_approved_by_role ?? 'Belum ACC' }}{{ $group->supervisorApprover ? ' — '.$group->supervisorApprover->name : '' }}</span>
        </div>
        @endif
    </div>
    @empty
    <div class="alert alert-info">Belum ada kelompok yang diplot kepada profil dosen ini.</div>
    @endforelse
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).on('submit', '.form-group-title', function(e) {
        e.preventDefault();
        const form = $(this);
        $.ajax({
            url: "{{ url('capstone/dosen/group-title') }}/" + form.data('id'),
            type: 'PUT',
            data: form.serialize(),
            success: res => Swal.fire('Berhasil', res.message, 'success'),
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
