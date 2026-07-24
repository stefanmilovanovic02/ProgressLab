<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackDailyLogin;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(TrackDailyLogin::class);
        Carbon::setTestNow('2026-07-20 12:00:00');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('full_name')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->unique();
            $table->string('avatar_path')->nullable();
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

        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('login_date');
            $table->timestamps();
            $table->unique(['user_id', 'login_date']);
        });

        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('workout_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('workout_id')->nullable();
            $table->date('entry_date');
            $table->timestamps();
        });

        Schema::create('workout_log_exercises', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workout_log_id');
            $table->unsignedBigInteger('exercise_id');
            $table->timestamps();
        });

        Schema::create('workout_log_sets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workout_log_exercise_id');
            $table->unsignedTinyInteger('set_number');
            $table->unsignedSmallInteger('reps')->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('experience_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('source_type');
            $table->string('source_key');
            $table->unsignedInteger('points');
            $table->timestamps();
        });

        Schema::create('user_exercise_ranks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('exercise_id');
            $table->decimal('best_value', 10, 3);
            $table->decimal('score', 6, 2);
            $table->string('rank');
            $table->timestamps();
            $table->unique(['user_id', 'exercise_id']);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('user_exercise_ranks');
        Schema::dropIfExists('experience_events');
        Schema::dropIfExists('workout_log_sets');
        Schema::dropIfExists('workout_log_exercises');
        Schema::dropIfExists('workout_logs');
        Schema::dropIfExists('exercises');
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('friends');
        Schema::dropIfExists('users');
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_friends_login_leaderboard_only_contains_friends(): void
    {
        $user = $this->createUser('Me', 'me@example.test');
        $friend = $this->createUser('Training Friend', 'friend@example.test');
        $stranger = $this->createUser('Stranger', 'stranger@example.test');

        DB::table('friends')->insert([
            ['user_id' => $user->id, 'friend_id' => $friend->id, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $friend->id, 'friend_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->logDays($friend, ['2026-07-20', '2026-07-19', '2026-07-18']);
        $this->logDays($stranger, ['2026-07-20', '2026-07-19', '2026-07-18', '2026-07-17']);

        $response = $this->actingAs($user)->getJson(route('leaderboards.data', [
            'scope' => 'friends',
            'metric' => 'login',
        ]));

        $response->assertOk()
            ->assertJsonPath('rows.0.name', 'Training Friend')
            ->assertJsonPath('rows.0.value', '3 days')
            ->assertJsonPath('rows.0.rank', 1)
            ->assertJsonCount(1, 'rows');

        $this->assertArrayNotHasKey('email', $response->json('rows.0'));
    }

    public function test_global_exercise_leaderboard_ranks_real_records_without_zeroes(): void
    {
        $user = $this->createUser('Me', 'me@example.test');
        $friend = $this->createUser('No Record', 'friend@example.test');
        $strongest = $this->createUser('Strongest', 'strong@example.test');
        $exerciseId = DB::table('exercises')->insertGetId([
            'name' => 'Barbell Bench Press',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logWeight($user, $exerciseId, 80);
        $this->logWeight($strongest, $exerciseId, 105.5);

        $response = $this->actingAs($user)->getJson(route('leaderboards.data', [
            'scope' => 'global',
            'metric' => 'exercise',
            'exercise_id' => $exerciseId,
        ]));

        $response->assertOk()
            ->assertJsonPath('meta.exercise_name', 'Barbell Bench Press')
            ->assertJsonPath('rows.0.name', 'Strongest')
            ->assertJsonPath('rows.0.value', '105.5 kg')
            ->assertJsonPath('rows.1.name', 'Me')
            ->assertJsonPath('rows.1.value', '80 kg')
            ->assertJsonCount(2, 'rows');

        $this->assertNotContains($friend->name, collect($response->json('rows'))->pluck('name'));
    }

    public function test_global_account_rank_leaderboard_uses_total_xp_and_badges(): void
    {
        $user = $this->createUser('Me', 'me@example.test');
        $leader = $this->createUser('XP Leader', 'leader@example.test');

        DB::table('experience_events')->insert([
            [
                'user_id' => $user->id,
                'source_type' => 'test',
                'source_key' => 'mine',
                'points' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $leader->id,
                'source_type' => 'test',
                'source_key' => 'leader',
                'points' => 500,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($user)->getJson(route('leaderboards.data', [
            'scope' => 'global',
            'metric' => 'ranked',
        ]));

        $response->assertOk()
            ->assertJsonPath('rows.0.name', 'XP Leader')
            ->assertJsonPath('rows.0.value', 'Bronze IV')
            ->assertJsonPath('rows.0.detail', '500 total XP')
            ->assertJsonPath('rows.1.name', 'Me')
            ->assertJsonPath('rows.1.value', 'Bronze II');

        $this->assertStringContainsString('/images/ranks/bronze.png', $response->json('rows.0.badge_url'));
    }

    public function test_exercise_rank_mode_orders_scores_and_hides_unranked_users(): void
    {
        $user = $this->createUser('Me', 'me@example.test');
        $leader = $this->createUser('Rank Leader', 'rankleader@example.test');
        $unranked = $this->createUser('Unranked', 'unranked@example.test');
        $exerciseId = DB::table('exercises')->insertGetId([
            'name' => 'Barbell Bench Press',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_exercise_ranks')->insert([
            [
                'user_id' => $user->id,
                'exercise_id' => $exerciseId,
                'best_value' => .70,
                'score' => 70,
                'rank' => 'Diamond',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $leader->id,
                'exercise_id' => $exerciseId,
                'best_value' => .92,
                'score' => 92,
                'rank' => 'Titan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($user)->getJson(route('leaderboards.data', [
            'scope' => 'global',
            'metric' => 'exercise',
            'exercise_mode' => 'ranked',
            'exercise_id' => $exerciseId,
        ]));

        $response->assertOk()
            ->assertJsonPath('meta.exercise_mode', 'ranked')
            ->assertJsonPath('rows.0.name', 'Rank Leader')
            ->assertJsonPath('rows.0.value', 'Titan')
            ->assertJsonPath('rows.0.detail', '92 / 100 strength score')
            ->assertJsonPath('rows.1.value', 'Diamond')
            ->assertJsonCount(2, 'rows');

        $this->assertNotContains($unranked->name, collect($response->json('rows'))->pluck('name'));
    }

    private function createUser(string $name, string $email): User
    {
        return User::query()->create([
            'name' => $name,
            'full_name' => $name,
            'username' => strtolower(str_replace(' ', '_', $name)),
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function logDays(User $user, array $dates): void
    {
        foreach ($dates as $date) {
            DB::table('login_logs')->insert([
                'user_id' => $user->id,
                'login_date' => $date,
                'created_at' => $date . ' 12:00:00',
                'updated_at' => $date . ' 12:00:00',
            ]);
        }
    }

    private function logWeight(User $user, int $exerciseId, float $weight): void
    {
        $logId = DB::table('workout_logs')->insertGetId([
            'user_id' => $user->id,
            'workout_id' => null,
            'entry_date' => '2026-07-20',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $loggedExerciseId = DB::table('workout_log_exercises')->insertGetId([
            'workout_log_id' => $logId,
            'exercise_id' => $exerciseId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('workout_log_sets')->insert([
            'workout_log_exercise_id' => $loggedExerciseId,
            'set_number' => 1,
            'reps' => 5,
            'weight_kg' => $weight,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
