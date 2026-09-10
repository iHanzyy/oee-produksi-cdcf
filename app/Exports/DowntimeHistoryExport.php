<?php

namespace App\Exports;

use App\Models\DowntimeLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class DowntimeHistoryExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = DowntimeLog::with(['productionSession.machine.area', 'downtimeCategory', 'personnel']);

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $start = Carbon::parse($this->filters['start_date'])->startOfDay();
            $end = Carbon::parse($this->filters['end_date'])->endOfDay();
            $query->whereBetween('stop_time', [$start, $end]);
        }

        if (!empty($this->filters['area_id'])) {
            $query->whereHas('productionSession.machine', function($q) {
                $q->where('area_id', $this->filters['area_id']);
            });
        }

        if (!empty($this->filters['machine_id'])) {
            $query->whereHas('productionSession', function($q) {
                $q->where('machine_id', $this->filters['machine_id']);
            });
        }

        return $query->latest()->get();
    }

    public function map($log): array
    {
        return [
            Carbon::parse($log->stop_time)->format('d-m-Y H:i:s'),
            optional(optional($log->productionSession)->machine)->area->area_name ?? '-',
            optional(optional($log->productionSession)->machine)->machine_name ?? '-',
            optional($log->downtimeCategory)->category_name ?? '-',
            $log->replaced_component ?? '-',
            $log->repair_action ?? '-',
            optional($log->personnel)->name ?? '-',
            round($log->duration_seconds / 60, 2),
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
            'Durasi (Menit)'
        ];
    }
}