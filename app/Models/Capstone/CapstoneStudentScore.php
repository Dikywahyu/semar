<?php

namespace App\Models\Capstone;

use App\Models\User;
use App\Models\Dosen\Dosen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapstoneStudentScore extends Model
{
    protected $guarded = ['id'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(CapstoneGroup::class, 'capstone_group_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(CapstoneScoreComponent::class, 'component_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'evaluator_id', 'id_dosen');
    }
}
