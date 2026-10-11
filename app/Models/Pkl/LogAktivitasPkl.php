<?php

namespace App\Models\Pkl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class LogAktivitasPkl extends Model
{
    protected $table = 'log_aktivitas_pkl';

    protected $guarded = ['id'];

    public function pengajuanPkl(): BelongsTo
    {
        return $this->belongsTo(PengajuanPkl::class, 'pengajuan_pkl_id');
    }

    public function aktor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aktor_id');
    }
}