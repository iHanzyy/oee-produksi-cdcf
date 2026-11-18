<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use LogicException;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new LogicException('Seeder akun bawaan tidak boleh dijalankan di lingkungan produksi.');
        }

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
                'role' => 'amd',
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
