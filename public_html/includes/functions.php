<?php
declare(strict_types=1);

/**
 * URL Shortener - Core helpers
 * Semua akses database & validasi input dipusatkan di sini.
 */

function get_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dbDir = __DIR__ . '/../database';
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }
    $dbFile = $dbDir . '/links.sqlite';

    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS links (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT NOT NULL UNIQUE,
            original_url TEXT NOT NULL,
            created_at TEXT NOT NULL,
            expired_at TEXT NULL,
            click_count INTEGER NOT NULL DEFAULT 0
        )'
    );
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_links_slug ON links(slug)');

    return $pdo;
}

/** Kata-kata yang tidak boleh dipakai sebagai slug karena bentrok dengan struktur folder/file */
function reserved_slugs(): array
{
    return [
        'index', 'api', 'assets', 'database', 'includes',
        'redirect', 'readme', 'favicon.ico', '.htaccess',
        'style', 'script', 'admin', 'login',
    ];
}

function is_valid_slug(string $slug): bool
{
    if ($slug === '') {
        return false;
    }
    $len = function_exists('mb_strlen') ? mb_strlen($slug) : strlen($slug);
    if ($len < 3 || $len > 32) {
        return false;
    }
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
        return false;
    }
    if (in_array(strtolower($slug), reserved_slugs(), true)) {
        return false;
    }
    return true;
}

function generate_slug(int $length = 6): string
{
    // Tanpa karakter yang mirip: 0/O, 1/l/I
    $chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $max = strlen($chars) - 1;
    $slug = '';
    for ($i = 0; $i < $length; $i++) {
        $slug .= $chars[random_int(0, $max)];
    }
    return $slug;
}

function generate_unique_slug(PDO $pdo, int $length = 6, int $maxAttempts = 20): string
{
    for ($i = 0; $i < $maxAttempts; $i++) {
        $slug = generate_slug($length);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM links WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $slug;
        }
    }
    // Jika masih bentrok terus, perpanjang slug
    return generate_unique_slug($pdo, $length + 1, $maxAttempts);
}

/**
 * Validasi & sanitasi URL tujuan.
 * Hanya mengizinkan skema http/https, menolak javascript:, data:, vbscript:, file:, dll.
 */
function is_safe_url(string $url): bool
{
    $url = trim($url);
    if ($url === '') {
        return false;
    }

    // Tolak skema berbahaya lebih awal (sebelum di-parse)
    if (preg_match('/^\s*(javascript|data|vbscript|file|about)\s*:/i', $url)) {
        return false;
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    $parts = parse_url($url);
    if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
        return false;
    }

    $scheme = strtolower($parts['scheme']);
    if (!in_array($scheme, ['http', 'https'], true)) {
        return false;
    }

    $host = strtolower($parts['host']);
    $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];
    if (in_array($host, $blockedHosts, true)) {
        return false;
    }

    return true;
}

function sanitize_url(string $url): string
{
    return filter_var(trim($url), FILTER_SANITIZE_URL);
}

/**
 * Terima input tanggal dari <input type="date"> (Y-m-d) atau datetime lengkap,
 * kembalikan format 'Y-m-d H:i:s' atau null jika kosong/tidak valid.
 */
function normalize_expired_at(?string $input): ?string
{
    $input = trim((string) $input);
    if ($input === '') {
        return null;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
        // Hanya tanggal -> anggap berlaku sampai akhir hari itu
        $ts = strtotime($input . ' 23:59:59');
    } else {
        $ts = strtotime($input);
    }

    if ($ts === false) {
        return null;
    }

    return date('Y-m-d H:i:s', $ts);
}

function is_expired(?string $expiredAt): bool
{
    if ($expiredAt === null || $expiredAt === '') {
        return false;
    }
    return strtotime($expiredAt) < time();
}

function base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . $host;
}

function json_response(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
