# Implementation Plan: Analisis OEE

Source spec: `docs/specs/SPEC-oee-analytics.md`

## Overview

Menambah halaman `/oee` dengan filter bersama, agregasi database, donut SVG native, metrik pendukung, dan navbar dua section tanpa dependency baru.

## Architecture Decisions

- Tambahkan aksi `oee` pada `HistoryController` karena akses, filter, dan data source sama dengan riwayat.
- Ekstrak scope filter sesi selesai agar query riwayat dan agregasi memakai aturan tanggal, area, dan mesin yang sama.
- Ekstrak form filter Blade karena dipakai oleh halaman riwayat dan OEE.
- Gunakan SVG `pathLength=100` untuk donut; tidak memakai library chart.

## Task List

### Phase 1: Regression Contract

- [x] Task 1: Tambahkan feature test akses, navbar, filter, agregasi, dan empty state.

### Phase 2: Data and Route

- [x] Task 2: Tambahkan scope filter bersama, query agregasi, dan route `/oee`.

### Phase 3: Interface

- [x] Task 3: Tambahkan form filter reusable, navbar dua section, dan halaman donut responsif.

### Checkpoint: Complete

- [x] Focused tests lulus.
- [x] Blade cache dan frontend build lulus.
- [x] Full test suite lulus.
- [x] Runtime check pada 320px, 768px, 1024px, dan 1440px.

## Risks and Mitigations

| Risk | Mitigation |
|---|---|
| Query riwayat berubah saat scope diekstrak | Jalankan seluruh `HistoryReportingTest` dan suite penuh |
| Nilai OEE di atas 100 merusak donut | Clamp visual saja; tampilkan nilai asli |
| Navbar sempit pada mobile | Letakkan nav pada baris kedua di viewport kecil |

## Open Questions

Tidak ada.
