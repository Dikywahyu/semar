@extends('layouts.app')

@section('content')


<div class="container-fluid">
    {{-- Breadcrumb Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">User Roles & Permissions</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ url('/admin/dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Roles Management</li>
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
                        <iconify-icon icon="solar:user-speak-line-duotone" class="me-2 text-primary"></iconify-icon>
                        Daftar Roles
                    </h4>
                    <button type="button" class="btn btn-primary btn-sm d-flex align-items-center shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddRole">
                        <iconify-icon icon="solar:add-circle-line-duotone" class="me-1 fs-5"></iconify-icon>
                        Tambah Role
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="table-roles" class="table table-hover align-middle w-100">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">No</th>
                                    <th>Nama Role</th>
                                    <th>Guard</th>
                                    <th>Total Permissions</th>
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

{{-- MODAL TAMBAH ROLE --}}
<div class="modal fade" id="modalAddRole" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formAddRole" action="{{ url('admin/roles') }}" method="POST">
            @csrf
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title d-flex align-items-center text-white">Tambah Role Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Role</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: staff_akademik" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Guard Name</label>
                        <select name="guard_name" class="form-select">
                            <option value="web">web (Dashboard)</option>
                            <option value="api">api (SSO/Sanctum)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveRole">Simpan Role</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT ROLE & PERMISSIONS --}}
<div class="modal fade" id="modalEditRole" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="formEditRole" method="POST">
            @csrf @method('PUT')
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title d-flex align-items-center text-white">
                        <iconify-icon icon="solar:settings-line-duotone" class="me-2"></iconify-icon>
                        Konfigurasi Role & Akses
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="row">
                        <div class="col-md-5 border-end">
                            <h6 class="fw-bold mb-3">Informasi Utama</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Nama Role</label>
                                <input type="text" name="name" id="edit-name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Guard</label>
                                <input type="text" id="edit-guard-display" class="form-control bg-light" readonly>
                                <input type="hidden" name="guard_name" id="edit-guard">
                            </div>
                        </div>
                        <div class="col-md-7">
                            <h6 class="fw-bold mb-3 d-flex justify-content-between">
                                Pilih Permissions
                                <small class="text-primary" style="cursor: pointer" onclick="checkAllPerms()">Select All</small>
                            </h6>
                            <div id="permissions-container" class="row g-2 overflow-auto" style="max-height: 300px;">
                                {{-- Checkbox di-render via JS --}}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-white" id="btnUpdateRole">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // 1. Init DataTable
        var table = $('#table-roles').DataTable({
            processing: true,
            ajax: "{{ url('admin/roles') }}",
            columns: [{
                    data: null,
                    render: (data, type, row, meta) => meta.row + 1
                },
                {
                    data: 'name',
                    render: (data) => `<span class="fw-bold text-dark fs-3">${data}</span>`
                },
                {
                    data: 'guard_name',
                    render: (data) => `<span class="badge bg-primary-subtle text-primary px-2">${data}</span>`
                },
                {
                    data: 'permissions_count',
                    render: (data) => `<span class="badge bg-light text-dark border">${data || 0} Hak Akses</span>`
                },
                {
                    data: 'id',
                    className: 'text-center',
                    render: function(data, type, row) {
                        let buttons = `<div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-warning btn-edit-role shadow-sm" data-id="${data}" title="Edit Role & Permissions">
                                <iconify-icon icon="solar:settings-minimalistic-line-duotone"></iconify-icon>
                            </button>`;

                        if (row.name !== 'admin' && row.name !== 'super-admin') {
                            buttons += `
                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-role shadow-sm" data-id="${data}" title="Hapus Role">
                                <iconify-icon icon="solar:trash-bin-trash-line-duotone"></iconify-icon>
                            </button>`;
                        }
                        buttons += `</div>`;
                        return buttons;
                    }
                }
            ]
        });

        // 2. Tambah Role (AJAX)
        $('#formAddRole').on('submit', function(e) {
            e.preventDefault();
            let btn = $('#btnSaveRole');
            btn.prop('disabled', true).html('Menyimpan...');

            $.post($(this).attr('action'), $(this).serialize(), function(res) {
                $('#modalAddRole').modal('hide');
                $('#formAddRole')[0].reset();
                table.ajax.reload(null, false);
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                });
            }).fail(xhr => {
                Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
            }).always(() => btn.prop('disabled', false).html('Simpan Role'));
        });

        // 3. Edit Role - Load Data & Permissions
        $(document).on('click', '.btn-edit-role', function() {
            let id = $(this).data('id');
            let btn = $(this);
            btn.prop('disabled', true);

            $.get("{{ url('admin/roles') }}/" + id, function(res) {
                let role = res.data.role;
                let allPerms = res.data.all_permissions;
                let activePerms = res.data.role_permissions;

                console.log(res);

                $('#edit-name').val(role.name);
                $('#edit-guard').val(role.guard_name);
                $('#edit-guard-display').val(role.guard_name);
                $('#formEditRole').attr('action', "{{ url('admin/roles') }}/" + id);

                // Render Permissions
                let html = '';
                allPerms.forEach(p => {
                    let checked = activePerms.includes(p.id) ? 'checked' : '';
                    html += `
                        <div class="col-4">
                            <div class="form-check form-switch card p-2 mb-0 border shadow-none bg-light-subtle">
                                <input class="form-check-input" style="margin-left: 2.7rem;" type="checkbox" name="permissions[]" 
                                       value="${p.name}" id="p-${p.id}" ${checked}>
                                <label class="form-check-label fs-2 text-dark fw-semibold  text-center" for="p-${p.id}">${p.name}</label>
                            </div>
                        </div>`;
                });
                $('#permissions-container').html(html || '<p class="text-center text-muted fs-2">Belum ada permission di guard ini</p>');
                $('#modalEditRole').modal('show');
            }).always(() => btn.prop('disabled', false));
        });

        // 4. Update Role & Sync Permissions
        $('#formEditRole').on('submit', function(e) {
            e.preventDefault();
            let btn = $('#btnUpdateRole');
            btn.prop('disabled', true).html('Updating...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $('#modalEditRole').modal('hide');
                    table.ajax.reload(null, false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    Swal.fire('Gagal', xhr.responseJSON?.message || 'Cek kembali inputan', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html('Simpan Perubahan');
                }
            });
        });

        // 5. Delete Role
        $(document).on('click', '.btn-delete-role', function() {
            let id = $(this).data('id');
            Swal.fire({
                title: 'Hapus Role?',
                text: "User dengan role ini mungkin kehilangan akses!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#fa896b',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post("{{ url('admin/roles') }}/" + id, {
                        _method: 'DELETE',
                        _token: "{{ csrf_token() }}"
                    }, function(res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Dihapus!', res.message, 'success');
                    });
                }
            });
        });
    });

    function checkAllPerms() {
        let allChecked = true;
        $('#permissions-container input').each(function() {
            if (!$(this).prop('checked')) allChecked = false;
        });
        $('#permissions-container input').prop('checked', !allChecked);
    }
</script>
@endpush
@endsection