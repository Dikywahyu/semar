<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berkas_seminar_pkl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_pkl_id')->constrained('pengajuan_pkl')->onDelete('cascade');
            $table->string('jenis_berkas'); // Contoh: 'Draft Laporan Final', 'Kartu Bimbingan', 'Nilai Instansi'
            $table->string('path_berkas');
            $table->enum('status_verifikasi', ['Pending', 'Valid', 'Ditolak'])->default('Pending');
            $table->text('catatan_verifikator')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berkas_seminar_pkl');
    }
};