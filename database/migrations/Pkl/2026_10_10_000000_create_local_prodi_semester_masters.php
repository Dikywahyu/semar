<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prodis', function (Blueprint $table) {
            $table->id();
            $table->string('kode_prodi', 30)->unique();
            $table->string('nama_prodi', 255);
            $table->string('jenjang', 20);
            $table->string('fakultas', 255)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['nama_prodi', 'jenjang']);
        });

        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('tahun_akademik', 9);
            $table->string('periode', 10);
            $table->string('kode_semester', 5)->unique();
            $table->boolean('aktif')->default(false);
            $table->timestamps();

            $table->unique(['tahun_akademik', 'periode']);
        });

        Schema::table('dosens', function (Blueprint $table) {
            $table->foreignId('prodi_id')->nullable()->constrained('prodis')->restrictOnDelete();
        });

        Schema::table('mahasiswa_registrasis', function (Blueprint $table) {
            $table->foreignId('prodi_id')->nullable()->constrained('prodis')->restrictOnDelete();
        });

        Schema::table('pengajuan_pkl', function (Blueprint $table) {
            $table->foreignId('semester_master_id')->nullable()->constrained('semesters')->restrictOnDelete();
        });

        Schema::table('komponen_penilaian_pkl', function (Blueprint $table) {
            $table->foreignId('semester_master_id')->nullable()->constrained('semesters')->restrictOnDelete();
        });

        $this->linkExistingPrograms('dosens', 'program_studi');
        $this->linkExistingPrograms('mahasiswa_registrasis', 'nama_program_studi');
    }

    public function down(): void
    {
        Schema::table('komponen_penilaian_pkl', function (Blueprint $table) {
            $table->dropConstrainedForeignId('semester_master_id');
        });
        Schema::table('pengajuan_pkl', function (Blueprint $table) {
            $table->dropConstrainedForeignId('semester_master_id');
        });
        Schema::table('mahasiswa_registrasis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prodi_id');
        });
        Schema::table('dosens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prodi_id');
        });

        Schema::dropIfExists('semesters');
        Schema::dropIfExists('prodis');
    }

    private function linkExistingPrograms(string $table, string $column): void
    {
        DB::table($table)
            ->whereNotNull($column)
            ->selectRaw(($table === 'dosens' ? 'id_dosen' : 'id_registrasi_mahasiswa') . " as id, {$column}")
            ->orderBy($table === 'dosens' ? 'id_dosen' : 'id_registrasi_mahasiswa')
            ->chunk(200, function ($rows) use ($table, $column) {
                foreach ($rows as $row) {
                    $name = trim((string) $row->{$column});
                    if ($name === '') {
                        continue;
                    }

                    $prodi = DB::table('prodis')
                        ->whereRaw('LOWER(nama_prodi) = ?', [mb_strtolower($name)])
                        ->first();

                    if ($prodi) {
                        DB::table($table)
                            ->where($table === 'dosens' ? 'id_dosen' : 'id_registrasi_mahasiswa', $row->id)
                            ->update(['prodi_id' => $prodi->id]);
                        continue;
                    }

                    $kode = 'OLD-' . substr(hash('sha256', mb_strtolower($name)), 0, 12);
                    $prodiId = DB::table('prodis')->insertGetId([
                        'kode_prodi' => $kode,
                        'nama_prodi' => $name,
                        'jenjang' => 'Tidak diketahui',
                        'aktif' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table($table)
                        ->where($table === 'dosens' ? 'id_dosen' : 'id_registrasi_mahasiswa', $row->id)
                        ->update(['prodi_id' => $prodiId]);
                }
            });
    }
};
