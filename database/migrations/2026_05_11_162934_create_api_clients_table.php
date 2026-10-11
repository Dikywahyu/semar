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
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            // Icon menggunakan class Tabler Icons (misal: ti ti-device-mobile)
            $table->string('icon')->default('ti ti-apps');
            $table->string('app_name'); // Nama Aplikasi
            $table->string('description')->nullable(); // Deskripsi Pendek

            // Kredensial Akses
            $table->string('client_id')->unique();
            $table->string('client_secret');

            // Keamanan Host
            $table->string('allowed_host')->default('*'); // Bisa domain atau IP

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
