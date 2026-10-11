<?php

namespace App\Models\Pkl;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Dosen\Dosen;

class DosenPengujiPkl extends Pivot
{
    protected $table = 'dosen_penguji_pkl';

    public function pengajuanPkl(): BelongsTo
    {
        return $this->belongsTo(PengajuanPkl::class, 'pengajuan_pkl_id');
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_id');
    }
}