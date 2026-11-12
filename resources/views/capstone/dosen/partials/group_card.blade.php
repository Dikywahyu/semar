@php
    $isSupervisor = $role === 'pembimbing';
    $groupStudents = collect([$group->leader])
        ->merge($group->members->where('status', 'accepted')->pluck('student'))
        ->filter();
    $groupComponents = $components->where('category', $role);
@endphp
<article class="card bg-white p-3 p-md-4 mb-3">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
        <div class="flex-grow-1">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <span class="badge {{ $isSupervisor ? 'bg-primary' : 'bg-warning text-dark' }}">Dosen {{ ucfirst($role) }}</span>
                <span class="badge {{ $group->kpi_approval === 'approved' ? 'bg-success' : ($group->kpi_approval === 'rejected' ? 'bg-danger' : 'bg-secondary') }}">KPI: {{ ucfirst($group->kpi_approval ?? 'pending') }}</span>
                <span class="badge {{ $group->final_status === 'selesai' ? 'bg-success' : 'bg-light text-secondary border' }}">Status: {{ $group->final_status === 'selesai' ? 'Selesai' : 'Proses' }}</span>
            </div>
            <h5 class="fw-bold mb-1">{{ $group->title ?: 'Judul belum ditetapkan' }}</h5>
            <div class="text-muted small">{{ $group->expertise?->name ?? 'Bidang belum ditentukan' }} · {{ $group->project_location ?: 'Lokasi belum ditentukan' }}</div>
        </div>
        <a class="btn btn-outline-primary btn-sm align-self-lg-start" href="{{ route('capstone.dosen.scores.show', $group->id) }}" target="_blank" rel="noopener">
            <i class="ti ti-printer me-1"></i>Cetak / Unduh Nilai
        </a>
    </div>

    <div class="row g-3 my-2">
        <div class="col-md-6">
            <div class="p-3 rounded-3 bg-light h-100">
                <div class="small text-muted mb-1">Jadwal Ujian</div>
                @if($group->exam_date)
                    @php($examDateTime = \Carbon\Carbon::parse($group->exam_date . ' ' . ($group->exam_time ?? '00:00')))
                    <div class="fw-semibold"><i class="ti ti-calendar-event text-primary me-1"></i>{{ $examDateTime->translatedFormat('d M Y') }} · {{ $examDateTime->format('H:i') }}</div>
                    <div class="small text-secondary mt-1"><i class="ti ti-map-pin text-danger me-1"></i>{{ $group->exam_room ?: 'Ruangan belum ditetapkan Admin' }}</div>
                @elseif($group->proposed_exam_date)
                    <div class="fw-semibold text-warning-emphasis"><i class="ti ti-calendar-question me-1"></i>Usulan {{ \Carbon\Carbon::parse($group->proposed_exam_date)->translatedFormat('d M Y') }} · {{ $group->proposed_exam_time }}</div>
                    <div class="small text-secondary mt-1">Menunggu penetapan ruangan oleh Admin.</div>
                @else
                    <div class="text-muted">Belum ada usulan jadwal ujian.</div>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 rounded-3 bg-light h-100">
                <div class="small text-muted mb-1">Dewan Ujian</div>
                <div class="small"><strong>Pembimbing:</strong> {{ $group->supervisor?->nama_dosen ?? 'Belum diplot' }}</div>
                <div class="small"><strong>Penguji:</strong> {{ $group->examiner?->nama_dosen ?? 'Belum diplot' }}</div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <div class="small fw-semibold text-secondary mb-2">Anggota Kelompok</div>
        <div class="d-flex flex-wrap gap-2">
            @foreach($groupStudents as $student)
            <span class="badge bg-light text-dark border">{{ $student->name }}{{ $student->identifier_number ? ' · ' . $student->identifier_number : '' }}</span>
            @endforeach
        </div>
    </div>

    @if($isSupervisor)
    <form class="form-group-title border rounded-3 p-3 mb-3" action="{{ route('capstone.dosen.title.update', $group->id) }}">
        @csrf
        @method('PUT')
        <label class="form-label fw-semibold">Judul resmi Capstone Design</label>
        <div class="input-group">
            <input name="title" class="form-control" value="{{ $group->title }}" required maxlength="255" placeholder="Masukkan judul resmi Capstone Design">
            <button type="submit" class="btn btn-outline-primary">Simpan Judul</button>
        </div>
    </form>
    @endif

    <div class="border-top pt-3">
        <h6 class="fw-bold mb-3">Input Nilai {{ ucfirst($role) }}</h6>
        @forelse($groupStudents as $student)
        <form class="form-student-scores border rounded-3 p-3 mb-3" data-group="{{ $group->id }}">
            @csrf
            <div class="fw-semibold mb-2">{{ $student->name }}{{ $student->identifier_number ? ' · ' . $student->identifier_number : '' }}</div>
            @forelse($groupComponents as $component)
            @php
                $score = $group->studentScores->first(fn ($item) => (string) $item->student_id === (string) $student->id && (string) $item->component_id === (string) $component->id && (string) $item->evaluator_id === (string) $dosen->id_dosen);
            @endphp
            <div class="row align-items-center mb-2">
                <label class="col-md-5 col-form-label">{{ $component->name }} <span class="text-muted">({{ $component->percentage }}%)</span></label>
                <div class="col-md-4 col-lg-3">
                    <input type="hidden" name="scores[{{ $loop->index }}][student_id]" value="{{ $student->id }}">
                    <input type="hidden" name="scores[{{ $loop->index }}][component_id]" value="{{ $component->id }}">
                    <input type="number" step="0.01" min="0" max="100" name="scores[{{ $loop->index }}][score]" value="{{ $score?->score }}" class="form-control" placeholder="0–100" required>
                </div>
            </div>
            @empty
            <p class="text-muted mb-0">Komponen nilai kategori {{ $role }} belum diatur.</p>
            @endforelse
            @if($groupComponents->isNotEmpty())
            <button class="btn btn-primary btn-sm mt-2">Simpan Nilai {{ $student->name }}</button>
            @endif
        </form>
        @empty
        <p class="text-muted">Tidak ada mahasiswa aktif pada kelompok ini.</p>
        @endforelse
    </div>

    @if($isSupervisor)
    <div class="d-flex flex-wrap gap-2 align-items-center border-top pt-3">
        <button type="button" class="btn btn-{{ $group->acc_supervisor ? 'outline-danger' : 'success' }} btn-sm btn-final-approval" data-id="{{ $group->id }}" data-approved="{{ $group->acc_supervisor ? 1 : 0 }}">
            {{ $group->acc_supervisor ? 'Cabut ACC Final Pembimbing' : 'ACC Final Pembimbing' }}
        </button>
        <span class="small text-muted">Status: {{ $group->supervisor_approved_by_role ?? 'Belum ACC' }}{{ $group->supervisorApprover ? ' — '.$group->supervisorApprover->name : '' }}</span>
    </div>
    @endif
</article>
