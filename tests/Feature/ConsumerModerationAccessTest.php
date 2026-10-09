<?php

namespace Tests\Feature;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Food;
use App\Models\GreenwashingReport;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Reviewing and filing greenwashing reports are consumer actions; deciding a
 * report is an admin action. Each is enforced on the route, and a review may
 * only be deleted through the product it belongs to.
 */
class ConsumerModerationAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function nonConsumerProvider(): array
    {
        return [
            'admin' => ['admin'],
            'producer' => ['producer'],
            'processor' => ['processor'],
            'distributor' => ['distributor'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonAdminProvider(): array
    {
        return [
            'consumer' => ['consumer'],
            'producer' => ['producer'],
            'processor' => ['processor'],
            'distributor' => ['distributor'],
        ];
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    // ---------- Reviews ----------

    #[DataProvider('nonConsumerProvider')]
    public function test_only_a_consumer_may_leave_a_review(string $role): void
    {
        $food = Food::factory()->create();

        $this->actingAs($this->user($role))
            ->post(route('products.reviews.store', $food), ['rating' => 4])
            ->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    #[DataProvider('nonConsumerProvider')]
    public function test_only_a_consumer_may_delete_a_review(string $role): void
    {
        $food = Food::factory()->create();
        $review = Review::factory()->create(['food_id' => $food->id, 'user_id' => $this->user('consumer')->id]);

        $this->actingAs($this->user($role))
            ->delete(route('products.reviews.destroy', ['food' => $food, 'review' => $review]))
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    public function test_a_consumer_may_delete_their_own_review(): void
    {
        $consumer = $this->user('consumer');
        $food = Food::factory()->create();
        $review = Review::factory()->create(['food_id' => $food->id, 'user_id' => $consumer->id]);

        $this->actingAs($consumer)
            ->delete(route('products.reviews.destroy', ['food' => $food, 'review' => $review]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_a_review_cannot_be_deleted_through_another_products_address(): void
    {
        $consumer = $this->user('consumer');
        $ownProduct = Food::factory()->create();
        $unrelatedProduct = Food::factory()->create();

        $review = Review::factory()->create([
            'food_id' => $ownProduct->id,
            'user_id' => $consumer->id,
        ]);

        // The consumer owns the review, but it is not the review of the product
        // named in the URL. Route-model-binding is not authorization: both the
        // product and the review have to agree.
        $this->actingAs($consumer)
            ->delete(route('products.reviews.destroy', ['food' => $unrelatedProduct, 'review' => $review]))
            ->assertNotFound();

        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    public function test_a_user_cannot_delete_someone_elses_review(): void
    {
        $food = Food::factory()->create();
        $review = Review::factory()->create(['food_id' => $food->id, 'user_id' => $this->user('consumer')->id]);

        $this->actingAs($this->user('consumer'))
            ->delete(route('products.reviews.destroy', ['food' => $food, 'review' => $review]))
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    // ---------- Greenwashing reports ----------

    #[DataProvider('nonConsumerProvider')]
    public function test_only_a_consumer_may_file_a_greenwashing_report(string $role): void
    {
        $food = Food::factory()->create();

        $this->actingAs($this->user($role))
            ->post(route('products.reports.store', $food), ['reason' => ReportReason::UnverifiableClaim->value])
            ->assertForbidden();

        $this->assertDatabaseCount('greenwashing_reports', 0);
    }

    public function test_a_consumer_may_file_a_greenwashing_report(): void
    {
        $consumer = $this->user('consumer');
        $food = Food::factory()->create();

        $this->actingAs($consumer)
            ->post(route('products.reports.store', $food), ['reason' => ReportReason::UnverifiableClaim->value])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('greenwashing_reports', [
            'food_id' => $food->id,
            'user_id' => $consumer->id,
            'status' => ReportStatus::Pending->value,
        ]);
    }

    public function test_a_consumer_may_only_have_one_open_report_per_product(): void
    {
        $consumer = $this->user('consumer');
        $food = Food::factory()->create();

        $this->actingAs($consumer)->post(route('products.reports.store', $food), [
            'reason' => ReportReason::UnverifiableClaim->value,
        ]);

        $this->actingAs($consumer)->post(route('products.reports.store', $food), [
            'reason' => ReportReason::UnverifiableClaim->value,
        ])->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('greenwashing_reports', 1);
    }

    public function test_a_second_report_is_allowed_once_the_first_is_decided(): void
    {
        $consumer = $this->user('consumer');
        $food = Food::factory()->create();

        $first = GreenwashingReport::factory()->create([
            'food_id' => $food->id,
            'user_id' => $consumer->id,
            'status' => ReportStatus::Dismissed,
        ]);

        $this->actingAs($consumer)->post(route('products.reports.store', $food), [
            'reason' => ReportReason::UnverifiableClaim->value,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('greenwashing_reports', 2);
        $this->assertDatabaseHas('greenwashing_reports', ['id' => $first->id, 'status' => ReportStatus::Dismissed->value]);
    }

    #[DataProvider('nonAdminProvider')]
    public function test_only_an_admin_may_decide_a_report(string $role): void
    {
        $report = GreenwashingReport::factory()->create();

        $this->actingAs($this->user($role))
            ->patch(route('admin.reports.update', $report), ['status' => ReportStatus::Upheld->value])
            ->assertForbidden();

        $this->assertNull($report->fresh()->reviewed_by);
    }

    public function test_an_admin_upholding_a_report_lowers_the_transparency_score(): void
    {
        $food = Food::factory()->create();
        $report = GreenwashingReport::factory()->create([
            'food_id' => $food->id,
            'status' => ReportStatus::Pending,
        ]);

        $before = $food->transparencyScore();

        $this->actingAs($this->user('admin'))
            ->patch(route('admin.reports.update', $report), ['status' => ReportStatus::Upheld->value])
            ->assertSessionHasNoErrors();

        $this->assertSame(ReportStatus::Upheld, $report->fresh()->status);
        $this->assertLessThan($before, $food->fresh()->transparencyScore());
    }

    public function test_a_dismissed_report_does_not_lower_the_score(): void
    {
        $food = Food::factory()->create();
        $report = GreenwashingReport::factory()->create([
            'food_id' => $food->id,
            'status' => ReportStatus::Pending,
        ]);

        $before = $food->transparencyScore();

        $this->actingAs($this->user('admin'))
            ->patch(route('admin.reports.update', $report), ['status' => ReportStatus::Dismissed->value]);

        $this->assertSame($before, $food->fresh()->transparencyScore());
    }
}
