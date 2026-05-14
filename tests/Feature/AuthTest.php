<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user registration.
     */
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'phone' => '08123456789',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'token',
                'user' => ['id', 'name', 'email', 'role'],
            ])
            ->assertJson([
                'status' => 'success',
                'user' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    /**
     * Test user login with password.
     */
    public function test_user_can_login_with_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'token',
                'user' => ['id', 'name', 'role'],
            ]);
    }

    /**
     * Test user login with OTP.
     */
    public function test_user_can_login_with_otp(): void
    {
        $user = User::factory()->create();

        OtpCode::create([
            'email' => $user->email,
            'code' => '123456',
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'otp_code' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'token',
                'user' => ['id', 'name', 'role'],
            ]);

        $this->assertDatabaseMissing('otp_codes', [
            'email' => $user->email,
        ]);
    }

    /**
     * Test send OTP.
     */
    public function test_can_send_otp(): void
    {
        $response = $this->postJson('/api/auth/otp/send', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('otp_codes', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * Test get profile.
     */
    public function test_user_can_get_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'phone',
                'avatar',
                'role',
            ]);
    }

    /**
     * Test logout.
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertCount(0, $user->tokens);
    }
}
