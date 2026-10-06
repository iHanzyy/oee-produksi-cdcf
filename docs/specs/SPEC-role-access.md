# Spec: Role Access

Module ID: `role-access`

## Objective

Membatasi fitur berdasarkan role tanpa membuat alur login berbeda. Operator hanya mengakses kontrol produksi; SPV dan Manager hanya mengakses Riwayat & Arsip.

## Engineering Contract

Tech stack, commands, structure, style, testing, dan boundaries mengikuti `ENGINEERING-CONTRACT.md`.

## Requirements

- Role resmi adalah `operator`, `spv`, dan `manager`.
- Satu akun Operator boleh memiliki banyak sesi login pada tablet berbeda.
- Login sukses mengarahkan Operator ke dashboard kontrol dan SPV/Manager ke riwayat.
- Route kontrol produksi hanya menerima role Operator.
- Route riwayat dan export hanya menerima role SPV atau Manager.
- Navigasi hanya menampilkan fitur yang boleh diakses role tersebut.
- Route dan tampilan registrasi publik dihapus; akun dibuat manual.
- Akses langsung ke route yang tidak sesuai role menghasilkan HTTP 403.

## Project Structure

- Middleware role berada di `app/Http/Middleware/` dan didaftarkan pada bootstrap Laravel.
- Redirect autentikasi tetap berada pada controller autentikasi.
- Route role dikelompokkan pada `routes/web.php`.

## Success Criteria

- Operator tidak dapat membuka riwayat atau export.
- SPV/Manager tidak dapat membuka atau memanggil endpoint kontrol produksi.
- Registrasi publik mengembalikan 404.
- Login dan logout tetap bekerja pada beberapa tablet dengan akun Operator yang sama.
- Feature test mencakup ketiga role dan akses langsung ke seluruh kelompok route.

## Out of Scope

- UI pembuatan akun, reset role, atau pengelolaan permission granular.

## Open Questions

- Tidak ada sebelum planning.
