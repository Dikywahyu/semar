<?php

namespace App\Models\TugasAkhir;

use App\Models\Dosen\Dosen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThesisScore extends Model
{
    protected $guarded = ['id'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(ThesisApplication::class, 'thesis_application_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'evaluator_id', 'id_dosen');
    }
}
