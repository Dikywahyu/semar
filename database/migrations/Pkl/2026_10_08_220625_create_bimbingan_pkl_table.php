<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bimbingan_pkl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_pkl_id')->constrained('pengajuan_pkl')->onDelete('cascade');
            $table->string('judul_topik'); // Contoh: "Revisi Bab 1 - Latar Belakang"
            $table->enum('kategori_bimbingan', [
                'Konsultasi Proposal',
                'Bimbingan Lapangan',
                'Bimbingan Laporan',
                'Revisi Laporan'
            ]);
            $table->enum('status', ['Pending', 'Disetujui', 'Revisi'])->default('Pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bimbingan_pkl');
    }
};