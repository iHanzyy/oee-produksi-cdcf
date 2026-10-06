# Task List: Kontrol Produksi Multi-Tablet

Sumber persetujuan: `CAPABILITY-MAP.md`, `docs/ideas/kontrol-produksi-multi-tablet.md`, dan `docs/specs/SPEC-*.md`.

## Task 1: Terapkan akses role dan redirect login

**Description:** Tambahkan middleware role sederhana, kelompokkan route berdasarkan akses, dan arahkan login sesuai role tanpa package permission tambahan.

**Acceptance criteria:**
- [ ] Operator hanya dapat membuka kontrol produksi; SPV dan Manager hanya dapat membuka riwayat/export.
- [ ] Akses langsung ke kelompok route role lain menghasilkan HTTP 403.
- [ ] Login Operator menuju dashboard, sedangkan SPV/Manager menuju riwayat.

**Verification:**
- [ ] Jalankan `php artisan test --filter=RoleAccessTest`.
- [ ] Jalankan `php artisan test --filter=AuthenticationTest`.
- [ ] Periksa manual redirect login untuk ketiga role.

**Dependencies:** None

**Files likely touched:**
- `app/Http/Middleware/EnsureRole.php`
- `bootstrap/app.php`
- `routes/web.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `tests/Feature/RoleAccessTest.php`

**Estimated scope:** Medium, 5 files, 35–45 minutes.

## Task 2: Tutup registrasi dan selaraskan auth surface

**Description:** Hapus route registrasi publik dan tautan fitur yang tidak tersedia, lalu ubah test Breeze lama agar sesuai dengan surface aplikasi aktual.

**Acceptance criteria:**
- [ ] `GET /register` dan `POST /register` menghasilkan 404 serta tidak membuat akun.
- [ ] Navigasi tidak menawarkan registrasi, profil, atau fitur yang tidak memiliki route aktif.
- [ ] Test root, registrasi, dan profil mencerminkan redirect/404 yang memang didukung aplikasi.

**Verification:**
- [ ] Jalankan `php artisan test tests/Feature/Auth/RegistrationTest.php tests/Feature/ProfileTest.php tests/Feature/ExampleTest.php`.
- [ ] Jalankan `php artisan route:list` dan pastikan route registrasi tidak ada.
- [ ] Periksa manual navigasi untuk Operator, SPV, dan Manager.

**Dependencies:** Task 1

**Files likely touched:**
- `routes/auth.php`
- `resources/views/layouts/navigation.blade.php`
- `tests/Feature/Auth/RegistrationTest.php`
- `tests/Feature/ProfileTest.php`
- `tests/Feature/ExampleTest.php`

**Estimated scope:** Medium, 5 files, 25–35 minutes.

## Checkpoint A: Access

- [ ] Test role dan autentikasi lulus.
- [ ] Registrasi publik tidak tersedia.
- [ ] Review perubahan akses sebelum schema domain diubah.

## Task 3: Tambahkan schema dan model master produksi

**Description:** Tambahkan master produk, status aktif mesin, constraint unik per area, relasi model, dan perbaiki nama file `Personnel` agar sesuai PSR-4 tanpa menghapus histori.

**Acceptance criteria:**
- [ ] Produk dan mesin unik di dalam area serta dapat dinonaktifkan tanpa menghapus referensi histori.
- [ ] Relasi area–produk–mesin bekerja dan validasi lintas-area dapat dibuktikan melalui test.
- [ ] Autoload model `Personnel` memenuhi PSR-4 pada Windows dan Linux.

**Verification:**
- [ ] Jalankan `php artisan test --filter=ProductionMasterTest`.
- [ ] Jalankan `php artisan migrate:fresh --env=testing` pada database test.
- [ ] Jalankan `composer dump-autoload --strict-psr`.

**Dependencies:** None

**Files likely touched:**
- `database/migrations/*_add_production_master_data.php`
- `app/Models/Product.php`
- `app/Models/Area.php`
- `app/Models/Machine.php`
- `app/Models/Personnel.php`

**Estimated scope:** Medium, 5 files, 40–50 minutes.

## Task 4: Seed master data yang disetujui

**Description:** Buat seeder idempotent untuk lima area, 22 produk dan 7 mesin Tablet Hormon, empat pasangan dummy, kategori downtime, serta akun awal tanpa menggandakan data.

**Acceptance criteria:**
- [ ] Seeder memasukkan tepat master data yang disetujui dan memakai role resmi `operator`, `spv`, `manager`.
- [ ] Menjalankan seeder dua kali tidak membuat duplikasi atau menghapus data lama.
- [ ] Mesin lama yang bukan pilihan baru tetap tersimpan dan dapat ditandai nonaktif.

**Verification:**
- [ ] Jalankan `php artisan test --filter=ProductionMasterSeederTest`.
- [ ] Jalankan `php artisan db:seed` dua kali pada database test dan bandingkan jumlah record.
- [ ] Periksa nama produk/mesin Tablet Hormon terhadap dokumen ide.

**Dependencies:** Task 3

**Files likely touched:**
- `database/seeders/ProductionMasterSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `tests/Feature/ProductionMasterSeederTest.php`

**Estimated scope:** Medium, 3 files, 30–40 minutes.

## Checkpoint B: Master Data

- [ ] Migration dan seeder lulus dari database kosong.
- [ ] Seeder kedua tidak mengubah jumlah master.
- [ ] Tidak ada sesi produksi atau downtime historis yang dihapus.

## Task 5: Tambahkan kepemilikan browser pada schema sesi

**Description:** Simpan produk, nama Operator bebas, dan hash token pengontrol pada sesi; buat cookie perangkat terenkripsi otomatis tanpa meminta identitas tablet dari Operator.

**Acceptance criteria:**
- [ ] `production_sessions` menyimpan `product_id`, `operator_name`, dan hash token; `operator_id` lama menjadi nullable.
- [ ] Cookie perangkat tersedia otomatis dan tetap sama setelah refresh, logout, serta login ulang di browser yang sama.
- [ ] Token mentah tidak pernah disimpan di database atau ditampilkan pada response.

**Verification:**
- [ ] Jalankan `php artisan test --filter=DeviceOwnershipTest`.
- [ ] Periksa migration upgrade mempertahankan record sesi lama.
- [ ] Periksa database test hanya berisi hash token kontrol.

**Dependencies:** Tasks 1, 3, and 4

**Files likely touched:**
- `database/migrations/*_add_control_fields_to_production_sessions.php`
- `app/Http/Middleware/EnsureDeviceToken.php`
- `app/Models/ProductionSession.php`
- `bootstrap/app.php`
- `tests/Feature/DeviceOwnershipTest.php`

**Estimated scope:** Medium, 5 files, 40–50 minutes.

## Task 6: Implementasikan start atomik, takeover, dan polling

**Description:** Buat endpoint sesi yang memvalidasi master, mengunci row mesin saat start/takeover, membatasi satu sesi per browser, dan menyediakan status polling lima detik.

**Acceptance criteria:**
- [ ] Dua start bersamaan pada mesin sama menghasilkan tepat satu sesi aktif dan satu HTTP 409.
- [ ] Mesin berbeda dapat berjalan bersamaan; takeover mempertahankan sesi dan memutus hak kontrol browser lama.
- [ ] Polling mengembalikan state, kepemilikan, data sesi, dan timestamp ber-offset tanpa mengubah data.

**Verification:**
- [ ] Jalankan `php artisan test --filter=ProductionSessionControlTest`.
- [ ] Jalankan test konkurensi start/takeover menggunakan koneksi database yang mendukung row lock.
- [ ] Periksa response 409 dan payload polling secara manual.

**Dependencies:** Task 5

**Files likely touched:**
- `app/Http/Controllers/ProductionController.php`
- `app/Http/Requests/StartProductionSessionRequest.php`
- `app/Http/Requests/TakeoverProductionSessionRequest.php`
- `routes/web.php`
- `tests/Feature/ProductionSessionControlTest.php`

**Estimated scope:** Medium, 5 files, 55–70 minutes.

## Checkpoint C: Session Control

- [ ] Konflik mesin, konkurensi mesin berbeda, dan takeover lulus.
- [ ] Browser lama tidak dapat melakukan mutation setelah takeover.
- [ ] Refresh dan login ulang melanjutkan sesi browser yang sama.

## Task 7: Bangun satu layar adaptif Operator

**Description:** Ganti dashboard lama dengan layar Ready, Running, Downtime, Completed, dan Acknowledged menggunakan Blade + Alpine tanpa dependency frontend baru.

**Acceptance criteria:**
- [ ] Form Ready menyediakan pilihan area serta combobox produk/mesin yang dapat diketik tetapi hanya menerima master aktif sesuai area.
- [ ] Running menampilkan area, nama Operator, produk, mesin, waktu mulai, durasi, STOP MESIN, dan Selesai Produksi.
- [ ] Daftar Lanjutkan Sesi, polling lima detik, state kehilangan kontrol, dan tombol OKE bekerja tanpa kartu OEE statis.

**Verification:**
- [ ] Jalankan `php artisan test --filter=ProductionDashboardTest`.
- [ ] Jalankan `npm run build`.
- [ ] Periksa manual layar Ready/Running dan searchable combobox pada desktop serta tablet.

**Dependencies:** Task 6

**Files likely touched:**
- `resources/views/dashboard.blade.php`
- `tests/Feature/ProductionDashboardTest.php`

**Estimated scope:** Small, 2 files, 45–60 minutes.

## Task 8: Implementasikan downtime dan ringkasan selesai

**Description:** Terapkan state transition STOP, penyelesaian downtime dengan form kendala existing, resume mesin, penyelesaian produksi, dan ringkasan akhir berbasis waktu server.

**Acceptance criteria:**
- [ ] STOP/resume ganda atau mutation pada state/pemilik salah menghasilkan HTTP 409 tanpa duplikasi log.
- [ ] Sesi downtime tidak dapat diselesaikan sebelum kategori, pelaksana, komponen, dan tindakan perbaikan tersimpan.
- [ ] Ringkasan menampilkan waktu mulai/selesai, durasi total, ada/tidak downtime, jumlah downtime, total downtime, lalu OKE kembali ke Ready.

**Verification:**
- [ ] Jalankan `php artisan test --filter=DowntimeControlTest`.
- [ ] Jalankan alur manual Running → Downtime → Running → Completed → OKE.
- [ ] Pastikan timer dan durasi memakai waktu server dalam `Asia/Jakarta`.

**Dependencies:** Task 7

**Files likely touched:**
- `app/Http/Controllers/ProductionController.php`
- `app/Http/Requests/ResumeProductionSessionRequest.php`
- `routes/web.php`
- `resources/views/dashboard.blade.php`
- `tests/Feature/DowntimeControlTest.php`

**Estimated scope:** Medium, 5 files, 55–70 minutes.

## Checkpoint D: Operator Flow

- [ ] Dua tablet menjalankan dua mesin berbeda dengan satu akun Operator.
- [ ] Takeover saat running dan downtime mempertahankan waktu serta log.
- [ ] Tidak ada downtime terbuka baru pada sesi completed.

## Task 9: Upgrade dependency export yang rentan

**Description:** Naikkan Laravel Excel/PhpSpreadsheet ke versi kompatibel terkecil yang menutup advisory terkunci tanpa mengganti library export.

**Acceptance criteria:**
- [ ] Dependency export terpasang pada versi kompatibel Laravel 12/PHP 8.2+.
- [ ] `composer audit --locked` tidak melaporkan advisory pada dependency export yang digunakan.
- [ ] Export sederhana masih dapat dibuat setelah upgrade.

**Verification:**
- [ ] Jalankan `composer update maatwebsite/excel phpoffice/phpspreadsheet --with-all-dependencies`.
- [ ] Jalankan `composer audit --locked`.
- [ ] Jalankan `php artisan test --filter=HistoryReportingTest` setelah Task 10.

**Dependencies:** None; dapat dikerjakan paralel setelah kontrak versi dikonfirmasi.

**Files likely touched:**
- `composer.json`
- `composer.lock`

**Estimated scope:** Small, 2 files, 20–35 minutes.

## Task 10: Selaraskan query riwayat, filter, dan export

**Description:** Pusatkan filter downtime pada satu query scope, validasi filter, gunakan eager loading/pagination, dan buat export berbasis query agar hasil layar dan Excel identik.

**Acceptance criteria:**
- [x] Filter tanggal satu/dua sisi, urutan rentang, area, dan mesin divalidasi; parameter bertahan pada pagination.
- [x] Layar dan Excel memakai query, urutan, zona waktu, serta label status yang sama.
- [x] Export tidak memuat seluruh dataset ke memori dan Operator tetap menerima HTTP 403.

**Verification:**
- [x] Jalankan `php artisan test --filter=HistoryReportingTest`.
- [x] Bandingkan jumlah dan urutan baris layar dengan export untuk filter yang sama.
- [x] Periksa query eager loading dan tidak ada N+1 pada data uji.

**Dependencies:** Tasks 8 and 9

**Files likely touched:**
- `app/Http/Requests/HistoryFilterRequest.php`
- `app/Http/Controllers/HistoryController.php`
- `app/Models/DowntimeLog.php`
- `app/Exports/DowntimeHistoryExport.php`
- `tests/Feature/HistoryReportingTest.php`

**Estimated scope:** Medium, 5 files, 50–65 minutes.

## Checkpoint E: Reporting

- [x] SPV/Manager melihat dan mengekspor hasil filter yang sama.
- [x] Record aktif dan legacy tidak lengkap tidak tertukar.
- [x] Audit dependency export bersih untuk advisory yang digunakan.

## Task 11: Verifikasi integrasi, responsive, dan dokumentasi

**Description:** Hubungkan label pelaporan terakhir pada view, jalankan suite penuh, audit format/build, uji runtime multi-tablet, dan dokumentasikan hasil serta ketidakpastian yang tersisa.

**Acceptance criteria:**
- [x] Semua acceptance criteria lintas role, sesi, downtime, takeover, dan pelaporan lulus end-to-end.
- [x] Dashboard Operator dan riwayat usable pada desktop serta tablet tanpa angka OEE palsu.
- [x] Test, scoped formatting, frontend build, dan dependency audit lulus; perubahan user di `public/index.php` tidak tersentuh.

**Verification:**
- [x] Jalankan `composer test` dan `npm run build`.
- [x] Jalankan `vendor/bin/pint --test` pada file PHP yang disentuh serta `composer audit --locked && npm audit`.
- [x] Uji runtime dua browser/tablet: start berbeda, konflik mesin sama, takeover, downtime, selesai, OKE, history, dan export.

**Dependencies:** Tasks 1–10

**Files likely touched:**
- `resources/views/history.blade.php`
- `tests/Feature/EndToEndProductionFlowTest.php`
- `docs/specs/ENGINEERING-CONTRACT.md`
- `tasks/todo.md`

**Estimated scope:** Medium, 4 files, 45–60 minutes.

## Checkpoint F: Ready for Implementation Review

- [x] Setiap task memiliki acceptance criteria, verification, dependencies, dan maksimal sekitar 5 file.
- [x] Total rencana mengikuti dependency graph dan tidak menghapus data historis.
- [x] Human menyetujui plan sebelum Task 1 dimulai.
