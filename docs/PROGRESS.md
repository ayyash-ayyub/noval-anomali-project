# Progress Log — Laravel 12 MikroTik WiFi Voucher System

> Dokumen ini adalah satu-satunya sumber kebenaran untuk melanjutkan proyek ini di sesi berikutnya. Ditulis agar bisa dibaca "dingin" (cold start) tanpa konteks percakapan sebelumnya. Baca `prompt_laravel12_mikrotik_voucher.txt` (root proyek) dulu untuk spesifikasi lengkap — dokumen ini hanya mencatat **apa yang sudah dibangun, keputusan desain, dan state saat ini**, bukan mengulang spec.
>
> **Terakhir diperbarui:** 2026-10-01

---

## 1. Status Ringkas

- **Phase 1–8 dari roadmap asli (section 29 prompt) — SELESAI** (2026-09-23). Semua 30 item MVP (section 30) sudah dibangun.
- **Live-tested end-to-end terhadap router produksi sungguhan** (2026-10-01) — bukan cuma mock. Lihat §4.
- Beberapa fitur tambahan di luar roadmap asli dibangun ad-hoc setelah Phase 8 selesai, atas permintaan user satu per satu. Lihat §3.
- **125 test lulus** (`php artisan test`), `./vendor/bin/pint` bersih pada semua file yang disentuh.
- **Belum dibangun / sengaja ditunda:** modul `/reports` (masih placeholder), modul `/settings` (hanya RBAC-gated, belum ada fungsi pengaturan nyata), penanganan `limit_uptime` untuk profil yang sudah ada sebelum aplikasi ini (dikelola Mikhmon). Lihat §7.

---

## 2. Environment & Cara Menjalankan (hal yang tidak jelas dari kode)

- **MySQL** via **DBngin** (bukan Homebrew) — socket `/tmp/mysql_3306.sock`, juga bisa TCP `127.0.0.1:3306`, user `root`, tanpa password. Database: `noval_anomali`.
- **Redis** via `brew install redis`, jalan sebagai brew service (`brew services start redis`). `.env`: `QUEUE_CONNECTION=redis` untuk real dev; test suite override ke `sync` via `phpunit.xml`.
- **Queue worker wajib jalan manual** saat dev — tidak ada supervisor/auto-restart terpasang:
  ```
  php artisan queue:work redis --queue=voucher-generation,active-user-sync,mikrotik-sync,report,pdf-generation,default --tries=3 --timeout=60
  ```
  Tanpa ini, voucher/PDF batch akan diam selamanya di status `PROCESSING`/`PENDING` (pernah terjadi, lihat §4 — bukan bug, ini memang cara kerja queue).
- **Login admin:** username `adminanomali`, password `Noval@om4rsyad2025` (seeded via `DatabaseSeeder`). Login berbasis **username**, bukan email.
- **Theme UI "Matrix"** — background hitam, teks hijau, monospace, digital-rain canvas di halaman login. Ini **hard requirement** dari awal proyek, bukan gaya sembarangan — UI baru harus konsisten dengan ini.
- **Router live untuk testing** sudah tersedia dan tersimpan di DB (`mikrotiks` id=13, nama "Live Test Router") — lihat §4 untuk detail kredensial dan kondisi routernya.

---

## 3. Fitur Tambahan Setelah Phase 8 (di luar roadmap asli, dibangun ad-hoc)

Semua ini dibangun **setelah** live-test di §4 berhasil, atas permintaan user satu-satu dalam sesi yang sama (2026-10-01).

### 3.1 Perbaikan Status MikroTik (2 bug dari live-test)
Lihat detail penuh di §4.2. Ringkas: threshold ONLINE/DEGRADED dinaikkan dari 300ms ke 500ms (configurable via `.env`), dan bug `success=true` + `status=Offline` yang bisa terjadi bersamaan sudah diperbaiki secara struktural (trait `ClassifiesConnectionStatus`, OFFLINE sekarang hanya dari exception, tidak pernah dari ambang latensi).

