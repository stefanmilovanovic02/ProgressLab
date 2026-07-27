<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeRecentActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_recent_activity_includes_system_notifications(): void
    {
        $user = User::factory()->create();

        AppNotification::query()->create([
            'user_id' => $user->id,
            'source_type' => 'system',
            'source_id' => 987654,
            'category' => 'system',
            'title' => 'Plan reminder',
            'message' => 'Your ProgressLab plan expires soon.',
            'icon' => '✨',
            'action_url' => '/plans',
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Recent Activity')
            ->assertSee('Your ProgressLab plan expires soon.')
            ->assertSee('View All Notifications');
    }
}
