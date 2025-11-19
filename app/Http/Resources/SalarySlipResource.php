<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalarySlipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isIndexRequest = $request->is('api/salary-slips') 
                       || $request->routeIs('salary-slips.index');

        $data = [
            'id'             => $this->id,
            'period_month'   => $this->period_month,
            'basic_salary'   => (float) $this->basic_salary,
            'allowance'      => (float) ($this->allowance ?? 0),
            'deduction'      => (float) ($this->deduction ?? 0),
            'total_salary'   => (float) $this->total_salary,
            'remarks'        => $this->remarks,
            'created_at'     => $this->created_at?->format('Y-m-d H:i:s'),

            // Data karyawan (hanya info yang aman)
            'employee' => $this->whenLoaded('employee', fn() => [
                'id'    => $this->employee?->id,
                'name'  => $this->employee?->user?->name,
                'email' => $this->employee?->user?->email,
                // tambahkan nip, department jika perlu
            ]),

            // Data pembuat slip (admin HR)
            'creator' => $this->whenLoaded('creator', fn() => [
                'id'   => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),
        ];

        // Jika BUKAN dari index (misalnya show(), me(), dll) → tampilkan full
        if (! $isIndexRequest) {
            return array_merge($data, [
                'employee_id'  => $this->employee_id,
                'created_by'   => $this->created_by,
                'updated_at'   => $this->updated_at?->format('Y-m-d H:i:s'),
            ]);
        }

        // Untuk index(): SEMUA field sensitif disembunyikan
        return $data;
    }
}
