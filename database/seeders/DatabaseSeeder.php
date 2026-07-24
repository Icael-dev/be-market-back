<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            CategorySpecsSeeder::class,
            AdminSeeder::class,
            FAQSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call([
                ProductSeeder::class,
                CouponSeeder::class,
                OrderSeeder::class,
            ]);

            User::firstOrCreate(
                ['email' => 'test@example.com'],
                User::factory()->make(['name' => 'Test User', 'email' => 'test@example.com'])->getAttributes()
            );
        }
    }
}
