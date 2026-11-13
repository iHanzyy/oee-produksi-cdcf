<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DowntimeCategory extends Model
{
    public const OPERATOR_OPTIONS = [
        'SORTIR',
        'PERBAIKAN MESIN',
        'TMS SUHU & RH',
        'LAIN LAIN',
        'MENUNGGU BAHAN BAKU/KEMAS',
        'PREVENTIVE MAINTENANCE',
        'CLEANING',
        'SETTING AWAL CETAK',
        'PERSIAPAN MESIN',
        'CHANGE SPART',
        'MENUNGGU VERIFIKASI',
        'ISTIRAHAT',
    ];

    protected $guarded = [];

    public function downtimeLogs()
    {
        return $this->hasMany(DowntimeLog::class);
    }
}
