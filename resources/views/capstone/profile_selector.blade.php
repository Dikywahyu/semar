@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" style="min-height:100vh;background:#f5f7fb">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-6">
            <section class="card border-0 rounded-4 p-4 p-md-5 shadow-sm">
                <span class="badge rounded-pill bg-primary-subtle text-primary align-self-start mb-3">Capstone Design</span>
                <h2 class="fw-bold text-slate-800 mb-2">{{ $title }}</h2>
                <p class="text-secondary mb-4">{{ $description }}</p>

                @if($profiles->isEmpty())
                    <div class="alert alert-warning mb-0">Belum ada profil {{ $jenis }} aktif yang dapat dipilih.</div>
                @else
                    <form method="GET" action="{{ $route }}">
                        <label class="form-label fw-semibold" for="profile-choice">{{ ucfirst($jenis) }}</label>
                        <select class="form-select form-select-lg mb-3" name="{{ $parameter }}" id="profile-choice" required>
                            <option value="">Pilih {{ $jenis }}...</option>
                            @foreach($profiles as $profile)
                                <option value="{{ $profile->getKey() }}">{{ $label($profile) }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary w-100 fw-semibold" type="submit">Buka Dashboard {{ ucfirst($jenis) }}</button>
                    </form>
                @endif
            </section>
        </div>
    </div>
</div>
@endsection
