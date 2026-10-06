<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResumeProductionSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'replaced_component' => trim((string) $this->input('replaced_component')) ?: null,
            'repair_action' => trim((string) $this->input('repair_action')),
        ]);
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:downtime_categories,id'],
            'personnel_id' => ['required', 'integer', 'exists:personnels,id'],
            'replaced_component' => ['nullable', 'string', 'max:255'],
            'repair_action' => ['required', 'string', 'max:1000'],
        ];
    }
}
