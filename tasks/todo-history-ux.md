# Tasks: Penyempurnaan Riwayat Produksi

## Task 1: Kunci kontrak UX riwayat

**Description:** Tambahkan regression test untuk memastikan hanya tombol Lihat Detail yang membuka rincian, copy baru tampil, tombol sekunder solid, dan badge kendala tidak pecah.

**Acceptance criteria:**

- [x] View tidak mengandung row-click, keyboard navigation handler, `tabindex`, atau `cursor-pointer` pada baris sesi.
- [x] Copy `Riwayat Produksi`, `Kendala Produksi`, dan instruksi tombol detail terverifikasi.
- [x] Komponen tombol dan badge kendala memakai class palette serta `whitespace-nowrap` yang tepat.

**Verification:**

- [x] Test RED lalu GREEN: `php artisan test --filter=HistoryReportingTest`.
- [x] Test komponen: `php artisan test --filter=ProfileTest`.

**Dependencies:** None

**Files likely touched:**

- `tests/Feature/HistoryReportingTest.php`
- `tests/Feature/ProfileTest.php`

**Estimated scope:** Small, 2 files, 15-20 minutes.

## Task 2: Terapkan tampilan riwayat yang konsisten

**Description:** Ubah variant tombol sekunder menjadi solid, hapus interaksi baris, rapikan badge kendala, dan ganti copy sesuai spec.

**Acceptance criteria:**

- [x] Reset dan Lihat Detail menggunakan warna solid dengan kontras teks yang memenuhi kebutuhan baca.
- [x] Baris tabel bersifat informasional; tombol Lihat Detail menjadi satu-satunya kontrol navigasi.
- [x] Jumlah dan durasi kendala tetap satu baris pada seluruh breakpoint yang ditargetkan.

**Verification:**

- [x] `php artisan view:cache`.
- [x] `npm run build`.
- [x] `php artisan test`.
- [x] `git diff --check`.
- [ ] Runtime check pada 320px, 768px, 1024px, dan 1440px.

**Dependencies:** Task 1

**Files likely touched:**

- `resources/views/components/action-button.blade.php`
- `resources/views/history.blade.php`

**Estimated scope:** Small, 2 files, 20-30 minutes.
