<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('identifier_number', 50)->nullable()->unique()->after('id'); // NIM/NIP/NIK
            $table->string('phone', 30)->nullable()->after('email');
            $table->enum('gender', ['L', 'P'])->nullable()->after('phone'); // L/P
            $table->string('position_title', 100)->nullable()->after('gender'); // Jabatan/Status
            $table->string('institution', 150)->nullable()->after('position_title'); // Instansi
            $table->enum('role', ['admin', 'operator', 'participant'])->default('participant')->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'identifier_number', 
                'phone', 
                'gender', 
                'position_title', 
                'institution', 
                'role'
            ]);
        });
    }
};
