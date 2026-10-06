# Implementation Plan: OEE Preview

## Overview

Tambahkan panel visual OEE dummy pada layar kontrol sesi tanpa mengubah database, API, state machine, atau laporan. Implementasi mengikuti `docs/specs/SPEC-oee-preview.md`.

## Architecture Decisions

- Tempatkan panel sekali di area sesi aktif agar otomatis terlihat pada state `running`, `downtime`, dan `completed`.
- Gunakan markup Blade statis karena nilai hanya untuk preview desain; tidak perlu state JavaScript, helper, endpoint, atau dependency baru.
- Tampilkan hanya label metrik dan angka agar panel ringkas.

## Dependency Graph

```text
View contract test → OEE preview panel → focused/full verification
```

## Task List

### Phase 1: Preview Slice

- [x] Task 1: Render panel OEE dummy pada sesi aktif

### Checkpoint: Complete

- [x] Test dashboard dan suite lengkap lulus.
- [x] Frontend build dan format check lulus.
- [x] Panel terbaca pada state running, downtime, dan completed tanpa mengubah alur kontrol.

## Risks and Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Angka preview dianggap aktual | Medium | Nilai tetap dibatasi pada view dan tidak disimpan atau diekspor |
| Panel terduplikasi per state | Low | Letakkan satu panel pada container sesi aktif bersama |
| Perubahan visual mengganggu tablet | Low | Gunakan grid responsif dan komponen Tailwind yang sudah tersedia |

## Parallelization

Tidak diparalelkan; hanya satu slice dua file dan estimasi implementasi 20–30 menit.

## Open Questions

- Tidak ada.
