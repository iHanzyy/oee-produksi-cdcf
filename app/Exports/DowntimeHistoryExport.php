<?php

namespace App\Exports;

use App\Models\DowntimeLog;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DowntimeHistoryExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private readonly array $filters) {}

    public function query(): Builder
    {
        return DowntimeLog::query()->forHistory($this->filters);
    }

    public function map(mixed $log): array
    {
        assert($log instanceof DowntimeLog);

        return [
            $log->stop_time->timezone('Asia/Jakarta')->format('d-m-Y H:i:s'),
            optional(optional($log->productionSession)->machine)->area->area_name ?? '-',
            optional(optional($log->productionSession)->machine)->machine_name ?? '-',
            optional($log->downtimeCategory)->category_name ?? '-',
            $log->replaced_component ?? '-',
            $log->repair_action ?? '-',
            optional($log->personnel)->name ?? '-',
            round($log->duration_seconds / 60, 2),
            $log->status_label,
        ];
    }

    public function headings(): array
    {
        return [
            'Waktu Stop',
            'Area Produksi',
            'Nama Mesin',
            'Kategori Kendala',
            'Komponen Diganti',
            'Tindakan Perbaikan',
            'Pelaksana / Teknisi',
            'Durasi (Menit)',
            'Status',
        ];
    }
}
