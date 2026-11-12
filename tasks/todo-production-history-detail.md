# Task List: Riwayat Produksi Terpadu

Sumber persetujuan: `docs/specs/SPEC-production-history-detail.md`.

## Task 1: Muat detail downtime melalui query sesi

**Description:** Perluas query riwayat sesi agar setiap sesi completed membawa downtime, kategori, dan pelaksana dalam urutan waktu stop tanpa query tambahan per baris.

**Acceptance criteria:**
- [x] Paginator tetap berisi 15 sesi completed dan mempertahankan filter serta nama halaman yang ada.
- [x] Setiap sesi eager load `machine.area`, `product`, `downtimeLogs.downtimeCategory`, dan `downtimeLogs.personnel`.
- [x] Downtime pada satu sesi terurut dari `stop_time` paling awal dan tidak terpotong oleh filter tanggal sesi.

**Verification:**
- [x] Jalankan `php artisan test --filter=HistoryReportingTest`.
- [x] Test membuktikan relasi detail sudah dimuat dan dua downtime sesi tampil dalam urutan yang benar.

**Dependencies:** None

**Files likely touched:**
- `app/Models/ProductionSession.php`
- `tests/Feature/HistoryReportingTest.php`

**Estimated scope:** Small, 2 files, 20-30 menit.

## Task 2: Ganti dua tabel menjadi satu tabel expandable

**Description:** Hapus query dan panel downtime terpisah, ringkas tabel sesi untuk pemindaian cepat, lalu tampilkan data produksi, OEE, dan seluruh downtime pada panel detail di bawah sesi yang dipilih.

**Acceptance criteria:**
- [x] Halaman hanya merender satu tabel riwayat dengan satu baris ringkas per sesi completed.
- [x] Tombol `Lihat detail` membuka identitas, waktu, output aktual/teoritis/selisih, empat metrik OEE, peringatan, jumlah dan total downtime, serta seluruh detail kendala.
- [x] Sesi tanpa downtime dan snapshot OEE legacy memakai pesan yang jelas; tombol dapat dipakai keyboard dan memiliki atribut ARIA yang benar.

**Verification:**
- [x] Jalankan `php artisan test --filter=HistoryReportingTest`.
- [x] Jalankan `composer test`, `vendor/bin/pint --test`, dan `npm run build`.
- [x] Periksa kontrak aksesibilitas, filter, pagination, buka/tutup beberapa detail, dan export.

**Dependencies:** Task 1

**Files likely touched:**
- `app/Http/Controllers/HistoryController.php`
- `resources/views/history.blade.php`
- `tests/Feature/HistoryReportingTest.php`

**Estimated scope:** Medium, 3 files, 45-60 menit.

## Checkpoint: Ready for Implementation Review

- [ ] Kedua task memenuhi acceptance criteria dan tidak mengubah database atau export.
- [ ] Test terfokus, suite lengkap, Pint, dan build lulus.
- [ ] Runtime desktop/mobile serta akses keyboard lulus tanpa error console.
- [ ] Human menyetujui plan sebelum Task 1 dimulai.
