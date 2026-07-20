<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            CategorySpecsSeeder::class,
            AdminSeeder::class,
        ]);

        // Compte de demo : uniquement en local, jamais en production.
        // (email connu + mot de passe "password" -> risque si expose en prod)
        // firstOrCreate rend le seeder idempotent (relançable sans planter
        // sur la contrainte unique de l'email s'il existe deja).
        if (app()->environment('local')) {
            User::firstOrCreate(
                ['email' => 'test@example.com'],
                User::factory()->make(['name' => 'Test User', 'email' => 'test@example.com'])->getAttributes()
            );
        }
    }
}
