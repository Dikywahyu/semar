<?php

namespace App\Models\TugasAkhir;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThesisDocument extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['verified_at' => 'datetime'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(ThesisApplication::class, 'thesis_application_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
