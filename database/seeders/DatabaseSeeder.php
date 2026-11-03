<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with user accounts.
     * Business catalog and order data are intentionally left empty for the admin to configure.
     */
    public function run(): void
    {
        // ── Admin User ──
        User::firstOrCreate(
            ['email' => 'admin@dairybox.com'],
            [
                'name' => 'Admin DairyBox',
                'password' => Hash::make('password'),
                'phone' => '09171234567',
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        // ── Rider User ──
        User::firstOrCreate(
            ['email' => 'rider@dairybox.com'],
            [
                'name' => 'Juan Rider',
                'password' => Hash::make('password'),
                'phone' => '09181234567',
                'role' => 'rider',
                'status' => 'active',
            ]
        );

        // ── Customer Users ──
        $customerNames = [
            ['name' => 'Maria Santos', 'email' => 'maria@example.com'],
            ['name' => 'Jose Cruz', 'email' => 'jose@example.com'],
            ['name' => 'Ana Garcia', 'email' => 'ana@example.com'],
            ['name' => 'Pedro Reyes', 'email' => 'pedro@example.com'],
            ['name' => 'Rosa Mendoza', 'email' => 'rosa@example.com'],
        ];

        foreach ($customerNames as $index => $c) {
            User::firstOrCreate(
                ['email' => $c['email']],
                [
                    'name' => $c['name'],
                    'password' => Hash::make('password'),
                    'phone' => '0917' . str_pad($index + 1000000, 7, '0', STR_PAD_LEFT),
                    'role' => 'customer',
                    'status' => 'active',
                ]
            );
        }

        AuditLog::create([
            'user_id' => 1,
            'action' => 'SYSTEM_INIT',
            'details' => 'Database initialized with system accounts.',
            'ip_address' => '127.0.0.1',
        ]);
    }
}
