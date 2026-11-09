# Task List: Nomor Batch Produksi

Sumber persetujuan: `docs/specs/SPEC-production-batch-number.md`.
Plan teknis: `tasks/plan-production-batch-number.md`.

## Task 1: Siapkan caller test untuk kontrak start baru

**Description:** Tambahkan `batch_number` pada semua request start produksi yang menguji alur sukses, sebelum server mewajibkan field tersebut. Request role yang memang berhenti di middleware tidak diubah.

**Acceptance criteria:**
- [x] Semua helper/request start sukses di test control, downtime, dan end-to-end mengirim nomor batch.
- [x] Nilai batch test mudah dikenali dan tidak mengubah assertion perilaku lama.
- [x] Test terkait tetap lulus sebelum kontrak server berubah.

**Verification:**
- [x] Jalankan `php artisan test tests/Feature/ProductionSessionControlTest.php tests/Feature/DowntimeControlTest.php tests/Feature/EndToEndProductionFlowTest.php`.
- [x] Jalankan `rg -n "route\('machine\.start'\)" tests/Feature` dan pastikan caller sukses membawa `batch_number`.

**Dependencies:** None

**Files likely touched:**
- `tests/Feature/ProductionSessionControlTest.php`
- `tests/Feature/DowntimeControlTest.php`
- `tests/Feature/EndToEndProductionFlowTest.php`

**Estimated scope:** Medium, 3 files, 15-25 minutes.

## Task 2: Simpan dan kirim nomor batch melalui API sesi

**Description:** Tambahkan kolom database kompatibel histori, validasi trust boundary, penyimpanan saat start, dan properti payload sesi tanpa mengubah state machine atau endpoint.

**Acceptance criteria:**
- [x] Migration menambah `batch_number` nullable tanpa unique index dan dapat di-rollback.
- [x] Start mewajibkan string non-kosong maksimal 255 karakter, men-trim nilai, lalu menyimpannya.
- [x] Payload start, polling, takeover, dan daftar sesi membawa `batchNumber`; nomor yang sama boleh dipakai pada sesi berbeda.

**Verification:**
- [x] Jalankan `php artisan test --filter=ProductionSessionControlTest`.
- [x] Jalankan migration up/down pada SQLite terisolasi.
- [x] Pastikan request tanpa batch menerima HTTP 422 dan record lama bernilai null tetap dapat dimuat.

**Dependencies:** Task 1

**Files likely touched:**
- `database/migrations/2026_11_06_025124_add_batch_number_to_production_sessions_table.php`
- `app/Http/Requests/StartProductionSessionRequest.php`
- `app/Http/Controllers/ProductionController.php`
- `tests/Feature/ProductionSessionControlTest.php`

**Estimated scope:** Medium, 4 files, 35-45 minutes.

## Checkpoint A: Contract

- [x] Task 1 dan Task 2 acceptance criteria lulus.
- [x] `ProductionSessionControlTest`, `DowntimeControlTest`, dan `EndToEndProductionFlowTest` lulus.
- [x] Review schema dan response sebelum mengubah UI.

## Task 3: Tambahkan input dan tampilan pada dashboard Operator

**Description:** Hubungkan nomor batch ke state Alpine, form start, reset form, kartu sesi aktif, dan daftar takeover dengan pola UI yang sudah ada.

**Acceptance criteria:**
- [x] Input wajib `Nomor Batch` berada tepat di bawah input nama produk dan memiliki batas 255 karakter.
- [x] Request start mengirim `batch_number`; acknowledge mereset nilai input.
- [x] Sesi aktif dan daftar sesi yang dapat dilanjutkan menampilkan nomor batch atau `-` untuk histori lama.

**Verification:**
- [x] Jalankan `php artisan test --filter=ProductionDashboardTest`.
- [x] Jalankan `npm run build`.
- [x] Periksa manual form Ready dan Running pada viewport tablet; daftar takeover diverifikasi melalui feature test.

**Dependencies:** Task 2

**Files likely touched:**
- `resources/js/app.js`
- `resources/views/dashboard.blade.php`
- `tests/Feature/ProductionDashboardTest.php`

**Estimated scope:** Medium, 3 files, 30-40 minutes.

## Task 4: Tambahkan nomor batch pada monitoring, riwayat, dan export

**Description:** Teruskan nomor batch ke payload monitoring aktif, tampilkan pada kartu dan tabel downtime, lalu tambahkan kolom yang sama ke export Excel serta alur end-to-end.

**Acceptance criteria:**
- [x] Monitoring aktif SPV/Manager dan tabel riwayat downtime menampilkan nomor batch.
- [x] Export Excel memiliki heading `Nomor Batch` dan nilai yang sama dengan sesi pada layar.
- [x] Histori lama memakai fallback `-`, sedangkan alur end-to-end membawa batch dari start hingga riwayat.

**Verification:**
- [x] Jalankan `php artisan test tests/Feature/HistoryReportingTest.php tests/Feature/EndToEndProductionFlowTest.php`.
- [x] Bandingkan nomor batch pada monitoring, tabel, dan mapping export untuk fixture yang sama.
- [x] Verifikasi heading dan nilai kolom `Nomor Batch` melalui feature test export.

**Dependencies:** Task 2

**Files likely touched:**
- `app/Http/Controllers/HistoryController.php`
- `resources/views/history.blade.php`
- `app/Exports/DowntimeHistoryExport.php`
- `tests/Feature/HistoryReportingTest.php`
- `tests/Feature/EndToEndProductionFlowTest.php`

**Estimated scope:** Medium, 5 files, 40-55 minutes.

## Checkpoint B: Complete

- [x] Jalankan `php artisan test --filter='ProductionSessionControlTest|ProductionDashboardTest|HistoryReportingTest|EndToEndProductionFlowTest|DowntimeControlTest'`.
- [x] Jalankan `composer test` dan laporkan kegagalan yang tidak terkait tanpa memperbaiki di luar scope.
- [x] Jalankan `vendor/bin/pint --test` pada file PHP yang disentuh.
- [x] Jalankan `npm run build`.
- [x] Verifikasi runtime: start dengan batch, sesi aktif, dan monitoring SPV; selesai, riwayat, dan export Excel tercakup feature test.
- [ ] Human menyetujui hasil sebelum merge atau deploy.
