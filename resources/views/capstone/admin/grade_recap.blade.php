@extends('layouts.app')
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold">Rekap Nilai Capstone Design</h3>
            <p class="text-muted mb-0">Nilai per mahasiswa dan komponen penilaian.</p>
        </div>
        <button class="btn btn-outline-secondary" onclick="window.print()">Cetak</button>
    </div>
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-4">
            <select name="expertise_id" class="form-select">
                <option value="">Semua bidang keahlian</option>
                @foreach($expertises as $expertise)
                <option value="{{ $expertise->id }}" @selected(request('expertise_id') == $expertise->id)>{{ $expertise->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-primary">Filter</button></div>
        <div class="col-auto"><a class="btn btn-success" href="{{ route('capstone.admin.grade-recap', ['expertise_id' => request('expertise_id'), 'export' => 1]) }}">Export Excel</a></div>
    </form>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Kelompok / Judul</th><th>Bidang</th><th>Mahasiswa</th><th>Komponen</th><th>Kategori</th><th>Nilai</th></tr></thead>
            <tbody>
            @forelse($groups as $group)
                @foreach($group->studentScores as $score)
                <tr>
                    <td>{{ $group->title }}<br><small>{{ $group->leader?->name }}</small></td>
                    <td>{{ $group->expertise?->name }}</td>
                    <td>{{ $score->student?->name }}</td>
                    <td>{{ $score->component?->name }}</td>
                    <td>{{ ucfirst($score->component?->category ?? '') }}</td>
                    <td>{{ number_format((float) $score->score, 2) }}</td>
                </tr>
                @endforeach
            @empty
                <tr><td colspan="6" class="text-center">Belum ada data nilai.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