### 3.2 Paginasi pada halaman read-only HotSpot
`/hotspot/users` dan `/hotspot/active` sebelumnya me-render semua baris dari router dalam satu tabel (bisa ratusan baris). Sekarang dipaginasi 50/halaman via helper `HotspotController::paginateArray()` — membungkus array PHP biasa (bukan Eloquent) jadi `LengthAwarePaginator`. Untuk `/hotspot/active`, variabel `$totalActive` dipisah dari `$sessions` (yang sudah dipaginasi) supaya card "Total Active Users" tetap menghitung semua sesi, bukan cuma yang tampil di halaman saat ini.

### 3.3 Menu "Add User Profile" (`/hotspot/profiles/create`)
Membuat `/ip hotspot user profile` baru langsung di router pilihan, mirip menu Mikhmon tapi **tidak pakai Mikhmon** — murni Service Layer + RouterOS API.

- **Field yang diimplementasikan (native RouterOS):** Name, Address Pool (dropdown live dari `getIpPools()`), Shared Users, Rate Limit, Parent Queue.
- **Field Mikhmon yang SENGAJA tidak dibuat:** "Expired Mode" diganti dengan **Session Timeout** (field asli RouterOS, langsung dipakai oleh `VoucherLifecycleReconciler`/`resolveLimitUptime()` yang sudah ada — ini genuinely menutup celah `limit_uptime` kosong, lihat §4.3). "Price Rp", "Selling Price Rp", "Lock User" **tidak dibuat sama sekali** karena itu scripting/bookkeeping murni milik Mikhmon, bukan parameter RouterOS asli — spec eksplisit melarang menebak parameter RouterOS. User sudah setuju dengan keputusan ini setelah dijelaskan.
- Admin-only (`MikrotikPolicy::createHotspotProfile`). Interface baru: `getIpPools()`, `createHotspotProfile()` di `MikrotikServiceInterface` (2 implementasi: API & REST).
- **Live-verified:** profil `ayyash-test` (session-timeout=1d, rate-limit=512k/1M) dibuat di router produksi dan **sengaja dibiarkan di sana** atas permintaan user ("biarkan saja") — **jangan dihapus tanpa tanya dulu** kalau nanti melakukan "bersih-bersih test data".

### 3.4 Mode generate voucher "User = Password"
`UsernameGenerationMethod::UserEqualsPassword` — opsi baru di dropdown "Username Generation Method" pada `/vouchers/generate`. Saat dipilih, password voucher otomatis sama persis dengan username (field Password Generation Method disembunyikan via Alpine.js). Ini meniru mode `"up"` milik Mikhmon yang **terbukti nyata** ada di script `on-login` profil-profil router live (`$ucode = "up"`).

- `UsernameGenerator` generate kode acak (reuse logic `random()`); `VoucherBatchService::generate()` set `$passwords = $usernames` langsung, mengabaikan `password_method`/`password_length`.
- Migration `voucher_batches.username_method` (enum) diperluas untuk menampung value baru — **diedit langsung di migration asli** (belum ada migration `alter` di proyek ini, karena belum deploy produksi; untuk DB dev MySQL yang sudah berjalan, perubahan diterapkan via `ALTER TABLE ... MODIFY` manual supaya data yang ada tidak hilang).
- **Bug yang sempat kejadian & sudah diperbaiki:** sempat menulis `:required="! isUserEqualsPassword"` pada komponen Blade `<x-text-input>` — Blade mengevaluasi atribut berawalan titik-dua pada tag `<x-...>` sebagai PHP di server, BUKAN binding Alpine.js di browser, jadi langsung fatal error "Undefined constant". **Pelajaran:** atribut `:attr` hanya reactive-Alpine kalau dipasang di tag HTML polos; di komponen Blade `<x-...>` itu selalu dieval PHP server-side.
- Live-verified: 3 voucher asli dibuat di router (profil `default`), `username === password` terkonfirmasi di DB maupun di router, lalu dibersihkan.

