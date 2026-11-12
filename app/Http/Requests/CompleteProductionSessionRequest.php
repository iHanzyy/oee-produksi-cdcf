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
            'total_output' => ['required', 'numeric', 'min:0', 'max:999999999999.999'],
            'reject_output' => ['required', 'numeric', 'min:0', 'max:999999999999.999', 'lte:total_output'],
        ];
    }

    public function messages(): array
    {
        return [
            'total_output.required' => 'Total output wajib diisi.',
            'total_output.numeric' => 'Total output harus berupa angka.',
            'total_output.min' => 'Total output tidak boleh kurang dari 0.',
            'total_output.max' => 'Total output melebihi batas yang diizinkan.',
            'reject_output.required' => 'Reject wajib diisi.',
            'reject_output.numeric' => 'Reject harus berupa angka.',
            'reject_output.min' => 'Reject tidak boleh kurang dari 0.',
            'reject_output.max' => 'Reject melebihi batas yang diizinkan.',
            'reject_output.lte' => 'Reject tidak boleh melebihi total output.',
        ];
    }
}
