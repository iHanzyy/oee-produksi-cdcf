<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionSession extends Model
{
    protected $guarded = [];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function operator()
    {
        return $this->belongsTo(Personnel::class, 'operator_id');
    }

    public function downtimeLogs()
    {
        return $this->hasMany(DowntimeLog::class);
    }
}