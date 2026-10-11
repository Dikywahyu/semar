<?php

namespace App\Models\Pkl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Mahasiswa\MahasiswaRegistrasi;
use App\Models\Dosen\Dosen;
use App\Models\Semester;

class PengajuanPkl extends Model
{
    protected $table = 'pengajuan_pkl';

    protected $guarded = ['id'];

    protected $casts = [
        'is_acc_seminar' => 'boolean',
        'tanggal_seminar' => 'datetime',
        'nilai_akhir' => 'decimal:2',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function logbooks()
    {
        // Sesuaikan 'pengajuan_pkl_id' dengan foreign key di tabel logbook Anda
        return $this->hasMany(LogAktivitasPkl::class, 'pengajuan_pkl_id'); 
    }
    
    public function mahasiswaRegistrasi(): BelongsTo
    {
        return $this->belongsTo(MahasiswaRegistrasi::class, 'mahasiswa_registrasi_id', 'id_registrasi_mahasiswa');
    }

    public function semesterMaster(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_master_id');
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->mahasiswaRegistrasi();
    }

    public function dosenPembimbing(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_pembimbing_id', 'id_dosen');
    }

    public function dosenPenguji(): BelongsToMany
    {
        return $this->belongsToMany(
            Dosen::class, 
            'dosen_penguji_pkl', 
            'pengajuan_pkl_id', // Foreign key tabel ini (pengajuan_pkl) di pivot
            'dosen_id',         // Foreign key tabel tujuan (dosens) di pivot
            'id',               // Local key di tabel pengajuan_pkl
            'id_dosen'          // Owner key di tabel dosens
        )
        ->withPivot('peran')
        ->withTimestamps();
    }
    
    public function detailNilai(): HasMany
    {
        return $this->hasMany(DetailNilaiPkl::class, 'pengajuan_pkl_id');
    }

    public function bimbingan(): HasMany
    {
        return $this->hasMany(BimbinganPkl::class, 'pengajuan_pkl_id');
    }

    public function berkasSeminar(): HasMany
    {
        return $this->hasMany(BerkasSeminarPkl::class, 'pengajuan_pkl_id');
    }

    public function penilaian(): HasMany
    {
        return $this->hasMany(PenilaianPkl::class, 'pengajuan_pkl_id');
    }

    public function logAktivitas(): HasMany
    {
        return $this->hasMany(LogAktivitasPkl::class, 'pengajuan_pkl_id');
    }
}