<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HistoryFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $areaId = $this->integer('area_id');

        return [
            'start_date' => ['nullable', 'date_format:Y-m-d', 'required_with:end_date'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'required_with:start_date', 'after_or_equal:start_date'],
            'area_id' => ['nullable', 'integer', Rule::exists('areas', 'id')],
            'machine_id' => [
                'nullable',
                'integer',
                Rule::exists('machines', 'id')->when(
                    $areaId > 0,
                    fn ($rule) => $rule->where(fn (Builder $query) => $query->where('area_id', $areaId)),
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.date_format' => 'Format tanggal mulai tidak sesuai (gunakan YYYY-MM-DD).',
            'start_date.required_with' => 'Tanggal mulai wajib diisi jika tanggal akhir dipilih.',
            'end_date.date_format' => 'Format tanggal akhir tidak sesuai (gunakan YYYY-MM-DD).',
            'end_date.required_with' => 'Tanggal akhir wajib diisi jika tanggal mulai dipilih.',
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai.',
            'area_id.integer' => 'Area yang dipilih tidak valid.',
            'area_id.exists' => 'Area produksi tidak ditemukan.',
            'machine_id.integer' => 'Mesin yang dipilih tidak valid.',
            'machine_id.exists' => 'Mesin tidak ditemukan pada area yang dipilih.',
        ];
    }

    public function filters(): array
    {
        return array_filter(
            $this->validated(),
            fn ($value) => $value !== null && $value !== '',
        );
    }
}
