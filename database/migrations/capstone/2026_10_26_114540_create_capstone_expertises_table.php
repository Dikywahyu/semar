<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Tabel Keahlian Capstone
        Schema::create('capstone_expertises', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Menghubungkan koordinator ke tabel dosens (nullable)[cite: 7]
            $table->string('coordinator_id', 36)->nullable();
            $table->foreign('coordinator_id')->references('id_dosen')->on('dosens')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Tabel Pivot Keahlian dengan Banyak Dosen[cite: 7]
        Schema::create('capstone_expertise_lecturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capstone_expertise_id')->constrained('capstone_expertises')->cascadeOnDelete();
            $table->string('lecturer_id', 36);
            $table->foreign('lecturer_id')->references('id_dosen')->on('dosens')->cascadeOnDelete();
            $table->timestamps();
        });

        // 3. Tabel Komponen Nilai Dinamis[cite: 7]
        Schema::create('capstone_score_components', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama komponen (misal: Presentasi, Produk, Laporan)[cite: 7]
            $table->enum('category', ['pembimbing', 'penguji']); // Digolongkan untuk pembimbing atau penguji[cite: 7]
            $table->decimal('percentage', 5, 2); // Bobot persentase komponen[cite: 7]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capstone_score_components');
        Schema::dropIfExists('capstone_expertise_lecturers');
        Schema::dropIfExists('capstone_expertises');
    }
};
