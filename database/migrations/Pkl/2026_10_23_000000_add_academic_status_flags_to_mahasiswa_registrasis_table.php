<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahasiswa_registrasis', function (Blueprint $table) {
            $table->string('status_pkl')->default('Belum Ambil');
            $table->string('status_capstone')->default('Belum Ambil');
            $table->string('status_sempro')->default('Belum Ambil');
            $table->string('status_semhas')->default('Belum Ambil');
            $table->string('status_ta')->default('Belum Ambil');

            $table->boolean('is_eligible_capstone')->default(false);
            $table->boolean('is_eligible_sempro')->default(false);
            $table->boolean('is_eligible_semhas')->default(false);
            $table->boolean('is_eligible_ta')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('mahasiswa_registrasis', function (Blueprint $table) {
            $table->dropColumn([
                'status_pkl',
                'status_capstone',
                'status_sempro',
                'status_semhas',
                'status_ta',
                'is_eligible_capstone',
                'is_eligible_sempro',
                'is_eligible_semhas',
                'is_eligible_ta',
            ]);
        });
    }
};
