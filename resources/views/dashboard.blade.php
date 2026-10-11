@extends('layouts.app')

@section('title', 'Dashboard Utama')

@section('content')
<div class="row">
    <div class="col-lg-12 col-xl-8 d-flex align-items-stretch">
        <div class="card w-100 bg-primary-subtle overflow-hidden shadow-none">
            <div class="card-body position-relative">
                <div class="row align-items-center">
                    <div class="col-sm-7">
                        <div class="d-flex align-items-center mb-7">
                            <div class="rounded-circle overflow-hidden me-6 border border-2 border-white shadow-sm">
                                <img src="{{ Auth::user()->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode(Auth::user()->name).'&background=0085db&color=fff' }}"
                                    alt="user-img" width="45" height="45" style="object-fit: cover;">
                            </div>
                            <div>
                                <h5 class="fw-semibold mb-0 fs-5">Selamat datang kembali,</h5>
                                <h5 class="fw-bold mb-0 text-primary">{{ Auth::user()->name }}</h5>
                            </div>
                        </div>

                        <div class="d-flex align-items-center mb-4">
                            <div class="border-end pe-4 border-muted border-opacity-20">
                                <h3 class="mb-1 fw-bold fs-8 d-flex align-items-center text-primary" id="liveClock">
                                    00:00:00
                                </h3>
                                <p class="mb-0 text-muted fs-2"><i class="ti ti-clock me-1"></i> Waktu Server (WIB)</p>
                            </div>
                            <div class="ps-4">
                                <h3 class="mb-1 fw-semibold fs-5 d-flex align-items-center text-primary text-truncate" style="max-width: 150px;">
                                    {{ request()->header('User-Agent') && str_contains(request()->header('User-Agent'), 'Mobile') ? 'Mobile Device' : 'Desktop Browser' }}
                                </h3>
                                <p class="mb-0 text-muted fs-2"><i class="ti ti-device-laptop me-1"></i> Perangkat</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-white text-primary fw-semibold fs-2 shadow-sm">
                                <i class="ti ti-calendar me-1"></i> {{ date('d M Y') }}
                            </span>

                            <span class="badge bg-primary text-white fw-semibold fs-2 shadow-sm">
                                {{ Auth::user()->getRoleNames()->first() ?? 'User' }}
                            </span>
                        </div>
                    </div>

                    <div class="col-sm-5 d-none d-sm-block">
                        <div class="welcome-bg-img mb-n7 text-end">
                            <img src="{{ asset('dist/assets/images/backgrounds/welcome-bg.svg') }}" alt="welcome-img" class="img-fluid" style="width: 180px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-12 col-xl-4">
        <div class="row">
            <div class="col-sm-6 col-md-3 col-xl-6 d-flex align-items-stretch">
                <div class="card w-100">
                    <div class="card-body p-4 text-center">
                        <i id="2fa-icon" class="ti {{ Auth::user()->two_factor_enabled ? 'ti-shield-check text-success' : 'ti-shield-x text-danger' }} fs-7 mb-2"></i>

                        <div class="mt-1 mb-2">
                            <button type="button" id="btn-2fa-toggle"
                                class="btn btn-sm {{ Auth::user()->two_factor_enabled ? 'btn-success' : 'btn-secondary' }} fw-bold px-3 shadow-sm">
                                {{ Auth::user()->two_factor_enabled ? '● AKTIF' : '○ OFF' }}
                            </button>
                        </div>

                        <p class="mb-0 fs-2 text-muted text-uppercase fw-semibold">2FA Auth</p>
                    </div>
                </div>
            </div>

            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script>
                $(document).ready(function() {
                    $('#btn-2fa-toggle').on('click', function() {
                        let isCurrentlyActive = $(this).text().includes('AKTIF');
                        let judulConfirm = isCurrentlyActive ? 'Nonaktifkan 2FA?' : 'Aktifkan 2FA?';
                        let teksConfirm = isCurrentlyActive ?
                            'Apakah Anda yakin ingin mematikan fitur Dua Faktor Autentikasi?' :
                            'Sistem akan langsung mengaktifkan 2FA menggunakan Kunci Rahasia Anda.';
                        let warnaTombol = isCurrentlyActive ? '#d33' : '#28a745';

                        Swal.fire({
                            title: judulConfirm,
                            text: teksConfirm,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: warnaTombol,
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Ya, Lanjutkan!',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                Swal.showLoading();
                                $.ajax({
                                    url: "{{ route('2fa.toggle') }}",
                                    type: "POST",
                                    data: {
                                        _token: "{{ csrf_token() }}"
                                    },
                                    success: function(response) {
                                        Swal.close();
                                        if (response.status === 'success') {
                                            if (response.two_factor_enabled == 1) {
                                                $('#btn-2fa-toggle').removeClass('btn-secondary').addClass('btn-success').text('● AKTIF');
                                                $('#2fa-icon').removeClass('ti-shield-x text-danger').addClass('ti-shield-check text-success');
                                            } else {
                                                $('#btn-2fa-toggle').removeClass('btn-success').addClass('btn-secondary').text('○ OFF');
                                                $('#2fa-icon').removeClass('ti-shield-check text-success').addClass('ti-shield-x text-danger');
                                            }
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Berhasil!',
                                                text: response.message,
                                                timer: 2000,
                                                showConfirmButton: false
                                            });
                                        } else if (response.status === 'redirect') {
                                            Swal.fire({
                                                icon: 'info',
                                                title: 'Setup Diperlukan',
                                                text: response.message,
                                                confirmButtonText: 'Lanjutkan Ke Setup'
                                            }).then(() => {
                                                window.location.href = response.url;
                                            });
                                        }
                                    },
                                    error: function(xhr) {
                                        Swal.close();
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Oops...',
                                            text: 'Terjadi kesalahan sistem, silakan coba lagi nanti.'
                                        });
                                    }
                                });
                            }
                        });
                    });
                });
            </script>


            <div class="col-sm-6 col-md-3 col-xl-6 d-flex align-items-stretch">
                <div class="card w-100">
                    <div class="card-body p-4 text-center">
                        <div id="weather-icon">
                            <iconify-icon icon="solar:cloud-sun-2-line-duotone" class="fs-7 mb-2 text-warning"></iconify-icon>
                        </div>
                        <h4 class="fw-semibold mb-1" id="weather-temp">--°C</h4>
                        <p class="mb-0 fs-2 text-muted text-uppercase" id="weather-desc">Surabaya</p>
                    </div>
                </div>
            </div>

            <div class="col-sm-12 col-md-6 col-xl-12 d-flex align-items-stretch">
                <div class="card w-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-semibold mb-0">Jadwal Sholat</h6>
                            <span class="badge bg-primary-subtle text-primary fw-bold" id="next-prayer-name">-</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="ti ti-mosque fs-7 me-3 text-primary"></i>
                            <div>
                                <h3 class="fw-semibold mb-0" id="next-prayer-time">--:--</h3>
                                <p class="mb-0 fs-2 text-muted">Wilayah <span id="user-location">Surabaya & Sekitarnya</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>

    </div>




