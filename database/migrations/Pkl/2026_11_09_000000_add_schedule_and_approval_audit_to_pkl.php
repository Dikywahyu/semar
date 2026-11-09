<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_pkl', function (Blueprint $table) {
            $table->dateTime('usulan_tanggal_seminar')->nullable();
            $table->text('catatan_usulan_jadwal')->nullable();
            $table->foreignId('schedule_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('schedule_approved_at')->nullable();
            $table->foreignId('acc_seminar_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('acc_seminar_at')->nullable();
            $table->foreignId('final_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('final_approved_at')->nullable();
        });

        Schema::table('berkas_seminar_pkl', function (Blueprint $table) {
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('berkas_seminar_pkl', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['verified_at']);
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['approved_at']);
        });

        Schema::table('pengajuan_pkl', function (Blueprint $table) {
            $table->dropConstrainedForeignId('final_approved_by');
            $table->dropColumn(['final_approved_at', 'catatan_usulan_jadwal', 'usulan_tanggal_seminar']);
            $table->dropConstrainedForeignId('acc_seminar_by');
            $table->dropColumn(['acc_seminar_at']);
            $table->dropConstrainedForeignId('schedule_approved_by');
            $table->dropColumn(['schedule_approved_at']);
        });
    }
};
