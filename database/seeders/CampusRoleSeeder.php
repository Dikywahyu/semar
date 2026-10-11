<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CampusRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Daftar Roles
        $roles = [
            'Super Admin',
            'Rektor',
            'Dekan',
            'BAAK',
            'BAUK',
            'Akademik',
            'Kemahasiswaan',
            'LPM',
            'LPPM',
            'Dosen',
            'DosenLB',
            'Mahasiswa',
            'Kajur',
            'Kaprodi',
            'Kadep',
            'Kahim',
            'Kabem',
            'Guru Besar',
        ];

        // 2. Daftar Permissions (CRUD & Report)
        $permissions = [
            // Manajemen User & Akses
            'user-list',
            'user-create',
            'user-edit',
            'user-delete',
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',

            // Modul Akademik
            'academic-view',
            'academic-create',
            'academic-edit',
            'academic-delete',
            'academic-report',
            'curriculum-manage',

            // Modul Nilai
            'grade-view',
            'grade-create',
            'grade-edit',
            'grade-validate',
            'grade-report',

            // Modul Keuangan
            'payment-view',
            'payment-create',
            'payment-edit',
            'payment-report',

            // Modul Penelitian & Pengabdian
            'research-view',
            'research-create',
            'research-edit',
            'research-delete',
            'research-report',

            // Modul Penjaminan Mutu
            'audit-view',
            'audit-create',
            'audit-report',

            // Modul Kemahasiswaan & Organisasi
            'student-view',
            'student-create',
            'student-edit',
            'activity-propose',
            'activity-approve',
            'activity-report',

            // Modul Log & Monitoring
            'log-view',
            'log-report',
        ];

        // 3. Create Permissions untuk guard web dan api
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // 4. Create Roles & Assign Permissions
        // 4. Create Roles & Assign Permissions
        foreach ($roles as $roleName) {
            // Buat Role untuk kedua guard
            $roleWeb = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $roleApi = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);

            // Tentukan daftar permission untuk role ini
            $rolePermissions = [];

            if ($roleName === 'Super Admin') {
                // Ambil semua permission sesuai guard masing-masing
                $roleWeb->givePermissionTo(Permission::where('guard_name', 'web')->get());
                $roleApi->givePermissionTo(Permission::where('guard_name', 'api')->get());
                continue;
            }

            // Mapping hak akses spesifik
            if (in_array($roleName, ['BAAK', 'Akademik'])) {
                $rolePermissions = ['academic-view', 'academic-create', 'academic-edit', 'academic-report', 'student-view', 'student-create', 'student-edit'];
            } elseif ($roleName === 'BAUK') {
                $rolePermissions = ['payment-view', 'payment-create', 'payment-edit', 'payment-report'];
            } elseif (in_array($roleName, ['Dosen', 'DosenLB', 'Guru Besar'])) {
                $rolePermissions = ['grade-view', 'grade-create', 'grade-edit', 'academic-view'];
            } elseif (in_array($roleName, ['Kaprodi', 'Kajur', 'Kadep'])) {
                $rolePermissions = ['grade-view', 'grade-validate', 'grade-report', 'curriculum-manage', 'academic-view'];
            }

            // Assign permission secara aman (hanya jika ada daftar permission)
            if (!empty($rolePermissions)) {
                // Berikan permission guard 'web' ke role guard 'web'
                $pWeb = Permission::whereIn('name', $rolePermissions)->where('guard_name', 'web')->get();
                $roleWeb->givePermissionTo($pWeb);

                // Berikan permission guard 'api' ke role guard 'api'
                $pApi = Permission::whereIn('name', $rolePermissions)->where('guard_name', 'api')->get();
                $roleApi->givePermissionTo($pApi);
            }
        }
    }
}
