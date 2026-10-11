<?php

namespace App\Models\Kanban;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KanbanTask extends Model
{
    protected $table = 'kanban_tasks';

    protected $fillable = [
        'stage_id', 
        'title', 
        'description', 
        'due_date', 
        'priority', 
        'position', 
        'user_id'
    ];

    /**
     * Kolom tempat tugas ini berada
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(KanbanStage::class, 'stage_id');
    }

    /**
     * User penanggung jawab / pembuat
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}