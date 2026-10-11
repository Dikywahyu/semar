<?php

namespace App\Models\Pkl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BerkasSeminarPkl extends Model
{
    protected $table = 'berkas_seminar_pkl';

    protected $guarded = ['id'];

    public function pengajuanPkl(): BelongsTo
    {
        return $this->belongsTo(PengajuanPkl::class, 'pengajuan_pkl_id');
    }
}