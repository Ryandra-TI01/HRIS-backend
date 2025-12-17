<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalarySlip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SalaryGenerationController extends Controller
{
    /**
     * Generate salary slip untuk satu karyawan berdasarkan attendance
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function generateSingle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:employees,id',
            'period' => 'required|string|date_format:Y-m',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = SalarySlip::generateFromAttendance(
            $request->employee_id,
            $request->period,
            Auth::id()
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result, 201);
    }

    /**
     * Generate salary slip untuk semua karyawan dalam periode tertentu
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function generateBulk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period' => 'required|string|date_format:Y-m',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = SalarySlip::generateBulkFromAttendance(
            $request->period,
            Auth::id()
        );

        return response()->json([
            'success' => true,
            'message' => "Salary generation completed for period {$request->period}",
            'data' => $result,
        ], 200);
    }

    /**
     * Preview kalkulasi gaji tanpa menyimpan ke database
     * Berguna untuk cek dulu sebelum generate
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function previewCalculation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:employees,id',
            'period' => 'required|string|date_format:Y-m',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Ambil data employee
        $employee = \App\Models\Employee::with('user', 'department')->find($request->employee_id);

        // Hitung total jam kerja dari attendance
        $totalWorkHours = \App\Models\Attendance::where('employee_id', $request->employee_id)
            ->whereRaw("DATE_FORMAT(`date`, '%Y-%m') = ?", [$request->period])
            ->sum('work_hour');

        // Hitung kalkulasi
        $employeeBasicSalary = $employee->basic_salary ?? SalarySlip::BASIC_SALARY_MIN;
        $calculation = SalarySlip::calculateSalary($employeeBasicSalary, $totalWorkHours);

        return response()->json([
            'success' => true,
            'message' => 'Salary calculation preview',
            'data' => [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->user->name,
                    'employee_code' => $employee->employee_code,
                    'position' => $employee->position,
                    'department' => $employee->department->name ?? 'N/A',
                ],
                'period' => $request->period,
                'calculation' => $calculation,
            ],
        ], 200);
    }
}
