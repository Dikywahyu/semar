@extends('layouts.app')
@section('content')
<div class="container-fluid py-4">
    <h3 class="fw-bold">Penilaian Tugas Akhir</h3>
    <p class="text-muted">Profil dosen: {{ $dosen->nama_dosen }}{{ $dosen->nidn ? ' ('.$dosen->nidn.')' : '' }}</p>
    <p class="text-muted">Rumus nilai: rata-rata aritmetika sederhana dari nilai P1, P2, dan Penguji. Nilai dihitung setelah ketiga penilai mengisi.</p>
    @if(!$canAct)
    <div class="alert alert-info">Mode Super Admin hanya-baca. Nilai dan judul hanya dapat diubah oleh dosen yang ditugaskan.</div>
    @endif
    @forelse($applications as $application)
    @php
        $role = $application->p1_id === $dosen->id_dosen ? 'P1' : ($application->p2_id === $dosen->id_dosen ? 'P2' : 'PU');
        $types = $application->sempro_exam_date
            ? ['sempro_pelaksanaan' => 'Penilaian Pelaksanaan Sempro', 'sempro_komprehensif' => 'Nilai Komprehensif']
            : [];
        if ($application->semhas_finished_at) {
            $types['nilai_ta_final'] = 'Nilai Final TA';
        }
    @endphp
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <h5>{{ $application->student?->name }} — {{ $application->expertise_theme }}</h5>
        <div class="small text-muted mb-3">Peran Anda: {{ $role }} · P1 {{ $application->p1?->nama_dosen }} · P2 {{ $application->p2?->nama_dosen ?? 'belum ditetapkan' }} · Penguji {{ $application->examiner?->nama_dosen ?? 'belum ditetapkan' }}</div>
        @if($canAct && $role === 'P1')
        <form class="titleForm row g-2 mb-3" data-id="{{ $application->id }}">
            @csrf
            <div class="col-md-9"><input class="form-control" name="title" value="{{ $application->title }}" placeholder="Judul Tugas Akhir" required maxlength="255"></div>
            <div class="col-md-3"><button class="btn btn-outline-primary w-100">Simpan Judul</button></div>
        </form>
        @else
        <div class="small mb-3">Judul TA: {{ $application->title ?? 'Belum diinput Pembimbing 1' }}</div>
        @endif
        @if($types === [])
        <div class="alert alert-info mb-0">Penilaian Sempro dapat diinput setelah Admin menetapkan jadwal ujian.</div>
        @endif
        @foreach($types as $type => $label)
        @php
            $ownScore = $application->scores->first(fn ($score) => $score->assessment_type === $type && $score->evaluator_role === $role);
            $scores = $application->scores->where('assessment_type', $type);
            $average = $scores->count() === 3 ? number_format((float) $scores->avg('score'), 2) : null;
        @endphp
        @if($canAct)
        <form class="scoreForm border rounded p-3 mb-3" data-id="{{ $application->id }}">
            @csrf<input type="hidden" name="assessment_type" value="{{ $type }}">
            <h6>{{ $label }} — nilai gabungan: {{ $average ?? 'Menunggu P1, P2, dan Penguji lengkap' }}</h6>
            <div class="row g-2">
                <div class="col-md-3"><label class="form-label">Nilai Anda (0-100)</label><input class="form-control" type="number" step="0.01" min="0" max="100" name="score" value="{{ $ownScore?->score }}" required></div>
                <div class="col-md-7"><label class="form-label">Catatan</label><input class="form-control" name="notes" value="{{ $ownScore?->notes }}"></div>
                <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Simpan Nilai</button></div>
            </div>
            <small class="text-muted">P1: {{ $scores->firstWhere('evaluator_role', 'P1')?->score ?? '—' }} · P2: {{ $scores->firstWhere('evaluator_role', 'P2')?->score ?? '—' }} · PU: {{ $scores->firstWhere('evaluator_role', 'PU')?->score ?? '—' }}</small>
        </form>
        @else
        <div class="border rounded p-3 mb-3">
            <h6>{{ $label }} — nilai gabungan: {{ $average ?? 'Menunggu P1, P2, dan Penguji lengkap' }}</h6>
            <small class="text-muted">P1: {{ $scores->firstWhere('evaluator_role', 'P1')?->score ?? '—' }} · P2: {{ $scores->firstWhere('evaluator_role', 'P2')?->score ?? '—' }} · PU: {{ $scores->firstWhere('evaluator_role', 'PU')?->score ?? '—' }}</small>
        </div>
        @endif
        @endforeach
    </div>
    @empty<div class="alert alert-info">Belum ada mahasiswa yang ditugaskan kepada Anda.</div>@endforelse
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $('.titleForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        $.ajax({url: "{{ url('tugas-akhir') }}/" + form.data('id') + "/judul", type: 'PUT', data: form.serialize()})
            .done(res => Swal.fire('Berhasil', res.message, 'success').then(() => location.reload()))
            .fail(err => Swal.fire('Gagal', err.responseJSON?.message || 'Judul gagal disimpan.', 'error'));
    });
    $('.scoreForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        $.post("{{ url('tugas-akhir') }}/" + form.data('id') + "/nilai", form.serialize())
            .done(res => Swal.fire('Berhasil', res.message + (res.average ? ` Nilai rata-rata saat ini ${res.average}.` : ' Nilai final dihitung setelah seluruh panel mengisi.'), 'success').then(() => location.reload()))
            .fail(err => Swal.fire('Gagal', err.responseJSON?.message || 'Nilai gagal disimpan.', 'error'));
    });
</script>
@endpush
@endsection
