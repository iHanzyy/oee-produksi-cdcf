namespace App\Exports;

use App\Models\DowntimeLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DowntimeHistoryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function collection()
    {
        return DowntimeLog::with(['productionSession.machine.area', 'downtimeCategory', 'personnel'])->get();
    }

    public function headings(): array
    {
        return [
            'ID', 'Tanggal', 'Area', 'Mesin', 'Kategori Downtime', 
            'Aksi Perbaikan', 'Pelaksana', 'Waktu Stop', 'Waktu Go', 'Durasi (Menit)'
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            optional($row->stop_time)->format('Y-m-d'),
            optional(optional($row->productionSession)->machine)->area->area_name ?? '-',
            optional(optional($row->productionSession)->machine)->machine_name ?? '-',
            optional($row->downtimeCategory)->category_name ?? '-',
            $row->repair_action,
            optional($row->personnel)->name,
            $row->stop_time,
            $row->go_time,
            round($row->duration_seconds / 60, 2),
        ];
    }
}