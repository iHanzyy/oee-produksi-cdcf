# Spec: Rumus OEE dan Input Output Produksi

Module ID: `oee-formulas-and-output`

## Objective

Menghitung Availability, Performance, Quality, dan OEE sesuai jenis proses serta standar produk-mesin yang diberikan SPV. Operator menyelesaikan sesi dengan memasukkan Total Output dan Reject; sistem menghitung Good Output, menyimpan snapshot hasil, dan menampilkan hasil yang dapat diaudit pada dashboard serta riwayat.

Keberhasilan berarti hasil OEE tidak lagi memakai satu rumus berbasis kilogram untuk semua mesin, planned production time mengikuti produk-mesin, dan satuan input mengikuti proses: tablet, strip, blister, atau kg.

## Tech Stack

- PHP 8.2+, Laravel 12, Eloquent, Form Request, dan transaksi database.
- Blade, Alpine.js 3, Tailwind CSS 3, dan Vite 7.
- MySQL untuk produksi dan SQLite in-memory untuk test.
- Tidak menambah dependency.
- Kontrak umum mengikuti `docs/specs/ENGINEERING-CONTRACT.md`.

## Commands

- Test terfokus: `php artisan test --filter=OeeCalculationTest`
- Test master: `php artisan test --filter=OeeStandardTest`
- Test lengkap: `composer test`
- Format check: `vendor/bin/pint --test`
- Frontend build: `npm run build`
- Runtime: selesaikan satu sesi unit-cycle dan satu sesi yield-ratio pada viewport tablet.

## Project Structure

- `database/migrations/` menambah kolom standar generik dan snapshot output tanpa menghapus kolom legacy.
- `database/seeders/ProductionMasterSeeder.php` menyimpan standar dari empat gambar secara idempotent.
- `app/Models/OeeStandard.php` dan `app/Models/ProductionSession.php` menangani cast serta snapshot data baru.
- `app/Http/Requests/CompleteProductionSessionRequest.php` memvalidasi Total Output dan Reject pada trust boundary.
- `app/Http/Controllers/ProductionController.php` memilih rumus berdasarkan jenis proses dan menyimpan snapshot dalam transaksi penyelesaian sesi.
- `resources/js/app.js` dan `resources/views/dashboard.blade.php` meminta dua input akhir dengan label satuan dinamis.
- `resources/views/history*.blade.php` menampilkan output generik, reject, good output, standar, dan metrik snapshot tanpa merusak sesi legacy.
- `tests/Feature/` memverifikasi schema, master data, validasi, rumus, dashboard, dan alur produksi.

## Code Style

Gunakan rumus langsung pada satu titik penyelesaian sesi dan Form Request untuk validasi silang:

```php
$goodOutput = $totalOutput - $rejectOutput;
$qualityPercent = $totalOutput > 0 ? $goodOutput / $totalOutput * 100 : 0;
```

- Ikuti Laravel conventions dan Laravel Pint.
- Pertahankan transaksi serta row lock pada penyelesaian sesi.
- Gunakan nama field generik untuk data baru; kolom berakhiran `_kg` dan `_pcs` tetap dipertahankan hanya untuk sesi legacy.
- Jangan memotong persentase ke 100%; gunakan peringatan yang sudah ada.

## Data Contract

### Standar Produk-Mesin

Setiap pasangan produk-mesin aktif memiliki:

- `process_type`: `unit_cycle` untuk cetak, striping, dan blister; `yield_ratio` untuk granulasi dan coating;
- `output_unit`: `tablet`, `strip`, `blister`, atau `kg`;
- `planned_time_minutes` lebih dari 0;
- `theoretical_output` lebih dari 0 untuk referensi dan snapshot;
- `ideal_cycle_time_minutes_per_unit` lebih dari 0 untuk `unit_cycle`, dan kosong untuk `yield_ratio`.

