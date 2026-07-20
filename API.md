# API Documentation — URL Shortener

Base URL: `https://domain-anda.com`

Semua endpoint mengembalikan JSON. Endpoint `shorten.php` juga menerima data `application/json`
maupun `application/x-www-form-urlencoded`.

---

## 1. Membuat Short URL

```
POST /api/shorten.php
Content-Type: application/json
```

### Body Parameters

| Field         | Wajib | Tipe   | Keterangan                                                        |
|---------------|-------|--------|---------------------------------------------------------------------|
| `original_url`| ✅    | string | URL tujuan, harus diawali `http://` atau `https://`                |
| `custom_slug` | ❌    | string | 3-32 karakter `[a-zA-Z0-9_-]`. Kosongkan untuk slug acak otomatis. |
| `expired_at`  | ❌    | string | Format `YYYY-MM-DD` atau `YYYY-MM-DD HH:MM:SS`. Kosong = permanen. |

### Contoh Request

```bash
curl -X POST https://domain-anda.com/api/shorten.php \
  -H "Content-Type: application/json" \
  -d '{
    "original_url": "https://www.contoh.com/artikel/tips-produktivitas",
    "custom_slug": "tips-produktif",
    "expired_at": "2026-12-31"
  }'
```

### Contoh Response — Sukses (201/200)

```json
{
    "success": true,
    "slug": "tips-produktif",
    "short_url": "https://domain-anda.com/tips-produktif",
    "original_url": "https://www.contoh.com/artikel/tips-produktivitas",
    "created_at": "2026-07-20 10:15:00",
    "expired_at": "2026-12-31 23:59:59"
}
```

### Contoh Request Tanpa Custom Slug & Tanpa Expired (Permanen)

```bash
curl -X POST https://domain-anda.com/api/shorten.php \
  -H "Content-Type: application/json" \
  -d '{"original_url": "https://www.contoh.com"}'
```

```json
{
    "success": true,
    "slug": "aZ3kX9",
    "short_url": "https://domain-anda.com/aZ3kX9",
    "original_url": "https://www.contoh.com",
    "created_at": "2026-07-20 10:16:00",
    "expired_at": null
}
```

### Contoh Response — Gagal

| Kondisi                                | HTTP Status | Body                                                                                          |
|-----------------------------------------|-------------|-------------------------------------------------------------------------------------------------|
| URL tidak valid / skema berbahaya      | 422         | `{"success": false, "error": "URL tidak valid. Hanya URL dengan skema http/https yang diperbolehkan."}` |
| Custom slug tidak valid                 | 422         | `{"success": false, "error": "Custom slug tidak valid. ..."}`                                  |
| Slug sudah dipakai                      | 409         | `{"success": false, "error": "Slug sudah digunakan, silakan pilih slug lain."}`                |
| Format expired_at salah                 | 422         | `{"success": false, "error": "Format expired_at tidak valid. ..."}`                             |
| Method selain POST                      | 405         | `{"success": false, "error": "Method not allowed. Gunakan POST."}`                              |

---

## 2. Cek Detail Slug

```
GET /api/info.php?slug={slug}
```

### Contoh Request

```bash
curl "https://domain-anda.com/api/info.php?slug=tips-produktif"
```

### Contoh Response — Sukses

```json
{
    "success": true,
    "slug": "tips-produktif",
    "short_url": "https://domain-anda.com/tips-produktif",
    "original_url": "https://www.contoh.com/artikel/tips-produktivitas",
    "created_at": "2026-07-20 10:15:00",
    "expired_at": "2026-12-31 23:59:59",
    "click_count": 42,
    "is_expired": false
}
```

### Contoh Response — Slug Tidak Ditemukan

```json
{
    "success": false,
    "error": "Slug tidak ditemukan."
}
```

HTTP Status: `404`

---

## 3. Redirect (Akses Short URL)

```
GET /{slug}
```

Bukan endpoint JSON — ini adalah link short URL itu sendiri, contoh:
`https://domain-anda.com/tips-produktif`

- Jika slug **ditemukan & belum expired** → HTTP `302 Found` dengan header `Location` ke
  `original_url`, dan `click_count` bertambah 1.
- Jika slug **tidak ditemukan** → HTTP `404`, menampilkan halaman "Link Tidak Ditemukan".
- Jika slug **sudah expired** → HTTP `410 Gone`, menampilkan halaman "Link Sudah Kedaluwarsa".

---

## Catatan

- Semua endpoint API mengirim header `Access-Control-Allow-Origin: *` sehingga bisa dipanggil
  dari domain/aplikasi lain (public API).
- Slug bersifat case-sensitive.
- `click_count` hanya bertambah saat slug diakses lewat `GET /{slug}` (redirect), bukan lewat
  `api/info.php`.
