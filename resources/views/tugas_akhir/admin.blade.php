@extends('layouts.app')
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h3 class="fw-bold mb-1">Pengelolaan Tugas Akhir</h3><div class="text-muted">Semester {{ $semester?->nama ?? 'aktif belum ditetapkan' }}</div></div>
        @if(auth()->user()->hasAnyRole(['KPS', 'Kaprodi', 'Super Admin']))
        <a class="btn btn-success" href="{{ route('thesis.admin.export') }}">Export Plotting Excel</a>
        @endif
    </div>
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <h5>Kuota Pembimbing 1 dan Beban Dosen</h5>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Dosen</th><th>Bimbingan TA</th><th>Pengujian</th><th>Kuota P1</th></tr></thead>
                <tbody>
                @foreach($lecturers as $lecturer)
                <tr>
                    <td>{{ $lecturer->nama_dosen }}</td>
                    <td>{{ $load[$lecturer->id_dosen]['advising'] }}</td>
                    <td>{{ $load[$lecturer->id_dosen]['examining'] }}</td>
                    <td>{{ $semester ? \App\Models\TugasAkhir\ThesisApplication::where('semester_id', $semester->id)->where('p1_id', $lecturer->id_dosen)->where('status', '!=', 'withdrawn')->count() : 0 }}/10</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if(auth()->user()->hasAnyRole(['KPS', 'Kaprodi', 'Super Admin']))
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <h5>Import Plotting Pembimbing 2 dan Penguji</h5>
        <p class="small text-muted mb-2">Kolom wajib: <code>application_id</code>. Kolom <code>p2_id</code> dan <code>examiner_id</code> memakai ID dosen.</p>
        <form id="importForm" enctype="multipart/form-data" class="d-flex gap-2">
            @csrf<input class="form-control" type="file" name="file" accept=".xlsx,.xls,.csv" required>
            <button class="btn btn-outline-primary">Import</button>
        </form>
    </div>
    @endif
    @if(auth()->user()->hasRole('Super Admin'))
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <h5>Tambah Pengajuan Manual (Override Kuota P1)</h5>
        <form id="manualForm" class="row g-2">
            @csrf
            <div class="col-md-4"><select class="form-select" name="student_id" required><option value="">Pilih mahasiswa</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->name }} ({{ $student->identifier_number }})</option>@endforeach</select></div>
            <div class="col-md-3"><input class="form-control" name="expertise_theme" placeholder="Tema keahlian" required></div>
            <div class="col-md-3"><select class="form-select" name="p1_id" required><option value="">Pilih P1, kuota dapat override</option>@foreach($lecturers as $lecturer)<option value="{{ $lecturer->id_dosen }}">{{ $lecturer->nama_dosen }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Tambah</button></div>
        </form>
    </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 p-3">
        <h5>Pengajuan dan Plotting Panel</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Mahasiswa / Tema</th><th>P1</th><th>Panel Sempro</th><th>Status / Berkas</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse($applications as $application)
                <tr>
                    <td>{{ $application->student?->name }}<br><small>{{ $application->expertise_theme }} · {{ $application->status }}</small></td>
                    <td>{{ $application->p1?->nama_dosen ?? 'Belum ditentukan' }}</td>
                    <td>
                        @if(auth()->user()->hasAnyRole(['KPS', 'Kaprodi', 'Super Admin']))
                        <form class="panelForm" data-id="{{ $application->id }}">
                            @csrf
                            <select class="form-select form-select-sm mb-1" name="p2_id"><option value="">Pilih P2</option>@foreach($lecturers as $lecturer)<option value="{{ $lecturer->id_dosen }}" @selected($application->p2_id === $lecturer->id_dosen)>{{ $lecturer->nama_dosen }}</option>@endforeach</select>
                            <select class="form-select form-select-sm mb-1" name="examiner_id"><option value="">Pilih Penguji</option>@foreach($lecturers as $lecturer)<option value="{{ $lecturer->id_dosen }}" @selected($application->examiner_id === $lecturer->id_dosen)>{{ $lecturer->nama_dosen }}</option>@endforeach</select>
                            <button class="btn btn-sm btn-outline-primary">Simpan Panel</button>
                        </form>
                        @else
                        {{ $application->p2?->nama_dosen }} / {{ $application->examiner?->nama_dosen }}
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-secondary">{{ str_replace('_', ' ', $application->status) }}</span>
                        <div class="small">Konsultasi: {{ $application->sempro_consultation_approved ? 'ACC' : 'Belum' }}</div>
                        @if($application->sempro_approved_at)
                        <div class="small">ACC Sempro: {{ $application->semproApprover?->name ?? 'akun dihapus' }} ({{ $application->sempro_approved_by_role }})</div>
                        @endif
                        @if($application->ta_final_approved_at)
                        <div class="small">ACC Final TA: {{ $application->taFinalApprover?->name ?? 'akun dihapus' }} ({{ $application->ta_final_approved_by_role }})</div>
                        @endif
                        <div class="small">Sempro: {{ $application->sempro_exam_date ?? $application->sempro_proposed_date ?? 'Belum ada jadwal' }} {{ $application->sempro_exam_time ?? $application->sempro_proposed_time }}</div>
                        <div class="small">Sidang TA: {{ $application->ta_exam_date ?? $application->ta_proposed_date ?? 'Belum ada jadwal' }} {{ $application->ta_exam_time ?? $application->ta_proposed_time }} {{ $application->ta_exam_room ? '— '.$application->ta_exam_room : '' }}</div>
                        @foreach($application->documents as $document)
                        <a class="d-block small" href="{{ asset('storage/'.$document->file_path) }}" target="_blank" rel="noopener">{{ str_replace('_', ' ', $document->document_type) }} — {{ $document->verification_status }}</a>
                        @if($document->verified_at)
                        <div class="small text-muted">Verifikasi: {{ $document->verifier?->name ?? 'akun dihapus' }} ({{ $document->verified_by_role }})</div>
                        @endif
                        @endforeach
                    </td>
                    <td class="d-flex flex-wrap gap-1">
                        @if(auth()->user()->hasRole('Super Admin'))
                        <button class="btn btn-sm btn-outline-warning overrideP1" data-id="{{ $application->id }}">Override P1</button>
                        @endif
                        @if($application->sempro_proposed_date)
                        <button class="btn btn-sm btn-outline-primary setSchedule" data-id="{{ $application->id }}" data-date="{{ $application->sempro_proposed_date }}" data-time="{{ substr((string)$application->sempro_proposed_time, 0, 5) }}">Atur Jadwal / Ruangan</button>
                        <button class="btn btn-sm btn-outline-info sendInvitation" data-id="{{ $application->id }}">Kirim Undangan</button>
                        @endif
                        @if($application->ta_proposed_date)
                        <button class="btn btn-sm btn-outline-primary setTaSchedule" data-id="{{ $application->id }}" data-date="{{ $application->ta_proposed_date }}" data-time="{{ substr((string)$application->ta_proposed_time, 0, 5) }}" data-room="{{ $application->ta_exam_room }}">Atur Sidang TA</button>
                        @endif
                        @if($application->ta_exam_date)
                        <button class="btn btn-sm btn-outline-info sendTaInvitation" data-id="{{ $application->id }}">Kirim Undangan TA</button>
                        @endif
                        @foreach(['sk-sempro' => 'SK Sempro', 'ba-sempro' => 'BA Sempro', 'ba-komprehensif' => 'BA Komprehensif', 'ba-sidang-ta' => 'BA Sidang TA'] as $type => $label)
                        <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="{{ route('thesis.admin.print', [$application->id, $type]) }}">{{ $label }}</a>
                        @endforeach
                        @if(auth()->user()->hasAnyRole(['KPS', 'Kaprodi', 'Super Admin']))
                        <button class="btn btn-sm btn-success finishSempro" data-id="{{ $application->id }}">ACC Selesai Sempro</button>
                        <button class="btn btn-sm btn-outline-success finishSemhas" data-id="{{ $application->id }}">Selesai Semhas</button>
                        <button class="btn btn-sm btn-danger finishTa" data-id="{{ $application->id }}">ACC Lulus TA</button>
                        @endif
                    </td>
                </tr>
                @empty<tr><td colspan="5" class="text-center text-muted">Belum ada pengajuan untuk semester aktif.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function showResult(request) {
        request.done(res => Swal.fire('Berhasil', res.message, 'success').then(() => location.reload()))
            .fail(err => Swal.fire('Gagal', err.responseJSON?.message || 'Operasi gagal.', 'error'));
    }
    $('.panelForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        showResult($.ajax({url: "{{ url('tugas-akhir/admin') }}/" + form.data('id') + "/panel", type: 'PUT', data: form.serialize()}));
    });
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        showResult($.ajax({url: "{{ route('thesis.admin.import') }}", type: 'POST', data: new FormData(this), processData: false, contentType: false}));
    });
    $('#manualForm').on('submit', function(e) {
        e.preventDefault();
        showResult($.post("{{ route('thesis.admin.proposal.manual') }}", $(this).serialize()));
    });
    $(document).on('click', '.overrideP1', function() {
        const id = $(this).data('id');
        const options = @json($lecturers->map(fn ($dosen) => ['id' => $dosen->id_dosen, 'name' => $dosen->nama_dosen])->values());
        const select = document.createElement('select');
        select.className = 'form-select';
        options.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            select.appendChild(option);
        });
        Swal.fire({title: 'Override Pembimbing 1', html: select, showCancelButton: true, preConfirm: () => select.value}).then(result => {
            if (result.isConfirmed) showResult($.ajax({url: "{{ url('tugas-akhir/admin') }}/" + id + "/override-p1", type: 'PUT', data: {p1_id: result.value, _token: '{{ csrf_token() }}'}}));
        });
    });
    $(document).on('click', '.setSchedule', function() {
        const id = $(this).data('id');
        const date = $(this).data('date');
        const time = $(this).data('time');
        Swal.fire({
            title: 'Jadwal / Ruangan Sempro',
            html: `<input id="ta-date" class="swal2-input" type="date" value="${date}"><input id="ta-time" class="swal2-input" type="time" value="${time}"><input id="ta-room" class="swal2-input" placeholder="Ruangan">`,
            showCancelButton: true,
            preConfirm: () => ({sempro_exam_date: $('#ta-date').val(), sempro_exam_time: $('#ta-time').val(), sempro_exam_room: $('#ta-room').val()})
        }).then(result => {
            if (result.isConfirmed) showResult($.ajax({url: "{{ url('tugas-akhir/admin') }}/" + id + "/jadwal-sempro", type: 'POST', data: {...result.value, _token: '{{ csrf_token() }}'}}));
        });
    });
    $(document).on('click', '.sendInvitation', function() { showResult($.post("{{ url('tugas-akhir/admin') }}/" + $(this).data('id') + "/undangan-sempro", {_token: '{{ csrf_token() }}'})); });
    $(document).on('click', '.setTaSchedule', function() {
        const button = $(this);
        const date = button.data('date');
        const time = button.data('time');
        const room = button.data('room') || '';
        Swal.fire({
            title: 'Jadwal / Ruangan Sidang TA',
            html: '<input id="ta-exam-date" class="swal2-input" type="date"><input id="ta-exam-time" class="swal2-input" type="time"><input id="ta-exam-room" class="swal2-input" placeholder="Ruangan">',
            showCancelButton: true,
            didOpen: () => {
                $('#ta-exam-date').val(date);
                $('#ta-exam-time').val(time);
                $('#ta-exam-room').val(room);
            },
            preConfirm: () => ({ta_exam_date: $('#ta-exam-date').val(), ta_exam_time: $('#ta-exam-time').val(), ta_exam_room: $('#ta-exam-room').val()})
        }).then(result => {
            if (result.isConfirmed) showResult($.ajax({url: "{{ url('tugas-akhir/admin') }}/" + button.data('id') + "/jadwal-ta", type: 'POST', data: {...result.value, _token: '{{ csrf_token() }}'}}));
        });
    });
    $(document).on('click', '.sendTaInvitation', function() { showResult($.post("{{ url('tugas-akhir/admin') }}/" + $(this).data('id') + "/undangan-ta", {_token: '{{ csrf_token() }}'})); });
    $(document).on('click', '.finishSempro', function() { showResult($.post("{{ url('tugas-akhir/admin') }}/" + $(this).data('id') + "/sempro/selesai", {_token: '{{ csrf_token() }}'})); });
    $(document).on('click', '.finishSemhas', function() { showResult($.post("{{ url('tugas-akhir/admin') }}/" + $(this).data('id') + "/semhas/selesai", {_token: '{{ csrf_token() }}'})); });
    $(document).on('click', '.finishTa', function() { showResult($.post("{{ url('tugas-akhir/admin') }}/" + $(this).data('id') + "/ta/selesai", {_token: '{{ csrf_token() }}'})); });
</script>
@endpush
@endsection
