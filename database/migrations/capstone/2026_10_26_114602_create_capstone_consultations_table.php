<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('capstone_consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capstone_group_id')->constrained('capstone_groups')->cascadeOnDelete();
            $table->date('consultation_date');
            $table->text('notes');
            $table->enum('status', ['pending', 'approved'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capstone_consultations');
    }
};
