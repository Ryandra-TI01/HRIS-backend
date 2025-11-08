<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LeaveRequestController extends Controller
{
    /**
     * Store a newly created leave request
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();
        $employee = $user->employee;

        abort_if(!$employee, 422, 'Employee profile not yet available');

        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'] ?? null,
            'status' => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'data' => $leaveRequest,
        ], 201);
    }

    /**
     * Get leave requests for logged in employee
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();
        $employee = $user->employee;

        abort_if(!$employee, 422, 'Employee profile not yet available');

        $leaveRequests = LeaveRequest::where('employee_id', $employee->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $leaveRequests,
        ]);
    }

    /**
     * Get all leave requests (Admin HR / Manager only)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        abort_unless($user->isAdminHr() || $user->isManager(), 403, 'Forbidden');

        $query = LeaveRequest::with(['employee.user', 'reviewer']);

        // Filter by status
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Filter by employee_id
        if ($employeeId = $request->query('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        // Filter by period (month)
        if ($yearMonth = $request->query('period')) {
            $query->inPeriod($yearMonth);
        }

        // If manager: limit to their team
        if ($user->isManager()) {
            $query->forManagerTeam($user->id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderByDesc('id')->paginate(20),
        ]);
    }

    /**
     * Approve leave request
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        abort_unless($user->isAdminHr() || $user->isManager(), 403, 'Forbidden');

        $leaveRequest = LeaveRequest::findOrFail($id);

        // If manager: ensure it's their team member
        if ($user->isManager()) {
            abort_unless(
                $leaveRequest->employee && $leaveRequest->employee->manager_id === $user->id,
                403,
                'Forbidden'
            );
        }

        $leaveRequest->approve($user->id, $request->input('reviewer_note'));

        return response()->json([
            'success' => true,
            'data' => $leaveRequest,
        ]);
    }

    /**
     * Reject leave request
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        abort_unless($user->isAdminHr() || $user->isManager(), 403, 'Forbidden');

        $leaveRequest = LeaveRequest::findOrFail($id);

        // If manager: ensure it's their team member
        if ($user->isManager()) {
            abort_unless(
                $leaveRequest->employee && $leaveRequest->employee->manager_id === $user->id,
                403,
                'Forbidden'
            );
        }

        $leaveRequest->reject($user->id, $request->input('reviewer_note'));

        return response()->json([
            'success' => true,
            'data' => $leaveRequest,
        ]);
    }
}
