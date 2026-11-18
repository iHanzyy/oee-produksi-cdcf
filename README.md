# OEE Produksi Catur

Aplikasi Laravel untuk mengendalikan sesi produksi, mencatat waktu henti, dan menyajikan laporan OEE.

## Persyaratan

- PHP 8.2 atau versi yang lebih baru
- Composer 2
- Node.js dan npm
- MySQL untuk lingkungan produksi

## Pengembangan

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
npm ci
composer run dev
```

Akun bawaan dan data presentasi hanya tersedia di lingkungan selain produksi.

## Persiapan Produksi

1. Salin `.env.production.example` menjadi `.env`, lalu isi `APP_KEY`, alamat HTTPS, dan kredensial basis data.
2. Pastikan akar dokumen peladen web mengarah ke direktori `public`, bukan akar proyek.
3. Pasang dependensi dan bangun aset.

```powershell
composer install --no-dev --classmap-authoritative
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --class=ProductionMasterSeeder --force
php artisan optimize
```

Jangan jalankan `UserSeeder` atau `PresentationHistorySeeder` di produksi. Buat akun produksi dengan kata sandi unik yang kuat dan ganti seluruh kata sandi akun bawaan sebelum peluncuran.

## Pemeriksaan Rilis

```powershell
php artisan test
vendor/bin/pint --test
npm run build
composer audit --locked
npm audit --omit=dev --audit-level=high
php artisan about --only=environment
php artisan migrate:status
```

Hasil `php artisan about --only=environment` harus menunjukkan `production` dan mode awakutu harus nonaktif. HTTPS wajib digunakan agar kuki `Secure` dan HSTS berfungsi.

## Penerapan dan Pemulihan

- Cadangkan basis data sebelum migrasi atau pemutakhiran data master.
- Simpan artefak rilis sebelumnya agar pemulihan dapat dilakukan tanpa membangun ulang.
- Setelah penerapan, periksa `/up`, alur masuk, kendali sesi produksi, riwayat, ekspor, galat aplikasi, dan waktu tanggap.
- Pulihkan versi sebelumnya jika terjadi kerusakan data, celah keamanan, kenaikan galat dua kali lipat, atau kenaikan latensi persentil ke-95 lebih dari 50 persen.
