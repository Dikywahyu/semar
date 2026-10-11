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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('activity_sessions')->onDelete('cascade');
            
            // Peserta Internal (Jika Login)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            
            // Peserta Eksternal / Tamu Public Form
            $table->string('guest_name', 100)->nullable();
            $table->string('guest_email', 100)->nullable();
            $table->string('guest_phone', 30)->nullable();
            $table->enum('guest_gender', ['L', 'P'])->nullable();
            $table->string('guest_position', 100)->nullable();
            $table->string('guest_organization', 150)->nullable();
            
            $table->dateTime('check_in_time');
            $table->enum('status', ['present', 'late', 'permission', 'absent'])->default('present');
            $table->enum('method', ['qr_code', 'public_form', 'manual_operator', 'self_checkin'])->default('qr_code');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            // Mencegah double check-in untuk user internal
            $table->unique(['session_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
