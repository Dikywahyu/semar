<?php

namespace App\Models\Kanban;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class KanbanBoard extends Model
{
    protected $table = 'kanban_boards';
    
    protected $fillable = ['name', 'description', 'user_id'];

    /**
     * Hubungan ke kolom/stages (Eager Loading target)
     */
    public function stages(): HasMany
    {
        // Mengarahkan ke class di namespace yang sama, diurutkan berdasarkan posisi
        return $this->hasMany(KanbanStage::class, 'board_id')->orderBy('position');
    }
    
    /**
     * Relasi ke Owner/Pemilik asli Papan (Many-to-One)
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * PERBAIKAN BARU: Relasi Many-to-Many ke tabel Pivot Kolaborator
     * Mengambil semua user/dosen yang diundang ke papan ini
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'kanban_board_members', 'board_id', 'user_id')
                    ->withPivot('role') // Membawa kolom status hak akses ('member' / 'viewer')
                    ->withTimestamps();
    }

    /**
     * Pembuat Board
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}