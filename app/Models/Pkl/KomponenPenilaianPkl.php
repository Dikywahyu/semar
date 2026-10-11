<?php

namespace App\Models\Pkl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Semester;

class KomponenPenilaianPkl extends Model
{
    protected $table = 'komponen_penilaian_pkl';

    protected $guarded = ['id'];

    protected $casts = [
        'bobot' => 'decimal:2',
    ];

    /**
     * Relasi ke detail nilai mahasiswa
     */
    public function detailNilai(): HasMany
    {
        return $this->hasMany(DetailNilaiPkl::class, 'komponen_penilaian_pkl_id');
    }

    public function semesterMaster(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_master_id');
    }
}