<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserRolesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role', 20)->default(UserRole::User->value);
            $table->rememberToken();
            $table->timestamps();
        });

        Route::middleware('role:admin,owner')
            ->get('/_test/admin-role', fn () => response('allowed'));

        Route::middleware('role:owner')
            ->get('/_test/owner-role', fn () => response('owner allowed'));
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_new_accounts_default_to_user_and_cannot_mass_assign_owner(): void
    {
        $user = User::query()->create([
            'name' => 'New User',
            'email' => 'new-user@example.test',
            'password' => 'password',
            'role' => UserRole::Owner->value,
        ]);

        $user = $user->fresh();

        $this->assertSame(UserRole::User, $user->role);
        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isOwner());
    }

    public function test_role_helpers_recognize_paid_trainer_admin_and_owner(): void
    {
        $user = $this->user();

        $user->forceFill(['role' => UserRole::Trainer])->save();
        $this->assertTrue($user->fresh()->isTrainer());

        $user->forceFill(['role' => UserRole::Paid])->save();
        $this->assertTrue($user->fresh()->isPaid());

        $user->forceFill(['role' => UserRole::Admin])->save();
        $this->assertTrue($user->fresh()->isAdmin());

        $user->forceFill(['role' => UserRole::Owner])->save();
        $owner = $user->fresh();
        $this->assertTrue($owner->isAdmin());
        $this->assertTrue($owner->isOwner());
    }

    public function test_role_middleware_allows_configured_roles_and_blocks_others(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->get('/_test/admin-role')
            ->assertForbidden();

        $user->forceFill(['role' => UserRole::Admin])->save();
        $this->actingAs($user->fresh())
            ->get('/_test/admin-role')
            ->assertOk();
        $this->actingAs($user->fresh())
            ->get('/_test/owner-role')
            ->assertForbidden();

        $user->forceFill(['role' => UserRole::Owner])->save();
        $this->actingAs($user->fresh())
            ->get('/_test/admin-role')
            ->assertOk();
        $this->actingAs($user->fresh())
            ->get('/_test/owner-role')
            ->assertOk();
    }

    private function user(): User
    {
        return User::query()->create([
            'name' => 'Role Tester',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
        ]);
    }
}
