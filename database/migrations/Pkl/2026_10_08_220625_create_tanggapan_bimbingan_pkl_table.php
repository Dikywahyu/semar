<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tanggapan_bimbingan_pkl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bimbingan_pkl_id')->constrained('bimbingan_pkl')->onDelete('cascade');
            $table->foreignId('pengirim_id')->constrained('users')->onDelete('cascade'); // Bisa Mahasiswa atau Dosen
            
            $table->text('pesan'); // Isi chat / balasan bimbingan
            $table->string('berkas_lampiran')->nullable(); // File draf/coretan revisi susulan
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tanggapan_bimbingan_pkl');
    }
};