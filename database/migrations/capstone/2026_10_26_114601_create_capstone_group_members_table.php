<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('capstone_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capstone_group_id')->constrained('capstone_groups')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            // Status undangan (Max 2 orang tambahan, bisa setuju/tolak)
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capstone_group_members');
    }
};
