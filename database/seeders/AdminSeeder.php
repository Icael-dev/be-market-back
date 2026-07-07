<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstWhere('email', 'admindev.tech@gmail.com');
        if (! $user) {
            $user = new User([
                'email' => 'admindev.tech@gmail.com',
                'name' => 'Admin',
                'password' => Hash::make('Admindev.tech@171204'),
                'is_admin' => true,
            ]);
            $user->id = (string) Str::uuid();
            $user->save();
        }

        Profile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'id' => (string) Str::uuid(),
                'email' => $user->email,
                'full_name' => $user->name,
                'phone' => null,
                'avatar_url' => null,
                'is_admin' => true,
            ]
        );
    }
}
