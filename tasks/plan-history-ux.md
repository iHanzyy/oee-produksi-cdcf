# Implementation Plan: Penyempurnaan Riwayat Produksi

Source spec: `docs/specs/SPEC-history-ux.md`

## Overview

Menghapus interaksi ganda pada baris riwayat, menerapkan tombol solid melalui komponen bersama, merapikan informasi kendala, dan memperjelas copy tanpa mengubah query, filter, pagination, export, atau data OEE.

## Architecture Decisions

- Ubah variant `secondary` pada `x-action-button` menjadi tombol solid `#005A9C` agar Reset, Lihat Detail, dan aksi sekunder lain konsisten.
- Pertahankan URL detail hanya pada `x-action-button`; hapus handler Alpine, keyboard handler, `tabindex`, dan cursor interaktif dari baris tabel.
- Gunakan `whitespace-nowrap` pada badge kendala, bukan mengubah struktur data atau formatter durasi.

## Task List

### Phase 1: Regression Contract

- [x] Task 1: Kunci interaksi tunggal, copy, warna tombol, dan format kendala dalam feature test.

### Phase 2: UI Implementation

- [x] Task 2: Terapkan variant tombol solid dan rapikan tabel riwayat sesuai spec.

### Checkpoint: Complete

- [x] Focused tests lulus.
- [x] Blade berhasil dikompilasi dan frontend berhasil dibangun.
- [x] Full test suite lulus.
- [ ] Viewport 320px, 768px, 1024px, dan 1440px tidak memecah badge kendala atau tombol.
- [ ] Human menyetujui hasil sebelum merge atau deploy.

## Risks and Mitigations

| Risk | Mitigation |
|---|---|
| Perubahan variant `secondary` memengaruhi tombol kembali pada detail | Verifikasi halaman list dan detail melalui feature test serta runtime check |
| Badge kendala melebar pada viewport kecil | Tabel tetap memakai horizontal scroll; badge tidak memaksa wrapping |
| URL detail hilang saat row-click dihapus | Feature test memastikan tombol Lihat Detail tetap membawa filter dan pagination |
