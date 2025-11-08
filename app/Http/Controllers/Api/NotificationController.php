<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications
     * Ambil semua notifikasi user yang sedang login (Admin bisa lihat semua).
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Admin bisa lihat semua notifikasi
        if ($user->role === 'admin_hr') {
            $notifications = Notification::with('user:id,name,email')
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        } else {
            // User biasa hanya bisa lihat notifikasi sendiri
            $notifications = Notification::ofUser($user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully',
            'data' => $notifications,
        ], 200);
    }

    /**
     * GET /api/notifications/me
     * Ambil notifikasi milik user yang sedang login.
     */
    public function me(): JsonResponse
    {
        $user = Auth::user();

        $notifications = Notification::ofUser($user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $unreadCount = Notification::ofUser($user->id)->unread()->count();

        return response()->json([
            'success' => true,
            'message' => 'Your notifications retrieved successfully',
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
            ],
        ], 200);
    }

    /**
     * GET /api/notifications/unread
     * Ambil hanya notifikasi yang belum dibaca.
     */
    public function unread(): JsonResponse
    {
        $user = Auth::user();

        $notifications = Notification::ofUser($user->id)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Unread notifications retrieved successfully',
            'data' => $notifications,
        ], 200);
    }

    /**
     * POST /api/notifications
     * Buat notifikasi baru (Hanya Admin HR).
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Hanya Admin HR yang bisa membuat notifikasi
        if ($user->role !== 'admin_hr') {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to create notifications',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $notification = Notification::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Notification created successfully',
            'data' => $notification->load('user:id,name,email'),
        ], 201);
    }

    /**
     * POST /api/notifications/broadcast
     * Kirim notifikasi ke semua user atau group tertentu (Hanya Admin HR).
     */
    public function broadcast(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Hanya Admin HR yang bisa broadcast notifikasi
        if ($user->role !== 'admin_hr') {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to broadcast notifications',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|string|max:255',
            'message' => 'required|string',
            'target_role' => 'nullable|in:admin_hr,manager,employee', // Kirim ke role tertentu atau semua
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Ambil target users
        $targetUsers = \App\Models\User::query();
        if ($request->target_role) {
            $targetUsers->where('role', $request->target_role);
        }
        $users = $targetUsers->get();

        // Buat notifikasi untuk setiap user
        $notifications = [];
        foreach ($users as $targetUser) {
            $notifications[] = [
                'user_id' => $targetUser->id,
                'type' => $request->type,
                'message' => $request->message,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Notification::insert($notifications);

        return response()->json([
            'success' => true,
            'message' => 'Notifications successfully broadcast to ' . count($notifications) . ' users',
            'data' => [
                'total_sent' => count($notifications),
                'target_role' => $request->target_role ?? 'all',
            ],
        ], 201);
    }

    /**
     * GET /api/notifications/{id}
     * Detail notifikasi (User hanya bisa lihat miliknya sendiri).
     */
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();

        $notification = Notification::with('user:id,name,email')->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        // User biasa hanya bisa lihat notifikasi sendiri, Admin bisa lihat semua
        if ($user->role !== 'admin_hr' && $notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this notification',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification details retrieved successfully',
            'data' => $notification,
        ], 200);
    }

    /**
     * PUT /api/notifications/{id}/read
     * Tandai notifikasi sebagai sudah dibaca.
     */
    public function markAsRead(string $id): JsonResponse
    {
        $user = Auth::user();

        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        // User hanya bisa update notifikasi sendiri
        if ($notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this notification',
            ], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read successfully',
            'data' => $notification->fresh(),
        ], 200);
    }

    /**
     * PUT /api/notifications/read-all
     * Tandai semua notifikasi user sebagai sudah dibaca.
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();

        $updated = Notification::ofUser($user->id)
            ->unread()
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => "All notifications marked as read successfully ($updated notifications)",
            'data' => [
                'total_marked' => $updated,
            ],
        ], 200);
    }

    /**
     * DELETE /api/notifications/{id}
     * Hapus notifikasi (User hanya bisa hapus miliknya sendiri).
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();

        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        // User hanya bisa hapus notifikasi sendiri, Admin bisa hapus semua
        if ($user->role !== 'admin_hr' && $notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to delete this notification',
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully',
        ], 200);
    }
}
