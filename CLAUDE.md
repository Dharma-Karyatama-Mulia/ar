# ar — Aplikasi Accounts Receivable (Suite ERP DKM)

> Aplikasi Laravel berdiri sendiri untuk modul **AR**: Invoice → Payment → (opsional) Credit Note, plus laporan Aged Receivables. App kedua dalam suite ERP baru PT. Dharma Karyatama Mulia (DKM), setelah `sls`. Lihat `C:\Project\Web\sls\CLAUDE.md` untuk konteks suite secara umum dan `C:\Project\Web\erp-schema\MODULES-ROADMAP.md` untuk rencana modul lain.

## Cakupan

- **Invoice bisa dibuat dua cara**: dari Sales Order `sls` yang sudah `selesai` (auto-tarik customer/ship-to/lines), **atau** manual berdiri sendiri (untuk billing jasa/lain-lain yang tidak lewat `sls`) — keputusan eksplisit user, bukan dibatasi cuma dari SO seperti BS1 aslinya yang sebenarnya juga mendukung keduanya.
- **Payment** mengalokasikan satu pembayaran ke banyak invoice sekaligus, dengan opsi diskon (early payment) dan write-off per alokasi (write-off butuh pilih GL account, field saja — belum ada posting jurnal sungguhan, GL app belum dibangun).
- **Credit Note** mengurangi sisa tagihan invoice (retur/pembatalan), tervalidasi tidak boleh melebihi sisa owing invoice asal.
- **Tidak ada posting GL** — sama seperti `sls`, ditunda sampai app `gl` ada.
- **Tidak ada bank reconciliation sungguhan** — cuma flag `reconciled` di `ar_payments`, bukan layar rekonsiliasi penuh (itu butuh `gl_journal_lines` yang belum operasional).

## Computed Accessor, bukan Stored Column (penting!)

`ArInvoice::paid_amount`/`disc_taken_amount`/`write_off_amount`/`owing` **semua accessor**, dihitung dari relasi `allocations` (`ArPaymentAllocation`) — **bukan kolom tersimpan**. Migration awal `erp-schema` (dibuat sebelum field BS1 dibaca detail) sempat punya kolom `amount`/`paid_amount` tersimpan di `ar_invoices` — sudah di-drop lewat migration susulan (`2025_01_03_000001_add_ar_invoice_payment_fields.php`), diganti accessor, konsisten dengan pola `SalesOrder::total` di `sls`.

**Gotcha yang sempat kena (sudah diperbaiki, jangan diulang):** accessor `paid_amount`/dst awalnya menghitung dari **semua** allocation tanpa peduli status payment-nya — akibatnya invoice yang baru dibuat payment **draft** (belum di-approve) langsung kelihatan owing-nya berkurang. Fix: `ArInvoice::confirmedAllocations()` filter `allocations` yang payment-nya `disetujui`/`selesai` saja. Pola yang sama (filter by related-record status) sudah dipakai `SalesOrderLine::qty_shipped` di `sls` — terapkan pola ini lagi kalau bikin accessor serupa di app lain.

## Model Read-Only Lintas App

`Customer`/`Item`/`Shipto`/`Warehouse`/`Bank`/`GlAccount`/`Tax` — model biasa (tabel shared `erp`, tanpa migration di app ini). `SalesOrder`/`SalesOrderLine` di app ini **read-only** (tidak ada `$fillable`, tidak pernah ditulis) — `ar` cuma baca SO `sls` yang `selesai` untuk dijadikan dasar invoice, tidak pernah insert/update ke tabel `sls_*`.

## RBAC & Struktur

Identik dengan `sls`: role `sso_admin|admin|user|approval|viewer`, `/dev-login` untuk dev lokal (`AR_DEV_LOGIN_ENABLED`), tabel `ar_users`/`ar_sessions`/`ar_cache`/`ar_jobs` dst (prefix `ar_`, app punya users/framework table sendiri meski database `erp` dipakai bersama). `AutoNumberService` prefix: `INV` (invoice), `RCT` (payment/receipt), `CN` (credit note).

## Deployment

✅ App code, SSO registration (kode `AR`), dan nginx vhost (`ar.dkmapps.com`) semua sudah selesai di production — tapi **belum bisa diakses**, DNS record `ar.dkmapps.com` belum didaftarkan (lihat catatan terbaru di `sls/CLAUDE.md` bagian Deployment — DNS subdomain ternyata harus didaftarkan manual, bukan wildcard otomatis seperti yang dikira sebelumnya).

**Jangan jalankan `db:seed` di production** — `UserSeeder` isinya 5 user dev-login fiktif (email placeholder `admin@dkmapps.com` dst), cuma untuk testing lokal. Sempat salah dijalankan sekali di production saat deploy awal, langsung dihapus manual. Production cukup `migrate --force` saja.

## Status & Verifikasi

✅ Alur penuh sudah dicoba lokal via curl: buat invoice dari SO `selesai` → submit → approve → catat payment (partial + write-off) → submit → approve → owing terupdate benar → buat credit note partial → submit → approve → owing berkurang lagi → cek di Aged Receivables report. Juga dicoba invoice manual standalone (tanpa SO) — `due_date` terhitung benar dari `invoice_date + term_days`. RBAC dicoba (viewer diblokir dari create, tetap bisa lihat index).

⏳ Belum dicoba: alur lewat SSO Panel sungguhan (baru dev-login), tampilan visual di browser asli (baru HTTP/curl).
