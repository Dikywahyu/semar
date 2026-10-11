<?php

namespace App\Models\Pkl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class TanggapanBimbinganPkl extends Model
{
    protected $table = 'tanggapan_bimbingan_pkl';

    protected $guarded = ['id'];

    public function bimbinganPkl(): BelongsTo
    {
        return $this->belongsTo(BimbinganPkl::class, 'bimbingan_pkl_id');
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengirim_id');
    }
}