<?php

namespace App\Models\Dosen;

use App\Models\Pkl\PengajuanPkl;
use App\Models\Prodi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Dosen extends Model
{
    protected $table = 'dosens';

    protected $primaryKey = 'id_dosen';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'nidn',
        'nuptk',
        'nama_dosen',
        'gelar_depan',
        'gelar_belakang',
        'email',
        'telepon',
        'program_studi',
        'prodi_id',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Dosen $dosen) {
            $dosen->id_dosen ??= (string) Str::uuid();
        });
    }

    public function pengajuanBimbingan(): HasMany
    {
        return $this->hasMany(PengajuanPkl::class, 'dosen_pembimbing_id', 'id_dosen');
    }

    public function capstoneGroups(): HasMany
    {
        return $this->hasMany(\App\Models\Capstone\CapstoneGroup::class, 'supervisor_id', 'id_dosen');
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function pengajuanDiuji(): BelongsToMany
    {
        return $this->belongsToMany(
            PengajuanPkl::class,
            'dosen_penguji_pkl',
            'dosen_id',
            'pengajuan_pkl_id',
            'id_dosen',
            'id'
        )->withPivot('peran')->withTimestamps();
    }
}
