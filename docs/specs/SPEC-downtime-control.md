# Spec: Downtime Control

Module ID: `downtime-control`

## Objective

Mencatat downtime sebagai state transition yang konsisten, mudah dioperasikan, dan tidak meninggalkan log terbuka setelah produksi selesai.

## Engineering Contract

Tech stack, commands, structure, style, testing, dan boundaries mengikuti `ENGINEERING-CONTRACT.md`.

## Requirements

- Hanya browser pengontrol sesi yang dapat memulai atau menyelesaikan downtime.
- STOP MESIN hanya valid dari status `running`.
- STOP membuat satu `downtime_log` terbuka dan mengubah sesi menjadi `downtime` dalam satu transaksi.
- Setelah STOP, layar menampilkan pemberitahuan merah bahwa mesin sedang downtime beserta timer.
- Tombol Selesai Downtime membuka form kendala existing: kategori, pelaksana, komponen, dan tindakan perbaikan.
- Submit form menutup log, menghitung `duration_seconds` di server, lalu mengubah sesi ke `running` dalam satu transaksi.
- Satu sesi tidak boleh memiliki lebih dari satu downtime terbuka.
- Takeover saat downtime mempertahankan log terbuka dan timer yang sama.
- Endpoint yang dipanggil pada state salah mengembalikan HTTP 409 tanpa mengubah data.
- Sesi dalam status downtime tidak dapat diselesaikan sebelum form kendala disimpan.

## Project Structure

- Form Request memvalidasi ID kategori/personnel dan panjang teks.
- State transition menggunakan row lock pada `production_sessions` dan downtime terbuka.
- Indeks mendukung pencarian downtime terbuka per sesi dan pengurutan histori.

## Success Criteria

- STOP ganda tidak membuat dua log terbuka.
- Resume ganda tidak menutup atau menghitung log dua kali.
- Durasi downtime dihitung dari waktu server, bukan nilai browser.
- Setelah resume, layar kembali ke state Running tanpa membuat sesi baru.
- Ringkasan produksi menghitung jumlah dan total downtime dari log sesi tersebut.
- Log lama yang terbuka pada sesi completed ditampilkan sebagai “Data tidak lengkap”, bukan “Berlangsung”.

## Out of Scope

- Edit atau hapus downtime historis dan approval perbaikan oleh SPV.

## Open Questions

- Tidak ada sebelum planning.
