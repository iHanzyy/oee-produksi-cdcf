# Spec: Production Batch Number

Module ID: `production-batch-number`

## Objective

Memungkinkan Operator mencatat nomor batch secara manual saat memulai produksi agar sesi aktif, riwayat downtime, dan laporan Excel dapat ditelusuri ke batch yang benar.

## Assumptions

- Nomor batch adalah teks bebas yang di-trim, wajib untuk sesi baru, dan maksimal 255 karakter.
- Nomor batch tidak unik; nilai yang sama boleh dipakai pada beberapa sesi atau mesin.
- Kolom database nullable agar histori lama tetap terbaca tanpa membuat nomor batch palsu.
- Histori lama tanpa nomor batch ditampilkan sebagai `-`.

## Tech Stack

Mengikuti `ENGINEERING-CONTRACT.md`: PHP 8.2+, Laravel 12, Blade, Alpine.js 3, Tailwind CSS 3, Vite 7, MySQL produksi, SQLite in-memory untuk test, dan Laravel Excel untuk XLSX.

## Commands

- Test terfokus: `php artisan test --filter='ProductionSessionControlTest|ProductionDashboardTest|HistoryReportingTest|EndToEndProductionFlowTest'`
- Test lengkap: `composer test`
- Format check: `vendor/bin/pint --test`
- Frontend build: `npm run build`

## Requirements

- Form mulai produksi menampilkan input wajib `Nomor Batch` tepat di bawah input nama produk.
- Server memvalidasi nomor batch sebagai string wajib, membuang spasi di awal/akhir, dan menolak nilai lebih dari 255 karakter.
- `production_sessions` menyimpan nomor batch pada kolom `batch_number` tanpa unique index.
- Response sesi produksi menggunakan properti `batchNumber` agar satu payload dipakai oleh dashboard, polling, takeover, dan daftar sesi aktif.
- Dashboard Operator menampilkan nomor batch pada sesi yang sedang dikontrol dan daftar sesi aktif yang dapat dilanjutkan.
- Halaman riwayat SPV/Manager menampilkan nomor batch pada kartu sesi aktif dan setiap baris riwayat downtime.
- Export Excel menambahkan kolom `Nomor Batch` dengan filter dan urutan data yang sama seperti riwayat layar.
- Setelah sesi selesai dan diakui, nilai input nomor batch di-reset bersama form mulai produksi lainnya.

## Project Structure

- `database/migrations/` menambah kolom nullable tanpa mengubah data historis.
- `app/Http/Requests/StartProductionSessionRequest.php` menormalisasi dan memvalidasi input.
- `app/Http/Controllers/ProductionController.php` menyimpan dan mengirim nomor batch pada payload sesi.
- `resources/js/app.js` mengelola state form, request start, dan reset input.
- `resources/views/dashboard.blade.php` menampilkan input dan nomor batch produksi aktif.
- `app/Http/Controllers/HistoryController.php` mengirim nomor batch pada payload sesi aktif.
- `resources/views/history.blade.php` menampilkan nomor batch pada monitoring aktif dan riwayat downtime.
- `app/Exports/DowntimeHistoryExport.php` menambahkan nomor batch ke XLSX.
- Test regresi tetap berada di `tests/Feature/` yang sudah mencakup alur produksi dan pelaporan.

## Code Style

Ikuti pola Form Request dan payload camelCase yang sudah ada:

```php
$this->merge([
    'batch_number' => trim((string) $this->input('batch_number')),
]);

'batch_number' => ['required', 'string', 'max:255'],

// Payload browser mengikuti pola operatorName dan startTime.
'batchNumber' => $session->batch_number,
```

## Testing Strategy

- Feature test start memastikan nomor batch wajib, spasi dibuang, nilai tersimpan, dan muncul pada JSON sesi.
- Feature test memastikan nomor batch yang sama dapat dipakai pada sesi atau mesin berbeda.
- Dashboard test memastikan label/input serta nomor batch sesi aktif dirender.
- History test memastikan nomor batch terlihat pada monitoring aktif, tabel downtime, heading export, dan mapping XLSX.
- End-to-end test membawa nomor batch dari start sampai riwayat setelah produksi selesai.
- Build frontend memverifikasi perubahan Alpine.js dan Blade dapat dikompilasi.

## Boundaries

- **Always:** validasi server-side, pertahankan histori lama, gunakan payload sesi yang sudah ada, dan tambahkan test regresi pada alur terdampak.
- **Ask first:** mengubah format bisnis nomor batch, membatasi nomor batch per produk/mesin, atau membuat data lama wajib diisi ulang.
- **Never:** membuat nomor batch otomatis, menambahkan unique constraint, mengubah nomor batch setelah sesi dimulai, atau menghapus histori lama.

## Success Criteria

- Request start tanpa nomor batch menerima HTTP 422 dan tidak membuat sesi.
- Request start dengan nomor batch valid membuat sesi dan menyimpan nilai yang sudah di-trim.
- Dua sesi berbeda dapat menyimpan nomor batch yang sama.
- Nomor batch tampil pada sesi aktif Operator, monitoring aktif SPV/Manager, riwayat downtime, dan export Excel.
- Record lama dengan `batch_number = null` tetap dapat dibuka dan ditampilkan sebagai `-`.
- Test terfokus, format check, dan frontend build lulus.

## Out of Scope

- Pembuatan nomor batch otomatis.
- Validasi unik atau pola khusus nomor batch.
- Pencarian dan filter riwayat berdasarkan nomor batch.
- Pengubahan nomor batch setelah produksi dimulai.
- Backfill nomor batch untuk histori lama.

## Open Questions

- Tidak ada sebelum planning.