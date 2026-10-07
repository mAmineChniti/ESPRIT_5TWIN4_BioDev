<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEtapeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ordre' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
                Rule::unique('etapes_parcours', 'ordre')
                    ->where(fn ($query) => $query->where('parcours_id', $this->route('parcours')->id)),
            ],
            'type' => ['required', Rule::in(['origine', 'transport', 'stockage', 'vente'])],
            'lieu' => ['required', 'string', 'max:120'],
            'date_etape' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'ordre.required' => 'L’ordre est obligatoire.',
            'ordre.integer' => 'L’ordre doit être un nombre entier.',
            'ordre.min' => 'L’ordre doit être supérieur ou égal à 1.',
            'ordre.max' => 'L’ordre ne peut pas dépasser 65535.',
            'ordre.unique' => 'Cet ordre est déjà utilisé dans ce parcours.',
            'type.required' => 'Le type est obligatoire.',
            'type.in' => 'Le type sélectionné est invalide.',
            'lieu.required' => 'Le lieu est obligatoire.',
            'lieu.string' => 'Le lieu doit être une chaîne de caractères.',
            'lieu.max' => 'Le lieu ne peut pas dépasser 120 caractères.',
            'date_etape.required' => 'La date de l’étape est obligatoire.',
            'date_etape.date' => 'La date de l’étape est invalide.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'description.max' => 'La description ne peut pas dépasser 255 caractères.',
        ];
    }
}
