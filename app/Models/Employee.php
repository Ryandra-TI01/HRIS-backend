<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends BaseModel
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'employee_code',
        'position',
        'department',
        'join_date',
        'employment_status',
        'contact',
        'manager_id',
    ];

    /**
     * Mendapatkan atribut yang harus di-cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'employment_status' => EmploymentStatus::class,
        ];
    }

        // ========== Relasi ==========

    /**
     * Relasi N:1 dengan User
     * Setiap karyawan terhubung ke satu akun login
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi N:1 dengan User (sebagai manager)
     * Setiap karyawan memiliki satu atasan
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Relasi 1:N dengan Attendance
     * Satu karyawan punya banyak catatan absensi
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    /**
     * Relasi 1:N dengan LeaveRequest
     * Satu karyawan bisa ajukan banyak cuti
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'employee_id');
    }

    /**
     * Relasi 1:N dengan PerformanceReview
     * Satu karyawan bisa menerima banyak penilaian kinerja
     */
    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class, 'employee_id');
    }

    /**
     * Relasi 1:N dengan SalarySlip
     * Satu karyawan bisa menerima banyak slip gaji
     */
    public function salarySlips(): HasMany
    {
        return $this->hasMany(SalarySlip::class, 'employee_id');
    }

    // ========== Scopes ==========

    /**
     * Scope untuk filter karyawan yang dikelola oleh manager tertentu
     */
    public function scopeManagedBy($query, int $managerUserId)
    {
        return $query->where('manager_id', $managerUserId);
    }

    /**
     * Scope untuk pencarian karyawan
     */
    public function scopeSearch($query, ?string $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($subQuery) use ($term) {
            $subQuery->where('employee_code', 'like', "%{$term}%")
                ->orWhere('position', 'like', "%{$term}%")
                ->orWhere('department', 'like', "%{$term}%")
                ->orWhereHas('user', function ($userQuery) use ($term) {
                    $userQuery->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
        });
    }
}
