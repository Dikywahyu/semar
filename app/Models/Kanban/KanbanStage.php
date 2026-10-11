<?php

namespace App\Models\Kanban;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class KanbanStage extends Model
{
    protected $table = 'kanban_stages';

    protected $fillable = ['board_id', 'name', 'color', 'position'];

    /**
     * Hubungan ke Board utama
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(KanbanBoard::class, 'board_id');
    }

    /**
     * Hubungan ke kartu tugas/cards di dalamnya
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(KanbanTask::class, 'stage_id')->orderBy('position');
    }
}