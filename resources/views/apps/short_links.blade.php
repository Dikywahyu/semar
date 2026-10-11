@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- 1. BREADCRUMBS & BANNER ASLI TEMPLATE --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Monitoring User</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="../dashboard">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Short Links</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-center">
                    <img src="{{ asset('dist/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" />
                </div>
            </div>
        </div>
    </div>

    {{-- Notifikasi Alert Sistem --}}
    @if(session('success_msg'))
    <div class="alert alert-success border-0 shadow-sm">{{ session('success_msg') }}</div>
    @endif
    @if(session('error_msg'))
    <div class="alert alert-danger border-0 shadow-sm">{{ session('error_msg') }}</div>
    @endif

    <div class="row">
        {{-- PANEL KIRI: FORM SINGLE-PAGE UNTUK ADD NEW LINK --}}
        <div class="col-12 col-lg-4">
            <div class="card border shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title fw-bold text-dark mb-0 fs-4">Buat Tautan Pendek</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('short-links.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">URL Tujuan (Panjang)</label>
                            <input type="url" name="destination_url" class="form-control" placeholder="https://unitri.ac.id/modul/dokumen/verify/xyz123" required value="{{ old('destination_url') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Custom Kode Pendek (Opsional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fs-2">/s/</span>
                                <input type="text" name="short_code" class="form-control" placeholder="diky-sign" value="{{ old('short_code') }}">
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">Kosongkan jika ingin dibuat acak oleh sistem.</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-1">
                            <i class="ti ti-link fs-4"></i> Generate Tautan
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border shadow-sm mt-4">
                <div class="card-header bg-light">
                    <h5 class="card-title fw-bold text-dark mb-0 fs-4">QR Generator Instan</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Masukkan URL</label>
                        <input type="url" id="instant-url" class="form-control" placeholder="https://example.com">
                    </div>
                    <button type="button" id="btn-generate-instant" class="btn btn-success w-100">
                        <i class="ti ti-qrcode fs-4"></i> Generate QR
                    </button>
                </div>
            </div>
        </div>

        {{-- PANEL KANAN: TABEL RESOURCE DAFTAR LINK & STATISTIK --}}
        <div class="col-12 col-lg-8">
            <div class="card border shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title fw-bold text-dark mb-0 fs-4">Daftar Link Anda</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Tautan Pendek</th>
                                    <th>URL Asli</th>
                                    <th class="text-center">Klik</th>
                                    <th class="text-end pe-3">Aksi</th>
                                    <th class="text-center">QR Code</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($links->isEmpty())
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada short link yang Anda buat.</td>
                                </tr>
                                @else
                                @foreach($links as $link)
                                @php $fullUrl = url('/s/' . $link->short_code); @endphp
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="{{ $fullUrl }}" target="_blank" class="fw-bold text-primary">{{ '/s/' . $link->short_code }}</a>
                                            <button class="btn btn-sm btn-light p-1 rounded btn-copy" data-url="{{ $fullUrl }}" title="Salin Link">
                                                <i class="ti ti-copy fs-3 text-muted"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-muted text-truncate" style="max-width: 250px;" title="{{ $link->destination_url }}">
                                            {{ $link->destination_url }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info px-2 py-1 rounded fw-bold">{{ $link->clicks_count }} x</span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end gap-1">
                                            {{-- Tombol Pemicu Modal Edit --}}
                                            <button class="btn btn-sm btn-light-info text-info p-1 btn-edit-trigger" data-id="{{ $link->id }}" data-code="{{ $link->short_code }}" data-dest="{{ $link->destination_url }}">
                                                <i class="ti ti-pencil fs-4"></i>
                                            </button>

                                            {{-- Form Hapus Resource --}}
                                            <form action="{{ route('short-links.destroy', $link->id) }}" method="POST" class="d-inline form-delete">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light-danger text-danger p-1">
                                                    <i class="ti ti-trash fs-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-light-primary text-primary p-1 btn-qr-trigger"
                                            data-id="{{ $link->id }}"
                                            data-url="{{ url('/s/' . $link->short_code) }}"
                                            title="Lihat QR Code">
                                            <i class="ti ti-qrcode fs-4"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL POPUP: QR CODE --}}
