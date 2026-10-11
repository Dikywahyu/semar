<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kanban_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('kanban_boards')->onDelete('cascade');
            $table->string('name'); // Contoh: To Do, In Progress
            $table->string('color', 7)->default('#6c757d'); // Menyimpan kode HEX warna kolom
            $table->integer('position')->default(0); // Untuk sorting urutan kolom
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_stages');
    }
};