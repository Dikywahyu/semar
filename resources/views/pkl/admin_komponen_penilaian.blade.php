@extends('layouts.app') {{-- Sesuaikan dengan layout utama Anda --}}

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="fw-bold text-slate-800">Master Komponen Penilaian Seminar PKL</h3>
                <a href="{{ route('pkl.admin.index') }}" class="btn btn-outline-secondary btn-sm">Kembali ke Dashboard</a>
            </div>
        </div>

        <!-- Filter Semester -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold mb-3">Pilih Semester Akademik</h5>
                <form action="{{ route('pkl.admin.komponen.index') }}" method="GET">
                    <div class="mb-3">
                        <select name="id_semester" class="form-select select2" onchange="this.form.submit()" {{ $semesters->isEmpty() ? 'disabled' : '' }}>
                            @foreach($semesters as $sem)
                                <option value="{{ $sem->id_semester }}" {{ $selectedSemester == $sem->id_semester ? 'selected' : '' }}>
                                    {{ $sem->nama_semester }} {{ $sem->a_periode_aktif == 1 ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>

                <hr class="my-4">

                <!-- Form Tambah Komponen Baru -->
                <h6 class="fw-bold mb-3">Tambah Komponen Nilai</h6>
                @if(!$selectedSemesterMasterId)
                    <div class="alert alert-warning mb-0">Tambahkan semester pada menu Master Data dan aktifkan salah satunya sebelum mengatur komponen penilaian.</div>
                @else
                <form id="formTambahKomponen" action="{{ route('pkl.admin.komponen.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="semester_master_id" value="{{ $selectedSemesterMasterId }}">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Komponen</label>
                        <input type="text" name="nama_komponen" class="form-control" placeholder="Cth: Penguasaan Materi / Laporan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Bobot (%)</label>
                        <input type="number" step="0.01" name="bobot" class="form-control" placeholder="Cth: 30" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-semibold">Simpan Komponen</button>
                </form>
                @endif
            </div>
        </div>

        <!-- Tabel Daftar Komponen & Cek Total Bobot -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0">Daftar Komponen (Semester: {{ $semesters->firstWhere('kode_semester', $selectedSemester)?->nama_semester ?? 'Belum dipilih' }})</h5>
                    <div>
                        <span class="badge {{ $totalBobot == 100 ? 'bg-success' : 'bg-warning text-dark' }}">
                            Total Bobot: {{ $totalBobot }}%
                        </span>
                    </div>
                </div>

                @if($totalBobot != 100)
                    <div class="alert alert-warning small py-2">
                        <i class="ti ti-alert-circle me-1"></i> Peringatan: Total bobot komponen untuk semester ini harus tepat 100% agar perhitungan nilai akhir valid.
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama Komponen</th>
                                <th>Bobot</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($komponens as $index => $komp)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="fw-semibold">{{ $komp->nama_komponen }}</td>
                                    <td>{{ $komp->bobot }}%</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-komponen" data-id="{{ $komp->id }}">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada komponen penilaian untuk semester ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // AJAX Tambah Komponen
    $('#formTambahKomponen').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            success: function(res) {
                Swal.fire({icon: 'success', title: 'Berhasil', text: res.message, timer: 1500, showConfirmButton: false})
                    .then(() => location.reload());
            },
            error: function(xhr) {
                let msg = xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || {}).flat().join('\n') || 'Terjadi kesalahan.';
                Swal.fire('Gagal', msg, 'error');
            }
        });
    });

    // AJAX Hapus Komponen
    $(document).on('click', '.btn-hapus-komponen', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Hapus komponen ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('pkl/admin/komponen-penilaian') }}/" + id,
                    type: 'DELETE',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    success: function(res) {
                        Swal.fire({icon: 'success', title: 'Terhapus', text: res.message, timer: 1200, showConfirmButton: false})
                            .then(() => location.reload());
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Komponen gagal dihapus.', 'error');
                    }
                });
            }
        });
    });
</script>
@endpush