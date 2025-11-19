<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * kolom yang ingin disembunyikan dari response
     * 
     */
    public function toArray(Request $request): array
    {
        $isIndexRequest = $request->is('api/attendances') || 
                          $request->routeIs('attendances.index');

        $data = [
            'id'              => $this->id,
            'date'            => $this->date,
            'check_in_time'   => $this->check_in_time?->format('H:i:s'),
            'check_out_time'  => $this->check_out_time?->format('H:i:s'),
            'work_hour'       => $this->work_hour,
            'status'          => $this->status ?? null,
            // Relasi employee hanya tampilkan data yang aman
            'employee'        => [
                'id'           => $this->employee?->id,
                'name'         => $this->employee?->user?->name,
                'email'        => $this->employee?->user?->email,
                // bisa tambah field lain yang boleh dilihat seperti nip, department, dll
            ],
        ];

        // Hanya tampilkan field lengkap jika bukan request index (misalnya untuk /me)
        if (! $isIndexRequest) {
            return array_merge($data, parent::toArray($request));
        }

        return $data;
    }
}
