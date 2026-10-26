<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('capstone_student_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capstone_group_id')->constrained('capstone_groups')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('capstone_score_components')->cascadeOnDelete();

            // Dosen penilai (pembimbing/penguji) merujuk ke dosens
            $table->string('evaluator_id', 36);
            $table->foreign('evaluator_id')->references('id_dosen')->on('dosens')->cascadeOnDelete();

            $table->decimal('score', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capstone_student_scores');
    }
};
