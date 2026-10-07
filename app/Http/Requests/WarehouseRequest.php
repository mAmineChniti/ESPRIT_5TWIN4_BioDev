<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Access is already restricted by the route middleware (distributor, admin).
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_refrigerated' => $this->boolean('is_refrigerated')]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:80'],
            'country' => ['required', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:200'],
            'capacity_m2' => ['required', 'integer', 'min:1', 'max:1000000'],
            'is_refrigerated' => ['boolean'],
        ];
    }
}
