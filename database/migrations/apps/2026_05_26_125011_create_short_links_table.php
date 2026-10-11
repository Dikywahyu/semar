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
        Schema::create('short_links', function (Blueprint $table) {
            $table->id();
            // Relasi ke user pembuat short link
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Kode unik pendek (contoh: 'diky-sign' atau 'krs-01')
            $table->string('short_code')->unique();
            
            // URL tujuan asli yang sangat panjang
            $table->text('destination_url');
            
            // Counter opsional untuk menghitung berapa kali link diklik
            $table->integer('clicks_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('short_links');
    }
};
