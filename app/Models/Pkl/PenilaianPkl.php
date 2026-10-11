<?php

namespace App\Models\Pkl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class PenilaianPkl extends Model
{
    protected $table = 'penilaian_pkl';

    protected $guarded = ['id'];

    protected $casts = [
        'nilai_angka' => 'decimal:2',
    ];

    public function pengajuanPkl(): BelongsTo
    {
        return $this->belongsTo(PengajuanPkl::class, 'pengajuan_pkl_id');
    }

    public function komponenPenilaian(): BelongsTo
    {
        return $this->belongsTo(KomponenPenilaianPkl::class, 'komponen_penilaian_id');
    }

    public function penilai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penilai_id');
    }
}