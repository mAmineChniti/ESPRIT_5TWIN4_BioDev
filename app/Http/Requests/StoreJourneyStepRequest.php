<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJourneyStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'step_order' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
                Rule::unique('journey_steps', 'step_order')
                    ->where(fn ($query) => $query->where('journey_id', $this->route('journey')->id)),
            ],
            'type' => ['required', Rule::in(['origin', 'transport', 'storage', 'sale'])],
            'location' => ['required', 'string', 'max:120'],
            'step_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'step_order.required' => 'The step order is required.',
            'step_order.integer' => 'The step order must be an integer.',
            'step_order.min' => 'The step order must be at least 1.',
            'step_order.max' => 'The step order cannot exceed 65535.',
            'step_order.unique' => 'This step order is already used in this journey.',
            'type.required' => 'The step type is required.',
            'type.in' => 'The selected type is invalid.',
            'location.required' => 'The location is required.',
            'location.string' => 'The location must be a string.',
            'location.max' => 'The location cannot exceed 120 characters.',
            'step_date.required' => 'The step date is required.',
            'step_date.date' => 'The step date is invalid.',
            'description.string' => 'The description must be a string.',
            'description.max' => 'The description cannot exceed 255 characters.',
        ];
    }
}
