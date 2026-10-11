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
        Schema::create('kanban_board_members', function (Blueprint $table) {
            $table->id();
            // Relasi Many-to-Many ke tabel kanban_boards
            // onDelete('cascade') memastikan jika board dihapus, data kolaborator di sini ikut bersih
            $table->foreignId('board_id')
                  ->constrained('kanban_boards')
                  ->onDelete('cascade');
                  
            // Relasi ke tabel users (siapa user/dosen/staff yang diundang)
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            // Menentukan hak akses user di dalam papan tersebut (opsional)
            // 'member' bisa edit/geser kartu, 'viewer' hanya bisa melihat saja
            $table->string('role')->default('member'); 
            
            $table->timestamps();

            // Mencegah duplikasi data: satu user tidak bisa diundang dua kali di board yang sama
            $table->unique(['board_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_board_members');
    }
};
