<div align="center">

# SEMAR

**Satu portal untuk identitas, akses, dan layanan operasional akademik.**

Menghubungkan pengelolaan akun dan hak akses dengan proses PKL, kegiatan, presensi, dan kolaborasi dalam satu aplikasi.

<br>

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![Vite](https://img.shields.io/badge/Vite-7-646CFF?logo=vite&logoColor=white)
![Status](https://img.shields.io/badge/status-in%20development-blue)

</div>

---

## Tentang SEMAR

SEMAR adalah aplikasi web internal untuk mendukung pengelolaan identitas dan aktivitas akademik. Aplikasi ini menyediakan autentikasi, pengaturan role dan permission, integrasi OAuth/API, serta modul operasional untuk mahasiswa, dosen, dan pengelola.

## Fitur utama

- **Identity & Access Management** — pengelolaan akun, role, permission, klien API, serta alur login dan klaim akun.
- **Keamanan akun** — verifikasi email, autentikasi dua faktor (2FA), pengaturan profil, dan pengelolaan password.
- **Integrasi aplikasi** — OAuth 2.0 dengan Laravel Passport, endpoint profil pengguna, dan sinkronisasi role.
- **Pengelolaan PKL** — alur pengajuan dan berkas, bimbingan dosen, penilaian, serta pengelolaan data mahasiswa dan dosen. Master data mendukung impor CSV/Excel dan ekspor Excel.
- **Kegiatan dan presensi** — pengelolaan kegiatan dan sesi, presensi melalui QR, input manual, serta ekspor rekap ke Excel/PDF.
- **Berita acara** — pengelolaan laporan kegiatan, persetujuan digital, dokumen PDF, dan verifikasi tanda tangan melalui QR.
- **Kolaborasi** — papan Kanban dengan dukungan berbagi board dan tautan singkat.

> Akses modul dan tindakan tertentu mengikuti autentikasi serta role/permission yang dikonfigurasi pada aplikasi.

## Teknologi

| Area | Teknologi |
| --- | --- |
| Backend | PHP 8.2+, Laravel 12 |
| Autentikasi & API | Laravel Passport, Sanctum, Socialite |
| Hak akses | Spatie Laravel Permission |
| Frontend assets | Vite 7, Tailwind CSS 4 |
| Database | SQLite (default) atau database lain yang didukung Laravel |

## Memulai

### Prasyarat

- PHP **8.2 atau lebih baru**
- [Composer](https://getcomposer.org/)
- [Node.js dan npm](https://nodejs.org/)
- SQLite atau database lain yang didukung Laravel

### Instalasi lokal (Windows PowerShell)

```powershell
git clone https://github.com/Dikywahyu/semar.git
Set-Location semar

composer install
Copy-Item .env.example .env
php artisan key:generate

# Buat file SQLite jika belum tersedia
if (-not (Test-Path database/database.sqlite)) {
    New-Item -ItemType File database/database.sqlite | Out-Null
}

php artisan migrate
npm install
npm run build
php artisan serve
```

Buka alamat yang ditampilkan oleh `php artisan serve` (umumnya `http://127.0.0.1:8000`).

### Konfigurasi

Sesuaikan `.env` dengan lingkungan lokal sebelum menjalankan aplikasi:

- **Database:** konfigurasi bawaan menggunakan SQLite. Untuk MySQL, ubah `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`.
- **URL aplikasi:** atur `APP_URL` sesuai alamat lokal atau domain aplikasi.
- **Integrasi opsional:** isi konfigurasi OAuth/Google, email, dan Passport hanya jika integrasi terkait digunakan.

Jangan memasukkan `.env`, kredensial, atau private key ke repository.

### Mode pengembangan frontend

Jalankan Vite di terminal terpisah agar perubahan aset diproses secara langsung:

```powershell
npm run dev
```

Untuk menjalankan seluruh proses pengembangan yang sudah dikonfigurasi pada Composer (server, queue, log, dan Vite):

```powershell
composer run dev
```

## Pengujian

Jalankan suite pengujian aplikasi dengan:

```powershell
composer test
```

## Struktur aplikasi

```text
app/                 Logika aplikasi, controller, model, dan layanan
database/            Migrasi, factory, dan seeder
resources/            View Blade dan aset sumber
routes/               Route web, API, dan console
public/                Front controller dan aset publik
tests/                 Pengujian aplikasi
```

## Catatan pengelolaan data PKL

Data dosen dan mahasiswa dikelola secara lokal dan tidak bergantung pada Feeder. Akun pengguna dipasangkan dengan data master menggunakan email yang sama atau `identifier_number` yang berisi NIDN/NIM.

Impor master data menerima berkas `.xlsx` atau `.csv` dengan baris pertama sebagai header. Gunakan format kolom pada file ekspor; impor menambahkan data baru dan melewati baris tidak valid atau identitas yang sudah digunakan.

Portal PKL tersedia pada `/pkl/mahasiswa`, `/pkl/dosen`, dan `/pkl/admin`. Ketiganya memerlukan login; hak akses admin mengikuti konfigurasi role/permission aplikasi.

---

<div align="center">

Dibangun untuk mendukung layanan akademik yang terintegrasi.

</div>
