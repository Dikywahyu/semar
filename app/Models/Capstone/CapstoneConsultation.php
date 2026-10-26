<?php

namespace App\Models\Capstone;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapstoneConsultation extends Model
{
    protected $guarded = ['id'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(CapstoneGroup::class, 'capstone_group_id');
    }
}
