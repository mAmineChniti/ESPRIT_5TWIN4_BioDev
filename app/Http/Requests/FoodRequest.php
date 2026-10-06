<?php

namespace App\Http\Requests;

use App\Enums\EnvironmentalScore;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FoodRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'origin' => ['nullable', 'string', 'max:255'],
            'environmental_score' => ['nullable', Rule::enum(EnvironmentalScore::class)],
            'calories' => ['required', 'integer', 'min:0', 'max:10000'],
            'protein' => ['required', 'numeric', 'min:0', 'max:100'],
            'carbs' => ['required', 'numeric', 'min:0', 'max:100'],
            'fat' => ['required', 'numeric', 'min:0', 'max:100'],
            'certifications' => ['nullable', 'array'],
            'certifications.*' => ['integer', 'exists:certifications,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'certifications.*.exists' => 'Selected certification is invalid.',
        ];
    }

    public function environmentalScore(): ?EnvironmentalScore
    {
        $score = $this->input('environmental_score');

        return is_string($score) ? EnvironmentalScore::tryFrom(strtoupper(trim($score))) : null;
    }

    public function foodPayload(): array
    {
        $payload = $this->safe()->except('certifications');

        $payload['environmental_score'] = $this->environmentalScore();

        return $payload;
    }

    public function certificationIds(): array
    {
        return array_map('intval', $this->input('certifications', []));
    }
}
