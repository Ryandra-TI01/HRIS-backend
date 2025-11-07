<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        if ($user->isAdminHr()) {
            $query = Employee::with(['user', 'manager'])
                ->search($request->query('q'));
        } elseif ($user->isManager()) {
            $query = Employee::with(['user', 'manager'])
                ->managedBy($user->id)
                ->search($request->query('q'));
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(20),
        ]);
    }

    /**
     * Display the specified employee
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();
        $employee = Employee::with(['user', 'manager'])->findOrFail($id);

        // Check authorization
        if ($user->isAdminHr() ||
            ($user->isManager() && $employee->manager_id == $user->id) ||
            ($user->id == $employee->user_id)) {
            return response()->json([
                'success' => true,
                'data' => $employee,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Forbidden'
        ], 403);
    }

    /**
     * Store a newly created employee
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'user_id' => 'required|exists:users,id|unique:employees,user_id',
            'employee_code' => 'required|alpha_num|unique:employees,employee_code',
            'position' => 'required|string',
            'department' => 'required|string',
            'join_date' => 'required|date',
            'employment_status' => 'required|in:permanent,contract,intern,resigned',
            'contact' => 'nullable|string',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        $employee = Employee::create($data);

        return response()->json([
            'success' => true,
            'data' => $employee,
        ], 201);
    }

    /**
     * Update the specified employee
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $this->authorizeAdmin();

        $employee = Employee::findOrFail($id);

        $data = $request->validate([
            'employee_code' => "sometimes|alpha_num|unique:employees,employee_code,{$employee->id}",
            'position' => 'sometimes|string',
            'department' => 'sometimes|string',
            'join_date' => 'sometimes|date',
            'employment_status' => 'sometimes|in:permanent,contract,intern,resigned',
            'contact' => 'nullable|string',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        $employee->update($data);

        return response()->json([
            'success' => true,
            'data' => $employee,
        ]);
    }

    /**
     * Remove the specified employee
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $this->authorizeAdmin();

        Employee::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee deleted successfully',
        ]);
    }

    /**
     * Authorize admin only
     */
    private function authorizeAdmin(): void
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();
        abort_unless($user && $user->isAdminHr(), 403, 'Forbidden');
    }
}
