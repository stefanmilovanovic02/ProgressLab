<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_sees_locked_report_and_cannot_download_pdf(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('charts.index'))
            ->assertOk()
            ->assertSeeInOrder(['Progress Photo Comparison', 'Weekly Report'])
            ->assertSee('Weekly summaries and downloadable PDF reports are available')
            ->assertSee('View plans');

        $this->actingAs($user)
            ->get(route('charts.weekly-report.download'))
            ->assertForbidden();
    }

    public function test_paid_user_can_download_weekly_report_pdf(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => UserRole::Paid])->save();

        $this->actingAs($user)
            ->get(route('charts.index'))
            ->assertOk()
            ->assertSee('Download weekly PDF')
            ->assertDontSee('Weekly summaries and downloadable PDF reports are available');

        $response = $this->actingAs($user)
            ->get(route('charts.weekly-report.download'));

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control')
        );
        $this->assertStringContainsString(
            'progresslab-weekly-report-',
            (string) $response->headers->get('Content-Disposition')
        );
    }
}
