@extends('layouts.auth')

@section('title', 'Setup Keamanan - Integrated Academic Management')

@section('content')
<div class="forny-form">
    <div class="mb-8 text-center forny-logo">
        <img src="../../assets/UNITRI.png">
    </div>

    <div class="text-center">
        <h4>Aktifkan 2FA</h4>
        <p class="mb-6">Tingkatkan keamanan akun Anda dengan verifikasi dua langkah.</p>
    </div>

    <div class="text-center mb-6">
        <p class="small text-muted mb-3">1. Scan QR Code ini melalui aplikasi <strong>Google Authenticator</strong>:</p>
        <div class="d-inline-block p-3 bg-white border rounded">
            {!! $qrCodeUrl !!}
        </div>
        <p class="mt-3 small">Atau masukkan kode manual: <br><strong class="text-primary">{{ $secret }}</strong></p>
    </div>

    <form method="POST" action="{{ route('2fa.activate') }}">
        @csrf
        <p class="text-center small mb-3">2. Masukkan kode 6-digit untuk konfirmasi:</p>
        <div class="form-group">
            <div class="input-group">
                <input required class="form-control text-center" name="one_time_password" type="number" placeholder="000000">
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="btn btn-success btn-block">Aktifkan Keamanan</button>
            <a href="{{ route('dashboard') }}" class="btn btn-light btn-block mt-2">Batal</a>
        </div>
    </form>
</div>
@endsection