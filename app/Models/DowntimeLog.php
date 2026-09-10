<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DowntimeLog extends Model
{
    protected $guarded = [];

    public function productionSession()
    {
        return $this->belongsTo(ProductionSession::class);
    }

    public function downtimeCategory()
    {
        return $this->belongsTo(DowntimeCategory::class);
    }

    public function personnel()
    {
        return $this->belongsTo(Personnel::class);
    }
}