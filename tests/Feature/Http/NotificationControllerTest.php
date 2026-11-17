<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Notification;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');

    Permission::findOrCreate('notification.create');
    Permission::findOrCreate('notification.update');
});

test('guest cannot access endpoints', function () {
    $notification = Notification::create([
        'title' => 'Test Notification',
        'body' => 'Test Body',
        'checksum' => hash('sha512', 'test1'),
        'source' => '1',
        'datetime' => now(),
    ]);

    $this->get(route('notifications.index'))->assertRedirect('/login');
    $this->get(route('notifications.archive'))->assertRedirect('/login');
    $this->postJson(route('notifications.store'), ['title' => 'Test', 'body' => 'Body'])->assertUnauthorized();
    $this->putJson(route('notifications.read', $notification))->assertUnauthorized();
    $this->putJson(route('notifications.read-all'))->assertUnauthorized();
    $this->putJson(route('notifications.stick', $notification))->assertUnauthorized();
    $this->deleteJson(route('notifications.unstick', $notification))->assertUnauthorized();
});

test('user can view notifications index', function () {
    $user = User::factory()->create(['enabled' => 1]);

    $notification = Notification::create([
        'title' => 'Sample Alert',
        'body' => 'Sample notification body',
        'checksum' => hash('sha512', 'sample-alert'),
        'source' => (string) $user->user_id,
        'datetime' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('notifications.index'));

    $response->assertOk();
    $response->assertViewIs('notifications.index');
    $response->assertViewHas('isArchive', false);
    $response->assertViewHas('unreadCount', 1);
    $response->assertSee('Sample Alert');
    $response->assertSee('Sample notification body');
});

test('user can view notifications archive', function () {
    $user = User::factory()->create(['enabled' => 1]);

    $notification = Notification::create([
        'title' => 'Archived Alert',
        'body' => 'Archived body text',
        'checksum' => hash('sha512', 'archived-alert'),
        'source' => (string) $user->user_id,
        'datetime' => now(),
    ]);

    // Mark as read
    $this->actingAs($user)->putJson(route('notifications.read', $notification));

    $response = $this->actingAs($user)->get(route('notifications.archive'));

    $response->assertOk();
    $response->assertViewIs('notifications.index');
    $response->assertViewHas('isArchive', true);
    $response->assertSee('Archived Alert');
    $response->assertSee('Archived body text');
});

test('user without permission cannot create notification', function () {
    $user = User::factory()->create(['enabled' => 1]);

    $response = $this->actingAs($user)->postJson(route('notifications.store'), [
        'title' => 'Test Notification',
        'body' => 'Test message',
    ]);

    $response->assertForbidden();
});

test('user with permission can create notification', function () {
    $user = User::factory()->create(['enabled' => 1]);
    $user->givePermissionTo('notification.create');

    $response = $this->actingAs($user)->postJson(route('notifications.store'), [
        'title' => 'New Alert',
        'body' => 'Details about the alert',
    ]);

    $response->assertOk();
    $response->assertJson([
        'status' => 'ok',
        'message' => 'Created',
    ]);

    $expectedChecksum = hash('sha512', $user->user_id . '.LOCAL.New Alert');
    $this->assertDatabaseHas('notifications', [
        'title' => 'New Alert',
        'body' => 'Details about the alert',
        'source' => (string) $user->user_id,
        'checksum' => $expectedChecksum,
    ]);
});

test('store validation', function () {
    $user = User::factory()->create(['enabled' => 1]);
    $user->givePermissionTo('notification.create');

    $response = $this->actingAs($user)->postJson(route('notifications.store'), []);
    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['title', 'body']);

    $response = $this->actingAs($user)->postJson(route('notifications.store'), [
        'title' => str_repeat('a', 256),
        'body' => 'Valid body',
    ]);
    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['title']);
});

