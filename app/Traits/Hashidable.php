<?php

namespace App\Traits;

use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin \Illuminate\Database\Eloquent\Builder
 */

trait Hashidable
{
    /**
     * Mengenkripsi ID utama saat diakses melalui route binding.
     * Ini memastikan URL seperti /user/jR8vOQ berfungsi otomatis.
     */
    public function getRouteKey(): string
    {
        return Hashids::encode($this->getKey());
    }


    /**
     * FUNGSI KRUSIAL: Menangani pencarian model secara otomatis dari URL.
     * Laravel akan memanggil ini saat Anda menggunakan Type-Hinting di Controller.
     * * @param mixed $value
     * @param string|null $field
     * @return Model|null
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        // Menggunakan scopeFindByHashid melalui static call
        return static::findByHashid($value)->first();
    }

    /**
     * Menambahkan atribut virtual 'hashid' agar muncul di JSON response.
     * Sangat berguna saat mengirim data user ke aplikasi Mikroservis lain.
     */
    public function getHashidAttribute()
    {
        if (!isset($this->attributes['id'])) {
            return null;
        }
        return Hashids::encode($this->attributes['id']);
    }

    /**
     * Helper Statis: User::findByHashid('jR8vOQ')
     */
    public static function findByHashid($hashid)
    {
        $decoded = Hashids::decode($hashid);
        if (empty($decoded)) return null;

        return static::find($decoded[0]);
    }

    /**
     * Scope: User::whereHashid('jR8vOQ')->first()
     */
    public function scopeWhereHashid($query, $hashid)
    {
        $decoded = Hashids::decode($hashid);
        if (empty($decoded)) return $query->whereRaw('1 = 0');

        return $query->where('id', $decoded[0]);
    }

    /**
     * Fungsi pembantu untuk mencari data berdasarkan Hashid secara manual.
     * Contoh penggunaan: User::findByHashid($id);
     */
    public static function scopeFindByHashid($query, $hashid)
    {
        $decoded = Hashids::decode($hashid);

        if (empty($decoded)) {
            return $query->whereRaw('1 = 0'); // Mengembalikan query kosong jika decode gagal
        }

        return $query->where('id', $decoded[0]);
    }
}
