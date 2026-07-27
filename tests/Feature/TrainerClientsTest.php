<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\TrackDailyLogin;
use App\Models\TrainerClient;
use App\Models\SubscriptionRequest;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TrainerClientsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TrackDailyLogin::class);
        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('syncForUser')->zeroOrMoreTimes();
            $mock->shouldReceive('unreadCount')->zeroOrMoreTimes()->andReturn(0);
            $mock->shouldReceive('notifyTrainerInvitation')->zeroOrMoreTimes();
            $mock->shouldReceive('notifyTrainerInvitationAccepted')->zeroOrMoreTimes();
            $mock->shouldReceive('sendSystem')->zeroOrMoreTimes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('full_name')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('user');
            $table->string('avatar_path')->nullable();
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
        Schema::create('trainer_clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trainer_id');
            $table->unsignedBigInteger('client_id');
            $table->string('status')->default('pending');
            $table->boolean('can_view_nutrition')->default(true);
            $table->boolean('can_view_exercises')->default(true);
            $table->boolean('can_view_weight')->default(true);
            $table->boolean('can_view_streaks')->default(true);
            $table->text('trainer_notes')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['trainer_id', 'client_id']);
        });
        Schema::create('subscription_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('plan');
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('EUR');
            $table->string('paypal_email');
            $table->string('paypal_transaction_id')->unique();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('owner_notes')->nullable();
            $table->timestamps();
        });
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('plan');
            $table->string('status')->default('active');
            $table->boolean('is_complimentary')->default(false);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('currency')->default('EUR');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('nutrition_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('entry_date');
            $table->integer('calories')->nullable();
            $table->decimal('protein_g')->nullable();
            $table->decimal('carbs_g')->nullable();
            $table->decimal('fat_g')->nullable();
            $table->decimal('creatine_g')->nullable();
            $table->decimal('water_ml')->nullable();
            $table->timestamps();
        });
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('login_date');
            $table->timestamps();
        });
        Schema::create('weight_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('recorded_on');
            $table->decimal('weight_kg');
            $table->string('source')->default('profile');
            $table->timestamps();
        });
        Schema::create('progress_photo_sets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('captured_on');
            $table->string('front_path');
            $table->string('side_path');
            $table->string('back_path');
            $table->timestamps();
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
            $table->integer('reps')->nullable();
            $table->decimal('weight_kg')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'workout_log_sets', 'workout_log_exercises', 'workout_logs', 'exercises',
            'progress_photo_sets', 'weight_entries', 'login_logs', 'nutrition_entries',
            'subscriptions', 'subscription_requests', 'trainer_clients', 'friends', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_trainer_must_be_friends_before_inviting_and_client_controls_permissions(): void
    {
        $trainer = $this->user(UserRole::Trainer, 'trainer@example.test');
        $client = $this->user(UserRole::User, 'client@example.test');

        $this->actingAs($trainer)
            ->postJson(route('trainer-invitations.store', $client))
            ->assertUnprocessable();

        $this->makeFriends($trainer, $client);
        $response = $this->actingAs($trainer)
            ->postJson(route('trainer-invitations.store', $client))
            ->assertOk()
            ->assertJsonPath('relationship.status', 'pending');

        $relationship = TrainerClient::findOrFail($response->json('relationship.id'));
        $this->actingAs($client)
            ->postJson(route('trainer-invitations.accept', $relationship), [
                'can_view_nutrition' => true,
                'can_view_exercises' => false,
                'can_view_weight' => true,
                'can_view_streaks' => false,
            ])
            ->assertOk();

        $this->assertDatabaseHas('trainer_clients', [
            'id' => $relationship->id,
            'status' => TrainerClient::STATUS_ACCEPTED,
            'can_view_nutrition' => 1,
            'can_view_exercises' => 0,
            'can_view_weight' => 1,
            'can_view_streaks' => 0,
        ]);
    }

    public function test_trainer_chart_endpoints_enforce_relationship_and_each_permission(): void
    {
        $trainer = $this->user(UserRole::Trainer, 'trainer@example.test');
        $client = $this->user(UserRole::User, 'client@example.test');
        $otherTrainer = $this->user(UserRole::Trainer, 'other@example.test');
        $relationship = TrainerClient::create([
            'trainer_id' => $trainer->id,
            'client_id' => $client->id,
            'status' => TrainerClient::STATUS_ACCEPTED,
            'can_view_nutrition' => true,
            'can_view_exercises' => false,
            'can_view_weight' => true,
            'can_view_streaks' => false,
        ]);
        DB::table('nutrition_entries')->insert([
            'user_id' => $client->id, 'entry_date' => now()->toDateString(), 'calories' => 2200,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('weight_entries')->insert([
            'user_id' => $client->id, 'recorded_on' => now()->toDateString(), 'weight_kg' => 81.5,
            'source' => 'profile', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $exerciseId = DB::table('exercises')->insertGetId(['name' => 'Test Press', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($trainer)
            ->getJson(route('trainer.clients.charts.macros', [$client, 'macro' => 'calories', 'period' => 'all']))
            ->assertOk()
            ->assertJsonPath('values.0', 2200);
        $this->actingAs($trainer)
            ->getJson(route('trainer.clients.charts.weight', [$client, 'period' => 'all']))
            ->assertOk()
            ->assertJsonPath('values.0', 81.5);
        $this->actingAs($trainer)
            ->getJson(route('trainer.clients.charts.exercise-data', [$client, 'exercise_id' => $exerciseId]))
            ->assertForbidden();
        $this->actingAs($otherTrainer)
            ->getJson(route('trainer.clients.charts.macros', [$client, 'period' => 'month']))
            ->assertForbidden();

        $this->actingAs($client)
            ->deleteJson(route('trainer-access.destroy', $relationship))
            ->assertOk();
        $this->actingAs($trainer)
            ->getJson(route('trainer.clients.charts.macros', [$client, 'period' => 'month']))
            ->assertForbidden();
    }

    public function test_free_user_cannot_request_year_or_all_time_charts_but_paid_user_can(): void
    {
        $user = $this->user(UserRole::User, 'free@example.test');
        $paid = $this->user(UserRole::Paid, 'paid@example.test');

        $this->actingAs($user)
            ->getJson(route('charts.macros', ['macro' => 'calories', 'period' => 'year']))
            ->assertForbidden();
        $this->actingAs($user)
            ->getJson(route('charts.macros', ['macro' => 'calories', 'period' => 'month']))
            ->assertOk();
        $this->actingAs($paid)
            ->getJson(route('charts.macros', ['macro' => 'calories', 'period' => 'all']))
            ->assertOk();
    }

    public function test_free_chart_page_shows_upgrade_panels_and_plans_page_has_three_options(): void
    {
        $user = $this->user(UserRole::User, 'plans@example.test');

        $this->actingAs($user)
            ->get(route('charts.index'))
            ->assertOk()
            ->assertSee('data-macro-locked-period="year"', false)
            ->assertSee('data-exercise-locked-period="all"', false)
            ->assertSee('Upgrade to see the full insights')
            ->assertSee(route('plans.index'), false);

        $this->actingAs($user)
            ->get(route('plans.index'))
            ->assertOk()
            ->assertSee('Free')
            ->assertSee('ProgressLab+')
            ->assertSee('Trainer')
            ->assertSee('Manual PayPal activation')
            ->assertSee('paypal.me/StefanMilovanovic02/4.99EUR', false)
            ->assertSee('paypal.me/StefanMilovanovic02/14.99EUR', false)
            ->assertSee('Upgrade to ProgressLab+')
            ->assertSee('Your current plan');
    }

    public function test_paid_chart_page_renders_full_period_buttons_without_upgrade_overlay(): void
    {
        $paid = $this->user(UserRole::Paid, 'paid-plans@example.test');

        $this->actingAs($paid)
            ->get(route('charts.index'))
            ->assertOk()
            ->assertSee('data-period="year"', false)
            ->assertSee('data-ep-period="all"', false)
            ->assertDontSee('<div class="ch-upgrade-overlay" data-macro-upgrade', false);

        $this->actingAs($paid)
            ->get(route('plans.index'))
            ->assertOk()
            ->assertSee('ProgressLab+ active')
            ->assertSee('View your plan');
    }

    public function test_trainer_footer_links_to_the_active_trainer_plan(): void
    {
        $trainer = $this->user(UserRole::Trainer, 'footer-trainer@example.test');

        $this->actingAs($trainer)
            ->get(route('plans.index'))
            ->assertOk()
            ->assertSee('Trainer active')
            ->assertSee('View your Trainer plan');
    }

    public function test_paypal_claim_requires_owner_verification_before_role_activation(): void
    {
        $user = $this->user(UserRole::User, 'buyer@example.test');
        $admin = $this->user(UserRole::Admin, 'admin@example.test');
        $owner = $this->user(UserRole::Owner, 'owner@example.test');

        $this->actingAs($user)
            ->post(route('plans.request-activation'), [
                'plan' => 'paid',
                'paypal_email' => 'payer@example.test',
                'paypal_transaction_id' => '12A34567BC890123D',
            ])
            ->assertRedirect(route('plans.index'));

        $claim = SubscriptionRequest::query()->firstOrFail();
        $this->assertSame(UserRole::User, $user->fresh()->role);
        $this->assertSame('4.99', $claim->amount);
        $this->assertSame('pending', $claim->status);

        $this->actingAs($admin)
            ->post(route('admin.subscription-requests.approve', $claim))
            ->assertForbidden();
        $this->assertSame(UserRole::User, $user->fresh()->role);

        $this->actingAs($owner)
            ->post(route('admin.subscription-requests.approve', $claim))
            ->assertRedirect();

        $this->assertSame(UserRole::Paid, $user->fresh()->role);
        $this->assertDatabaseHas('subscription_requests', ['id' => $claim->id, 'status' => 'approved']);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan' => 'paid',
            'status' => 'active',
            'amount_paid' => 4.99,
            'is_complimentary' => 0,
        ]);
    }

    public function test_duplicate_paypal_transaction_ids_and_parallel_pending_claims_are_rejected(): void
    {
        $first = $this->user(UserRole::User, 'first-buyer@example.test');
        $second = $this->user(UserRole::User, 'second-buyer@example.test');
        $payload = [
            'plan' => 'trainer',
            'paypal_email' => 'payer@example.test',
            'paypal_transaction_id' => '98Z76543YX210987W',
        ];

        $this->actingAs($first)->post(route('plans.request-activation'), $payload)->assertRedirect();
        $this->actingAs($second)
            ->post(route('plans.request-activation'), $payload)
            ->assertSessionHasErrors('paypal_transaction_id');
        $this->actingAs($first)
            ->post(route('plans.request-activation'), [
                ...$payload,
                'paypal_transaction_id' => '11Z76543YX210987W',
            ])
            ->assertSessionHasErrors('activation');

        $this->assertSame(1, SubscriptionRequest::query()->count());
    }

    public function test_dashboard_does_not_reveal_unshared_activity_through_status_fields(): void
    {
        $trainer = $this->user(UserRole::Trainer, 'trainer@example.test');
        $client = $this->user(UserRole::User, 'private@example.test');
        TrainerClient::create([
            'trainer_id' => $trainer->id,
            'client_id' => $client->id,
            'status' => TrainerClient::STATUS_ACCEPTED,
            'can_view_nutrition' => false,
            'can_view_exercises' => false,
            'can_view_weight' => false,
            'can_view_streaks' => false,
        ]);
        DB::table('nutrition_entries')->insert([
            'user_id' => $client->id, 'entry_date' => now()->toDateString(), 'calories' => 2200,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('login_logs')->insert([
            'user_id' => $client->id, 'login_date' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($trainer)
            ->get(route('trainer.dashboard'))
            ->assertOk()
            ->assertSee('Not shared')
            ->assertViewHas('summary', fn (array $summary) =>
                $summary['missing_nutrition'] === 0
                && $summary['active_this_week'] === 0
                && $summary['streak_at_risk'] === 0
            );
    }

    public function test_client_dashboard_is_read_only_and_never_offers_progress_photos(): void
    {
        $trainer = $this->user(UserRole::Trainer, 'trainer-view@example.test');
        $client = $this->user(UserRole::User, 'client-view@example.test');
        TrainerClient::create([
            'trainer_id' => $trainer->id,
            'client_id' => $client->id,
            'status' => TrainerClient::STATUS_ACCEPTED,
            'can_view_nutrition' => false,
            'can_view_exercises' => false,
            'can_view_weight' => false,
            'can_view_streaks' => false,
        ]);

        $this->actingAs($trainer)
            ->get(route('trainer.clients.show', $client))
            ->assertOk()
            ->assertSee('Read-only client dashboard')
            ->assertSee('Progress photos excluded')
            ->assertDontSee('progress-photos.show', false)
            ->assertDontSee('Streaks and achievements')
            ->assertDontSee('tr-achievements', false)
            ->assertSee('Private notes');
    }

    public function test_dashboard_lists_recent_records_only_for_clients_sharing_exercises(): void
    {
        $trainer = $this->user(UserRole::Trainer, 'records-trainer@example.test');
        $client = $this->user(UserRole::User, 'records-client@example.test');
        TrainerClient::create([
            'trainer_id' => $trainer->id,
            'client_id' => $client->id,
            'status' => TrainerClient::STATUS_ACCEPTED,
            'can_view_nutrition' => false,
            'can_view_exercises' => true,
            'can_view_weight' => false,
            'can_view_streaks' => false,
        ]);
        $exerciseId = DB::table('exercises')->insertGetId(['name' => 'Shared Record Press', 'created_at' => now(), 'updated_at' => now()]);
        $logId = DB::table('workout_logs')->insertGetId([
            'user_id' => $client->id, 'entry_date' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $loggedId = DB::table('workout_log_exercises')->insertGetId([
            'workout_log_id' => $logId, 'exercise_id' => $exerciseId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('workout_log_sets')->insert([
            'workout_log_exercise_id' => $loggedId, 'reps' => 5, 'weight_kg' => 100,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($trainer)
            ->get(route('trainer.dashboard'))
            ->assertOk()
            ->assertSee('Shared Record Press')
            ->assertSee('100 kg');
    }

    private function user(UserRole $role, string $email): User
    {
        $user = User::query()->create([
            'name' => $role->label(),
            'full_name' => $role->label(),
            'username' => $role->value . fake()->unique()->numberBetween(1, 999),
            'email' => $email,
            'password' => 'password',
        ]);
        $user->forceFill(['role' => $role])->save();
        return $user->fresh();
    }

    private function makeFriends(User $first, User $second): void
    {
        DB::table('friends')->insert([
            ['user_id' => $first->id, 'friend_id' => $second->id, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $second->id, 'friend_id' => $first->id, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
