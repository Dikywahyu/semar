@extends('layouts.app')

@section('title', 'Manajemen API Client')

@section('content')
<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Manajemen API Client</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">API Client</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3">
                    <div class="text-center mb-n5">
                        <img src="{{ asset('dist/assets/images/breadcrumb/ChatBc.png') }}" alt="modernize-img" class="img-fluid mb-n4" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="mb-3 d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Daftar Aplikasi Terdaftar</h4>
                <button class="btn btn-primary d-flex align-items-center gap-2" id="btn-add-client">
                    <i class="ti ti-plus fs-4"></i> Tambah Client
                </button>
            </div>
            <div class="table-responsive">
                <table id="table_api_clients" class="table table-bordered table-striped align-middle text-nowrap w-100">
                    <thead>
                        <tr> 
                            <th>Nama Aplikasi</th>
                            <th>Callback</th>
                            <th>Credentials</th>
                            <th>Status</th>
                            <th width="50">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Inisialisasi DataTable menggunakan path URL langsung
        const table = $('#table_api_clients').DataTable({
                        ajax: {
                            url: "{{ url('admin/api-clients') }}",
                            dataSrc: 'data'
                        },
                        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
                        buttons: [{
                            extend: 'pageLength',
                            className: 'btn btn-outline-secondary btn-sm shadow-none me-2',
                        }, {
                            extend: 'colvis',
                            text: '<i class="ti ti-layout-columns fs-4"></i> Atur Kolom',
                            className: 'btn btn-outline-primary',
                            columns: ':not(:first-child):not(:last-child)'
                        }],
                        columns: [
                            {
                                // Kolom 1: Nama Aplikasi + Proteksi Parsing Grant Types
                                data: null,
                                render: function(data) {
                                    let grantBadge = '-';
                                    
                                    if (data.grant_types) {
                                        try {
                                            // Jika data yang datang masih berbentuk String JSON, kita bongkar jadi Array
                                            let grants = typeof data.grant_types === 'string' 
                                                ? JSON.parse(data.grant_types) 
                                                : data.grant_types;
                                            
                                            if (Array.isArray(grants)) {
                                                grantBadge = grants.join(', ');
                                            }
                                        } catch (e) {
                                            // Jika gagal parsing, tampilkan teks mentahnya saja
                                            grantBadge = data.grant_types;
                                        }
                                    }
                            
                                    return `<div><strong>${data.name}</strong><br><small class="text-muted">Grant: ${grantBadge}</small></div>`;
                                }
                            },
                            {
                                // Kolom 2: Redirect URI + Trik Pembersihan Backslash ([\/])
                                data: 'redirect',
                                render: function(data) {
                                    if (!data) return '-';
                                    
                                    // Menghapus tanda [ ], " ", dan backslash \ yang merusak estetika URL
                                    let cleanUrl = data.replace(/[\[\]"'\\]/g, ''); 
                                    
                                    return `<a href="${cleanUrl}" target="_blank" class="text-primary text-decoration-underline fs-2">${cleanUrl}</a>`;
                                }
                            },
                            {
                                // Kolom 3: Credentials (Mengikuti schema Passport UUID: data.id)
                                data: null,
                                render: function(data) {
                                    return `
                                    <div class="fs-2">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <code>ID: ${data.id}</code>
                                            <a href="javascript:void(0)" class="text-primary btn-copy" data-value="${data.id}"><i class="ti ti-copy"></i></a>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <code>Sec: Terenkripsi (Bcrypt)</code>
                                        </div>
                                    </div>`;
                                }
                            },
                            {
                                // Kolom 4: Status Revoked (Aktif / Nonaktif)
                                data: 'revoked',
                                className: 'text-center',
                                render: function(data) {
                                    if (data == 1) {
                                        return `<span class="badge bg-danger-subtle text-danger">Revoked</span>`;
                                    }
                                    return `<span class="badge bg-success-subtle text-success">Active</span>`;
                                }
                            },
                            {
                                // Kolom 5: Tombol Aksi (Edit & Delete)
                                data: null,
                                orderable: false,
                                className: 'text-center',
                                render: function(data) {
                                    return `
                                    <div class="d-flex justify-content-center gap-2">
                                        <button class="btn btn-sm btn-light-warning text-warning btn-regenerate-secret" data-id="${data.id}" title="Regenerate Secret"><i class="ti ti-key fs-4"></i></button>
                                        <button class="btn btn-sm btn-light-info text-info btn-edit-client" data-id="${data.id}"><i class="ti ti-pencil fs-4"></i></button>
                                        <button class="btn btn-sm btn-light-danger text-danger btn-delete-client" data-id="${data.id}"><i class="ti ti-trash fs-4"></i></button>
                                    </div>`;
                                }
                            }
                        ],
                        language: {
                            url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json"
                        }
                    });
        // 0. FUNGSI REGENERATE SECRET KEY (JIKA LUPA)
        $(document).on('click', '.btn-regenerate-secret', function() {
            let id = $(this).data('id');
        
            Swal.fire({
                title: 'Regenerate Secret Key?',
                text: "Kunci rahasia lama aplikasi SIAKAD Anda tidak akan bisa digunakan lagi setelah ini diganti!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Ganti Kunci Baru!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-warning me-2',
                    cancelButton: 'btn btn-light'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Jalankan AJAX post menuju backend router
                    $.post("{{ url('admin/api-clients') }}/" + id + "/regenerate-secret", {
                        _token: "{{ csrf_token() }}"
                    })
                    .done((res) => {
                        // Tampilkan Modal Berhasil berisikan Plain Secret Key Baru
                        Swal.fire({
                            title: 'Kunci Rahasia Diperbarui!',
                            icon: 'success',
                            html: `
                                <div class="alert alert-success text-start fs-2 mb-3" role="alert">
                                    <i class="ti ti-check fw-bold"></i> Berhasil memperbarui kredensial client.
                                </div>
                                <div class="text-start">
                                    <p class="fs-2 text-muted mb-2">Silakan salin Secret Key yang baru ini ke file <code>.env</code> projek SIAKAD lokal Anda:</p>
                                    <div class="mb-2">
                                        <label class="fw-bold mb-1 fs-2">Client Secret Baru (Plain Key):</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control form-control-sm bg-light fs-2 fw-semibold text-danger" value="${res.client_secret}" readonly id="res-new-secret">
                                            <button class="btn btn-outline-secondary btn-sm btn-copy-swal" data-target="#res-new-secret" type="button"><i class="ti ti-copy"></i></button>
                                        </div>
                                    </div>
                                </div>
                            `,
                            confirmButtonText: 'Selesai',
                            customClass: { confirmButton: 'btn btn-success' },
                            buttonsStyling: false
                        });
                    })
                    .fail((err) => {
                        let errMsg = err.responseJSON?.message || 'Gagal memperbarui kunci rahasia.';
                        Swal.fire('Error', errMsg, 'error');
                    });
                }
            });
        });

        // 1. FUNGSI COPY TO CLIPBOARD
        $(document).on('click', '.btn-copy', function() {
            let text = $(this).data('value');
            navigator.clipboard.writeText(text).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Berhasil disalin!',
                    showConfirmButton: false,
                    timer: 1500
                });
            });
        });

        // 2. FUNGSI EDIT CLIENT 
        $(document).on('click', '.btn-edit-client', function() {
            let id = $(this).data('id');
        
            // Ambil data detail OAuth Client dari server IAM
            $.get("{{ url('admin/api-clients') }}/" + id, function(res) { 
                
                // SINKRONISASI: Ambil properti 'redirect' yang sudah disiapkan oleh Controller
                let rawRedirect = res.redirect || res.redirect_uris || '';
                let cleanRedirect = rawRedirect.replace(/^"|"$/g, '').replace(/\\/g, '');
        
                Swal.fire({
                    title: 'Edit API Client (SSO)',
                    html: `
                        <div class="text-start">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nama Aplikasi / Client</label>
                                <input type="text" id="edit-app-name" class="form-control" value="${res.name || ''}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Redirect URI (Callback URL)</label>
                                <input type="text" id="edit-redirect" class="form-control" value="${cleanRedirect}" placeholder="Contoh: http://127.0.0.1:8000/auth/sso/callback">
                                <small class="text-muted fs-2">Ubah ke URL localhost Anda jika sedang dalam tahap development.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Provider Utama</label>
                                <input type="text" id="edit-provider" class="form-control bg-light text-muted" value="${res.provider || 'users'}" readonly>
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Update Data',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-primary me-2',
                        cancelButton: 'btn btn-light'
                    },
                    buttonsStyling: false,
                    preConfirm: () => {
                        let name = $('#edit-app-name').val().trim();
                        let redirect = $('#edit-redirect').val().trim();
        
                        if (!name || !redirect) {
                            Swal.showValidationMessage('Nama dan Redirect URI tidak boleh dikosongkan!');
                            return false;
                        }
        
                        return {
                            name: name,
                            redirect: redirect,
                            _method: 'PUT' // Method Spoofing agar Route::put() di Laravel mendeteksi
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Kirim data pembaruan ke Controller
                        $.post("{{ url('admin/api-clients') }}/" + id, {
                            _token: "{{ csrf_token() }}",
                            ...result.value
                        })
                        .done((response) => {
                            Swal.fire('Berhasil', response.message || 'Data API Client berhasil diperbarui!', 'success');
                            if (typeof table !== 'undefined') {
                                table.ajax.reload(null, false);
                            }
                        })
                        .fail((err) => {
                            let errMsg = err.responseJSON?.message || 'Gagal memperbarui data client.';
                            Swal.fire('Error', errMsg, 'error');
                        });
                    }
                });
            });
        });

        // Form Tambah Client 
        $('#btn-add-client').click(function() {
            Swal.fire({
                title: 'Tambah API Client Baru (SSO)',
                html: `
                    <div class="text-start">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Aplikasi / Client</label>
                            <input type="text" id="swal-app-name" class="form-control" placeholder="Contoh: SIAKAD Utama">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Redirect URI (Callback URL)</label>
                            <input type="text" id="swal-redirect" class="form-control" placeholder="Contoh: http://127.0.0.1:8000/auth/sso/callback">
                            <small class="text-muted fs-2">Gunakan URL localhost jika Anda sedang mendevelop aplikasi client secara lokal.</small>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Simpan Client',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-primary me-2',
                    cancelButton: 'btn btn-light'
                },
                buttonsStyling: false,
                preConfirm: () => {
                    let name = $('#swal-app-name').val().trim();
                    let redirect = $('#swal-redirect').val().trim();
        
                    if (!name || !redirect) {
                        Swal.showValidationMessage('Nama Aplikasi dan Redirect URI wajib diisi!');
                        return false;
                    }
        
                    return {
                        name: name,
                        redirect: redirect
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Kirim data client baru ke backend controller
                    $.post("{{ url('admin/api-clients') }}", {
                        _token: "{{ csrf_token() }}",
                        ...result.value
                    })
                    .done((res) => {
                        
                        try {
                            // Susun format isi file JSON yang akan diunduh
                            let backupData = {
                                app_name: result.value.name,
                                client_id: res.client_id,
                                client_secret: res.client_secret,
                                redirect_uri: result.value.redirect,
                                created_at: new Date().toISOString().replace('T', ' ').substring(0, 19)
                            };
        
                            // Ubah objek menjadi string JSON yang rapi (dengan indentasi 4 spasi)
                            let jsonString = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(backupData, null, 4));
                            
                            // Buat elemen link darurat di memori browser untuk memicu download
                            let downloadAnchor = document.createElement('a');
                            downloadAnchor.setAttribute("href", jsonString);
                            
                            // Format nama file: sso-secret-[nama-app].json (nama file bersih dari spasi)
                            let cleanFileName = `sso-secret-${result.value.name.toLowerCase().replace(/[^a-z0-9]/g, '-')}.json`;
                            downloadAnchor.setAttribute("download", cleanFileName);
                            
                            // Sematkan ke dokumen, klik otomatis, lalu hapus kembali
                            document.body.appendChild(downloadAnchor);
                            downloadAnchor.click();
                            document.body.removeChild(downloadAnchor);
                        } catch (error) {
                            console.error("Gagal men-download file JSON kredensial:", error);
                        }
                        
                        // SINKRONISASI AKTIF: Tampilkan Client ID & Plain Secret Key dari Controller
                        Swal.fire({
                            title: 'Client Berhasil Dibuat!',
                            icon: 'success',
                            html: `
                                <div class="alert alert-warning text-start fs-2 mb-3" role="alert">
                                    <i class="ti ti-alert-triangle fw-bold"></i> <strong>Penting:</strong> Simpan Kunci Rahasia (Secret Key) ini sekarang. Kunci ini tidak akan ditampilkan lagi demi alasan keamanan!
                                </div>
                                <div class="text-start">
                                    <div class="mb-2">
                                        <label class="fw-bold mb-1 fs-2">Client ID (UUID):</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control form-control-sm bg-light fs-2" value="${res.client_id}" readonly id="res-client-id">
                                            <button class="btn btn-outline-secondary btn-sm btn-copy-swal" data-target="#res-client-id" type="button"><i class="ti ti-copy"></i></button>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="fw-bold mb-1 fs-2">Client Secret (Plain Key):</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control form-control-sm bg-light fs-2 fw-semibold text-danger" value="${res.client_secret}" readonly id="res-client-secret">
                                            <button class="btn btn-outline-secondary btn-sm btn-copy-swal" data-target="#res-client-secret" type="button"><i class="ti ti-copy"></i></button>
                                        </div>
                                    </div>
                                </div>
                            `,
                            confirmButtonText: 'Selesai',
                            customClass: { confirmButton: 'btn btn-success' },
                            buttonsStyling: false
                        });
                        
                        if (typeof table !== 'undefined') {
                            table.ajax.reload(null, false);
                        }
                    })
                    .fail((err) => {
                        let errMsg = err.responseJSON?.message || 'Gagal menyimpan API Client baru.';
                        Swal.fire('Error', errMsg, 'error');
                    });
                }
            });
        });
        
        // Helper Handler copy teks dari dalam box alert success
        $(document).on('click', '.btn-copy-swal', function() {
            let targetSelector = $(this).data('target');
            let textToCopy = $(targetSelector).val();
            navigator.clipboard.writeText(textToCopy).then(() => {
                $(this).html('<i class="ti ti-check text-success"></i>');
                setTimeout(() => { $(this).html('<i class="ti ti-copy"></i>'); }, 1500);
            });
        });

        // Toggle Status menggunakan path URL
        $(document).on('click', '.btn-toggle-status', function() {
            let id = $(this).data('id');
            $.post("{{ url('admin/api-clients') }}/" + id + "/toggle", {
                _token: "{{ csrf_token() }}"
            }).done((res) => {
                table.ajax.reload(null, false);
                Swal.fire({
                    icon: 'success',
                    title: res.message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            });
        });

        // Hapus Client menggunakan path URL
        $(document).on('click', '.btn-delete-client', function() {
            let id = $(this).data('id');
            Swal.fire({
                title: 'Hapus Client?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('admin/api-clients') }}/" + id, // Menggunakan url()
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(res) {
                            table.ajax.reload();
                            Swal.fire('Terhapus', res.message, 'success');
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
@endsection