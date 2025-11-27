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
     * Membuat profil employee untuk semua 50 users.
     *
     * Struktur organisasi:
     * - EMP001: Eko Muchamad Haryono (Admin HR) - No manager
     * - EMP002-005: 4 Managers - Managed by Admin HR
     * - EMP006-050: 45 Employees - Distributed across departments
     *   - IT Department: ~15 employees (Developers, Designers, QA, DevOps, etc)
     *   - Marketing: ~10 employees (Executives, Content Creators, Social Media, etc)
     *   - Finance: ~10 employees (Accountants, Finance Staff, Auditors, etc)
     *   - Operations: ~10 employees (Operations Staff, Admin, Logistics, etc)
     *
     * Dependency: UserSeeder (butuh user_id dan manager_id)
     */
    public function run(): void
    {
        // Ambil semua users
        $adminUser = User::where('email', 'admin@hris.com')->first();
        $managerUser = User::where('email', 'manager@hris.com')->first();
        $yossyUser = User::where('email', 'yossy.manager@hris.com')->first();
        $dinaUser = User::where('email', 'dina.manager@hris.com')->first();
        $ahmadUser = User::where('email', 'ahmad.manager@hris.com')->first();

        // Check managers exist
        if (!$adminUser || !$managerUser || !$yossyUser || !$dinaUser || !$ahmadUser) {
            $this->command->error('❌ Manager users not found! Please run UserSeeder first.');
            return;
        }

        // Get all employee users (employee1-45)
        $employeeUsers = [];
        for ($i = 1; $i <= 45; $i++) {
            $user = User::where('email', "employee{$i}@hris.com")->first();
            if (!$user) {
                $this->command->error("❌ Employee user {$i} not found! Please run UserSeeder first.");
                return;
            }
            $employeeUsers[] = $user;
        }

        // Admin HR dan Managers (EMP001-005)
        $employees = [
            [
                'user_id' => $adminUser->id,
                'employee_code' => 'EMP001',
                'position' => 'HR Manager',
                'department' => 'Human Resources',
                'join_date' => '2023-01-15',
                'employment_status' => EmploymentStatus::PERMANENT,
                'contact' => '+628123456789',
                'manager_id' => null,
            ],
            [
                'user_id' => $managerUser->id,
                'employee_code' => 'EMP002',
                'position' => 'Engineering Manager',
                'department' => 'IT',
                'join_date' => '2023-03-01',
                'employment_status' => EmploymentStatus::PERMANENT,
                'contact' => '+628234567890',
                'manager_id' => $adminUser->id,
            ],
            [
                'user_id' => $yossyUser->id,
                'employee_code' => 'EMP003',
                'position' => 'Marketing Manager',
                'department' => 'Marketing',
                'join_date' => '2023-09-10',
                'employment_status' => EmploymentStatus::PERMANENT,
                'contact' => '+628567890123',
                'manager_id' => $adminUser->id,
            ],
            [
                'user_id' => $dinaUser->id,
                'employee_code' => 'EMP004',
                'position' => 'Finance Manager',
                'department' => 'Finance',
                'join_date' => '2023-10-05',
                'employment_status' => EmploymentStatus::PERMANENT,
                'contact' => '+628678901234',
                'manager_id' => $adminUser->id,
            ],
            [
                'user_id' => $ahmadUser->id,
                'employee_code' => 'EMP005',
                'position' => 'Operations Manager',
                'department' => 'Operations',
                'join_date' => '2023-11-01',
                'employment_status' => EmploymentStatus::PERMANENT,
                'contact' => '+628789012345',
                'manager_id' => $adminUser->id,
            ],
        ];

        // Employee positions berdasarkan departemen
        $departments = [
            'IT' => [
                'positions' => ['Software Developer', 'Backend Developer', 'Frontend Developer', 'Full Stack Developer',
                               'UI/UX Designer', 'Graphic Designer', 'QA Tester', 'QA Engineer', 'DevOps Engineer',
                               'System Administrator', 'Database Administrator', 'Mobile Developer', 'Web Developer',
                               'Security Engineer', 'Data Engineer'],
                'manager_id' => $managerUser->id,
                'count' => 15
            ],
            'Marketing' => [
                'positions' => ['Marketing Executive', 'Content Creator', 'Social Media Specialist', 'SEO Specialist',
                               'Digital Marketing Manager', 'Brand Manager', 'Marketing Analyst', 'Copywriter',
                               'Creative Director', 'Public Relations'],
                'manager_id' => $yossyUser->id,
                'count' => 10
            ],
            'Finance' => [
                'positions' => ['Accountant', 'Finance Staff', 'Financial Analyst', 'Tax Specialist', 'Auditor',
                               'Budget Analyst', 'Treasury Staff', 'Accounts Payable', 'Accounts Receivable', 'Payroll Staff'],
                'manager_id' => $dinaUser->id,
                'count' => 10
            ],
            'Operations' => [
                'positions' => ['Operations Staff', 'Admin Staff', 'HR Generalist', 'Recruiter', 'Logistics Coordinator',
                               'Procurement Staff', 'Legal Staff', 'Compliance Officer', 'Office Manager', 'Executive Assistant'],
                'manager_id' => $ahmadUser->id,
                'count' => 10
            ]
        ];

        // Generate employee data (EMP006-050)
        $empNumber = 6;
        $employeeIndex = 0;

        foreach ($departments as $deptName => $deptData) {
            for ($i = 0; $i < $deptData['count']; $i++) {
                if ($employeeIndex >= 45) break; // Safety check

                $user = $employeeUsers[$employeeIndex];
                $position = $deptData['positions'][$i % count($deptData['positions'])];

                // Random join date between 2023-2024
                $joinYear = rand(2023, 2024);
                $joinMonth = rand(1, 12);
                $joinDay = rand(1, 28);

                // Random employment status (70% permanent, 20% contract, 10% intern)
                $statusRand = rand(1, 100);
                if ($statusRand <= 70) {
                    $empStatus = EmploymentStatus::PERMANENT;
                } elseif ($statusRand <= 90) {
                    $empStatus = EmploymentStatus::CONTRACT;
                } else {
                    $empStatus = EmploymentStatus::INTERN;
                }

                $employees[] = [
                    'user_id' => $user->id,
                    'employee_code' => sprintf('EMP%03d', $empNumber),
                    'position' => $position,
                    'department' => $deptName,
                    'join_date' => sprintf('%d-%02d-%02d', $joinYear, $joinMonth, $joinDay),
                    'employment_status' => $empStatus,
                    'contact' => '+62' . rand(800000000, 899999999),
                    'manager_id' => $deptData['manager_id'],
                ];

                $empNumber++;
                $employeeIndex++;
            }
        }

        foreach ($employees as $employeeData) {
            Employee::create($employeeData);
        }

        $this->command->info('✅ 50 Employees created successfully (1 Admin HR + 4 Managers + 45 Employees)!');
    }
}
