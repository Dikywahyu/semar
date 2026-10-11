<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dosens', function (Blueprint $table) {
            $table->string('id_dosen', 36)->primary();
            $table->string('nidn', 30)->nullable()->unique();
            $table->string('nama_dosen');
            $table->string('gelar_depan')->nullable();
            $table->string('gelar_belakang')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('telepon', 30)->nullable();
            $table->string('program_studi')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dosens');
    }
};
