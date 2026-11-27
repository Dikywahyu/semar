<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pengajuan_pkl')) {
            Schema::table('pengajuan_pkl', function (Blueprint $table) {
                if (!Schema::hasColumn('pengajuan_pkl', 'status_verifikasi_pengajuan')) {
                    $table->string('status_verifikasi_pengajuan', 50)->default('Menunggu Verifikasi')->after('status');
                }
                if (!Schema::hasColumn('pengajuan_pkl', 'alasan_penolakan')) {
                    $table->text('alasan_penolakan')->nullable()->after('catatan_admin');
                }
                if (!Schema::hasColumn('pengajuan_pkl', 'verified_pengajuan_by')) {
                    $table->foreignId('verified_pengajuan_by')->nullable()->after('status_verifikasi_pengajuan')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('pengajuan_pkl', 'verified_pengajuan_at')) {
                    $table->dateTime('verified_pengajuan_at')->nullable()->after('verified_pengajuan_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pengajuan_pkl')) {
            Schema::table('pengajuan_pkl', function (Blueprint $table) {
                if (Schema::hasColumn('pengajuan_pkl', 'verified_pengajuan_by')) {
                    $table->dropConstrainedForeignId('verified_pengajuan_by');
                }
                $columnsToDrop = [];
                if (Schema::hasColumn('pengajuan_pkl', 'status_verifikasi_pengajuan')) {
                    $columnsToDrop[] = 'status_verifikasi_pengajuan';
                }
                if (Schema::hasColumn('pengajuan_pkl', 'alasan_penolakan')) {
                    $columnsToDrop[] = 'alasan_penolakan';
                }
                if (Schema::hasColumn('pengajuan_pkl', 'verified_pengajuan_at')) {
                    $columnsToDrop[] = 'verified_pengajuan_at';
                }
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }
};
