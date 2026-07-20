<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirectToRoute('login');
    }

    public function test_login_page_has_persistent_login_without_reload_script(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSee('name="remember" value="1"', false)
            ->assertDontSee('sessionStorage', false)
            ->assertDontSee('ngrokSkip', false)
            ->assertSee('<meta name="description"', false)
            ->assertSee('<meta property="og:image"', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('/images/branding/progresslab-og.png', false)
            ->assertSee('/images/branding/progresslab-favicon.png', false)
            ->assertSee('/images/branding/progresslab-touch-icon.png', false);
    }
}
