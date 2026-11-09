<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DowntimeCategory extends Model
{
    protected $guarded = [];

    public function downtimeLogs()
    {
        return $this->hasMany(DowntimeLog::class);
    }
}
