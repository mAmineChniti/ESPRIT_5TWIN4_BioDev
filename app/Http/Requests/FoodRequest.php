<?php

namespace App\Http\Requests;

use App\Enums\EnvironmentalScore;
use App\Models\Certification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
        $certs = $this->input('certifications', []);

        return array_map('intval', is_array($certs) ? $certs : []);
    }

    /**
     * Detect geographic inconsistencies between origin and certifications.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                // mb_strtolower, not strtolower: the latter is ASCII only, so
                // "Tunisie" variants with accents would skip the check entirely.
                $origin = mb_strtolower(trim((string) $this->input('origin', '')));
                $origin = $origin === 'tunisie' ? 'tunisia' : $origin;
                $certifications = $this->certificationIds();

                if (empty($origin) || empty($certifications)) {
                    return;
                }

                $certs = Certification::whereIn('id', $certifications)->get();

                foreach ($certs as $cert) {
                    $certName = mb_strtolower($cert->name);

                    // Business rule 1: "Local" means Tunisia.
                    if ($certName === 'local' && $origin !== 'tunisia') {
                        $validator->errors()->add(
                            'origin',
                            "Inconsistency detected: the 'Local' certification requires the origin to be Tunisia (you entered: {$this->input('origin')})."
                        );
                    }

                    // Business rule 2: "AOP Normandie" means France.
                    if (str_contains($certName, 'normandie') && $origin !== 'france') {
                        $validator->errors()->add(
                            'origin',
                            "Inconsistency detected: the '{$cert->name}' certification requires the origin to be France (you entered: {$this->input('origin')})."
                        );
                    }
                }
            },
        ];
    }
}
