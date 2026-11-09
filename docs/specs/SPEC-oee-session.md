# Spec: OEE Per Sesi Produksi

Module ID: `oee-session`

## Objective

Menghitung dan menyimpan Availability, Quality, Performance, dan OEE untuk setiap sesi produksi agar Operator melihat ringkasan setelah selesai dan SPV/Manager melihat satu baris hasil per sesi pada halaman riwayat.

Keberhasilan berarti angka berasal dari output aktual Operator, waktu operasi yang dihitung server, serta standar aktif untuk kombinasi produk dan mesin. Riwayat downtime tetap berjalan terpisah dan tidak menggandakan hasil OEE.

## Tech Stack

- PHP 8.2+, Laravel 12, Eloquent, dan validasi Form Request.
- Blade, Alpine.js 3, Tailwind CSS 3, dan Vite 7.
- MySQL untuk aplikasi dan SQLite in-memory untuk test.
- Kontrak umum mengikuti `docs/specs/ENGINEERING-CONTRACT.md`.

## Commands

- Test terfokus: `php artisan test --filter=Oee`
- Test alur produksi dan riwayat: `php artisan test --filter='Production|History|Downtime'`
- Test lengkap: `composer test`
- Format check: `vendor/bin/pint --test`
- Frontend build: `npm run build`

## Project Structure

- `app/Models/` menyimpan model standar OEE dan scope riwayat sesi.
- `app/Http/Requests/` memvalidasi input penyelesaian sesi dan kombinasi master.
- `app/Http/Controllers/` mengorkestrasi start, penyelesaian, payload Operator, dan riwayat.
- `database/migrations/` dan `database/seeders/` menyimpan schema serta master yang sudah terverifikasi.
- `resources/views/`, `resources/js/`, dan `tests/Feature/` menangani input, tampilan, serta verifikasi alur lengkap.

## Data Model

### Master `oee_standards`

- `product_id` dan `machine_id` membentuk pasangan unik.
- `theoretical_output_kg` memakai decimal dan wajib lebih dari nol.
- `ideal_cycle_time_hours_per_kg` memakai decimal dan wajib lebih dari nol.
- `is_active` menentukan apakah kombinasi boleh memulai sesi.
- Hanya pasangan dengan konversi kg dan jam/kg yang sudah terverifikasi yang disediakan oleh seeder.
- Data SPV memetakan `Mixing` dari Granulasi, `Stripper Indomach` dari Running Stripper, serta kedua `Cetak Gylongli` dari Running Mesin Cetak.
- Konversi `jam/kg`: Granulasi memakai `1 / kg per jam`, Stripper memakai `jam standard / kg batch`, dan Cetak memakai `(butir teoritis / butir per jam) / kg batch`.
- Kedua mesin Cetak memakai standar produk yang sama. Blister tetap tanpa standar aktif karena tabel belum menyediakan bobot teoritis kg yang dapat dipetakan ke produk aplikasi.

### Snapshot `production_sessions`

- Tambahkan nilai nullable: `actual_output_kg`, `theoretical_output_kg`, `ideal_cycle_time_hours_per_kg`, dan `operating_time_seconds`.
- Tambahkan hasil nullable: `availability_percent`, `quality_percent`, `performance_percent`, dan `oee_percent`.
- Gunakan `planned_time_minutes` yang sudah ada dengan nilai 480 menit.
- Kolom nullable menjaga kompatibilitas record lama; tampilan menggunakan tanda pisah ketika hasil OEE tidak tersedia.
- Jangan memakai `total_output_pcs` atau `reject_pcs` karena satuannya tidak sesuai kontrak kg.

## Calculation Contract

Semua perhitungan dilakukan server-side saat sesi diselesaikan. Nilai disimpan dengan presisi minimal empat angka desimal dan ditampilkan dengan dua angka desimal.

```php
$operatingHours = $operatingTimeSeconds / 3600;
$availability = $operatingTimeSeconds / (480 * 60) * 100;
$quality = $actualOutputKg / $theoreticalOutputKg * 100;
$performance = $idealCycleTimeHoursPerKg * $actualOutputKg / $operatingHours * 100;
$oee = $availability * $quality * $performance / 10000;
```

- `operating_time_seconds` adalah waktu sejak mulai sampai selesai dikurangi seluruh downtime.
- `actual_output_kg` menerima angka nol atau lebih; standar dan waktu operasi wajib lebih dari nol.
- Persentase tidak dibatasi pada 100% dan tidak dibulatkan sebelum OEE dihitung.
- Peringatan diturunkan dari snapshot ketika salah satu hasil melebihi 100%.
- Perubahan master setelah sesi selesai tidak mengubah snapshot atau riwayat lama.

## Production Flow

