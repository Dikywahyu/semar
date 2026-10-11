<?php

namespace App\Models\Pkl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailNilaiPkl extends Model
{
    protected $table = 'detail_nilai_pkl';

    protected $guarded = ['id'];

    protected $casts = [
        'nilai_angka' => 'decimal:2',
    ];

    /**
     * Relasi ke pengajuan PKL mahasiswa
     */
    public function pengajuanPkl(): BelongsTo
    {
        return $this->belongsTo(PengajuanPkl::class, 'pengajuan_pkl_id');
    }

    /**
     * Relasi ke master komponen penilaian
     */
    public function komponen(): BelongsTo
    {
        return $this->belongsTo(KomponenPenilaianPkl::class, 'komponen_penilaian_pkl_id');
    }
}