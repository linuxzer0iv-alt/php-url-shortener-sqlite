# URL Shortener (PHP Native + PDO + SQLite)

Aplikasi pemendek URL sederhana, dibangun dengan PHP native (tanpa framework), PDO, dan
database SQLite. Didesain khusus agar **semua file muat di dalam satu folder `public_html`**
sehingga cocok untuk shared hosting cPanel yang tidak memberi akses di luar `public_html`.

## Struktur Folder

```
public_html/
├── index.php               # Halaman utama: form shorten URL
├── redirect.php             # Handler redirect (dipanggil lewat rewrite .htaccess)
├── .htaccess                 # Rewrite domain.com/{slug} -> redirect.php?slug={slug}
├── api/
│   ├── shorten.php          # POST - buat short URL
│   └── info.php             # GET  - cek detail sebuah slug
├── includes/
│   ├── functions.php        # Koneksi PDO + semua helper (validasi, generate slug, dll)
│   └── .htaccess             # Blokir akses langsung browser ke folder ini
├── database/
│   ├── links.sqlite          # File database (dibuat otomatis saat pertama kali diakses)
│   └── .htaccess             # WAJIB: blokir akses langsung ke file .sqlite
└── assets/
    ├── style.css
    └── script.js
```

Tabel `links`:

| Kolom          | Tipe    | Keterangan                              |
|----------------|---------|------------------------------------------|
| id             | INTEGER | primary key, autoincrement               |
| slug           | TEXT    | unik, bagian pendek URL                  |
| original_url   | TEXT    | URL tujuan asli                          |
| created_at     | TEXT    | waktu dibuat (`Y-m-d H:i:s`)              |
| expired_at     | TEXT    | nullable, waktu kedaluwarsa               |
| click_count    | INTEGER | jumlah klik, default 0                    |

## Cara Upload ke cPanel / Shared Hosting

1. Login ke **cPanel** → buka **File Manager** (atau gunakan FTP/SFTP client seperti FileZilla).
2. Masuk ke folder `public_html` domain/subdomain Anda.
   - Jika ingin app ini jadi halaman utama domain, upload **isi** folder `public_html` project
     ini (bukan foldernya, tapi isinya) langsung ke `public_html` hosting Anda.
   - Jika ingin diletakkan di subfolder (misal `domain.com/shortener/`), upload isi folder
     `public_html` project ini ke `public_html/shortener/` di hosting.
3. Pastikan struktur di server sama persis seperti di atas — folder `api/`, `includes/`,
   `database/`, `assets/`, beserta semua file `.htaccess` (file `.htaccess` biasanya
   tersembunyi, aktifkan "Show Hidden Files" di File Manager sebelum upload/cek).
4. Beri permission folder `database/` agar bisa ditulis oleh PHP (biasanya `755` sudah cukup
   di shared hosting cPanel; jika muncul error "unable to open database file", coba `775`).
5. Pastikan module Apache `mod_rewrite` aktif (default aktif di hampir semua shared hosting
   cPanel). Tidak perlu setting tambahan di cPanel untuk ini.
6. Pastikan ekstensi PHP **pdo_sqlite** aktif. Cek di cPanel → **MultiPHP INI Editor** atau
   **Select PHP Version** → centang `pdo_sqlite` dan `sqlite3` jika ada opsinya (umumnya sudah
   aktif secara default).
7. Buka `https://domain-anda.com/` di browser. Saat pertama kali diakses, file
   `database/links.sqlite` dan tabel `links` akan **dibuat otomatis** oleh `functions.php` —
   tidak perlu import manual.

### Verifikasi Keamanan Setelah Upload

Setelah upload, cek dari browser:

- `https://domain-anda.com/database/links.sqlite` → **harus 403 Forbidden**
- `https://domain-anda.com/includes/functions.php` → **harus 403 Forbidden**

Jika masih bisa diakses/didownload, berarti `mod_rewrite`/`mod_authz_core` tidak aktif di
hosting Anda — hubungi provider hosting untuk mengaktifkannya, karena ini krusial untuk
keamanan data.

## Cara Pakai (Web UI)

1. Buka halaman utama (`index.php`).
2. Isi **URL Asli** (wajib).
3. Opsional: isi **Custom Slug** (3-32 karakter, huruf/angka/`-`/`_`), atau kosongkan untuk
   slug acak otomatis.
4. Opsional: isi **Tanggal Kedaluwarsa**, atau kosongkan agar link berlaku permanen.
5. Klik **Perpendek URL** → short URL akan tampil dan bisa langsung disalin.
6. Gunakan bagian **Cek Detail Link** untuk melihat info (jumlah klik, status expired, dll)
   dari sebuah slug.

## Dokumentasi API

Lihat [`API.md`](API.md) untuk contoh request/response lengkap.

Ringkasnya:

- `POST /api/shorten.php` — membuat short URL baru.
- `GET /api/info.php?slug={slug}` — melihat detail sebuah slug.
- `GET /{slug}` — redirect ke URL asli (via rewrite `.htaccess`).

## Keamanan yang Sudah Diterapkan

- Semua query database memakai **PDO prepared statement** (parameter binding), tidak ada
  string SQL yang dirakit manual dari input user → aman dari SQL Injection.
- Validasi URL tujuan hanya mengizinkan skema `http`/`https`; skema berbahaya seperti
  `javascript:`, `data:`, `vbscript:`, `file:` ditolak sebelum disimpan.
- Slug custom divalidasi ketat (regex whitelist karakter + panjang + daftar kata yang
  dicadangkan seperti `api`, `admin`, `index`, dll) agar tidak bentrok dengan struktur file
  atau menimbulkan celah.
- Folder `database/` dan `includes/` diproteksi `.htaccess` (`Require all denied`) sehingga
  tidak bisa diakses langsung lewat browser.
- `Options -Indexes` diset agar directory listing tidak aktif.
- Header keamanan dasar (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`)
  ditambahkan lewat `.htaccess`.
- Output slug/URL yang ditampilkan di halaman HTML di-escape dengan `htmlspecialchars()`
  untuk mencegah XSS pada halaman pesan error.

## Menjalankan Secara Lokal (opsional, untuk development)

Jika ingin mencoba di komputer lokal sebelum upload (butuh PHP CLI dengan ekstensi
`pdo_sqlite`):

```bash
cd public_html
php -S localhost:8000
```

> Catatan: rewrite `.htaccess` (`domain.com/{slug}`) hanya berfungsi di Apache. Saat testing
> lokal dengan `php -S`, akses slug tetap bisa lewat `redirect.php?slug={slug}` secara manual,
> atau gunakan Apache/XAMPP lokal untuk meniru environment production sepenuhnya.

## Lisensi

Bebas digunakan dan dimodifikasi sesuai kebutuhan Anda.
