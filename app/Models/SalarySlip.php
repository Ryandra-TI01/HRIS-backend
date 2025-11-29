<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class SalarySlip extends BaseModel
{
    use HasFactory;

    // ──────────────────────────────────────────────────────────────
    // KONSTANTA BATAS NILAI (mudah diubah di satu tempat saja)
    // ──────────────────────────────────────────────────────────────

    // konstanta batas minimal dan maksimal untuk basic_salary
    const BASIC_SALARY_MIN = 1_000_000.00;
    const BASIC_SALARY_MAX = 99_999_999_999.99;

    // konstanta batas minimal dan maksimal untuk allowance
    const ALLOWANCE_MAX = 99_999_999_999.99;

    // konstanta batas minimal dan maksimal untuk deduction
    const DEDUCTION_MAX = 99_999_999_999.99;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'created_by',
        'period_month',
        'basic_salary',
        'allowance',
        'deduction',
        'total_salary',
        'remarks',
    ];

    /**
     * Mendapatkan atribut yang harus di-cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'allowance' => 'decimal:2',
            'deduction' => 'decimal:2',
            'total_salary' => 'decimal:2',
        ];
    }

    // ========== Relasi ==========

    /**
     * Relasi N:1 dengan Employee
     * Karyawan penerima slip gaji
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Relasi N:1 dengan User (sebagai creator)
     * Admin HR yang membuat slip gaji
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ========== Scopes ==========

    /**
     * Scope untuk filter slip gaji berdasarkan employee
     */
    public function scopeOfEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope untuk filter slip gaji berdasarkan periode
     */
    public function scopeInPeriod($query, string $period)
    {
        return $query->where('period_month', $period);
    }

    // ========== Metode Helper ==========

    /**
     * Hitung total salary otomatis
     * Formula: basic_salary + allowance - deduction
     */
    public function computeTotalSalary(): void
    {
        $this->total_salary = $this->basic_salary + $this->allowance - $this->deduction;
    }

    // ──────────────────────────────────────────────────────────────
    // MUTATORS (dengan validasi min + max + pesan Rupiah yang cantik)
    // ──────────────────────────────────────────────────────────────

    public function setBasicSalaryAttribute($value): void
    {
        $this->attributes['basic_salary'] = $this->normalizeNumeric($value);
    }

    public function setAllowanceAttribute($value): void
    {
        $this->attributes['allowance'] = $this->normalizeNumeric($value);
    }

    public function setDeductionAttribute($value): void
    {
        $this->attributes['deduction'] = $this->normalizeNumeric($value);
    }

    // ──────────────────────────────────────────────────────────────
    // HELPER PRIVATE (untuk normalize nilai numeric, agar aman dari null/invalid)
    // ──────────────────────────────────────────────────────────────
    private function normalizeNumeric($value, float $default = 0.0): float
    {
        return $value !== null ? round((float) $value, 2) : $default;
    }

    /**
     * Scope untuk filter slip gaji berdasarkan kata kunci di berbagai field
     * Mencari di: period_month, basic_salary, allowance, deduction, total_salary, remarks
     *
     * @param mixed $query
     * @param mixed $term
     * @return mixed
     */
    public function scopeSearch($query, ?string $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('period_month', 'like', "%{$term}%")
                ->orWhere('basic_salary', 'like', "%{$term}%")
                ->orWhere('allowance', 'like', "%{$term}%")
                ->orWhere('deduction', 'like', "%{$term}%")
                ->orWhere('total_salary', 'like', "%{$term}%")
                ->orWhere('remarks', 'like', "%{$term}%")
                ->orWhereHas('employee', function ($employeeQuery) use ($term) {
                    $employeeQuery
                        ->where('employee_code', 'like', "%{$term}%")
                        ->orWhere('position', 'like', "%{$term}%")
                        ->orWhere('department', 'like', "%{$term}%")
                        ->orWhereHas('user', function ($userQuery) use ($term) {
                            $userQuery
                                ->where('name', 'like', "%{$term}%")
                                ->orWhere('email', 'like', "%{$term}%");
                        });
                });
        });
    }
    /**
     * Scope untuk filter slip gaji berdasarkan range nilai basic salary
     *
     * @param mixed $query
     * @param float|null $salaryFrom nilai basic salary terendah
     * @param float|null $salaryTo nilai basic salary tertinggi
     * @return mixed
     */
    public function scopeFilterBasicSalary($query, ?float $salaryFrom, ?float $salaryTo)
    {
        return $query->when($salaryFrom, function ($q) use ($salaryFrom) {
            $q->where('basic_salary', '>=', $salaryFrom);
        })
            ->when($salaryTo, function ($q) use ($salaryTo) {
                $q->where('basic_salary', '<=', $salaryTo);
            });
    }
    /**
     * Scope untuk filter slip gaji berdasarkan range nilai total salary
     *
     * @param mixed $query
     * @param float|null $from nilai total salary terendah
     * @param float|null $to nilai total salary tertinggi
     * @return mixed
     */
    public function scopeFilterTotalSalary($query, ?float $from, ?float $to)
    {
        return $query->when($from, fn($q) => $q->where('total_salary', '>=', $from))
            ->when($to, fn($q) => $q->where('total_salary', '<=', $to));
    }


}
