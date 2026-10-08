<?php

namespace App\Http\Requests;

use App\Enums\AnalysisDisputeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnalysisDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route group restricts this to admins; repeated here so a route
        // moved out of the group still fails closed.
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // `pending` is excluded: a dispute is created pending and decided
            // once. Re-opening it would erase the decision.
            'status' => [
                'required',
                Rule::enum(AnalysisDisputeStatus::class),
                Rule::notIn([AnalysisDisputeStatus::Pending->value]),
            ],
            // Required on dismissal so the reporter is not told nothing without
            // being told why.
            'resolution_note' => ['nullable', 'string', 'max:2000', Rule::requiredIf($this->input('status') === AnalysisDisputeStatus::Dismissed->value)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'resolution_note.required' => 'Say why the analysis stands, so the reporter is not left without an answer.',
            'resolution_note.max' => 'Please keep this under 2000 characters.',
        ];
    }
}
