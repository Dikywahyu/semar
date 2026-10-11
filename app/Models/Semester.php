<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    protected $table = 'semesters';

    protected $fillable = [
        'tahun_akademik',
        'periode',
        'kode_semester',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function pengajuanPkl(): HasMany
    {
        return $this->hasMany(Pkl\PengajuanPkl::class, 'semester_master_id');
    }

    public function komponenPenilaianPkl(): HasMany
    {
        return $this->hasMany(Pkl\KomponenPenilaianPkl::class, 'semester_master_id');
    }

    public function getNamaAttribute(): string
    {
        return "{$this->tahun_akademik} " . ucfirst($this->periode);
    }

    public function getIdSemesterAttribute(): string
    {
        return $this->kode_semester;
    }

    public function getNamaSemesterAttribute(): string
    {
        return $this->nama;
    }

    public function getAPeriodeAktifAttribute(): bool
    {
        return $this->aktif;
    }
}
