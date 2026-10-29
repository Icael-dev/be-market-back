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
        if (app()->environment('production')) {
            return;
        }

        $email = env('ADMIN_SEED_EMAIL');
        $password = env('ADMIN_SEED_PASSWORD');
        $name = env('ADMIN_SEED_NAME');

        if (! $email || ! $password || ! $name) {
            return;
        }

        $user = User::firstWhere('email', $email);
        if (! $user) {
            $user = new User([
                'email' => $email,
                'name' => $name,
                'password' => Hash::make($password),
                'is_admin' => true,
                'role' => User::ROLE_ADMIN,
            ]);
            $user->id = (string) Str::uuid();
            $user->save();
        } else {
            $user->update([
                'name' => $name,
                'is_admin' => true,
                'role' => User::ROLE_ADMIN,
            ]);
            if ($password) {
                $user->update(['password' => Hash::make($password)]);
            }
        }

        Profile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'id' => (string) Str::uuid(),
                'email' => $user->email,
                'full_name' => $user->name,
                'is_admin' => true,
                'role' => User::ROLE_ADMIN,
            ]
        );
    }
}
