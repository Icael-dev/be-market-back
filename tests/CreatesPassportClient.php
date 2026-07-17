<?php

namespace Tests;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Passport\Client;

trait CreatesPassportClient
{
    protected function createPersonalAccessClient(): void
    {
        Client::create([
            'id' => (string) Str::uuid(),
            'owner_type' => User::class,
            'owner_id' => (string) Str::uuid(),
            'name' => 'BeMarket Personal',
            'secret' => Str::random(40),
            'provider' => 'users',
            'redirect_uris' => [],
            'grant_types' => ['personal_access'],
            'revoked' => false,
        ]);
    }
}
