@extends('layouts.app')

@section('title', 'Data Mahasiswa')

@section('content')
<div class="container-fluid py-4">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-2">Data Mahasiswa</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('pkl.admin.index') }}">PKL</a></li>
                            <li class="breadcrumb-item" aria-current="page">Data Mahasiswa</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-center"><img src="{{ asset('dist/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" /></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h5 class="fw-semibold mb-1">Master Mahasiswa</h5>
                    <p class="text-muted mb-0">Kelola data mahasiswa lokal untuk kebutuhan akademik dan PKL.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('master.mahasiswas.export') }}" class="btn btn-outline-success"><i class="ti ti-file-spreadsheet me-1"></i> Ekspor Excel</a>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalImportMahasiswa"><i class="ti ti-upload me-1"></i> Impor</button>
                    <button type="button" class="btn btn-outline-secondary" id="btn-generate-mahasiswa-users"><i class="ti ti-user-plus me-1"></i> Buat Akun Login</button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMahasiswa" id="btn-add-mahasiswa"><i class="ti ti-plus me-1"></i> Tambah Mahasiswa</button>
                    <a href="{{ route('pkl.admin.index') }}" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i> Kembali ke PKL</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="card-title mb-0">Daftar Mahasiswa</h5></div>
        <div class="card-body px-4">
            <div class="table-responsive">
                <table id="table-mahasiswas" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr><th>NIM</th><th>Nama Mahasiswa</th><th>Email</th><th>Program Studi</th><th>Angkatan</th><th>Telepon</th><th>Status</th><th class="text-center">Aksi</th></tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMahasiswa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="formMahasiswa" method="POST" action="{{ route('master.mahasiswas.store') }}" class="modal-content border-0 shadow-lg">
            @csrf
            <input type="hidden" name="_method" id="mahasiswa-method" value="POST">
            <input type="hidden" name="aktif" value="0">
            <div class="modal-header bg-primary text-white">
                <div><h5 class="modal-title text-white" id="modalMahasiswaTitle">Tambah Mahasiswa</h5><small class="text-white-50">Lengkapi informasi mahasiswa di bawah ini.</small></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">NIM <span class="text-danger">*</span></label><input class="form-control" name="nim" maxlength="30" required></div>
                    <div class="col-md-6"><label class="form-label">Nama Mahasiswa <span class="text-danger">*</span></label><input class="form-control" name="nama_mahasiswa" maxlength="255" required></div>
                    <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" maxlength="255"></div>
                    <div class="col-md-6"><label class="form-label">Program Studi <span class="text-danger">*</span></label><select class="form-select" name="prodi_id" required><option value="">Pilih program studi</option>@foreach($prodis as $prodi)<option value="{{ $prodi->id }}">{{ $prodi->kode_prodi }} - {{ $prodi->nama_prodi }} ({{ $prodi->jenjang }})</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Angkatan</label><input class="form-control" type="number" name="angkatan" min="1900" max="2100"></div>
                    <div class="col-md-4"><label class="form-label">Telepon</label><input class="form-control" name="telepon" maxlength="30"></div>
                    <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="aktif" value="1" checked id="mahasiswa-aktif"><label class="form-check-label" for="mahasiswa-aktif">Mahasiswa aktif</label></div></div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="btn-save-mahasiswa"><i class="ti ti-device-floppy me-1"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalImportMahasiswa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formImportMahasiswa" method="POST" action="{{ route('master.mahasiswas.import') }}" enctype="multipart/form-data" class="modal-content border-0 shadow-lg">
            @csrf
            <div class="modal-header bg-success text-white">
                <div><h5 class="modal-title text-white">Impor Data Mahasiswa</h5><small class="text-white-50">Pilih file Excel atau CSV untuk ditambahkan.</small></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4">
                <label class="form-label fw-semibold" for="mahasiswa-import-file">File data (.xlsx atau .csv)</label>
                <input class="form-control" type="file" name="file" id="mahasiswa-import-file" accept=".xlsx,.csv,.txt" required>
                <div class="form-text mt-2">Gunakan baris pertama sebagai header. NIM/email yang sudah ada tidak akan diubah; baris yang dilewati dilaporkan setelah impor.</div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success" id="btn-import-mahasiswa"><i class="ti ti-upload me-1"></i> Mulai Impor</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    const baseUrl = @json(url('admin/master/mahasiswas'));
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalMahasiswa'));
    const importModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalImportMahasiswa'));
    const table = $('#table-mahasiswas').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        ajax: { url: baseUrl, type: 'GET' },
        ordering: false,
        language: {
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_–_END_ dari _TOTAL_ mahasiswa',
            infoEmpty: 'Tidak ada data mahasiswa',
            zeroRecords: 'Mahasiswa tidak ditemukan',
            processing: 'Memuat data...',
            paginate: { previous: 'Sebelumnya', next: 'Berikutnya' }
        },
        columns: [
            { data: 'nim', render: safe },
            { data: 'nama_mahasiswa', render: value => `<div class="fw-semibold">${safe(value)}</div>` },
            { data: 'email', defaultContent: '—', render: safe },
            { data: 'prodi.nama_prodi', defaultContent: '—', render: safe },
            { data: 'angkatan', defaultContent: '—', render: safe },
            { data: 'telepon', defaultContent: '—', render: safe },
            { data: 'aktif', render: value => value ? '<span class="badge bg-success-subtle text-success rounded-pill px-3">Aktif</span>' : '<span class="badge bg-secondary-subtle text-secondary rounded-pill px-3">Nonaktif</span>' },
            { data: null, orderable: false, searchable: false, className: 'text-center text-nowrap', render: (data, type, row) => `<button type="button" class="btn btn-sm btn-light-primary btn-edit-mahasiswa me-1" data-id="${safe(row.id_registrasi_mahasiswa)}" title="Edit"><i class="ti ti-edit"></i></button><button type="button" class="btn btn-sm btn-light-danger btn-delete-mahasiswa" data-id="${safe(row.id_registrasi_mahasiswa)}" title="Hapus"><i class="ti ti-trash"></i></button>` }
        ],
        order: []
    });

    function safe(value) {
        if (value === null || value === undefined || value === '') return '—';
        return $('<span>').text(String(value)).html();
    }
    function showError(xhr) {
        const errors = xhr.responseJSON?.errors;
        const message = errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || 'Terjadi kesalahan. Silakan coba lagi.');
        Swal.fire({ icon: 'error', title: 'Gagal', text: message });
    }
    $('#btn-generate-mahasiswa-users').on('click', function () {
        Swal.fire({
            icon: 'warning',
            title: 'Buat akun mahasiswa?',
            html: 'Akun akan dibuat untuk mahasiswa aktif yang belum memiliki akun. Username dan password awal menggunakan NIM. Akun yang sudah ada tidak akan diubah.',
            showCancelButton: true,
            confirmButtonText: 'Ya, buat akun',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (!result.isConfirmed) return;
            const button = $('#btn-generate-mahasiswa-users').prop('disabled', true).html('<i class="ti ti-loader me-1"></i> Membuat...');
            $.ajax({
                url: @json(route('master.mahasiswas.generate-users')),
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            }).done(response => {
                table.ajax.reload(null, false);
                let details = `<p>${safe(response.created_count)} akun dibuat; ${safe(response.skipped_count)} data dilewati.</p>`;
                if (response.skipped?.length) {
                    details += '<div class="table-responsive text-start"><table class="table table-sm"><thead><tr><th>NIM</th><th>Alasan</th></tr></thead><tbody>';
                    response.skipped.forEach(item => { details += `<tr><td>${safe(item.identifier)}</td><td>${safe(item.reason)}</td></tr>`; });
                    details += '</tbody></table></div>';
                }
                Swal.fire({ icon: response.skipped_count ? 'warning' : 'success', title: response.message, html: details, width: 720 });
            }).fail(showError).always(() => button.prop('disabled', false).html('<i class="ti ti-user-plus me-1"></i> Buat Akun Login'));
        });
    });
    function resetMahasiswaForm() {
        $('#formMahasiswa')[0].reset();
        $('#formMahasiswa').attr('action', `${baseUrl}`);
        $('#mahasiswa-method').val('POST');
        $('#modalMahasiswaTitle').text('Tambah Mahasiswa');
        $('#mahasiswa-aktif').prop('checked', true);
    }
    $('#btn-add-mahasiswa').on('click', resetMahasiswaForm);

    $('#formMahasiswa').on('submit', function (event) {
        event.preventDefault();
        const button = $('#btn-save-mahasiswa').prop('disabled', true).text('Menyimpan...');
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            headers: { Accept: 'application/json' },
            data: $(this).serialize()
        }).done(response => {
            modal.hide();
            table.ajax.reload(null, false);
            Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message, timer: 1600, showConfirmButton: false });
        }).fail(showError).always(() => button.prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Simpan'));
    });

    $('#table-mahasiswas').on('click', '.btn-edit-mahasiswa', function () {
        const record = table.row($(this).closest('tr')).data();
        if (!record) return;
        resetMahasiswaForm();
        $('#modalMahasiswaTitle').text('Edit Data Mahasiswa');
        $('#formMahasiswa').attr('action', `${baseUrl}/${encodeURIComponent(record.id_registrasi_mahasiswa)}`);
        $('#mahasiswa-method').val('PUT');
        Object.entries(record).forEach(([key, value]) => $(`#formMahasiswa [name="${key}"]`).val(value ?? ''));
        $('#formMahasiswa input[type="hidden"][name="aktif"]').val(0);
        $('#mahasiswa-aktif').val(1);
        $('#mahasiswa-aktif').prop('checked', Boolean(record.aktif));
        modal.show();
    });

    $('#table-mahasiswas').on('click', '.btn-delete-mahasiswa', function () {
        const record = table.row($(this).closest('tr')).data();
        if (!record) return;
        Swal.fire({ title: 'Hapus data mahasiswa?', text: record.nama_mahasiswa, icon: 'warning', showCancelButton: true, confirmButtonColor: '#fa896b', confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal' }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({ url: `${baseUrl}/${encodeURIComponent(record.id_registrasi_mahasiswa)}`, method: 'POST', headers: { Accept: 'application/json' }, data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') } })
                .done(response => { table.ajax.reload(null, false); Swal.fire('Terhapus', response.message, 'success'); })
                .fail(showError);
        });
    });

    $('#formImportMahasiswa').on('submit', function (event) {
        event.preventDefault();
        const button = $('#btn-import-mahasiswa').prop('disabled', true).text('Mengimpor...');
        $.ajax({ url: $(this).attr('action'), method: 'POST', headers: { Accept: 'application/json' }, data: new FormData(this), processData: false, contentType: false })
            .done(response => {
                importModal.hide();
                this.reset();
                table.ajax.reload(null, false);
                let details = `<p>${safe(response.imported_count)} data ditambahkan; ${safe(response.skipped_count)} baris dilewati.</p>`;
                if (response.skipped?.length) {
                    details += '<div class="table-responsive text-start"><table class="table table-sm"><thead><tr><th>Baris</th><th>NIM</th><th>Alasan</th></tr></thead><tbody>';
                    response.skipped.forEach(item => { details += `<tr><td>${safe(item.rowNumber)}</td><td>${safe(item.identifier)}</td><td>${safe(item.reason)}</td></tr>`; });
                    details += '</tbody></table></div>';
                }
                Swal.fire({ icon: response.skipped_count ? 'warning' : 'success', title: 'Impor selesai', html: details, width: 720 });
            }).fail(showError).always(() => button.prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Mulai Impor'));
    });
});
</script>
@endpush
@endsection
