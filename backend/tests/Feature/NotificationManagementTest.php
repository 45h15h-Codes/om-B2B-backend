<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\AdminPermission;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationManagementTest extends TestCase
{
    use RefreshDatabase;

    private function getUser($email = 'admin@omgems.com', $role = 'normal_admin')
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'role' => $role
            ]
        );

        if ($role === 'normal_admin') {
            AdminPermission::firstOrCreate([
                'user_id' => $user->id,
                'permission' => 'view_notifications',
            ]);
            $user->refreshPermissionsCache();
        }

        return $user;
    }

    private function createNotification($user, $data = [])
    {
        return DatabaseNotification::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Notifications\\SystemAlertNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => array_merge([
                'title' => 'Test Notification',
                'message' => 'This is a test notification message.',
                'action_url' => '/home',
            ], $data),
            'read_at' => null,
        ]);
    }

    public function test_mark_read_works_and_returns_ajax_response()
    {
        $user = $this->getUser();
        $notification = $this->createNotification($user);

        $response = $this->actingAs($user)
            ->postJson("/notifications/read/{$notification->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'unread_count' => 0,
            ]);

        $this->assertNotNull($notification->refresh()->read_at);
        $this->assertCount(1, $response->json('notifications'));
        $this->assertNotNull($response->json('notifications.0.read_at'));
    }

    public function test_mark_unread_works_and_returns_ajax_response()
    {
        $user = $this->getUser();
        $notification = $this->createNotification($user);
        $notification->markAsRead();

        $this->assertNotNull($notification->refresh()->read_at);

        $response = $this->actingAs($user)
            ->postJson("/notifications/unread/{$notification->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'unread_count' => 1,
            ]);

        $this->assertNull($notification->refresh()->read_at);
        $this->assertCount(1, $response->json('notifications'));
        $this->assertNull($response->json('notifications.0.read_at'));
    }

    public function test_delete_single_works_and_returns_ajax_response()
    {
        $user = $this->getUser();
        $notification = $this->createNotification($user);

        $response = $this->actingAs($user)
            ->postJson("/notifications/delete/{$notification->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'unread_count' => 0,
                'notifications' => [],
            ]);

        $this->assertSoftDeleted('notifications', ['id' => $notification->id]);
    }

    public function test_delete_all_works_and_returns_ajax_response()
    {
        $user = $this->getUser();
        $this->createNotification($user, ['title' => 'Notif 1']);
        $this->createNotification($user, ['title' => 'Notif 2']);

        $response = $this->actingAs($user)
            ->postJson('/notifications/delete-all');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'unread_count' => 0,
                'notifications' => [],
            ]);

        $this->assertSoftDeleted('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class
        ]);
    }

    public function test_user_cannot_manage_notifications_of_another_user()
    {
        $userA = $this->getUser('usera@omgems.com');
        $userB = $this->getUser('userb@omgems.com');
        
        $notifB = $this->createNotification($userB);

        // A tries to mark read B's notification
        $response = $this->actingAs($userA)
            ->postJson("/notifications/read/{$notifB->id}");
        $response->assertStatus(404);
        $this->assertNull($notifB->refresh()->read_at);

        // A tries to mark unread B's notification
        $notifB->markAsRead();
        $response = $this->actingAs($userA)
            ->postJson("/notifications/unread/{$notifB->id}");
        $response->assertStatus(404);
        $this->assertNotNull($notifB->refresh()->read_at);

        // A tries to delete B's notification
        $response = $this->actingAs($userA)
            ->postJson("/notifications/delete/{$notifB->id}");
        $response->assertStatus(404);
        $this->assertDatabaseHas('notifications', ['id' => $notifB->id]);
    }

    public function test_super_admin_cannot_delete_notifications_belonging_to_other_users()
    {
        $superAdmin = $this->getUser('superadmin@omgems.com', 'super_admin');
        $userB = $this->getUser('userb@omgems.com');
        
        $notifB = $this->createNotification($userB);

        // Super Admin tries to delete B's notification
        $response = $this->actingAs($superAdmin)
            ->postJson("/notifications/delete/{$notifB->id}");
        
        $response->assertStatus(404);
        $this->assertDatabaseHas('notifications', ['id' => $notifB->id]);

        // Super Admin tries to delete-all (should only delete Super Admin's own, not B's)
        $response = $this->actingAs($superAdmin)
            ->postJson('/notifications/delete-all');

        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', ['id' => $notifB->id]);
    }
}
