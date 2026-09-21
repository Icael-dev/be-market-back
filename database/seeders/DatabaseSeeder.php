<?php

namespace Database\Seeders;

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
            ]);
        }
    }
}
