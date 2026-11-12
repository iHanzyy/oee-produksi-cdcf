# Implementation Plan: Riwayat Produksi Terpadu

## Overview

Gabungkan tabel OEE dan downtime menjadi satu daftar sesi produksi selesai. Setiap sesi dapat dibuka untuk melihat data produksi, komponen OEE, dan seluruh downtime tanpa route, dependency, atau perubahan database baru.

## Approved Source

- `docs/specs/SPEC-production-history-detail.md`

## Architecture Decisions

- Gunakan `ProductionSession` sebagai sumber utama karena satu baris mewakili satu sesi.
- Eager load downtime, kategori, dan pelaksana melalui query sesi agar detail tidak memicu N+1.
- Pertahankan pagination 15 sesi dan filter berdasarkan `end_time`, area, serta mesin.
- Gunakan Alpine.js yang sudah terpasang langsung di Blade; setiap sesi memiliki state detail sendiri.
- Gunakan tombol native dengan `aria-expanded` dan `aria-controls`; jangan membuat seluruh baris menjadi kontrol klik.
- Pertahankan export downtime, monitoring live, dan loading filter tanpa perubahan.

## Dependency Graph

```text
Task 1 data sesi lengkap -> Task 2 tabel terpadu -> Checkpoint verifikasi
```

## Task List

Rincian acceptance criteria, verifikasi, dependency, dan file berada di `tasks/todo-production-history-detail.md`.

### Phase 1: Data Readiness

- [x] Task 1: Muat detail downtime melalui query sesi

### Phase 2: Unified History UI

- [x] Task 2: Ganti dua tabel menjadi satu tabel expandable

### Checkpoint: Complete

- [x] Test terfokus dan suite lengkap lulus.
- [x] Pint dan frontend build lulus.
- [x] Detail memiliki tombol `<button type="button">` dengan `aria-expanded` dan `aria-controls` untuk mouse dan keyboard.
- [x] Monitoring live, filter, loading, pagination sesi, dan export tetap bekerja.

## Risks and Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Detail downtime menambah jumlah data pada halaman | Medium | Batasi ke 15 sesi per halaman dan eager load hanya relasi yang dirender |
| Sesi dengan banyak downtime membuat detail panjang | Low | Gunakan daftar detail di dalam panel yang tetap berada di sesi terkait |
| Downtime aktif hilang dari tabel riwayat | Medium | Pertahankan panel monitoring live; tabel riwayat hanya untuk sesi completed sesuai spec |
| Kontrol klik sulit dipakai keyboard | Medium | Gunakan button native, focus state, `aria-expanded`, dan target id unik |
| Data legacy kosong menyesatkan pengguna | High | Tampilkan `-` dan pesan `Data OEE sesi ini belum lengkap.` tanpa menebak nilai |

## Open Questions

- Tidak ada.

## Estimated Effort

- Task 1: 20-30 menit.
- Task 2: 45-60 menit.
- Verifikasi: 20-30 menit.
- Total: 85-120 menit.
