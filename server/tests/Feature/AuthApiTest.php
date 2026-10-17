<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/dashboard')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Sesi Anda tidak valid atau telah berakhir. Silakan masuk kembali.');

        $this->getJson('/api/v1/products')->assertUnauthorized();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@stokku.test',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'admin@stokku.test')
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'admin@stokku.test']);

        $this->postJson('/api/v1/login', [
            'email' => 'admin@stokku.test',
            'password' => 'salah',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@stokku.test',
            'password' => 'password',
        ]);

        $token = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->json('data.token');

        $headers = ['Authorization' => 'Bearer '.$token];

        $this->getJson('/api/v1/me', $headers)->assertOk();
        $this->postJson('/api/v1/logout', [], $headers)->assertOk();

        // Forget the guard cache so the next test request re-resolves the token.
        $this->app['auth']->forgetGuards();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->getJson('/api/v1/me', $headers)->assertUnauthorized();
    }

    public function test_login_is_rate_limited_with_an_indonesian_message(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@stokku.test',
            'password' => 'password',
        ]);

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->postJson('/api/v1/login', [
                'email' => $user->email,
                'password' => 'salah',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'salah',
        ])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Terlalu banyak permintaan. Silakan tunggu sebentar lalu coba lagi.');
    }
}
