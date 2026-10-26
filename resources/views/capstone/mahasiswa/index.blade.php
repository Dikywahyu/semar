@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-slate-800 mb-1">Dashboard Capstone Design (Mahasiswa)</h3>
            <p class="text-secondary mb-0">Kelola kelompok, anggota, bimbingan, dan unggah berkas sidang akhir.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Form Pendaftaran / Detail Kelompok -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h5 class="fw-bold mb-3">Pendaftaran Kelompok & Keahlian</h5>
                <form id="formPendaftaranCapstone">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Judul Capstone Design</label>
                        <input type="text" name="title" class="form-control" required placeholder="Judul proyek...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pilih Keahlian</label>
                        <select name="expertise_id" class="form-select" required>
                            <option value="">-- Pilih Keahlian --</option>
                            @foreach($expertises ?? [] as $exp)
                            <option value="{{ $exp->id }}">{{ $exp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Anggota Tambahan (Maksimal 2 Orang)</label>
                        <select name="members[]" class="form-select select2-members" multiple>
                            <!-- List mahasiswa -->
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Daftarkan Kelompok</button>
                </form>
            </div>
        </div>

        <!-- Panel Status ACC & Upload Sidang -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h5 class="fw-bold mb-3">Status ACC & Berkas Sidang</h5>
                <div class="alert alert-secondary">
                    <ul class="mb-0 small">
                        <li>ACC Koordinator: <span id="acc-koor">Belum</span></li>
                        <li>ACC Pembimbing: <span id="acc-pembimbing">Belum</span></li>
                        <li>ACC KPS: <span id="acc-kps">Belum</span></li>
                    </ul>
                </div>
                <hr>
                <form id="formUploadSidang" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Upload Laporan Final (PDF)</label>
                        <input type="file" name="final_report" class="form-control" accept=".pdf">
                    </div>
                    <button type="submit" class="btn btn-success w-100">Unggah Berkas Sidang</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $('#formPendaftaranCapstone').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: "{{ route('capstone.mahasiswa.group.store') }}",
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                Swal.fire('Berhasil!', res.message, 'success');
            },
            error: function(err) {
                Swal.fire('Gagal', err.responseJSON.message || 'Terjadi kesalahan', 'error');
            }
        });
    });
</script>
@endpush
@endsection