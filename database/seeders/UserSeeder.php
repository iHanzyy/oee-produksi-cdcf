<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Operator Produksi',
                'email' => 'operator',
                'password' => Hash::make('operator'),
                'role' => 'operator',
            ],
            [
                'name' => 'Supervisor Produksi',
                'email' => 'supervisor',
                'password' => Hash::make('supervisor'),
                'role' => 'spv',
            ],
            [
                'name' => 'Manager Produksi',
                'email' => 'manager',
                'password' => Hash::make('manager'),
                'role' => 'manager',
            ],
            [
                'name' => 'Associate Managing Director',
                'email' => 'AMD',
                'password' => Hash::make('AMD'),
                'role' => 'manager',
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                $account
            );
        }

        User::query()->where('email', 'spv')->delete();
    }
}
