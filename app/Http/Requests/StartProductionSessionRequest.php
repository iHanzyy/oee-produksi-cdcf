<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartProductionSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'operator_name' => trim((string) $this->input('operator_name')),
            'batch_number' => trim((string) $this->input('batch_number')),
        ]);
    }

    public function rules(): array
    {
        $areaId = $this->integer('area_id');

        return [
            'area_id' => ['required', 'integer', Rule::exists('areas', 'id')],
            'operator_name' => ['required', 'string', 'max:100'],
            'batch_number' => ['required', 'string', 'max:255'],
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(
                    fn (Builder $query) => $query->where('area_id', $areaId)->where('is_active', true)
                ),
            ],
            'machine_id' => [
                'required',
                'integer',
                Rule::exists('machines', 'id')->where(
                    fn (Builder $query) => $query->where('area_id', $areaId)->where('is_active', true)
                ),
            ],
            'client_time' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'area_id.required' => 'Pilih area operasional dulu.',
            'area_id.integer' => 'Area operasional yang dipilih tidak valid.',
            'area_id.exists' => 'Area operasional tidak ditemukan.',
            'operator_name.required' => 'Nama operator wajib diisi.',
            'operator_name.max' => 'Nama operator maksimal 100 karakter.',
            'batch_number.required' => 'Nomor batch wajib diisi.',
            'batch_number.max' => 'Nomor batch terlalu panjang (maksimal 255 karakter).',
            'product_id.required' => 'Pilih produk dari daftar yang tersedia.',
            'product_id.integer' => 'Produk yang dipilih tidak valid.',
            'product_id.exists' => 'Produk tidak tersedia atau belum aktif di area ini.',
            'machine_id.required' => 'Pilih mesin dari daftar yang tersedia.',
            'machine_id.integer' => 'Mesin yang dipilih tidak valid.',
            'machine_id.exists' => 'Mesin tidak tersedia atau belum aktif di area ini.',
        ];
    }
}
