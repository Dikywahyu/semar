@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" style="background: linear-gradient(135deg, #f6f8fd 0%, #f1f5f9 100%); min-height: 100vh;">

    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-slate-800 mb-1">Dashboard & Monitoring Capstone Design</h3>
            <p class="text-secondary mb-0">Kelola kelompok, plotting dosen, verifikasi KPI, penjadwalan ujian, dan komponen nilai.</p>
        </div>
        <div>
            <a href="{{ route('capstone.admin.komponen.index') }}" class="btn btn-outline-primary fw-semibold me-2">
                <i class="ti ti-adjustments me-1"></i> Master Komponen Nilai
            </a>
            <a href="{{ route('capstone.admin.grade-recap') }}" class="btn btn-outline-success fw-semibold me-2">Rekap Nilai CD</a>
            <a href="{{ route('capstone.admin.expertises.index') }}" class="btn btn-outline-secondary fw-semibold">Bidang Keahlian</a>
        </div>
    </div>
    @if(auth()->user()->hasRole('Super Admin'))
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ route('capstone.admin.plotting.export') }}" class="btn btn-success btn-sm">Export Excel</a>
            <form id="formImportPlotting" class="d-flex gap-2" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" class="form-control form-control-sm" accept=".xlsx,.xls,.csv" required>
                <button class="btn btn-outline-primary btn-sm">Import Excel</button>
            </form>
        </div>
        <small class="text-muted mt-2">Format import: group_id (opsional), leader_id, expertise_id, supervisor_id, examiner_id, project_location. Judul Capstone ditetapkan oleh dosen pembimbing pada menu dosen. Baris dengan group_id akan memperbarui plotting.</small>
        <form id="formManualPlotting" class="row g-2 mt-2">
            @csrf
            <div class="col-md-2"><input class="form-control" type="number" name="leader_id" placeholder="ID mahasiswa" required></div>
            <div class="col-md-2">
                <select class="form-select" name="expertise_id" required>
                    <option value="">Pilih bidang keahlian</option>
                    @foreach($expertises as $expertise)<option value="{{ $expertise->id }}">{{ $expertise->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="supervisor_id">
                    <option value="">Pilih dosen pembimbing</option>
                    @foreach($dosens as $dosen)<option value="{{ $dosen->id_dosen }}">{{ $dosen->nama_dosen }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="examiner_id">
                    <option value="">Pilih dosen penguji</option>
                    @foreach($dosens as $dosen)<option value="{{ $dosen->id_dosen }}">{{ $dosen->nama_dosen }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2"><input class="form-control" name="project_location" placeholder="Tempat"></div>
            <div class="col-md-2"><button class="btn btn-primary btn-sm w-100">Tambah</button></div>
        </form>
    </div>
    @endif

    <!-- 1. SECTION GRAFIK & FILTER -->
    <div class="row g-4 mb-4">
        <!-- Grafik Status Kelompok Capstone -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-slate-800 mb-0">Statistik Status Kelompok Capstone</h5>
                    <span class="badge bg-light text-secondary">Real-time</span>
                </div>
                <div style="height: 300px; position: relative;">
                    <canvas id="capstoneStatusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Filter Data Cepat -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h5 class="fw-bold text-slate-800 mb-3"><i class="ti ti-filter me-2"></i>Filter Data Capstone</h5>
                <form id="filterForm">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Keahlian</label>
                        <select name="expertise_id" class="form-select">
                            <option value="">-- Semua Keahlian --</option>
                            @foreach($expertises ?? [] as $exp)
                            <option value="{{ $exp->id }}">{{ $exp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Status Final</label>
                        <select name="final_status" class="form-select">
                            <option value="">-- Semua Status --</option>
                            <option value="proses">Proses</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cari Judul / Ketua</label>
                        <input type="text" name="keyword" class="form-control" placeholder="Judul / Nama Ketua...">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. TABEL DATA UTAMA -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-body p-4" id="tableContainer">
            <h5 class="fw-bold text-slate-800 mb-3">Daftar Kelompok & Progress Capstone Design</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Judul / Ketua</th>
                            <th>Keahlian</th>
                            <th>Dosen Pembimbing / Penguji</th>
                            <th>Status KPI & Ujian</th>
                            <th>Status ACC Multi-Pihak</th>
                            <th>Aksi Admin</th>
                        </tr>
                    </thead>
                    <tbody id="tableBodyGroups">
                        <!-- Di-load via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let capstoneChart = null;
    const canApproveKps = @json(auth()->user()->hasAnyRole(['Super Admin', 'KPS', 'Kaprodi']));
    const canPrintSk = @json(auth()->user()->hasRole('Super Admin'));
    const capstoneDosen = @json($dosens->map(fn ($dosen) => ['id' => $dosen->id_dosen, 'name' => $dosen->nama_dosen])->values());

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]));
    }

    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('capstoneStatusChart').getContext('2d');
        capstoneChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Draft / Submitted', 'Ujian Dijadwalkan', 'Proses Sidang', 'Selesai'],
                datasets: [{
                    data: [0, 0, 0, 0], // Data dinamis atau default
                    backgroundColor: ['#64748b', '#0891b2', '#f59e0b', '#16a34a'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        loadTableData();
        $('#formImportPlotting').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: "{{ route('capstone.admin.plotting.import') }}",
                type: 'POST',
                data: new FormData(this),
                contentType: false,
                processData: false,
                success: function(res) {
                    Swal.fire('Berhasil', res.message, 'success');
                    loadTableData();
                },
                error: function(err) {
                    Swal.fire('Gagal', err.responseJSON?.message || 'Import plotting gagal.', 'error');
                }
            });
            $('#formManualPlotting').on('submit', function(e) {
                e.preventDefault();
                $.post("{{ route('capstone.admin.groups.store') }}", $(this).serialize())
                    .done(function(res) {
                        Swal.fire('Berhasil', res.message, 'success');
                        $('#formManualPlotting')[0].reset();
                        loadTableData();
                    })
                    .fail(function(err) {
                        Swal.fire('Gagal', err.responseJSON?.message || 'Plotting manual gagal.', 'error');
                    });
            });
        });

        // Event listener untuk filter live
        $('#filterForm select').on('change', function() {
            loadTableData();
        });

        let searchTimer;
        $('#filterForm input[name="keyword"]').on('keyup', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(loadTableData, 400);
        });
    });

    function loadTableData() {
        let expertiseId = $('select[name="expertise_id"]').val();
        let finalStatus = $('select[name="final_status"]').val();
        let keyword = $('input[name="keyword"]').val();

        $.ajax({
            url: "{{ route('capstone.admin.index') }}",
            type: 'GET',
            data: {
                expertise_id: expertiseId,
                final_status: finalStatus,
                keyword: keyword
            },
            success: function(response) {
                let rows = '';
                let countDraft = 0;
                let countUjian = 0;
                let countSidang = 0;
                let countSelesai = 0;

                if (response.data && response.data.length > 0) {
                    response.data.forEach(function(group) {
                        // Kategorisasi statistik untuk chart 4 status
                        if (group.final_status === 'selesai') {
                            countSelesai++;
                        } else if (group.exam_room) {
                            countUjian++;
                        } else if (group.status_pendaftaran === 'approved') {
                            countSidang++;
                        } else {
                            countDraft++;
                        }

                        rows += `<tr>
                            <td><strong>${group.title ?? 'Belum ada judul'}</strong><br><small class="text-muted">Ketua: ${group.leader?.name ?? '-'}</small></td>
                            <td>${group.expertise?.name ?? 'Belum memilih'}<br><small>${group.project_location ?? 'Lokasi belum diatur'}</small></td>
                            <td>
                                Pembimbing: ${group.supervisor?.nama_dosen ?? '<span class="text-danger">Belum ada</span>'}<br>
                                Penguji: ${group.examiner?.nama_dosen ?? '<span class="text-danger">Belum ada</span>'}
                            </td>
                            <td>
                                KPI: <span class="badge bg-${group.kpi_approval == 'approved' ? 'success' : 'warning'}">${group.kpi_approval}</span><br>
                                Usulan: ${group.proposed_exam_date ?? '-'} ${group.proposed_exam_time ?? ''}<br>
                                Jadwal: ${group.exam_date ?? '-'} ${group.exam_time ?? ''}<br>
                                Ruang: ${group.exam_room ?? '-'}
                            </td>
                            <td>
                                Koor: ${group.acc_coordinator ? '✅' : '❌'} ${group.coordinator_approved_by_role ?? ''} ${group.coordinator_approver?.name ?? ''}<br>
                                Pembimbing: ${group.acc_supervisor ? '✅' : '❌'} ${group.supervisor_approved_by_role ?? ''} ${group.supervisor_approver?.name ?? ''}<br>
                                KPS: ${group.acc_kps ? '✅' : '❌'} ${group.kps_approved_by_role ?? ''} ${group.kps_approver?.name ?? ''}
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary btn-admin-plot" data-id="${group.id}" data-supervisor="${group.supervisor_id ?? ''}" data-examiner="${group.examiner_id ?? ''}" data-location="${escapeHtml(group.project_location ?? '')}">Plotting</button>
                                <button class="btn btn-sm btn-outline-primary btn-set-room" data-id="${group.id}" data-date="${group.proposed_exam_date ?? ''}" data-time="${group.proposed_exam_time ?? ''}">Atur Jadwal / Ruangan</button>
                                ${canApproveKps ? `<button class="btn btn-sm btn-outline-success btn-kps-approval" data-id="${group.id}" data-approved="${group.acc_kps ? 1 : 0}">${group.acc_kps ? 'Cabut ACC KPS' : 'ACC KPS'}</button>` : ''}
                                <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ url('capstone/admin/invitation') }}/${group.id}">Undangan</a>
                                <button class="btn btn-sm btn-outline-info btn-send-invitation" data-id="${group.id}">Kirim</button>
                                <button class="btn btn-sm btn-outline-dark btn-print-document" data-id="${group.id}" data-type="berita-acara">Berita Acara</button>
                                <button class="btn btn-sm btn-outline-dark btn-print-document" data-id="${group.id}" data-type="kesepakatan">Kesepakatan</button>
                                ${canPrintSk ? `<button class="btn btn-sm btn-outline-dark btn-print-document" data-id="${group.id}" data-type="sk">Cetak SK</button>` : ''}
                                <button class="btn btn-sm btn-outline-danger btn-delete-group" data-id="${group.id}">Hapus</button>
                            </td>
                        </tr>`;
                    });
                } else {
                    rows = `<tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data kelompok capstone.</td></tr>`;
                }

                $(document).on('click', '.btn-admin-plot', function() {
                    const button = $(this);
                    const supervisorId = String(button.data('supervisor') ?? '');
                    const examinerId = String(button.data('examiner') ?? '');
                    const options = capstoneDosen.map(dosen => {
                        const id = escapeHtml(dosen.id);
                        const name = escapeHtml(dosen.name);
                        return `<option value="${id}">${name}</option>`;
                    }).join('');
                    Swal.fire({
                        title: 'Plotting Tempat dan Dosen',
                        html: `<input id="project-location" class="swal2-input" maxlength="255" placeholder="Tempat / lokasi proyek" value="${escapeHtml(button.data('location'))}"><select id="plot-supervisor" class="swal2-select"><option value="">Pilih pembimbing</option>${options}</select><select id="plot-examiner" class="swal2-select"><option value="">Pilih penguji</option>${options}</select>`,
                        showCancelButton: true,
                        confirmButtonText: 'Simpan',
                        didOpen: () => {
                            document.getElementById('plot-supervisor').value = supervisorId;
                            document.getElementById('plot-examiner').value = examinerId;
                        },
                        preConfirm: () => ({
                            project_location: document.getElementById('project-location').value,
                            supervisor_id: document.getElementById('plot-supervisor').value,
                            examiner_id: document.getElementById('plot-examiner').value
                        })
                    }).then(result => {
                        if (!result.isConfirmed) return;
                        $.ajax({
                            url: "{{ url('capstone/admin/groups') }}/" + button.data('id') + "/plotting",
                            type: 'PUT',
                            data: {...result.value, _token: '{{ csrf_token() }}'},
                            success: res => {
                                Swal.fire('Berhasil', res.message, 'success');
                                loadTableData();
                            },
                            error: err => Swal.fire('Gagal', err.responseJSON?.message || 'Plotting gagal disimpan.', 'error')
                        });
                    });
                });

                $('#tableBodyGroups').html(rows);

                // Perbarui data chart secara real-time
                if (capstoneChart) {
                    capstoneChart.data.datasets[0].data = [countDraft, countUjian, countSidang, countSelesai];
                    capstoneChart.update();
                }
            }
        });
    }

    // Aksi Admin Set Ruangan Ujian via AJAX SweetAlert
    $(document).on('click', '.btn-set-room', function() {
        let button = $(this);
        let groupId = button.data('id');
        let proposedDate = button.data('date') || '';
        let proposedTime = String(button.data('time') || '').slice(0, 5);
        Swal.fire({
            title: 'Tentukan Ruang Ujian',
            html: `<input id="exam-date" type="date" class="swal2-input" aria-label="Tanggal ujian" value="${proposedDate}"><input id="exam-time" type="time" class="swal2-input" aria-label="Waktu ujian" value="${proposedTime}"><input id="exam-room" type="text" class="swal2-input" placeholder="Nama / Nomor Ruangan...">`,
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            preConfirm: () => {
                const exam_room = document.getElementById('exam-room').value;
                const exam_date = document.getElementById('exam-date').value;
                const exam_time = document.getElementById('exam-time').value;
                if (!exam_room || !exam_date || !exam_time) {
                    Swal.showValidationMessage('Tanggal, waktu, dan ruangan wajib diisi.');
                    return false;
                }
                return {exam_room, exam_date, exam_time};
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                $.ajax({
                    url: "{{ url('capstone/admin/room') }}/" + groupId,
                    type: 'POST',
                    data: {
                        exam_room: result.value.exam_room,
                        exam_date: result.value.exam_date,
                        exam_time: result.value.exam_time,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        Swal.fire('Berhasil', res.message, 'success');
                        loadTableData();
                    },
                    error: function(err) {
                        Swal.fire('Gagal', err.responseJSON?.message || 'Terjadi kesalahan.', 'error');
                    }
                });
            }
        });
    });

    $(document).on('click', '.btn-kps-approval', function() {
        const groupId = $(this).data('id');
        const approved = !Number($(this).data('approved'));
        $.post("{{ url('capstone/admin/groups') }}/" + groupId + "/kps-approval", {
            approved: approved ? 1 : 0,
            _token: '{{ csrf_token() }}'
        }).done(function(res) {
            Swal.fire('Berhasil', res.message, 'success');
            loadTableData();
        }).fail(function(err) {
            Swal.fire('Gagal', err.responseJSON?.message || 'ACC KPS gagal disimpan.', 'error');
        });
    });

    $(document).on('click', '.btn-send-invitation', function() {
        $.post("{{ url('capstone/admin/invitation') }}/" + $(this).data('id') + "/send", {
            _token: '{{ csrf_token() }}'
        }).done(function(res) {
            Swal.fire('Berhasil', res.message, 'success');
        }).fail(function(err) {
            Swal.fire('Gagal', err.responseJSON?.message || 'Undangan gagal dikirim.', 'error');
        });
    });

    $(document).on('click', '.btn-print-document', function() {
        window.open("{{ url('capstone/admin/print') }}/" + $(this).data('id') + "/" + $(this).data('type'), '_blank', 'noopener');
    });

    $(document).on('click', '.btn-delete-group', function() {
        const groupId = $(this).data('id');
        Swal.fire({title: 'Hapus ajuan ini?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Hapus'}).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ url('capstone/admin/groups') }}/" + groupId,
                type: 'DELETE',
                data: {_token: '{{ csrf_token() }}'},
                success: function(res) {
                    Swal.fire('Terhapus', res.message, 'success');
                    loadTableData();
                },
                error: function(err) {
                    Swal.fire('Gagal', err.responseJSON?.message || 'Ajuan gagal dihapus.', 'error');
                }
            });
        });
    });
</script>
@endpush
@endsection