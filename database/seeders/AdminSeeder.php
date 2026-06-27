<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admindev.tech@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Admindev.tech@171204'),
                'is_admin' => true,
            ]
        );

        Profile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'email' => $user->email,
                'full_name' => $user->name,
                'phone' => null,
                'avatar_url' => null,
                'is_admin' => true,
            ]
        );
    }
}
