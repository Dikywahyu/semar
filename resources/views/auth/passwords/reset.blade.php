@extends('layouts.auth')

@section('title', 'Atur Ulang Password - Integrated Academic Management')

@section('content')
<div class="forny-form">
    <div class="mb-8 text-center forny-logo">
        <img src="{{ asset('templates/img/logo-03.svg') }}">
    </div>
    
    <div class="text-center">
        <h4>Password Baru</h4>
        <p class="mb-10">Silakan masukkan password baru Anda di bawah ini.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 px-3">
                @foreach ($errors->all() as $error)
                    <li><small>{{ $error }}</small></li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="form-group">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    </span>
                </div>
                <input required class="form-control" name="email" type="email" placeholder="Alamat Email" value="{{ $email ?? old('email') }}" readonly>
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </span>
                </div>
                <input required class="form-control" name="password" type="password" placeholder="Password Baru">
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </span>
                </div>
                <input required class="form-control" name="password_confirmation" type="password" placeholder="Konfirmasi Password Baru">
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="btn btn-primary btn-block">Perbarui Password</button>
        </div>
    </form>
</div>
@endsection