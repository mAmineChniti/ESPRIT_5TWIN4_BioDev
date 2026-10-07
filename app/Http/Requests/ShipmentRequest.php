<?php

namespace App\Http\Requests;

use App\Enums\ShipmentStatus;
use App\Enums\TransportMode;
use App\Services\CarbonFootprintCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reference' => [
                'required', 'string', 'max:30',
                Rule::unique('shipments', 'reference')->ignore($this->route('shipment')),
            ],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'food_id' => ['nullable', 'exists:foods,id'],
            'destination' => ['required', 'string', 'max:120'],
            'distance_km' => ['required', 'numeric', 'min:0.1', 'max:30000'],
            'weight_kg' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'transport_mode' => ['required', Rule::enum(TransportMode::class)],
            'status' => ['required', Rule::enum(ShipmentStatus::class)],
            'shipped_on' => ['required', 'date'],
        ];
    }

    /**
     * Validated data plus the computed footprint, ready to save.
     *
     * @return array<string, mixed>
     */
    public function shipmentPayload(): array
    {
        $data = $this->validated();

        $data['carbon_footprint_kg'] = CarbonFootprintCalculator::calculate(
            (float) $data['weight_kg'],
            (float) $data['distance_km'],
            TransportMode::from($data['transport_mode']),
        );

        return $data;
    }
}