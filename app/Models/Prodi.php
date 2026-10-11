<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prodi extends Model
{
    protected $table = 'prodis';

    protected $fillable = [
        'kode_prodi',
        'nama_prodi',
        'jenjang',
        'fakultas',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function dosens(): HasMany
    {
        return $this->hasMany(Dosen\Dosen::class);
    }

    public function mahasiswas(): HasMany
    {
        return $this->hasMany(Mahasiswa\MahasiswaRegistrasi::class);
    }
}
