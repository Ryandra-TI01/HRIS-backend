<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Membuat data absensi untuk bulan November 2025 (Tim FWD Batch 3):
     * - Setiap employee punya 10 hari absensi (tanggal 1-10 Nov 2025)
     * - Skip weekend (Sabtu & Minggu)
     * - Variasi check-in: 08:00 - 09:00
     * - Variasi check-out: 17:00 - 18:00
     * - Work hour dihitung otomatis via model method computeWorkHour()
     *
     * Dependency: EmployeeSeeder (butuh employee_id)
     */
    public function run(): void
    {
        $employees = Employee::all();

        if ($employees->isEmpty()) {
            $this->command->error('❌ No employees found! Please run EmployeeSeeder first.');
            return;
        }

        $totalAttendances = 0;

        foreach ($employees as $employee) {
            // Buat 10 hari absensi untuk setiap employee (1-10 November 2025)
            for ($day = 1; $day <= 10; $day++) {
                $date = Carbon::create(2025, 11, $day);

                // Skip weekend (Sabtu & Minggu)
                if ($date->isWeekend()) {
                    continue;
                }

                // Variasi check-in time (08:00 - 09:00)
                $checkInHour = rand(8, 9);
                $checkInMinute = rand(0, 59);
                $checkInTime = $date->copy()->setTime($checkInHour, $checkInMinute);

                // Check-out time (17:00 - 18:00)
                $checkOutHour = rand(17, 18);
                $checkOutMinute = rand(0, 59);
                $checkOutTime = $date->copy()->setTime($checkOutHour, $checkOutMinute);

                $attendance = Attendance::create([
                    'employee_id' => $employee->id,
                    'date' => $date->format('Y-m-d'),
                    'check_in_time' => $checkInTime,
                    'check_out_time' => $checkOutTime,
                    'work_hour' => 0, // Will be calculated
                ]);

                // Hitung work_hour menggunakan method dari model
                $attendance->computeWorkHour();
                $attendance->save();

                $totalAttendances++;
            }
        }

        $this->command->info("✅ {$totalAttendances} Attendances created successfully!");
    }
}
