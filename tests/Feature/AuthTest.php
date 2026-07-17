<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\CreatesPassportClient;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use CreatesPassportClient;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createPersonalAccessClient();
    }

    public function test_user_can_register_and_login(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'full_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
        $this->assertDatabaseHas('profiles', ['email' => 'jane@example.com']);
    }

    public function test_register_requires_valid_password(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'full_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'short',
        ]);

        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_fetch_their_profile(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401);
    }
}
