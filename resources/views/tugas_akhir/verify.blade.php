@extends('layouts.app')
@section('content')
<div class="container-fluid py-4">
    <h3 class="fw-bold">Verifikasi Dokumen Tugas Akhir</h3>
    <p class="text-muted">Tindakan verifikasi dan peran akun dicatat untuk audit.</p>
    @forelse($applications as $application)
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
        <h5>{{ $application->student?->name }} — {{ $application->expertise_theme }}</h5>
        <small class="text-muted mb-2">Status: {{ $application->status }}</small>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Jenis Dokumen</th><th>File</th><th>Status</th><th>Catatan</th><th>Verifikasi</th></tr></thead>
                <tbody>
                @forelse($application->documents as $document)
                <tr>
                    <td>{{ str_replace('_', ' ', ucfirst($document->document_type)) }}</td>
                    <td><a href="{{ asset('storage/'.$document->file_path) }}" target="_blank" rel="noopener">Buka file</a></td>
                    <td>
                        {{ ucfirst($document->verification_status) }}
                        @if($document->verified_at)
                        <div class="small text-muted">oleh {{ $document->verifier?->name ?? 'akun dihapus' }} ({{ $document->verified_by_role }}) — {{ $document->verified_at->format('d-m-Y H:i') }}</div>
                        @endif
                    </td>
                    <td>{{ $document->verification_note ?? '-' }}</td>
                    <td>
                        <form class="verifyForm d-flex gap-1" data-application="{{ $application->id }}" data-document="{{ $document->id }}">
                            @csrf
                            <select class="form-select form-select-sm" name="decision"><option value="approved">Setujui</option><option value="rejected">Tolak</option></select>
                            <input class="form-control form-control-sm" name="note" placeholder="Catatan">
                            <button class="btn btn-sm btn-primary">Simpan</button>
                        </form>
                    </td>
                </tr>
                @empty<tr><td colspan="5" class="text-muted">Belum ada dokumen.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
    @empty<div class="alert alert-info">Belum ada pengajuan untuk semester aktif.</div>@endforelse
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $('.verifyForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        $.post("{{ url('tugas-akhir') }}/" + form.data('application') + "/dokumen/" + form.data('document') + "/verifikasi", form.serialize())
            .done(res => Swal.fire('Berhasil', res.message, 'success').then(() => location.reload()))
            .fail(err => Swal.fire('Gagal', err.responseJSON?.message || 'Verifikasi gagal.', 'error'));
    });
</script>
@endpush
@endsection
