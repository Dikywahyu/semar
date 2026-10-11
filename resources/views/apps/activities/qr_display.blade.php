@extends('layouts.auth')

@section('title', 'Layar Presensi QR - ' . $session->activity->title)

@push('styles')
<style>
    /* 1. Bebaskan Scroll & Atur Centering */
    html,
    body {
        height: auto !important;
        min-height: 100vh !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        background-color: #f4f6f9 !important;
    }

    /* 2. Container Buka Kunci dengan Padding Bawah Ekstra */
    .forny-container {
        min-height: 100vh !important;
        height: auto !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 40px 15px 100px 15px !important;
        /* Padding bawah 100px agar link terbawah aman */
    }

    .forny-inner {
        width: 100% !important;
        max-width: 520px !important;
        margin: 0 auto !important;
    }

    .forny-form {
        margin-bottom: 30px !important;
        padding-bottom: 20px !important;
    }

    /* 3. Styling Khusus Link Alternatif Terbawah */
    .qr-link-container {
        margin-top: 25px !important;
        padding-top: 15px !important;
        margin-bottom: 30px !important;
        padding-bottom: 20px !important;
        word-break: break-all !important;
    }
</style>
@endpush

@section('content')
<div class="forny-form text-center py-2">
    {{-- Header Logo Instansi --}}
    <div class="mb-4 forny-logo">
        <img src="../../assets/UNITRI.png" alt="Logo" style="max-height: 55px;">
    </div>

    {{-- Category Badge --}}
    <div class="mb-3">
        <span class="badge badge-info px-3 py-1 text-uppercase" style="letter-spacing: 0.5px;">
            {{ $session->activity->activityType->name ?? 'Kegiatan' }}
        </span>
    </div>

    {{-- Judul Kegiatan & Sesi --}}
    <h3 class="fw-bold mb-1 text-dark">{{ $session->activity->title }}</h3>

    {{-- Box QR Code --}}
    <div class="card p-3 d-inline-block shadow-sm border rounded-lg bg-white my-2">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={{ urlencode($qrUrl) }}"
            alt="QR Code Presensi"
            class="img-fluid"
            style="width: 250px; height: 250px; object-fit: contain;">
    </div>

    {{-- Petunjuk --}}
    <div class="mt-3">
        <p class="mb-0 fw-bold text-dark fs-5">
            Pindai QR Code di Atas
        </p>
        <p class="text-muted small mb-0">
            Terbuka untuk Peserta Internal & Tamu Eksternal
        </p>
    </div>

    {{-- Info Lokasi & Batas Waktu --}}
    <div class="row mt-4 pt-3 border-top text-left small text-muted">
        <div class="col-6">
            <span class="d-block text-uppercase" style="font-size: 10px; font-weight: 700;">Lokasi:</span>
            <strong class="text-dark">{{ $session->activity->location ?? 'Ruangan Utama' }}</strong>
        </div>
        <div class="col-6 text-right">
            <span class="d-block text-uppercase" style="font-size: 10px; font-weight: 700;">Batas Absensi:</span>
            <strong class="text-primary">
                {{ \Carbon\Carbon::parse($session->start_time)->timezone('Asia/Jakarta')->format('H:i') }}
                -
                {{ \Carbon\Carbon::parse($session->end_time)->timezone('Asia/Jakarta')->format('H:i') }} WIB
            </strong>
        </div>
    </div>

    {{-- Direct Link Alternative (Beri Container Khusus dengan Margin) --}}
    <div class="qr-link-container">
        <small class="text-muted d-block mb-1" style="font-size: 11px;">Link alternatif presensi:</small>
        <a href="{{ $qrUrl }}" target="_blank" class="small text-primary font-weight-bold text-break">{{ $qrUrl }}</a>
    </div>

    <div style="height: 100px; display: block; width: 100%;"></div>
</div>
@endsection