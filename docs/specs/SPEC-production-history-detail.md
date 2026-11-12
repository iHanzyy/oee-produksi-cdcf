# Spec: Riwayat Produksi Terpadu

Module ID: `production-history-detail`

## Objective

Menggabungkan riwayat OEE dan downtime menjadi satu tabel sesi produksi pada `/history` yang ketika diklik membuka halaman detail sesi `/history/{productionSession}` agar SPV dan Manager dapat membaca hasil produksi, membuka penyebab kendala, lalu menarik kesimpulan dari data satu sesi tanpa tampilan tabel yang menumpuk ke bawah.

Keberhasilan berarti satu baris mewakili satu sesi produksi selesai yang ringkas untuk dipindai dan mengarah ke halaman detail lengkap `/history/{productionSession}`.

## Tech Stack

- PHP 8.2+, Laravel 12, Eloquent, dan Blade.
- Alpine.js 3 untuk buka-tutup detail; tidak menambah dependency atau endpoint baru.
- Tailwind CSS 3 dan Vite 7.
- Kontrak umum mengikuti `docs/specs/ENGINEERING-CONTRACT.md`.

## Commands

- Test terfokus: `php artisan test --filter=HistoryReportingTest`
- Test lengkap: `composer test`
- Format check: `vendor/bin/pint --test`
- Frontend build: `npm run build`
- Runtime: buka halaman riwayat sebagai SPV dan Manager pada desktop serta mobile.

## Project Structure

- `app/Models/ProductionSession.php` memusatkan query sesi selesai beserta relasi detail downtime.
- `app/Http/Controllers/HistoryController.php` mengirim satu paginator riwayat sesi ke view; query export downtime tetap terpisah.
- `resources/views/history.blade.php` merender satu tabel riwayat dengan detail per sesi.
- `tests/Feature/HistoryReportingTest.php` memverifikasi kontrak data, filter, relasi, dan tampilan detail.

## Data Contract

- Satu baris tabel mewakili satu `production_sessions` berstatus `completed`.
- Filter tanggal mengikuti `end_time` sesi dalam zona `Asia/Jakarta`.
- Filter area dan mesin mengikuti relasi mesin pada sesi.
- Setiap sesi memuat seluruh `downtime_logs` miliknya, termasuk relasi kategori dan pelaksana, tanpa filter tanggal terpisah.
- Downtime dalam detail diurutkan dari waktu stop paling awal agar alur kendala mudah dibaca.
- Pagination tetap berbasis sesi dengan 15 sesi per halaman; pagination downtime terpisah dihapus.
- Sesi aktif tetap ditampilkan pada panel monitoring live dan tidak dicampur ke tabel riwayat.
- Export Excel tetap memakai laporan downtime dan filter yang sudah ada.

## User Interface

### Baris Ringkas

Tabel menampilkan data yang dibutuhkan untuk memindai hasil sesi:

- waktu selesai;
- identitas sesi: area, mesin, produk, batch, dan operator;
- output aktual dibanding output teoritis;
- OEE;
- indikasi data atau peringatan OEE;
- tombol `Lihat detail`.

Tombol detail memakai elemen `button`, `aria-expanded`, dan `aria-controls`. Baris tabel tidak dijadikan tombol agar navigasi keyboard dan pembaca layar tetap jelas. Beberapa detail boleh terbuka bersamaan untuk membandingkan sesi.

### Detail Sesi

Panel detail menampilkan data lengkap yang tersedia untuk membantu analisis:

- waktu mulai, waktu selesai, durasi sesi, waktu operasi, dan waktu rencana;
- output aktual, output teoritis, serta selisih output;
- Availability, Quality, Performance, dan OEE;
- seluruh peringatan persentase di atas 100%;
- jumlah kejadian downtime dan total durasi downtime;
- setiap downtime: waktu stop, waktu jalan kembali, durasi, kategori, tindakan perbaikan, komponen, pelaksana, dan status data.

Jangan memberi label otomatis seperti `baik`, `buruk`, atau `target tercapai` karena target OEE belum didefinisikan. Sistem hanya menampilkan fakta, kelengkapan data, dan peringatan yang sudah dapat dibuktikan.

### Empty dan Incomplete States

- Tidak ada sesi pada filter: `Belum ada sesi produksi selesai pada filter ini.`
- Sesi tanpa downtime: `Sesi ini selesai tanpa kendala yang tercatat.`
- Snapshot OEE lama tidak lengkap: `Data OEE sesi ini belum lengkap.`
- Nilai relasi atau field legacy yang kosong ditampilkan sebagai `-`, bukan ditebak.

## Performance and Security

- Gunakan eager loading untuk mesin, area, produk, downtime, kategori downtime, dan pelaksana agar tidak terjadi N+1 query.
- Hanya 15 sesi pada halaman aktif yang memuat detail downtime.
- Role akses tetap dibatasi middleware untuk SPV dan Manager.
- Seluruh teks database tetap dirender melalui escaping Blade; tidak menggunakan HTML mentah.
- Tidak menambah route, request mutasi, atau data sensitif baru.

## Testing Strategy

- Feature test memastikan satu baris per sesi selesai dan tabel downtime terpisah sudah tidak dirender.
- Feature test memastikan seluruh downtime sesi ikut dimuat dan relasi kategori serta pelaksana sudah eager loaded.
- Feature test memastikan filter tanggal memakai waktu selesai sesi serta filter area/mesin tetap bekerja.
- Feature test mencakup sesi dengan beberapa downtime, tanpa downtime, OEE di atas 100%, dan snapshot OEE legacy yang kosong.
- Pemeriksaan markup memastikan kontrol detail dapat difokuskan, memiliki `aria-expanded`, dan memiliki target detail yang unik.
- Runtime check memastikan detail dapat dibuka dan ditutup dengan mouse serta keyboard tanpa error console pada desktop dan mobile.

## Boundaries

- **Always:** tampilkan data sesi dan downtime yang tersimpan apa adanya, gunakan zona `Asia/Jakarta`, eager load seluruh relasi yang dirender, dan pertahankan akses SPV/Manager.
- **Ask first:** mengubah rumus OEE, menambah target penilaian, mengubah isi export, atau memasukkan sesi aktif ke riwayat.
- **Never:** menebak nilai yang kosong, memotong persentase ke 100%, merender input database sebagai HTML mentah, atau menghapus data historis.
- **Preserve:** monitoring live, filter yang ada, loading saat pencarian, route halaman, dan export downtime.

## Success Criteria

- Halaman hanya memiliki satu tabel riwayat sesi produksi, bukan tabel OEE dan downtime yang terpisah.
- SPV dan Manager dapat membuka satu sesi dan melihat identitas, waktu, output, empat metrik OEE, peringatan, ringkasan downtime, serta detail seluruh kendala.
- Sesi dengan lebih dari satu downtime tetap muncul sekali dan semua downtime tampil di detailnya.
- Data lama yang tidak lengkap tetap dapat dibaca dengan pesan yang jujur dan mudah dipahami.
- Filter, pagination sesi, monitoring live, loading pencarian, serta export downtime tetap bekerja.
- Test terfokus, suite lengkap, Pint, build frontend, dan pemeriksaan runtime lulus.

## Out of Scope

- Grafik, tren harian/bulanan, agregasi lintas sesi, dan kesimpulan otomatis.
- Target OEE atau klasifikasi performa baik/buruk.
- Halaman detail baru, modal, endpoint baru, serta perubahan database.
- Perubahan isi atau format export Excel.

## Open Questions

- Tidak ada sebelum planning.
