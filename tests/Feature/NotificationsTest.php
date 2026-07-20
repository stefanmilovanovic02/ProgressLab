<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackDailyLogin;
use App\Models\AppNotification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(TrackDailyLogin::class);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id');
            $table->string('category', 30);
            $table->string('title');
            $table->text('message');
            $table->string('icon', 20)->nullable();
            $table->string('action_url')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'source_type', 'source_id']);
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('endpoint')->unique();
            $table->text('public_key');
            $table->text('auth_token');
            $table->string('content_encoding')->default('aes128gcm');
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->dateTime('login_date');
            $table->timestamps();
        });

        config()->set('services.webpush.public_key', null);
        config()->set('services.webpush.private_key', null);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_notification_center_syncs_without_duplicates_and_marks_all_read(): void
    {
        $user = User::query()->create([
            'name' => 'Notification User',
            'email' => 'notifications@example.test',
            'password' => 'password',
        ]);

        $service = app(NotificationService::class);
        $service->syncForUser($user);
        $service->syncForUser($user);

        $this->assertSame(1, $user->appNotifications()->count());
        $this->assertSame(1, $service->unreadCount($user));

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Welcome to your notification center')
            ->assertSee('pl-nav__notifications-badge', false)
            ->assertSee('Never lose a streak')
            ->assertSee('data-push-settings', false);

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $service->unreadCount($user));
    }

    public function test_users_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'password' => 'password',
        ]);
        $otherUser = User::query()->create([
            'name' => 'Other User',
            'email' => 'other@example.test',
            'password' => 'password',
        ]);

        app(NotificationService::class)->syncForUser($owner);
        $notification = AppNotification::query()->where('user_id', $owner->id)->firstOrFail();

        $this->actingAs($otherUser)
            ->post(route('notifications.read', $notification))
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_user_can_enable_and_disable_push_for_a_device(): void
    {
        $user = User::query()->create([
            'name' => 'Push User',
            'email' => 'push@example.test',
            'password' => 'password',
        ]);

        $payload = [
            'endpoint' => 'https://push.example.test/subscriptions/device-one',
            'keys' => [
                'p256dh' => str_repeat('a', 88),
                'auth' => str_repeat('b', 24),
            ],
            'contentEncoding' => 'aes128gcm',
        ];

        $this->actingAs($user)
            ->postJson(route('push-subscriptions.store'), $payload)
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => $payload['endpoint'],
        ]);

        $this->actingAs($user)
            ->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => $payload['endpoint']])
            ->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $payload['endpoint']]);
    }

    public function test_reminder_command_creates_one_streak_expiry_notification_per_day(): void
    {
        $user = User::query()->create([
            'name' => 'Streak User',
            'email' => 'streak@example.test',
            'password' => 'password',
        ]);

        $user->pushSubscriptions()->create([
            'endpoint' => 'https://push.example.test/subscriptions/streak-device',
            'public_key' => str_repeat('a', 88),
            'auth_token' => str_repeat('b', 24),
            'content_encoding' => 'aes128gcm',
        ]);

        \App\Models\LoginLog::query()->create([
            'user_id' => $user->id,
            'login_date' => now()->subDay()->startOfDay(),
        ]);

        $this->artisan('notifications:send-reminders')->assertSuccessful();
        $this->artisan('notifications:send-reminders')->assertSuccessful();

        $this->assertSame(1, $user->appNotifications()
            ->where('title', 'Your streak expires tonight 🔥')
            ->count());
    }
}
