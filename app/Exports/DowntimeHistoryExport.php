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
            $this->safeText(optional(optional($log->productionSession)->machine)->area->area_name ?? null),
            $this->safeText(optional(optional($log->productionSession)->machine)->machine_name ?? null),
            $this->safeText($log->productionSession?->batch_number),
            $this->safeText(optional($log->downtimeCategory)->category_name ?? null),
            $this->safeText($log->replaced_component),
            $this->safeText($log->repair_action),
            $this->safeText(optional($log->personnel)->name ?? null),
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
            'Nomor Batch',
            'Kategori Kendala',
            'Komponen Diganti',
            'Tindakan Perbaikan',
            'Pelaksana / Teknisi',
            'Durasi (Menit)',
            'Status',
        ];
    }

    private function safeText(?string $value): string
    {
        $text = trim((string) $value);
        if ($text === '' || $text === '-') {
            return '-';
        }

        // ponytail: prefix formula-triggering characters on export to block spreadsheet injection without extra classes.
        return preg_match('/^[=+\-@]/', $text) === 1 ? "'".$text : $text;
    }
}
