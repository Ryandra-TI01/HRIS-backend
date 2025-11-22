<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends BaseModel
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'reviewer_id',
        'period',
        'total_star',
        'review_description',
    ];

    /**
     * Mendapatkan atribut yang harus di-cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_star' => 'integer',
        ];
    }

    // ========== Relasi ==========

    /**
     * Relasi N:1 dengan Employee
     * Karyawan yang dinilai
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Relasi N:1 dengan User (sebagai reviewer)
     * User/Manager yang memberikan penilaian
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // ========== Scopes ==========

    /**
     * Scope untuk filter review berdasarkan employee
     */
    public function scopeOfEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope untuk filter review berdasarkan periode
     */
    public function scopeInPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Scope untuk filter review yang dibuat oleh reviewer tertentu
     */
    public function scopeByReviewer($query, int $reviewerId)
    {
        return $query->where('reviewer_id', $reviewerId);
    }

    // ========== Enhanced Search Scopes ==========

    /**
     * Scope untuk pencarian global di berbagai field
     * Mencari di: nama karyawan, email, kode karyawan, departemen, posisi, deskripsi review, periode, nama reviewer
     */
    public function scopeSearch($query, ?string $searchTerm)
    {
        if (empty($searchTerm)) {
            return $query;
        }

        return $query->where(function ($subQuery) use ($searchTerm) {
            $subQuery->where('review_description', 'like', "%{$searchTerm}%")
                ->orWhere('period', 'like', "%{$searchTerm}%")
                ->orWhereHas('employee', function ($employeeQuery) use ($searchTerm) {
                    $employeeQuery->where('employee_code', 'like', "%{$searchTerm}%")
                        ->orWhere('position', 'like', "%{$searchTerm}%")
                        ->orWhere('department', 'like', "%{$searchTerm}%")
                        ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                            $userQuery->where('name', 'like', "%{$searchTerm}%")
                                ->orWhere('email', 'like', "%{$searchTerm}%");
                        });
                })
                ->orWhereHas('reviewer', function ($reviewerQuery) use ($searchTerm) {
                    $reviewerQuery->where('name', 'like', "%{$searchTerm}%");
                });
        });
    }

    /**
     * Scope untuk filter berdasarkan range rating/bintang
     */
    public function scopeByRatingRange($query, ?int $minRating = null, ?int $maxRating = null)
    {
        if ($minRating !== null) {
            $query->where('total_star', '>=', $minRating);
        }
        
        if ($maxRating !== null) {
            $query->where('total_star', '<=', $maxRating);
        }
        
        return $query;
    }

    /**
     * Scope untuk filter berdasarkan departemen karyawan yang direview
     */
    public function scopeByDepartment($query, ?string $department)
    {
        if (empty($department)) {
            return $query;
        }

        return $query->whereHas('employee', function ($employeeQuery) use ($department) {
            $employeeQuery->where('department', 'like', "%{$department}%");
        });
    }

    /**
     * Scope untuk filter berdasarkan range tanggal pembuatan review
     */
    public function scopeByDateRange($query, ?string $dateFrom = null, ?string $dateTo = null)
    {
        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom . ' 00:00:00');
        }
        
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }
        
        return $query;
    }

    /**
     * Scope untuk filter berdasarkan tahun tertentu
     */
    public function scopeByYear($query, ?string $year = null)
    {
        if (empty($year)) {
            return $query;
        }

        return $query->where(function ($subQuery) use ($year) {
            // Filter untuk format bulanan: "2025-10", "2025-11"
            $subQuery->where('period', 'like', "{$year}-%")
                // Filter untuk format kuartalan: "Q1-2025", "Q4-2025"  
                ->orWhere('period', 'like', "%-{$year}")
                // Filter berdasarkan created_at juga
                ->orWhereYear('created_at', $year);
        });
    }

    /**
     * Scope untuk filter berdasarkan tipe periode (bulanan/kuartalan)
     */
    public function scopeByPeriodType($query, ?string $periodType = null)
    {
        if (empty($periodType)) {
            return $query;
        }

        if ($periodType === 'monthly') {
            // Format: "2025-10", "2025-11"
            return $query->whereRaw('period REGEXP ?', ['^[0-9]{4}-[0-9]{2}$']);
        } elseif ($periodType === 'quarterly') {
            // Format: "Q1-2025", "Q4-2025" 
            return $query->whereRaw('period REGEXP ?', ['^Q[1-4]-[0-9]{4}$']);
        }

        return $query;
    }
}
