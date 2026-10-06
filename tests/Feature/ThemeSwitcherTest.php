<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ThemeSwitcherTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function publicPageProvider(): array
    {
        return [
            'home' => ['/'],
            'login' => ['/login'],
            'register' => ['/register'],
            'catalog' => ['/products'],
            'greenwashing guide' => ['/greenwashing'],
        ];
    }

    #[DataProvider('publicPageProvider')]
    public function test_every_public_page_inlines_the_theme_guard(string $url): void
    {
        $html = $this->get($url)->assertOk()->getContent();

        // The guard has to be inline and synchronous to beat the first paint.
        $this->assertStringContainsString("classList.toggle('dark'", $html);
        $this->assertStringContainsString('nutritrace-theme', $html);
        $this->assertStringContainsString('prefers-color-scheme: dark', $html);
    }

    #[DataProvider('publicPageProvider')]
    public function test_the_theme_guard_runs_before_the_stylesheet_is_requested(string $url): void
    {
        $html = $this->get($url)->assertOk()->getContent();

        $guard = strpos($html, "classList.toggle('dark'");
        // The favicon is also a <link>, so match the stylesheet specifically.
        $styles = strpos($html, 'rel="stylesheet"');

        $this->assertIsInt($guard);
        $this->assertIsInt($styles, 'No stylesheet link found on the page.');
        $this->assertLessThan($styles, $guard, 'The theme guard runs after the stylesheet, which causes a flash.');
    }

    #[DataProvider('publicPageProvider')]
    public function test_the_theme_guard_declares_a_theme_color_for_mobile_browsers(string $url): void
    {
        $this->get($url)
            ->assertOk()
            ->assertSee('data-theme-color', false);
    }

    #[DataProvider('publicPageProvider')]
    public function test_the_theme_toggle_is_available_on_public_pages(string $url): void
    {
        $html = $this->get($url)->assertOk()->getContent();

        $this->assertStringContainsString('Change colour theme', $html);
        $this->assertStringContainsString('NutriTraceTheme.set', $html);
    }

    public function test_the_dropdown_is_anchored_in_the_header_not_teleported_to_the_footer(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // April positions its panel with x-anchor, which needs the Alpine
        // Floating UI plugin. That plugin is not bundled, so teleporting the
        // panel to <body> would drop it at the end of the document, behind the
        // footer. It is anchored in CSS instead.
        $this->assertStringNotContainsString('x-teleport', $html);

        $header = strpos($html, '<header');
        $panel = strpos($html, 'data-slot="dropdown-menu-content"');

        $this->assertIsInt($header);
        $this->assertIsInt($panel, 'Dropdown panel was not rendered.');
        $this->assertGreaterThan($header, $panel, 'The dropdown panel is not inside the header.');
    }

    public function test_the_dropdown_panel_is_positioned_by_css(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match('/<div[^>]*data-slot="dropdown-menu-content"[^>]*>/', $html, $match);

        $this->assertNotEmpty($match);

        foreach (['absolute', 'right-0', 'top-full'] as $positioning) {
            $this->assertStringContainsString($positioning, $match[0]);
        }

        // A relative wrapper provides the containing block.
        $this->assertStringContainsString('class="relative"', $html);
    }

    public function test_the_dropdown_root_never_declares_two_x_data_attributes(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match('/<div[^>]*data-slot="dropdown-menu"[^>]*>/', $html, $match);

        $this->assertNotEmpty($match, 'Dropdown root not found.');

        // A duplicate x-data is silently dropped by the HTML parser, which would
        // leave the toggle's own state undefined and render no icon at all.
        $this->assertSame(
            1,
            preg_match_all('/\bx-data=/', $match[0]),
            'Dropdown root declares x-data more than once.'
        );
    }

    public function test_the_toggle_offers_light_dark_and_system(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        foreach (['light', 'dark', 'system'] as $mode) {
            $this->assertStringContainsString("NutriTraceTheme.set('{$mode}')", $html);
        }
    }

    public function test_the_back_office_offers_the_theme_toggle(): void
    {
        $user = User::factory()->create(['role' => 'consumer']);

        $this->actingAs($user)
            ->get(route('consumer.dashboard'))
            ->assertOk()
            ->assertSee('Change colour theme', false);
    }
}
