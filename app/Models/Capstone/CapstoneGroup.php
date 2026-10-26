<?php

namespace App\Models\Capstone;

use App\Models\User;
use App\Models\Dosen\Dosen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CapstoneGroup extends Model
{
    protected $guarded = ['id'];

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function expertise(): BelongsTo
    {
        return $this->belongsTo(CapstoneExpertise::class, 'expertise_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'supervisor_id', 'id_dosen');
    }

    public function examiner(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'examiner_id', 'id_dosen');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CapstoneGroupMember::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(CapstoneConsultation::class);
    }

    public function studentScores(): HasMany
    {
        return $this->hasMany(CapstoneStudentScore::class);
    }
}
