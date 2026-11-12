@extends('layouts.app')

@push('styles')
<!-- Select2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    /* Paksa sembunyikan select asli agar tidak bocor ke tampilan */
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
        border-color: #dfe5ef;
        border-radius: 0.375rem;
    }

    .select2-container--bootstrap-5 .select2-selection--multiple {
        padding-top: 0.2rem;
        padding-bottom: 0.2rem;
    }

    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        width: 100%;
        margin: 0;
        padding: 0;
    }

    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
        max-width: 100%;
        overflow-wrap: anywhere;
        white-space: normal;
    }

    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__display {
        white-space: normal;
    }

    /* Memastikan dropdown Select2 muncul di atas modal Bootstrap */
    .select2-container {
        z-index: 99999 !important;
    }

    /* Roadmap Stepper */
    .stepper-wrapper {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1.5rem;
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
        background-color: #e9ecef;
        top: 15px;
        left: -50%;
        z-index: 1;
    }

    .stepper-item:first-child::before {
        content: none;
    }

    .stepper-item .step-counter {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #e9ecef;
        color: #6c757d;
        display: flex;
        justify-content: center;
        align-items: center;
        font-weight: 600;
        font-size: 0.875rem;
        position: relative;
        z-index: 2;
        margin-bottom: 6px;
    }

    .stepper-item.completed .step-counter {
        background-color: #198754;
        color: white;
    }

    .stepper-item.active .step-counter {
        background-color: #0d6efd;
        color: white;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.15);
    }

    .stepper-item .step-name {
        font-size: 0.75rem;
        color: #6c757d;
        text-align: center;
        font-weight: 500;
    }

    .stepper-item.completed .step-name,
    .stepper-item.active .step-name {
        color: #212529;
        font-weight: 600;
    }

    /* Styling Kartu Checklist Berkas Individual */
    .doc-item-card {
        border: 1px solid #e2e8f0;
        border-left-width: 4px;
        border-radius: 12px;
        transition: all 0.2s ease;
        background-color: #ffffff;
    }

    .doc-item-card:hover {
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
    }

    .doc-item-card.is-uploaded {
        border-left-color: #198754;
        background-color: #f8fdf9;
    }

    .doc-item-card.is-missing {
        border-left-color: #cbd5e1;
    }

    .doc-item-card.is-optional {
        border-left-color: #0dcaf0;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Header Page -->
    <div class="mb-4 pt-2 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h3 class="fw-bold text-slate-800 mb-1 fs-5 fs-md-4">Dashboard Capstone Design (Mahasiswa)</h3>
            <p class="text-secondary small mb-0">
                Kelola pendaftaran kelompok dan bidang keahlian, bimbingan, serta unggah berkas sidang akhir. Judul ditetapkan oleh dosen pembimbing.
                @if(auth()->user()->hasRole(['Super Admin', 'BAAK', 'KPS']))
                <br><span class="text-primary fw-semibold"><i class="ti ti-user-check"></i> Sedang melihat data: {{ $student->name ?? 'Mahasiswa' }}</span>
                @endif
            </p>
        </div>

        <!-- Tombol Pilih Ulang Mahasiswa Khusus Admin -->
        @if(auth()->user()->hasRole(['Super Admin', 'BAAK', 'KPS']))
        <div>
            <a href="{{ route('capstone.mahasiswa.reset_profile') }}" class="btn btn-outline-danger btn-sm fw-semibold shadow-sm">
                <i class="ti ti-refresh me-1"></i> Pilih Ulang Mahasiswa
            </a>
        </div>
        @endif
    </div>

    <!-- Panel Undangan Bergabung Kelompok -->
    @if(isset($invitations) && $invitations->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4" style="background-color: #fdf3d8;">
                <div class="card-body p-3 p-md-4">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="ti ti-mail-forward me-2 text-warning"></i> Undangan Bergabung Kelompok ({{ $invitations->count() }})
                    </h5>

                    <div class="d-flex flex-column gap-3">
                        @foreach($invitations as $invitation)
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center bg-white p-3 rounded-3 shadow-sm border-start border-warning border-4">
                            <div>
                                <h6 class="fw-bold mb-1">{{ optional($invitation->group)->title ?: 'Judul menunggu penetapan dosen pembimbing' }}</h6>
                                <p class="small text-muted mb-0">
                                    <i class="ti ti-user me-1"></i> Ketua: <strong>{{ optional(optional($invitation->group)->leader)->name ?? 'Tidak diketahui' }}</strong> &nbsp;|&nbsp;
                                    <i class="ti ti-briefcase me-1"></i> Bidang: <strong>{{ optional(optional($invitation->group)->expertise)->name ?? 'Belum ditentukan' }}</strong>
                                </p>
                            </div>
                            <div class="mt-3 mt-md-0 d-flex gap-2">
                                <button type="button" class="btn btn-success btn-sm fw-semibold px-3 btn-respond-invitation" data-id="{{ $invitation->id }}" data-status="accepted">
                                    <i class="ti ti-check me-1"></i> Terima
                                </button>
                                <button type="button" class="btn btn-danger btn-sm fw-semibold px-3 btn-respond-invitation" data-id="{{ $invitation->id }}" data-status="rejected">
                                    <i class="ti ti-x me-1"></i> Tolak
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>

                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Roadmap Progress Capstone -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <h5 class="fw-bold mb-3 fs-6 fs-md-5"><i class="ti ti-timeline me-2 text-primary"></i>Tahapan (Roadmap) Capstone Design</h5>

        @php
        $hasGroup = isset($group) && $group;
        $hasExamDate = $hasGroup && !empty($group->exam_date);
        $isFinished = $hasGroup && $group->final_status === 'selesai';

        $step1Class = $hasGroup ? 'completed' : 'active';
        $step2Class = !$hasGroup ? '' : ($hasExamDate ? 'completed' : 'active');
        $step3Class = !$hasExamDate ? '' : ($isFinished ? 'completed' : 'active');
        $step4Class = $isFinished ? 'completed' : '';
        @endphp

        <div class="stepper-wrapper">
            <div class="stepper-item {{ $step1Class }}">
                <div class="step-counter"><i class="ti ti-check"></i></div>
                <div class="step-name">1. Pendaftaran Kelompok</div>
            </div>
            <div class="stepper-item {{ $step2Class }}">
                <div class="step-counter">2</div>
                <div class="step-name">2. Bimbingan & Proposal</div>
            </div>
            <div class="stepper-item {{ $step3Class }}">
                <div class="step-counter">3</div>
                <div class="step-name">3. Ujian / Sidang Akhir</div>
            </div>
            <div class="stepper-item {{ $step4Class }}">
                <div class="step-counter">4</div>
                <div class="step-name">4. Selesai / Lulus</div>
            </div>
        </div>

        <div class="alert alert-info py-2 px-3 small mb-0 d-flex align-items-center">
            <i class="ti ti-info-circle fs-5 me-2"></i>
            <div>
                Status saat ini:
                @if(!$hasGroup)
                <strong class="text-primary">Silakan bentuk kelompok dan pilih bidang keahlian. Judul akan dibuat dosen pembimbing setelah plotting.</strong>
                @elseif(!$hasExamDate)
                <strong class="text-warning">Kelompok aktif. Lakukan bimbingan dan unggah draft laporan/kartu konsultasi.</strong>
                @elseif(!$isFinished)
                <strong class="text-info">Tahap Ujian/Sidang berlangsung. Lengkapi berkas dan tunggu persetujuan (ACC) multi-pihak.</strong>
                @else
                <strong class="text-success">Selamat! Seluruh tahapan Capstone Design telah selesai dan disetujui.</strong>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Kolom Kiri: Form Pengajuan (Belum Ada) / Kartu Informasi Kelompok (Sudah Ada) -->
        <div class="col-lg-5">
            @if(!$hasGroup)
            <!-- FORM PENDAFTARAN KELOMPOK BARU -->
            <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white h-100">
                <h5 class="fw-bold mb-3 fs-6 fs-md-5"><i class="ti ti-file-text me-2"></i>Ajuan Kelompok & Bidang Keahlian</h5>
                <form id="formPendaftaranCapstone">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pilih Bidang Keahlian</label>
                        <select name="expertise_id" id="inputExpertise" class="select2-expertise w-100" required>
                            <option value="">-- Pilih Keahlian --</option>
                            @foreach($expertises ?? [] as $exp)
                            <option value="{{ $exp->id }}">{{ $exp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tambah Anggota Kelompok (maksimal 3 mahasiswa termasuk ketua)</label>
                        <select name="members[]" class="select2-members w-100" multiple data-placeholder="-- Cari mahasiswa anggota --">
                            @foreach($availableStudents ?? [] as $student)
                            <option value="{{ $student->id }}">{{ $student->name }}{{ $student->identifier_number ? ' (' . $student->identifier_number . ')' : '' }}</option>
                            @endforeach
                        </select>
                        <div class="form-text small text-muted mt-1">Pilih maksimal 2 teman. Anggota harus menyetujui undangan sebelum resmi bergabung.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-semibold mt-2"><i class="ti ti-device-floppy me-1"></i> Simpan Ajuan Kelompok</button>
                </form>
            </div>
            @else
            <!-- KARTU INFORMASI KELOMPOK (SUDAH MENGAJUKAN) -->
            <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0 fs-6 fs-md-5"><i class="ti ti-users me-2 text-primary"></i>Informasi Kelompok Anda</h5>
                    <div class="d-flex gap-2">
                        @if(in_array($group->status_pendaftaran, ['draft', 'submitted']) && $group->kpi_approval === 'pending' && !$group->acc_coordinator && !$group->acc_supervisor && !$group->acc_kps && $group->members->where('status', 'accepted')->count() < 2)
                            <button type="button" class="btn btn-outline-warning btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalEditGroup">
                            <i class="ti ti-edit me-1"></i> Edit Ajuan
                            </button>
                            @endif
                            @if(in_array($group->status_pendaftaran, ['draft', 'submitted']) && $group->kpi_approval === 'pending' && !$group->acc_coordinator && !$group->acc_supervisor && !$group->acc_kps && !$group->members->contains('status', 'accepted'))
                            <button type="button" class="btn btn-outline-danger btn-sm fw-semibold" id="btnDeleteGroup" data-id="{{ $group->id }}">
                                <i class="ti ti-trash me-1"></i> Hapus Ajuan
                            </button>
                            @endif
                    </div>
                </div>

                <div class="mb-3">
                    <span class="text-muted small d-block">Judul Proyek:</span>
                    <h6 class="fw-bold text-dark mb-0">{{ $group->title ?: 'Menunggu judul dari dosen pembimbing' }}</h6>
                </div>

                <div class="mb-3">
                    <span class="text-muted small d-block">Bidang Keahlian:</span>
                    <span class="badge bg-info-subtle text-info fw-semibold px-2 py-1">
                        {{ optional($group->expertise)->name ?? 'Belum ditentukan' }}
                    </span>
                </div>

                <hr class="my-3">

                <h6 class="fw-bold small text-uppercase text-secondary mb-2">Daftar Anggota & Status ACC Undangan:</h6>
                <ul class="list-group list-group-flush small mb-3">
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><strong>{{ optional($group->leader)->name ?? 'Ketua' }}</strong> (Ketua Kelompok)</span>
                        <span class="badge bg-success">Ketua</span>
                    </li>
                    @foreach($group->members as $member)
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span>{{ optional($member->student)->name ?? 'Anggota' }}</span>
                        @if($member->status == 'accepted')
                        <span class="badge bg-success">Disetujui (ACC)</span>
                        @elseif($member->status == 'rejected')
                        <span class="badge bg-danger">Ditolak</span>
                        @else
                        <span class="badge bg-warning text-dark">Menunggu Konfirmasi</span>
                        @endif
                    </li>
                    @endforeach
                </ul>

                <hr class="my-3">

                <!-- Box Status ACC Multi-Pihak -->
                <div class="card bg-light border-0 shadow-none p-3 mb-3 rounded-3">
                    <h6 class="fw-bold text-secondary small mb-2 text-uppercase">Status Persetujuan Sidang:</h6>
                    <div class="row text-center g-2">
                        <div class="col-4 border-end">
                            <span class="d-block small text-muted">Koordinator</span>
                            <span class="badge {{ ($hasGroup && $group->acc_coordinator) ? 'bg-success' : 'bg-warning text-dark' }} mt-1">
                                {{ ($hasGroup && $group->acc_coordinator) ? 'ACC' : 'Belum' }}
                            </span>
                        </div>
                        <div class="col-4 border-end">
                            <span class="d-block small text-muted">Pembimbing</span>
                            <span class="badge {{ ($hasGroup && $group->acc_supervisor) ? 'bg-success' : 'bg-warning text-dark' }} mt-1">
                                {{ ($hasGroup && $group->acc_supervisor) ? 'ACC' : 'Belum' }}
                            </span>
                        </div>
                        <div class="col-4">
                            <span class="d-block small text-muted">KPS</span>
                            <span class="badge {{ ($hasGroup && $group->acc_kps) ? 'bg-success' : 'bg-warning text-dark' }} mt-1">
                                {{ ($hasGroup && $group->acc_kps) ? 'ACC' : 'Belum' }}
                            </span>
                        </div>
                    </div>
                    <div class="small mt-3 text-muted">
                        <div>Koordinator: {{ $group?->acc_coordinator ? $group->coordinator_approved_by_role : ($group?->coordinator_approved_by_role ? 'Riwayat ACC: '.$group->coordinator_approved_by_role : 'Belum ACC') }}{{ $group?->coordinatorApprover ? ' — '.$group->coordinatorApprover->name : '' }}</div>
                        <div>Pembimbing: {{ $group?->acc_supervisor ? $group->supervisor_approved_by_role : ($group?->supervisor_approved_by_role ? 'Riwayat ACC: '.$group->supervisor_approved_by_role : 'Belum ACC') }}{{ $group?->supervisorApprover ? ' — '.$group->supervisorApprover->name : '' }}</div>
                        <div>KPS: {{ $group?->acc_kps ? $group->kps_approved_by_role : ($group?->kps_approved_by_role ? 'Riwayat ACC: '.$group->kps_approved_by_role : 'Belum ACC') }}{{ $group?->kpsApprover ? ' — '.$group->kpsApprover->name : '' }}</div>
                    </div>
                </div>

                <!-- Usulan Jadwal Ujian -->
                <form id="formProposeSchedule" class="border rounded-3 p-3 bg-light bg-opacity-50">
                    @csrf
                    <h6 class="fw-semibold mb-2">Usulan Jadwal Ujian</h6>
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label small mb-1">Tanggal</label><input type="date" name="exam_date" class="form-control form-control-sm" min="{{ now()->addDay()->toDateString() }}" value="{{ $group->proposed_exam_date }}" required></div>
                        <div class="col-md-6"><label class="form-label small mb-1">Waktu</label><input type="time" name="exam_time" class="form-control form-control-sm" value="{{ $group->proposed_exam_time }}" required></div>
                    </div>
                    <p class="small text-muted mt-2 mb-2">Jadwal resmi dan ruangan ditetapkan Admin setelah koordinasi dengan dosen pembimbing.</p>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button class="btn btn-outline-primary btn-sm" type="submit">Kirim / Revisi Usulan</button>
                        @if($group->exam_date && $group->exam_room)
                        <span class="badge bg-success">Jadwal: {{ $group->exam_date }} {{ $group->exam_time }} — {{ $group->exam_room }}</span>
                        @endif
                    </div>
                </form>
            </div>
            @endif
        </div>

        <!-- Kolom Kanan: Checklist Unggah Berkas Individual -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white h-100">
                @php
                $documentChecklist = [
                'consultation_card' => [
                'label' => 'Kartu Konsultasi / Bimbingan',
                'format' => 'PDF / Gambar (JPG, PNG)',
                'accept' => '.pdf,.jpg,.jpeg,.png',
                'optional' => false,
                'file' => $group->consultation_card_file ?? null,
                ],
                'draft_report' => [
                'label' => 'Draft Laporan Proposal',
                'format' => 'PDF',
                'accept' => '.pdf',
                'optional' => false,
                'file' => $group->draft_report_file ?? null,
                ],
                'berita_acara' => [
                'label' => 'Berita Acara Ujian',
                'format' => 'PDF / Gambar (JPG, PNG)',
                'accept' => '.pdf,.jpg,.jpeg,.png',
                'optional' => false,
                'file' => $group->berita_acara_file ?? null,
                ],
                'lembar_revisi' => [
                'label' => 'Lembar Pengesahan Revisi',
                'format' => 'PDF',
                'accept' => '.pdf',
                'optional' => false,
                'file' => $group->lembar_revisi_file ?? null,
                ],
                'final_report' => [
                'label' => 'Laporan Final / Buku Capstone',
                'format' => 'PDF',
                'accept' => '.pdf',
                'optional' => false,
                'file' => $group->final_report_file ?? null,
                ],
                'agreement' => [
                'label' => 'Kesepakatan Bersama CD',
                'format' => 'PDF',
                'accept' => '.pdf',
                'optional' => false,
                'file' => $group->agreement_file ?? null,
                ],
                'drawings' => [
                'label' => 'Gambar Kerja / Desain',
                'format' => 'PDF / DWG / DXF / ZIP',
                'accept' => '.pdf,.dwg,.dxf,.zip',
                'optional' => false,
                'file' => $group->drawings_file ?? null,
                ],
                'rab' => [
                'label' => 'Rencana Anggaran Biaya (RAB)',
                'format' => 'PDF / Excel (XLSX, XLS) / CSV',
                'accept' => '.pdf,.xlsx,.xls,.csv',
                'optional' => false,
                'file' => $group->rab_file ?? null,
                ],
                'recap_score' => [
                'label' => 'Rekapitulasi Nilai',
                'format' => 'PDF',
                'accept' => '.pdf',
                'optional' => false,
                'file' => $group->recap_score_file ?? null,
                ],
                'structural_calculation' => [
                'label' => 'Perhitungan Struktural',
                'format' => 'PDF / Excel / ZIP (Opsional)',
                'accept' => '.pdf,.xlsx,.xls,.csv,.zip',
                'optional' => true,
                'file' => $group->structural_calculation_file ?? null,
                ],
                ];

                $mandatoryDocs = collect($documentChecklist)->where('optional', false);
                $uploadedMandatoryCount = $mandatoryDocs->where('file', '!=', null)->count();
                $totalMandatoryCount = $mandatoryDocs->count();
                @endphp

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h5 class="fw-bold mb-1 fs-6 fs-md-5"><i class="ti ti-cloud-upload me-2 text-primary"></i>Kelengkapan Berkas Capstone</h5>
                        <p class="small text-muted mb-0">Unggah masing-masing berkas pada slot di bawah ini.</p>
                    </div>
                    @if($hasGroup)
                    <span class="badge {{ $uploadedMandatoryCount === $totalMandatoryCount ? 'bg-success' : 'bg-primary-subtle text-primary' }} px-3 py-2 rounded-pill fw-semibold">
                        <i class="ti ti-checklist me-1"></i> {{ $uploadedMandatoryCount }} / {{ $totalMandatoryCount }} Berkas Wajib
                    </span>
                    @endif
                </div>

                @if(!$hasGroup)
                <div class="alert alert-warning py-3 small mb-0">
                    <i class="ti ti-alert-triangle me-1"></i> Anda harus mengajukan kelompok terlebih dahulu sebelum dapat mengunggah berkas.
                </div>
                @else
                <div class="d-flex flex-column gap-3">
                    @foreach($documentChecklist as $type => $doc)
                    @php
                    $isUploaded = !empty($doc['file']);
                    $cardStateClass = $isUploaded ? 'is-uploaded' : ($doc['optional'] ? 'is-optional' : 'is-missing');
                    @endphp
                    <div class="doc-item-card {{ $cardStateClass }} p-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    @if($isUploaded)
                                    <i class="ti ti-circle-check-filled text-success fs-5"></i>
                                    @else
                                    <i class="ti ti-circle-dashed text-secondary fs-5"></i>
                                    @endif
                                    <span class="fw-bold text-dark">{{ $doc['label'] }}</span>
                                </div>
                                <small class="text-muted d-block ms-4">Format: {{ $doc['format'] }}</small>
                            </div>

                            <div class="d-flex align-items-center gap-1">
                                @if($isUploaded)
                                <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1 me-1">Sudah Diunggah</span>
                                <a href="{{ route('capstone.file.view', ['path' => $doc['file']]) }}" target="_blank" class="btn btn-sm btn-info text-white py-1 px-2" title="Lihat Berkas">
                                    <i class="ti ti-eye"></i> Lihat
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 btn-delete-file" data-type="{{ $type }}" title="Hapus Berkas">
                                    <i class="ti ti-trash"></i>
                                </button>
                                @elseif($doc['optional'])
                                <span class="badge bg-info-subtle text-info rounded-pill px-2 py-1">Opsional</span>
                                @else
                                <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2 py-1">Belum Diunggah</span>
                                @endif
                            </div>
                        </div>

                        <!-- Form Upload Individual per Berkas -->
                        <form class="form-single-upload mt-2 pt-2 border-top border-light-subtle" data-type="{{ $type }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="file_type" value="{{ $type }}">
                            <div class="input-group input-group-sm">
                                <input type="file" name="document_file" class="form-control" accept="{{ $doc['accept'] }}" required>
                                <button type="submit" class="btn {{ $isUploaded ? 'btn-outline-success' : 'btn-primary' }} fw-semibold px-3 btn-upload-submit">
                                    <i class="ti ti-upload me-1"></i> {{ $isUploaded ? 'Ganti File' : 'Unggah' }}
                                </button>
                            </div>
                        </form>
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
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
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
                                timer: 1500,
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
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function(err) {
                            Swal.fire('Gagal', err.responseJSON?.message || 'Terjadi kesalahan sistem.', 'error');
                        }
                    });
                }
            });
        });

        // Inisialisasi Select2 untuk form luar
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

        // Inisialisasi Select2 khusus di dalam Modal Edit
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

    // Submit Pendaftaran Kelompok Baru
    $(document).on('submit', '#formPendaftaranCapstone', function(e) {
        e.preventDefault();
        submitGroupData($(this).serialize());
    });

    // Submit Edit Kelompok Modal
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
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            },
            error: function(err) {
                Swal.fire('Gagal', err.responseJSON?.message || 'Terjadi kesalahan', 'error');
            }
        });
    }

    // Proses Submit Unggah Berkas Individual per Slot
    $(document).on('submit', '.form-single-upload', function(e) {
        e.preventDefault();
        let form = $(this);
        let btn = form.find('.btn-upload-submit');
        let originalBtnHtml = btn.html();
        let formData = new FormData(this);
        let groupId = "{{ $group->id ?? '' }}";

        if (!groupId) {
            Swal.fire('Perhatian', 'Anda harus terdaftar dalam kelompok terlebih dahulu!', 'warning');
            return;
        }

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Mengunggah...');

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
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            },
            error: function(err) {
                btn.prop('disabled', false).html(originalBtnHtml);
                Swal.fire('Gagal', err.responseJSON?.message || 'Terjadi kesalahan saat mengunggah berkas', 'error');
            }
        });
    });
</script>
@endpush
@endsection