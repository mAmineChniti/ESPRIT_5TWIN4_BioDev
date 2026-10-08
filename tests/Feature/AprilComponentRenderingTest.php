<?php

namespace Tests\Feature;

use App\Enums\EnvironmentalScore;
use App\Enums\Stage;
use App\Models\Category;
use App\Models\Certification;
use App\Models\Food;
use App\Models\GreenwashingReport;
use App\Models\Meal;
use App\Models\StageTransition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the April UI migrations.
 *
 * April renders slots conditionally (card checks isset($content), data-table
 * checks isset($header)/isset($body), alert-dialog checks isset($content)), so a
 * mis-nested tag silently drops a whole region of the page instead of raising.
 * These assert the migrated markup actually reaches the browser, and that the
 * destructive flows still post what the endpoints validate.
 */
class AprilComponentRenderingTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function food(?User $producer = null): Food
    {
        return Food::factory()->create([
            'producer_id' => ($producer ?? $this->user('producer'))->id,
        ]);
    }

    // ---------- Cards ----------

    public function test_product_pages_render_the_card_regions(): void
    {
        $food = $this->food();
        $food->certifications()->attach(Certification::factory()->create(['name' => 'Organic']));

        $producer = $food->producer;

        foreach ([route('products.show', $food), route('foods.show', $food)] as $url) {
            $html = $this->actingAs($producer)->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('data-slot="card"', $html, "no card on {$url}");
            $this->assertStringContainsString('data-slot="card-content"', $html, "no card-content on {$url}");
            $this->assertStringNotContainsString('undefined', $html, "undefined leaked into {$url}");
            $this->assertStringNotContainsString('class="null"', $html, "null class leaked into {$url}");
        }
    }

    public function test_the_eco_score_badge_renders_with_its_own_text(): void
    {
        $food = Food::factory()->create(['environmental_score' => EnvironmentalScore::A]);
        $html = $this->get(route('products.show', $food))->assertOk()->getContent();

        $this->assertStringContainsString('data-slot="badge"', $html);
        $this->assertStringContainsString('Very low impact', $html);
    }

    // ---------- Data tables ----------

    public function test_migrated_tables_render_header_and_body_slots(): void
    {
        $admin = $this->user('admin');
        $consumer = $this->user('consumer');

        $food = $this->food();
        $food->transitions()->create([
            'actor_id' => $food->producer_id,
            'from_stage' => null,
            'to_stage' => Stage::Produced->value,
            'occurred_at' => now(),
        ]);
        $report = GreenwashingReport::factory()->create(['food_id' => $food->id]);
        $meal = Meal::factory()->create(['user_id' => $consumer->id]);

        $pages = [
            route('foods.index') => 'Products',
            route('admin.reports.index') => 'Reported by',
            route('meals.index') => 'Meal',
        ];

        foreach ($pages as $url => $headerText) {
            $html = $this->actingAs($url === route('admin.reports.index') ? $admin : ($url === route('meals.index') ? $consumer : $admin))
                ->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('data-slot="data-table"', $html, "no data-table on {$url}");
            $this->assertStringContainsString('data-slot="data-table-header"', $html, "no header on {$url}");
            $this->assertStringContainsString('data-slot="data-table-body"', $html, "no body on {$url}");
            $this->assertStringContainsString($headerText, $html);
        }

        $this->assertNotNull($report->id);
        $this->assertNotNull($meal->id);
    }

    public function test_a_table_with_no_rows_still_renders_its_empty_state(): void
    {
        $admin = $this->user('admin');

        $html = $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()->getContent();

        $this->assertStringContainsString('No reports found', $html);
    }

    // ---------- Alert dialogs ----------

    public function test_destructive_tables_render_a_confirmation_dialog_per_row(): void
    {
        $admin = $this->user('admin');
        $food = $this->food();
        GreenwashingReport::factory()->count(2)->create(['food_id' => $food->id]);

        $html = $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()->getContent();

        // Two pending reports, each with its own dialog for both decisions.
        $this->assertSame(4, substr_count($html, 'data-slot="alert-dialog-content"'));
        $this->assertSame(4, substr_count($html, 'data-slot="alert-dialog-footer"'));
        // The forms must sit inside the dialog footer so the action posts them.
        $this->assertStringContainsString('value="upheld"', $html);
        $this->assertStringContainsString('value="dismissed"', $html);
    }

    public function test_a_reviewed_report_offers_no_dialog(): void
    {
        $admin = $this->user('admin');
        GreenwashingReport::factory()->upheld()->create();

        $html = $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('value="upheld"', $html);
        $this->assertStringContainsString('Reviewed', $html);
    }

    public function test_the_alert_dialogs_are_wired_for_assistive_tech(): void
    {
        $admin = $this->user('admin');
        GreenwashingReport::factory()->create();

        $html = $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()->getContent();

        // x-bind="title"/"description" feed aria-labelledby/aria-describedby.
        $this->assertStringContainsString('x-bind="title"', $html);
        $this->assertStringContainsString('x-bind="description"', $html);
    }

    // ---------- The account deletion dialog posts the real form ----------

    public function test_the_delete_account_dialog_posts_the_form_holding_the_password(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)->get(route('profile.edit'))->assertOk()->getContent();

        // A separate hidden form would drop the password and the endpoint would
        // always reject the submission.
        $this->assertStringNotContainsString('delete-account-form', $html);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('data-slot="alert-dialog"', $html);
    }

    public function test_an_account_can_actually_be_deleted(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-password')]);

        $response = $this->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), ['password' => 'secret-password']);

        $response->assertRedirect('/');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_account_deletion_still_requires_the_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-password')]);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), ['password' => 'wrong-password'])
            ->assertSessionHasErrorsIn('userDeletion', 'password');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    // ---------- Steps ----------

    public function test_the_trace_page_renders_a_step_per_stage_with_its_actor(): void
    {
        $producer = $this->user('producer');
        $food = $this->food($producer);

        $food->transitions()->create([
            'actor_id' => $producer->id,
            'from_stage' => null,
            'to_stage' => Stage::Produced->value,
            'occurred_at' => now(),
        ]);

        StageTransition::create([
            'food_id' => $food->id,
            'actor_id' => $this->user('processor')->id,
            'from_stage' => Stage::Produced->value,
            'to_stage' => Stage::Processed->value,
            'occurred_at' => now()->addDay(),
        ]);

        $html = $this->actingAs($producer)->get(route('foods.transitions.index', $food))->assertOk()->getContent();

        $this->assertStringContainsString('data-slot="steps"', $html);
        $this->assertStringContainsString('data-orientation="vertical"', $html);
        $this->assertStringContainsString('data-state="completed"', $html);
        // The third stage has not happened yet.
        $this->assertStringContainsString('data-state="inactive"', $html);
        $this->assertStringContainsString(Stage::Distributed->label(), $html);
    }

    public function test_the_trace_page_with_no_steps_does_not_render_a_stepper(): void
    {
        $producer = $this->user('producer');
        $food = $this->food($producer);

        $html = $this->actingAs($producer)->get(route('foods.transitions.index', $food))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-slot="steps"', $html);
        $this->assertStringContainsString('No supply chain steps recorded yet', $html);
    }

    public function test_the_trace_page_explains_why_a_stage_is_not_offered(): void
    {
        $producer = $this->user('producer');
        $food = $this->food($producer);
        $food->transitions()->create([
            'actor_id' => $producer->id,
            'from_stage' => null,
            'to_stage' => Stage::Produced->value,
            'occurred_at' => now(),
        ]);

        $html = $this->actingAs($producer)->get(route('foods.transitions.index', $food))->assertOk()->getContent();

        // The producer may not sign for Processed, so no form is offered.
        $this->assertStringNotContainsString('name="to_stage"', $html);
        $this->assertStringContainsString('processor', $html);
    }

    // ---------- Form controls ----------

    public function test_native_selects_post_the_selected_value(): void
    {
        $admin = $this->user('admin');
        $user = $this->user('consumer');

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'processor',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('processor', $user->fresh()->role);
    }

    public function test_a_product_can_be_created_through_the_shared_form_component(): void
    {
        $producer = $this->user('producer');
        $food = Food::factory()->create(['category_id' => Category::factory()->create()->id]);
        $category = Category::first();

        $this->actingAs($producer)
            ->post(route('foods.store'), [
                'name' => 'Component Apple',
                'category_id' => $category->id,
                'origin' => 'France',
                'calories' => 52,
                'protein' => 0.3,
                'carbs' => 13.8,
                'fat' => 0.2,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('foods', ['name' => 'Component Apple']);
        $this->assertNotNull($food->id);
    }

    public function test_the_edit_form_prefills_from_the_model(): void
    {
        $producer = $this->user('producer');
        $food = $this->food($producer);
        $certification = Certification::factory()->create(['name' => 'Organic']);
        $food->certifications()->attach($certification);

        $html = $this->actingAs($producer)->get(route('foods.edit', $food))->assertOk()->getContent();

        $this->assertStringContainsString('id="name" name="name" value="'.$food->name.'"', $html);
        $this->assertStringContainsString('id="origin" name="origin" value="'.$food->origin.'"', $html);
        $this->assertStringContainsString('value="'.$food->calories.'"', $html);

        // Only the attached certification is ticked.
        $checked = preg_match_all('/name="certifications\[\]"[^>]*value="(\d+)"/', $html, $m)
            ? substr_count($html, 'name="certifications[]"')
            : 0;
        $this->assertGreaterThan(0, $checked);

        preg_match_all('/<input(?=[^>]*name="certifications\[\]")(?=[^>]*checked)[^>]*value="(\d+)"/', $html, $checkedOnes);
        $this->assertSame([(string) $certification->id], $checkedOnes[1]);
    }

    public function test_the_csv_import_control_is_reachable_by_keyboard(): void
    {
        $producer = $this->user('producer');

        $html = $this->actingAs($producer)->get(route('foods.index'))->assertOk()->getContent();

        // A hidden input was out of the tab order entirely, making the upload
        // mouse-only. The control is now visible, labelled and explicitly
        // submitted rather than fired by merely choosing a file.
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
        $this->assertStringContainsString('for="csv_file"', $html);
        $this->assertStringContainsString('id="csv_file"', $html);
        $this->assertStringNotContainsString('onchange="this.form.submit()"', $html);

        preg_match('/<input(?=[^>]*id="csv_file")[^>]*>/', $html, $m);
        $this->assertNotEmpty($m);
        $this->assertStringNotContainsString('hidden', $m[0]);
        $this->assertStringNotContainsString('sr-only', $m[0]);
        $this->assertStringContainsString('w-auto', $m[0]);
    }

    // ---------- Tooltip ----------

    public function test_the_disabled_delete_action_explains_itself(): void
    {
        $admin = $this->user('admin');

        $html = $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-slot="tooltip"', $html);
        $this->assertStringContainsString('You cannot delete your own account', $html);
        $this->assertStringContainsString('aria-disabled="true"', $html);
    }

    // ---------- Theme toggle ----------

    public function test_the_theme_toggle_is_an_april_button_with_a_label(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="Change colour theme"', $html);
        $this->assertStringContainsString('data-slot="button"', $html);
    }

    // ---------- Session status ----------

    public function test_the_session_status_uses_an_announced_region(): void
    {
        // April's alert carries role="alert"; the previous hand-rolled div was
        // never announced, so post-login confirmations were silent for screen
        // reader users.
        $html = $this->withSession(['status' => 'Password reset successfully.'])
            ->get(route('login'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-slot="alert"', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('Password reset successfully.', $html);
    }
}
