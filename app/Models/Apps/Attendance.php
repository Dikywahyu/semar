<?php

namespace App\Models\Apps;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';

    protected $fillable = [
        'session_id',
        'user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'guest_gender',
        'guest_position',
        'guest_organization',
        'check_in_time',
        'status',
        'method',
        'notes',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
    ];

    /**
     * Relasi ke Sesi Absensi.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ActivitySession::class, 'session_id');
    }

    /**
     * Relasi ke User Internal (jika presensi dilakukan oleh user terdaftar).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}