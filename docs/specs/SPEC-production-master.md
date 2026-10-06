# Spec: Production Master

Module ID: `production-master`

## Objective

Menyediakan pilihan area, produk, dan mesin yang konsisten agar Operator dapat mencari cepat tanpa menyimpan typo sebagai master baru.

## Engineering Contract

Tech stack, commands, structure, style, testing, dan boundaries mengikuti `ENGINEERING-CONTRACT.md`.

## Requirements

- Tambahkan master `products` yang berelasi ke `areas`.
- Mesin tetap berelasi ke area dan memiliki status aktif/nonaktif untuk mempertahankan referensi histori lama.
- Nama produk unik di dalam area; nama mesin unik di dalam area.
- Pilihan produk dan mesin berubah mengikuti area terpilih.
- Komponen input mendukung pencarian teks dan dropdown, tetapi submit hanya menerima ID master aktif yang valid.
- Server memverifikasi produk dan mesin berasal dari area yang dikirim.
- Seeder idempotent menambahkan 22 produk dan 7 mesin Tablet Hormon yang disetujui.
- Seeder menambahkan masing-masing satu produk dan mesin dummy untuk empat area lainnya.
- Mesin lama yang hanya dipakai histori tidak dihapus; mesin tersebut dapat dinonaktifkan dari pilihan baru.

## Project Structure

- Model baru: `app/Models/Product.php`.
- Migration baru menambahkan tabel produk, constraint unik, indeks, dan status mesin.
- Seeder master terpisah dipanggil oleh `DatabaseSeeder`.
- Searchable combobox dibuat di Blade/Alpine tanpa dependency baru.

## Success Criteria

- Memilih area hanya menampilkan produk dan mesin area tersebut.
- Nilai ketik yang tidak cocok dengan master aktif ditolak dengan pesan validasi.
- Seeder dapat dijalankan berulang tanpa duplikasi.
- Histori yang menunjuk mesin lama tetap dapat ditampilkan.
- Test mencakup constraint unik dan validasi lintas-area.

## Out of Scope

- CRUD master data melalui UI dan import master dari spreadsheet.

## Open Questions

- Data dummy diganti melalui seeder/migration baru saat data aktual diterima.
