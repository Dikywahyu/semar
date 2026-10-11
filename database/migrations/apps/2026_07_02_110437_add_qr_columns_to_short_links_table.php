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
        Schema::table('short_links', function (Blueprint $table) {
            //
            // Menambahkan kolom untuk QR
            $table->string('qr_code_path')->nullable()->after('destination_url');
            $table->string('logo_path')->nullable()->after('qr_code_path');
            
            // Menambahkan index agar query pencarian (redirect) lebih cepat
            $table->index('short_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('short_links', function (Blueprint $table) {
            //
            $table->dropColumn(['qr_code_path', 'logo_path']);
            $table->dropIndex(['short_code']);
        });
    }
};
