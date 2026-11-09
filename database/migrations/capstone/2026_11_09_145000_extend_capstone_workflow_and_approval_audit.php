<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capstone_groups', function (Blueprint $table) {
            $table->time('exam_time')->nullable();
            $table->string('project_location')->nullable();
            $table->date('proposed_exam_date')->nullable();
            $table->time('proposed_exam_time')->nullable();
            $table->timestamp('exam_invitation_sent_at')->nullable();
            $table->string('agreement_file')->nullable();
            $table->string('drawings_file')->nullable();
            $table->string('rab_file')->nullable();
            $table->string('structural_calculation_file')->nullable();
            $table->string('approved_by_role')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('coordinator_approved_by_role')->nullable();
            $table->foreignId('coordinator_approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('coordinator_approved_at')->nullable();
            $table->string('supervisor_approved_by_role')->nullable();
            $table->foreignId('supervisor_approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('supervisor_approved_at')->nullable();
            $table->string('kps_approved_by_role')->nullable();
            $table->foreignId('kps_approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('kps_approved_at')->nullable();
            $table->string('kpi_approved_by_role')->nullable();
            $table->foreignId('kpi_approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('kpi_approved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('capstone_groups', function (Blueprint $table) {
            $table->dropForeign(['approved_by_user_id']);
            $table->dropForeign(['coordinator_approved_by_user_id']);
            $table->dropForeign(['supervisor_approved_by_user_id']);
            $table->dropForeign(['kps_approved_by_user_id']);
            $table->dropForeign(['kpi_approved_by_user_id']);
            $table->dropColumn([
                'exam_time',
                'project_location',
                'proposed_exam_date',
                'proposed_exam_time',
                'exam_invitation_sent_at',
                'agreement_file',
                'drawings_file',
                'rab_file',
                'structural_calculation_file',
                'approved_by_role',
                'approved_by_user_id',
                'coordinator_approved_by_role',
                'coordinator_approved_by_user_id',
                'coordinator_approved_at',
                'supervisor_approved_by_role',
                'supervisor_approved_by_user_id',
                'supervisor_approved_at',
                'kps_approved_by_role',
                'kps_approved_by_user_id',
                'kps_approved_at',
                'kpi_approved_by_role',
                'kpi_approved_by_user_id',
                'kpi_approved_at',
            ]);
        });
    }
};
