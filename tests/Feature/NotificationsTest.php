<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackDailyLogin;
use App\Models\AppNotification;
use App\Models\FriendActivity;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\WebPushService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
            $table->timestamp('push_sent_at')->nullable();
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

        Schema::create('friends', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('friend_id');
            $table->timestamps();
            $table->unique(['user_id', 'friend_id']);
        });

        Schema::create('friend_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->string('text');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->dateTime('login_date');
            $table->timestamps();
        });

        Schema::create('nutrition_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('entry_date');
            $table->unsignedSmallInteger('calories')->default(0);
            $table->unsignedSmallInteger('protein_g')->default(0);
            $table->unsignedSmallInteger('carbs_g')->default(0);
            $table->unsignedSmallInteger('fat_g')->default(0);
            $table->timestamps();
        });

        Schema::create('nutrition_goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedSmallInteger('calorie_target');
            $table->unsignedSmallInteger('protein_g');
            $table->unsignedSmallInteger('carbs_g');
            $table->unsignedSmallInteger('fat_g');
            $table->timestamps();
        });

        Schema::create('workout_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('entry_date');
            $table->timestamps();
        });

        config()->set('services.webpush.public_key', null);
        config()->set('services.webpush.private_key', null);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('workout_logs');
        Schema::dropIfExists('nutrition_goals');
        Schema::dropIfExists('nutrition_entries');
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('friend_activities');
        Schema::dropIfExists('friends');
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
        $now = Carbon::parse('2026-09-30 20:00:00', config('app.timezone'));
        Carbon::setTestNow($now);
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

        DB::table('login_logs')->insert([
            'user_id' => $user->id,
            'login_date' => $now->copy()->subDay()->toDateString(),
            'created_at' => $now->copy()->subHours(23),
            'updated_at' => $now->copy()->subHours(23),
        ]);

        $this->artisan('notifications:send-reminders', ['--now' => $now->toIso8601String()])->assertSuccessful();
        $this->artisan('notifications:send-reminders', ['--now' => $now->toIso8601String()])->assertSuccessful();

        $this->assertSame(1, $user->appNotifications()
            ->where('title', 'Your login streak is close to expiring')
            ->count());
    }

    public function test_reminders_cover_missing_food_unfinished_goals_and_workouts_without_duplicates(): void
    {
        $user = User::query()->create([
            'name' => 'Daily Reminder User',
            'email' => 'daily-reminders@example.test',
            'password' => 'password',
        ]);
        $user->pushSubscriptions()->create([
            'endpoint' => 'https://push.example.test/subscriptions/daily-device',
            'public_key' => str_repeat('a', 88),
            'auth_token' => str_repeat('b', 24),
            'content_encoding' => 'aes128gcm',
        ]);

        $daytime = Carbon::parse('2026-09-30 15:00:00', config('app.timezone'));
        $this->artisan('notifications:send-reminders', ['--now' => $daytime->toIso8601String()])->assertSuccessful();
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $user->id,
            'title' => 'Quick nutrition check-in',
        ]);

        DB::table('nutrition_entries')->insert([
            'user_id' => $user->id,
            'entry_date' => $daytime->toDateString(),
            'calories' => 1200,
            'protein_g' => 70,
            'carbs_g' => 100,
            'fat_g' => 35,
            'created_at' => $daytime,
            'updated_at' => $daytime,
        ]);
        DB::table('nutrition_goals')->insert([
            'user_id' => $user->id,
            'calorie_target' => 2500,
            'protein_g' => 160,
            'carbs_g' => 280,
            'fat_g' => 75,
            'created_at' => $daytime,
            'updated_at' => $daytime,
        ]);

        $evening = $daytime->copy()->setTime(20, 0);
        $this->artisan('notifications:send-reminders', ['--now' => $evening->toIso8601String()])->assertSuccessful();
        $this->artisan('notifications:send-reminders', ['--now' => $evening->toIso8601String()])->assertSuccessful();

        $this->assertSame(1, $user->appNotifications()->where('title', 'Your nutrition goal is not finished')->count());
        $this->assertSame(1, $user->appNotifications()->where('title', 'No workout logged today')->count());
    }

    public function test_push_test_reports_failure_when_no_provider_accepts_delivery(): void
    {
        $user = User::query()->create([
            'name' => 'No Push Device',
            'email' => 'no-device@example.test',
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->postJson(route('push-subscriptions.test'))
            ->assertStatus(502)
            ->assertJson([
                'ok' => false,
                'message' => 'The push provider did not accept the notification. Please disable push on this device, enable it again, and retry.',
            ]);
    }

    public function test_new_friend_activity_creates_and_pushes_one_notification_immediately(): void
    {
        $actor = User::query()->create([
            'name' => 'Nutrition Friend',
            'email' => 'nutrition-friend@example.test',
            'password' => 'password',
        ]);
        $recipient = User::query()->create([
            'name' => 'Push Recipient',
            'email' => 'push-recipient@example.test',
            'password' => 'password',
        ]);

        \Illuminate\Support\Facades\DB::table('friends')->insert([
            'user_id' => $actor->id,
            'friend_id' => $recipient->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $activity = FriendActivity::query()->create([
            'user_id' => $actor->id,
            'type' => 'nutrition',
            'text' => 'logged nutrition for today.',
            'meta' => ['date' => now()->toDateString()],
        ]);

        $push = \Mockery::mock(WebPushService::class);
        $push->shouldReceive('sendToUser')
            ->once()
            ->withArgs(fn (User $user, array $payload) =>
                $user->is($recipient)
                && $payload['title'] === 'Friend logged nutrition'
                && $payload['body'] === 'Nutrition Friend logged nutrition for today.'
                && $payload['url'] === route('friends.index', [], false)
            )
            ->andReturn(1);

        $service = new NotificationService($push);
        $this->assertSame(1, $service->notifyFriendActivity($activity));
        $this->assertSame(0, $service->notifyFriendActivity($activity));

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $recipient->id,
            'source_type' => 'friend_activity',
            'source_id' => $activity->id,
            'title' => 'Friend logged nutrition',
            'message' => 'Nutrition Friend logged nutrition for today.',
        ]);
        $this->assertNotNull($recipient->appNotifications()->first()->push_sent_at);
        $this->assertSame(1, $recipient->appNotifications()->count());
    }
}
