<?php

namespace App\Models\Capstone;

use App\Models\Dosen\Dosen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CapstoneExpertise extends Model
{
    protected $guarded = ['id'];

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'coordinator_id', 'id_dosen');
    }

    public function lecturers(): BelongsToMany
    {
        return $this->belongsToMany(Dosen::class, 'capstone_expertise_lecturers', 'capstone_expertise_id', 'lecturer_id', 'id', 'id_dosen');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(CapstoneGroup::class, 'expertise_id');
    }
}
