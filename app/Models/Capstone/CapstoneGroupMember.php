<?php

namespace App\Models\Capstone;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapstoneGroupMember extends Model
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
}
