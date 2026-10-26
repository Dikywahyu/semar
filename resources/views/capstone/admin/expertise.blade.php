@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<!-- Select2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .dataTables_length select.form-select {
        display: inline-block;
        width: 75px !important;
        padding-top: 0.35rem;
        padding-bottom: 0.35rem;
        padding-left: 0.75rem;
        padding-right: 2rem;
        font-size: 0.875rem;
    }

    .dataTables_wrapper .dataTables_length label,
    .dataTables_wrapper .dataTables_filter label {
        font-size: 0.875rem;
        color: #5a6a85;
    }

    .dataTables_wrapper .dataTables_filter input {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 0.5rem;
        border: 1px solid #dfe5ef;
    }

    /* Memperbaiki agar Select2 tampil sempurna di dalam Bootstrap Modal */
    .select2-container--bootstrap-5 {
        width: 100% !important;
    }

    .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border-color: #dfe5ef;
    }

    .select2-container {
        z-index: 99999 !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 py-md-4">

    <!-- Breadcrumb Banner -->
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-3 px-md-4 py-3">
            <div class="row align-items-center">
                <div class="col-8 col-sm-9">
                    <h4 class="fw-semibold mb-2 fs-5 fs-md-4">Master Bidang Keahlian Capstone</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 small">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="http://127.0.0.1:8000">Home</a></li>
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('capstone.admin.index') }}">Capstone</a></li>
                            <li class="breadcrumb-item" aria-current="page">Master Keahlian</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-4 col-sm-3 text-center"><img src="http://127.0.0.1:8000/dist/assets/images/breadcrumb/ChatBc.png" alt="" class="img-fluid mb-n4"></div>
            </div>
        </div>
    </div>

    <!-- Header Action Buttons -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="text-secondary small mb-0">Kelola daftar bidang keahlian serta atur dosen pengampu yang dapat dipilih mahasiswa saat mendaftarkan kelompok.</p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2">
            <a href="{{ route('capstone.admin.index') }}" class="btn btn-outline-secondary fw-semibold btn-sm">
                <i class="ti ti-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
            <button type="button" class="btn btn-primary fw-semibold shadow-sm btn-sm" data-bs-toggle="modal" data-bs-target="#modalExpertise" id="btnTambahExpertise">
                <i class="ti ti-plus me-1"></i> Tambah Keahlian
            </button>
        </div>
    </div>

    <!-- Tabel Master Keahlian -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table id="tableExpertise" class="table table-hover align-middle w-100 mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3">No</th>
                            <th class="py-3">Nama Bidang Keahlian</th>
                            <th class="py-3">Dosen Koordinator</th>
                            <th class="py-3">Dosen Bidang</th>
                            <th class="py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tableBodyExpertise">
                        <!-- Load via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah / Edit Keahlian -->
