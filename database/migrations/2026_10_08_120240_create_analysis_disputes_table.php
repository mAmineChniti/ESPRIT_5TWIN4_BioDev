<?php

use App\Enums\AnalysisDisputeStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A challenge to the AI detector's verdict on a product.
     *
     * This is deliberately not a greenwashing report. A report says "this
     * product's claims are misleading"; a dispute says "our analysis of this
     * product is wrong". Mixing the two would let a tool bug be filed as a claim
     * against the product, and would drag the product's trust score down for a
     * mistake NutriTrace made.
     */
    public function up(): void
    {
        Schema::create('analysis_disputes', function (Blueprint $table): void {
            $table->id();
            // The table is named explicitly: `constrained()` guesses `food`
            // from `food_id`, not `foods`, and a dangling FK reference fails at
            // insert time rather than at migration time.
            $table->foreignId('food_id')->constrained('foods')->cascadeOnDelete();
            // A dispute outlives the account that filed it, so the record of who
            // challenged the analysis is not lost when the account is closed.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason');
            // Which finding is being challenged, when it is a specific one
            // rather than the verdict as a whole.
            $table->string('finding_category')->nullable();
            $table->text('comment');
            $table->string('status')->default(AnalysisDisputeStatus::Pending->value);
            $table->text('resolution_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // The admin queue filters by status and orders by age, which is the
            // one query that runs on every page of it.
            $table->index(['status', 'created_at']);
            $table->index('food_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_disputes');
    }
};
