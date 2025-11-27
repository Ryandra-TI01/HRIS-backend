<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use App\Models\SalarySlip;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SalarySlipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Membuat data slip gaji (Tim FWD Batch 3):
     * - Setiap employee punya 2 slip (Oktober & November 2025)
     * - Created by Admin HR (Eko Muchamad Haryono)
     * - Gaji berbeda berdasarkan posisi (HR Manager, Engineering Manager, Developer, Designer)
     * - Total salary dihitung otomatis via model method computeTotalSalary()
     *
     * Dependency: EmployeeSeeder (butuh employee_id), UserSeeder (butuh admin_hr untuk created_by)
     */
    public function run(): void
    {
        $employees = Employee::all();
        $adminHr = User::where('role', 'admin_hr')->first();

        if ($employees->isEmpty()) {
            $this->command->error('❌ No employees found! Please run EmployeeSeeder first.');
            return;
        }

        if (!$adminHr) {
            $this->command->error('❌ No admin HR found! Please run UserSeeder first.');
            return;
        }

        // Mapping gaji berdasarkan posisi dengan range yang realistis
        $salaryByPosition = [
            'HR Manager' => [
                'basic_range' => [14000000, 16000000],
                'allowance_range' => [2500000, 3500000],
                'deduction_range' => [700000, 800000]
            ],
            'Engineering Manager' => [
                'basic_range' => [17000000, 20000000],
                'allowance_range' => [3500000, 4500000],
                'deduction_range' => [850000, 1000000]
            ],
            'Marketing Manager' => [
                'basic_range' => [15000000, 18000000],
                'allowance_range' => [3000000, 4000000],
                'deduction_range' => [750000, 900000]
            ],
            'Finance Manager' => [
                'basic_range' => [16000000, 19000000],
                'allowance_range' => [3200000, 4200000],
                'deduction_range' => [800000, 950000]
            ],
            'Operations Manager' => [
                'basic_range' => [14000000, 17000000],
                'allowance_range' => [2800000, 3800000],
                'deduction_range' => [700000, 850000]
            ],
            'Software Developer' => [
                'basic_range' => [11000000, 13000000],
                'allowance_range' => [2000000, 3000000],
                'deduction_range' => [550000, 650000]
            ],
            'Backend Developer' => [
                'basic_range' => [12000000, 14000000],
                'allowance_range' => [2200000, 3200000],
                'deduction_range' => [600000, 700000]
            ],
            'DevOps Engineer' => [
                'basic_range' => [13000000, 15000000],
                'allowance_range' => [2500000, 3500000],
                'deduction_range' => [650000, 750000]
            ],
            'UI/UX Designer' => [
                'basic_range' => [9000000, 11000000],
                'allowance_range' => [1800000, 2500000],
                'deduction_range' => [450000, 550000]
            ],
            'QA Tester' => [
                'basic_range' => [8500000, 10500000],
                'allowance_range' => [1700000, 2300000],
                'deduction_range' => [425000, 525000]
            ],
            'Marketing Executive' => [
                'basic_range' => [7500000, 9500000],
                'allowance_range' => [1500000, 2200000],
                'deduction_range' => [375000, 475000]
            ],
            'Content Creator' => [
                'basic_range' => [7000000, 9000000],
                'allowance_range' => [1400000, 2000000],
                'deduction_range' => [350000, 450000]
            ],
            'Finance Staff' => [
                'basic_range' => [8000000, 10000000],
                'allowance_range' => [1600000, 2400000],
                'deduction_range' => [400000, 500000]
            ],
            'Accountant' => [
                'basic_range' => [8500000, 10500000],
                'allowance_range' => [1700000, 2500000],
                'deduction_range' => [425000, 525000]
            ],
            'Operations Staff' => [
                'basic_range' => [7000000, 9000000],
                'allowance_range' => [1400000, 2000000],
                'deduction_range' => [350000, 450000]
            ]
        ];

        // 6 months of salary data
        $salaryPeriods = [
            '2025-06', '2025-07', '2025-08', '2025-09', '2025-10', '2025-11'
        ];

        // Remarks templates
        $remarksTemplates = [
            'Gaji bulan %s - Regular payment',
            'Salary %s - Include performance bonus',
            'Gaji %s - With overtime allowance',
            'Salary %s - Standard monthly payment',
            'Gaji %s - Include project completion bonus'
        ];

        $totalSlips = 0;

        foreach ($employees as $employee) {
            // Get salary range based on position
            $salaryRange = $salaryByPosition[$employee->position] ?? [
                'basic_range' => [7000000, 8500000],
                'allowance_range' => [1400000, 1800000],
                'deduction_range' => [350000, 425000]
            ];

            // Set base salary for this employee (consistent across months)
            $baseSalary = rand($salaryRange['basic_range'][0], $salaryRange['basic_range'][1]);
            $baseAllowance = rand($salaryRange['allowance_range'][0], $salaryRange['allowance_range'][1]);
            $baseDeduction = rand($salaryRange['deduction_range'][0], $salaryRange['deduction_range'][1]);

            foreach ($salaryPeriods as $period) {
                // Add monthly variations (±5% for realism)
                $monthlyBasicVariation = $baseSalary * (rand(-5, 5) / 100);
                $monthlyAllowanceVariation = $baseAllowance * (rand(-10, 15) / 100); // More variation in allowance
                $monthlyDeductionVariation = $baseDeduction * (rand(-3, 8) / 100);

                $finalBasic = $baseSalary + $monthlyBasicVariation;
                $finalAllowance = $baseAllowance + $monthlyAllowanceVariation;
                $finalDeduction = $baseDeduction + $monthlyDeductionVariation;

                // Round to nearest thousand
                $finalBasic = round($finalBasic / 1000) * 1000;
                $finalAllowance = round($finalAllowance / 1000) * 1000;
                $finalDeduction = round($finalDeduction / 1000) * 1000;

                $monthName = Carbon::createFromFormat('Y-m', $period)->format('F Y');
                $remarks = sprintf($remarksTemplates[array_rand($remarksTemplates)], $monthName);

                $slip = SalarySlip::create([
                    'employee_id' => $employee->id,
                    'created_by' => $adminHr->id,
                    'period_month' => $period,
                    'basic_salary' => $finalBasic,
                    'allowance' => $finalAllowance,
                    'deduction' => $finalDeduction,
                    'total_salary' => 0, // Will be calculated
                    'remarks' => $remarks,
                    'created_at' => Carbon::createFromFormat('Y-m', $period)->addDays(rand(25, 30)), // Created end of month
                    'updated_at' => Carbon::createFromFormat('Y-m', $period)->addDays(rand(25, 30))
                ]);

                $slip->computeTotalSalary();
                $slip->save();

                $totalSlips++;
            }
        }

        $this->command->info("✅ {$totalSlips} Salary Slips created successfully!");
    }
}