<div class="modal fade" id="modalExpertise" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTitle">Tambah Bidang Keahlian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="formExpertise">
                @csrf
                <input type="hidden" id="expertiseId" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Bidang Keahlian</label>
                        <input type="text" name="name" id="inputName" class="form-control" required placeholder="Contoh: Software Engineering...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Dosen Koordinator (Opsional)</label>
                        <select name="coordinator_id" id="inputCoordinator" class="form-select select2-modal">
                            <option value="">-- Pilih Dosen Koordinator --</option>
                            @foreach($dosens as $dosen)
                            <option value="{{ $dosen->id_dosen }}">{{ $dosen->nama_dosen }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSimpan">Simpan Keahlian</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Atur Dosen Keahlian -->
<div class="modal fade" id="modalManageLecturers" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold fs-6 fs-md-5">Dosen Anggota Keahlian: <span id="expertiseTitleName" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <!-- Form Tambah Dosen ke Keahlian -->
                <form id="formAddExpertiseLecturer" class="mb-4">
                    @csrf
                    <input type="hidden" id="currentExpertiseId" name="expertise_id">
                    <div class="row align-items-end g-2">
                        <div class="col-12 col-md-8">
                            <label class="form-label small fw-semibold">Pilih Dosen Pengampu / Anggota</label>
                            <select name="lecturer_id" id="selectNewLecturer" class="form-select select2-dosen" required>
                                <option value="">-- Cari atau Pilih Dosen --</option>
                                @foreach($dosens as $dosen)
                                <option value="{{ $dosen->id_dosen }}">{{ $dosen->nama_dosen }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4 d-grid">
                            <button type="submit" class="btn btn-success fw-semibold"><i class="ti ti-plus me-1"></i> Tambah Dosen</button>
                        </div>
                    </div>
                </form>

                <hr class="my-3">

                <!-- Tabel Daftar Dosen yang Tergabung -->
                <h6 class="fw-bold mb-3 fs-6">Dosen yang Mengampu Keahlian Ini:</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama Dosen</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tableBodyAssignedLecturers">
                            <!-- Di-load via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let dataTableExpertise = null;

    document.addEventListener("DOMContentLoaded", function() {
        dataTableExpertise = $('#tableExpertise').DataTable({
            responsive: true,
            language: {
                lengthMenu: "Show _MENU_ entries",
                search: "Search:",
                paginate: {
                    previous: "Prev",
                    next: "Next"
                },
                info: "Showing _START_ to _END_ of _TOTAL_ entries"
            }
        });
        loadExpertiseData();

        // Inisialisasi Select2 di dalam modal
        $('.select2-dosen').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalManageLecturers')
        });

        $('.select2-modal').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalExpertise')
        });

        $('#modalExpertise').on('show.bs.modal', function(event) {
            let button = $(event.relatedTarget);
            if (button.attr('id') === 'btnTambahExpertise') {
                $('#modalTitle').text('Tambah Bidang Keahlian');
                $('#formExpertise')[0].reset();
                $('#inputCoordinator').val(null).trigger('change');
                $('#expertiseId').val('');
            }
        });

        $('#formExpertise').on('submit', function(e) {
            e.preventDefault();
            let id = $('#expertiseId').val();
            let url = id ? "{{ url('capstone/admin/expertises') }}/" + id : "{{ route('capstone.admin.expertises.store') }}";
            let type = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                type: type,
                data: $(this).serialize(),
                success: function(res) {
                    $('#modalExpertise').modal('hide');
                    Swal.fire('Berhasil!', res.message, 'success');
                    loadExpertiseData();
                },
                error: function(err) {
                    Swal.fire('Gagal', err.responseJSON?.message || 'Terjadi kesalahan.', 'error');
                }
            });
        });
    });

    function loadExpertiseData() {
        $.ajax({
            url: "{{ route('capstone.admin.expertises.index') }}",
            type: 'GET',
            success: function(response) {
                if ($.fn.DataTable.isDataTable('#tableExpertise')) {
                    $('#tableExpertise').DataTable().destroy();
                }

                let rows = '';
                if (response.data && response.data.length > 0) {
                    response.data.forEach(function(item, index) {
                        // Merangkai daftar dosen bidang (lecturers) menjadi bentuk list badge/teks rapi
                        // Merangkai daftar dosen bidang menggunakan badge warna-warni Bootstrap
                        let lecturersHtml = '<span class="text-muted small">Belum ada dosen</span>';
                        if (item.lecturers && item.lecturers.length > 0) {
                            // Array pilihan warna badge Bootstrap
                            const badgeColors = ['bg-primary', 'bg-info  ', 'bg-success', 'bg-warning text-dark', 'bg-danger', 'bg-secondary', 'bg-dark'];

                            lecturersHtml = '<div class="d-flex flex-wrap gap-1">';
                            item.lecturers.forEach(function(dosen, idx) {
                                // Mengambil warna secara berputar (cyclic) berdasarkan index dosen
                                let colorClass = badgeColors[idx % badgeColors.length];
                                lecturersHtml += `<span class="badge ${colorClass} fw-normal py-1 px-2">${dosen.nama_dosen}</span>`;
                            });
                            lecturersHtml += '</div>';
                        }

                        rows += `<tr>
                            <td>${index + 1}</td>
                            <td><strong>${item.name}</strong></td>
                            <td>${item.coordinator?.nama_dosen ?? '<span class="text-muted">Belum ditentukan</span>'}</td>
                            <td>${lecturersHtml}</td>
                            <td class="text-center">
                                <div class="d-flex flex-column flex-sm-row justify-content-center gap-1">
                                    <button class="btn btn-sm btn-outline-info btn-manage-lecturers" data-id="${item.id}" data-name="${item.name}"><i class="ti ti-users"></i> Atur Dosen</button>
                                    <button class="btn btn-sm btn-outline-warning btn-edit" data-id="${item.id}" data-name="${item.name}" data-coordinator="${item.coordinator_id ?? ''}"><i class="ti ti-edit"></i> Edit</button>
                                    <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${item.id}"><i class="ti ti-trash"></i> Hapus</button>
                                </div>
                            </td>
                        </tr>`;
                    });
                }
                $('#tableBodyExpertise').html(rows);
                dataTableExpertise = $('#tableExpertise').DataTable({
                    responsive: true,
                    language: {
                        lengthMenu: "Show _MENU_ entries",
                        search: "Search:",
                        paginate: {
                            previous: "Prev",
                            next: "Next"
                        },
                        info: "Showing _START_ to _END_ of _TOTAL_ entries"
                    }
                });
                $('.dataTables_length select').addClass('form-select form-select-sm');
            }
        });
    }

    // Klik Tombol "Atur Dosen"
    $(document).on('click', '.btn-manage-lecturers', function() {
        let expertiseId = $(this).data('id');
        let expertiseName = $(this).data('name');

        $('#currentExpertiseId').val(expertiseId);
        $('#expertiseTitleName').text(expertiseName);
        $('#formAddExpertiseLecturer')[0].reset();
        $('#selectNewLecturer').val(null).trigger('change');

        fetchAssignedLecturers(expertiseId);
        $('#modalManageLecturers').modal('show');
    });

    // Fungsi ambil data dosen terpelihara
    function fetchAssignedLecturers(expertiseId) {
        $.ajax({
            url: "{{ url('capstone/admin/expertises') }}/" + expertiseId + "/lecturers",
            type: 'GET',
            success: function(res) {
                let rows = '';
                if (res.assigned_lecturers && res.assigned_lecturers.length > 0) {
                    res.assigned_lecturers.forEach(function(dosen, index) {
                        rows += `<tr>
                            <td>${index + 1}</td>
                            <td>${dosen.nama_dosen}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-danger btn-remove-lecturer" data-exp="${expertiseId}" data-dosen="${dosen.id_dosen}"><i class="ti ti-trash"></i> Hapus</button>
                            </td>
                        </tr>`;
                    });
                } else {
                    rows = `<tr><td colspan="3" class="text-center py-3 text-muted">Belum ada dosen anggota yang ditambahkan pada keahlian ini.</td></tr>`;
                }
                $('#tableBodyAssignedLecturers').html(rows);

                // Refresh data utama tabel di belakang agar langsung sinkron
                loadExpertiseData();
            }
        });
    }

    // Submit Tambah Dosen ke Keahlian
    $('#formAddExpertiseLecturer').on('submit', function(e) {
        e.preventDefault();
        let expertiseId = $('#currentExpertiseId').val();

        $.ajax({
            url: "{{ url('capstone/admin/expertises') }}/" + expertiseId + "/lecturers",
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: res.message,
                    timer: 1200,
                    showConfirmButton: false
                });
                fetchAssignedLecturers(expertiseId);
                $('#selectNewLecturer').val(null).trigger('change');
            },
            error: function(err) {
                Swal.fire('Gagal', err.responseJSON?.message || 'Terjadi kesalahan.', 'error');
            }
        });
    });

    // Hapus Dosen dari Keahlian
    $(document).on('click', '.btn-remove-lecturer', function() {
        let expertiseId = $(this).data('exp');
        let lecturerId = $(this).data('dosen');

        Swal.fire({
            title: 'Hapus dosen dari keahlian ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('capstone/admin/expertises') }}/" + expertiseId + "/lecturers/" + lecturerId,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus',
                            text: res.message,
                            timer: 1200,
                            showConfirmButton: false
                        });
                        fetchAssignedLecturers(expertiseId);
                    }
                });
            }
        });
    });

    // Klik Edit
    $(document).on('click', '.btn-edit', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        let coordinator = $(this).data('coordinator');

        $('#modalTitle').text('Edit Bidang Keahlian');
        $('#expertiseId').val(id);
        $('#inputName').val(name);
        $('#inputCoordinator').val(coordinator).trigger('change');

        $('#modalExpertise').modal('show');
    });

    // Klik Hapus
    $(document).on('click', '.btn-delete', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Hapus keahlian ini?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('capstone/admin/expertises') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        Swal.fire('Terhapus!', res.message, 'success');
                        loadExpertiseData();
                    },
                    error: function() {
                        Swal.fire('Gagal', 'Terjadi kesalahan saat menghapus data.', 'error');
                    }
                });
            }
        });
    });
</script>
@endpush
@endsection