<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('capstone_groups', function (Blueprint $table) {
            $table->foreignId('semester_master_id')
                ->nullable()
                ->after('expertise_id')
                ->constrained('semesters')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('capstone_groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('semester_master_id');
        });
    }
};
