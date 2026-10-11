<?php

namespace App\Models\Apps;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Activity extends Model
{
    use HasFactory;

    protected $table = 'activities';

    protected $fillable = [
        'activity_type_id',
        'title',
        'description',
        'location',
        'created_by',
    ];

    /**
     * Relasi ke Tipe Kegiatan (Rapat, Matrikulasi, Seminar, dll).
     */
    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');
    }

    /**
     * Relasi ke Pengguna/Operator yang membuat kegiatan ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke Sesi-sesi Pertemuan/Absensi pada kegiatan ini.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(ActivitySession::class, 'activity_id');
    }

    /**
     * Relasi ke Berita Acara / Notulensi Hasil Kegiatan.
     */
    public function report(): HasOne
    {
        return $this->hasOne(ActivityReport::class, 'activity_id');
    }
}