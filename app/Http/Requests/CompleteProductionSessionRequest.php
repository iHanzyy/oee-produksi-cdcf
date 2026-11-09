<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteProductionSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_output_kg' => ['required', 'numeric', 'min:0', 'max:999999999.999'],
        ];
    }

    public function messages(): array
    {
        return [
            'actual_output_kg.required' => 'Output aktual dalam kg wajib diisi.',
            'actual_output_kg.numeric' => 'Output aktual harus berupa angka.',
            'actual_output_kg.min' => 'Output aktual tidak boleh kurang dari 0 kg.',
            'actual_output_kg.max' => 'Output aktual melebihi batas yang diizinkan.',
        ];
    }
}
