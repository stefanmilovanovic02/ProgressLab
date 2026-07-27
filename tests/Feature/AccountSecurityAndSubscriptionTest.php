<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\TrainerClient;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AccountSecurityAndSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_password_reset_request_is_generic_and_sends_a_branded_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas(
                'status',
                'If an account exists for that email, a password reset link has been sent.'
            );

        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $this->post(route('password.email'), ['email' => 'missing@example.test'])
            ->assertSessionHas(
                'status',
                'If an account exists for that email, a password reset link has been sent.'
            );
    }

    public function test_a_valid_reset_token_changes_password_and_rotates_remember_token(): void
    {
        $user = User::factory()->create([
            'remember_token' => 'old-remember-token',
        ]);
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Securepass123',
            'password_confirmation' => 'Securepass123',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('Securepass123', $user->password));
        $this->assertNotSame('old-remember-token', $user->remember_token);
    }

    public function test_login_attempts_are_rate_limited_and_security_headers_are_present(): void
    {
        $response = $this->get(route('login'));
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), [
                'email' => 'attacker@example.test',
                'password' => 'incorrect-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), [
            'email' => 'attacker@example.test',
            'password' => 'incorrect-password',
        ])->assertStatus(429);
    }

    public function test_expired_paid_plan_downgrades_user_and_expires_subscription(): void
    {
        Carbon::setTestNow('2026-07-26 10:00:00');
        $user = User::factory()->create();
        $user->forceFill(['role' => UserRole::Paid])->save();

        $subscription = Subscription::query()->create([
            'user_id' => $user->id,
            'plan' => 'paid',
            'status' => 'active',
            'is_complimentary' => false,
            'amount_paid' => 4.99,
            'currency' => 'EUR',
            'starts_on' => '2026-06-26',
            'ends_on' => '2026-07-25',
            'paid_at' => '2026-06-26 10:00:00',
        ]);

        $this->artisan('subscriptions:maintain')->assertSuccessful();

        $this->assertSame(UserRole::User, $user->fresh()->role);
        $this->assertSame('expired', $subscription->fresh()->status);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $user->id,
            'title' => 'Your plan has expired',
        ]);
    }

    public function test_expired_trainer_plan_revokes_client_access(): void
    {
        Carbon::setTestNow('2026-07-26 10:00:00');
        $trainer = User::factory()->create();
        $trainer->forceFill(['role' => UserRole::Trainer])->save();
        $client = User::factory()->create();

        Subscription::query()->create([
            'user_id' => $trainer->id,
            'plan' => 'trainer',
            'status' => 'active',
            'is_complimentary' => false,
            'amount_paid' => 14.99,
            'currency' => 'EUR',
            'starts_on' => '2026-06-26',
            'ends_on' => '2026-07-25',
            'paid_at' => '2026-06-26 10:00:00',
        ]);

        $relationship = TrainerClient::query()->create([
            'trainer_id' => $trainer->id,
            'client_id' => $client->id,
            'status' => TrainerClient::STATUS_ACCEPTED,
            'accepted_at' => now()->subWeek(),
        ]);

        $this->artisan('subscriptions:maintain')->assertSuccessful();

        $this->assertSame(UserRole::User, $trainer->fresh()->role);
        $this->assertSame(TrainerClient::STATUS_REVOKED, $relationship->fresh()->status);
        $this->assertNotNull($relationship->fresh()->revoked_at);
    }

    public function test_active_complimentary_access_and_direct_owner_grants_are_preserved(): void
    {
        Carbon::setTestNow('2026-07-26 10:00:00');
        $complimentary = User::factory()->create();
        $complimentary->forceFill(['role' => UserRole::Paid])->save();
        $directGrant = User::factory()->create();
        $directGrant->forceFill(['role' => UserRole::Paid])->save();

        Subscription::query()->create([
            'user_id' => $complimentary->id,
            'plan' => 'paid',
            'status' => 'active',
            'is_complimentary' => true,
            'amount_paid' => 0,
            'currency' => 'EUR',
            'starts_on' => '2026-07-01',
            'ends_on' => null,
            'paid_at' => null,
        ]);

        $this->artisan('subscriptions:maintain')->assertSuccessful();

        $this->assertSame(UserRole::Paid, $complimentary->fresh()->role);
        $this->assertSame(UserRole::Paid, $directGrant->fresh()->role);
    }
}
