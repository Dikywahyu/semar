<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kanban_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('kanban_stages')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->integer('position')->default(0); // Urutan kartu di dalam kolom
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Kreator tugas
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_tasks');
    }
};