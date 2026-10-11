<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dosen_penguji_pkl', function (Blueprint $table) {
            // 1. Drop foreign key constraint terlebih dahulu
            $table->dropForeign('dosen_penguji_pkl_dosen_id_foreign');
        });

        Schema::table('dosen_penguji_pkl', function (Blueprint $table) {
            // 2. Ubah tipe kolom dosen_id menjadi string (UUID)
            $table->string('dosen_id', 36)->change();
        });

        Schema::table('dosen_penguji_pkl', function (Blueprint $table) {
            $table->foreign('dosen_id')
                  ->references('id_dosen')
                  ->on('dosens')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('dosen_penguji_pkl', function (Blueprint $table) {
            $table->dropForeign('dosen_penguji_pkl_dosen_id_foreign');
            $table->string('dosen_id', 36)->change();
        });
    }
};