<?php

namespace App\Models\Apps;

use Illuminate\Database\Eloquent\Model;

class ShortLink extends Model
{
    protected $fillable = ['user_id', 'short_code', 'destination_url', 'clicks_count', 'logo_path', 'qr_code_path'];

    /**
     * Relasi kembali ke pemilik Link
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
