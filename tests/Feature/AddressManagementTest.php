<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * User can update their own address.
     */
    public function test_user_can_update_their_own_address(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create([
            'user_id' => $user->id,
            'label' => 'Old Label',
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/user/addresses/{$address->id}", [
                'label' => 'New Label',
                'recipient' => 'New Recipient',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('label', 'New Label')
            ->assertJsonPath('recipient', 'New Recipient');

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'label' => 'New Label',
            'recipient_name' => 'New Recipient',
        ]);
    }

    /**
     * User cannot update someone else's address.
     */
    public function test_user_cannot_update_someone_elses_address(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $address = Address::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/user/addresses/{$address->id}", [
                'label' => 'Hack Attempt',
            ]);

        $response->assertStatus(404);
    }

    /**
     * Setting an address as default updates other addresses.
     */
    public function test_setting_address_as_default_updates_other_addresses(): void
    {
        $user = User::factory()->create();
        $address1 = Address::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $address2 = Address::factory()->create(['user_id' => $user->id, 'is_default' => false]);

        $response = $this->actingAs($user)
            ->patchJson("/api/user/addresses/{$address2->id}", [
                'is_default' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('is_default', true);

        $address1->refresh();
        $address2->refresh();

        $this->assertFalse($address1->is_default);
        $this->assertTrue($address2->is_default);
    }

    /**
     * User can delete their own address.
     */
    public function test_user_can_delete_their_own_address(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/user/addresses/{$address->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }

    /**
     * User cannot delete someone else's address.
     */
    public function test_user_cannot_delete_someone_elses_address(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $address = Address::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/user/addresses/{$address->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('addresses', ['id' => $address->id]);
    }
}
