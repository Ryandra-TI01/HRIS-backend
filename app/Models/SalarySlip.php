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

    // ========== SALARY CALCULATION BASED ON ATTENDANCE ==========

    /**
     * Konstanta untuk kalkulasi gaji
     */
    const STANDARD_WORK_HOURS_PER_DAY = 8;      // 8 jam/hari
    const STANDARD_WORK_DAYS_PER_MONTH = 22;    // 22 hari kerja/bulan
    const STANDARD_WORK_HOURS_PER_MONTH = 176;  // 8 × 22 = 176 jam/bulan
    const STATIC_ALLOWANCE = 1000000.00;        // Tunjangan tetap Rp 1.000.000
    const DEDUCTION_PERCENTAGE = 12;            // Potongan pajak 12%

    /**
     * Generate salary slip untuk satu karyawan berdasarkan attendance
     *
     * Rumus:
     * 1. Hitung jam kerja real dari attendance bulan tersebut
     * 2. Hourly rate = basic_salary / 176 jam
     * 3. Calculated basic = hourly_rate × real_working_hours
     * 4. Allowance = Rp 1.000.000 (static)
     * 5. Deduction = (calculated_basic + allowance) × 12%
     * 6. Total salary = (calculated_basic + allowance) - deduction
     *
     * @param int $employeeId ID karyawan
     * @param string $period Format: YYYY-MM (contoh: 2025-12)
     * @param int $createdBy ID user yang membuat (Admin HR)
     * @return array Result dengan status dan data salary slip
     */
    public static function generateFromAttendance(int $employeeId, string $period, int $createdBy): array
    {
        // Validasi employee exists
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return [
                'success' => false,
                'message' => 'Employee not found',
            ];
        }

        // Cek apakah salary slip sudah ada untuk periode ini
        $existingSlip = static::where('employee_id', $employeeId)
            ->where('period_month', $period)
            ->first();

        if ($existingSlip) {
            return [
                'success' => false,
                'message' => 'Salary slip already exists for this period',
                'data' => $existingSlip,
            ];
        }

        // Hitung total jam kerja dari attendance
        $totalWorkHours = Attendance::where('employee_id', $employeeId)
            ->whereRaw("DATE_FORMAT(`date`, '%Y-%m') = ?", [$period])
            ->sum('work_hour');

        // Jika tidak ada attendance, return error
        if ($totalWorkHours == 0) {
            return [
                'success' => false,
                'message' => 'No attendance records found for this period',
                'total_work_hours' => 0,
            ];
        }

        // Ambil basic salary dari employee
        $employeeBasicSalary = $employee->basic_salary ?? self::BASIC_SALARY_MIN;

        // Kalkulasi gaji
        $calculation = self::calculateSalary($employeeBasicSalary, $totalWorkHours);

        // Buat salary slip
        $salarySlip = static::create([
            'employee_id' => $employeeId,
            'created_by' => $createdBy,
            'period_month' => $period,
            'basic_salary' => $calculation['calculated_basic_salary'],
            'allowance' => $calculation['allowance'],
            'deduction' => $calculation['deduction'],
            'total_salary' => $calculation['total_salary'],
            'remarks' => $calculation['remarks'],
        ]);

        return [
            'success' => true,
            'message' => 'Salary slip generated successfully',
            'data' => $salarySlip,
            'calculation_details' => $calculation,
        ];
    }

    /**
     * Kalkulasi gaji berdasarkan basic salary dan jam kerja real
     *
     * @param float $employeeBasicSalary Gaji pokok dari tabel employees
     * @param float $realWorkHours Jam kerja real dari attendance
     * @return array Detail kalkulasi
     */
    public static function calculateSalary(float $employeeBasicSalary, float $realWorkHours): array
    {
        // 1. Hitung hourly rate
        $hourlyRate = $employeeBasicSalary / self::STANDARD_WORK_HOURS_PER_MONTH;

        // 2. Hitung basic salary berdasarkan jam kerja real
        $calculatedBasicSalary = $hourlyRate * $realWorkHours;

        // 3. Allowance static
        $allowance = self::STATIC_ALLOWANCE;

        // 4. Hitung deduction (pajak 12%)
        $grossSalary = $calculatedBasicSalary + $allowance;
        $deduction = $grossSalary * (self::DEDUCTION_PERCENTAGE / 100);

        // 5. Total salary
        $totalSalary = $grossSalary - $deduction;

        // 6. Buat remarks dengan detail kalkulasi
        $remarks = sprintf(
            "Kalkulasi: Base Salary Rp %s | Hourly Rate Rp %s | Work Hours: %.2f jam | " .
            "Calculated Basic: Rp %s | Allowance: Rp %s | Gross: Rp %s | Deduction 12%%: Rp %s",
            number_format($employeeBasicSalary, 0, ',', '.'),
            number_format($hourlyRate, 2, ',', '.'),
            $realWorkHours,
            number_format($calculatedBasicSalary, 0, ',', '.'),
            number_format($allowance, 0, ',', '.'),
            number_format($grossSalary, 0, ',', '.'),
            number_format($deduction, 0, ',', '.')
        );

        return [
            'employee_basic_salary' => round($employeeBasicSalary, 2),
            'standard_hours' => self::STANDARD_WORK_HOURS_PER_MONTH,
            'real_work_hours' => round($realWorkHours, 2),
            'hourly_rate' => round($hourlyRate, 2),
            'calculated_basic_salary' => round($calculatedBasicSalary, 2),
            'allowance' => round($allowance, 2),
            'gross_salary' => round($grossSalary, 2),
            'deduction_percentage' => self::DEDUCTION_PERCENTAGE,
            'deduction' => round($deduction, 2),
            'total_salary' => round($totalSalary, 2),
            'remarks' => $remarks,
        ];
    }

    /**
     * Generate salary slip untuk semua karyawan dalam periode tertentu
     *
     * @param string $period Format: YYYY-MM
     * @param int $createdBy ID user yang membuat (Admin HR)
     * @return array Summary hasil generate
     */
    public static function generateBulkFromAttendance(string $period, int $createdBy): array
    {
        $employees = Employee::whereIn('employment_status', ['permanent', 'contract'])
            ->get();

        $results = [
            'success' => [],
            'failed' => [],
            'skipped' => [],
        ];

        foreach ($employees as $employee) {
            $result = self::generateFromAttendance($employee->id, $period, $createdBy);

            if ($result['success']) {
                $results['success'][] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->user->name ?? 'N/A',
                    'total_salary' => $result['data']->total_salary,
                ];
            } else {
                if (str_contains($result['message'], 'already exists')) {
                    $results['skipped'][] = [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->user->name ?? 'N/A',
                        'reason' => $result['message'],
                    ];
                } else {
                    $results['failed'][] = [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->user->name ?? 'N/A',
                        'reason' => $result['message'],
                    ];
                }
            }
        }

        return [
            'period' => $period,
            'total_employees' => $employees->count(),
            'success_count' => count($results['success']),
            'failed_count' => count($results['failed']),
            'skipped_count' => count($results['skipped']),
            'details' => $results,
        ];
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
