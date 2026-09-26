# Koneksi ke MikroTik via IP Publik (Beda Jaringan / Beda VPN)

Dokumen ini untuk skenario: **aplikasi Laravel dideploy di server/VPN yang berbeda jaringan dengan router MikroTik** — bukan VPN site-to-site yang menyatukan kedua jaringan, sehingga satu-satunya jalur yang tersedia adalah lewat **IP publik** router (langsung, atau lewat MikroTik Cloud/DDNS).

> **Peringatan.** Ini bukan opsi paling aman. Membuka RouterOS API ke internet punya risiko lebih tinggi dibanding VPN site-to-site. Kalau memungkinkan, tetap pertimbangkan VPN (WireGuard/IPsec) sebagai pengganti. Kalau tidak memungkinkan, dokumen ini menjelaskan cara memperkecil risikonya semaksimal mungkin **dari sisi konfigurasi MikroTik**, sesuai prinsip di `prompt_laravel12_mikrotik_voucher.txt` bagian 15 (Security): *"Jangan hardcode credential", "Jangan menampilkan password MikroTik di log"*, dan *"Jika RouterOS API dapat diakses melalui VPN/private network, lebih baik menggunakan VPN/private network"* — bagian ini adalah fallback ketika VPN benar-benar tidak memungkinkan.

## Daftar isi

