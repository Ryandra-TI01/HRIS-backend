<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardAdminController extends Controller
{
    /**
     * Dashboard Admin HR - Overview data untuk admin HR
     *
     * SECTION 1 – Ringkasan Karyawan
     * - Card: Total Karyawan
     * - Card: User Aktif
     * - Chart: Karyawan Baru per Bulan
     *
     * SECTION 2 – Ringkasan Kehadiran
     * - Card: Record Kehadiran Hari Ini
     * - Card: Rata-rata Jam Kerja (Bulan Ini)
     * - Chart: Kehadiran per Hari (Bulanan)
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::guard('api')->user();

        // Pastikan user adalah admin HR
        if (!$user || !$user->isAdminHr()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin HR role required.'
            ], 403);
        }

        // === SECTION 1: Ringkasan Karyawan ===

        // Total Karyawan
        $totalEmployees = Employee::count();

        // User Aktif (users yang status_active = true dan punya employee profile)
        $activeUsers = User::where('status_active', true)
            ->whereHas('employee')
            ->count();

        // Karyawan Baru per Bulan (12 bulan terakhir)
        $employeesPerMonth = Employee::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Carbon::createFromDate($item->year, $item->month)->format('M Y'),
                    'count' => $item->count,
                    'year_month' => sprintf('%04d-%02d', $item->year, $item->month)
                ];
            })->toArray();

        // === SECTION 2: Ringkasan Kehadiran ===
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $yearMonth = $now->format('Y-m');

        // Record Kehadiran Hari Ini
        $attendanceToday = Attendance::whereDate('date', $today)->count();

        // Rata-rata Jam Kerja Bulan Ini
        $avgWorkHours = Attendance::inMonth($yearMonth)
            ->avg('work_hour') ?? 0;

        // Kehadiran per Hari (bulan ini)
        $attendancePerDay = Attendance::select(
                'date',
                DB::raw('COUNT(*) as attendance_count'),
                DB::raw('AVG(work_hour) as avg_work_hours')
            )
            ->inMonth($yearMonth)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('Y-m-d'),
                    'attendance_count' => $item->attendance_count,
                    'avg_work_hours' => round($item->avg_work_hours ?? 0, 2),
                    'day_name' => Carbon::parse($item->date)->format('D')
                ];
            })->toArray();

        // Ringkasan Overview Karyawan
        $employeeOverview = [
            'total_employees' => $totalEmployees,
            'active_users' => $activeUsers,
        ];

        // Ringkasan Overview Kehadiran
        $attendanceOverview = [
            'attendance_records_today' => $attendanceToday,
            'average_work_hours_this_month' => round($avgWorkHours, 2),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Admin HR dashboard data retrieved successfully',
            'data' => [
                'employee_overview' => [
                    'cards' => $employeeOverview,
                    'chart_employees_per_month' => $employeesPerMonth,
                ],
                'attendance_overview' => [
                    'cards' => $attendanceOverview,
                    'chart_attendance_per_day' => $attendancePerDay,
                ],
                'period_info' => [
                    'current_month' => $now->format('F Y'),
                    'year_month' => $yearMonth,
                    'today' => $today,
                ]
            ]
        ]);
    }
}
