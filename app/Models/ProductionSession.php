<?php

namespace App\Models;

use Carbon\CarbonImmutable;
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
            'actual_output_kg' => 'decimal:3',
            'theoretical_output_kg' => 'decimal:3',
            'ideal_cycle_time_hours_per_kg' => 'decimal:10',
            'operating_time_seconds' => 'integer',
            'availability_percent' => 'decimal:4',
            'quality_percent' => 'decimal:4',
            'performance_percent' => 'decimal:4',
            'oee_percent' => 'decimal:4',
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

    public function scopeForHistory(Builder $query, array $filters): Builder
    {
        return $query
            ->with(['machine.area', 'product'])
            ->where('status', 'completed')
            ->when(isset($filters['start_date']), function (Builder $query) use ($filters): void {
                $start = CarbonImmutable::createFromFormat('Y-m-d', $filters['start_date'], 'Asia/Jakarta')
                    ->startOfDay()
                    ->utc();
                $end = CarbonImmutable::createFromFormat('Y-m-d', $filters['end_date'], 'Asia/Jakarta')
                    ->endOfDay()
                    ->utc();

                $query->whereBetween('end_time', [$start, $end]);
            })
            ->when(isset($filters['area_id']), fn (Builder $query) => $query->whereHas(
                'machine',
                fn (Builder $query) => $query->where('area_id', $filters['area_id']),
            ))
            ->when(isset($filters['machine_id']), fn (Builder $query) => $query->where('machine_id', $filters['machine_id']))
            ->orderByDesc('end_time')
            ->orderByDesc('id');
    }

    public function oeeWarnings(): array
    {
        return array_values(array_filter([
            $this->availability_percent !== null && (float) $this->availability_percent > 100 ? 'Availability melebihi 100%.' : null,
            $this->quality_percent !== null && (float) $this->quality_percent > 100 ? 'Quality melebihi 100%.' : null,
            $this->performance_percent !== null && (float) $this->performance_percent > 100 ? 'Performance melebihi 100%.' : null,
            $this->oee_percent !== null && (float) $this->oee_percent > 100 ? 'OEE melebihi 100%.' : null,
        ]));
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
