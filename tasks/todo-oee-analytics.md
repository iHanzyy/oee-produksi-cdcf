# Tasks: Analisis OEE

## Task 1: Kunci kontrak analitik

**Acceptance criteria:**

- [x] SPV dan Manager dapat membuka `/oee`; Operator tidak dapat membukanya.
- [x] Hanya sesi selesai dengan OEE terisi yang dihitung.
- [x] Filter tanggal, area, dan mesin memengaruhi agregasi.
- [x] Navbar dan empty state terverifikasi.

**Verification:**

- [x] RED lalu GREEN: `php artisan test --filter=OeeAnalyticsTest`.

## Task 2: Terapkan data dan UI

**Acceptance criteria:**

- [x] Donut menampilkan rata-rata OEE seluruh sesi hasil filter.
- [x] Availability, Quality, dan Performance tampil terpisah.
- [x] Form filter dipakai bersama oleh riwayat dan OEE.
- [x] Layout responsif dan semua kontrol memiliki tujuan nyata.

**Verification:**

- [x] `php artisan test --filter=HistoryReportingTest`.
- [x] `php artisan test --filter=ProfileTest`.
- [x] `php artisan view:cache`.
- [x] `npm run build`.
- [x] `php artisan test`.
- [x] `git diff --check`.
