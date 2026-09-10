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
}