test('user can mark notification as read', function () {
    $user = User::factory()->create(['enabled' => 1]);
    $notification = Notification::create([
        'title' => 'Unread Notification',
        'body' => 'Body text',
        'checksum' => hash('sha512', 'unread1'),
        'source' => 'system',
        'datetime' => now(),
    ]);

    $response = $this->actingAs($user)->putJson(route('notifications.read', $notification));

    $response->assertOk();
    $response->assertJson([
        'status' => 'ok',
        'message' => 'Set as Read',
    ]);

    $this->assertDatabaseHas('notifications_attribs', [
        'notifications_id' => $notification->notifications_id,
        'user_id' => $user->user_id,
        'key' => 'read',
        'value' => '1',
    ]);
});

test('user can mark all notifications as read', function () {
    $user = User::factory()->create(['enabled' => 1]);
    $notification1 = Notification::create([
        'title' => 'Notif 1',
        'body' => 'Body 1',
        'checksum' => hash('sha512', 'notif1'),
        'source' => 'system',
        'datetime' => now(),
    ]);
    $notification2 = Notification::create([
        'title' => 'Notif 2',
        'body' => 'Body 2',
        'checksum' => hash('sha512', 'notif2'),
        'source' => 'system',
        'datetime' => now(),
    ]);

    $response = $this->actingAs($user)->putJson(route('notifications.read-all'));

    $response->assertOk();
    $response->assertJson([
        'status' => 'ok',
        'message' => 'All notifications set as read',
    ]);

    $this->assertDatabaseHas('notifications_attribs', [
        'notifications_id' => $notification1->notifications_id,
        'user_id' => $user->user_id,
        'key' => 'read',
        'value' => '1',
    ]);
    $this->assertDatabaseHas('notifications_attribs', [
        'notifications_id' => $notification2->notifications_id,
        'user_id' => $user->user_id,
        'key' => 'read',
        'value' => '1',
    ]);
});

test('user without permission cannot stick or unstick', function () {
    $user = User::factory()->create(['enabled' => 1]);
    $notification = Notification::create([
        'title' => 'Sticky Candidate',
        'body' => 'Body',
        'checksum' => hash('sha512', 'sticky-cand'),
        'source' => 'system',
        'datetime' => now(),
    ]);

    $this->actingAs($user)->putJson(route('notifications.stick', $notification))->assertForbidden();
    $this->actingAs($user)->deleteJson(route('notifications.unstick', $notification))->assertForbidden();
});

test('user with permission can stick and unstick notification', function () {
    $user = User::factory()->create(['enabled' => 1]);
    $user->givePermissionTo('notification.update');

    $notification = Notification::create([
        'title' => 'Sticky Candidate',
        'body' => 'Body',
        'checksum' => hash('sha512', 'sticky-cand2'),
        'source' => 'system',
        'datetime' => now(),
    ]);

    $stickResponse = $this->actingAs($user)->putJson(route('notifications.stick', $notification));
    $stickResponse->assertOk();
    $stickResponse->assertJson([
        'status' => 'ok',
        'message' => 'Set as Sticky',
    ]);

    $this->assertDatabaseHas('notifications_attribs', [
        'notifications_id' => $notification->notifications_id,
        'user_id' => $user->user_id,
        'key' => 'sticky',
        'value' => '1',
    ]);

    $unstickResponse = $this->actingAs($user)->deleteJson(route('notifications.unstick', $notification));
    $unstickResponse->assertOk();
    $unstickResponse->assertJson([
        'status' => 'ok',
        'message' => 'Removed Sticky',
    ]);

    $this->assertDatabaseMissing('notifications_attribs', [
        'notifications_id' => $notification->notifications_id,
        'user_id' => $user->user_id,
        'key' => 'sticky',
    ]);
});

