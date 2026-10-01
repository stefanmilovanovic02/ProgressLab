<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\TrackDailyLogin;
use App\Models\FriendRequest;
use App\Models\NutritionGoal;
use App\Models\User;
use App\Models\UserMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FriendsDiscoveryAndProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TrackDailyLogin::class);
    }

    public function test_sender_can_revoke_only_their_own_pending_request(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $other = User::factory()->create();
        $request = FriendRequest::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => 'pending',
        ]);

        $this->actingAs($other)->deleteJson(route('friends.requests.cancel', $request))->assertForbidden();
        $this->actingAs($sender)->deleteJson(route('friends.requests.cancel', $request))->assertOk();
        $this->assertDatabaseMissing('friend_requests', ['id' => $request->id]);
    }

    public function test_suggestions_prioritize_same_location_then_active_users(): void
    {
        $viewer = User::factory()->create(['location' => 'Belgrade, Serbia']);
        $nearby = User::factory()->create(['full_name' => 'Nearby Member', 'location' => 'belgrade, serbia']);
        $active = User::factory()->create(['full_name' => 'Active Member', 'location' => 'Novi Sad, Serbia']);
        DB::table('login_logs')->insert([
            'user_id' => $active->id,
            'login_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($viewer)->get(route('friends.index'))
            ->assertOk()
            ->assertSeeInOrder(['Nearby Member', 'Active Member'])
            ->assertSee('Suggested people')
            ->assertSee('data-profile-id="'.$nearby->id.'"', false);
    }

    public function test_non_friend_can_view_public_profile_but_cannot_compare_strength(): void
    {
        $viewer = User::factory()->create();
        $member = User::factory()->create([
            'profile_quote' => 'Consistency creates progress.',
            'social_instagram' => 'https://instagram.com/progresslab',
            'profile_accent_color' => '#8b5cf6',
        ]);

        $this->actingAs($viewer)->getJson(route('friends.summary', $member))
            ->assertOk()
            ->assertJsonPath('user.quote', 'Consistency creates progress.')
            ->assertJsonPath('user.theme.accent', '#8b5cf6')
            ->assertJsonPath('relationship.is_friend', false)
            ->assertJsonPath('relationship.can_compare', false)
            ->assertJsonPath('user.email', '');

        $this->actingAs($viewer)->getJson(route('friends.comparison-exercises', $member))->assertForbidden();
    }

    public function test_social_profile_customization_requires_a_plus_eligible_role(): void
    {
        $free = User::factory()->create(['username' => 'free_profile_user']);
        $paid = User::factory()->create([
            'username' => 'paid_profile_user',
            'role' => UserRole::Paid,
        ]);
        UserMetric::create([
            'user_id' => $paid->id,
            'gender' => 'male',
            'age' => 30,
            'height_cm' => 180,
            'weight_kg' => 80,
            'activity_multiplier' => 1.55,
            'bmr' => 1755,
            'tdee' => 2720,
        ]);
        NutritionGoal::create([
            'user_id' => $paid->id,
            'goal' => 'recomp',
            'calorie_target' => 2500,
            'protein_g' => 160,
            'fat_g' => 75,
            'carbs_g' => 295,
        ]);

        $this->actingAs($free)->put(route('profile.update'), $this->profilePayload($free) + [
            'profile_quote' => 'Not allowed',
        ])->assertForbidden();

        $this->actingAs($paid)->put(route('profile.update'), $this->profilePayload($paid) + [
            'profile_quote' => 'Earn the next version of yourself.',
            'social_linkedin' => 'https://www.linkedin.com/in/progresslab',
            'profile_accent_color' => '#8b5cf6',
            'profile_accent_opacity' => 85,
            'profile_secondary_color' => '#22d3ee',
            'profile_secondary_opacity' => 75,
            'profile_surface_color' => '#111827',
            'profile_surface_opacity' => 90,
            'profile_text_color' => '#f8fafc',
            'profile_text_opacity' => 100,
        ])->assertRedirect(route('profile.show'));

        $this->assertDatabaseHas('users', [
            'id' => $paid->id,
            'profile_quote' => 'Earn the next version of yourself.',
            'social_linkedin' => 'https://www.linkedin.com/in/progresslab',
            'profile_accent_color' => '#8b5cf6',
            'profile_accent_opacity' => 85,
            'profile_secondary_color' => '#22d3ee',
            'profile_secondary_opacity' => 75,
            'profile_surface_color' => '#111827',
            'profile_surface_opacity' => 90,
            'profile_text_color' => '#f8fafc',
            'profile_text_opacity' => 100,
        ]);

        $themePayload = [
            'profile_accent_color' => '#f97316',
            'profile_accent_opacity' => 80,
            'profile_secondary_color' => '#facc15',
            'profile_secondary_opacity' => 70,
            'profile_surface_color' => '#172033',
            'profile_surface_opacity' => 88,
            'profile_text_color' => '#ffffff',
            'profile_text_opacity' => 95,
        ];
        $this->actingAs($free)->patchJson(route('profile.theme.update'), $themePayload)->assertForbidden();
        $this->actingAs($paid)->patchJson(route('profile.theme.update'), $themePayload)
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->actingAs($paid)->get(route('profile.show'))
            ->assertOk()
            ->assertSee(route('friends.index', [
                'open_profile' => $paid->id,
                'return_to' => 'profile',
            ]))
            ->assertSee('Preview Public Profile');
    }

    public function test_plus_user_can_upload_a_background_video(): void
    {
        Storage::fake('public');
        $paid = User::factory()->create(['role' => UserRole::Paid]);

        $response = $this->actingAs($paid)
            ->withHeader('Accept', 'application/json')
            ->put(route('profile.cover.update'), [
                'cover' => UploadedFile::fake()->create('profile-background.mp4', 512, 'video/mp4'),
            ]);

        $response->assertOk()->assertJsonPath('ok', true);
        $paid->refresh();
        $this->assertNotNull($paid->profile_background_video_path);
        Storage::disk('public')->assertExists(str_replace('storage/', '', $paid->profile_background_video_path));
    }

    private function profilePayload(User $user): array
    {
        return [
            'full_name' => $user->full_name,
            'username' => $user->username,
            'email' => $user->email,
            'unit_system' => 'metric',
        ];
    }
}
