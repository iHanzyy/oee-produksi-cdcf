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
        ];
    }
}
