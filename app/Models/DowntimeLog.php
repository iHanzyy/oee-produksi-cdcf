<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DowntimeLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'stop_time' => 'datetime',
            'go_time' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function scopeForHistory(Builder $query, array $filters): Builder
    {
        return $query
            ->with(['productionSession.machine.area', 'downtimeCategory', 'personnel'])
            ->when(isset($filters['start_date']), function (Builder $query) use ($filters): void {
                $start = CarbonImmutable::createFromFormat('Y-m-d', $filters['start_date'], 'Asia/Jakarta')
                    ->startOfDay()
                    ->utc();
                $end = CarbonImmutable::createFromFormat('Y-m-d', $filters['end_date'], 'Asia/Jakarta')
                    ->endOfDay()
                    ->utc();

                $query->whereBetween('stop_time', [$start, $end]);
            })
            ->when(isset($filters['area_id']), fn (Builder $query) => $query->whereHas(
                'productionSession.machine',
                fn (Builder $query) => $query->where('area_id', $filters['area_id']),
            ))
            ->when(isset($filters['machine_id']), fn (Builder $query) => $query->whereHas(
                'productionSession',
                fn (Builder $query) => $query->where('machine_id', $filters['machine_id']),
            ))
            ->orderByDesc('stop_time')
            ->orderByDesc('id');
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->go_time !== null) {
            return 'Selesai';
        }

        return in_array($this->productionSession?->status, ['running', 'downtime'], true)
            ? 'Berlangsung'
            : 'Data tidak lengkap';
    }

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
