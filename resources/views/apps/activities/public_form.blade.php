@extends('layouts.auth')

@section('title', 'Presensi Kehadiran - ' . $session->activity->title)

@push('styles')
<style>
    html,
    body {
        height: auto !important;
        overflow-y: auto !important;
    }

    body {
        min-height: 100vh !important;
        display: block !important;
        background-color: #f4f6f9;
    }

    .forny-container {
        min-height: 100vh !important;
        height: auto !important;
        display: block !important;
        padding: 30px 15px 120px 15px !important;
    }

    .forny-inner {
        margin: 0 auto !important;
        max-width: 520px !important;
    }

    .countdown-box {
        background: #f8f9fa;
        border: 2px dashed #0d6efd;
        border-radius: 12px;
        padding: 15px;
    }

    .timer-number {
        font-size: 1.8rem;
        font-weight: 700;
        color: #0d6efd;
    }
</style>
@endpush

@section('content')
<div class="forny-form">
    {{-- Header Logo --}}
    <div class="mb-4 text-center forny-logo">
        <img src="../../assets/UNITRI.png" alt="Logo" style="max-height: 60px;">
    </div>

    {{-- Info Kegiatan --}}
    <div class="text-center mb-4">
        <span class="badge badge-info mb-2 py-1 px-3">
            {{ $session->activity->activityType->name ?? 'Kegiatan' }}
        </span>
        <h4 class="mb-1 text-dark fw-bold">{{ $session->activity->title }}</h4>
        <p class="text-muted small mb-0">
            <strong>Sesi:</strong> {{ $session->session_name }}
        </p>
        <small class="text-muted d-block mt-1">
            📍 {{ $session->activity->location ?? 'Ruangan Utama' }} |
            ⏰ {{ \Carbon\Carbon::parse($session->start_time)->timezone('Asia/Jakarta')->format('H:i') }} - {{ \Carbon\Carbon::parse($session->end_time)->timezone('Asia/Jakarta')->format('H:i') }} WIB
        </small>
    </div>

    {{-- Alert Notification --}}
    @if (session('error') || isset($error))
    <div class="alert alert-danger py-2 mb-4 text-center small">
        {{ session('error') ?? $error }}
    </div>
    @endif

    @if (session('success'))
    <div class="alert alert-success py-3 mb-4 text-center">
        <strong>Berhasil!</strong><br>
        <small>{{ session('success') }}</small>
    </div>
    @else

    @php
    // Timestamp Unix dalam Milidetik untuk JavaScript Countdown
    $nowTimestamp = \Carbon\Carbon::now('Asia/Jakarta')->timestamp * 1000;
    $startTimestamp = \Carbon\Carbon::parse($session->start_time)->timezone('Asia/Jakarta')->timestamp * 1000;
    $endTimestamp = \Carbon\Carbon::parse($session->end_time)->timezone('Asia/Jakarta')->timestamp * 1000;

    $isNotStarted = $nowTimestamp < $startTimestamp;
        $isExpired=$nowTimestamp> $endTimestamp;
        @endphp

        {{-- KONDISI 1: JIKA PRESENSI BELUM DIBUKA (TAMPILKAN COUNTDOWN) --}}
        @if ($isNotStarted)
        <div class="card border-0 shadow-sm text-center p-4 my-3">
            <div class="mb-2">
                <span class="badge bg-warning text-dark px-3 py-1">⚠️ PRESENSI BELUM DIBUKA</span>
            </div>
            <p class="text-muted small mb-3">
                Formulir presensi akan otomatis terbuka pada jam
                <strong>{{ \Carbon\Carbon::parse($session->start_time)->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</strong>
            </p>

            {{-- Box Hitung Mundur --}}
            <div class="countdown-box my-2">
                <small class="text-muted d-block mb-1 font-weight-bold">Waktu Mundur Pembukaan:</small>
                <div id="countdown-timer" class="timer-number">00:00:00</div>
            </div>

            <small class="text-muted mt-2 d-block style=" font-size: 11px;">Halaman ini akan otomatis memuat ulang saat waktu tiba.</small>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const startTime = {
                    {
                        $startTimestamp
                    }
                };

                function updateCountdown() {
                    const now = new Date().getTime();
                    const distance = startTime - now;

                    if (distance <= 0) {
                        document.getElementById("countdown-timer").innerHTML = "00:00:00";
                        // Reload otomatis begitu waktu tiba
                        location.reload();
                        return;
                    }

                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    const h = hours < 10 ? "0" + hours : hours;
                    const m = minutes < 10 ? "0" + minutes : minutes;
                    const s = seconds < 10 ? "0" + seconds : seconds;

                    document.getElementById("countdown-timer").innerHTML = `${h}:${m}:${s}`;
                }

                updateCountdown();
                setInterval(updateCountdown, 1000);
            });
        </script>

        {{-- KONDISI 2: JIKA PRESENSI SUDAH DITUTUP --}}
        @elseif ($isExpired)
        <div class="card border-0 shadow-sm text-center p-4 my-3 bg-light">
            <div class="mb-2">
                <span class="badge bg-danger text-white px-3 py-1">🚫 PRESENSI TELAH DITUTUP</span>
            </div>
            <p class="text-muted small mb-0">
                Batas waktu presensi untuk sesi ini telah berakhir pada pukul
                <strong>{{ \Carbon\Carbon::parse($session->end_time)->timezone('Asia/Jakarta')->format('H:i') }} WIB</strong>.
            </p>
        </div>

        {{-- KONDISI 3: PRESENSI SEDANG BERLANGSUNG (TAMPILKAN FORM) --}}
        @else

        <form action="{{ route('attendances.public_submit', $session->qr_code_token) }}" method="POST">
            @csrf

            @auth
            <div class="alert alert-info py-2 px-3 mb-4 text-center small">
                Terdeteksi akun: <strong>{{ auth()->user()->email }}</strong>. Silakan sesuaikan data presensi Anda jika diperlukan.
            </div>
            @endauth

            @php
            $defaultName = old('guest_name', auth()->user()->name ?? '');
            $defaultEmail = old('guest_email', auth()->user()->email ?? '');
            $defaultPhone = old('guest_phone', auth()->user()->phone ?? '');
            $defaultGender = old('guest_gender', auth()->user()->gender ?? '');
            $defaultPosition = old('guest_position', auth()->user()->position_title ?? '');
            $defaultInst = old('guest_organization', auth()->user()->institution ?? '');
            @endphp

            <div class="form-group mb-3">
                <label class="form-label small text-muted mb-1">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                <input required class="form-control" name="guest_name" type="text" placeholder="Masukkan nama lengkap" value="{{ $defaultName }}">
            </div>

            <div class="row">
                <div class="col-md-6 form-group mb-3">
                    <label class="form-label small text-muted mb-1">Email <span class="text-danger">*</span></label>
                    <input required class="form-control" name="guest_email" type="email" placeholder="contoh@email.com" value="{{ $defaultEmail }}">
                </div>
                <div class="col-md-6 form-group mb-3">
                    <label class="form-label small text-muted mb-1">No. WhatsApp / HP <span class="text-danger">*</span></label>
                    <input required class="form-control" name="guest_phone" type="text" placeholder="081234567xxx" value="{{ $defaultPhone }}">
                </div>
            </div>

            <div class="form-group mb-3">
                <label class="form-label small text-muted mb-1">Jenis Kelamin <span class="text-danger">*</span></label>
                <div class="d-flex gap-3 mt-1">
                    <div class="custom-control custom-radio mr-4">
                        <input type="radio" id="gender_l" name="guest_gender" value="L" class="custom-control-input" required {{ $defaultGender == 'L' ? 'checked' : '' }}>
                        <label class="custom-control-label" for="gender_l">Laki-laki</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="gender_p" name="guest_gender" value="P" class="custom-control-input" required {{ $defaultGender == 'P' ? 'checked' : '' }}>
                        <label class="custom-control-label" for="gender_p">Perempuan</label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 form-group mb-3">
                    <label class="form-label small text-muted mb-1">Jabatan / Status</label>
                    <input class="form-control" name="guest_position" type="text" placeholder="Contoh: Dosen / Staff / Peserta" value="{{ $defaultPosition }}">
                </div>
                <div class="col-md-6 form-group mb-3">
                    <label class="form-label small text-muted mb-1">Instansi / Organisasi</label>
                    <input class="form-control" name="guest_organization" type="text" placeholder="Contoh: Universitas X / PT Y" value="{{ $defaultInst }}">
                </div>
            </div>

            <div class="mt-4 mb-5 pb-4">
                <button type="submit" class="btn btn-primary btn-block py-2 w-100 shadow-sm">
                    Kirim Presensi Kehadiran
                </button>
            </div>

        </form>

        @endif

        @endif
</div>
@endsection