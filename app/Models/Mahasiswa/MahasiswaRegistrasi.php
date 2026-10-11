<?php

namespace App\Models\Mahasiswa;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Pkl\PengajuanPkl;
use App\Models\Prodi;

class MahasiswaRegistrasi extends Model
{
    protected $table = 'mahasiswa_registrasis';

    protected $primaryKey = 'id_registrasi_mahasiswa';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'nim',
        'nama_mahasiswa',
        'email',
        'nama_program_studi',
        'prodi_id',
        'angkatan',
        'telepon',
        'aktif',
    ];

    protected $casts = [
        'angkatan' => 'integer',
        'aktif' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (MahasiswaRegistrasi $mahasiswa) {
            $mahasiswa->id_registrasi_mahasiswa ??= (string) Str::uuid();
        });
    }

    public function pengajuanPkl(): HasMany
    {
        return $this->hasMany(PengajuanPkl::class, 'mahasiswa_registrasi_id', 'id_registrasi_mahasiswa');
    }

    public function prodi(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }
}
