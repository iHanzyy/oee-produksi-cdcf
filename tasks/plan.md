# Implementation Plan: Rumus OEE dan Input Output Produksi

Source: `docs/specs/SPEC-oee-formulas-and-output.md`

## Overview

Ganti kontrak OEE lama yang berbasis kilogram dengan standar generik per produk-mesin, lalu alirkan snapshot standar, input Total/Reject, rumus per jenis proses, dan satuan dinamis sampai dashboard serta riwayat. Perubahan tetap additive untuk schema agar sesi legacy dan pekerjaan history-detail yang belum di-commit tidak rusak.

## Architecture Decisions

- Tambah field generik pada `oee_standards` dan `production_sessions`; jangan hapus field `_kg`/`_pcs` legacy.
- Gunakan dua jenis proses saja: `unit_cycle` dan `yield_ratio`; tidak membuat class kalkulator karena rumus hanya dipakai saat completion.
- Simpan planned time pada sesi saat start dan snapshot standar lain saat completion.
- Hitung Good Output di server dari Total dikurangi Reject; frontend hanya memberi preview.
- Gunakan field output decimal yang sama untuk unit dan kg; UI memakai langkah `1` untuk unit serta `0.001` untuk kg.

## Dependency Graph

```text
Task 1 schema/model contract
    -> Task 2 master standards
        -> Task 3 completion formulas
            -> Task 4 operator UI
                -> Task 5 history compatibility
```

## Task List

Rincian acceptance criteria dan verifikasi berada di `tasks/todo.md`.

### Phase 1: Data Foundation

- [x] Task 1: Tambahkan kontrak schema dan model OEE generik
- [x] Task 2: Seed standar produk-mesin dari gambar

### Checkpoint: Data Foundation

- [x] Migration berjalan pada database kosong dan database existing.
- [x] Seeder dua kali menghasilkan 29 produk, 11 mesin, dan 72 standar tanpa duplikasi.
- [x] Data session legacy tetap tersedia.

### Phase 2: Calculation Slice

- [x] Task 3: Terapkan input, validasi, snapshot, dan rumus OEE baru

### Checkpoint: Calculation

- [x] Test membuktikan RED lalu GREEN untuk `unit_cycle` dan `yield_ratio`.
- [x] Reject di atas total dan operating time nonpositif ditolak tanpa menyelesaikan sesi.
- [x] Nilai di atas 100% tetap disimpan dan diberi peringatan.

### Phase 3: User Flow and Reporting

- [x] Task 4: Ubah dialog selesai produksi menjadi Total dan Reject bersatuan dinamis
- [x] Task 5: Tampilkan snapshot output baru tanpa merusak history legacy

### Checkpoint: Complete

- [x] Focused tests, suite lengkap, scoped Pint, dan frontend build lulus.
- [x] Runtime flow unit-cycle dan yield-ratio lulus pada viewport tablet.
- [x] Perubahan history-detail yang sudah ada tetap bekerja.

## Risks and Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Nama field legacy menyebut kg/pcs | Medium | Tambah field generik; jangan reinterpretasi atau hapus data lama |
| Standard berubah saat sesi berjalan | Medium | Snapshot planned time saat start dan standard lainnya saat completion |
| Downtime melebihi planned time | High | Tolak completion dan pertahankan sesi running |
| Salah satuan membuat Performance salah besar | High | Simpan `output_unit`, tampilkan pada input, dan test setiap jenis proses |
| File history sedang memiliki perubahan user | High | Edit hanya blok output/OEE terkait dan pertahankan diff lain |

## Parallelization

Tidak diparalelkan. Lima task menyentuh kontrak data yang sama dan harus berjalan berurutan agar setiap checkpoint dapat diuji.

## Open Questions

- Tidak ada.
