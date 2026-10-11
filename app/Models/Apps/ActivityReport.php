<?php

namespace App\Models\Apps;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityReport extends Model
{
    use HasFactory;

    protected $table = 'activity_reports';

    protected $fillable = [
        'activity_id',
        'document_number',
        'summary',
        'decisions',
        'action_items',
        'attachment_file',
        'created_by',
        'approved_by_name',
        'approved_by_position',
        'approved_by_identifier',
        'approval_status',
        'digital_signature_token',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * Relasi kembali ke Kegiatan Utama.
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    /**
     * Relasi ke User/Notulis yang membuat Berita Acara.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}