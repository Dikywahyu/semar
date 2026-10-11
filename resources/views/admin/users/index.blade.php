@extends('layouts.app')

@section('title', 'Monitoring User')

@section('content')
<div class="container-fluid">
    {{-- Breadcrumb tetap sama --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Monitoring User</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="../dashboard">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Monitoring User</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-center">
                    <img src="{{ asset('dist/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" />
                </div>
            </div>
        </div>
    </div>

    <div class="datatables">
        <div class="card">
            <div class="card-body">
                <div class="mb-3 d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0">Monitoring User</h4>
                    <button id="btn-reload-table" class="btn btn-primary btn-light-info d-flex align-items-center gap-2">
                        <i class="ti ti-refresh fs-4"></i> <span class="d-none d-md-block">Reload Data</span>
                    </button>
                </div>

                <div class="table-responsive">
                    {{-- Tambahkan class 'responsive' dan ganti ID agar match dengan JS --}}
                    <table id="user_table_advanced" class="table table-striped table-bordered display responsive nowrap align-middle w-100">
                        <thead>
                            <tr>
                                <th class="all">Pengguna</th> {{-- 'all' berarti selalu tampil --}}
                                <th class="min-tablet">Role</th>
                                <th class="min-tablet">Status</th>
                                <th class="none">Aktivitas Terakhir</th> {{-- 'none' otomatis masuk ke child row (hidden di mobile) --}}
                                <th class="all">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Fungsi Salin Global yang sudah diperbaiki
    function copyToClipboard(btn) {
        const copyText = document.getElementById("copyPwd");
        if (!copyText) return;

        navigator.clipboard.writeText(copyText.value).then(() => {
            Swal.showValidationMessage('Berhasil disalin!');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="ti ti-check"></i>';
            btn.classList.replace('btn-outline-primary', 'btn-success');
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.classList.replace('btn-success', 'btn-outline-primary');
            }, 2000);
        });
    }

    $(document).ready(function() {
        var table = $('#user_table_advanced').DataTable({
            responsive: true,
            processing: true,
            // Menentukan jumlah default saat pertama kali dimuat
            pageLength: 10,
            // Pilihan jumlah data yang bisa dipilih user
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Semua"]
            ],
            ajax: {
                url: "{{ route('admin.users.index') }}",
                type: "GET",
            },
            // Update DOM agar tombol Filter Kolom dan Dropdown Jumlah Data tampil berdampingan
            dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
            buttons: [{
                    extend: 'pageLength', // Tombol untuk memilih jumlah data
                    className: 'btn btn-outline-secondary btn-sm shadow-none me-2',
                },
                {
                    extend: 'colvis', // Tombol filter kolom yang tadi
                    text: '<i class="ti ti-layout-columns me-1"></i> Kolom',
                    className: 'btn btn-outline-secondary btn-sm shadow-none',
                }
            ],
            columns: [{
                    data: null,
                    responsivePriority: 1, // Prioritas utama tampil
                    render: function(data) {
                        let avatar = data.avatar ? data.avatar : `https://ui-avatars.com/api/?name=${encodeURIComponent(data.name)}&background=0085db&color=fff`;
                        return `
                        <div class="d-flex align-items-center">
                            <img src="${avatar}" class="rounded-circle me-3" width="35" height="35" />
                            <div>
                                <h6 class="fs-3 fw-semibold mb-0">${data.name}</h6>
                                <span class="fw-normal fs-2 text-muted d-none d-md-block">${data.email}</span>
                            </div>
                        </div>`;
                    }
                },
                {
                    data: 'role',
                    responsivePriority: 3,
                    render: function(role) {
                        // 1. Pengaman jika data role kosong atau null
                        if (!role) {
                            return `<span class="badge bg-secondary-subtle text-secondary fw-semibold fs-2 text-uppercase">User</span>`;
                        }
                        
                        // 2. Pastikan berbentuk array
                        let rolesArray = Array.isArray(role) ? role : [role];
                        
                        // 3. Kumpulan variasi warna badge yang tersedia di tema Modernize Bapak (Selain danger)
                        let randomColors = ['primary', 'success', 'warning', 'info'];
                        
                        // 4. Lakukan looping untuk membuat badge
                        let badges = rolesArray.map(function(singleRole) {
                            let checkRole = singleRole.trim();
                            let color;
                
                            // Tetap kunci Super Admin dan Admin di warna merah (danger)
                            if (checkRole === 'Super Admin' || checkRole === 'Admin') {
                                color = 'danger';
                            } else {
                                // Trik cerdas: Ambil index warna berdasarkan total nilai karakter kode teks (Hashing Sederhana)
                                // Ini membuat warna role "Dosen" akan selalu sama (misal: Hijau), tapi berbeda dengan "Karyawan" (misal: Ungu)
                                let charCodeSum = 0;
                                for (let i = 0; i < checkRole.length; i++) {
                                    charCodeSum += checkRole.charCodeAt(i);
                                }
                                let colorIndex = charCodeSum % randomColors.length;
                                color = randomColors[colorIndex];
                            }
                
                            return `<span class="badge bg-${color}-subtle text-${color} fw-semibold fs-2 text-uppercase me-1 mb-1">${singleRole}</span>`;
                        });
                        
                        // 5. Gabungkan semua badge
                        return badges.join('');
                    }
                },
                {
                    data: 'is_online',
                    responsivePriority: 4,
                    render: function(isOnline) {
                        return isOnline ?
                            `<span class="badge bg-success-subtle text-success fw-semibold fs-2">Online</span>` :
                            `<span class="badge bg-light text-muted fw-semibold fs-2">Offline</span>`;
                    }
                },
                {
                    data: 'last_seen',
                    visible: true
                }, // Ini akan masuk ke child row di HP karena class 'none' di header
                {
                    data: null,
                    responsivePriority: 2, // Aksi harus selalu tampil
                    orderable: false,
                    render: function(data) {
                        return `
                        <div class="dropdown dropstart">
                            <a href="javascript:void(0)" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ti ti-dots-vertical fs-6"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li><button class="dropdown-item d-flex align-items-center gap-3 btn-manage-role" data-id="${data.hashid}" data-name="${data.name}"><i class="fs-4 ti ti-lock"></i> Role</button></li>
                                <li>
                                    <button class="dropdown-item d-flex align-items-center gap-3 btn-manage-permission" data-id="${data.hashid}" data-name="${data.name}">
                                        <i class="fs-4 ti ti-lock-access"></i> Izin Khusus
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item d-flex align-items-center gap-3 btn-show-logs" 
                                            data-id="${data.hashid}" 
                                            data-name="${data.name}">
                                        <i class="fs-4 ti ti-history"></i> Log Aktivitas
                                    </button>
                                </li>
                                <li><button class="dropdown-item d-flex align-items-center gap-3 btn-show-credential" data-id="${data.hashid}" data-name="${data.name}"><i class="fs-4 ti ti-eye"></i> Password</button></li>
                                <li><button class="dropdown-item d-flex align-items-center gap-3 btn-reset-password" data-id="${data.hashid}"><i class="fs-4 ti ti-key"></i> Reset</button></li>
                            </ul>
                        </div>`;
                    }
                }
            ],
            language: {
                search: "",
                searchPlaceholder: "Cari...",
                lengthMenu: "_MENU_",
            }
        });

        $(document).on('click', '.btn-show-logs', function() {
            let hashid = $(this).data('id');
            let name = $(this).data('name');

            Swal.fire({
                title: `Log Aktivitas: ${name}`,
                html: `
            <div class="table-responsive text-start">
                <table id="table_user_logs" class="table table-bordered table-striped w-100" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>Waktu</th> 
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>`,
                width: '800px',
                showConfirmButton: true,
                confirmButtonText: 'Tutup',
                didOpen: () => {
                    // Inisialisasi DataTable di dalam modal
                    // Di dalam bagian didOpen: () => { ... }
                    $('#table_user_logs').DataTable({
                        processing: true,
                        serverSide: false,
                        ajax: {
                            url: `/admin/users/${hashid}/logs`,
                            dataSrc: 'logs'
                        },
                        autoWidth: false, // Matikan autowidth agar pengaturan kita diprioritaskan
                        columns: [{
                                data: 'created_at',
                                width: '15%'
                            },
                            {
                                data: 'properties',
                                width: '40%', // Kolom detail
                                render: function(data) {
                                    return `<div style="white-space: normal; font-size: 11px; max-height: 100px; overflow-y: auto;">${data}</div>`;
                                }
                            }
                        ],
                        order: [
                            [0, 'desc']
                        ],
                        language: {
                            search: "Cari:",
                            lengthMenu: "_MENU_",
                            info: "_TOTAL_ log"
                        },
                        pageLength: 5,
                        lengthMenu: [5, 10, 20]
                    });
                }
            });
        });

        $(document).on('click', '.btn-manage-permission', function() {
            let hashid = $(this).data('id');
            let name = $(this).data('name');

            // Tampilkan loading sebentar
            Swal.fire({
                title: 'Memuat...',
                didOpen: () => Swal.showLoading()
            });

            $.get(`/admin/users/${hashid}/permissions`, function(res) {
                let permOptions = '';
                let uniquePerms = [];

                res.all_permissions.forEach(perm => {
                    if (!uniquePerms.includes(perm.name)) {
                        uniquePerms.push(perm.name);

                        // Cek apakah user punya izin ini secara langsung
                        let checked = res.user_permissions.includes(perm.name) ? 'checked' : '';

                        permOptions += `
                <div class="form-check text-start mb-2">
                    <input class="form-check-input perm-checkbox" type="checkbox" 
                           value="${perm.name}" id="perm_${perm.id}" ${checked}>
                    <label class="form-check-label d-flex justify-content-between align-items-center w-100" for="perm_${perm.id}">
                        <span>${perm.name}</span>
                    </label>
                </div>`;
                    }
                });

                Swal.fire({
                    title: 'Izin Khusus: ' + name,
                    html: `
                <p class="text-muted fs-2 mb-3 text-start">Izin ini diberikan langsung ke individu (melampaui Role).</p>
                <div class="p-3 border rounded bg-light-subtle" style="max-height: 300px; overflow-y: auto;">
                    ${permOptions.length > 0 ? permOptions : '<span class="text-muted">Tidak ada data izin.</span>'}
                </div>`,
                    showCancelButton: true,
                    confirmButtonText: 'Simpan Izin',
                    preConfirm: () => {
                        let selectedPerms = [];
                        $('.perm-checkbox:checked').each(function() {
                            selectedPerms.push($(this).val());
                        });
                        return selectedPerms;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post(`/admin/users/${hashid}/permissions`, {
                            _token: "{{ csrf_token() }}",
                            permissions: result.value
                        }).done(() => {
                            Swal.fire('Berhasil', 'Izin khusus ' + name + ' diperbarui', 'success');
                        }).fail((xhr) => {
                            Swal.fire('Gagal', xhr.responseJSON.message || 'Gagal update izin', 'error');
                        });
                    }
                });
            });
        });

        // Event: Kelola Role
        $(document).on('click', '.btn-manage-role', function() {
            let hashid = $(this).data('id');
            let name = $(this).data('name');

            $.get(`/admin/users/${hashid}/roles`, function(res) {
                let roleOptions = '';
                let uniqueRoles = [];

                res.all_roles.forEach(role => {
                    // FILTER: Jika nama role belum pernah dimasukkan ke uniqueRoles
                    if (!uniqueRoles.includes(role.name)) {
                        uniqueRoles.push(role.name); // Tandai nama role sebagai sudah ada

                        let checked = res.user_roles.includes(role.name) ? 'checked' : '';

                        roleOptions += `
                        <div class="form-check text-start mb-2">
                            <input class="form-check-input role-checkbox" type="checkbox" 
                                value="${role.name}" 
                                id="role_${role.id}" 
                                ${checked}>
                            <label class="form-check-label d-flex justify-content-between align-items-center w-100" for="role_${role.id}">
                                <span>${role.name}</span>
                                </label>
                        </div>`;
                    }
                });

                Swal.fire({
                    title: 'Kelola Role: ' + name,
                    html: `<div class="p-3 border rounded bg-light-subtle" style="max-height: 300px; overflow-y: auto;">${roleOptions}</div>`,
                    showCancelButton: true,
                    confirmButtonText: 'Simpan Perubahan',
                    cancelButtonText: 'Batal',
                    preConfirm: () => {
                        let selectedRoles = [];
                        $('.role-checkbox:checked').each(function() {
                            selectedRoles.push($(this).val());
                        });
                        return selectedRoles;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post(`/admin/users/${hashid}/roles`, {
                                _token: "{{ csrf_token() }}",
                                roles: result.value
                            })
                            .done(() => {
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: 'Akses role ' + name + ' telah diperbarui.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                // Reload tabel otomatis agar kolom Role terupdate
                                $('#user_table_advanced').DataTable().ajax.reload(null, false);
                            })
                            .fail((xhr) => {
                                Swal.fire('Gagal', xhr.responseJSON.message || 'Terjadi kesalahan sistem', 'error');
                            });
                    }
                });
            });
        });

        // Event: Lihat Password
        $(document).on('click', '.btn-show-credential', function() {
            let hashid = $(this).data('id');
            let name = $(this).data('name');

            Swal.fire({
                title: 'Memuat...',
                didOpen: () => Swal.showLoading()
            });

            $.get(`/admin/users/${hashid}/credential`, function(res) {
                Swal.fire({
                    title: 'Kredensial',
                    html: `
                        <div class="text-start">
                            <p class="mb-1">User: <b>${name}</b></p>
                            <div class="input-group">
                                <input type="text" class="form-control" value="${res.plain_password}" id="copyPwd" readonly>
                                <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard(this)">Salin</button>
                            </div>
                        </div>`,
                    showConfirmButton: false
                });
            });
        });

        // Event: Reset Password
        $(document).on('click', '.btn-reset-password', function() {
            let hashid = $(this).data('id');
            Swal.fire({
                title: 'Reset Password?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Reset!',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return $.post(`/admin/users/${hashid}/reset-password`, {
                            _token: "{{ csrf_token() }}"
                        })
                        .fail(xhr => Swal.showValidationMessage('Gagal: ' + xhr.responseJSON.message));
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Password Baru',
                        html: `<code class="fs-7 fw-bold">${result.value.new_password}</code>`,
                        icon: 'success'
                    });
                }
            });
        });

        $('#btn-reload-table').on('click', function() {
            table.ajax.reload(null, false);
        });
    });
</script>
@endpush
@endsection