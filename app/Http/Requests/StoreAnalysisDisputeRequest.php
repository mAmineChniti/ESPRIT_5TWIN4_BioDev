<?php

namespace App\Http\Requests;

use App\Enums\AnalysisDisputeReason;
use App\Enums\FindingCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnalysisDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Feedback about our own tool is not a supply chain action, so every
        // signed in role may file one — including a producer disputing a verdict
        // about their own product, and an admin reporting a wrong reading.
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Written once from the enum, so the writer and the reader cannot
            // drift the way a hardcoded list would.
            'reason' => ['required', Rule::enum(AnalysisDisputeReason::class)],
            // Optional: a dispute may be about the verdict as a whole.
            'finding_category' => ['nullable', Rule::enum(FindingCategory::class)],
            'comment' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Say what the analysis got wrong.',
            'comment.required' => 'Explain what the analysis got wrong, so an admin can act on it.',
            'comment.min' => 'Please give at least 10 characters of detail.',
            'comment.max' => 'Please keep this under 2000 characters.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function disputePayload(): array
    {
        return [
            'food_id' => $this->route('food')->id,
            'user_id' => $this->user()->id,
            'reason' => AnalysisDisputeReason::from($this->validated('reason')),
            'finding_category' => $this->filled('finding_category')
                ? FindingCategory::from($this->validated('finding_category'))
                : null,
            'comment' => $this->validated('comment'),
        ];
    }
}
