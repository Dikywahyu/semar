<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('capstone_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leader_id')->constrained('users')->cascadeOnDelete(); // Mahasiswa (ke users)
            $table->foreignId('expertise_id')->nullable()->constrained('capstone_expertises')->nullOnDelete();

            // Dosen Pembimbing terhubung ke tabel dosens
            $table->string('supervisor_id', 36)->nullable();
            $table->foreign('supervisor_id')->references('id_dosen')->on('dosens')->nullOnDelete();

            // Dosen Penguji terhubung ke tabel dosens (opsional jika dibutuhkan di samping pembimbing)
            $table->string('examiner_id', 36)->nullable();
            $table->foreign('examiner_id')->references('id_dosen')->on('dosens')->nullOnDelete();

            $table->string('title')->nullable();

            // Status & Berkas
            $table->enum('status_pendaftaran', ['draft', 'submitted', 'approved'])->default('draft');
            $table->date('exam_date')->nullable();
            $table->string('exam_room')->nullable();

            $table->string('consultation_card_file')->nullable();
            $table->string('draft_report_file')->nullable();
            $table->enum('kpi_approval', ['pending', 'approved', 'rejected'])->default('pending');

            $table->string('berita_acara_file')->nullable();
            $table->string('lembar_revisi_file')->nullable();
            $table->string('final_report_file')->nullable();
            $table->string('recap_score_file')->nullable();

            $table->boolean('acc_coordinator')->default(false);
            $table->boolean('acc_supervisor')->default(false);
            $table->boolean('acc_kps')->default(false);
            $table->enum('final_status', ['proses', 'selesai'])->default('proses');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capstone_groups');
    }
};
