@extends('layouts.auth')

@section('title', 'Persetujuan Akses SSO - Integrated Academic Management')

@section('content')
<div class="forny-form">
    <div class="mb-8 text-center forny-logo">
        <img src="../../assets/UNITRI.png" alt="Logo Instansi">
    </div>

    <div class="text-center mb-6">
        <h4>Sign In SSO</h4>
        <p class="mb-4">
            Aplikasi <strong class="text-primary">{{ $client->name }}</strong> meminta izin resmi untuk mengakses profil akun Anda.
        </p>
    </div>

    <div class="alert alert-info border-0 py-2 px-3 mb-6 text-start" style="background-color: #e8f4fd; color: #1a73e8; border-radius: 6px; font-size: 0.85rem;">
        <small>ℹ️ Aplikasi ini akan membaca nama, email, foto profil, dan hak akses aktif Anda.</small>
    </div>

    <div class="d-flex flex-column gap-3">

        <form method="POST" action="{{ route('passport.authorizations.approve') }}" class="w-100 m-0">
            @csrf
            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="btn btn-outline-success py-2.5 fw-semibold w-100 m-2" style="letter-spacing: 0.3px;">
                ✔️ Izinkan Akses
            </button>
        </form>

        <form method="POST" action="{{ route('passport.authorizations.deny') }}" class="w-100 m-0">
            @csrf
            @method('DELETE')
            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="btn btn-outline-danger py-2.5 fw-semibold w-100  m-2" style="letter-spacing: 0.3px;">
                ❌ Tolak & Batalkan
            </button>
        </form>

    </div>
</div>
@endsection