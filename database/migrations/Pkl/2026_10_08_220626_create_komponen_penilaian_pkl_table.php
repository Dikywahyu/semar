<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('komponen_penilaian_pkl', function (Blueprint $table) {
            $table->id();
            $table->string('nama_komponen'); 
            $table->enum('penilai', ['Instansi', 'Pembimbing', 'Penguji']); 
            $table->decimal('bobot_persen', 5, 2); // Contoh: 30.00 untuk 30%
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komponen_penilaian_pkl');
    }
};