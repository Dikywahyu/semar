@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .capstone-student-page {
        background-color: #f8fafc;
        color: #0f172a;
    }

    /* Hero Banner */
    .student-hero {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 55%, #4f46e5 100%);
        border-radius: 20px;
        padding: 1.75rem 2rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 12px 28px rgba(49, 46, 129, 0.15);
    }

    .student-hero::after {
        content: "";
        position: absolute;
        right: -30px;
        top: -30px;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(129, 140, 248, 0.35) 0%, transparent 70%);
        pointer-events: none;
    }

    /* Card Styling */
    .modern-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        box-shadow: 0 2px 12px rgba(15, 23, 42, 0.03);
    }

    /* Select2 Fix */
    .select2-hidden-accessible {
        display: none !important;
        visibility: hidden !important;
    }

    .select2-container--bootstrap-5 {
        width: 100% !important;
    }

    .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border-color: #cbd5e1;
        border-radius: 0.5rem;
    }

    .select2-container {
        z-index: 99999 !important;
    }

    /* Roadmap Stepper */
    .stepper-wrapper {
        display: flex;
        justify-content: space-between;
        position: relative;
    }

    .stepper-item {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
    }

    .stepper-item::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 3px;
        background-color: #e2e8f0;
        top: 16px;
        left: -50%;
        z-index: 1;
    }

    .stepper-item:first-child::before {
        content: none;
    }

    .stepper-item.completed::before {
        background-color: #10b981;
    }

    .stepper-item .step-counter {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background-color: #f1f5f9;
        border: 2px solid #cbd5e1;
        color: #64748b;
        display: flex;
        justify-content: center;
        align-items: center;
        font-weight: 700;
        font-size: 0.85rem;
        position: relative;
        z-index: 2;
        margin-bottom: 8px;
        transition: all 0.2s ease;
    }

    .stepper-item.completed .step-counter {
        background-color: #10b981;
        border-color: #10b981;
        color: white;
    }

    .stepper-item.active .step-counter {
        background-color: #4f46e5;
        border-color: #4f46e5;
        color: white;
        box-shadow: 0 0 0 5px rgba(79, 70, 229, 0.15);
    }

    .stepper-item .step-name {
        font-size: 0.78rem;
        color: #64748b;
        text-align: center;
        font-weight: 500;
    }

    .stepper-item.completed .step-name,
    .stepper-item.active .step-name {
        color: #0f172a;
        font-weight: 600;
    }

    /* Avatar Member */
    .member-avatar {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.8rem;
        flex-shrink: 0;
    }

    /* Modern Compact Document Tile */
    .doc-tile {
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        padding: 0.95rem;
        background: #ffffff;
        transition: all 0.2s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .doc-tile:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
    }

    .doc-tile.uploaded {
        border-color: #a7f3d0;
        background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
    }

    .doc-tile.missing {
        border-style: dashed;
        border-color: #cbd5e1;
        background: #f8fafc;
    }

    .doc-tile.optional {
        border-style: dashed;
        border-color: #bae6fd;
        background: #f0f9ff;
    }

    .doc-icon-box {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4 px-3 px-md-4 capstone-student-page min-vh-100">
    <!-- Hero Header -->
    <div class="student-hero text-white mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-25 rounded-pill px-3 py-1 mb-2">
                    <i class="ti ti-school me-1"></i> Portal Mahasiswa Capstone Design
                </span>
                <h3 class="fw-bold text-white mb-1">Dashboard Capstone Design</h3>
                <p class="text-white-50 small mb-0">
                    Kelola pendaftaran kelompok, usulan jadwal bimbingan/sidang, dan kelengkapan berkas akhir.
                    @if(auth()->user()->hasRole(['Super Admin', 'BAAK', 'KPS']))
                    <span class="badge bg-warning text-dark ms-1">Melihat: {{ $student->name ?? 'Mahasiswa' }}</span>
                    @endif
                </p>
            </div>
            @if(auth()->user()->hasRole(['Super Admin', 'BAAK', 'KPS']))
            <div>
                <a href="{{ route('capstone.mahasiswa.reset_profile') }}" class="btn btn-light btn-sm fw-semibold text-danger rounded-pill px-3 shadow-sm">
                    <i class="ti ti-refresh me-1"></i> Ganti Mahasiswa
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- Panel Undangan Bergabung Kelompok -->
    @if(isset($invitations) && $invitations->count() > 0)
    <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%); border-left: 5px solid #f59e0b !important;">
        <div class="card-body p-3 p-md-4">
            <h6 class="fw-bold text-warning-emphasis mb-3">
                <i class="ti ti-mail-forward me-2"></i> Undangan Bergabung Kelompok ({{ $invitations->count() }})
            </h6>
            <div class="d-flex flex-column gap-2">
                @foreach($invitations as $invitation)
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center bg-white p-3 rounded-3 border">
                    <div>
                        <h6 class="fw-bold mb-1">{{ optional($invitation->group)->title ?: 'Judul menunggu penetapan dosen pembimbing' }}</h6>
                        <p class="small text-muted mb-0">
                            <i class="ti ti-user me-1"></i> Ketua: <strong>{{ optional(optional($invitation->group)->leader)->name ?? 'Tidak diketahui' }}</strong> &nbsp;·&nbsp;
                            <i class="ti ti-briefcase me-1"></i> Bidang: <strong>{{ optional(optional($invitation->group)->expertise)->name ?? 'Belum ditentukan' }}</strong>
                        </p>
                    </div>
                    <div class="mt-2 mt-md-0 d-flex gap-2">
                        <button type="button" class="btn btn-success btn-sm rounded-pill px-3 btn-respond-invitation" data-id="{{ $invitation->id }}" data-status="accepted">
                            <i class="ti ti-check me-1"></i> Terima
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 btn-respond-invitation" data-id="{{ $invitation->id }}" data-status="rejected">
                            <i class="ti ti-x me-1"></i> Tolak
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Roadmap Progress Capstone -->
    <div class="modern-card p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <h6 class="fw-bold mb-0"><i class="ti ti-timeline me-2 text-primary"></i>Tahapan Capstone Design</h6>
            @php
            $hasGroup = isset($group) && $group;
            $hasExamDate = $hasGroup && !empty($group->exam_date);
            $isFinished = $hasGroup && $group->final_status === 'selesai';

            $step1Class = $hasGroup ? 'completed' : 'active';
            $step2Class = !$hasGroup ? '' : ($hasExamDate ? 'completed' : 'active');
            $step3Class = !$hasExamDate ? '' : ($isFinished ? 'completed' : 'active');
            $step4Class = $isFinished ? 'completed' : '';
            @endphp
            @if(!$hasGroup)
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1">Tahap 1: Bentuk Kelompok</span>
            @elseif(!$hasExamDate)
            <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-3 py-1">Tahap 2: Bimbingan & Proposal</span>
            @elseif(!$isFinished)
            <span class="badge bg-info-subtle text-info-emphasis rounded-pill px-3 py-1">Tahap 3: Sidang & Pemberkasan</span>
            @else
            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1">Tahap 4: Lulus / Selesai</span>
            @endif
        </div>

        <div class="stepper-wrapper my-3">
            <div class="stepper-item {{ $step1Class }}">
                <div class="step-counter">@if($hasGroup)<i class="ti ti-check"></i>@else 1 @endif</div>
                <div class="step-name">1. Pendaftaran Kelompok</div>
            </div>
            <div class="stepper-item {{ $step2Class }}">
                <div class="step-counter">@if($hasExamDate)<i class="ti ti-check"></i>@else 2 @endif</div>
                <div class="step-name">2. Bimbingan & Proposal</div>
            </div>
            <div class="stepper-item {{ $step3Class }}">
                <div class="step-counter">@if($isFinished)<i class="ti ti-check"></i>@else 3 @endif</div>
                <div class="step-name">3. Ujian / Sidang Akhir</div>
            </div>
            <div class="stepper-item {{ $step4Class }}">
                <div class="step-counter">@if($isFinished)<i class="ti ti-check"></i>@else 4 @endif</div>
                <div class="step-name">4. Selesai / Lulus</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- KOLOM KIRI: Informasi Kelompok, ACC, & Jadwal -->
        <div class="col-lg-5">
            @if(!$hasGroup)
            <div class="modern-card p-4 h-100">
                <h5 class="fw-bold mb-3"><i class="ti ti-users-plus me-2 text-primary"></i>Ajuan Kelompok Baru</h5>
                <form id="formPendaftaranCapstone">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Bidang Keahlian</label>
                        <select name="expertise_id" id="inputExpertise" class="select2-expertise w-100" required>
                            <option value="">-- Pilih Keahlian --</option>
                            @foreach($expertises ?? [] as $exp)
                            <option value="{{ $exp->id }}">{{ $exp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Anggota Kelompok (Maks. 2 teman)</label>
                        <select name="members[]" class="select2-members w-100" multiple data-placeholder="-- Cari mahasiswa anggota --">
                            @foreach($availableStudents ?? [] as $student)
                            <option value="{{ $student->id }}">{{ $student->name }}{{ $student->identifier_number ? ' (' . $student->identifier_number . ')' : '' }}</option>
                            @endforeach
                        </select>
                        <div class="form-text small text-muted mt-1">Anggota harus menerima undangan sebelum resmi bergabung.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-semibold rounded-3 py-2 mt-2">
                        <i class="ti ti-send me-1"></i> Kirim Ajuan Kelompok
                    </button>
                </form>
            </div>
            @else
            <div class="d-flex flex-column gap-4">
                <!-- Kartu Info Proyek & Anggota -->
                <div class="modern-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 fw-semibold">
                            <i class="ti ti-briefcase me-1"></i>{{ optional($group->expertise)->name ?? 'Bidang belum ditentukan' }}
                        </span>
                        <div class="d-flex gap-1">
                            @if(in_array($group->status_pendaftaran, ['draft', 'submitted']) && $group->kpi_approval === 'pending' && !$group->acc_coordinator && !$group->acc_supervisor && !$group->acc_kps && $group->members->where('status', 'accepted')->count() < 2)
                                <button type="button" class="btn btn-light btn-sm border rounded-pill px-2 text-warning-emphasis" data-bs-toggle="modal" data-bs-target="#modalEditGroup" title="Edit Ajuan">
                                <i class="ti ti-edit"></i> Edit
                                </button>
                                @endif
                                @if(in_array($group->status_pendaftaran, ['draft', 'submitted']) && $group->kpi_approval === 'pending' && !$group->acc_coordinator && !$group->acc_supervisor && !$group->acc_kps && !$group->members->contains('status', 'accepted'))
                                <button type="button" class="btn btn-light btn-sm border rounded-pill px-2 text-danger" id="btnDeleteGroup" data-id="{{ $group->id }}" title="Hapus Ajuan">
                                    <i class="ti ti-trash"></i>
                                </button>
                                @endif
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mb-1" style="line-height: 1.4;">
                        {{ $group->title ?: 'Menunggu penetapan judul oleh dosen pembimbing' }}
                    </h5>
                    <div class="small text-muted mb-3">
                        Pembimbing: <strong class="text-dark">{{ $group->supervisor?->nama_dosen ?? 'Belum diplot' }}</strong>
                    </div>

                    <div class="border-top pt-3">
                        <div class="small fw-bold text-secondary text-uppercase mb-2">Anggota Kelompok</div>
                        <div class="d-flex flex-column gap-2">
                            <!-- Ketua -->
                            <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="member-avatar bg-primary text-white">{{ strtoupper(substr($group->leader?->name ?? 'K', 0, 1)) }}</span>
                                    <div>
                                        <div class="small fw-bold text-dark">{{ $group->leader?->name ?? 'Ketua' }}</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">Ketua Kelompok</div>
                                    </div>
                                </div>
                                <span class="badge bg-primary rounded-pill">Ketua</span>
                            </div>

                            <!-- Anggota -->
                            @foreach($group->members as $member)
                            <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="member-avatar bg-secondary-subtle text-secondary">{{ strtoupper(substr($member->student?->name ?? 'A', 0, 1)) }}</span>
                                    <div>
                                        <div class="small fw-semibold text-dark">{{ $member->student?->name ?? 'Anggota' }}</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">Anggota</div>
                                    </div>
                                </div>
                                @if($member->status == 'accepted')
                                <span class="badge bg-success-subtle text-success rounded-pill"><i class="ti ti-check me-1"></i>ACC</span>
                                @elseif($member->status == 'rejected')
                                <span class="badge bg-danger-subtle text-danger rounded-pill">Ditolak</span>
                                @else
                                <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill">Menunggu</span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Kartu Status ACC & Usulan Jadwal -->
                <div class="modern-card p-4">
                    <h6 class="fw-bold mb-3"><i class="ti ti-shield-check me-2 text-success"></i>Status Persetujuan Sidang</h6>
                    <div class="row g-2 text-center mb-3">
                        <div class="col-4">
                            <div class="p-2 rounded-3 border {{ $group->acc_coordinator ? 'bg-success-subtle border-success-subtle' : 'bg-light' }}">
                                <div class="small text-muted mb-1">Koordinator</div>
                                <span class="badge rounded-pill {{ $group->acc_coordinator ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $group->acc_coordinator ? 'ACC' : 'Belum' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-3 border {{ $group->acc_supervisor ? 'bg-success-subtle border-success-subtle' : 'bg-light' }}">
                                <div class="small text-muted mb-1">Pembimbing</div>
                                <span class="badge rounded-pill {{ $group->acc_supervisor ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $group->acc_supervisor ? 'ACC' : 'Belum' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-3 border {{ $group->acc_kps ? 'bg-success-subtle border-success-subtle' : 'bg-light' }}">
                                <div class="small text-muted mb-1">KPS</div>
                                <span class="badge rounded-pill {{ $group->acc_kps ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $group->acc_kps ? 'ACC' : 'Belum' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <form id="formProposeSchedule" class="border-top pt-3">
                        @csrf
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold small text-uppercase text-secondary mb-0">Usulan Jadwal Ujian</h6>
                            @if($group->exam_date && $group->exam_room)
                            <span class="badge bg-success rounded-pill"><i class="ti ti-calendar-check me-1"></i>Resmi: {{ $group->exam_date }} ({{ $group->exam_room }})</span>
                            @endif
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <input type="date" name="exam_date" class="form-control form-control-sm" min="{{ now()->addDay()->toDateString() }}" value="{{ $group->proposed_exam_date }}" required>
                            </div>
                            <div class="col-6">
                                <input type="time" name="exam_time" class="form-control form-control-sm" value="{{ $group->proposed_exam_time }}" required>
                            </div>
                        </div>
                        <button class="btn btn-outline-primary btn-sm w-100 rounded-3" type="submit">
                            <i class="ti ti-calendar-plus me-1"></i> Simpan Usulan Jadwal
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>

        <!-- KOLOM KANAN: Grid Berkas Capstone (2 Kolom Modern + Auto Upload) -->
        <div class="col-lg-7">
            <div class="modern-card p-4 h-100">
                @php
                $documentChecklist = [
                'consultation_card' => [
                'label' => 'Kartu Konsultasi',
                'format' => 'PDF / JPG / PNG',
                'accept' => '.pdf,.jpg,.jpeg,.png',
                'icon' => 'ti-notes',
                'color' => 'primary',
                'optional' => false,
                'file' => $group->consultation_card_file ?? null,
                ],
                'draft_report' => [
                'label' => 'Draft Proposal',
                'format' => 'PDF',
                'accept' => '.pdf',
                'icon' => 'ti-file-text',
                'color' => 'indigo',
                'optional' => false,
                'file' => $group->draft_report_file ?? null,
                ],
                'berita_acara' => [
                'label' => 'Berita Acara Ujian',
                'format' => 'PDF / JPG / PNG',
                'accept' => '.pdf,.jpg,.jpeg,.png',
                'icon' => 'ti-file-certificate',
                'color' => 'warning',
                'optional' => false,
                'file' => $group->berita_acara_file ?? null,
                ],
                'lembar_revisi' => [
                'label' => 'Lembar Revisi',
                'format' => 'PDF',
                'accept' => '.pdf',
                'icon' => 'ti-edit-circle',
                'color' => 'danger',
                'optional' => false,
                'file' => $group->lembar_revisi_file ?? null,
                ],
                'final_report' => [
                'label' => 'Laporan Final Capstone',
                'format' => 'PDF',
                'accept' => '.pdf',
                'icon' => 'ti-book',
                'color' => 'success',
                'optional' => false,
                'file' => $group->final_report_file ?? null,
                ],
                'agreement' => [
                'label' => 'Kesepakatan Bersama',
                'format' => 'PDF',
                'accept' => '.pdf',
                'icon' => 'ti-writing-sign',
                'color' => 'primary',
                'optional' => false,
                'file' => $group->agreement_file ?? null,
                ],
                'drawings' => [
                'label' => 'Gambar Kerja / Desain',
                'format' => 'PDF / DWG / DXF / ZIP',
                'accept' => '.pdf,.dwg,.dxf,.zip',
                'icon' => 'ti-ruler-measure',
                'color' => 'info',
                'optional' => false,
                'file' => $group->drawings_file ?? null,
                ],
                'rab' => [
                'label' => 'Rencana Anggaran (RAB)',
                'format' => 'PDF / XLSX / CSV',
                'accept' => '.pdf,.xlsx,.xls,.csv',
                'icon' => 'ti-calculator',
                'color' => 'success',
                'optional' => false,
                'file' => $group->rab_file ?? null,
                ],
                'recap_score' => [
                'label' => 'Rekapitulasi Nilai',
                'format' => 'PDF',
                'accept' => '.pdf',
                'icon' => 'ti-award',
                'color' => 'warning',
                'optional' => false,
                'file' => $group->recap_score_file ?? null,
                ],
                'structural_calculation' => [
                'label' => 'Perhitungan Struktural',
                'format' => 'PDF / XLSX / ZIP',
                'accept' => '.pdf,.xlsx,.xls,.csv,.zip',
                'icon' => 'ti-building-bridge',
                'color' => 'info',
                'optional' => true,
                'file' => $group->structural_calculation_file ?? null,
                ],
                ];

                $mandatoryDocs = collect($documentChecklist)->where('optional', false);
                $uploadedMandatoryCount = $mandatoryDocs->where('file', '!=', null)->count();
                $totalMandatoryCount = $mandatoryDocs->count();
                $progressPercent = $totalMandatoryCount > 0 ? round(($uploadedMandatoryCount / $totalMandatoryCount) * 100) : 0;
                @endphp

                <!-- Header & Progress Bar Kelengkapan -->
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div>
                        <h5 class="fw-bold mb-0"><i class="ti ti-folders me-2 text-primary"></i>Berkas Persyaratan Capstone</h5>
                        <p class="small text-muted mb-0">Klik tombol unggah pada masing-masing kartu; file langsung tersimpan otomatis.</p>
                    </div>
                    @if($hasGroup)
                    <span class="badge {{ $uploadedMandatoryCount === $totalMandatoryCount ? 'bg-success' : 'bg-primary' }} rounded-pill px-3 py-2">
                        {{ $uploadedMandatoryCount }} / {{ $totalMandatoryCount }} Wajib ({{ $progressPercent }}%)
                    </span>
                    @endif
                </div>

                @if($hasGroup)
                <div class="progress mb-4" style="height: 6px; border-radius: 999px;">
                    <div class="progress-bar {{ $progressPercent == 100 ? 'bg-success' : 'bg-primary' }}" role="progressbar" style="width: {{ $progressPercent }}%;"></div>
                </div>
                @endif

                @if(!$hasGroup)
                <div class="alert alert-warning rounded-3 small mb-0">
                    <i class="ti ti-alert-triangle me-1"></i> Daftarkan kelompok terlebih dahulu di sebelah kiri untuk membuka slot unggah berkas.
                </div>
                @else
                <div class="row g-3">
                    @foreach($documentChecklist as $type => $doc)
                    @php
                    $isUploaded = !empty($doc['file']);
                    $tileClass = $isUploaded ? 'uploaded' : ($doc['optional'] ? 'optional' : 'missing');
                    @endphp
                    <div class="col-md-6">
                        <div class="doc-tile {{ $tileClass }}">
                            <div>
                                <!-- Atas: Ikon + Judul + Status Pill -->
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="doc-icon-box {{ $isUploaded ? 'bg-success text-white' : 'bg-'.$doc['color'].'-subtle text-'.$doc['color'] }}">
                                            <i class="ti {{ $isUploaded ? 'ti-check' : $doc['icon'] }}"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark small" style="line-height: 1.25;">{{ $doc['label'] }}</div>
                                            <span class="text-muted" style="font-size: 0.7rem;">{{ $doc['format'] }}</span>
                                        </div>
                                    </div>

                                    @if($isUploaded)
                                    <span class="badge bg-success-subtle text-success rounded-pill" style="font-size: 0.68rem;">Sudah</span>
                                    @elseif($doc['optional'])
                                    <span class="badge bg-info-subtle text-info rounded-pill" style="font-size: 0.68rem;">Opsional</span>
                                    @else
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill" style="font-size: 0.68rem;">Belum</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Bawah: Tombol Aksi Bersih (Auto Upload saat file dipilih) -->
                            <div class="pt-2 mt-2 border-top border-secondary border-opacity-10 d-flex align-items-center justify-content-between gap-1">
                                <input type="file" id="fileInput-{{ $type }}" class="d-none auto-upload-input" data-type="{{ $type }}" accept="{{ $doc['accept'] }}">

                                @if($isUploaded)
                                <div class="d-flex gap-1">
                                    <a href="{{ route('capstone.file.view', ['path' => $doc['file']]) }}" target="_blank" class="btn btn-sm btn-info text-white rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                        <i class="ti ti-eye me-1"></i>Lihat
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1 btn-delete-file" data-type="{{ $type }}" title="Hapus Berkas" style="font-size: 0.75rem;">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                                <label for="fileInput-{{ $type }}" class="btn btn-sm btn-outline-success rounded-pill px-2 py-1 mb-0 cursor-pointer fw-semibold" style="font-size: 0.75rem;">
                                    <i class="ti ti-refresh me-1"></i>Ganti
                                </label>
                                @else
                                <span class="text-muted" style="font-size: 0.72rem;">Belum ada file</span>
                                <label for="fileInput-{{ $type }}" class="btn btn-sm btn-primary rounded-pill px-3 py-1 mb-0 cursor-pointer fw-semibold" style="font-size: 0.75rem;">
                                    <i class="ti ti-upload me-1"></i>Unggah
                                </label>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($hasGroup)
<!-- Modal Edit Ajuan Kelompok -->
<div class="modal fade" id="modalEditGroup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Ajuan Kelompok</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="formEditPendaftaranCapstone">
                @csrf
                <input type="hidden" name="group_id" value="{{ $group->id }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pilih Bidang Keahlian</label>
                        <select name="expertise_id" id="inputExpertiseModal" class="select2-expertise-modal w-100" required>
                            <option value="">-- Pilih Keahlian --</option>
                            @foreach($expertises ?? [] as $exp)
                            <option value="{{ $exp->id }}" {{ $group->expertise_id == $exp->id ? 'selected' : '' }}>
                                {{ $exp->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Ubah Anggota Kelompok (maksimal 2 anggota tambahan)</label>
                        <select name="members[]" class="select2-members-modal w-100" multiple data-placeholder="-- Cari mahasiswa anggota --">
                            @php
                            $selectedMemberIds = $group->members
                            ? $group->members->whereIn('status', ['accepted', 'pending'])->pluck('student_id')->toArray()
                            : [];
                            @endphp
                            @foreach($availableStudents ?? [] as $student)
                            <option value="{{ $student->id }}" {{ in_array($student->id, $selectedMemberIds) ? 'selected' : '' }}>
                                {{ $student->name }}{{ $student->identifier_number ? ' (' . $student->identifier_number . ')' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Auto-Upload langsung saat mahasiswa memilih file dari komputer
        $(document).on('change', '.auto-upload-input', function() {
            let input = this;
            if (!input.files || !input.files[0]) return;

            let fileType = $(this).data('type');
            let groupId = "{{ $group->id ?? '' }}";

            if (!groupId) {
                Swal.fire('Perhatian', 'Anda harus terdaftar dalam kelompok terlebih dahulu!', 'warning');
                return;
            }

            let formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('file_type', fileType);
            formData.append('document_file', input.files[0]);

            Swal.fire({
                title: 'Mengunggah Berkas...',
                text: 'Mohon tunggu sebentar',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "{{ url('capstone/mahasiswa/upload-sidang') }}/" + groupId,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(res) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1400,
                        showConfirmButton: false
                    }).then(() => location.reload());
                },
                error: function(err) {
                    $(input).val(''); // Reset input file jika gagal
                    Swal.fire('Gagal', err.responseJSON?.message || 'Terjadi kesalahan saat mengunggah berkas.', 'error');
                }
            });
        });

        // Proses Hapus Berkas
        $('.btn-delete-file').on('click', function() {
            let fileType = $(this).data('type');
            let groupId = "{{ $group->id ?? '' }}";

            Swal.fire({
                title: 'Hapus Berkas?',
                text: "Berkas yang dihapus akan hilang dari sistem!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('capstone/mahasiswa/delete-sidang') }}/" + groupId + "/" + fileType,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Terhapus!',
                                text: res.message,
                                timer: 1400,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        },
                        error: function(err) {
                            Swal.fire('Gagal', err.responseJSON?.message || 'Terjadi kesalahan saat menghapus berkas.', 'error');
                        }
                    });
                }
            });
        });

        // Proses Merespon Undangan Kelompok (Terima / Tolak)
        $('.btn-respond-invitation').on('click', function() {
            let invitationId = $(this).data('id');
            let status = $(this).data('status');
            let actionText = status === 'accepted' ? 'menerima' : 'menolak';
            let confirmColor = status === 'accepted' ? '#198754' : '#dc3545';

            Swal.fire({
                title: 'Konfirmasi Undangan',
                text: `Apakah Anda yakin ingin ${actionText} undangan ini?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Ya, ${status === 'accepted' ? 'Terima' : 'Tolak'}`,
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('capstone/mahasiswa/invitation') }}/" + invitationId,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            status: status
                        },
                        success: function(res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: res.message,
                                timer: 1400,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        },
                        error: function(err) {
                            Swal.fire('Gagal', err.responseJSON?.message || 'Terjadi kesalahan sistem.', 'error');
                        }
                    });
                }
            });
        });

        // Inisialisasi Select2
        if ($('.select2-members').length) {
            $('.select2-members').select2({
                theme: 'bootstrap-5',
                maximumSelectionLength: 2,
                width: '100%'
            });
        }
        if ($('.select2-expertise').length) {
            $('.select2-expertise').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        }
        if ($('#modalEditGroup').length) {
            $('.select2-expertise-modal').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $('#modalEditGroup')
            });
            $('.select2-members-modal').select2({
                theme: 'bootstrap-5',
                maximumSelectionLength: 2,
                width: '100%',
                dropdownParent: $('#modalEditGroup')
            });
        }
    });

    $(document).on('submit', '#formPendaftaranCapstone', function(e) {
        e.preventDefault();
        submitGroupData($(this).serialize());
    });

    $(document).on('submit', '#formEditPendaftaranCapstone', function(e) {
        e.preventDefault();
        submitGroupData($(this).serialize());
    });

    $(document).on('submit', '#formProposeSchedule', function(e) {
        e.preventDefault();
        $.ajax({
            url: "{{ route('capstone.mahasiswa.schedule.propose', ['groupId' => $group->id ?? 0]) }}",
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                Swal.fire('Berhasil', res.message, 'success').then(() => location.reload());
            },
            error: function(err) {
                Swal.fire('Gagal', err.responseJSON?.message || 'Usulan jadwal gagal disimpan.', 'error');
            }
        });
    });

    $(document).on('click', '#btnDeleteGroup', function() {
        const groupId = $(this).data('id');
        Swal.fire({
            title: 'Hapus ajuan Capstone?',
            text: 'Penghapusan hanya tersedia selama ajuan masih menunggu proses.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ url('capstone/mahasiswa/group') }}/" + groupId,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    Swal.fire('Berhasil', res.message, 'success').then(() => location.reload());
                },
                error: function(err) {
                    Swal.fire('Gagal', err.responseJSON?.message || 'Ajuan tidak dapat dihapus.', 'error');
                }
            });
        });
    });

    function submitGroupData(formData) {
        $.ajax({
            url: "{{ route('capstone.mahasiswa.group.store') }}",
            type: 'POST',
            data: formData,
            success: function(res) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 1400,
                    showConfirmButton: false
                }).then(() => location.reload());
            },
            error: function(err) {
                Swal.fire('Gagal', err.responseJSON?.message || 'Terjadi kesalahan', 'error');
            }
        });
    }
</script>
@endpush
@endsection