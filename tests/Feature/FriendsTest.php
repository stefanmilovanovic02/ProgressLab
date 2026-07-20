<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackDailyLogin;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FriendsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(TrackDailyLogin::class);
        Carbon::setTestNow('2026-07-20 12:00:00');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('friends', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('friend_id');
            $table->timestamps();
            $table->unique(['user_id', 'friend_id']);
        });

        Schema::create('friend_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('receiver_id');
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->unique(['sender_id', 'receiver_id']);
        });

        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('login_date');
            $table->timestamps();
            $table->unique(['user_id', 'login_date']);
        });

        Schema::create('workout_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('workout_id')->nullable();
            $table->date('entry_date');
            $table->timestamps();
        });

        Schema::create('nutrition_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('entry_date');
            $table->unsignedInteger('calories')->default(0);
            $table->unsignedInteger('protein_g')->default(0);
            $table->unsignedInteger('carbs_g')->default(0);
            $table->unsignedInteger('fat_g')->default(0);
            $table->unsignedInteger('creatine_g')->default(0);
            $table->unsignedInteger('water_ml')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('nutrition_entries');
        Schema::dropIfExists('workout_logs');
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('friend_requests');
        Schema::dropIfExists('friends');
        Schema::dropIfExists('users');
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_user_can_remove_a_friend_in_both_directions(): void
    {
        $user = $this->createUser('user@example.test');
        $friend = $this->createUser('friend@example.test');
        $this->makeFriends($user, $friend);

        DB::table('friend_requests')->insert([
            'sender_id' => $user->id,
            'receiver_id' => $friend->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->deleteJson(route('friends.destroy', $friend))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('friends', [
            'user_id' => $user->id,
            'friend_id' => $friend->id,
        ]);
        $this->assertDatabaseMissing('friends', [
            'user_id' => $friend->id,
            'friend_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('friend_requests', [
            'sender_id' => $user->id,
            'receiver_id' => $friend->id,
        ]);
    }

    public function test_a_user_cannot_remove_someone_who_is_not_their_friend(): void
    {
        $owner = $this->createUser('owner@example.test');
        $friend = $this->createUser('owners-friend@example.test');
        $otherUser = $this->createUser('other@example.test');
        $this->makeFriends($owner, $friend);

        $this->actingAs($otherUser)
            ->deleteJson(route('friends.destroy', $friend))
            ->assertNotFound();

        $this->assertDatabaseHas('friends', [
            'user_id' => $owner->id,
            'friend_id' => $friend->id,
        ]);
        $this->assertDatabaseHas('friends', [
            'user_id' => $friend->id,
            'friend_id' => $owner->id,
        ]);
    }

    public function test_friend_summary_reports_presence_and_highest_nutrition_streak(): void
    {
        $user = $this->createUser('viewer@example.test');
        $friend = $this->createUser('active-friend@example.test');
        $this->makeFriends($user, $friend);

        DB::table('login_logs')->insert([
            'user_id' => $friend->id,
            'login_date' => '2026-07-20 00:00:00',
            'created_at' => '2026-07-20 08:00:00',
            'updated_at' => '2026-07-20 11:56:00',
        ]);
        DB::table('workout_logs')->insert([
            'user_id' => $friend->id,
            'workout_id' => 1,
            'entry_date' => '2026-07-20',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['2026-07-19', '2026-07-20'] as $date) {
            DB::table('nutrition_entries')->insert([
                'user_id' => $friend->id,
                'entry_date' => $date,
                'protein_g' => 150,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($user)
            ->getJson(route('friends.summary', $friend))
            ->assertOk()
            ->assertJsonPath('user.status', 'Online')
            ->assertJsonPath('user.last_active', '4 minutes ago')
            ->assertJsonPath('streaks.0.label', 'Login Streak')
            ->assertJsonPath('streaks.1.label', 'Workout Streak')
            ->assertJsonPath('streaks.2.label', 'Protein Streak')
            ->assertJsonPath('streaks.2.value', 2);
    }

    public function test_authenticated_activity_refreshes_the_users_last_active_timestamp(): void
    {
        $user = $this->createUser('presence@example.test');
        DB::table('login_logs')->insert([
            'user_id' => $user->id,
            'login_date' => '2026-07-20 00:00:00',
            'created_at' => '2026-07-20 08:00:00',
            'updated_at' => '2026-07-20 08:00:00',
        ]);

        $request = Request::create('/friends', 'GET');
        $request->setUserResolver(fn ($guard = null) => $user);
        $this->assertSame($user->id, $request->user()->id);
        (new TrackDailyLogin())->handle($request, fn () => response('ok'));

        $updatedAt = DB::table('login_logs')
            ->where('user_id', $user->id)
            ->value('updated_at');

        $this->assertSame('2026-07-20 12:00:00', Carbon::parse($updatedAt)->format('Y-m-d H:i:s'));
        $this->assertSame(1, DB::table('login_logs')->where('user_id', $user->id)->count());
    }

    private function createUser(string $email): User
    {
        return User::query()->create([
            'name' => 'Friend User',
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function makeFriends(User $first, User $second): void
    {
        DB::table('friends')->insert([
            [
                'user_id' => $first->id,
                'friend_id' => $second->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $second->id,
                'friend_id' => $first->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
