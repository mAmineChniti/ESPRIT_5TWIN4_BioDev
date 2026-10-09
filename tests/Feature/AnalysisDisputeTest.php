<?php

namespace Tests\Feature;

use App\Enums\AnalysisDisputeReason;
use App\Enums\AnalysisDisputeStatus;
use App\Enums\FindingCategory;
use App\Models\AnalysisDispute;
use App\Models\Food;
use App\Models\GreenwashingReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Reporting that the AI detector got a product wrong.
 *
 * This is deliberately separate from a greenwashing report: that claims a
 * product is misleading and changes its trust score, while this claims
 * NutriTrace's own analysis is wrong and must not. The tests below hold both
 * halves of that apart.
 */
class AnalysisDisputeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function roleProvider(): array
    {
        return [
            'admin' => ['admin'],
            'producer' => ['producer'],
            'processor' => ['processor'],
            'distributor' => ['distributor'],
            'consumer' => ['consumer'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonAdminProvider(): array
    {
        return [
            'producer' => ['producer'],
            'processor' => ['processor'],
            'distributor' => ['distributor'],
            'consumer' => ['consumer'],
        ];
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /**
     * @param  list<array<string, string>>  $findings
     */
    private function fakeDetector(array $findings): void
    {
        // The client refuses to call without a key before any HTTP happens,
        // so the key is stubbed too: without it these tests only pass when
        // the developer's own .env happens to contain a real one, and CI —
        // correctly keyless — sees an "unavailable" analysis instead.
        config(['services.ai.key' => 'test-key']);
        // Any missing fake must fail loudly rather than reach a paid provider.
        Http::preventStrayRequests();
        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode([
                        'findings' => $findings,
                        'summary' => 'An olive oil from Tunisia.',
                        'risk_score' => 45,
                        'verdict' => 'Claims not fully supported',
                    ])],
                ]],
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validDispute(array $overrides = []): array
    {
        return [
            'reason' => AnalysisDisputeReason::FalsePositive->value,
            'comment' => 'The organic certificate is on file, so this claim is evidenced after all.',
            ...$overrides,
        ];
    }

    private function finding(): array
    {
        return [[
            'category' => FindingCategory::UnverifiableClaim->value,
            'severity' => 'medium',
            'title' => 'Organic claim is not evidenced',
            'detail' => 'No certificate is on file for the organic claim.',
            'evidence' => 'origin: Tunisia',
        ]];
    }

    // ---------- Filing a report ----------

    #[DataProvider('roleProvider')]
    public function test_every_role_is_offered_the_control_and_can_file_one(string $role): void
    {
        $this->fakeDetector($this->finding());
        $food = Food::factory()->create();
        $user = $this->user($role);

        $html = $this->actingAs($user)->get(route('products.show', $food))->assertOk()->getContent();

        $this->assertStringContainsString('Report a problem with this analysis', $html);
        $this->assertStringContainsString(route('products.analysis-disputes.store', $food), $html);

        $this->actingAs($user)
            ->from(route('products.show', $food))
            ->post(route('products.analysis-disputes.store', $food), $this->validDispute([
                'finding_category' => FindingCategory::UnverifiableClaim->value,
            ]))
            ->assertRedirect(route('products.show', $food))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('analysis_disputes', [
            'food_id' => $food->id,
            'user_id' => $user->id,
            'reason' => AnalysisDisputeReason::FalsePositive->value,
            'finding_category' => FindingCategory::UnverifiableClaim->value,
            'status' => AnalysisDisputeStatus::Pending->value,
        ]);
    }

    public function test_a_guest_is_offered_a_sign_in_link_instead_of_the_form(): void
    {
        $this->fakeDetector($this->finding());
        $food = Food::factory()->create();

        $html = $this->get(route('products.show', $food))->assertOk()->getContent();

        $this->assertStringContainsString('to tell us the analysis got this product wrong', $html);
        $this->assertStringNotContainsString(route('products.analysis-disputes.store', $food), $html);

        $this->post(route('products.analysis-disputes.store', $food), $this->validDispute())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('analysis_disputes', 0);
    }

    public function test_no_control_is_offered_when_the_detector_failed(): void
    {
        // No fake configured, so the detector raises and reports "unavailable".
        // There is no verdict to dispute in that case.
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response('nope', 500)]);

        $food = Food::factory()->create();
        $html = $this->actingAs($this->user('consumer'))
            ->get(route('products.show', $food))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Analysis unavailable', $html);
        $this->assertStringNotContainsString('Report a problem with this analysis', $html);
    }

    public function test_a_dispute_needs_a_reason_and_an_explanatory_comment(): void
    {
        $food = Food::factory()->create();
        $user = $this->user('consumer');

        $this->actingAs($user)
            ->post(route('products.analysis-disputes.store', $food), ['comment' => 'No reason given.'])
            ->assertSessionHasErrors('reason');

        $this->actingAs($user)
            ->post(route('products.analysis-disputes.store', $food), [
                'reason' => AnalysisDisputeReason::WrongVerdict->value,
                'comment' => 'too short',
            ])
            ->assertSessionHasErrors('comment');

        $this->actingAs($user)
            ->post(route('products.analysis-disputes.store', $food), $this->validDispute([
                'reason' => 'because-i-said-so',
            ]))
            ->assertSessionHasErrors('reason');

        $this->actingAs($user)
            ->post(route('products.analysis-disputes.store', $food), $this->validDispute([
                'finding_category' => 'not-a-category',
            ]))
            ->assertSessionHasErrors('finding_category');

        $this->assertDatabaseCount('analysis_disputes', 0);
    }

    public function test_one_open_report_per_account_per_product(): void
    {
        $food = Food::factory()->create();
        $user = $this->user('consumer');

        $this->actingAs($user)
            ->from(route('products.show', $food))
            ->post(route('products.analysis-disputes.store', $food), $this->validDispute())
            ->assertSessionHasNoErrors();

        // The second is refused while the first is open, so the queue an admin
        // reads cannot be flooded by one account.
        $this->actingAs($user)
            ->from(route('products.show', $food))
            ->post(route('products.analysis-disputes.store', $food), $this->validDispute([
                'comment' => 'A second attempt that is long enough to pass validation.',
            ]))
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('analysis_disputes', 1);

        // A second account may still file one, so the queue is not blocked by
        // the first reporter.
        $this->actingAs($this->user('consumer'))
            ->post(route('products.analysis-disputes.store', $food), $this->validDispute())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('analysis_disputes', 2);
    }

    // ---------- The admin queue ----------

    public function test_an_admin_sees_the_report_with_its_comment_and_reporter(): void
    {
        $reporter = $this->user('producer');
        $food = Food::factory()->create(['name' => 'Zitoun Beldi']);
        AnalysisDispute::factory()->create([
            'food_id' => $food->id,
            'user_id' => $reporter->id,
            'reason' => AnalysisDisputeReason::MissedIssue,
            'comment' => 'The expiry date on the label contradicts the record.',
        ]);

        $html = $this->actingAs($this->user('admin'))
            ->get(route('admin.analysis-disputes.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Zitoun Beldi', $html);
        $this->assertStringContainsString('The expiry date on the label contradicts the record.', $html);
        $this->assertStringContainsString($reporter->name, $html);
        $this->assertStringContainsString(AnalysisDisputeReason::MissedIssue->label(), $html);
        // And a control to act on it.
        $this->assertStringContainsString('Decide', $html);
    }

    #[DataProvider('nonAdminProvider')]
    public function test_no_other_role_reaches_the_queue(string $role): void
    {
        AnalysisDispute::factory()->create();

        $this->actingAs($this->user($role))
            ->get(route('admin.analysis-disputes.index'))
            ->assertForbidden();
    }

    #[DataProvider('nonAdminProvider')]
    public function test_no_other_role_may_decide(string $role): void
    {
        $dispute = AnalysisDispute::factory()->create();

        $this->actingAs($this->user($role))
            ->patch(route('admin.analysis-disputes.update', $dispute), [
                'status' => AnalysisDisputeStatus::Dismissed->value,
                'resolution_note' => 'Not my call.',
            ])
            ->assertForbidden();

        $this->assertSame(AnalysisDisputeStatus::Pending, $dispute->fresh()->status);
    }

    public function test_a_dispute_is_not_offered_as_a_decision(): void
    {
        $dispute = AnalysisDispute::factory()->create();

        $this->actingAs($this->user('admin'))
            ->from(route('admin.analysis-disputes.index'))
            ->patch(route('admin.analysis-disputes.update', $dispute), [
                'status' => AnalysisDisputeStatus::Pending->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(AnalysisDisputeStatus::Pending, $dispute->fresh()->status);
    }

    public function test_dismissing_requires_an_explanation(): void
    {
        $dispute = AnalysisDispute::factory()->create();

        $this->actingAs($this->user('admin'))
            ->from(route('admin.analysis-disputes.index'))
            ->patch(route('admin.analysis-disputes.update', $dispute), [
                'status' => AnalysisDisputeStatus::Dismissed->value,
            ])
            ->assertSessionHasErrors('resolution_note');

        $this->assertSame(AnalysisDisputeStatus::Pending, $dispute->fresh()->status);
    }

    public function test_a_decided_report_cannot_be_decided_again(): void
    {
        $admin = $this->user('admin');
        $dispute = AnalysisDispute::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.analysis-disputes.index'))
            ->patch(route('admin.analysis-disputes.update', $dispute), [
                'status' => AnalysisDisputeStatus::Upheld->value,
                'resolution_note' => 'The certificate is on file; the finding was a false positive.',
            ])
            ->assertSessionHasNoErrors();

        $dispute->refresh();
        $this->assertSame(AnalysisDisputeStatus::Upheld, $dispute->status);
        $this->assertSame($admin->id, $dispute->reviewed_by);
        $this->assertNotNull($dispute->reviewed_at);

        // A second decision would erase the first, so it is refused outright.
        $this->actingAs($admin)
            ->from(route('admin.analysis-disputes.index'))
            ->patch(route('admin.analysis-disputes.update', $dispute), [
                'status' => AnalysisDisputeStatus::Dismissed->value,
                'resolution_note' => 'Changing my mind.',
            ])
            ->assertStatus(422);
    }

    public function test_the_queue_can_be_filtered_by_status(): void
    {
        $pending = AnalysisDispute::factory()->create(['comment' => 'Still waiting on a decision.']);
        $dismissed = AnalysisDispute::factory()->dismissed()->create(['comment' => 'Already ruled on.']);

        $html = $this->actingAs($this->user('admin'))
            ->get(route('admin.analysis-disputes.index', ['status' => AnalysisDisputeStatus::Pending->value]))
            ->assertOk()
            ->getContent();

        // Assert on the row content, not the status label: every status is
        // always rendered as a filter tab, so the label appears either way.
        $this->assertStringContainsString($pending->comment, $html);
        $this->assertStringNotContainsString($dismissed->comment, $html);

        // A bogus filter is ignored rather than rendered as a tab.
        $this->actingAs($this->user('admin'))
            ->get(route('admin.analysis-disputes.index', ['status' => 'nonsense']))
            ->assertOk()
            ->assertSee($dismissed->comment, false);
    }

    // ---------- The loop closes for the reporter ----------

    public function test_the_reporter_sees_the_awaiting_state_and_then_the_decision(): void
    {
        $this->fakeDetector($this->finding());
        $reporter = $this->user('producer');
        $food = Food::factory()->create();
        $dispute = AnalysisDispute::factory()->create([
            'food_id' => $food->id,
            'user_id' => $reporter->id,
        ]);

        $pending = $this->actingAs($reporter)->get(route('products.show', $food))->assertOk()->getContent();
        $this->assertStringContainsString('awaiting review', $pending);
        // No second report while one is open.
        $this->assertStringNotContainsString('Report a problem with this analysis', $pending);

        $this->actingAs($this->user('admin'))
            ->from(route('admin.analysis-disputes.index'))
            ->patch(route('admin.analysis-disputes.update', $dispute), [
                'status' => AnalysisDisputeStatus::Dismissed->value,
                'resolution_note' => 'The certificate reference does match the record.',
            ])
            ->assertSessionHasNoErrors();

        $decided = $this->actingAs($reporter)->get(route('products.show', $food))->assertOk()->getContent();
        $this->assertStringContainsString('The certificate reference does match the record.', $decided);
        $this->assertStringContainsString('Analysis stands', $decided);
        // Now that it is settled they may report again.
        $this->assertStringContainsString('Report a problem with this analysis', $decided);
    }

    // ---------- It must not punish the product ----------

    public function test_no_decision_changes_the_products_trust_score(): void
    {
        $admin = $this->user('admin');
        $food = Food::factory()->create();

        $before = $food->transparencyScore();

        $upheld = AnalysisDispute::factory()->create(['food_id' => $food->id]);
        $this->actingAs($admin)
            ->from(route('admin.analysis-disputes.index'))
            ->patch(route('admin.analysis-disputes.update', $upheld), [
                'status' => AnalysisDisputeStatus::Upheld->value,
                'resolution_note' => 'The detector was wrong about this one.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($before, $food->fresh()->transparencyScore());
        $this->assertSame(0, $food->fresh()->upheldReportCount());

        // The two queues stay independent: an upheld dispute is not a report.
        $this->assertDatabaseCount('greenwashing_reports', 0);
    }

    public function test_an_analysis_dispute_is_not_a_greenwashing_report(): void
    {
        $reporter = $this->user('consumer');
        $food = Food::factory()->create();

        AnalysisDispute::factory()->create([
            'food_id' => $food->id,
            'user_id' => $reporter->id,
            'status' => AnalysisDisputeStatus::Upheld,
        ]);

        $this->assertCount(0, $food->fresh()->reports);
        $this->assertCount(1, $food->fresh()->analysisDisputes);
        $this->assertNull($food->fresh()->reports()->first());
        $this->assertInstanceOf(GreenwashingReport::class, GreenwashingReport::query()->first() ?? new GreenwashingReport);
    }
}
