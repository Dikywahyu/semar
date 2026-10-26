<?php

namespace App\Models\Capstone;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CapstoneScoreComponent extends Model
{
    protected $guarded = ['id'];

    public function studentScores(): HasMany
    {
        return $this->hasMany(CapstoneStudentScore::class, 'component_id');
    }
}
