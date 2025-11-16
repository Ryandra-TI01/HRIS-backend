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
     * Membuat profil employee untuk semua user.
     *
     * Struktur organisasi:
     * - EMP001: Eko Muchamad Haryono (Admin HR) - No manager
     * - EMP002: Raka Muhammad Rabbani (IT Manager) - Managed by Eko
     * - EMP003: Yossy Indra Kusuma (Marketing Manager) - Managed by Eko
     * - EMP004: Dina Ayu Lestari (Finance Manager) - Managed by Eko
     * - EMP005: Ahmad Rizky Pratama (Operations Manager) - Managed by Eko
     * - EMP006: Ryandra Athaya Saleh (Developer) - Managed by Raka
     * - EMP007: Octaviani Nursalsabila (Designer) - Managed by Raka
     * - EMP008: Budi Santoso (QA Tester) - Managed by Raka
     * - EMP009: Sari Dewi (Marketing Executive) - Managed by Yossy
     * - EMP010: Andi Wijaya (Finance Staff) - Managed by Dina
     *
     * Dependency: UserSeeder (butuh user_id dan manager_id)
     */
    public function run(): void
    {
        // Ambil user berdasarkan email (sudah dibuat di UserSeeder)
        $adminUser = User::where('email', 'admin@hris.com')->first();
        $managerUser = User::where('email', 'manager@hris.com')->first();
        $yossyUser = User::where('email', 'yossy.manager@hris.com')->first();
        $dinaUser = User::where('email', 'dina.manager@hris.com')->first();
        $ahmadUser = User::where('email', 'ahmad.manager@hris.com')->first();
        $employee1User = User::where('email', 'employee1@hris.com')->first();
        $employee2User = User::where('email', 'employee2@hris.com')->first();
        $employee3User = User::where('email', 'employee3@hris.com')->first();
        $employee4User = User::where('email', 'employee4@hris.com')->first();
        $employee5User = User::where('email', 'employee5@hris.com')->first();

        if (!$adminUser || !$managerUser || !$yossyUser || !$dinaUser || !$ahmadUser || !$employee1User || !$employee2User || !$employee3User || !$employee4User || !$employee5User) {
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
                'user_id' => $yossyUser->id,
                'employee_code' => 'EMP003',
                'position' => 'Marketing Manager',
                'department' => 'Marketing',
                'join_date' => '2023-09-10',
                'employment_status' => EmploymentStatus::PERMANENT,
                'contact' => '+628567890123',
                'manager_id' => $adminUser->id, // Yossy (Marketing Manager) dibawahi Eko (Admin HR)
            ],
            [
                'user_id' => $dinaUser->id,
                'employee_code' => 'EMP004',
                'position' => 'Finance Manager',
                'department' => 'Finance',
                'join_date' => '2023-10-05',
                'employment_status' => EmploymentStatus::PERMANENT,
                'contact' => '+628678901234',
                'manager_id' => $adminUser->id, // Dina (Finance Manager) dibawahi Eko (Admin HR)
            ],
            [
                'user_id' => $ahmadUser->id,
                'employee_code' => 'EMP005',
                'position' => 'Operations Manager',
                'department' => 'Operations',
                'join_date' => '2023-11-01',
                'employment_status' => EmploymentStatus::PERMANENT,
                'contact' => '+628789012345',
                'manager_id' => $adminUser->id, // Ahmad (Operations Manager) dibawahi Eko (Admin HR)
            ],
            [
                'user_id' => $employee1User->id,
                'employee_code' => 'EMP006',
                'position' => 'Software Developer',
                'department' => 'IT',
                'join_date' => '2023-06-15',
                'employment_status' => EmploymentStatus::PERMANENT,
                'contact' => '+628345678901',
                'manager_id' => $managerUser->id, // Ryandra (Developer) dibawahi Raka (Manager)
            ],
            [
                'user_id' => $employee2User->id,
                'employee_code' => 'EMP007',
                'position' => 'UI/UX Designer',
                'department' => 'IT',
                'join_date' => '2023-08-20',
                'employment_status' => EmploymentStatus::CONTRACT,
                'contact' => '+628456789012',
                'manager_id' => $managerUser->id, // Octaviani (Designer) dibawahi Raka (Manager)
            ],
            [
                'user_id' => $employee3User->id,
                'employee_code' => 'EMP008',
                'position' => 'QA Tester',
                'department' => 'IT',
                'join_date' => '2023-12-01',
                'employment_status' => EmploymentStatus::PERMANENT,
                'contact' => '+628890123456',
                'manager_id' => $managerUser->id, // Budi (QA) dibawahi Raka (Manager)
            ],
            [
                'user_id' => $employee4User->id,
                'employee_code' => 'EMP009',
                'position' => 'Marketing Executive',
                'department' => 'Marketing',
                'join_date' => '2024-01-15',
                'employment_status' => EmploymentStatus::PERMANENT,
                'contact' => '+628901234567',
                'manager_id' => $yossyUser->id, // Sari (Marketing Exec) dibawahi Yossy (Marketing Manager)
            ],
            [
                'user_id' => $employee5User->id,
                'employee_code' => 'EMP010',
                'position' => 'Finance Staff',
                'department' => 'Finance',
                'join_date' => '2024-02-01',
                'employment_status' => EmploymentStatus::CONTRACT,
                'contact' => '+629012345678',
                'manager_id' => $dinaUser->id, // Andi (Finance Staff) dibawahi Dina (Finance Manager)
            ],
        ];

        foreach ($employees as $employeeData) {
            Employee::create($employeeData);
        }

        $this->command->info('✅ 10 Employees created successfully (1 Admin HR + 4 Managers + 5 Employees)!');
    }
}