Saat sesi dimulai, planned production time diambil dari standar produk-mesin. Saat sesi diselesaikan, standar aktif disalin sebagai snapshot agar perubahan master berikutnya tidak mengubah hasil sesi lama.

### Input Penyelesaian

- Operator mengisi `total_output` dan `reject_output` dalam satuan standar mesin.
- Keduanya numerik dan tidak negatif; reject tidak boleh melebihi total.
- `good_output = total_output - reject_output`.
- Total nol dengan reject nol valid dan menghasilkan Performance, Quality, serta OEE nol.

### Rumus

```text
Realtime Process / Session Duration = akumulasi waktu mesin berstatus running
Downtime tidak masuk ke timer Session Duration karena timer berhenti selama downtime
Availability = Realtime Process / Planned Production Time * 100%
Quality = Good Output / Total Output * 100%
OEE = Availability * Performance * Quality / 10,000
```

Untuk cetak, striping, dan blister:

```text
Performance = Ideal Cycle Time * Total Output / Realtime Process * 100%
```

Ideal Cycle Time dan Realtime Process sama-sama memakai menit. Total Output memakai tablet, strip, atau blister sesuai standar.

Untuk granulasi dan coating, rumus dipertahankan persis sesuai ketentuan pengguna:

```text
Performance = Process Time * Good Output / (Realtime Process * Total Output) * 100%
```

Process Time dan Realtime Process memakai akumulasi waktu mesin running yang sama, sehingga rumus tersebut secara matematis sama dengan Quality. Jika Realtime Process tidak positif, penyelesaian ditolak. Downtime tetap dicatat dan ditampilkan terpisah; timer Durasi Sesi berhenti selama downtime lalu berlanjut saat mesin running kembali.

## Master Data

### Striping — `Stripper Indomach`

- Semua produk memakai `0.0125` menit/strip.
- Alyrenol, Pregnolin, Progeston, Prestrenol: output teoritis 20,000 strip; planned time 960 menit.
- Bifertil, Provula, Novasterone, Norelut, Meprolut, Luteron, Norethisterone, Etiro-N, Andalut: output teoritis 14,000 strip; planned time 480 menit.

### Cetak — `Cetak Gylongli 1` dan `Cetak Gylongli 2`

- Semua produk memakai `0.0004525` menit/tablet.
- Alyrenol, Pregnolin, Progeston, Prestrenol: output teoritis 200,000 tablet; planned time 480 menit.
- Bifertil, Provula, Novasterone, Norelut, Meprolut, Luteron, Norethisterone, Etiro-N, Andalut: output teoritis 140,000 tablet; planned time 480 menit.
- Trinordiol Fase 1: 636,000 tablet; planned time 660 menit.
- Trinordiol Fase 2: 530,000 tablet; planned time 660 menit.
- Trinordiol Fase 3: 1,060,000 tablet; planned time 660 menit.
- Pil KB Harmonis: 1,500,000 tablet; planned time 1,140 menit.

### Blister — `Blister DKPS 1` dan `Blister DKPS 2`

- Pil KB Harmonis: output teoritis 71,428 blister; `0.0111` menit/blister; planned time 1,980 menit.
- Trinordiol: output teoritis 106,000 blister; `0.01667` menit/blister; planned time 3,300 menit.

### Granulasi — `Mixing`

- Alyrenol, Pregnolin, Progeston, Prestrenol: output teoritis 40 kg; planned time 480 menit.
- Bifertil, Provula: output teoritis 25.55 kg; planned time 480 menit.
- Novasterone, Norelut, Meprolut, Luteron, Norethisterone, Etiro-N, Andalut: output teoritis 25.235 kg; planned time 480 menit.
- Trinordiol Fase 1: 34.980 kg; planned time 660 menit.
- Trinordiol Fase 2: 29.150 kg; planned time 660 menit.
- Trinordiol Fase 3: 58.300 kg; planned time 660 menit.
- Pil KB Harmonis: 90 kg; planned time 660 menit.

