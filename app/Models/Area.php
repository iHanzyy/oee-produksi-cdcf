<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $guarded = [];

    public function machines()
    {
        return $this->hasMany(Machine::class);
    }

    public function activeMachines()
    {
        return $this->machines()->where('is_active', true);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function activeProducts()
    {
        return $this->products()->where('is_active', true);
    }
}
