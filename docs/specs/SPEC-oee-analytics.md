# Specification: Analisis OEE

## Intent

Menyediakan halaman analitik terpisah untuk Supervisor, Manager, dan akun AMD yang memakai role `manager`. Halaman menampilkan rata-rata OEE seluruh sesi selesai sesuai filter, bukan rincian per sesi.

## User Experience

- Navbar pelaporan memiliki dua tujuan nyata: `Riwayat Produksi` dan `Analisis OEE`.
- Halaman tersedia pada `/oee` dan memakai filter tanggal, area, serta mesin yang sama dengan riwayat.
- Pilihan mesin mempersempit data setelah periode dan area dipilih.
- Donut utama menampilkan rata-rata OEE terhadap sisa menuju 100%.
- Availability, Quality, dan Performance tampil sebagai metrik pendukung terpisah, bukan irisan donut.
- Empty state menjelaskan bahwa tidak ada sesi OEE yang cocok dan meminta pengguna mengubah filter.

## Data Contract

- Hanya `production_sessions.status = completed` dengan `oee_percent` tidak null yang dihitung.
- Filter tanggal memakai `end_time` dan zona waktu Asia/Jakarta, sama seperti halaman riwayat.
- Rata-rata dihitung langsung oleh database untuk OEE, Availability, Quality, dan Performance.
- Nilai donut dibatasi secara visual pada rentang 0-100, tetapi nilai rata-rata asli tetap ditampilkan.

## Access and Routes

- `GET /oee`, route name `oee.index`.
- Akses mengikuti grup pelaporan `role:spv,manager`.
- Operator diarahkan kembali ke dashboard operator oleh middleware role yang ada.

## Design Contract

- Palette: `primary #1B365D`, `secondary #005A9C`, `accent #60C1E5`, `background #A0E0EF`.
- Satu focal point: donut rata-rata OEE.
- ENERGY 2, RHYTHM 2, MOTION 1.
- Gunakan SVG native agar tidak menambah dependency chart.
- Mobile menumpuk donut dan metrik; tablet/desktop memakai dua kolom.

## Boundaries

- Always: gunakan validasi `HistoryFilterRequest` dan komponen tombol/nav yang ada.
- Never: ubah rumus OEE, menampilkan sesi aktif, atau menambah dependency chart.
- Deferred: tren waktu, perbandingan antarmesin, target OEE, dan export analitik.

## Success Criteria

- Filter mesin menghasilkan rata-rata hanya dari mesin tersebut.
- Sesi aktif, sesi tanpa OEE, dan sesi di luar filter tidak masuk agregasi.
- Navbar menunjukkan state aktif yang benar pada kedua halaman.
- Empty state, keyboard focus, dan layout mobile tersedia.

## Open Questions

Tidak ada.
