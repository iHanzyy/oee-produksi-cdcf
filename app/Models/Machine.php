<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    protected $guarded = [];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function productionSessions()
    {
        return $this->hasMany(ProductionSession::class);
    }
}