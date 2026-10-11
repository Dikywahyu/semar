<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCredential extends Model
{
    // Menentukan kolom yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'user_id',
        'encrypted_password',
    ];

    /**
     * Relasi ke model User.
     * Setiap kredensial dimiliki oleh satu user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Static Helper: Simpan atau Update Password.
     * Fungsi ini otomatis mengenkripsi plain password sebelum masuk database.
     * * @param int $userId
     * @param string $plainPassword
     */
    public static function storePassword($userId, $plainPassword)
    {
        return self::updateOrCreate(
            ['user_id' => $userId],
            ['encrypted_password' => Crypt::encryptString($plainPassword)]
        );
    }

    /**
     * Method untuk mengambil kembali password asli.
     * Gunakan ini di Controller saat Super Admin butuh memberikan password ke user tua.
     */
    public function getPlainPassword(): string
    {
        try {
            return Crypt::decryptString($this->encrypted_password);
        } catch (\Exception $e) {
            // Jika APP_KEY berubah atau data korup, dekripsi akan gagal
            return "Gagal mendekripsi password.";
        }
    }
}
