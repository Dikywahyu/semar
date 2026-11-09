@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-6">
            <section class="card border-0 rounded-4 p-4 p-md-5 shadow-sm">
                <span class="badge rounded-pill bg-primary-subtle text-primary align-self-start mb-3">Tugas Akhir</span>
                <h2 class="fw-bold mb-2">{{ $title }}</h2>
                <p class="text-secondary mb-4">{{ $description }}</p>

                @if($profiles->isEmpty())
                    <div class="alert alert-warning mb-0">Belum ada profil {{ $kind }} yang dapat dipilih.</div>
                @else
                    <form method="GET" action="{{ $route }}">
                        <label class="form-label fw-semibold" for="profile-choice">{{ ucfirst($kind) }}</label>
                        <select class="form-select form-select-lg mb-3" name="{{ $parameter }}" id="profile-choice" required>
                            <option value="">Pilih {{ $kind }}...</option>
                            @foreach($profiles as $profile)
                                <option value="{{ $profile->getKey() }}">
                                    @if($kind === 'mahasiswa')
                                        {{ $profile->name }}{{ $profile->identifier_number ? ' (' . $profile->identifier_number . ')' : '' }}
                                    @else
                                        {{ $profile->nama_dosen }}{{ $profile->nidn ? ' (' . $profile->nidn . ')' : '' }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary w-100 fw-semibold" type="submit">Buka Dashboard {{ ucfirst($kind) }}</button>
                    </form>
                @endif
            </section>
        </div>
    </div>
</div>
@endsection
