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
        $email = env('ADMIN_SEED_EMAIL', 'admin@example.com');
        $password = env('ADMIN_SEED_PASSWORD', 'password');
        $name = env('ADMIN_SEED_NAME', 'Admin');

        $user = User::firstWhere('email', $email);
        if (! $user) {
            $user = new User([
                'email' => $email,
                'name' => $name,
                'password' => Hash::make($password),
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
