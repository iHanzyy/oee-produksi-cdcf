# Kontrol Produksi Multi-Tablet

## Problem Statement

Bagaimana membuat kontrol produksi yang cepat pada lima tablet dengan satu akun Operator bersama, tanpa risiko satu mesin menjalankan lebih dari satu sesi aktif?

## Recommended Direction

Gunakan satu halaman adaptif yang berubah mengikuti keadaan sesi: form mulai, produksi berjalan, downtime, lalu ringkasan selesai. Halaman memperbarui status melalui polling sekitar lima detik agar perubahan dan pengambilalihan sesi terlihat tanpa menambah infrastruktur WebSocket.

Browser mengingat sesi aktif secara otomatis. Jika tablet awal rusak atau data browser hilang, tablet lain dapat mengambil alih sesi aktif berdasarkan mesin tanpa membuat sesi baru. Server tetap menjadi sumber kebenaran dan menolak sesi baru ketika mesin sudah dipakai.

## Key Assumptions to Validate

- [ ] Polling sekitar lima detik cukup cepat untuk kebutuhan operasional.
- [ ] Koneksi jaringan lokal tersedia selama proses produksi.
- [ ] Satu browser hanya mengontrol satu sesi produksi pada satu waktu.
- [ ] Nama Operator bebas hanya menjadi catatan aktivitas, bukan bukti identitas petugas.
- [ ] Pengambilalihan sesi oleh tablet lain dapat dilakukan tanpa persetujuan SPV atau Manager.

## Master Data Awal

### Tablet Hormon

Produk:

- Novasteron
- Meprolut
- Norelut
- Etiro-N
- Norethisterone Tab 5 mg
- Luteron
- Andalut
- Bifertil
- Provula
- Ovulas
- Ovulet
- Clomifil
- Alyrenol
- Progeston
- Pregnolin
- Obstanon
- Prestrenol
- Maxinor
- Prinolas
- Pil KB Harmonies
- Trinordiol
- Dienogest

Mesin:

- Cetak Gylongli 1
- Cetak Gylonglu 2
- Stripper Indomach
- Blister DKPS 1
- Blister DKPS 2
- Mixing
- Coating Yenchen

### Data Dummy

- Tablet Non Hormon: `Produk Tablet Non Hormon 1`, `Mesin Tablet Non Hormon 1`
- Injeksi: `Produk Injeksi 1`, `Mesin Injeksi 1`
- Implant: `Produk Implant 1`, `Mesin Implant 1`
- Packing Hormon: `Produk Packing Hormon 1`, `Mesin Packing Hormon 1`

## MVP Scope

- Role Operator, SPV, dan Manager dengan akses berbeda.
- Produk dan mesin dapat dicari tetapi harus berasal dari master data yang valid.
- Kontrol sesi produksi, downtime, pengambilalihan sesi, dan ringkasan akhir.
- SPV dan Manager hanya dapat membuka Riwayat & Arsip.
- Registrasi publik ditutup; akun dikelola manual.

## Not Doing

- Perhitungan atau kartu OEE.
- WebSocket dan mode offline.
- UI manajemen akun atau master data.
- Identitas tablet yang harus dipilih Operator.
- Dashboard analitik baru.

## Confirmed Interaction Flow

1. Operator memilih area, mengisi nama petugas, lalu memilih produk dan mesin.
2. Sistem menolak permintaan jika mesin sudah memiliki sesi aktif.
3. Produksi berjalan dan halaman menampilkan area, Operator, produk, mesin, serta durasi.
4. Downtime mengikuti alur STOP MESIN, Selesai Downtime, isi form kendala, lalu Mulai Mesin.
5. Selesai Produksi menampilkan waktu mulai-selesai, durasi sesi, status downtime, jumlah downtime, dan total durasi downtime. Tombol OKE kembali ke form awal.
