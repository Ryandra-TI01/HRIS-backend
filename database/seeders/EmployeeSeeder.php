<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Employee;
use App\Enums\EmploymentStatus;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Membuat profil employee untuk semua user (Tim FWD Batch 3).
     *
     * Struktur organisasi:
     * - EMP001: Eko Muchamad Haryono (Admin HR) - No manager
     * - EMP002: Raka Muhammad Rabbani (Manager) - Managed by Eko
     * - EMP003: Ryandra Athaya Saleh (Developer) - Managed by Raka
     * - EMP004: Octaviani Nursalsabila (Designer) - Managed by Raka
     *
     * Dependency: UserSeeder (butuh user_id dan manager_id)
     */
    public function run(): void
    {
        // Ambil user berdasarkan email (sudah dibuat di UserSeeder)
        $adminUser = User::where('email', 'admin@hris.com')->first();
        $managerUser = User::where('email', 'manager@hris.com')->first();
        $employee1User = User::where('email', 'employee1@hris.com')->first();
        $employee2User = User::where('email', 'employee2@hris.com')->first();

        if (!$adminUser || !$managerUser || !$employee1User || !$employee2User) {
            $this->command->error('❌ Users not found! Please run UserSeeder first.');
            return;
        }

        $employees = [
            [
                'user_id' => $adminUser->id,
                'employee_code' => 'EMP001',
                'position' => 'HR Manager',
                'department' => 'Human Resources',
                'join_date' => '2023-01-15',
                'employment_status' => EmploymentStatus::PERMANENT,
                'contact' => '+628123456789',
                'manager_id' => null, // Eko (Admin HR) tidak punya manager
            ],
            [
                'user_id' => $managerUser->id,
                'employee_code' => 'EMP002',
                'position' => 'Engineering Manager',
                'department' => 'IT',
                'join_date' => '2023-03-01',
                'employment_status' => EmploymentStatus::PERMANENT,
                'contact' => '+628234567890',
                'manager_id' => $adminUser->id, // Raka (Manager) dibawahi Eko (Admin HR)
            ],
            [
                'user_id' => $employee1User->id,
                'employee_code' => 'EMP003',
                'position' => 'Software Developer',
                'department' => 'IT',
                'join_date' => '2023-06-15',
                'employment_status' => EmploymentStatus::PERMANENT,
                'contact' => '+628345678901',
                'manager_id' => $managerUser->id, // Ryandra (Developer) dibawahi Raka (Manager)
            ],
            [
                'user_id' => $employee2User->id,
                'employee_code' => 'EMP004',
                'position' => 'UI/UX Designer',
                'department' => 'IT',
                'join_date' => '2023-08-20',
                'employment_status' => EmploymentStatus::CONTRACT,
                'contact' => '+628456789012',
                'manager_id' => $managerUser->id, // Octaviani (Designer) dibawahi Raka (Manager)
            ],
        ];

        foreach ($employees as $employeeData) {
            Employee::create($employeeData);
        }

        $this->command->info('✅ 4 Employees created successfully (Tim FWD Batch 3)!');
    }
}
