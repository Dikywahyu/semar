<?php

namespace App\Models;

use Laravel\Passport\Client as PassportClientModel;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PassportClient extends PassportClientModel
{
    /**
     * Mengizinkan mass-assignment agar kolom tidak terblokir saat proses $client->save()
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'secret',
        'provider',
        'redirect_uris',
        'grant_types',
        'revoked',
    ];

    /**
     * Konfigurasi casting standar Eloquent untuk menjaga tipe data dasar
     *
     * @var array<string, string>
     */
    protected $casts = [
        'grant_types' => 'array',
        'redirect_uris' => 'string',
        'revoked' => 'boolean',
    ];

    /**
     * FORCE INTERCEPTOR ACCESSOR (Standar Laravel 12)
     * * Menggunakan nama camelCase 'grantTypes' agar otomatis terikat dengan kolom database 'grant_types'.
     * Fungsi ini menjamin nilai yang dikembalikan ke core internal Passport SELALU berbentuk 
     * array murni PHP, sehingga mencegah terjadinya fatal error 'null returned'.
     */
    protected function grantTypes(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                // 1. Jika data dari PDO database sudah otomatis berbentuk array PHP, langsung loloskan
                if (is_array($value)) {
                    return $value;
                }

                // 2. Jika kolom di database kosong atau NULL, berikan default array akses SSO
                if (empty($value)) {
                    return ['authorization_code', 'refresh_token'];
                }

                // 3. Jika berupa string JSON (akibat query builder mentah), bongkar menjadi array murni
                $decoded = json_decode($value, true);
                return is_array($decoded) ? $decoded : ['authorization_code', 'refresh_token'];
            },
            set: function ($value) {
                // Menjamin data tersimpan ke database dalam format JSON string yang aman
                return is_array($value) ? json_encode($value) : $value;
            }
        );
    }
}