### 3.5 Fitur IP Bindings (`/hotspot/ip-bindings`)
Mirip "IP Binding" di Mikhmon, tapi kali ini **seluruhnya native RouterOS** (`/ip/hotspot/ip-binding`) — tidak ada field Mikhmon-only yang perlu dikecualikan seperti kasus Profile di atas.

- **List (read-only, kedua role):** `HotspotController::ipBindings()`, dipaginasi, menampilkan Nama (dari field `comment` — RouterOS tidak punya field "nama" khusus, sama seperti konvensi Mikhmon sendiri), MAC Address, Address, To Address, Type, Status.
- **Add (Admin-only):** `HotspotIpBindingController`, field: Name (opsional → `comment`), MAC Address (wajib, divalidasi regex), Type (`bypassed`/`blocked`/`regular`), Address & To Address (opsional).
- **Sengaja hanya list + add** — tidak ada edit/delete, sesuai scope yang diminta user secara eksplisit.
- Interface baru: `getIpBindings()`, `createIpBinding()` (2 implementasi).
- Live-verified: binding `AA:BB:CC:00:11:99` (type=bypassed, comment="Laptop Ayyash Test") dibuat di router produksi, dikonfirmasi, lalu dihapus lagi (pakai reflection ke `RouterOsApiClient` langsung karena memang sengaja tidak ada method `deleteIpBinding()` di interface).

### Ringkasan jumlah test per tahap
| Setelah fitur | Jumlah test |
|---|---|
| Akhir Phase 8 | 99 |
| + live-test bug fixes (§4.2) | 102 |
| + paginasi hotspot/users & active | 106 |
| + Add User Profile | 113 |
| + User = Password | 115 |
| + IP Bindings | **125** |

---

## 4. Live-Test Terhadap Router Produksi Sungguhan (2026-10-01)

Ini bukan sekadar unit test dengan mock — ini koneksi nyata ke router produksi yang sedang melayani pelanggan sungguhan.

### 4.1 Konfigurasi router yang terbukti jalan
- Endpoint publik: `192.103.46.14:12273` → NAT ke internal `8729` (`api-ssl`).
- `api_type: api` (binary protocol), `ssl_enabled: true`.
- Tersimpan sebagai record `mikrotiks` **id=13** ("Live Test Router") di DB dev.
- TLS negosiasi `ECDHE-RSA-AES256-GCM-SHA384`, self-signed (diterima karena `RouterOsApiClient` set `allow_self_signed`).
- **Router asli:** hAP ax^3, RouterOS 7.20.6 (stable), arm64, 1GB RAM. **777 hotspot user, ~106 sesi aktif** — ini router produksi sungguhan, bukan lab. Setiap operasi tulis harus hati-hati.
- **Mikhmon sudah terpasang di router ini.** 8 dari 9 profil hotspot punya script `on-login` besar (Mikhmon 25.02.23) yang mengatur masa berlaku via `/system scheduler` + field `comment`, dan menyandikan harga/durasi dalam string `:put (",remc,<harga>,<durasi>,<jual>,,Disable,")`.

### 4.2 Dua bug yang ditemukan live-test & SUDAH diperbaiki
1. **Threshold status terlalu ketat untuk koneksi internet.** `online_max_ms` lama 300ms, padahal latensi nyata ke router via internet 384–450ms (median 415ms) — router sehat selamanya tampil DEGRADED. **Fix:** default dinaikkan ke 500ms, dibuat configurable via `.env` (`MIKROTIK_ONLINE_MAX_MS`, `MIKROTIK_DEGRADED_MAX_MS`).
2. **`success=true` bisa terjadi bersamaan dengan `status=Offline`.** Logika lama men-downgrade status jadi OFFLINE kalau response lambat (>2000ms), padahal itu tetap panggilan yang BERHASIL — `MikrotikStatusChecker` jadi salah mencatat `last_online_at` pada router yang baru saja ditandai OFFLINE. **Fix:** OFFLINE sekarang murni dari exception (timeout/error asli), tidak pernah dari ambang latensi. Logic dipindah ke trait `App\Services\Mikrotik\Concerns\ClassifiesConnectionStatus`, dipakai kedua service (API & REST).

