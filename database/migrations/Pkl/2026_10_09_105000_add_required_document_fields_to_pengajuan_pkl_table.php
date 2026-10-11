<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_pkl', function (Blueprint $table) {
            foreach ([
                'berkas_proposal',
                'berkas_logbook',
                'berkas_kartu_bimbingan',
                'berkas_draft_laporan',
                'berkas_nilai_pamong',
                'berkas_persetujuan_pamong',
            ] as $column) {
                if (!Schema::hasColumn('pengajuan_pkl', $column)) {
                    $table->text($column)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_pkl', function (Blueprint $table) {
            $table->dropColumn([
                'berkas_proposal',
                'berkas_logbook',
                'berkas_kartu_bimbingan',
                'berkas_draft_laporan',
                'berkas_nilai_pamong',
                'berkas_persetujuan_pamong',
            ]);
        });
    }
};
