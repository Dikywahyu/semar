<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration untuk menambah kolom.
     */
    public function up(): void
    {
        Schema::table('pengajuan_pkl', function (Blueprint $table) {
            $table->string('id_semester', 5)->nullable();
            $table->char('program_mbkm', 1)->default('1')->nullable();
            $table->char('jenis_anggota', 1)->default('0')->nullable();
            $table->string('id_jenis_aktivitas', 5)->default('3')->nullable();
            $table->string('judul_aktivitas')->nullable();
            $table->string('sk_tugas')->nullable();
            $table->date('tanggal_sk_tugas')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('id_aktivitas_feeder')->nullable(); // Menyimpan ID balasan dari PDDikti Feeder
        });
    }

    /**
     * Balikkan migration (jika di-rollback).
     */
    public function down(): void
    {
        Schema::table('pengajuan_pkl', function (Blueprint $table) {
            $table->dropColumn([
                'id_semester',
                'program_mbkm',
                'jenis_anggota',
                'id_jenis_aktivitas',
                'judul_aktivitas',
                'sk_tugas',
                'tanggal_sk_tugas',
                'tanggal_mulai',
                'tanggal_selesai',
                'id_aktivitas_feeder',
            ]);
        });
    }
};