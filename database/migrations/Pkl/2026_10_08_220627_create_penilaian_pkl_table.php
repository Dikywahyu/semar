<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penilaian_pkl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_pkl_id')->constrained('pengajuan_pkl')->onDelete('cascade');
            $table->foreignId('komponen_penilaian_id')->constrained('komponen_penilaian_pkl')->onDelete('cascade');
            $table->foreignId('penilai_id')->nullable()->constrained('users')->onDelete('set null'); // Dosen Pembimbing / Penguji 1 / Penguji 2
            $table->decimal('nilai_angka', 5, 2); 
            $table->text('catatan_penilaian')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian_pkl');
    }
};