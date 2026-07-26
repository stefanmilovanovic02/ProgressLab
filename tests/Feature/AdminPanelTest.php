<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\TrackDailyLogin;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(TrackDailyLogin::class);
        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('syncForUser')->zeroOrMoreTimes();
            $mock->shouldReceive('unreadCount')->zeroOrMoreTimes()->andReturn(0);
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('full_name')->nullable();
            $table->string('username')->nullable()->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('user');
            $table->string('gender')->nullable();
            $table->string('location')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('avatar_path')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('muscle_group')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
        Schema::create('exercise_rank_standards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exercise_id')->unique();
            $table->string('scoring_type');
            $table->decimal('olympian_target', 8, 2)->nullable();
            $table->string('unit');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('exercise_workout', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exercise_id');
            $table->unsignedBigInteger('workout_id');
            $table->timestamps();
        });
        Schema::create('user_exercise_ranks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('exercise_id');
            $table->decimal('best_value', 10, 3)->default(0);
            $table->decimal('score', 6, 2)->default(0);
            $table->string('rank')->default('Bronze');
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
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('login_date');
            $table->timestamps();
        });
        Schema::create('workouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('workout_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('workout_id')->nullable();
            $table->date('entry_date');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
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
            $table->unsignedInteger('reps')->nullable();
            $table->decimal('weight_kg', 8, 2)->nullable();
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
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('rarity')->default('common');
            $table->timestamps();
        });
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('achievement_id');
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();
        });
        Schema::create('friends', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('friend_id');
            $table->timestamps();
        });
        Schema::create('progress_photo_sets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('front_path');
            $table->string('side_path');
            $table->string('back_path');
            $table->date('captured_on')->nullable();
            $table->timestamps();
        });
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('plan');
            $table->string('status');
            $table->boolean('is_complimentary')->default(false);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'subscriptions', 'progress_photo_sets', 'friends', 'user_achievements', 'achievements',
            'nutrition_entries', 'workout_log_sets', 'workout_log_exercises',
            'workout_logs', 'workouts', 'login_logs', 'experience_events',
            'user_exercise_ranks', 'exercise_workout', 'exercise_rank_standards',
            'exercises', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_only_admins_and_owners_can_open_the_dashboard(): void
    {
        $this->actingAs($this->user(UserRole::User))
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($this->user(UserRole::Admin, 'admin@example.test'))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard');

        $this->actingAs($this->user(UserRole::Owner, 'owner@example.test'))
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_user_statistics_are_view_only_and_never_query_or_render_progress_photos(): void
    {
        $admin = $this->user(UserRole::Admin);
        $member = $this->user(UserRole::User, 'member@example.test');
        DB::table('progress_photo_sets')->insert([
            'user_id' => $member->id,
            'front_path' => 'private/front-secret.jpg',
            'side_path' => 'private/side-secret.jpg',
            'back_path' => 'private/back-secret.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = strtolower($query->sql);
        });

        $response = $this->actingAs($admin)
            ->get(route('admin.users.show', $member))
            ->assertOk()
            ->assertSee('Not editable')
            ->assertSee('Privacy protected')
            ->assertDontSee('front-secret.jpg')
            ->assertDontSee('side-secret.jpg')
            ->assertDontSee('back-secret.jpg');

        $this->assertFalse(
            collect($queries)->contains(fn (string $sql) => str_contains($sql, 'progress_photo_sets')),
            'The user statistics page must not query private progress photos.'
        );
    }

    public function test_admin_cannot_manage_owner_or_assign_staff_roles_but_owner_can_assign_admin(): void
    {
        $admin = $this->user(UserRole::Admin);
        $owner = $this->user(UserRole::Owner, 'owner@example.test');

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $owner))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), $this->userPayload('blocked@example.test', 'admin'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('admin.users.store'), $this->userPayload('new-admin@example.test', 'admin'))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'new-admin@example.test',
            'role' => 'admin',
        ]);
    }

    public function test_exercise_crud_creates_rank_standard_and_protects_logged_history(): void
    {
        $admin = $this->user(UserRole::Admin);

        $this->actingAs($admin)
            ->post(route('admin.exercises.store'), [
                'name' => 'Admin Test Press',
                'muscle_group' => 'Chest',
                'scoring_type' => 'estimated_1rm_bodyweight',
                'olympian_target' => 1.75,
                'unit' => 'ratio',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $exerciseId = DB::table('exercises')->where('name', 'Admin Test Press')->value('id');
        $this->assertNotNull($exerciseId);
        $this->assertDatabaseHas('exercise_rank_standards', [
            'exercise_id' => $exerciseId,
            'scoring_type' => 'estimated_1rm_bodyweight',
            'unit' => 'ratio',
            'is_active' => 1,
        ]);

        DB::table('workout_log_exercises')->insert([
            'workout_log_id' => 999,
            'exercise_id' => $exerciseId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.exercises.destroy', $exerciseId))
            ->assertSessionHasErrors('exercise');
        $this->assertDatabaseHas('exercises', ['id' => $exerciseId]);

        $assignedId = DB::table('exercises')->insertGetId([
            'name' => 'Assigned Exercise',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('exercise_workout')->insert([
            'exercise_id' => $assignedId,
            'workout_id' => 999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->actingAs($admin)
            ->delete(route('admin.exercises.destroy', $assignedId))
            ->assertSessionHasErrors('exercise');
        $this->assertDatabaseHas('exercises', ['id' => $assignedId]);

        $unusedId = DB::table('exercises')->insertGetId([
            'name' => 'Unused Exercise',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->actingAs($admin)
            ->delete(route('admin.exercises.destroy', $unusedId))
            ->assertRedirect(route('admin.exercises.index'));
        $this->assertDatabaseMissing('exercises', ['id' => $unusedId]);
    }

    public function test_only_owner_can_manage_subscriptions_and_revenue_uses_recorded_payments(): void
    {
        $admin = $this->user(UserRole::Admin);
        $owner = $this->user(UserRole::Owner, 'owner@example.test');
        $member = $this->user(UserRole::Paid, 'subscriber@example.test');

        $this->actingAs($admin)
            ->get(route('admin.subscriptions.index'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('admin.subscriptions.store'), [
                'user_id' => $member->id,
                'plan' => 'paid',
                'status' => 'active',
                'amount_paid' => 29.99,
                'currency' => 'EUR',
                'starts_on' => now()->toDateString(),
                'paid_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $member->id,
            'status' => 'active',
            'is_complimentary' => false,
            'amount_paid' => 29.99,
        ]);

        $this->actingAs($owner)
            ->post(route('admin.subscriptions.store'), [
                'user_id' => $member->id,
                'plan' => 'paid',
                'status' => 'active',
                'is_complimentary' => 1,
                'amount_paid' => 99,
                'currency' => 'EUR',
                'starts_on' => now()->toDateString(),
                'paid_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $member->id,
            'is_complimentary' => true,
            'amount_paid' => 0,
            'paid_at' => null,
        ]);

        $this->actingAs($owner)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Business overview')
            ->assertSee('Complimentary access')
            ->assertSee('€29.99');
    }

    public function test_staff_chart_endpoints_show_selected_users_macro_and_exercise_data(): void
    {
        $admin = $this->user(UserRole::Admin);
        $member = $this->user(UserRole::User, 'charts@example.test');
        $ordinaryUser = $this->user(UserRole::User, 'blocked-charts@example.test');

        DB::table('nutrition_entries')->insert([
            ['user_id' => $member->id, 'entry_date' => now()->subDay()->toDateString(), 'calories' => 2100, 'protein_g' => 140, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $member->id, 'entry_date' => now()->toDateString(), 'calories' => 2300, 'protein_g' => 160, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $exerciseId = DB::table('exercises')->insertGetId([
            'name' => 'Chart Test Press',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $logId = DB::table('workout_logs')->insertGetId([
            'user_id' => $member->id,
            'entry_date' => now()->toDateString(),
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
            ['workout_log_exercise_id' => $loggedExerciseId, 'reps' => 10, 'weight_kg' => 60, 'created_at' => now(), 'updated_at' => now()],
            ['workout_log_exercise_id' => $loggedExerciseId, 'reps' => 6, 'weight_kg' => 80, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($ordinaryUser)
            ->getJson(route('admin.users.charts.macros', [$member, 'macro' => 'protein', 'period' => 'month']))
            ->assertForbidden();

        $this->actingAs($admin)
            ->getJson(route('admin.users.charts.macros', [$member, 'macro' => 'protein', 'period' => 'month']))
            ->assertOk()
            ->assertJsonPath('values.0', 140)
            ->assertJsonPath('values.1', 160)
            ->assertJsonPath('insights.latest', 160);

        $this->actingAs($admin)
            ->getJson(route('admin.users.charts.exercise-data', [$member, 'exercise_id' => $exerciseId, 'period' => 'all']))
            ->assertOk()
            ->assertJsonPath('weight.0', 80)
            ->assertJsonPath('reps.0', 6)
            ->assertJsonPath('days', 1);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $member))
            ->assertOk()
            ->assertSee('Macronutrient Progress')
            ->assertSee('Exercise Progress')
            ->assertSee('Chart Test Press');
    }

    public function test_progress_photos_are_available_only_to_owner_routes(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('progress-photos/secret-front.jpg', 'private-photo');

        $admin = $this->user(UserRole::Admin);
        $owner = $this->user(UserRole::Owner, 'owner@example.test');
        $member = $this->user(UserRole::User, 'photo-member@example.test');
        $photoId = DB::table('progress_photo_sets')->insertGetId([
            'user_id' => $member->id,
            'front_path' => 'progress-photos/secret-front.jpg',
            'side_path' => 'progress-photos/secret-side.jpg',
            'back_path' => 'progress-photos/secret-back.jpg',
            'captured_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.progress-photos.show', [$photoId, 'front']))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('admin.progress-photos.show', [$photoId, 'front']))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->actingAs($owner)
            ->get(route('admin.users.show', $member))
            ->assertOk()
            ->assertSee('Progress photos')
            ->assertSee(route('admin.progress-photos.show', [$photoId, 'front']), false);
    }

    private function user(UserRole $role, string $email = 'role@example.test'): User
    {
        $user = new User([
            'name' => $role->label(),
            'full_name' => $role->label(),
            'username' => strtolower($role->value) . fake()->unique()->numberBetween(10, 9999),
            'email' => $email,
            'password' => 'password',
        ]);
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    private function userPayload(string $email, string $role): array
    {
        return [
            'full_name' => 'Created Account',
            'username' => 'created_' . str_replace(['@', '.'], '_', $email),
            'email' => $email,
            'role' => $role,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
    }
}
