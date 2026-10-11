<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable; // Pastikan namespace Trait ini benar sesuai project Bapak

class ApiClient extends Model
{
    use HasFactory, Hashidable; // Tambahkan Hashidable di sini

    protected $table = 'api_clients';

    protected $fillable = [
        'icon',
        'app_name',
        'description',
        'client_id',
        'client_secret',
        'allowed_host',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Menambahkan hashid ke dalam JSON response secara otomatis
     */
    protected $appends = ['hashid'];
}
