<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductionSession extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public static function hashControllerToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['running', 'downtime']);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function operator()
    {
        return $this->belongsTo(Personnel::class, 'operator_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function downtimeLogs()
    {
        return $this->hasMany(DowntimeLog::class);
    }
}
