<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_aktivitas_pkl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_pkl_id')->constrained('pengajuan_pkl')->onDelete('cascade');
            $table->foreignId('aktor_id')->constrained('users')->onDelete('cascade');
            $table->string('aksi'); 
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas_pkl');
    }
};