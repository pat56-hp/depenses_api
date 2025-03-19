<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HistoriqueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'libelle' => 'required|max: 50',
            'description' => 'nullable|max:255',
            'montant' => 'required|numeric',
            'date' => 'required|date',
            'type' => 'required|in:0,1'
        ];
    }
}
