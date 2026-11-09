@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h3 class="fw-bold">Tugas Akhir — Sempro, Semhas, Sidang TA</h3>
    <p class="text-muted">Semester aktif: {{ $semester?->nama ?? 'Belum diatur' }}</p>

    @if(!$semester)
    <div class="alert alert-warning">Pengajuan belum dapat dilakukan karena semester aktif belum ditetapkan.</div>
    @elseif(!$application)
    <div class="card border-0 shadow-sm rounded-4 p-4">
        <h5>Pengajuan Seminar Proposal</h5>
        <form id="proposalForm" class="row g-3">
            @csrf
            <div class="col-md-5">
                <label class="form-label">Tema Keahlian</label>
                <input class="form-control" name="expertise_theme" maxlength="255" required>
            </div>
            <div class="col-md-5">
                <label class="form-label">Dosen Pembimbing 1 (kuota maksimal 10)</label>
                <select class="form-select" name="p1_id" required>
                    <option value="">Pilih dosen</option>
                    @foreach($activeP1 as $dosen)
                    <option value="{{ $dosen->id_dosen }}">{{ $dosen->nama_dosen }} — {{ $dosen->active_thesis_count }}/10</option>
                    @endforeach
                </select>
                @if($activeP1->isEmpty())<small class="text-danger">Semua dosen telah mencapai kuota.</small>@endif
            </div>
            <div class="col-12"><button class="btn btn-primary" @disabled($activeP1->isEmpty())>Ajukan Sempro</button></div>
        </form>
    </div>
    @else
    @php
        $documentLabels = [
            'sempro_consultation' => 'Kartu Konsultasi Sempro',
            'sempro_agreement' => 'Kesepakatan Bersama Sempro',
            'sempro_final_draft' => 'Draft Final Sempro',
            'capstone_completion' => 'Screenshot Selesai Capstone Design',
            'sempro_completion' => 'Screenshot Selesai Sempro',
            'semhas_draft' => 'Draft Semhas (Bab 1-5)',
            'semhas_attendance' => 'Daftar Hadir Semhas',
            'semhas_result' => 'Screenshot Hasil Semhas',
            'journal_submission' => 'Bukti Submit Jurnal / Prosiding',
            'ta_final_draft' => 'Draft Final Tugas Akhir (PDF)',
            'ta_revised_final' => 'Tugas Akhir Final Terevisi',
        ];
        $uploaded = $application->documents->keyBy('document_type');
    @endphp
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-2">
            <div>
                <h5 class="mb-1">{{ $application->expertise_theme }}</h5>
                <span class="badge bg-primary">{{ str_replace('_', ' ', ucfirst($application->status)) }}</span>
            </div>
            <div class="small text-end">
                <div>P1: {{ $application->p1?->nama_dosen ?? 'Belum ditentukan' }}</div>
                <div>P2: {{ $application->p2?->nama_dosen ?? 'Belum ditentukan' }}</div>
                <div>Penguji: {{ $application->examiner?->nama_dosen ?? 'Belum ditentukan' }}</div>
            </div>
        </div>
        <hr>
        <div class="row g-3">
            <div class="col-md-4">
                <strong>Kartu konsultasi Sempro</strong><br>
                {{ $application->sempro_consultation_approved ? '✅ Disetujui' : '⏳ Menunggu ACC Kaprodi' }}
                @if($application->sempro_consultation_approved)
                <div class="small text-muted">ACC oleh {{ $application->semproConsultationApprover?->name ?? 'akun dihapus' }} ({{ $application->sempro_consultation_approved_by_role }}) — {{ $application->sempro_consultation_approved_at?->format('d-m-Y H:i') }}</div>
                @endif
            </div>
            <div class="col-md-4">
                <strong>Persyaratan Semhas</strong><br>
                Koordinator {{ $application->semhas_coordinator_verified ? '✅' : '⏳' }} · Kaprodi {{ $application->semhas_kps_verified ? '✅' : '⏳' }}
                @if($application->semhas_coordinator_verified)
                <div class="small text-muted">Koordinator: {{ $application->semhasCoordinatorVerifier?->name ?? 'akun dihapus' }} ({{ $application->semhas_coordinator_verified_by_role }})</div>
                @endif
                @if($application->semhas_kps_verified)
                <div class="small text-muted">Kaprodi: {{ $application->semhasKpsVerifier?->name ?? 'akun dihapus' }} ({{ $application->semhas_kps_verified_by_role }})</div>
                @endif
            </div>
            <div class="col-md-4">
                <strong>Persyaratan Sidang TA</strong><br>
                Koordinator {{ $application->ta_coordinator_verified ? '✅' : '⏳' }} · Kaprodi {{ $application->ta_kps_verified ? '✅' : '⏳' }} · Draft {{ $application->ta_draft_verified ? '✅' : '⏳' }}
                @if($application->ta_coordinator_verified)
                <div class="small text-muted">Koordinator: {{ $application->taCoordinatorVerifier?->name ?? 'akun dihapus' }} ({{ $application->ta_coordinator_verified_by_role }})</div>
                @endif
                @if($application->ta_kps_verified)
                <div class="small text-muted">Kaprodi: {{ $application->taKpsVerifier?->name ?? 'akun dihapus' }} ({{ $application->ta_kps_verified_by_role }})</div>
                @endif
                @if($application->ta_draft_verified)
                <div class="small text-muted">Draft diverifikasi {{ $application->taDraftVerifier?->name ?? 'akun dihapus' }} ({{ $application->ta_draft_verified_by_role }})</div>
                @endif
            </div>
        </div>
        @if($application->sempro_exam_date)
        <div class="alert alert-info mt-3 mb-0">Jadwal Sempro: {{ $application->sempro_exam_date }} {{ $application->sempro_exam_time }} — {{ $application->sempro_exam_room }}</div>
        @endif
        @if($application->ta_exam_date)
        <div class="alert alert-info mt-3 mb-0">Jadwal Sidang TA: {{ $application->ta_exam_date }} {{ $application->ta_exam_time }} — {{ $application->ta_exam_room }}</div>
        @elseif($application->ta_proposed_date)
        <div class="alert alert-warning mt-3 mb-0">Usulan Sidang TA: {{ $application->ta_proposed_date }} {{ $application->ta_proposed_time }} — menunggu Admin menetapkan ruangan.</div>
        @endif
        @if($application->sempro_approved_at)
        <p class="text-success mt-3 mb-0">Sempro selesai — ACC {{ $application->semproApprover?->name ?? 'akun dihapus' }} ({{ $application->sempro_approved_by_role }}) pada {{ $application->sempro_approved_at->format('d-m-Y H:i') }}.</p>
        @endif
        @if($application->semhas_finished_at)<p class="text-success mt-2 mb-0">Semhas selesai — {{ $application->semhasApprover?->name ?? 'akun dihapus' }} ({{ $application->semhas_finished_by_role }}) pada {{ $application->semhas_finished_at->format('d-m-Y H:i') }}.</p>@endif
        @if($application->ta_final_approved_at)
        <p class="text-success mt-2 mb-0">Tugas Akhir selesai — ACC {{ $application->taFinalApprover?->name ?? 'akun dihapus' }} ({{ $application->ta_final_approved_by_role }}) pada {{ $application->ta_final_approved_at->format('d-m-Y H:i') }}.</p>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5>Unggah Dokumen</h5>
                <form id="documentForm" enctype="multipart/form-data">
                    @csrf
                    <label class="form-label">Jenis dokumen</label>
                    <select class="form-select mb-3" name="document_type" required>
                        <option value="">Pilih dokumen</option>
                        @foreach($documentLabels as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="file" name="document" class="form-control mb-3" accept=".pdf,.jpg,.jpeg,.png" required>
                    <button class="btn btn-primary">Unggah</button>
                </form>
                <hr>
                <h6>Usulan Jadwal Sempro</h6>
                <form id="scheduleForm">
                    @csrf
                    <input type="date" name="sempro_proposed_date" class="form-control mb-2" min="{{ now()->addDay()->toDateString() }}" required>
                    <input type="time" name="sempro_proposed_time" class="form-control mb-2" required>
                    <button class="btn btn-outline-primary btn-sm">Kirim / Revisi Jadwal</button>
                </form>
                @if($application->semhas_finished_at && $application->ta_draft_verified)
                <hr>
                <h6>Usulan Jadwal Sidang Tugas Akhir</h6>
                <form id="taScheduleForm">
                    @csrf
                    <input type="date" name="ta_proposed_date" class="form-control mb-2" min="{{ now()->addDay()->toDateString() }}" value="{{ $application->ta_proposed_date }}" required>
                    <input type="time" name="ta_proposed_time" class="form-control mb-2" value="{{ $application->ta_proposed_time ? substr((string)$application->ta_proposed_time, 0, 5) : '' }}" required>
                    <button class="btn btn-outline-primary btn-sm">Kirim / Revisi Jadwal Sidang</button>
                </form>
                @endif
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5>Status Dokumen</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Dokumen</th><th>Status</th><th>Catatan Verifikasi</th><th>File</th></tr></thead>
                        <tbody>
                        @foreach($documentLabels as $key => $label)
                            @php($doc = $uploaded->get($key))
                            <tr>
                                <td>{{ $label }}</td>
                                <td>{{ $doc ? ucfirst($doc->verification_status) : 'Belum diunggah' }}</td>
                                <td>{{ $doc?->verification_note ?? '-' }}</td>
                                <td>@if($doc)<a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank" rel="noopener">Lihat</a>@else—@endif</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function taRequest(options) {
        $.ajax(options).done(res => Swal.fire('Berhasil', res.message, 'success').then(() => location.reload()))
            .fail(err => Swal.fire('Gagal', err.responseJSON?.message || 'Permintaan gagal diproses.', 'error'));
    }
    $('#proposalForm').on('submit', function(e) {
        e.preventDefault();
        taRequest({url: "{{ route('thesis.proposal.store') }}", type: 'POST', data: $(this).serialize()});
    });
    @if($application)
    $('#documentForm').on('submit', function(e) {
        e.preventDefault();
        taRequest({url: "{{ route('thesis.documents.upload', $application->id) }}", type: 'POST', data: new FormData(this), processData: false, contentType: false});
    });
    $('#scheduleForm').on('submit', function(e) {
        e.preventDefault();
        taRequest({url: "{{ route('thesis.sempro.schedule.propose', $application->id) }}", type: 'POST', data: $(this).serialize()});
    });
    $('#taScheduleForm').on('submit', function(e) {
        e.preventDefault();
        taRequest({url: "{{ route('thesis.ta.schedule.propose', $application->id) }}", type: 'POST', data: $(this).serialize()});
    });
    @endif
</script>
@endpush
@endsection