### 4.3 Masalah yang BELUM diperbaiki — sengaja ditunda
**Setiap profil di router ini punya `session-timeout` kosong** (karena durasi dikelola Mikhmon lewat script `on-login`, bukan field native). Akibatnya `VoucherBatchService::resolveLimitUptime()` (baca `session-timeout` dari profil) selalu dapat `null` untuk profil-profil lama ini → `limit_uptime` voucher kosong → `VoucherLifecycleReconciler` tidak pernah bisa mencapai status `USED` untuk voucher di profil-profil tersebut.

**Keputusan user (2026-10-01): "bahas nanti"** — jangan dibangun tanpa diminta. Opsi yang pernah dilontarkan: parse string `on-login` Mikhmon, set `session-timeout` manual di router, atau simpan durasi di sisi aplikasi. **Catatan:** fitur "Add User Profile" (§3.3) sudah menyediakan field Session Timeout untuk **profil baru** yang dibuat lewat aplikasi ini — jadi masalah ini hanya relevan untuk profil-profil lama peninggalan Mikhmon.

### 4.4 Yang sudah terverifikasi jalan (Service Layer lengkap, record id=13)
`testConnection()`, `getRouterInfo()`, `getHotspotProfiles()`, `getIpPools()`, `createHotspotProfile()`, `getHotspotUsers()`, `getActiveHotspotUsers()`, `findHotspotUser()`, `createHotspotUser()`, `updateHotspotUser()`, `disableHotspotUser()`, `deleteHotspotUser()`, `getIpBindings()`, `createIpBinding()`, `MikrotikStatusChecker::check()`. Juga full alur generate voucher via UI sungguhan (bukan tinker) → queue Redis → job diproses → voucher tersinkron di router → PDF ter-generate dan terunduh.

---

## 5. Arsitektur & Konvensi yang Sudah Mapan

