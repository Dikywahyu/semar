<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable; 
use Spatie\Permission\Traits\HasRoles; // Tambahkan ini
use App\Traits\Hashidable; // Gunakan trait Hashids yang Anda buat
use App\Models\Apps\Attendance;
use Illuminate\Database\Eloquent\Relations\HasMany;


class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasRoles, Hashidable, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'last_seen',
        'two_factor_enabled',
        'google2fa_secret',
        'identifier_number',
        'phone',
        'gender',
        'position_title',
        'institution',
        'role',
    ];

    protected $appends = ['hashid'];

    // protected $guard_name = ['web', 'api'];
    protected $guard_name = 'web';


    protected $casts = [
        'last_seen' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function credential()
    {
        // Ini memungkinkan Anda memanggil $user->credential
        return $this->hasOne(UserCredential::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isOnline()
    {
        // Menganggap online jika aktivitas terakhir kurang dari 5 menit yang lalu
        return $this->last_seen && $this->last_seen->diffInMinutes(now()) < 5;
    }
    
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'user_id');
    }
}
