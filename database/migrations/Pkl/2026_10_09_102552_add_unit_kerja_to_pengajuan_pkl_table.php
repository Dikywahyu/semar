<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_pkl', function (Blueprint $table) {
            // Tambahkan kolom unit_kerja jika belum ada
            if (!Schema::hasColumn('pengajuan_pkl', 'unit_kerja')) {
                $table->string('unit_kerja')->nullable()->after('nama_instansi');
            }
            
            // Pastikan kolom alamat_instansi juga ada (jika sebelumnya terlewat)
            if (!Schema::hasColumn('pengajuan_pkl', 'alamat_instansi')) {
                $table->text('alamat_instansi')->nullable()->after('unit_kerja');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_pkl', function (Blueprint $table) {
            $table->dropColumn(['unit_kerja', 'alamat_instansi']);
        });
    }
};