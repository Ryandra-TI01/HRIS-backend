<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PerformanceReview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PerformanceReviewController extends Controller
{
    /**
     * Get all performance reviews
     * Admin: semua review
     * Manager: review yang dia buat untuk timnya
     * Employee: review untuk dirinya sendiri
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        $query = PerformanceReview::with(['employee.user', 'reviewer']);

        if ($user->isEmployee()) {
            // Employee: hanya lihat review untuk dirinya sendiri
            abort_if(!$user->employee, 422, 'Profile employee belum tersedia');
            $query->where('employee_id', $user->employee->id);
        } elseif ($user->isManager()) {
            // Manager: lihat review yang dia buat
            $query->where('reviewer_id', $user->id);
        }
        // Admin: lihat semua

        // Filter by employee_id
        if ($employeeId = $request->query('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        // Filter by period
        if ($period = $request->query('period')) {
            $query->where('period', $period);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('created_at', 'desc')->paginate(20),
        ]);
    }

    /**
     * Get my performance reviews (employee)
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

        $query = PerformanceReview::ofEmployee($employee->id)
            ->with('reviewer');

        // Filter by period (optional)
        if ($period = $request->query('period')) {
            $query->inPeriod($period);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('created_at', 'desc')->get(),
        ]);
    }

    /**
     * Create new performance review (Manager/Admin only)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        abort_unless($user->isAdminHr() || $user->isManager(), 403, 'Forbidden');

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'period' => 'required|string|max:20',
            'total_star' => 'required|integer|min:1|max:10',
            'review_description' => 'required|string',
        ]);

        $validated['reviewer_id'] = $user->id;

        $review = PerformanceReview::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Performance review created successfully',
            'data' => $review->load(['employee.user', 'reviewer']),
        ], 201);
    }

    /**
     * Get specific performance review
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        $review = PerformanceReview::with(['employee.user', 'reviewer'])->findOrFail($id);

        // Employee hanya bisa lihat review miliknya
        if ($user->isEmployee()) {
            abort_if(!$user->employee, 422, 'Profile employee belum tersedia');
            abort_unless($review->employee_id === $user->employee->id, 403, 'Forbidden');
        }

        // Manager hanya bisa lihat review yang dia buat
        if ($user->isManager()) {
            abort_unless($review->reviewer_id === $user->id, 403, 'Forbidden');
        }

        return response()->json([
            'success' => true,
            'data' => $review,
        ]);
    }

    /**
     * Update performance review (Manager/Admin only, hanya yang membuatnya)
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        abort_unless($user->isAdminHr() || $user->isManager(), 403, 'Forbidden');

        $review = PerformanceReview::findOrFail($id);

        // Hanya reviewer yang membuatnya yang bisa update (kecuali admin)
        if (!$user->isAdminHr()) {
            abort_unless($review->reviewer_id === $user->id, 403, 'Forbidden');
        }

        $validated = $request->validate([
            'period' => 'sometimes|string|max:20',
            'total_star' => 'sometimes|integer|min:1|max:10',
            'review_description' => 'sometimes|string',
        ]);

        $review->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Performance review updated successfully',
            'data' => $review->load(['employee.user', 'reviewer']),
        ]);
    }

    /**
     * Delete performance review (Admin only)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        abort_unless($user->isAdminHr(), 403, 'Forbidden');

        $review = PerformanceReview::findOrFail($id);
        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Performance review deleted successfully',
        ]);
    }
}
