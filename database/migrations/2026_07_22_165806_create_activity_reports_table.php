<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
            $table->string('document_number', 100)->nullable(); // Nomor Berita Acara
            $table->text('summary')->nullable(); // Ringkasan Jalannya Acara
            $table->text('decisions')->nullable(); // Poin Keputusan/Hasil
            $table->text('action_items')->nullable(); // Rencana Tindak Lanjut
            $table->string('attachment_file')->nullable(); // Foto/File Dokumentasi
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Notulis / Pembuat

            // --- KOLOM PENGESAHAN (MANUAL & DIGITAL) ---
            $table->string('approved_by_name', 150)->nullable(); // Nama Pejabat/Pihak yang Mengesahkan (Ketik Manual)
            $table->string('approved_by_position', 150)->nullable(); // Jabatan Pengesah (Contoh: Ketua Dept / Dekan / Rektor)
            $table->string('approved_by_identifier', 50)->nullable(); // NIP / NIDN / NIK Pengesah (Opsional)
            
            // Persiapan Pengesahan Digital (Digital Signature)
            $table->enum('approval_status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->string('digital_signature_token', 255)->nullable()->unique(); // Token Verifikasi QR Pengesahan Digital
            $table->timestamp('approved_at')->nullable(); // Waktu Pengesahan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_reports');
    }
};
