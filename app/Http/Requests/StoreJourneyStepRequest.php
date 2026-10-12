<?php

namespace App\Http\Requests;

use App\Enums\FarmStatus;
use App\Enums\ShipmentStatus;
use App\Models\JourneyStep;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJourneyStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route group admits processors; repeated here so a route moved
        // out of the group still fails closed.
        return $this->user()?->role === 'processor';
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
            'type' => ['required', Rule::in(JourneyStep::FLOW)],
            'location' => ['required', 'string', 'max:120'],
            'step_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            // A step may only cite a farm the platform actually publishes
            // and a shipment that has not been cancelled: pending, rejected
            // or foreign records must not be linkable through this id.
            'farm_id' => ['nullable', 'integer', Rule::exists('farms', 'id')->where(fn ($query) => $query->where('status', FarmStatus::Approved->value))],
            'shipment_id' => ['nullable', 'integer', Rule::exists('shipments', 'id')->where(fn ($query) => $query->where('status', '!=', ShipmentStatus::Cancelled->value))],
        ];
    }

    public function messages(): array
    {
        return [
            'step_order.required' => __('The step order is required.'),
            'step_order.integer' => __('The step order must be an integer.'),
            'step_order.min' => __('The step order must be at least 1.'),
            'step_order.max' => __('The step order cannot exceed 65535.'),
            'step_order.unique' => __('This step order is already used in this journey.'),
            'type.required' => __('The step type is required.'),
            'type.in' => __('The selected type is invalid.'),
            'location.required' => __('The location is required.'),
            'location.string' => __('The location must be a string.'),
            'location.max' => __('The location cannot exceed 120 characters.'),
            'step_date.required' => __('The step date is required.'),
            'step_date.date' => __('The step date is invalid.'),
            'description.string' => __('The description must be a string.'),
            'description.max' => __('The description cannot exceed 255 characters.'),
            'farm_id.exists' => __('The selected farm is not an approved farm.'),
            'shipment_id.exists' => __('The selected shipment is not available.'),
        ];
    }
}
