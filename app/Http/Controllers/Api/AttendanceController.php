<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * Check-in attendance
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkIn(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();
        $employee = $user->employee;

        abort_if(!$employee, 422, 'Profile employee belum tersedia');

        $attendance = Attendance::firstOrCreate([
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
        ]);

        abort_if($attendance->check_in_time, 409, 'Sudah check-in hari ini');

        $attendance->check_in_time = now();
        $attendance->save();

        return response()->json([
            'success' => true,
            'data' => $attendance,
        ]);
    }

    /**
     * Check-out attendance
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkOut(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();
        $employee = $user->employee;

        abort_if(!$employee, 422, 'Profile employee belum tersedia');

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())
            ->firstOrFail();

        abort_if(!$attendance->check_in_time, 422, 'Belum check-in');
        abort_if($attendance->check_out_time, 409, 'Sudah check-out');

        $attendance->check_out_time = now();
        $attendance->computeWorkHour(); // Otomatis hitung work_hour (dikurangi 1 jam break)
        $attendance->save();

        return response()->json([
            'success' => true,
            'data' => $attendance,
        ]);
    }

    /**
     * Get attendance history for logged in employee
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();
        $employee = $user->employee;

        abort_if(!$employee, 422, 'Profile employee belum tersedia');

        $query = Attendance::ofEmployee($employee->id);

        // Filter by month (optional)
        if ($yearMonth = $request->query('month')) {
            $query->inMonth($yearMonth);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('date', 'desc')->get(),
        ]);
    }

    /**
     * Get all attendances (Admin HR / Manager only)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        abort_unless($user->isAdminHr() || $user->isManager(), 403, 'Forbidden');

        $query = Attendance::with('employee.user');

        // Filter by employee_id
        if ($employeeId = $request->query('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        // Filter by month
        if ($yearMonth = $request->query('month')) {
            $query->inMonth($yearMonth);
        }

        // If manager: limit to their team
        if ($user->isManager()) {
            $managerId = $user->id;
            $query->whereHas('employee', function ($employeeQuery) use ($managerId) {
                $employeeQuery->where('manager_id', $managerId);
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('date', 'desc')->paginate(20),
        ]);
    }
}
