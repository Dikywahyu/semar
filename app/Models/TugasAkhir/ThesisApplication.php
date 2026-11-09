<?php

namespace App\Models\TugasAkhir;

use App\Models\Dosen\Dosen;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThesisApplication extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'sempro_consultation_approved_at' => 'datetime',
        'sempro_invitation_sent_at' => 'datetime',
        'sempro_approved_at' => 'datetime',
        'semhas_coordinator_verified_at' => 'datetime',
        'semhas_kps_verified_at' => 'datetime',
        'semhas_finished_at' => 'datetime',
        'ta_invitation_sent_at' => 'datetime',
        'ta_coordinator_verified_at' => 'datetime',
        'ta_kps_verified_at' => 'datetime',
        'ta_draft_verified_at' => 'datetime',
        'ta_final_approved_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function p1(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'p1_id', 'id_dosen');
    }

    public function p2(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'p2_id', 'id_dosen');
    }

    public function examiner(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'examiner_id', 'id_dosen');
    }

    public function semproConsultationApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sempro_consultation_approved_by');
    }

    public function semproApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sempro_approved_by');
    }

    public function semhasCoordinatorVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'semhas_coordinator_verified_by');
    }

    public function semhasKpsVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'semhas_kps_verified_by');
    }

    public function semhasApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'semhas_finished_by');
    }

    public function taCoordinatorVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ta_coordinator_verified_by');
    }

    public function taKpsVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ta_kps_verified_by');
    }

    public function taDraftVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ta_draft_verified_by');
    }

    public function taFinalApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ta_final_approved_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ThesisDocument::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ThesisScore::class);
    }
}
