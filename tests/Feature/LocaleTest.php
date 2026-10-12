<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\Journey;
use App\Models\JourneyStep;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The language contract: the URL prefix wins, the visitor's locale cookie is
 * second, English is the default. Every route lives under /{locale}.
 */
class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_root_redirects_to_english(): void
    {
        $this->get('/')->assertRedirect('/en');
    }

    public function test_root_redirect_follows_the_locale_cookie(): void
    {
        $this->withCookie('locale', 'fr')->get('/')->assertRedirect('/fr');
        $this->withCookie('locale', 'ar')->get('/')->assertRedirect('/ar');
    }

    public function test_root_redirect_ignores_a_bogus_cookie(): void
    {
        $this->withCookie('locale', 'de')->get('/')->assertRedirect('/en');
    }

    public function test_unknown_locale_prefix_is_a_404(): void
    {
        $this->get('/de/products')->assertNotFound();
        $this->get('/login')->assertNotFound();
    }

    public function test_french_page_renders_french_copy(): void
    {
        $html = $this->get('/fr')->assertOk()->getContent();

        $this->assertStringContainsString('lang="fr"', $html);
        $this->assertStringContainsString('Accueil', $html);
        $this->assertStringNotContainsString('>Home<', $html);
    }

    public function test_arabic_page_is_right_to_left(): void
    {
        $html = $this->get('/ar')->assertOk()->getContent();

        $this->assertStringContainsString('lang="ar"', $html);
        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('الرئيسية', $html);
    }

    public function test_english_page_stays_left_to_right(): void
    {
        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertStringContainsString('lang="en"', $html);
        $this->assertStringContainsString('dir="ltr"', $html);
    }

    public function test_prefixed_page_restamps_the_locale_cookie(): void
    {
        $response = $this->get('/fr/products');

        $response->assertOk();
        $this->assertSame('fr', $response->getCookie('locale')->getValue());
    }

    public function test_url_wins_over_the_cookie(): void
    {
        // The address bar is the single source of truth: a French cookie does
        // not move an English URL, and the response re-stamps the cookie.
        $response = $this->withCookie('locale', 'fr')->get('/en/products');

        $response->assertOk()->assertSee('Find a product', false);
        $this->assertSame('en', $response->getCookie('locale')->getValue());
    }

    public function test_guest_cannot_reach_unprefixed_auth_pages(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/login', [])->assertNotFound();
    }

    public function test_french_product_page_renders_french_copy(): void
    {
        $food = Food::factory()->create();
        $consumer = User::factory()->create(['role' => 'consumer']);

        $html = $this->actingAs($consumer)
            ->get(route('products.show', ['locale' => 'fr', 'food' => $food]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Envoyer le signalement', $html);
        $this->assertStringContainsString('Avis des consommateurs', $html);
        $this->assertStringContainsString('lang="fr"', $html);
    }

    public function test_arabic_catalog_renders_translated_chrome(): void
    {
        $html = $this->get('/ar/products')->assertOk()->getContent();

        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('ابحث في الكتالوج', $html);
    }

    public function test_french_shipments_index_renders_french_copy(): void
    {
        $distributor = User::factory()->create(['role' => 'distributor']);

        $html = $this->actingAs($distributor)
            ->get('/fr/logistics/shipments')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Empreinte totale', $html);
        $this->assertStringContainsString('Tous les statuts', $html);
        $this->assertStringContainsString('lang="fr"', $html);
    }

    public function test_arabic_workflow_tracker_renders_arabic_copy(): void
    {
        $processor = User::factory()->create(['role' => 'processor']);
        $journey = Journey::factory()->create();
        JourneyStep::factory()->create([
            'journey_id' => $journey->id,
            'step_order' => 1,
            'type' => 'origin',
        ]);

        $html = $this->actingAs($processor)
            ->get('/ar/processor/journeys/'.$journey->id.'/steps')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('1 من 4 مراحل مسجلة', $html);
        $this->assertStringContainsString('تسجيل النقل — الخطوة 2', $html);
    }

    public function test_french_product_page_carries_translated_chart_and_assistant_strings(): void
    {
        $food = Food::factory()->create();

        $html = $this->get('/fr/products/'.$food->id)->assertOk()->getContent();

        $this->assertStringContainsString('Jours dans la phase', $html);
        // @js() unicode-escapes the string, so assert the ASCII-only tail.
        $this->assertStringContainsString('questions sur ce produit', $html);
        $this->assertStringContainsString('lang="fr"', $html);
    }

    public function test_french_dashboard_carries_translated_chart_strings(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);

        $html = $this->actingAs($consumer)
            ->get('/fr/consumer/dashboard')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('"grade":"Note"', $html);
    }

    public function test_french_meals_index_renders_translated_meal_type(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);
        Meal::factory()->create(['user_id' => $consumer->id, 'type' => 'breakfast', 'name' => 'Petit test']);

        $html = $this->actingAs($consumer)
            ->get('/fr/meals')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Petit-déjeuner', $html);
    }

    public function test_french_validation_errors_use_translated_field_names(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);

        $this->actingAs($consumer)->post('/fr/meals', [])->assertSessionHasErrors('name');

        $this->assertStringContainsString('nom', (string) session('errors')->first('name'));
    }

    public function test_language_switcher_keeps_the_current_page(): void
    {
        $html = $this->get('/en/products')->assertOk()->getContent();

        $this->assertStringContainsString("switchTo('en')", $html);
        $this->assertStringNotContainsString("window.location='/en'", $html);
    }

    public function test_french_sidebar_renders_translated_role(): void
    {
        $producer = User::factory()->create(['role' => 'producer']);

        $html = $this->actingAs($producer)
            ->get('/fr/farms')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Producteur', $html);
    }

    public function test_french_confirm_password_renders_french_copy(): void
    {
        $user = User::factory()->create(['role' => 'consumer']);

        $html = $this->actingAs($user)
            ->get('/fr/confirm-password')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('C&#039;est une zone s', $html);
        $this->assertStringContainsString('lang="fr"', $html);
    }
}
