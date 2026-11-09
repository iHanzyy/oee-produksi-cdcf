# Task List: OEE Per Sesi Produksi

Source: `docs/specs/SPEC-oee-session.md`

## Task 1: Tambahkan schema dan master standar OEE

**Description:** Buat tabel master OEE dan kolom snapshot nullable pada sesi produksi, lalu seed hanya pasangan produk + mesin yang memiliki teori kg dan jam/kg terverifikasi.

**Acceptance criteria:**

- [x] `oee_standards` memiliki pasangan produk + mesin unik, nilai standar positif, dan status aktif.
- [x] `production_sessions` menyimpan input, standar, waktu operasi, serta empat hasil nullable tanpa mengubah data lama.
- [x] Seeder idempotent dan tidak menebak konversi dari stripper, butir, atau blister ke kg.

**Verification:**

- [x] Jalankan `php artisan test --filter=OeeStandardTest`.
- [x] Jalankan migration pada SQLite test dan MySQL development tanpa menghapus master atau histori lain.
- [x] Jalankan seeder dua kali dan bandingkan jumlah serta nilai record.

**Dependencies:** None

**Files likely touched:**

- `database/migrations/<timestamp>_add_oee_standards_and_session_results.php`
- `app/Models/OeeStandard.php`
- `database/seeders/ProductionMasterSeeder.php`
- `tests/Feature/OeeStandardTest.php`

**Estimated scope:** Medium, 4 files, 45–60 minutes.

## Task 2: Tolak start tanpa standar aktif

**Description:** Validasi pasangan produk + mesin terhadap master OEE sebelum membuat sesi dan kembalikan pesan yang dapat ditampilkan oleh UI sekarang.

**Acceptance criteria:**

- [x] Pasangan tanpa standar aktif menerima HTTP 422 dengan pesan `Standar OEE belum tersedia untuk produk dan mesin ini.`
- [x] Pasangan dengan standar aktif tetap mengikuti validasi area, status master, dan konflik mesin yang sudah ada.
- [x] Request gagal tidak membuat sesi atau mengubah kepemilikan mesin.

**Verification:**

- [x] Jalankan `php artisan test --filter=ProductionSessionControlTest`.
- [x] Uji pasangan produk/mesin lintas area, standar nonaktif, dan standar aktif.
- [x] Pastikan test konflik mesin dan takeover tetap lulus.

**Dependencies:** Task 1

**Files likely touched:**

- `app/Http/Requests/StartProductionSessionRequest.php`
- `app/Http/Controllers/ProductionController.php`
- `tests/Feature/ProductionSessionControlTest.php`

**Estimated scope:** Small, 3 files, 25–35 minutes.

## Checkpoint: Foundation

- [x] Schema kompatibel dengan data existing.
- [x] Start hanya tersedia bagi pasangan dengan standar aktif.
- [x] Master produksi yang belum terverifikasi tetap tidak aktif.

## Task 3: Hitung dan simpan snapshot OEE saat sesi selesai

**Description:** Tambahkan input output aktual pada endpoint selesai, hitung waktu operasi tanpa downtime serta empat metrik, lalu simpan seluruh snapshot dalam transaksi yang sudah mengunci sesi.

**Acceptance criteria:**

- [x] Output aktual divalidasi sebagai angka minimal nol dan wajib bersama konfirmasi selesai.
- [x] Availability, Quality, Performance, dan OEE tersimpan sesuai rumus tanpa cap 100% atau pembulatan antara.
- [x] Payload completed mengirim output, hasil, dan peringatan; sesi lama tetap aman saat nilainya null.

**Verification:**

- [x] Jalankan `php artisan test --filter=OeeCalculationTest`.
- [x] Jalankan `php artisan test --filter=DowntimeControlTest`.
- [x] Verifikasi output nol, downtime, hasil di atas 100%, request ganda, dan rollback transaksi.

**Dependencies:** Task 2

**Files likely touched:**

- `app/Http/Requests/CompleteProductionSessionRequest.php`
- `app/Http/Controllers/ProductionController.php`
- `app/Models/ProductionSession.php`
- `tests/Feature/OeeCalculationTest.php`

**Estimated scope:** Medium, 4 files, 60–75 minutes.

## Checkpoint: Calculation

- [x] Snapshot database sama dengan hasil rumus untuk fixture yang diketahui.
- [x] Perubahan master tidak mengubah sesi completed.
- [x] State running, downtime, completed, dan acknowledged tetap valid.

## Task 4: Tambahkan input dan ringkasan OEE Operator

**Description:** Minta output aktual kg sebelum konfirmasi selesai dan tampilkan hasil riil pada state completed menggunakan komponen dashboard yang sudah ada.

**Acceptance criteria:**

- [x] Operator tidak dapat mengirim selesai tanpa output aktual yang valid.
- [x] Ringkasan completed menampilkan Output Aktual, Availability, Quality, Performance, OEE, dan Peringatan.
- [x] Tidak ada angka dummy atau fallback yang dapat dianggap sebagai hasil produksi.

**Verification:**

- [x] Jalankan `php artisan test --filter=ProductionDashboardTest`.
- [x] Jalankan `php artisan test --filter=EndToEndProductionFlowTest`.
- [x] Periksa alur Running → Selesai → Ringkasan → OKE pada viewport tablet.

**Dependencies:** Task 3

**Files likely touched:**

- `resources/views/dashboard.blade.php`
- `resources/js/app.js`
- `tests/Feature/ProductionDashboardTest.php`
- `tests/Feature/EndToEndProductionFlowTest.php`

**Estimated scope:** Medium, 4 files, 45–60 minutes.

## Task 5: Tambahkan tabel Riwayat Produksi/OEE

**Description:** Query sesi completed dengan filter yang sama, paginator terpisah, dan render satu baris per sesi di atas tabel downtime yang tetap dipertahankan.

**Acceptance criteria:**

- [x] SPV/Manager melihat identitas sesi serta enam kolom OEE dengan tanda pisah untuk snapshot null.
- [x] Sesi tanpa downtime tetap muncul sekali dan sesi dengan banyak downtime tidak menggandakan baris OEE.
- [x] Filter tanggal memakai `end_time`; pagination OEE dan downtime tidak saling mengubah halaman.

**Verification:**

- [x] Jalankan `php artisan test --filter=HistoryReportingTest`.
- [x] Verifikasi role Operator tetap tidak dapat membuka halaman riwayat.
- [x] Periksa tabel pada desktop dan tablet serta pastikan export downtime tidak berubah.

**Dependencies:** Task 3; dikerjakan setelah Task 4 untuk review berurutan.

**Files likely touched:**

- `app/Models/ProductionSession.php`
- `app/Http/Controllers/HistoryController.php`
- `resources/views/history.blade.php`
- `tests/Feature/HistoryReportingTest.php`

**Estimated scope:** Medium, 4 files, 50–65 minutes.

## Checkpoint: Complete

- [x] Jalankan `composer test` dan pastikan tidak ada regresi.
- [x] Jalankan `vendor/bin/pint --test` dan `npm run build`.
- [x] Verifikasi runtime start valid/invalid, downtime, selesai, ringkasan Operator, dan riwayat SPV/Manager.
- [ ] Human menyetujui hasil sebelum merge atau deploy.
