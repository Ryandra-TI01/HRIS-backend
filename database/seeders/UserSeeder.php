<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Membuat 4 user dengan role berbeda (menggunakan nama tim):
     * - 1 Admin HR: Eko Muchamad Haryono (FWD03029)
     * - 1 Manager: Raka Muhammad Rabbani (FWD03031)
     * - 2 Employee: Ryandra Athaya Saleh (FWD03019) & Octaviani Nursalsabila (FWD03039)
     * 
     * Note: Yossy Indra Kusuma (FWD03017) tidak termasuk dalam demo users
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Eko Muchamad Haryono',
                'email' => 'admin@hris.com',
                'password' => Hash::make('password123'),
                'role' => Role::ADMIN_HR,
                'status_active' => true,
            ],
            [
                'name' => 'Raka Muhammad Rabbani',
                'email' => 'manager@hris.com',
                'password' => Hash::make('password123'),
                'role' => Role::MANAGER,
                'status_active' => true,
            ],
            [
                'name' => 'Ryandra Athaya Saleh',
                'email' => 'employee1@hris.com',
                'password' => Hash::make('password123'),
                'role' => Role::EMPLOYEE,
                'status_active' => true,
            ],
            [
                'name' => 'Octaviani Nursalsabila',
                'email' => 'employee2@hris.com',
                'password' => Hash::make('password123'),
                'role' => Role::EMPLOYEE,
                'status_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        $this->command->info('✅ 4 Users created successfully (Tim FWD Batch 3)!');
    }
}