- **Service Layer** (`App\Services\Mikrotik\`) adalah satu-satunya yang boleh bicara ke router. `MikrotikServiceInterface`, 2 implementasi: `RouterOsApiService` (binary protocol) dan `RouterOsRestService` (REST), diresolve per-router via `MikrotikServiceFactory`. **Jangan pernah biarkan Controller bicara langsung ke router.**
- Setiap kali menambah method baru ke `MikrotikServiceInterface`, **harus diimplementasikan di KEDUA class** (`RouterOsApiService` dan `RouterOsRestService`) — tidak ada implementasi lain.
- **RBAC pola yang konsisten:** halaman read-only hotspot (profiles/users/active/ip-bindings) → kedua role (Admin & Operator) bisa lihat. Aksi yang mengubah konfigurasi router (buat profile, buat ip-binding, kelola MikroTik) → **Admin-only**, lewat `MikrotikPolicy`. Generate voucher adalah **satu-satunya** aksi tulis milik Operator (sesuai spec eksplisit).
- **Audit logging** wajib untuk setiap aksi mutating — pakai `App\Services\AuditLogService::log()`, termasuk hasil gagal (`result: 'failed'`) saat router menolak dengan error aslinya.
- **Prinsip "jangan menebak parameter RouterOS":** kalau mau menambah field baru yang mengirim data ke router, pastikan field itu genuinely ada di dokumentasi/skema RouterOS resmi. Kalau mirip Mikhmon tapi field-nya Mikhmon-only (scripting/bookkeeping internal Mikhmon, bukan API RouterOS asli), **jangan dibuat berpura-pura berfungsi** — lebih baik dijelaskan ke user kenapa tidak dibuat (lihat §3.3 sebagai contoh preseden).
- **Halaman hotspot read-only tidak pernah menyimpan data lokal** — selalu baca langsung dari router tiap request. Pola ini dipertahankan konsisten di semua fitur hotspot baru.
- **Paginasi untuk array biasa (bukan Eloquent):** pakai helper `HotspotController::paginateArray()` — bungkus `array_slice()` ke `LengthAwarePaginator` manual, lalu `->withQueryString()`. Reusable untuk list apa pun yang datang dari router.
- **Bug class yang pernah muncul, hindari lagi:** jangan pasang atribut `:attr="$alpineVar"` pada komponen Blade `<x-...>` kalau `$alpineVar` cuma ada di `x-data` Alpine — itu akan dieval sebagai PHP di server dan gagal dengan "Undefined constant". Pola aman: pasang plain `required`/dsb statis, biarkan Alpine `x-show` yang mengatur visibility saja.
- **Migration:** proyek ini belum pernah deploy produksi, jadi perubahan skema langsung mengedit migration `create_*` aslinya (bukan bikin migration `alter_*` baru) — tapi kalau DB dev MySQL sudah terlanjur jalan dengan skema lama, terapkan perubahan manual via `ALTER TABLE` supaya data dev yang sudah ada (terutama record router id=13) tidak hilang. **Jangan pernah `migrate:fresh` tanpa cek dulu isi DB** — record `mikrotiks` id=13 dan kredensial live-test bisa hilang.

---

## 6. State Database Dev Saat Ini (per 2026-10-01)

- `mikrotiks`: **1 record**, id=13 "Live Test Router" (lihat §4.1) — **jangan dihapus**, ini konfigurasi live-test yang dipakai berulang.
- `vouchers`/`voucher_batches`: ada sisa data dari sesi testing —
  - Batch id=9 (`WIFI-2026-10-01-NHIJ`, prefix `JKT`, 5 voucher) — user secara eksplisit minta ini diproses (bukan dihapus) saat membahas status PENDING.
  - Batch id=11 (`WIFI-2026-10-01-V6X0`, prefix `noval`, 3 voucher) — dibuat oleh **user sendiri** lewat browser (bukan oleh sesi AI ini), kemungkinan testing manual mereka sendiri. **Jangan dihapus tanpa tanya dulu.**
- Router produksi id=13 juga punya 1 profil tambahan yang sengaja ditinggalkan: `ayyash-test` (lihat §3.3).
- Audit log: terus bertambah dari semua testing di atas, belum pernah dibersihkan sejak awal proyek.

> **Untuk sesi berikutnya:** kalau mau "bersih-bersih" data test, **tanya dulu ke user** — beberapa data di atas sengaja ditinggalkan atas permintaan eksplisit mereka, bukan sampah.

---

## 7. Yang Belum Dibangun / Sengaja Ditunda

- **`/reports`** — masih halaman placeholder "coming soon". Section 18 di spec tidak memberi assignment fase untuk ini. Perlu dibahas scope-nya dengan user kalau mau dibangun.
- **`/settings`** — hanya ditutup RBAC-nya (Admin-only) di Phase 8, belum ada fungsi pengaturan sistem nyata (spec section 15 "Admin dapat ... mengatur sistem" belum pernah di-spec detail).
- **`limit_uptime` untuk profil lama peninggalan Mikhmon** — lihat §4.3, keputusan user "bahas nanti".
- **Edit/Delete untuk Hotspot Profile dan IP Binding** — sengaja tidak dibangun, user hanya minta list+add untuk keduanya.

---

## 8. Checklist Mulai Sesi Berikutnya

1. Baca dokumen ini dulu, lalu `prompt_laravel12_mikrotik_voucher.txt` kalau perlu re-check spec detail.
2. Jalankan `php artisan test` — harus 125 lulus. Kalau beda, ada perubahan yang belum tercatat di sini.
3. Kalau mau testing live ke router lagi: `php artisan serve` + `php artisan queue:work redis --queue=voucher-generation,active-user-sync,mikrotik-sync,report,pdf-generation,default` (keduanya **tidak auto-start**, harus dijalankan manual tiap sesi dev).
4. Cek §6 sebelum menghapus data apa pun dari DB dev atau dari router live — beberapa data sengaja ditinggalkan.
5. Kalau user bilang "lanjut" tanpa spesifik, ajukan §7 sebagai kandidat kerjaan berikutnya (reports, settings, atau `limit_uptime` untuk profil lama).
