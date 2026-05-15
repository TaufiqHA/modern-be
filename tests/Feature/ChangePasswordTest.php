<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test change password successfully.
     */
    public function test_change_password_successfully(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/user/change-password', [
                'current_password' => 'old_password',
                'new_password' => 'new_password123',
                'new_password_confirmation' => 'new_password123',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $user->refresh();
        $this->assertTrue(Hash::check('new_password123', $user->password));
    }

    /**
     * Test change password fails with wrong current password.
     */
    public function test_change_password_fails_with_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/user/change-password', [
                'current_password' => 'wrong_password',
                'new_password' => 'new_password123',
                'new_password_confirmation' => 'new_password123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    /**
     * Test change password fails if new password is too short.
     */
    public function test_change_password_fails_if_new_password_is_too_short(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/user/change-password', [
                'current_password' => 'old_password',
                'new_password' => 'short',
                'new_password_confirmation' => 'short',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }

    /**
     * Test change password unauthorized.
     */
    public function test_change_password_unauthorized(): void
    {
        $response = $this->postJson('/api/user/change-password', [
            'current_password' => 'old_password',
            'new_password' => 'new_password123',
            'new_password_confirmation' => 'new_password123',
        ]);

        $response->assertStatus(401);
    }
}
