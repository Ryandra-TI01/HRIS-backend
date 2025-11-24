<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardEmployeeController extends Controller
{
    /**
     * Dashboard Employee - Overview data untuk employee
     *
     * SECTION 1 – Ringkasan Info Personal
     * - Card: Departemen Saya
     * - Card: Manager Saya
     *
     * SECTION 2 – Ringkasan Kehadiran
     * - Card: Total Jam Kerja (Bulan Ini)
     * - Card: Hari Hadir (Bulan Ini)
     * - Chart: Tren Jam Kerja Harian Saya
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::guard('api')->user();

        // Pastikan user adalah employee atau admin_hr
        if (!$user || (!$user->isEmployee() && !$user->isAdminHr())) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Employee or Admin HR role required.'
            ], 403);
        }

        // Pastikan punya profile employee
        $employee = $user->employee;
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee profile not found.'
            ], 422);
        }

        // === SECTION 1: Ringkasan Info Personal ===
        $personalInfo = [
            'my_department' => $employee->department ?? 'Not Assigned',
            'my_manager' => $employee->manager?->name ?? 'No Manager Assigned',
        ];

        // === SECTION 2: Ringkasan Kehadiran (Bulan Ini) ===
        $now = Carbon::now();
        $yearMonth = $now->format('Y-m');

        // Ambil data kehadiran untuk bulan ini
        $attendances = Attendance::where('employee_id', $employee->id)
            ->inMonth($yearMonth)
            ->orderBy('date')
            ->get();

        // Hitung jumlah hari hadir
        $daysPresent = $attendances->whereNotNull('check_in_time')->count();

        // === TOTAL JAM KERJA BULAN INI → FORMAT HH:MM ===
        $totalMinutes = $attendances->sum(function ($att) {
            // Pakai accessor $att->work_hour → otomatis jadi "07:11", "09:20", dll
            if (!$att->work_hour || $att->work_hour === '00:00') {
                return 0;
            }
            [$hours, $minutes] = explode(':', $att->work_hour);
            return (int)$hours * 60 + (int)$minutes;
        });

        $totalHours   = floor($totalMinutes / 60);
        $totalMins    = $totalMinutes % 60;
        $totalWorkHoursFormatted = sprintf('%02d:%02d', $totalHours, $totalMins);
        // Hasil: "47:55", "188:30", dll

        // === DATA CHART HARIAN → SEMUA DALAM FORMAT HH:MM ===
        $dailyChart = $attendances->map(function ($attendance) {
            return [
                'date'                 => Carbon::parse($attendance->date)->format('Y-m-d'),
                'work_hours'           => $attendance->work_hour ?? '00:00',           // HH:MM
                'work_hours_formatted' => $attendance->work_hour ?? '00:00',           // HH:MM (sama)
                'day_name'             => Carbon::parse($attendance->date)->format('D'),
                'status'               => $attendance->check_in_time ? 'present' : 'absent'
            ];
        })->values()->toArray();

        return response()->json([
            'success' => true,
            'message' => 'Employee dashboard data retrieved successfully',
            'data' => [
                'personal_info' => $personalInfo,
                'attendance_summary' => [
                    'cards' => [
                        'total_work_hours_this_month' => $totalWorkHoursFormatted, // ← "47:55"
                        'days_present_this_month'     => $daysPresent,
                    ],
                    'chart_work_hours_daily' => $dailyChart,
                ],
                'period_info' => [
                    'current_month' => $now->format('F Y'),
                    'year_month'    => $yearMonth,
                ]
            ]
        ]);
    }
}
