@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h3 class="fw-bold text-slate-800 mb-1">Panel Koordinator Keahlian</h3>
        <p class="text-secondary mb-0">Plotting dosen pembimbing/penguji dan validasi menu KPI setuju/belum untuk ujian.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <h5 class="fw-bold mb-3">Daftar Kelompok di Keahlian Anda</h5>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Kelompok / Judul</th>
                        <th>Mahasiswa (Ketua & Anggota)</th>
                        <th>Plotting Dosen</th>
                        <th>KPI Ujian</th>
                        <th>Aksi Koordinator</th>
                    </tr>
                </thead>
                <tbody id="koordinatorTableBody">
                    <!-- Loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection