<?php

namespace App\Models\Apps;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivitySession extends Model
{
    use HasFactory;

    protected $table = 'activity_sessions';

    protected $fillable = [
        'activity_id',
        'session_name',
        'start_time',
        'end_time',
        'qr_code_token',
        'access_type',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    /**
     * Relasi kembali ke Kegiatan Utama.
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    /**
     * Relasi ke Transaksi Presensi/Kehadiran di sesi ini.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'session_id');
    }
}