1. [Ringkasan arsitektur](#1-ringkasan-arsitektur)
2. [Setup di sisi MikroTik](#2-setup-di-sisi-mikrotik)
   - [2.1. IP Cloud / DDNS](#21-ip-cloud--ddns-untuk-ip-publik-dinamis)
   - [2.2. Aktifkan hanya API-SSL / REST-SSL](#22-aktifkan-hanya-api-ssl--rest-ssl-matikan-yang-plaintext)
   - [2.3. Sertifikat SSL](#23-sertifikat-ssl-untuk-api-ssl--rest-ssl)
   - [2.4. Ganti port default](#24-ganti-port-default-security-through-obscurity---lapisan-tambahan)
   - [2.5. User RouterOS khusus untuk aplikasi](#25-buat-user-routeros-khusus-untuk-aplikasi-bukan-admin)
   - [2.6. Firewall: whitelist IP sumber](#26-firewall-whitelist-ip-sumber-paling-penting)
   - [2.7. Address-list & proteksi brute-force](#27-address-list--proteksi-brute-force)
   - [2.8. Port forwarding (kalau MikroTik di belakang NAT ISP)](#28-port-forwarding-kalau-mikrotik-di-belakang-nat-isp)
   - [2.9. Logging](#29-logging)
3. [Setup di sisi aplikasi Laravel](#3-setup-di-sisi-aplikasi-laravel)
4. [Verifikasi & testing](#4-verifikasi--testing)
5. [Checklist keamanan ringkas](#5-checklist-keamanan-ringkas)
6. [Kapan harus pindah ke VPN](#6-kapan-harus-pindah-ke-vpn)

---

## 1. Ringkasan arsitektur

```
Laravel (server/VPN A)                         MikroTik (jaringan B)
        |                                              |
        |----- HTTPS/API-SSL, port custom, TLS ------->|  <-- hanya dari IP server Laravel
        |                                              |
        |                                        Firewall input chain
        |                                        (drop semua kecuali IP server)
```

Semua konfigurasi di dokumen ini **diatur dari sisi MikroTik** — tidak ada perubahan pada kode aplikasi selain field yang memang sudah tersedia di form Add/Edit MikroTik (`host`, `port`, `api_type`, `ssl_enabled`) dan beberapa env var timeout/verifikasi TLS yang memang sudah ada (`config/mikrotik.php`).

---

## 2. Setup di sisi MikroTik

Lakukan semua langkah berikut lewat WinBox / SSH / Terminal RouterOS.

### 2.1. IP Cloud / DDNS (untuk IP publik dinamis)

Kalau MikroTik punya IP publik tapi **dinamis** (bukan static dari ISP), aktifkan IP Cloud bawaan MikroTik supaya dapat hostname tetap:

```
/ip cloud
set ddns-enabled=yes
print
```

Catat `dns-name` yang muncul (contoh: `abcd1234.sn.mynetname.net`). Hostname ini yang nanti dipakai sebagai `host` di aplikasi, bukan IP mentah — supaya tidak perlu update manual tiap kali IP publik berubah.

Kalau IP publiknya sudah static, langkah ini boleh dilewati — pakai IP publik langsung.

### 2.2. Aktifkan hanya API-SSL / REST-SSL, matikan yang plaintext

Jangan pernah expose `api` (8728) atau `www` (HTTP REST) polos ke internet — kredensial dikirim tanpa enkripsi. Cek dan matikan service yang tidak dipakai:

```
/ip service
print
```

Set seperti ini (sesuaikan dengan `api_type` yang dipilih di aplikasi — pilih **salah satu**, jangan dua-duanya):

**Kalau pakai Binary API (`api_type = api` di aplikasi):**
```
/ip service
disable api
set api-ssl port=8729 disabled=no certificate=<nama-cert>
```

**Kalau pakai REST API (`api_type = rest` di aplikasi):**
```
/ip service
disable www
set www-ssl port=443 disabled=no certificate=<nama-cert>
```

Set `ssl_enabled = true` di form Mikrotik pada aplikasi agar cocok dengan ini.

### 2.3. Sertifikat SSL untuk API-SSL / REST-SSL

`api-ssl`/`www-ssl` butuh sertifikat. Opsi termudah — sertifikat self-signed langsung dari RouterOS:

```
/certificate
add name=api-cert common-name=<dns-name-dari-ip-cloud-atau-ip-publik> key-usage=tls-server days-valid=3650
sign api-cert
```

Lalu pasang ke service:
```
/ip service set api-ssl certificate=api-cert
```

> Karena ini self-signed, aplikasi tidak akan otomatis percaya sertifikatnya. Sesuai desain aplikasi ini, env var `MIKROTIK_REST_VERIFY_TLS` (default `false`) memang disediakan untuk kasus sertifikat self-signed di jaringan privat/VPN — **tapi untuk koneksi lewat internet publik, sebaiknya jangan matikan verifikasi TLS**. Kalau memungkinkan, gunakan sertifikat dari Let's Encrypt lewat fitur `/certificate enable-ssl-certificate` MikroTik Cloud (butuh IP Cloud aktif dan port 80 terbuka sementara saat request), supaya `MIKROTIK_REST_VERIFY_TLS=true` bisa diaktifkan dengan aman.

### 2.4. Ganti port default (security through obscurity — lapisan tambahan)

Port default (8729 untuk API-SSL, 443 untuk REST-SSL) adalah target pertama yang di-scan bot. Ganti ke port non-standar sebagai lapisan tambahan (bukan pengganti firewall, hanya mengurangi noise):

```
/ip service set api-ssl port=<port-custom-anda>
```

Field `port` di form Mikrotik aplikasi sudah **configurable** (bukan hardcoded 8729) — sesuaikan nilainya di sana juga.

### 2.5. Buat user RouterOS khusus untuk aplikasi (bukan admin)

Jangan pakai akun `admin` bawaan untuk kredensial yang disimpan aplikasi. Buat user dengan hak akses seminimal mungkin — cukup untuk kebutuhan `MikrotikServiceInterface` (baca resource/profile/hotspot user, tulis hotspot user):

```
/user group
add name=voucher-app policy=api,read,write,!local,!telnet,!ssh,!ftp,!reboot,!policy,!test,!winbox,!password,!web,!sniff,!sensitive,!romon,!dude,!tikapp

/user
add name=voucher-app password=<password-kuat-random> group=voucher-app
```

Password ini yang dimasukkan ke field `password_encrypted` di form Mikrotik — aplikasi otomatis meng-enkripsi sebelum disimpan (`Mikrotik::casts()` pakai cast `encrypted`), dan tidak pernah menampilkannya di log/response (`$hidden` di model `Mikrotik`).

### 2.6. Firewall: whitelist IP sumber (paling penting)

Ini adalah **lapisan proteksi utama** — jauh lebih penting daripada ganti port. Batasi input chain supaya port API/REST cuma bisa diakses dari IP publik server Laravel:

```
/ip firewall filter
add chain=input protocol=tcp dst-port=<port-api-ssl-anda> src-address=<IP-publik-server-laravel> action=accept comment="Allow Laravel app - voucher system"
add chain=input protocol=tcp dst-port=<port-api-ssl-anda> action=drop comment="Drop all other API access"
```

Pasang rule ini **di atas** rule `drop`/`reject` umum yang mungkin sudah ada, dan letakkan sebelum rule broad "accept established/related" kalau perlu — cek urutan (`/ip firewall filter print`) supaya tidak ketiban rule lain yang keburu accept semua.

Kalau IP server Laravel juga dinamis (misal deploy di provider yang IP-nya bisa berubah), pertimbangkan:
- Pakai IP Cloud/DDNS juga di sisi server, lalu resolve ke IP dan update address-list secara berkala (lihat 2.7), atau
- Pakai range CIDR provider cloud kamu kalau mereka publish IP range resmi, atau
- **Ini justru sinyal kuat untuk pindah ke VPN** (lihat bagian 6) — IP dinamis di kedua sisi jauh lebih mudah diamankan lewat VPN daripada terus update firewall rule manual.

### 2.7. Address-list & proteksi brute-force

Tambahan lapisan: auto-block IP yang berulang kali gagal koneksi (mirip fail2ban), berlaku untuk trafik di luar whitelist 2.6 (jaga-jaga kalau whitelist bocor/berubah):

```
/ip firewall filter
add chain=input protocol=tcp dst-port=<port-api-ssl-anda> connection-state=new src-address-list=blocked-api action=drop comment="Drop blocked brute-force IPs"
add chain=input protocol=tcp dst-port=<port-api-ssl-anda> connection-state=new action=add-src-to-address-list address-list=api-attempts address-list-timeout=10m
add chain=input protocol=tcp dst-port=<port-api-ssl-anda> connection-state=new src-address-list=api-attempts action=drop comment="Rate-limit new connection attempts"
```

Sesuaikan threshold sesuai kebutuhan (RouterOS firewall bisa dikombinasikan dengan `connection-limit` per source IP untuk membatasi jumlah koneksi paralel).

### 2.8. Port forwarding (kalau MikroTik di belakang NAT ISP)

Kalau MikroTik ini **bukan** router edge (ada modem ISP lain di depannya yang pegang IP publik), forward port dari modem tersebut ke MikroTik. Kalau modemnya sendiri adalah RouterOS (mode bridge/router), contohnya:

```
/ip firewall nat
add chain=dstnat protocol=tcp dst-port=<port-publik> action=dst-nat to-addresses=<ip-lokal-mikrotik-hotspot> to-ports=<port-api-ssl-lokal>
```

Kalau modemnya bukan RouterOS (router ISP bawaan), setup port forwarding lewat halaman admin modem tersebut, arahkan ke IP lokal MikroTik di port API-SSL-nya.

> Tetap terapkan whitelist IP (2.6) di firewall MikroTik itu sendiri — port forwarding di modem tidak menggantikan firewall filter di MikroTik.

### 2.9. Logging

Aktifkan logging untuk service API supaya ada jejak audit di sisi router juga (melengkapi `audit_logs` yang sudah dicatat di sisi aplikasi):

```
/system logging
add topics=account action=memory
```

Cek log koneksi masuk secara berkala: `/log print where topics~"account"`.

---

## 3. Setup di sisi aplikasi Laravel

Setelah sisi MikroTik siap, isi form **Add MikroTik** (`/mikrotiks/create`) di aplikasi:

| Field | Nilai |
|---|---|
| `host` | Hostname IP Cloud (`xxxx.sn.mynetname.net`) atau IP publik statis |
| `port` | Port custom yang di-set di langkah 2.4 |
| `username` | User khusus dari langkah 2.5 (bukan `admin`) |
| `password` | Password user tersebut (otomatis dienkripsi saat disimpan) |
| `api_type` | `api` atau `rest`, sesuai yang diaktifkan di 2.2 |
| `ssl_enabled` | **Wajib `true`** — jangan pernah matikan untuk koneksi lewat internet publik |

Dan di `.env` server tempat Laravel dideploy:

```env
# Naikkan timeout karena koneksi lewat WAN/internet, bukan LAN lokal
MIKROTIK_CONNECT_TIMEOUT=10
MIKROTIK_READ_TIMEOUT=15

# Kalau sudah pakai sertifikat terpercaya (Let's Encrypt via MikroTik Cloud, bagian 2.3) → true
# Kalau masih self-signed → hanya set false kalau memang tidak ada pilihan lain, dan pahami risikonya
MIKROTIK_REST_VERIFY_TLS=true
```

Tidak ada perubahan kode yang diperlukan — semua field ini memang sudah dirancang configurable sejak awal (`config/mikrotik.php`, migration `mikrotiks` table), justru supaya skenario ini bisa didukung tanpa sentuh kode sama sekali.

---

## 4. Verifikasi & testing

1. **Cek raw connectivity dulu dari server Laravel**, sebelum coba lewat aplikasi:
   ```bash
   nc -zv <host> <port>
   ```
   Kalau gagal di sini, masalahnya di jaringan/firewall (langkah 2.6–2.8), bukan di aplikasi.

2. **Cek dari sisi MikroTik**, pastikan IP server Laravel benar-benar match dengan whitelist:
   ```
   /ip firewall filter print stats
   ```
   Lihat counter di rule `accept` — kalau 0 padahal sudah dicoba, kemungkinan besar IP publik server Laravel beda dari yang di-whitelist (cek IP publik aktual server, bukan IP privat internalnya).

3. **Baru coba lewat aplikasi** — buka detail MikroTik yang baru ditambahkan → klik **Test Connection**. Hasil akan menunjukkan ONLINE/DEGRADED/OFFLINE berdasarkan response time (di atas WAN, wajar kalau masuk kategori DEGRADED — threshold-nya 300ms/2000ms, bisa disesuaikan lewat `config/mikrotik.php` kalau perlu).

4. Kalau sukses, buka halaman **HotSpot Profiles** — profile yang sudah dibuat manual di router akan muncul di sana, tanda komunikasi dua arah sudah berjalan.

---

## 5. Checklist keamanan ringkas

- [ ] `api`/`www` (plaintext) dimatikan, hanya `api-ssl`/`www-ssl` yang aktif
- [ ] Sertifikat TLS terpasang (idealnya bukan self-signed — pakai Let's Encrypt via MikroTik Cloud)
- [ ] Port default diganti ke port custom
- [ ] User RouterOS khusus untuk aplikasi, bukan `admin`, dengan group policy minimal
- [ ] Firewall input chain whitelist hanya IP publik server Laravel
- [ ] Address-list rate-limiting untuk percobaan koneksi baru
- [ ] Logging service API aktif
- [ ] `ssl_enabled = true` di form Mikrotik aplikasi
- [ ] `MIKROTIK_REST_VERIFY_TLS=true` kalau sudah pakai sertifikat terpercaya
- [ ] Password user RouterOS random/kuat, disimpan hanya lewat form aplikasi (terenkripsi di database, tidak pernah di-commit/di-hardcode)

---

## 6. Kapan harus pindah ke VPN

Pertimbangkan pindah ke VPN site-to-site (WireGuard/IPsec) kalau salah satu dari ini terjadi:

- IP publik di salah satu sisi (server Laravel atau MikroTik) sering berubah, sehingga whitelist firewall (2.6) jadi merepotkan untuk terus diupdate.
- Jumlah MikroTik bertambah banyak (skala 10+ sesuai roadmap jangka panjang aplikasi ini) — mengelola whitelist/sertifikat manual per router jadi tidak scalable.
- Ada kebutuhan compliance/kebijakan internal yang mengharuskan tidak ada service apa pun yang expose ke internet publik.

VPN menghilangkan kebutuhan expose port API sama sekali ke internet — MikroTik cukup jadi VPN client/server ke satu titik pusat, dan firewall filter (2.6) cukup mengizinkan dari IP VPN internal saja. Endpoint di aplikasi (`host`) tinggal diganti dari hostname publik menjadi IP privat VPN, tanpa perubahan lain.
