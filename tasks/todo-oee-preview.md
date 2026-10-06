# Task List: OEE Preview

Source: `docs/specs/SPEC-oee-preview.md`

## Task 1: Render panel OEE dummy pada sesi aktif

**Description:** Tambahkan kontrak tampilan lalu panel OEE statis yang ringkas pada container sesi aktif sehingga panel yang sama terlihat saat running, downtime, dan completed.

**Acceptance criteria:**

- [x] Dashboard merender OEE `78.8%`, Availability `92.0%`, Performance `87.4%`, dan Quality `98.0%`.
- [x] Panel hanya menampilkan label metrik dan angka tanpa copy dummy.
- [x] Tidak ada perubahan pada model, controller, route, migration, payload, export, atau alur kontrol sesi.

**Verification:**

- [x] RED lalu GREEN: `php artisan test --filter=ProductionDashboardTest`.
- [x] Suite dan format: `composer test && vendor/bin/pint --test tests/Feature/ProductionDashboardTest.php`.
- [x] Build dan runtime: `npm run build`, lalu periksa state running, downtime, dan completed pada viewport tablet.

**Dependencies:** None

**Files likely touched:**

- `tests/Feature/ProductionDashboardTest.php`
- `resources/views/dashboard.blade.php`

**Estimated scope:** Small, 2 files, 20–30 minutes.

## Checkpoint: Complete

- [x] Acceptance criteria terpenuhi.
- [x] Tidak ada regresi pada start, stop, resume, complete, takeover, dan acknowledge.
- [x] Siap direview manusia sebelum merge atau deploy.
