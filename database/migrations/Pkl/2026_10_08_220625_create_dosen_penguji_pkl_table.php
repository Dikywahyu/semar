<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dosen_penguji_pkl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_pkl_id')->constrained('pengajuan_pkl')->onDelete('cascade');
            $table->foreignId('dosen_id')->constrained('users')->onDelete('cascade');
            $table->enum('peran', ['Ketua Penguji', 'Anggota Penguji'])->default('Anggota Penguji');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dosen_penguji_pkl');
    }
};