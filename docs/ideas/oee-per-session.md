# OEE Per Sesi Produksi

## Problem Statement

Bagaimana menampilkan OEE yang konsisten dan dapat diaudit untuk SPV dan manager tanpa menambah input manual operator selain output aktual dalam kg?

## Recommended Direction

Gunakan master standar per kombinasi produk dan mesin. Saat sesi diselesaikan, operator memasukkan output aktual dalam kg. Server menghitung waktu operasi dari durasi sesi dikurangi downtime, lalu menyimpan snapshot standar dan seluruh hasil OEE.

Halaman Riwayat Produksi menampilkan Output Aktual, Availability, Quality, Performance, OEE, dan Peringatan. Operator melihat ringkasan yang sama setelah sesi selesai.

## Key Assumptions to Validate

- [x] Waktu rencana tetap 480 menit.
- [x] Ideal cycle time menggunakan satuan jam/kg.
- [x] Kombinasi tanpa standar lengkap tidak dapat memulai sesi.
- [x] Nilai di atas 100% tetap disimpan dengan peringatan.
- [ ] Nilai jam/kg semua kombinasi aktif masih perlu dilengkapi.

## MVP Scope

- Master OEE per produk dan mesin.
- Input output aktual dalam kg saat menyelesaikan sesi.
- Perhitungan dan snapshot Availability, Quality, Performance, dan OEE.
- Ringkasan operator setelah produksi selesai.
- Enam kolom baru pada Riwayat Produksi.
- Sesi lama menampilkan tanda pisah.

## Not Doing

- Dashboard dan grafik OEE karena data tabel sudah memenuhi kebutuhan awal.
- Rekap per hari atau bulan karena belum diminta.
- Perhitungan reject atau scrap karena bukan bagian rumus yang disepakati.
- Layar CRUD master OEE karena master awal cukup melalui seeder.
- Kalkulasi ulang sesi lama karena merusak konsistensi audit.

## Open Questions

- Kombinasi produk dan mesin mana yang memiliki theoretical output kg dan ideal cycle time hours per kg lengkap?
- Siapa yang akan menyediakan konversi standar untuk data Stripper, Cetak, Blister, dan Granulasi yang masih berbeda satuan?
