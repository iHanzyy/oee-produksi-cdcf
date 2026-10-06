# Spec: Downtime Reporting

Module ID: `downtime-reporting`

## Objective

Memberi SPV dan Manager tampilan baca untuk memantau serta mengekspor histori downtime, dengan hasil filter yang konsisten antara layar dan Excel.

## Engineering Contract

Tech stack, commands, structure, style, testing, dan boundaries mengikuti `ENGINEERING-CONTRACT.md`.

## Requirements

- Operator tidak dapat membuka halaman riwayat atau export.
- SPV dan Manager memiliki akses baca yang sama pada tahap ini.
- Pertahankan filter tanggal mulai, tanggal selesai, area, dan mesin.
- Validasi tanggal, urutan rentang, serta ID area/mesin sebelum query dijalankan.
- Mesin pada filter dapat dibatasi mengikuti area tanpa mengubah hasil server-side.
- Query menggunakan eager loading dan pagination; parameter filter bertahan saat berpindah halaman.
- Export memakai filter yang sama persis dengan halaman.
- Export menggunakan query/chunking, bukan memuat seluruh dataset ke memori.
- Record aktif ditandai “Berlangsung”; record legacy tidak lengkap pada sesi completed ditandai “Data tidak lengkap”.
- Waktu layar dan Excel ditampilkan dalam zona `Asia/Jakarta`.
- Upgrade Laravel Excel dan PhpSpreadsheet ke versi kompatibel yang tidak memiliki advisory terkunci saat ini.

## Project Structure

- Query filter dipusatkan pada scope/model agar controller dan export tidak menduplikasi aturan.
- Export tetap berada di `app/Exports/` dan file duplikat yang salah lokasi ditangani pada task cleanup terpisah.
- View utama tetap `resources/views/history.blade.php`; view histori lama yang tidak dipakai tidak digunakan sebagai pola baru.

## Success Criteria

- Operator menerima 403 pada halaman dan export.
- SPV/Manager melihat hasil dan pagination yang sama untuk filter identik.
- File Excel memiliki jumlah baris dan urutan yang sama dengan query tanpa pagination.
- Filter satu sisi atau rentang tanggal terbalik menghasilkan pesan validasi.
- Query tidak menghasilkan N+1 dan kolom filter utama memiliki indeks yang sesuai.
- `composer audit --locked` tidak melaporkan advisory pada dependency export yang dipakai.

## Out of Scope

- Grafik analitik, KPI OEE, approval downtime, dan dashboard Manager terpisah.

## Open Questions

- Tidak ada sebelum planning.