test('unstick only removes own sticky', function () {
    $user1 = User::factory()->create(['enabled' => 1]);
    $user1->givePermissionTo('notification.update');
    $user2 = User::factory()->create(['enabled' => 1]);
    $user2->givePermissionTo('notification.update');

    $notification = Notification::create([
        'title' => 'Multi-user Sticky',
        'body' => 'Body',
        'checksum' => hash('sha512', 'multi-sticky'),
        'source' => 'system',
        'datetime' => now(),
    ]);

    $this->actingAs($user1)->putJson(route('notifications.stick', $notification));
    $this->actingAs($user2)->putJson(route('notifications.stick', $notification));

    // User 1 unsticks
    $this->actingAs($user1)->deleteJson(route('notifications.unstick', $notification))->assertOk();

    // User 1's sticky is gone, but User 2's sticky remains
    $this->assertDatabaseMissing('notifications_attribs', [
        'notifications_id' => $notification->notifications_id,
        'user_id' => $user1->user_id,
        'key' => 'sticky',
    ]);
    $this->assertDatabaseHas('notifications_attribs', [
        'notifications_id' => $notification->notifications_id,
        'user_id' => $user2->user_id,
        'key' => 'sticky',
        'value' => '1',
    ]);
});

test('non existent notification route returns404', function () {
    $user = User::factory()->create(['enabled' => 1]);

    $this->actingAs($user)->putJson('/notifications/9999999/read')->assertNotFound();
});

test('menu notification count matches page count', function () {
    $user1 = User::factory()->create(['enabled' => 1]);
    $user2 = User::factory()->create(['enabled' => 1]);
    $user3 = User::factory()->create(['enabled' => 1]);

    // Create 5 notifications
    $notifications = [];
    for ($i = 1; $i <= 5; $i++) {
        $notifications[] = Notification::create([
            'title' => "Notification $i",
            'body' => "Body $i",
            'checksum' => hash('sha512', "checksum-$i"),
            'source' => 'system',
            'datetime' => now(),
        ]);
    }

    // User2 and User3 mark all 5 as read (creates 10 entries in notifications_attribs)
    foreach ($notifications as $notif) {
        $this->actingAs($user2)->putJson(route('notifications.read', $notif))->assertOk();
        $this->actingAs($user3)->putJson(route('notifications.read', $notif))->assertOk();
    }

    // User1 has read 2 notifications, so 3 remain unread
    $this->actingAs($user1)->putJson(route('notifications.read', $notifications[0]))->assertOk();
    $this->actingAs($user1)->putJson(route('notifications.read', $notifications[1]))->assertOk();

    // Check page
    $pageResponse = $this->actingAs($user1)->get(route('notifications.index'));
    $pageResponse->assertOk();
    $pageResponse->assertViewHas('unreadCount', 3);

    // Check menu query count directly (as computed by MenuComposer)
    $menuCount = Notification::isSticky()->orWhere(fn ($q) => $q->isUnread($user1))->count();
    expect($menuCount)->toBe(3);
});

test('notifications are ordered newest first', function () {
    $user = User::factory()->create(['enabled' => 1]);

    $older = Notification::create([
        'title' => 'Older Notification',
        'body' => 'Older Body',
        'checksum' => hash('sha512', 'older-notif'),
        'source' => 'system',
        'datetime' => '2026-01-01 10:00:00',
    ]);

    $newer = Notification::create([
        'title' => 'Newer Notification',
        'body' => 'Newer Body',
        'checksum' => hash('sha512', 'newer-notif'),
        'source' => 'system',
        'datetime' => '2026-01-02 10:00:00',
    ]);

    // Unread notifications: newer first
    $response = $this->actingAs($user)->get(route('notifications.index'));
    $response->assertOk();
    $notifications = $response->viewData('notifications');
    expect($notifications->pluck('notifications_id')->values()->all())->toBe([$newer->notifications_id, $older->notifications_id]);

    // Read notifications: newer first
    $this->actingAs($user)->putJson(route('notifications.read', $older))->assertOk();
    $this->actingAs($user)->putJson(route('notifications.read', $newer))->assertOk();

    $archiveResponse = $this->actingAs($user)->get(route('notifications.archive'));
    $archiveResponse->assertOk();
    $archiveNotifications = $archiveResponse->viewData('notifications');
    expect($archiveNotifications->pluck('notifications_id')->values()->all())->toBe([$newer->notifications_id, $older->notifications_id]);
});