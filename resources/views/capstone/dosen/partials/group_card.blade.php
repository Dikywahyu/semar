@php
$isSupervisor = $role === 'pembimbing';
$groupStudents = collect([$group->leader])
->merge($group->members->where('status', 'accepted')->pluck('student'))
->filter();
$groupComponents = $components->where('category', $role);
$modalId = 'capstoneGroupModal-' . $group->id . '-' . $role;

$kpiClass = match($group->kpi_approval) {
'approved' => 'bg-success-subtle text-success',
'rejected' => 'bg-danger-subtle text-danger',
default => 'bg-secondary-subtle text-secondary',
};
@endphp

<article class="group-item-card {{ $isSupervisor ? 'is-supervisor' : 'is-examiner' }} p-3 h-100 d-flex flex-column justify-content-between">
    <div>
        <!-- Header Badges -->
        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
            <div class="d-flex flex-wrap align-items-center gap-1">
                <span class="badge rounded-pill {{ $isSupervisor ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning-emphasis' }}">
                    {{ ucfirst($role) }}
                </span>
                <span class="badge rounded-pill {{ $kpiClass }}">
                    KPI: {{ ucfirst($group->kpi_approval ?? 'Pending') }}
                </span>
                @if($group->final_status === 'selesai')
                <span class="badge rounded-pill bg-success text-white"><i class="ti ti-check me-1"></i>Selesai</span>
                @endif
            </div>
            <span class="small text-muted fw-medium">{{ $group->semesterMaster?->nama ?? 'Belum tercatat' }}</span>
        </div>

        <!-- Judul & Bidang -->
        <h6 class="fw-bold text-dark mb-1" style="line-height: 1.4;">
            {{ $group->title ?: 'Judul belum ditetapkan' }}
        </h6>
        <div class="small text-muted mb-3">
            <i class="ti ti-bookmark text-primary me-1"></i>{{ $group->expertise?->name ?? 'Bidang belum ditentukan' }}
            <span class="mx-1">·</span>
            <i class="ti ti-users me-1"></i>{{ $groupStudents->count() }} Mahasiswa
        </div>

        <!-- Info Ringkas Jadwal & Pasangan Dosen -->
        <div class="rounded-3 bg-light px-3 py-2 mb-3 small">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-secondary">Jadwal:</span>
                @if($group->exam_date)
                @php $examDateTime = \Carbon\Carbon::parse($group->exam_date . ' ' . ($group->exam_time ?? '00:00')); @endphp
                <span class="fw-semibold text-dark">
                    {{ $examDateTime->translatedFormat('d M Y') }}, {{ $examDateTime->format('H:i') }}
                    <span class="text-muted fw-normal">({{ $group->exam_room ?: 'Ruang TBD' }})</span>
                </span>
                @elseif($group->proposed_exam_date)
                <span class="text-warning-emphasis fw-medium">Usulan {{ \Carbon\Carbon::parse($group->proposed_exam_date)->translatedFormat('d M Y') }}</span>
                @else
                <span class="text-muted">Belum dijadwalkan</span>
                @endif
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-secondary">{{ $isSupervisor ? 'Penguji:' : 'Pembimbing:' }}</span>
                <span class="fw-medium text-dark text-truncate" style="max-width: 65%;">
                    {{ $isSupervisor ? ($group->examiner?->nama_dosen ?? 'Belum diplot') : ($group->supervisor?->nama_dosen ?? 'Belum diplot') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Footer Aksi -->
    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
        <div class="small text-muted text-truncate pe-2">
            <i class="ti ti-user me-1"></i>{{ $group->leader?->name ?? 'Ketua Kelompok' }}
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <a class="btn btn-light btn-sm border" href="{{ route('capstone.dosen.scores.show', $group->id) }}" target="_blank" rel="noopener" title="Cetak Nilai">
                <i class="ti ti-printer"></i>
            </a>
            <button type="button" class="btn {{ $isSupervisor ? 'btn-primary' : 'btn-warning text-dark fw-semibold' }} btn-sm px-3" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                <i class="ti ti-edit me-1"></i>Nilai & Detail
            </button>
        </div>
    </div>
</article>

<!-- Modal Detail & Penilaian (Tetap Lengkap) -->
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-bottom">
                <div>
                    <span class="badge rounded-pill {{ $isSupervisor ? 'bg-primary' : 'bg-warning text-dark' }} mb-1">Dosen {{ ucfirst($role) }}</span>
                    <h5 class="modal-title fw-bold" id="{{ $modalId }}Label">{{ $group->title ?: 'Judul belum ditetapkan' }}</h5>
                    <div class="small text-muted">{{ $group->semesterMaster?->nama ?? 'Periode belum tercatat' }} · {{ $groupStudents->count() }} mahasiswa</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <div class="small text-muted mb-1">Jadwal Ujian</div>
                            @if($group->exam_date)
                            <div class="fw-semibold">{{ \Carbon\Carbon::parse($group->exam_date)->translatedFormat('d M Y') }} · {{ $group->exam_time }}</div>
                            <div class="small text-secondary">{{ $group->exam_room ?: 'Ruangan belum ditetapkan Admin' }}</div>
                            @elseif($group->proposed_exam_date)
                            <div class="fw-semibold">Usulan {{ \Carbon\Carbon::parse($group->proposed_exam_date)->translatedFormat('d M Y') }} · {{ $group->proposed_exam_time }}</div>
                            <div class="small text-secondary">Menunggu penetapan ruangan oleh Admin.</div>
                            @else
                            <div class="text-muted">Belum ada usulan jadwal ujian.</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <div class="small text-muted mb-1">Bidang & Dewan Ujian</div>
                            <div class="small">{{ $group->expertise?->name ?? 'Bidang belum ditentukan' }} · {{ $group->project_location ?: 'Lokasi belum ditentukan' }}</div>
                            <div class="small"><strong>Pembimbing:</strong> {{ $group->supervisor?->nama_dosen ?? 'Belum diplot' }}</div>
                            <div class="small"><strong>Penguji:</strong> {{ $group->examiner?->nama_dosen ?? 'Belum diplot' }}</div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold">Mahasiswa dalam Kelompok</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($groupStudents as $student)
                        <span class="badge bg-light text-dark border py-2 px-3">{{ $student->name }}{{ $student->identifier_number ? ' · ' . $student->identifier_number : '' }}</span>
                        @endforeach
                    </div>
                </div>

                @if($isSupervisor)
                <form class="form-group-title border rounded-3 p-3 mb-4 bg-light bg-opacity-50" method="POST" action="{{ route('capstone.dosen.title.update', $group->id) }}">
                    @csrf
                    @method('PUT')
                    <label class="form-label fw-semibold">Judul Resmi Capstone Design</label>
                    <div class="input-group">
                        <input name="title" class="form-control bg-white" value="{{ $group->title }}" required maxlength="255" placeholder="Masukkan judul resmi Capstone Design">
                        <button type="submit" class="btn btn-primary">Simpan Judul</button>
                    </div>
                </form>
                @endif

                <div class="border-top pt-3">
                    <h6 class="fw-bold mb-3">Penilaian {{ ucfirst($role) }}</h6>
                    @forelse($groupStudents as $student)
                    <form class="form-student-scores border rounded-3 p-3 mb-3" data-group="{{ $group->id }}">
                        @csrf
                        <div class="fw-semibold text-primary mb-3">{{ $student->name }}{{ $student->identifier_number ? ' · ' . $student->identifier_number : '' }}</div>
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
                        <button class="btn btn-primary btn-sm mt-2">Simpan Nilai</button>
                        @endif
                    </form>
                    @empty
                    <p class="text-muted">Tidak ada mahasiswa aktif pada kelompok ini.</p>
                    @endforelse
                </div>

                @if($isSupervisor)
                <div class="border-top pt-3 d-flex flex-wrap gap-2 align-items-center">
                    <button type="button" class="btn btn-{{ $group->acc_supervisor ? 'outline-danger' : 'success' }} btn-sm btn-final-approval" data-id="{{ $group->id }}" data-approved="{{ $group->acc_supervisor ? 1 : 0 }}">
                        {{ $group->acc_supervisor ? 'Cabut ACC Final Pembimbing' : 'ACC Final Pembimbing' }}
                    </button>
                    <span class="small text-muted">Status: {{ $group->supervisor_approved_by_role ?? 'Belum ACC' }}{{ $group->supervisorApprover ? ' — '.$group->supervisorApprover->name : '' }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>