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
                'password' => Hash::make('password'),
                'role' => 'operator',
            ],
            [
                'name' => 'Supervisor Produksi',
                'email' => 'spv',
                'password' => Hash::make('password'),
                'role' => 'spv',
            ],
            [
                'name' => 'Manager Produksi',
                'email' => 'manager',
                'password' => Hash::make('password'),
                'role' => 'manager',
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                $account
            );
        }

        User::query()->where('email', 'supervisor')->update([
            'role' => 'spv',
            'password' => Hash::make('password'),
        ]);
    }
}
