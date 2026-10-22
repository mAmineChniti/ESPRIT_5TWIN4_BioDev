<?php

namespace App\Http\Requests;

use App\Enums\TransportMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The load typed into the shipment form, sent to the transport advisor.
 *
 * The limits match ShipmentRequest, so a load the form would refuse is refused
 * here too instead of being sent to the model.
 */
class TransportAdviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route group admits distributors and admins; repeated here so a
        // route moved out of the group still fails closed.
        return (bool) $this->user()?->isAdmin() || $this->user()?->role === 'distributor';
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'destination' => ['nullable', 'string', 'max:120'],
            'distance_km' => ['required', 'numeric', 'min:0.1', 'max:30000'],
            'weight_kg' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'food_id' => ['nullable', 'exists:foods,id'],
            'transport_mode' => ['nullable', Rule::enum(TransportMode::class)],
        ];
    }
}