<div class="modal fade" id="qrCodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg text-center">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <h6 class="fw-bold mb-3">Scan QR Code</h6>
                {{-- Tambahkan min-height agar tidak "loncat" saat load --}}
                <div id="qr-loader" class="mb-3" style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <img id="qr-image-display" src="" alt="QR Code" class="img-fluid mb-3" style="display:none; max-width: 200px;">
                <p class="text-muted small text-truncate" id="qr-url-text"></p>
                <button type="button" id="btn-download-qr" class="btn btn-sm btn-primary w-100 mt-2">
                    <i class="ti ti-download"></i> Unduh QR Code
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL POPUP: EDIT DETAIL TAUTAN --}}
<div class="modal fade" id="editLinkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title fw-bold text-warning">Edit Tautan Pendek</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-update-link" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ubah URL Tujuan</label>
                        <input type="url" id="edit-dest-url" name="destination_url" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ubah Kode Pendek</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">/s/</span>
                            <input type="text" id="edit-short-code" name="short_code" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-white">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // 1. FITUR COPY LINK KUSTOM KE CLIPBOARD
        $('.btn-copy').click(function() {
            let urlText = $(this).data('url');
            navigator.clipboard.writeText(urlText).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Link berhasil disalin!',
                    timer: 800,
                    showConfirmButton: false
                });
            });
        });

        $('#btn-download-qr').click(function() {
            let imgElement = $('#qr-image-display')[0];
            let canvas = document.createElement('canvas');
            let ctx = canvas.getContext('2d');

            // Set ukuran canvas (sesuaikan dengan ukuran yang Anda inginkan, misal 500x500)
            canvas.width = 500;
            canvas.height = 500;

            let img = new Image();
            img.src = imgElement.src;

            img.onload = function() {
                // Beri warna latar putih (karena SVG biasanya transparan)
                ctx.fillStyle = "#ffffff";
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                // Gambar QR ke canvas
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                // Ekspor ke PNG dan download
                let dataUrl = canvas.toDataURL('image/png');
                let link = document.createElement('a');
                link.download = 'qrcode_' + new Date().getTime() + '.png';
                link.href = dataUrl;
                link.click();
            };
        });

        // 2. TRIGGER MODAL EDIT & PARSING DATA
        $('.btn-edit-trigger').click(function() {
            let id = $(this).data('id');
            let code = $(this).data('code');
            let dest = $(this).data('dest');

            // Set action form URL secara dinamis ke route resource update
            $('#form-update-link').attr('action', `/short-links/${id}`);
            $('#edit-dest-url').val(dest);
            $('#edit-short-code').val(code);

            $('#editLinkModal').modal('show');
        });

        // 3. PROPROTEKSI COMFIRMATION SWEETALERT SAAT HAPUS LINK
        $('.form-delete').submit(function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Hapus Short Link?',
                text: "Tautan pendek ini tidak akan bisa diakses lagi oleh publik.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });

        // 4. TRIGGER MODAL QR (Tambahkan di dalam $(document).ready)
        $('.btn-qr-trigger').click(function() {
            let id = $(this).data('id');
            let url = $(this).data('url');

            // Reset modal
            $('#qr-image-display').hide();
            $('#qr-loader').show();
            $('#qrCodeModal').modal('show');

            $.ajax({
                url: `/short-links/${id}/qr-data`,
                type: 'GET',
                success: function(response) {
                    $('#qr-loader').hide();
                    $('#qr-image-display').attr('src', response.data).show();
                    $('#qr-url-text').text(url);
                    $('#download-qr').attr('href', response.data);
                },
                error: function() {
                    $('#qrCodeModal').modal('hide');
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal memuat QR Code'
                    });
                }
            });
        });

        // 5. GENERATE QR INSTAN 
        $('#btn-generate-instant').click(function() {
            let url = $('#instant-url').val();

            if (!url) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Masukkan URL terlebih dahulu!'
                });
                return;
            }

            $('#qr-image-display').hide();
            $('#qr-loader').show();
            $('#qrCodeModal').modal('show');
            $('#qr-url-text').text(url);

            $.ajax({
                url: '{{ route("qr.generate-instant") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    url: url
                },
                success: function(response) {
                    $('#qr-loader').hide();
                    $('#qr-image-display').attr('src', response.data).show();
                },
                error: function(xhr) { // <--- Tambahkan parameter xhr
                    $('#qrCodeModal').modal('hide');

                    // Mengambil pesan error asli dari response Laravel
                    let errorMessage = xhr.responseJSON?.message || 'Gagal generate QR';

                    Swal.fire({
                        icon: 'error',
                        title: 'Error 500 (Server Error)',
                        text: errorMessage
                    });
                }
            });
        });
    });
</script>
@endpush