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
     * Membuat 15 user:
     * - 1 Admin HR: Eko Muchamad Haryono
     * - 4 Manager: Raka, Yossy, Dina, Ahmad
     * - 10 Employee: Ryandra, Octaviani, Budi, Sari, Andi, Lisa, Doni, Maya, Ricky, Fitri
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
                'name' => 'Yossy Indra Kusuma',
                'email' => 'yossy.manager@hris.com',
                'password' => Hash::make('password123'),
                'role' => Role::MANAGER,
                'status_active' => true,
            ],
            [
                'name' => 'Dina Ayu Lestari',
                'email' => 'dina.manager@hris.com',
                'password' => Hash::make('password123'),
                'role' => Role::MANAGER,
                'status_active' => true,
            ],
            [
                'name' => 'Ahmad Rizky Pratama',
                'email' => 'ahmad.manager@hris.com',
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
            [
                'name' => 'Budi Santoso',
                'email' => 'employee3@hris.com',
                'password' => Hash::make('password123'),
                'role' => Role::EMPLOYEE,
                'status_active' => true,
            ],
            [
                'name' => 'Sari Dewi',
                'email' => 'employee4@hris.com',
                'password' => Hash::make('password123'),
                'role' => Role::EMPLOYEE,
                'status_active' => true,
            ],
            [
                'name' => 'Andi Wijaya',
                'email' => 'employee5@hris.com',
                'password' => Hash::make('password123'),
                'role' => Role::EMPLOYEE,
                'status_active' => true,
            ],
            [
                'name' => 'Lisa Permata',
                'email' => 'employee6@hris.com',
                'password' => Hash::make('password123'),
                'role' => Role::EMPLOYEE,
                'status_active' => true,
            ],
            [
                'name' => 'Doni Hartono',
                'email' => 'employee7@hris.com',
                'password' => Hash::make('password123'),
                'role' => Role::EMPLOYEE,
                'status_active' => true,
            ],
            [
                'name' => 'Maya Sari',
                'email' => 'employee8@hris.com',
                'password' => Hash::make('password123'),
                'role' => Role::EMPLOYEE,
                'status_active' => true,
            ],
            [
                'name' => 'Ricky Setiawan',
                'email' => 'employee9@hris.com',
                'password' => Hash::make('password123'),
                'role' => Role::EMPLOYEE,
                'status_active' => true,
            ],
            [
                'name' => 'Fitri Handayani',
                'email' => 'employee10@hris.com',
                'password' => Hash::make('password123'),
                'role' => Role::EMPLOYEE,
                'status_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        $this->command->info('✅ 15 Users created successfully (1 Admin HR + 4 Managers + 10 Employees)!');
    }
}