### Coating — `Coating Yenchen`

- Pil KB Harmonis: output teoritis 90 kg; planned time 660 menit.
- Trinordiol Fase 1: 34.980 kg; planned time 1,320 menit.
- Trinordiol Fase 2: 29.150 kg; planned time 1,140 menit.
- Trinordiol Fase 3: 58.300 kg; planned time 1,800 menit.

## User Interface

- Dialog selesai produksi menampilkan Total Output dan Reject, bukan hanya Output Aktual (kg).
- Label, helper text, `step`, dan contoh mengikuti `output_unit` dari standar sesi.
- Good Output ditampilkan sebagai hasil otomatis sebelum konfirmasi.
- Setelah selesai, dashboard dan riwayat menampilkan Total, Reject, Good, satuan, planned time, Durasi Sesi, Availability, Performance, Quality, OEE, dan peringatan tanpa Waktu Operasi Bersih.
- Form tetap dapat digunakan dengan keyboard, label terhubung ke input, error server terlihat, dan tombol konfirmasi tidak aktif saat input belum valid.

## Testing Strategy

- Schema test memastikan kolom standar dan snapshot generik tersedia tanpa menghapus kolom legacy.
- Seeder test memverifikasi jumlah pasangan standar, nilai representative setiap proses, tiga produk fase, dan idempotensi.
- Feature test mengikuti RED-GREEN untuk setiap rumus: unit-cycle dengan downtime, yield-ratio, total nol, reject melebihi total, downtime melebihi planned time, snapshot master, dan nilai di atas 100%.
- Dashboard test memastikan dua input, satuan dinamis, Good Output otomatis, dan markup aksesibel tersedia.
- End-to-end test memastikan sesi operator selesai dan snapshot dapat dibaca SPV.
- Runtime check menjalankan sedikitnya satu mesin unit-cycle dan satu mesin yield-ratio pada viewport tablet.

## Boundaries

- **Always:** validasi server-side, hitung Good Output di server, gunakan standar produk-mesin aktif, simpan snapshot, pertahankan transaksi, dan tampilkan satuan yang benar.
- **Ask first:** mengubah nilai master pada empat gambar, mengganti rumus yang sudah disetujui, atau memigrasikan ulang angka sesi legacy.
- **Never:** menghapus data historis, mengubah sesi lama secara retroaktif, menerima reject di atas total, membagi dengan nol, atau menyamarkan nilai di atas 100%.
- **Preserve:** kontrol multi-tablet, downtime flow, takeover, filter dan export history, route yang ada, serta perubahan history-detail yang sedang berjalan.

## Success Criteria

- Planned production time setiap sesi baru cocok dengan pasangan produk-mesin pada master di atas.
- Availability memakai akumulasi waktu mesin berstatus running, lalu dibandingkan dengan planned time.
- Performance cetak/striping/blister memakai ideal cycle dan total output dalam satuan mesin dibagi akumulasi waktu mesin running.
- Performance granulasi/coating memakai rumus yield-ratio yang disetujui dan sama dengan Quality.
- Quality memakai Good Output hasil Total Output dikurangi Reject.
- Input invalid ditolak tanpa menyelesaikan atau merusak sesi.
- Dashboard dan riwayat menampilkan nilai serta satuan baru; sesi legacy tetap dapat dibaca.
- Test terfokus, suite lengkap, Pint, build frontend, dan runtime check lulus.

## Out of Scope

- Halaman CRUD/admin untuk mengubah standar OEE.
- Input fase terpisah; fase Trinordiol adalah produk master tersendiri.
- Konversi kg menjadi tablet, strip, atau blister.
- Koreksi otomatis data sesi lama atau penghapusan kolom legacy.
- Target baik/buruk, grafik, tren, dan perubahan export Excel.

## Open Questions

- Tidak ada sebelum planning.
