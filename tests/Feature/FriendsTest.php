<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackDailyLogin;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FriendsTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('friend_requests');
        Schema::dropIfExists('friends');
        Schema::dropIfExists('users');

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
