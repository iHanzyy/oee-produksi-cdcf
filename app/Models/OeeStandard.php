<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OeeStandard extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'theoretical_output_kg' => 'decimal:3',
            'ideal_cycle_time_hours_per_kg' => 'decimal:10',
            'is_active' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
