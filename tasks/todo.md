# Task List: Rumus OEE dan Input Output Produksi

Source: `docs/specs/SPEC-oee-formulas-and-output.md`

## Task 1: Tambahkan kontrak schema dan model OEE generik

**Description:** Tambah field standar dan snapshot generik secara additive, lalu cast model agar sesi baru dapat menyimpan jenis proses, satuan, planned time, output, dan ideal cycle tanpa mengubah data legacy.

**Acceptance criteria:**

- [x] `oee_standards` memiliki process type, output unit, planned time, theoretical output, dan ideal cycle menit/unit.
- [x] `production_sessions` memiliki snapshot process type, output unit, theoretical output, ideal cycle, total output, dan reject output.
- [x] Kolom legacy tetap ada dan migration dapat rollback tanpa menghapus tabel atau record lama.

**Verification:**

- [x] RED lalu GREEN: `php artisan test --filter=OeeStandardTest`.
- [x] Jalankan `php artisan migrate:fresh --env=testing`.
- [x] Jalankan `vendor/bin/pint --test` pada migration dan dua model yang disentuh.

**Dependencies:** None

**Files likely touched:**

- `database/migrations/*_add_generic_oee_standard_and_outputs.php`
- `app/Models/OeeStandard.php`
- `app/Models/ProductionSession.php`
- `tests/Feature/OeeStandardTest.php`

**Estimated scope:** Medium, 4 files, 35-45 minutes.

## Task 2: Seed standar produk-mesin dari gambar

**Description:** Tambah tiga produk fase Trinordiol dan ganti kelompok standar lama dengan 72 pasangan standar idempotent untuk cetak, striping, blister, granulasi, serta coating.

**Acceptance criteria:**

- [x] Seeder menghasilkan total 29 produk, 11 mesin, dan 72 standar aktif tanpa duplikasi setelah dijalankan dua kali.
- [x] Nilai representative planned time, theoretical output, ideal cycle, process type, dan unit cocok dengan spec untuk kelima proses.
- [x] `Trinordiol` tetap tersedia untuk blister dan tiga produk fase tersedia untuk cetak, granulasi, serta coating.

**Verification:**

- [x] RED lalu GREEN: `php artisan test --filter=ProductionMasterSeederTest`.
- [x] Jalankan `php artisan test --filter=OeeStandardTest`.
- [x] Jalankan seeder dua kali pada test dan pastikan jumlah record tidak berubah.

**Dependencies:** Task 1

**Files likely touched:**

- `database/seeders/ProductionMasterSeeder.php`
- `tests/Feature/ProductionMasterSeederTest.php`
- `tests/Feature/OeeStandardTest.php`

**Estimated scope:** Medium, 3 files, 40-55 minutes.

## Checkpoint A: Data Foundation

- [x] Migration dan focused tests lulus.
- [x] Master data cocok dengan matriks pada spec.
- [x] Data legacy tidak dihapus atau dikonversi.

## Task 3: Terapkan input, validasi, snapshot, dan rumus OEE baru

**Description:** Validasi Total/Reject, ambil standar saat start, hitung Good Output serta metrik sesuai process type saat completion, lalu simpan snapshot secara atomik.

**Acceptance criteria:**

- [x] Start hanya menerima pasangan produk-mesin dengan standar baru yang valid dan menyalin planned time ke sesi.
- [x] Completion menolak reject di atas total dan operating time nonpositif; total nol menghasilkan metrik nol tanpa pembagian nol.
- [x] Availability, Performance, Quality, OEE, snapshot, dan warning sesuai spec untuk kedua process type.

**Verification:**

- [x] RED lalu GREEN: `php artisan test --filter=OeeCalculationTest`.
- [x] Jalankan `php artisan test --filter=ProductionSessionControlTest`.
- [x] Jalankan `vendor/bin/pint --test` pada request, controller, dan test yang disentuh.

**Dependencies:** Task 2

**Files likely touched:**

- `app/Http/Requests/CompleteProductionSessionRequest.php`
- `app/Http/Controllers/ProductionController.php`
- `tests/Feature/OeeCalculationTest.php`
- `tests/Feature/ProductionSessionControlTest.php`

**Estimated scope:** Medium, 4 files, 60-80 minutes.

## Checkpoint B: Calculation

- [x] Test unit-cycle memakai menit/unit dan total unit.
- [x] Test yield-ratio membuktikan Performance sama dengan Quality.
- [x] Completion invalid tidak mengubah status atau snapshot sesi.

## Task 4: Ubah dialog selesai produksi menjadi Total dan Reject bersatuan dinamis

**Description:** Kirim metadata standar pada payload sesi, ganti satu input output aktual dengan Total/Reject, tampilkan Good Output preview, lalu kirim field baru ke endpoint completion.

**Acceptance criteria:**

- [x] Dialog menampilkan dua input berlabel satuan mesin, Good Output otomatis, dan error yang dapat dibaca.
- [x] Tombol konfirmasi aktif hanya untuk angka valid dengan reject tidak melebihi total.
- [x] Ringkasan selesai menampilkan Total, Reject, Good, satuan, planned time, operating time, serta empat metrik OEE.

**Verification:**

- [x] RED lalu GREEN: `php artisan test --filter=ProductionDashboardTest`.
- [x] Jalankan `npm run build`.
- [x] Periksa keyboard, label, error, dan layout dialog pada viewport tablet.

**Dependencies:** Task 3

**Files likely touched:**

- `app/Http/Controllers/ProductionController.php`
- `resources/js/app.js`
- `resources/views/dashboard.blade.php`
- `tests/Feature/ProductionDashboardTest.php`

**Estimated scope:** Medium, 4 files, 45-65 minutes.

## Task 5: Tampilkan snapshot output baru tanpa merusak history legacy

**Description:** Render output generik pada list/detail history dan selaraskan end-to-end test, dengan fallback ke field kg lama untuk sesi legacy.

**Acceptance criteria:**

- [x] History baru menampilkan Total, Reject, Good, theoretical output, satuan, planned/operating time, dan metrik OEE.
- [x] Sesi legacy dengan field generik null tetap terbaca memakai tampilan lama atau tanda `-` yang jujur.
- [x] Filter, pagination, detail downtime, export, dan perubahan history-detail yang ada tidak berubah perilakunya.

**Verification:**

- [x] RED lalu GREEN: `php artisan test --filter=HistoryReportingTest`.
- [x] Jalankan `php artisan test --filter=EndToEndProductionFlowTest`.
- [x] Jalankan `composer test`, scoped `vendor/bin/pint --test`, `npm run build`, lalu runtime check kedua process type.

**Dependencies:** Task 4

**Files likely touched:**

- `resources/views/history.blade.php`
- `resources/views/history-show.blade.php`
- `tests/Feature/HistoryReportingTest.php`
- `tests/Feature/EndToEndProductionFlowTest.php`

**Estimated scope:** Medium, 4 files, 45-65 minutes.

## Checkpoint C: Complete

- [x] Semua success criteria spec terpenuhi.
- [x] Full task-scoped verification lulus tanpa menonaktifkan test.
- [x] Runtime unit-cycle dan yield-ratio bekerja pada viewport tablet.
- [ ] Human menyetujui hasil sebelum merge atau deploy.
