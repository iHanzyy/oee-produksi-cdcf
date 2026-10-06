# Spec: OEE Preview

Module ID: `oee-preview`

## Objective

Menambahkan rancangan visual OEE pada kontrol sesi mesin agar alur dan keterbacaan dashboard dapat dievaluasi sebelum sumber data produksi riil tersedia.

## Engineering Contract

Tech stack, commands, structure, style, testing, dan boundaries mengikuti `ENGINEERING-CONTRACT.md`. Angka OEE preview hanya dirender di view dan tidak disimpan sebagai data produksi.

## Requirements

- Tampilkan satu panel OEE pada sesi yang sedang dikontrol dalam state `running`, `downtime`, dan `completed`.
- Panel menampilkan OEE total, Availability, Performance, dan Quality.
- Gunakan nilai dummy yang tetap selama halaman aktif agar angka tidak berubah saat polling.
- Tampilkan hanya label metrik dan angka agar panel tetap ringkas.
- Panel tetap terbaca pada layar tablet dan desktop serta mengikuti komponen visual dashboard yang sudah ada.
- Jangan mengubah state machine produksi, endpoint, schema database, atau payload server.
- Jangan menyimpan nilai dummy ke `production_sessions` atau menampilkannya pada export.

## Preview Data

- OEE: `78.8%`
- Availability: `92.0%`
- Performance: `87.4%`
- Quality: `98.0%`

Nilai tersebut hanya mengisi desain. Implementasi data riil nantinya wajib mengganti sumber nilai tanpa mempertahankan konstanta preview sebagai fallback.

## Project Structure

- Perubahan visual berada di `resources/views/dashboard.blade.php`.
- Test tampilan berada di `tests/Feature/ProductionDashboardTest.php`.
- Tidak ada file model, controller, migration, route, atau dependency baru.

## Code Style

Gunakan markup Blade/Alpine yang sudah ada dan atribut aksesibilitas sederhana:

```html
<section aria-labelledby="oee-preview-title">
    <h3 id="oee-preview-title">OEE Mesin</h3>
    <p>78.8%</p>
</section>
```

## Testing Strategy

- Feature test memastikan panel dan empat metrik dirender tanpa label atau penjelasan dummy.
- Test tidak menguji rumus OEE karena tahap ini tidak melakukan perhitungan.
- Jalankan test terfokus, suite lengkap, Pint pada file PHP yang disentuh, dan build frontend.

## Boundaries

- **Always:** pertahankan aksesibilitas dasar dan jangan mengubah perilaku kontrol sesi.
- **Ask first:** menambah input output/reject, target kecepatan, rumus OEE, riwayat OEE, atau penyimpanan database.
- **Never:** menyimpan angka preview atau memasukkannya ke laporan/export.

## Success Criteria

- Operator melihat panel OEE pada state running, downtime, dan completed.
- Panel menampilkan OEE, Availability, Performance, dan Quality tanpa copy tambahan.
- Angka tidak berubah akibat polling status.
- Kontrol start, stop, resume, complete, takeover, dan acknowledge tetap bekerja tanpa perubahan kontrak.
- Test dashboard, suite lengkap, format check, dan build frontend lulus.

## Out of Scope

- Rumus OEE riil.
- Input total output, reject, planned production time, target rate, atau ideal cycle time.
- Integrasi sensor/PLC.
- Tracking historis dan export OEE.
- OEE per mesin lain pada daftar monitoring.

## Open Questions

- Tidak ada untuk prototipe visual ini.
