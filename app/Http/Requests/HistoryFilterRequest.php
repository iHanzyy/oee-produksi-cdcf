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

    public function filters(): array
    {
        return array_filter(
            $this->validated(),
            fn ($value) => $value !== null && $value !== '',
        );
    }
}
