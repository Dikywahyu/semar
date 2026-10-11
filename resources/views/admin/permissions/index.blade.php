@extends('layouts.app')

@section('title', 'Menu Akses & Permissions')

@section('content')
<div class="container-fluid">
    {{-- Breadcrumb Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Permissions</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ url('/admin/dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Permissions</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-center">
                    <img src="{{ asset('dist/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" />
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom">
                    <h4 class="card-title mb-0">
                        <iconify-icon icon="solar:shield-keyhole-line-duotone" class="me-2"></iconify-icon>
                        Daftar Permissions
                    </h4>
                    <button class="btn btn-primary btn-sm d-flex align-items-center shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddPermission">
                        <iconify-icon icon="solar:add-circle-line-duotone" class="me-1 fs-5"></iconify-icon>
                        Tambah Permission
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="table-permissions" class="table table-hover align-middle w-100">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">No</th>
                                    <th>Nama Permission</th>
                                    <th>Guard</th>
                                    <th width="120" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH PERMISSION --}}
<div class="modal fade" id="modalAddPermission" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formAddPermission" action="{{ url('admin/permissions') }}" method="POST">
            @csrf
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title d-flex align-items-center text-white">
                        Tambah Permission
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Permission</label>
                        <input type="text" name="name" class="form-control" placeholder="cth: user-create" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Guard Name</label>
                        <select name="guard_name" class="form-select">
                            <option value="api">api (SSO/Sanctum)</option>
                            <option value="web">web (Dashboard)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSavePermission">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT PERMISSION --}}
<div class="modal fade" id="modalEditPermission" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formEditPermission" method="POST">
            @csrf @method('PUT')
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title d-flex align-items-center text-white">
                        Edit Permission
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Permission</label>
                        <input type="text" name="name" id="edit-perm-name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Guard Name</label>
                        <select name="guard_name" id="edit-perm-guard" class="form-select">
                            <option value="api">api</option>
                            <option value="web">web</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-white" id="btnUpdatePermission">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
{{-- Library Assets --}}
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // 1. Inisialisasi DataTable
        var table = $('#table-permissions').DataTable({
            processing: true,
            ajax: "{{ url('admin/permissions') }}",
            columns: [{
                    data: null,
                    render: (data, type, row, meta) => meta.row + 1
                },
                {
                    data: 'name',
                    render: (data) => `<code class="fw-bold text-primary">${data}</code>`
                },
                {
                    data: 'guard_name',
                    render: (data) => `<span class="badge bg-secondary px-2">${data}</span>`
                },
                {
                    data: 'id',
                    className: 'text-center',
                    render: (data) => `
                        <div class="d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-outline-warning btn-edit-permission" data-id="${data}">
                                <iconify-icon icon="solar:pen-new-square-line-duotone"></iconify-icon>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-delete-permission" data-id="${data}">
                                <iconify-icon icon="solar:trash-bin-trash-line-duotone"></iconify-icon>
                            </button>
                        </div>`
                }
            ],
            dom: 'lfrtip',
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, "Semua"]
            ]
        });

        // 2. Simpan Permission (AJAX)
        $('#formAddPermission').on('submit', function(e) {
            e.preventDefault();
            let btn = $('#btnSavePermission');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $('#modalAddPermission').modal('hide');
                    $('#formAddPermission')[0].reset();
                    table.ajax.reload(null, false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: xhr.responseJSON?.message || 'Error occurred'
                    });
                },
                complete: function() {
                    btn.prop('disabled', false).text('Simpan');
                }
            });
        });

        // 3. Edit Permission - Munculkan Modal (Event Delegation)
        $(document).on('click', '.btn-edit-permission', function() {
            let id = $(this).data('id');
            let btn = $(this);
            btn.prop('disabled', true);

            $.get("{{ url('admin/permissions') }}/" + id, function(res) {
                $('#edit-perm-name').val(res.data.name);
                $('#edit-perm-guard').val(res.data.guard_name);
                $('#formEditPermission').attr('action', "{{ url('admin/permissions') }}/" + id);
                $('#modalEditPermission').modal('show');
            }).always(() => btn.prop('disabled', false));
        });

        // 4. Update Permission (AJAX)
        $('#formEditPermission').on('submit', function(e) {
            e.preventDefault();
            let btn = $('#btnUpdatePermission');
            let form = $(this);

            btn.prop('disabled', true).text('Updating...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST', // Kita tetap pakai POST di sini
                data: form.serialize(), // PENTING: serialize() akan mengirimkan field _method: PUT
                success: function(res) {
                    $('#modalEditPermission').modal('hide');
                    table.ajax.reload(null, false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    console.log(xhr.responseText); // Cek detail error di console F12
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops',
                        text: xhr.responseJSON?.message || 'Metode pengiriman tidak didukung.'
                    });
                },
                complete: function() {
                    btn.prop('disabled', false).text('Update');
                }
            });
        });

        // 5. Delete Permission (SweetAlert2)
        $(document).on('click', '.btn-delete-permission', function() {
            let id = $(this).data('id');
            Swal.fire({
                title: 'Hapus Permission?',
                text: "Aksi ini tidak dapat dibatalkan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('admin/permissions') }}/" + id,
                        type: 'POST',
                        data: {
                            _method: 'DELETE',
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(res) {
                            table.ajax.reload(null, false);
                            Swal.fire('Terhapus!', res.message, 'success');
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
@endsection