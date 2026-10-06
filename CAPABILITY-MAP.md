# Capability Map: Kontrol Produksi Multi-Tablet

| Module ID | Responsibility | Depends on |
|---|---|---|
| `role-access` | Role Operator, SPV, dan Manager; redirect berdasarkan role; registrasi publik ditutup | — |
| `production-master` | Master area, produk, dan mesin; pencarian pilihan; seed data aktual dan dummy | — |
| `production-session` | Mulai sesi, penguncian mesin, penyimpanan sesi browser, takeover, polling, dan penyelesaian produksi | `role-access`, `production-master` |
| `downtime-control` | STOP MESIN, status downtime, penyelesaian downtime, form kendala, dan mulai mesin kembali | `production-session` |
| `downtime-reporting` | Riwayat, filter, pagination, export, serta akses baca untuk SPV dan Manager | `role-access`, `downtime-control` |

Build order: `role-access` + `production-master` → `production-session` → `downtime-control` → `downtime-reporting`.
