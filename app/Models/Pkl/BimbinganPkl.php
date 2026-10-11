<?php

namespace App\Models\Pkl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BimbinganPkl extends Model
{
    protected $table = 'bimbingan_pkl';

    protected $guarded = ['id'];

    public function pengajuanPkl(): BelongsTo
    {
        return $this->belongsTo(PengajuanPkl::class, 'pengajuan_pkl_id');
    }

    public function tanggapan(): HasMany
    {
        return $this->hasMany(TanggapanBimbinganPkl::class, 'bimbingan_pkl_id');
    }
}