<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pengajuan_pkl') && ! Schema::hasColumn('pengajuan_pkl', 'usulan_dosen_penguji')) {
            Schema::table('pengajuan_pkl', function (Blueprint $table) {
                $table->json('usulan_dosen_penguji')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pengajuan_pkl') && Schema::hasColumn('pengajuan_pkl', 'usulan_dosen_penguji')) {
            Schema::table('pengajuan_pkl', function (Blueprint $table) {
                $table->dropColumn('usulan_dosen_penguji');
            });
        }
    }
};
