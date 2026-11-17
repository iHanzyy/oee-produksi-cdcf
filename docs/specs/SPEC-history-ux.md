# Spec: Penyempurnaan Riwayat Produksi

Module ID: `history-ux`

## Objective

Merapikan halaman riwayat untuk Supervisor, Manager, dan AMD agar setiap sesi mudah dipindai dan hanya memiliki satu jalur yang jelas menuju detail. Keberhasilan berarti tombol memiliki warna solid sesuai palette, baris tabel tidak lagi interaktif, informasi kendala tidak pecah, dan copy menjelaskan tindakan yang benar tanpa redundansi.

## Tech Stack

- PHP 8.2+, Laravel 12, Blade, dan Eloquent.
- Tailwind CSS 3 dengan token `primary`, `secondary`, `accent`, dan `background`.
- Komponen Blade `x-action-button` untuk aksi konsisten tanpa dependency baru.

## Commands

- Test terfokus: `php artisan test --filter=HistoryReportingTest`
- Test akses dan komponen: `php artisan test --filter=ProfileTest`
- Kompilasi Blade: `php artisan view:cache`
- Frontend build: `npm run build`
- Test lengkap: `php artisan test`
- Pemeriksaan diff: `git diff --check`

## Project Structure

- `resources/views/history.blade.php` memuat tabel riwayat, copy, kendala, dan tombol detail.
- `resources/views/components/action-button.blade.php` menyediakan variant tombol/link yang reusable.
- `tests/Feature/HistoryReportingTest.php` mengunci interaksi, copy, dan tampilan kendala.
- `tests/Feature/ProfileTest.php` mengunci palette dan komponen navigasi lintas role.

## Code Style

```blade
<x-action-button href="{{ $detailUrl }}" class="min-h-[44px] px-4 py-2 text-xs">
    Lihat Detail
</x-action-button>
```

- Gunakan komponen yang sudah ada; jangan duplikasi class tombol.
- Gunakan copy Bahasa Indonesia yang singkat, spesifik, dan berorientasi tindakan.
- Gunakan `whitespace-nowrap` hanya untuk informasi pendek yang tidak boleh pecah.

## Testing Strategy

- Feature test memastikan baris tabel tidak memiliki handler navigasi atau cursor interaktif.
- Feature test memastikan hanya tombol `Lihat Detail` yang memuat URL detail.
- Feature test memastikan copy baru dan format `{jumlah} kendala · {durasi}` tersedia.
- Build Tailwind memastikan class palette dan responsive berhasil dibuat.

## Boundaries

- Always: pertahankan filter, pagination, export, query, dan akses role yang ada.
- Ask first: perubahan struktur tabel, endpoint, atau data OEE.
- Never: jadikan seluruh baris interaktif, hapus peringatan OEE, atau tambah dependency UI.

## Success Criteria

- Tombol Reset memakai warna solid `secondary #005A9C` dan Lihat Detail memakai `primary #1B365D`.
- Klik, Enter, atau Space pada baris tabel tidak membuka detail.
- Kendala dan durasi tampil satu baris, termasuk nilai seperti `1 kendala · 60,1 menit`.
- Judul menjadi `Riwayat Produksi`, kolom menjadi `Kendala Produksi`, dan deskripsi hanya mengarahkan pengguna ke tombol detail.
- Tampilan tetap dapat digunakan pada viewport 320px, 768px, 1024px, dan 1440px.

## Out of Scope (Deferred)

- Analitik donut OEE — ditangani modul `oee-analytics`.
- Perubahan rumus atau snapshot OEE — data yang ada hanya ditampilkan.
- Perubahan export Excel — format export tidak terkait perbaikan interaksi tabel.

## Open Questions

Tidak ada.
