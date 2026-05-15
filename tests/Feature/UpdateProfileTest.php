<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test update profile name successfully.
     */
    public function test_update_profile_name_successfully(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)
            ->patchJson('/api/user/me', [
                'name' => 'New Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'New Name');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
        ]);
    }

    /**
     * Test update profile email fails if already taken.
     */
    public function test_update_profile_email_fails_if_already_taken(): void
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $user = User::factory()->create(['email' => 'user@example.com']);

        $response = $this->actingAs($user)
            ->patchJson('/api/user/me', [
                'email' => 'other@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test update profile with avatar upload.
     */
    public function test_update_profile_with_avatar_upload(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($user)
            ->patchJson('/api/user/me', [
                'avatar' => $file,
            ]);

        $response->assertStatus(200);

        // Check if file is stored
        Storage::disk('public')->assertExists('avatars/'.$file->hashName());

        $user->refresh();
        $this->assertStringContainsString($file->hashName(), $user->avatar_url);
    }

    /**
     * Test update profile unauthorized.
     */
    public function test_update_profile_unauthorized(): void
    {
        $response = $this->patchJson('/api/user/me', [
            'name' => 'New Name',
        ]);

        $response->assertStatus(401);
    }
}
