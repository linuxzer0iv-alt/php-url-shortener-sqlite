<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>URL Shortener</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="container">
    <header class="site-header">
        <h1>🔗 URL Shortener</h1>
        <p class="subtitle">Persingkat URL panjangmu dalam sekejap, gratis dan sederhana.</p>
    </header>

    <div class="card">
        <form id="shorten-form" autocomplete="off">
            <div class="form-group">
                <label for="original_url">URL Asli <span class="required">*</span></label>
                <input type="url" id="original_url" name="original_url" placeholder="https://contoh.com/artikel-panjang-sekali" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="custom_slug">Custom Slug (opsional)</label>
                    <input type="text" id="custom_slug" name="custom_slug" placeholder="misal: promo-agustus" pattern="[a-zA-Z0-9_-]{3,32}" maxlength="32">
                    <small>3-32 karakter, huruf/angka/-/_. Kosongkan untuk slug acak.</small>
                </div>
                <div class="form-group">
                    <label for="expired_at">Tanggal Kedaluwarsa (opsional)</label>
                    <input type="date" id="expired_at" name="expired_at">
                    <small>Kosongkan jika link berlaku permanen.</small>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="submit-btn">Perpendek URL</button>
        </form>

        <div id="error-box" class="alert alert-error" hidden></div>

        <div id="result-box" class="result-box" hidden>
            <label>Short URL Anda:</label>
            <div class="result-row">
                <input type="text" id="result-url" readonly>
                <button type="button" class="btn btn-secondary" id="copy-btn">Salin</button>
            </div>
            <ul class="result-meta">
                <li><strong>Original URL:</strong> <span id="result-original"></span></li>
                <li><strong>Slug:</strong> <span id="result-slug"></span></li>
                <li><strong>Kedaluwarsa:</strong> <span id="result-expired"></span></li>
            </ul>
        </div>
    </div>

    <div class="card">
        <h2>Cek Detail Link</h2>
        <div class="form-row">
            <div class="form-group" style="flex:1">
                <label for="check-slug">Slug</label>
                <input type="text" id="check-slug" placeholder="misal: abc123">
            </div>
        </div>
        <button type="button" class="btn btn-secondary" id="check-btn">Cek Info</button>
        <pre id="check-result" class="check-result" hidden></pre>
    </div>

    <footer class="site-footer">
        <p>API publik tersedia di <code>/api/shorten.php</code> dan <code>/api/info.php</code>.</p>
    </footer>
</div>

<script src="/assets/script.js"></script>
</body>
</html>
