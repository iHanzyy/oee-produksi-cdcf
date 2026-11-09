# Implementation Plan: OEE Per Sesi Produksi

## Overview

Implementasi mengikuti `docs/specs/SPEC-oee-session.md`: tambahkan master OEE per produk dan mesin, hitung snapshot saat sesi selesai, tampilkan hasil riil kepada Operator, lalu tambahkan tabel Riwayat Produksi/OEE terpisah untuk SPV dan Manager. Laporan downtime tetap utuh.

## Approved Sources

- `docs/ideas/oee-per-session.md`
- `docs/specs/SPEC-oee-session.md`
- `docs/specs/ENGINEERING-CONTRACT.md`

## Architecture Decisions

- Gunakan tabel `oee_standards` dengan pasangan unik produk + mesin; pasangan tanpa standar aktif ditolak saat start.
- Simpan input, standar, waktu operasi, dan empat hasil sebagai snapshot nullable pada `production_sessions`.
- Hitung OEE hanya di server dalam transaksi penyelesaian sesi; UI hanya mengirim output aktual kg.
- Tampilkan satu baris per sesi completed pada tabel OEE baru; jangan menempelkan hasil ke downtime log.
- Pertahankan export downtime dan sesi lama tanpa OEE dengan fallback tanda pisah.

## Dependency Graph

```text
Schema dan master standar
    |
    v
Validasi start
    |
    v
Perhitungan dan snapshot saat selesai
    |
    +--> Ringkasan Operator
    |
    +--> Riwayat Produksi/OEE
    |
    v
Verifikasi integrasi
```

## Task List

### Phase 1: Foundation

- [x] Task 1: Tambahkan schema dan master standar OEE.
- [x] Task 2: Tolak start tanpa standar aktif.

### Checkpoint: Foundation

- [x] Migration berjalan dari database kosong dan database existing.
- [x] Kombinasi valid dapat start; kombinasi tanpa standar menerima pesan yang disepakati.
- [x] Tidak ada konversi kg atau jam/kg yang ditebak.

### Phase 2: Calculation

- [x] Task 3: Hitung dan simpan snapshot OEE saat sesi selesai.

### Checkpoint: Calculation

- [ ] Rumus terbukti dengan waktu tetap, downtime, output nol, dan nilai di atas 100%.
- [ ] Penyelesaian sesi tetap atomik dan state machine lama tidak berubah.

### Phase 3: User Surfaces

- [x] Task 4: Tambahkan input dan ringkasan OEE Operator.
- [x] Task 5: Tambahkan tabel Riwayat Produksi/OEE.

### Checkpoint: Complete

- [ ] Operator melihat hasil riil setelah selesai tanpa angka dummy.
- [ ] SPV/Manager melihat satu baris OEE per sesi dan tabel downtime tetap benar.
- [ ] Test, format, build, dan runtime tablet/desktop lulus.

## Risks and Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Master jam/kg belum lengkap | High | Aktifkan hanya pasangan yang sudah diverifikasi; blok pasangan lain |
| OEE ditempel ke downtime | High | Query sesi completed pada tabel terpisah |
| Standar berubah setelah produksi | High | Simpan nilai standar sebagai snapshot saat selesai |
| Nilai lebih dari 100% disalahartikan | Medium | Tampilkan nilai mentah dengan peringatan jelas |
| Dua paginator saling mengubah halaman | Medium | Gunakan nama parameter pagination berbeda |

## Parallelization

Task 1–3 harus berurutan. Task 4 dan Task 5 dapat dimulai setelah Task 3, tetapi dikerjakan berurutan agar perubahan payload dan query mudah direview. Estimasi total 4–5 jam termasuk verifikasi.

## Open Questions

- Data SPV sudah memverifikasi 13 produk untuk Mixing, Stripper Indomach, dan kedua Cetak Gylongli. Blister serta produk berfase tetap diblokir sampai teori kg per pasangan tersedia.

