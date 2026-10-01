<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_privacy_policy_is_public_and_describes_sensitive_features(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('Progress-photo privacy')
            ->assertSee('Admins cannot access progress photos')
            ->assertSee('restricted service-operator account')
            ->assertSee('Friendship alone never grants access')
            ->assertSee('progresslabsupport@gmail.com')
            ->assertSee('<meta name="description"', false);
    }

    public function test_terms_are_public_and_include_health_subscription_and_trainer_rules(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Terms of Use')
            ->assertSee('ProgressLab is not medical care')
            ->assertSee('one-time payments for 30 days')
            ->assertSee('Trainer access requires an accepted Trainer invitation')
            ->assertSee('laws of the Republic of Serbia');
    }

    public function test_registration_links_to_both_legal_pages(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee(route('legal.privacy'), false)
            ->assertSee(route('legal.terms'), false)
            ->assertSee('class="stepper-item is-current"', false)
            ->assertSee('aria-controls="password_confirmation"', false);
    }

    public function test_tdee_step_keeps_the_full_progresslab_activity_list_without_an_early_result(): void
    {
        $this->withSession(['register.step1' => [
            'full_name' => 'Test User',
            'username' => 'test_user',
            'email' => 'test@example.test',
            'password_hash' => 'hash',
        ]])->get(route('register.macros'))
            ->assertOk()
            ->assertSee('Mifflin–St Jeor')
            ->assertSee('value="1.2"', false)
            ->assertSee('value="1.5"', false)
            ->assertSee('value="1.65"', false)
            ->assertSee('value="1.7"', false)
            ->assertSee('value="1.8"', false)
            ->assertSee('value="2.0"', false)
            ->assertSee('value="2.2"', false)
            ->assertDontSee('Estimated maintenance');
    }

    public function test_goal_step_shows_maintenance_and_matching_live_macro_preview(): void
    {
        $this->withSession([
            'register.step1' => ['full_name' => 'Test User'],
            'register.step2' => ['weight_kg' => 80],
            'register.tdee' => 2759,
        ])->get(route('register.goal'))
            ->assertOk()
            ->assertSee('2759 kcal')
            ->assertSee('data-weight="80"', false)
            ->assertSee('data-macro-preview', false)
            ->assertSee("maintenance * 1.08", false)
            ->assertSee("maintenance * .85", false);
    }

    public function test_tdee_submission_uses_the_mifflin_st_jeor_result(): void
    {
        $response = $this->withSession(['register.step1' => [
            'full_name' => 'Test User',
            'username' => 'test_user',
            'email' => 'test@example.test',
            'password_hash' => 'hash',
        ]])->post(route('register.store.macros'), [
            'gender' => 'male',
            'age' => 30,
            'height' => 180,
            'weight' => 80,
            'activity' => '1.5',
        ]);

        $response
            ->assertRedirectToRoute('register.goal')
            ->assertSessionHas('register.bmr', 1780.0)
            ->assertSessionHas('register.tdee', 2670);
    }

    public function test_public_seo_discovery_files_are_valid(): void
    {
        $this->get(route('seo.robots'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Sitemap: ' . url('/sitemap.xml'), false)
            ->assertSee('Disallow: /profile', false);

        $this->get(route('seo.sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('login'), false)
            ->assertSee(route('register'), false)
            ->assertSee(route('legal.privacy'), false)
            ->assertSee(route('legal.terms'), false);
    }

    public function test_public_page_has_clean_structured_data(): void
    {
        $response = $this->get(route('login'))->assertOk();

        $response
            ->assertSee('"@context":"https://schema.org"', false)
            ->assertSee('"@type":"WebApplication"', false)
            ->assertDontSee('<?php $__contextArgs', false);
    }
}
