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
        Schema::create('activity_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
            $table->string('session_name', 100);
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('qr_code_token', 255)->unique(); // Token Unik QR
            $table->enum('access_type', ['public', 'restricted'])->default('public'); // Public vs Login Only
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_sessions');
    }
};
