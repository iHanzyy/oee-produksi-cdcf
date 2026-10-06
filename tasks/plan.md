# Implementation Plan: Kontrol Produksi Multi-Tablet

## Overview

Implementasi mengikuti capability map yang disetujui: akses role dan master data menjadi fondasi, dilanjutkan kontrol sesi produksi, downtime, lalu pelaporan. Setiap tahap meninggalkan aplikasi dalam kondisi dapat diuji dan tidak mereset data historis.

## Approved Sources

- `CAPABILITY-MAP.md`
- `docs/ideas/kontrol-produksi-multi-tablet.md`
- `docs/specs/ENGINEERING-CONTRACT.md`
- `docs/specs/SPEC-*.md`

## Architecture Decisions

- Gunakan middleware role sederhana; tidak menambah package permission.
- Gunakan cookie perangkat terenkripsi otomatis. Database hanya menyimpan hash token kontrol.
- Gunakan transaksi dan row lock mesin agar request pertama menang tanpa distributed lock.
- Gunakan Blade + Alpine untuk satu layar adaptif dan searchable combobox.
- Gunakan endpoint JSON kecil untuk polling lima detik; tidak memakai WebSocket.
- Simpan waktu secara konsisten dan tampilkan `Asia/Jakarta` menggunakan timestamp ber-offset.
- Pertahankan data lama; master lama dinonaktifkan dari input tetapi tetap dapat dibaca histori.
- Pusatkan filter downtime pada query scope agar layar dan export memakai aturan yang sama.

## Dependency Graph

```text
Task 1 role enforcement ─┬─> Task 2 auth surface
                        └─> Task 6 session control

Task 3 master schema ─> Task 4 master seed/UI data ─> Task 5 session ownership schema
                                                     └─> Task 6 session control

Task 6 session control ─> Task 7 adaptive UI ─> Task 8 downtime + summary

Task 8 downtime + summary ─┬─> Task 10 reporting
Task 9 dependency security ─┘

Tasks 1-10 ─> Task 11 integration verification
```

## Task List

Rincian acceptance criteria, verification, dependencies, file, dan estimasi berada di `tasks/todo.md`.

### Phase 1A: Access Foundation

- [ ] Task 1: Enforce role access and redirects
- [ ] Task 2: Close registration and align auth tests/navigation

### Checkpoint: Access

- [ ] Role matrix and authentication surface pass focused tests.
- [ ] Review access behavior before schema changes.

### Phase 1B: Master Foundation

- [ ] Task 3: Add production master schema and models
- [ ] Task 4: Seed approved production master data

### Checkpoint: Master Data

- [ ] Master seeder runs twice without duplicates.
- [ ] No existing production session or downtime row is deleted.

### Phase 2: Production Control

- [ ] Task 5: Add browser ownership and session schema
- [ ] Task 6: Implement atomic start, takeover, and polling
- [ ] Task 7: Build the adaptive Operator screen
- [ ] Task 8: Implement downtime transitions and final summary

### Checkpoint: Production Control

- [ ] Two different machines run concurrently from the shared Operator account.
- [ ] Competing starts on one machine create exactly one active session.
- [ ] Refresh, login ulang, polling, and takeover work end-to-end.
- [ ] Running → downtime → running → completed works without orphan logs.

### Phase 3: Reporting and Release Gate

- [x] Task 9: Upgrade vulnerable export dependencies
- [x] Task 10: Align history, filters, and export
- [x] Task 11: Run integration, responsive, and documentation verification

### Checkpoint: Complete

- [x] All relevant tests pass and the stale baseline failures are resolved.
- [x] Frontend production build succeeds.
- [x] Scoped Pint checks pass; unrelated user change in `public/index.php` remains untouched.
- [x] Composer/npm audits have no untriaged advisory affecting used paths.
- [x] Desktop and tablet flows pass manual runtime verification.

## Parallelization

- Tasks 1 and 3 can be developed independently after their contracts are fixed.
- Tasks 2 and 4 follow their respective foundation task.
- Tasks 5-8 are sequential because they share the production state contract.
- Task 9 can run beside Task 8; Task 10 waits for both.
- Task 11 runs after all implementation tasks.

## Risks and Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Shared account cannot prove human identity | Medium | Store free-text Operator name and state clearly that it is an operational label |
| Tablet/browser storage cleared | Medium | Offer takeover of an existing machine session without creating a new session |
| Simultaneous start requests | High | Lock the machine row inside one database transaction and return HTTP 409 to the loser |
| Browser lama acts after takeover | High | Verify controller-token hash on every mutation and expose ownership through polling |
| Legacy open downtime logs | Medium | Label completed legacy records as incomplete; never present them as currently running |
| Current export dependencies are vulnerable | High | Upgrade before reporting sign-off and require a clean locked audit |
| Existing tests and formatting are already red | Medium | Record baseline, fix tests in owning task, format only touched files, report unrelated residue |

## Open Questions

- None. Any scope change updates the approved specs before implementation.

## Estimated Effort

- Foundation: 90–120 minutes.
- Production control: 180–240 minutes.
- Reporting and release verification: 90–120 minutes.
- Total implementation estimate: 6–8 focused hours.
