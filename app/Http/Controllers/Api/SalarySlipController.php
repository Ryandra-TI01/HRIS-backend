<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalarySlip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SalarySlipController extends Controller
{
    /**
     * Get all salary slips
     * Admin: semua slip
     * Employee: slip miliknya sendiri
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        $query = SalarySlip::with(['employee.user', 'creator']);

        if ($user->isEmployee()) {
            // Employee: hanya lihat slip miliknya sendiri
            abort_if(!$user->employee, 422, 'Profile employee belum tersedia');
            $query->where('employee_id', $user->employee->id);
        }
        // Admin & Manager: lihat semua

        // Filter by employee_id
        if ($employeeId = $request->query('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        // Filter by period
        if ($period = $request->query('period')) {
            $query->where('period_month', $period);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('period_month', 'desc')->paginate(20),
        ]);
    }

    /**
     * Get my salary slips (employee)
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

        $query = SalarySlip::ofEmployee($employee->id)
            ->with('creator');

        // Filter by period (optional)
        if ($period = $request->query('period')) {
            $query->inPeriod($period);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('period_month', 'desc')->get(),
        ]);
    }

    /**
     * Create new salary slip (Admin HR only)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        abort_unless($user->isAdminHr(), 403, 'Forbidden - Admin HR only');

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'period_month' => 'required|string|max:20',
            'basic_salary' => 'required|numeric|min:0',
            'allowance' => 'nullable|numeric|min:0',
            'deduction' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $validated['created_by'] = $user->id;
        $validated['allowance'] = $validated['allowance'] ?? 0;
        $validated['deduction'] = $validated['deduction'] ?? 0;

        $slip = new SalarySlip($validated);
        $slip->computeTotalSalary(); // Hitung total_salary otomatis
        $slip->save();

        return response()->json([
            'success' => true,
            'message' => 'Salary slip created successfully',
            'data' => $slip->load(['employee.user', 'creator']),
        ], 201);
    }

    /**
     * Get specific salary slip
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        $slip = SalarySlip::with(['employee.user', 'creator'])->findOrFail($id);

        // Employee hanya bisa lihat slip miliknya
        if ($user->isEmployee()) {
            abort_if(!$user->employee, 422, 'Profile employee belum tersedia');
            abort_unless($slip->employee_id === $user->employee->id, 403, 'Forbidden');
        }

        return response()->json([
            'success' => true,
            'data' => $slip,
        ]);
    }

    /**
     * Update salary slip (Admin HR only)
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        abort_unless($user->isAdminHr(), 403, 'Forbidden - Admin HR only');

        $slip = SalarySlip::findOrFail($id);

        $validated = $request->validate([
            'period_month' => 'sometimes|string|max:20',
            'basic_salary' => 'sometimes|numeric|min:0',
            'allowance' => 'sometimes|numeric|min:0',
            'deduction' => 'sometimes|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $slip->fill($validated);
        $slip->computeTotalSalary(); // Hitung ulang total_salary
        $slip->save();

        return response()->json([
            'success' => true,
            'message' => 'Salary slip updated successfully',
            'data' => $slip->load(['employee.user', 'creator']),
        ]);
    }

    /**
     * Delete salary slip (Admin HR only)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        abort_unless($user->isAdminHr(), 403, 'Forbidden - Admin HR only');

        $slip = SalarySlip::findOrFail($id);
        $slip->delete();

        return response()->json([
            'success' => true,
            'message' => 'Salary slip deleted successfully',
        ]);
    }
}
