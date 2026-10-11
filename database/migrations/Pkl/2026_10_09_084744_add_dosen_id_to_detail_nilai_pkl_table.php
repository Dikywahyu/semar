<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_nilai_pkl', function (Blueprint $table) {
            // Menyimpan ID dosen yang memberikan penilaian pada komponen tersebut
            $table->string('dosen_id', 36)->after('komponen_penilaian_pkl_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('detail_nilai_pkl', function (Blueprint $table) {
            $table->dropColumn('dosen_id');
        });
    }
};