@extends('layouts.auth')

@section('title', 'Verifikasi Keamanan - Integrated Academic Management')

@section('content')
<div class="forny-form">
    <div class="mb-8 text-center forny-logo">
        <img src="../../assets/UNITRI.png">
    </div>

    <div class="text-center">
        <h4>Dua Faktor Autentikasi</h4>
        <p class="mb-10">Buka aplikasi <strong>Google Authenticator</strong> Anda dan masukkan kode 6-digit.</p>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger py-2 text-center">
        <small>{{ $errors->first() }}</small>
    </div>
    @endif

    <form method="POST" action="{{ route('2fa.verify') }}">
        @csrf
        <div class="form-group">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="5" y="11" width="14" height="10" rx="2" ry="2"></rect>
                            <circle cx="12" cy="16" r="1"></circle>
                            <path d="M8 11V7a4 4 0 0 1 8 0v4"></path>
                        </svg>
                    </span>
                </div>
                <input required class="form-control text-center" name="one_time_password" type="number"
                    placeholder="000000" style="font-size: 1.5rem; letter-spacing: 5px; font-weight: bold;" autofocus>
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="btn btn-primary btn-block">Verifikasi Sekarang</button>
        </div>
    </form>

    <div class="text-center mt-8">
        <a href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
            class="text-muted">
            <small>Gunakan akun lain? Keluar</small>
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    </div>
</div>
@endsection