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
    }

    protected function tearDown(): void
    {
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
            ->assertSee('pl-nav__notifications-badge', false);

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
}
