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
     * Membuat data absensi untuk 3 bulan (September - November 2025) dengan variasi tinggi:
     * - 50 employees x ~65 working days = ~3250 records
     * - Skip weekend (Sabtu & Minggu) + simulasi sick leave (5% absent)
     * - Variasi check-in realistis: 07:30 - 09:30 (employee behavior patterns)
     * - Variasi check-out: 16:30 - 19:00 (overtime simulation)
     * - Monthly patterns: Sep (back to work), Oct (productive), Nov (holiday prep)
     * - Individual employee punctuality & overtime tendencies
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
        $months = [
            ['year' => 2025, 'month' => 9, 'name' => 'September'],
            ['year' => 2025, 'month' => 10, 'name' => 'October'],
            ['year' => 2025, 'month' => 11, 'name' => 'November']
        ];

        foreach ($employees as $employee) {
            // Employee behavior patterns (some are more punctual than others)
            $punctualityLevel = rand(1, 3); // 1=very punctual, 2=normal, 3=often late
            $overtimeFrequency = rand(1, 4); // 1=never, 2=rarely, 3=sometimes, 4=often

            foreach ($months as $monthData) {
                $daysInMonth = Carbon::create($monthData['year'], $monthData['month'])->daysInMonth;
                
                // Monthly behavior adjustments
                $monthlyFactor = 1.0;
                $absentRate = 5; // Base 5% absent rate
                
                if ($monthData['month'] == 9) { // September - back to work
                    $monthlyFactor = 0.9; // Slightly less punctual
                    $absentRate = 7; // Higher absent rate (post-vacation)
                } elseif ($monthData['month'] == 10) { // October - productive month
                    $monthlyFactor = 1.1; // More productive
                    $absentRate = 3; // Lower absent rate
                } elseif ($monthData['month'] == 11) { // November - holiday preparation
                    $monthlyFactor = 0.95; // Slightly distracted
                    $absentRate = 8; // Higher absent rate (holiday prep)
                }
                
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = Carbon::create($monthData['year'], $monthData['month'], $day);

                    // Skip weekend (Sabtu & Minggu)
                    if ($date->isWeekend()) {
                        continue;
                    }

                    // Monthly adjusted absent rate
                    if (rand(1, 100) <= $absentRate) {
                        continue;
                    }                    // Variasi check-in berdasarkan punctuality level + monthly factor
                    $latenessPenalty = (1 - $monthlyFactor) * 15; // Max 15 min delay
                    
                    switch ($punctualityLevel) {
                        case 1: // Very punctual (07:30 - 08:15)
                            $checkInHour = rand(7, 8);
                            $baseMinute = $checkInHour == 7 ? rand(30, 59) : rand(0, 15);
                            $checkInMinute = min(59, $baseMinute + $latenessPenalty);
                            break;
                        case 2: // Normal (08:00 - 08:45)
                            $checkInHour = 8;
                            $baseMinute = rand(0, 45);
                            $checkInMinute = min(59, $baseMinute + $latenessPenalty);
                            if ($checkInMinute >= 60) {
                                $checkInHour = 9;
                                $checkInMinute = $checkInMinute - 60;
                            }
                            break;
                        case 3: // Often late (08:15 - 09:30)
                            $checkInHour = rand(8, 9);
                            $baseMinute = $checkInHour == 8 ? rand(15, 59) : rand(0, 30);
                            $checkInMinute = min(59, $baseMinute + $latenessPenalty);
                            if ($checkInMinute >= 60) {
                                $checkInHour = min(9, $checkInHour + 1);
                                $checkInMinute = $checkInMinute - 60;
                            }
                            break;
                    }

                    $checkInTime = $date->copy()->setTime($checkInHour, $checkInMinute);

                    // Variasi check-out berdasarkan overtime frequency
                    $baseCheckOutHour = 17; // Standard 17:00
                    switch ($overtimeFrequency) {
                        case 1: // Never overtime (16:30 - 17:15)
                            $checkOutHour = rand(16, 17);
                            $checkOutMinute = $checkOutHour == 16 ? rand(30, 59) : rand(0, 15);
                            break;
                        case 2: // Rarely (17:00 - 17:45)
                            $checkOutHour = 17;
                            $checkOutMinute = rand(0, 45);
                            break;
                        case 3: // Sometimes (17:00 - 18:30)
                            $checkOutHour = rand(17, 18);
                            $checkOutMinute = $checkOutHour == 18 ? rand(0, 30) : rand(0, 59);
                            break;
                        case 4: // Often overtime (17:30 - 19:00)
                            $checkOutHour = rand(17, 19);
                            $checkOutMinute = $checkOutHour == 17 ? rand(30, 59) : rand(0, 59);
                            if ($checkOutHour == 19) $checkOutMinute = 0; // Max 19:00
                            break;
                    }

                    $checkOutTime = $date->copy()->setTime($checkOutHour, $checkOutMinute);

                    // Pastikan check-out selalu setelah check-in (min 4 jam kerja)
                    if ($checkOutTime->diffInHours($checkInTime) < 4) {
                        $checkOutTime = $checkInTime->copy()->addHours(8); // Standard 8 jam
                    }

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
        }

        $this->command->info("✅ {$totalAttendances} Attendances created successfully!");
    }
}
