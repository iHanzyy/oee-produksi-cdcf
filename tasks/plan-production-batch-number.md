# Implementation Plan: Nomor Batch Produksi

## Overview

Tambahkan nomor batch manual ke alur produksi tanpa mengubah state machine sesi. Perubahan dimulai dari kontrak request dan penyimpanan, lalu diteruskan ke dashboard Operator, monitoring SPV/Manager, riwayat downtime, dan export Excel. Task list disimpan terpisah di `tasks/todo-production-batch-number.md` agar plan lama tidak ditimpa.

## Approved Source

- `docs/specs/SPEC-production-batch-number.md`

## Architecture Decisions

- Gunakan kolom nullable `production_sessions.batch_number` untuk menjaga histori lama, tetapi wajibkan input pada request start baru.
- Simpan teks yang sudah di-trim dengan batas 255 karakter; tidak ada unique index atau format khusus.
- Tambahkan `batchNumber` ke payload sesi yang sudah ada; jangan membuat endpoint, service, atau model baru.
- Tampilkan `-` untuk record lama tanpa nomor batch.
- Tidak menambah filter batch, generator otomatis, atau fitur edit setelah sesi dimulai.

## Dependency Graph

```text
Test callers siap menerima kontrak baru
    |
    v
Schema + validasi + penyimpanan + payload
    |
    +--> Dashboard Operator
    |
    +--> Monitoring, riwayat, dan export
```

Task 3 dan Task 4 dapat dikerjakan paralel setelah Task 2 karena tidak berbagi file.

## Task List

### Phase 1: Contract Foundation

- [x] Task 1: Siapkan caller test untuk kontrak start baru.
- [x] Task 2: Simpan dan kirim nomor batch melalui API sesi.

### Checkpoint: Contract

- [x] Request tanpa nomor batch ditolak dengan HTTP 422.
- [x] Nomor batch di-trim, tersimpan, dan muncul pada payload sesi.
- [x] Nomor batch duplikat tetap diizinkan.
- [x] Test start, downtime, dan end-to-end lulus.

### Phase 2: User Surfaces

- [x] Task 3: Tambahkan input dan tampilan nomor batch pada dashboard Operator.
- [x] Task 4: Tambahkan nomor batch pada monitoring, riwayat, dan export.

### Checkpoint: Complete

- [x] Nomor batch terlihat dari start sampai laporan Excel.
- [x] Histori lama tanpa batch tetap dapat dibuka sebagai `-`.
- [x] Test terfokus dan suite penuh lulus.
- [x] Laravel Pint dan frontend build lulus.
- [x] Alur runtime start, aktif, dan monitoring SPV diverifikasi; selesai, riwayat, dan export diverifikasi melalui feature test.

## Risks and Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Field wajib mematahkan caller start lama | High | Task 1 memperbarui semua caller test sebelum validasi diwajibkan. |
| Histori lama tidak memiliki batch | Medium | Migration nullable dan UI/export memakai fallback `-`. |
| Urutan kolom export bergeser | Medium | Test heading dan mapping berdasarkan posisi kolom. |
| Grid dashboard padat pada tablet | Low | Pakai pola kartu/grid yang sudah ada dan cek viewport tablet. |
| Payload dashboard dan history berbeda | Medium | Keduanya memakai nama properti `batchNumber` dan test masing-masing. |

## Open Questions

- Tidak ada sebelum implementation.
