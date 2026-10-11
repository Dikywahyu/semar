<?php

namespace App\Models\Apps;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityType extends Model
{
    use HasFactory;

    protected $table = 'activity_types';

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Relasi ke seluruh kegiatan yang menggunakan tipe ini.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'activity_type_id');
    }
}