- Start menolak pasangan produk dan mesin tanpa standar aktif dengan pesan `Standar OEE belum tersedia untuk produk dan mesin ini.`
- Penyelesaian sesi meminta `actual_output_kg` dan konfirmasi yang sudah ada dalam satu request.
- Penyelesaian tetap ditolak saat downtime aktif atau dalam cooldown tiga detik.
- Transaksi dan lock sesi yang sudah ada mencakup penyimpanan output, snapshot standar, serta seluruh hasil OEE.
- Payload completed mengirim output aktual, empat metrik, dan daftar peringatan kepada Operator.

## User Interface

- Form selesai produksi menyediakan input angka `Output aktual (kg)` yang wajib, minimal nol, dan mendukung pecahan.
- Ringkasan completed mengganti preview dummy dengan Output Aktual, Availability, Quality, Performance, OEE, dan Peringatan.
- Angka di atas 100% tetap tampil dan diberi peringatan untuk memeriksa input atau master standar.
- Pesan validasi server ditampilkan melalui dialog kesalahan yang sudah ada.
- Kontrol start, stop, resume, takeover, acknowledge, dan timer tetap mengikuti pola sekarang.

## History Reporting

- Tambahkan tabel `Riwayat Produksi/OEE` pada `resources/views/history.blade.php` tanpa mengubah tabel downtime.
- Tabel memakai satu baris per `production_sessions` berstatus completed dan menampilkan identitas sesi serta enam kolom OEE yang disepakati.
- Filter tanggal memakai `end_time`; filter area dan mesin memakai relasi mesin yang sama dengan laporan downtime.
- Gunakan paginator terpisah agar pagination sesi tidak mengubah halaman downtime dan sebaliknya.
- Export Excel yang ada tetap khusus downtime; export OEE tidak termasuk MVP.

## Code Style

Gunakan nama domain yang menyatakan satuan dan hindari helper baru jika logika hanya dipakai pada satu state transition.

```php
$session->update([
    'actual_output_kg' => $validated['actual_output_kg'],
    'operating_time_seconds' => $operatingTimeSeconds,
    'oee_percent' => $oee,
]);
```

- Validasi input pada Form Request atau aturan request yang sudah menjadi trust boundary.
- Gunakan eager loading pada query riwayat.
- Gunakan transaksi dan `lockForUpdate()` pada penyelesaian sesi.
- Jangan menyimpan nilai persentase sebagai string berformat `%`.
- Jangan menambah dependency atau service layer baru untuk empat rumus ini.

## Testing Strategy

- Feature test membuktikan start ditolak tanpa standar dan diterima dengan standar aktif.
- Feature test penyelesaian memakai waktu tetap serta downtime untuk memverifikasi empat rumus dan snapshot database.
- Feature test mencakup output nol, nilai di atas 100%, pesan peringatan, validasi input, dan sesi lama bernilai null.
- History test memastikan satu baris per sesi, filter/pagination terpisah, role SPV/Manager, dan tabel downtime tidak berubah.
- Dashboard test memastikan hanya nilai riil dari payload yang tampil dan tidak ada konstanta preview.

## Boundaries

- **Always:** hitung server-side, simpan snapshot atomik, validasi satuan, dan tampilkan nilai di atas 100% tanpa pemotongan.
- **Ask first:** mengubah waktu rencana 480 menit, mengaktifkan pasangan master baru, atau mengubah rumus.
- **Never:** menebak konversi dari stripper/butir/blister ke kg, menghitung ulang sesi selesai dari master terbaru, atau menaruh OEE pada downtime log.
- **Preserve:** route, kepemilikan tablet, state machine, filter downtime, dan export downtime yang sudah ada.
- **Compatibility:** sesi lama tanpa snapshot tetap dapat dibaca dan menampilkan tanda pisah.

## Success Criteria

- Operator dapat menyelesaikan sesi dengan output aktual kg dan langsung melihat empat metrik serta peringatan.
- Hasil tersimpan sama dengan rumus berdasarkan waktu operasi tanpa downtime dan standar produk + mesin.
- Start tanpa standar aktif ditolak sebelum sesi dibuat.
- SPV/Manager melihat tepat satu baris OEE per sesi completed pada halaman riwayat.
- Test terfokus, suite lengkap, Pint, build frontend, dan pemeriksaan runtime tablet/desktop lulus.

## Out of Scope

- Grafik, dashboard KPI, agregasi harian/bulanan, atau target OEE.
- Reject/scrap dan sensor/PLC.
- CRUD master OEE serta import Excel otomatis.
- Export OEE dan kalkulasi ulang sesi lama.
- Perubahan pada laporan downtime selain pagination independen yang diperlukan.

## Open Questions

- Nilai master Blister dan produk berfase masih belum lengkap; implementasi hanya mengaktifkan pasangan yang memiliki teori kg dan jam/kg yang dapat diturunkan langsung dari data SPV.
