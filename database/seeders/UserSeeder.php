<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Administrator',
                'email' => 'admin@admin.com',
                'password' => Hash::make('2dinkR'),
                'role_id' => 1,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Administrator2',
                'email' => 'admin2@admin.com',
                'password' => Hash::make('3so5hU'),
                'role_id' => 1,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'User1',
                'email' => 'user1@user.com',
                'password' => Hash::make('3kd8nY'),
                'role_id' => 2,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'User2',
                'email' => 'user2@user.com',
                'password' => Hash::make('3kd8cP'),
                'role_id' => 2,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'User3',
                'email' => 'user3@user.com',
                'password' => Hash::make('9kd7nL'),
                'role_id' => 2,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'User4',
                'email' => 'user4@user.com',
                'password' => Hash::make('5jd8nK'),
                'role_id' => 2,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'User5',
                'email' => 'user5@user.com',
                'password' => Hash::make('3kd8bK'),
                'role_id' => 2,
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['email' => $user['email']], $user);
        }
    }
}
