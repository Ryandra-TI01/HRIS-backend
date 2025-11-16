<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends BaseModel
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'date',
        'check_in_time',
        'check_out_time',
        'work_hour',
    ];

    /**
     * Mendapatkan atribut yang harus di-cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
            'work_hour' => 'decimal:2',
        ];
    }

    // ========== Relasi ==========

    /**
     * Relasi N:1 dengan Employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // ========== Scopes ==========

    /**
     * Scope untuk filter absensi berdasarkan employee
     */
    public function scopeOfEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope untuk filter absensi dalam bulan tertentu (format: YYYY-MM)
     */
    public function scopeInMonth($query, string $yearMonth)
    {
        return $query->whereRaw("DATE_FORMAT(`date`, '%Y-%m') = ?", [$yearMonth]);
    }

    // ========== Metode Helper ==========

    /**
     * Hitung jam kerja berdasarkan check-in dan check-out
     * Formula: (check_out_time - check_in_time) dalam jam
     * Otomatis kurangi 1 jam untuk break
     */
    public function computeWorkHour(): void
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return;
        }

        $checkIn = \Carbon\Carbon::parse($this->check_in_time);
        $checkOut = \Carbon\Carbon::parse($this->check_out_time);

        // Hitung selisih menit dari check-in ke check-out
        $totalMinutes = $checkIn->diffInMinutes($checkOut);

        // Kurangi 1 jam (60 menit) untuk break, tapi pastikan tidak negatif
        $workMinutes = max(0, $totalMinutes - 60);

        $this->work_hour = round($workMinutes / 60, 2);
    }
}