</div>
@endsection

@push('scripts')
<script>
    /**
     * DASHBOARD UTILITY SCRIPTS
     * Mengelola Jam, Cuaca, dan Jadwal Sholat secara Real-time & Dinamis
     */

    // 1. LIVE CLOCK FUNCTION
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        }).replace(/\./g, ':');

        const clockElement = document.getElementById('liveClock');
        if (clockElement) clockElement.textContent = timeString;
    }

    // 2. WEATHER FUNCTION (Open-Meteo API)
    async function fetchWeather(lat = -7.2504, lon = 112.7688) {
        try {
            const response = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current_weather=true`);
            const data = await response.json();
            const tempElement = document.getElementById('weather-temp');
            if (tempElement) tempElement.textContent = Math.round(data.current_weather.temperature) + '°C';
        } catch (error) {
            console.error("Gagal memuat data cuaca:", error);
        }
    }

    // 3. PRAYER TIMES FUNCTION (Aladhan API - Lebih akurat untuk Koordinat)
    async function fetchPrayerTimes(lat = -7.2504, lon = 112.7688) {
        try {
            // Menggunakan Aladhan API karena mendukung koordinat Lat/Lon secara native
            const response = await fetch(`https://api.aladhan.com/v1/timings?latitude=${lat}&longitude=${lon}&method=2`);
            const data = await response.json();
            const timings = data.data.timings;

            const now = new Date().toLocaleTimeString('id-ID', {
                hour12: false
            }).slice(0, 5);

            // Daftar waktu sholat untuk dibandingkan
            const prayers = [{
                    name: 'Subuh',
                    time: timings.Fajr
                },
                {
                    name: 'Dzuhur',
                    time: timings.Dhuhr
                },
                {
                    name: 'Ashar',
                    time: timings.Asr
                },
                {
                    name: 'Maghrib',
                    time: timings.Maghrib
                },
                {
                    name: 'Isya',
                    time: timings.Isha
                }
            ];

            // Cari sholat terdekat berikutnya
            let next = prayers.find(p => p.time > now) || prayers[0];

            document.getElementById('next-prayer-name').textContent = next.name;
            document.getElementById('next-prayer-time').textContent = next.time;
        } catch (error) {
            console.error("Gagal memuat jadwal sholat:", error);
        }
    }

    // 4. CORE LOCATION DETECTION
    function initDashboardData() {
        // Jalankan Jam segera
        setInterval(updateClock, 1000);
        updateClock();

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(async (position) => {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;

                try {
                    // Reverse Geocoding untuk nama kota
                    const geoResponse = await fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lon}&localityLanguage=id`);
                    const geoData = await geoResponse.json();
                    const city = geoData.city || geoData.locality || "Sidoarjo"; // Fallback ke lokasi saat ini

                    const locElement = document.getElementById('user-location');
                    const descElement = document.getElementById('weather-desc');

                    if (locElement) locElement.textContent = city + " & Sekitarnya";
                    if (descElement) descElement.textContent = city;

                    // Update data berdasarkan lokasi user
                    fetchWeather(lat, lon);
                    fetchPrayerTimes(lat, lon);

                } catch (error) {
                    console.error("Gagal sinkronisasi lokasi:", error);
                    runDefaultData();
                }
            }, (error) => {
                console.warn("Akses lokasi ditolak, menggunakan lokasi default.");
                runDefaultData();
            });
        } else {
            runDefaultData();
        }
    }

    // Fallback jika GPS mati (Default ke Surabaya sesuai kebutuhan awal Anda)
    function runDefaultData() {
        fetchWeather(); // Default Surabaya
        fetchPrayerTimes(); // Default Surabaya
    }

    // INITIALIZE ON LOAD
    document.addEventListener('DOMContentLoaded', initDashboardData);
</script>
@endpush