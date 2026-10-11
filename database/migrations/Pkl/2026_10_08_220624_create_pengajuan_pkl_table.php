<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_pkl', function (Blueprint $table) {
            $table->id();
            $table->text('mahasiswa_registrasi_id');
            
            // Data Instansi / Perusahaan
            $table->string('nama_instansi');
            $table->text('alamat_instansi');
            $table->string('pimpinan_instansi')->nullable();
            $table->text('ringkasan_proposal')->nullable();
            $table->text('berkas_bukti_survei')->nullable(); 
            $table->text('berkas_surat_balasan')->nullable(); 
            
            // Dosen Pembimbing
            $table->text('dosen_pembimbing_id')->nullable();

            // Persetujuan Dosen Pembimbing untuk Daftar Seminar
            $table->boolean('is_acc_seminar')->default(false);

            // Jadwal Ujian Seminar
            $table->dateTime('tanggal_seminar')->nullable();
            $table->string('ruangan_seminar')->nullable(); 

            // Rekap Penilaian Akhir
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->string('nilai_huruf', 5)->nullable(); 

            // Berkas Pasca Ujian (Revisi & Lembar Pengesahan)
            $table->text('berkas_laporan_revisi')->nullable();
            $table->text('berkas_lembar_pengesahan')->nullable();

            // Status Alur PKL
            $table->enum('status', [
                'Draft / Pengajuan Survei',
                'Koordinasi Dosen Pembimbing',
                'Pengurusan Surat Pengantar',
                'Pelaksanaan PKL & Bimbingan',
                'Penyusunan Laporan',
                'Pendaftaran Seminar',
                'Verifikasi Berkas Admin',
                'Dijadwalkan Ujian',
                'Ujian Seminar & Penilaian',
                'Revisi Laporan',
                'Selesai'
            ])->default('Draft / Pengajuan Survei');

            $table->text('catatan_admin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_pkl');
    }
};