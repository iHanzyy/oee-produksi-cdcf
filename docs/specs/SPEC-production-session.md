# Spec: Production Session

Module ID: `production-session`

## Objective

Memungkinkan banyak tablet memakai satu akun Operator untuk menjalankan mesin berbeda secara bersamaan, sambil memastikan satu mesin hanya memiliki satu sesi aktif.

## Engineering Contract

Tech stack, commands, structure, style, testing, dan boundaries mengikuti `ENGINEERING-CONTRACT.md`.

## Requirements

- Form mulai meminta area, nama Operator bebas, produk, dan mesin.
- `production_sessions` menyimpan `product_id`, `operator_name`, dan hash token pengontrol.
- Kolom `operator_id` lama dibuat nullable agar histori lama tetap kompatibel.
- Satu browser hanya memiliki satu sesi yang dikontrol pada satu waktu.
- Cookie perangkat terenkripsi dibuat otomatis dan bertahan setelah refresh, logout, serta login ulang.
- Start menggunakan transaksi dan `lockForUpdate()` pada mesin; request pertama menang dan request berikutnya menerima HTTP 409 dengan pesan jelas.
- Beberapa mesin berbeda boleh memiliki sesi aktif bersamaan.
- Dashboard tanpa sesi milik browser menampilkan form mulai dan daftar sesi aktif yang dapat diambil alih.
- Takeover mengganti hash pengontrol secara atomik tanpa membuat sesi baru.
- Browser lama mendeteksi kehilangan kontrol pada polling berikutnya dan tidak dapat mengubah sesi.
- Polling status setiap lima detik mengembalikan status, kepemilikan kontrol, waktu, dan ringkasan data mesin.
- Produksi hanya dapat diselesaikan dari status `running`; downtime harus diselesaikan terlebih dahulu.

## Adaptive Screen States

1. **Ready:** form mulai dan daftar sesi yang dapat dilanjutkan.
2. **Running:** area, Operator, produk, mesin, waktu mulai, durasi, STOP MESIN, dan Selesai Produksi.
3. **Downtime:** informasi downtime aktif dan tombol Selesai Downtime.
4. **Completed:** ringkasan waktu mulai-selesai, durasi sesi, ada/tidak downtime, jumlah downtime, dan total downtime.
5. **Acknowledged:** tombol OKE kembali ke state Ready.

## Project Structure

- Controller produksi menangani orkestrasi route dan response.
- Form Request terpisah memvalidasi start dan takeover.
- Model menyediakan scope `active()` dan relasi produk/mesin/downtime.
- Endpoint status menghasilkan JSON minimal untuk polling.

## Success Criteria

- Dua request serentak untuk mesin sama menghasilkan tepat satu sesi aktif.
- Dua tablet dapat menjalankan mesin berbeda menggunakan akun Operator yang sama.
- Refresh dan login ulang pada browser yang sama mengembalikan sesi yang dikontrol.
- Takeover mempertahankan ID, waktu mulai, downtime, dan data sesi lama.
- Browser lama tidak dapat STOP, resume, atau menyelesaikan sesi setelah takeover.
- Timer menggunakan timestamp ber-offset dan tidak bergeser tujuh jam.

## Out of Scope

- Perhitungan output, reject, performance, quality, availability, atau OEE.

## Open Questions

- Tidak ada sebelum planning.
