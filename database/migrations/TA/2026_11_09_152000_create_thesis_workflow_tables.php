<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thesis_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->restrictOnDelete();
            $table->string('expertise_theme');
            $table->string('p1_id', 36)->nullable();
            $table->foreign('p1_id')->references('id_dosen')->on('dosens')->nullOnDelete();
            $table->string('p2_id', 36)->nullable();
            $table->foreign('p2_id')->references('id_dosen')->on('dosens')->nullOnDelete();
            $table->string('examiner_id', 36)->nullable();
            $table->foreign('examiner_id')->references('id_dosen')->on('dosens')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('status')->default('pengajuan_sempro');

            $table->boolean('sempro_consultation_approved')->default(false);
            $table->foreignId('sempro_consultation_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sempro_consultation_approved_by_role')->nullable();
            $table->timestamp('sempro_consultation_approved_at')->nullable();
            $table->date('sempro_proposed_date')->nullable();
            $table->time('sempro_proposed_time')->nullable();
            $table->date('sempro_exam_date')->nullable();
            $table->time('sempro_exam_time')->nullable();
            $table->string('sempro_exam_room')->nullable();
            $table->timestamp('sempro_invitation_sent_at')->nullable();
            $table->foreignId('sempro_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sempro_approved_by_role')->nullable();
            $table->timestamp('sempro_approved_at')->nullable();

            $table->boolean('semhas_coordinator_verified')->default(false);
            $table->foreignId('semhas_coordinator_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('semhas_coordinator_verified_by_role')->nullable();
            $table->timestamp('semhas_coordinator_verified_at')->nullable();
            $table->boolean('semhas_kps_verified')->default(false);
            $table->foreignId('semhas_kps_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('semhas_kps_verified_by_role')->nullable();
            $table->timestamp('semhas_kps_verified_at')->nullable();
            $table->timestamp('semhas_finished_at')->nullable();
            $table->foreignId('semhas_finished_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('semhas_finished_by_role')->nullable();

            $table->boolean('ta_coordinator_verified')->default(false);
            $table->foreignId('ta_coordinator_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ta_coordinator_verified_by_role')->nullable();
            $table->timestamp('ta_coordinator_verified_at')->nullable();
            $table->boolean('ta_kps_verified')->default(false);
            $table->foreignId('ta_kps_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ta_kps_verified_by_role')->nullable();
            $table->timestamp('ta_kps_verified_at')->nullable();
            $table->boolean('ta_draft_verified')->default(false);
            $table->foreignId('ta_draft_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ta_draft_verified_by_role')->nullable();
            $table->timestamp('ta_draft_verified_at')->nullable();
            $table->foreignId('ta_final_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ta_final_approved_by_role')->nullable();
            $table->timestamp('ta_final_approved_at')->nullable();
            $table->date('ta_proposed_date')->nullable();
            $table->time('ta_proposed_time')->nullable();
            $table->date('ta_exam_date')->nullable();
            $table->time('ta_exam_time')->nullable();
            $table->string('ta_exam_room')->nullable();
            $table->timestamp('ta_invitation_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'semester_id']);
            $table->index(['p1_id', 'status']);
        });

        Schema::create('thesis_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thesis_application_id')->constrained('thesis_applications')->cascadeOnDelete();
            $table->string('document_type', 60);
            $table->string('file_path');
            $table->string('verification_status')->default('pending');
            $table->text('verification_note')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verified_by_role')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['thesis_application_id', 'document_type'], 'thesis_docs_app_type_uq');
        });

        Schema::create('thesis_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thesis_application_id')->constrained('thesis_applications')->cascadeOnDelete();
            $table->string('assessment_type', 40);
            $table->string('evaluator_role', 3);
            $table->string('evaluator_id', 36);
            $table->foreign('evaluator_id')->references('id_dosen')->on('dosens')->cascadeOnDelete();
            $table->decimal('score', 5, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['thesis_application_id', 'assessment_type', 'evaluator_role'], 'thesis_scores_app_type_role_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thesis_scores');
        Schema::dropIfExists('thesis_documents');
        Schema::dropIfExists('thesis_applications');
    }
};
