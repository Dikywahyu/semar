<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('komponen_penilaian_pkl', function (Blueprint $table) {
            if (!Schema::hasColumn('komponen_penilaian_pkl', 'id_semester')) {
                $table->string('id_semester', 5)->default('LOCAL');
            }
            if (!Schema::hasColumn('komponen_penilaian_pkl', 'bobot')) {
                $table->decimal('bobot', 5, 2)->nullable();
            }
        });

        if (Schema::hasColumn('komponen_penilaian_pkl', 'bobot_persen')) {
            DB::table('komponen_penilaian_pkl')
                ->whereNull('bobot')
                ->update(['bobot' => DB::raw('bobot_persen')]);
        }
    }

    public function down(): void
    {
        Schema::table('komponen_penilaian_pkl', function (Blueprint $table) {
            $table->dropColumn(['id_semester', 'bobot']);
        });
    }
};
