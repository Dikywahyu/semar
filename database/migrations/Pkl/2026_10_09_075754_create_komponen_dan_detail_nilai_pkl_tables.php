<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat tabel master komponen penilaian berdasarkan semester
        if (!Schema::hasTable('komponen_penilaian_pkl')) {
            Schema::create('komponen_penilaian_pkl', function (Blueprint $table) {
                $table->id();
                $table->string('id_semester', 5); // Berelasi dengan semester
                $table->string('nama_komponen');  // Cth: Presentasi, Laporan, Nilai Lapangan
                $table->decimal('bobot', 5, 2);   // Persentase bobot (Cth: 30.00, 40.00)
                $table->timestamps();
            });
        }

        // 2. Buat tabel detail nilai mahasiswa per komponen
        if (!Schema::hasTable('detail_nilai_pkl')) {
            Schema::create('detail_nilai_pkl', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pengajuan_pkl_id')->constrained('pengajuan_pkl')->onDelete('cascade');
                $table->foreignId('komponen_penilaian_pkl_id')->constrained('komponen_penilaian_pkl')->onDelete('cascade');
                $table->decimal('nilai_angka', 5, 2); // Nilai mentah yang diinput (0 - 100)
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Nonaktifkan foreign key check sementara untuk menghindari error saat rollback
        Schema::disableForeignKeyConstraints();

        // Hapus tabel anak terlebih dahulu, baru tabel induknya
        Schema::dropIfExists('detail_nilai_pkl');
        Schema::dropIfExists('komponen_penilaian_pkl');

        // Nyalakan kembali foreign key check
        Schema::enableForeignKeyConstraints();
    }
};