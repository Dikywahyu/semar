<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasiswa_registrasis', function (Blueprint $table) {
            $table->string('id_registrasi_mahasiswa', 36)->primary();
            $table->string('nim', 30)->unique();
            $table->string('nama_mahasiswa');
            $table->string('email')->nullable()->unique();
            $table->string('nama_program_studi')->nullable();
            $table->unsignedSmallInteger('angkatan')->nullable();
            $table->string('telepon', 30)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasiswa_registrasis');
    }
};
