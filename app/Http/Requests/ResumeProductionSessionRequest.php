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

    public function messages(): array
    {
        return [
            'category_id.required' => 'Pilih kategori kendala dulu.',
            'category_id.integer' => 'Kategori kendala tidak valid.',
            'category_id.exists' => 'Kategori kendala tidak ditemukan.',
            'personnel_id.required' => 'Pilih pelaksana perbaikan dulu.',
            'personnel_id.integer' => 'Pelaksana perbaikan tidak valid.',
            'personnel_id.exists' => 'Data pelaksana perbaikan tidak ditemukan.',
            'replaced_component.max' => 'Nama komponen maksimal 255 karakter.',
            'repair_action.required' => 'Isi tindakan perbaikan yang sudah dilakukan.',
            'repair_action.max' => 'Catatan perbaikan maksimal 1000 karakter.',
        ];
    }
}
