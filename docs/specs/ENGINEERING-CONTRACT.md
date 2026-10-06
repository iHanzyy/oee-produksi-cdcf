# Engineering Contract: Kontrol Produksi Multi-Tablet

## Tech Stack

- PHP 8.2+ dengan Laravel 12 dan autentikasi session-cookie.
- Blade, Alpine.js 3, Tailwind CSS 3, dan Vite 7.
- MySQL untuk produksi; SQLite in-memory untuk test.
- Laravel Excel 3 untuk ekspor XLSX.

## Commands

- Install backend: `composer install`
- Install frontend: `npm ci`
- Development: `composer dev`
- Test lengkap: `composer test`
- Test terfokus: `php artisan test --filter=<TestName>`
- Format check: `vendor/bin/pint --test`
- Frontend build: `npm run build`
- Dependency audit: `composer audit --locked && npm audit`

Baseline sebelum implementasi: 19 test lulus dan 6 test lama gagal karena ekspektasi root/profile tidak lagi sesuai route aplikasi. Setiap modul wajib memperbaiki test terkait, bukan menghapus test tanpa pengganti.

## Project Structure

- `app/Http/Controllers/` — orkestrasi request dan response.
- `app/Http/Middleware/` — pembatasan role.
- `app/Http/Requests/` — validasi input pada trust boundary.
- `app/Models/` — relasi dan query scope domain.
- `database/migrations/` — perubahan schema yang mempertahankan data lama.
- `database/seeders/` — master data idempotent.
- `resources/views/` — halaman Blade adaptif dan accessible.
- `tests/Feature/` — test route, role, state transition, dan konkurensi.

## Code Style

Gunakan controller tipis, validasi melalui Form Request, dan transaksi untuk state transition:

```php
DB::transaction(function () use ($machine): void {
    $lockedMachine = Machine::query()->lockForUpdate()->findOrFail($machine->id);

    abort_if($lockedMachine->hasActiveSession(), 409, 'Mesin sedang digunakan.');
});
```

- Ikuti Laravel conventions dan Laravel Pint.
- Gunakan nama class PascalCase; nama file harus cocok dengan nama class untuk PSR-4.
- Gunakan eager loading untuk relasi yang dirender.
- Jangan menambah dependency frontend untuk searchable dropdown; Alpine.js yang sudah ada cukup.

## Testing Strategy

- Feature test untuk role access dan setiap endpoint perubahan state.
- Database test untuk relasi area–produk–mesin dan penolakan mesin yang bentrok.
- Test state machine untuk running, downtime, resume, completed, dan takeover.
- Test export memastikan filter layar dan file sama.
- Browser check desktop dan viewport tablet/mobile untuk satu layar adaptif.

## Boundaries

- **Always:** validasi server-side, CSRF, transaksi untuk perubahan state, eager loading, test regresi, dan waktu tampilan `Asia/Jakarta`.
- **Ask first:** menghapus data historis, mengubah daftar master aktual, menambah dependency, atau mengubah kebijakan takeover.
- **Never:** reset database produksi, menampilkan angka OEE statis, mempercayai ID dari browser tanpa validasi, atau menyimpan token kontrol mentah di database.

## Shared Decisions

- Database menyimpan waktu secara konsisten; UI dan ekspor menampilkan zona `Asia/Jakarta` dengan timestamp ber-offset agar timer browser akurat.
- Cookie perangkat terenkripsi dibuat otomatis dan tidak memerlukan input Operator.
- Hash cookie perangkat menjadi bukti kontrol sesi; takeover mengganti pemilik kontrol secara atomik.
- Polling status berjalan setiap lima detik hanya ketika halaman kontrol aktif.
- Data historis lama dipertahankan; record lama yang tidak lengkap ditandai sebagai data tidak lengkap, bukan direkayasa.
