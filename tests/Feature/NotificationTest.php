<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can view their own notifications.
     */
    public function test_user_can_view_own_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Notification::factory()->count(3)->create(['user_id' => $user->id]);
        Notification::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'message',
                        'type',
                        'is_read',
                        'created_at',
                    ],
                ],
                'meta',
            ]);
    }

    /**
     * Test user can mark a notification as read.
     */
    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_read', true);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => 1,
        ]);
    }

    /**
     * Test user can mark all notifications as read.
     */
    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(5)->create([
            'user_id' => $user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)
            ->patchJson('/api/notifications/read-all');

        $response->assertStatus(200);

        $this->assertEquals(0, Notification::where('user_id', $user->id)->where('is_read', false)->count());
    }

    /**
     * Test user cannot see or mark as read notifications of other users.
     */
    public function test_user_cannot_interact_with_others_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $otherUser->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(404);
    }
}
