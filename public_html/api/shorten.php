<?php
declare(strict_types=1);

/**
 * POST /api/shorten.php
 * Body (JSON atau form-urlencoded):
 *   original_url  (wajib)  - URL tujuan, harus http/https
 *   custom_slug   (opsional) - slug kustom 3-32 karakter [a-zA-Z0-9_-]
 *   expired_at    (opsional) - format YYYY-MM-DD atau YYYY-MM-DD HH:MM:SS, kosong = permanen
 *
 * Response JSON:
 *   { success, slug, short_url, original_url, created_at, expired_at }
 */

require_once __DIR__ . '/../includes/functions.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed. Gunakan POST.'], 405);
}

// Terima JSON body maupun form-urlencoded / multipart
$raw = file_get_contents('php://input');
$input = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false && $raw !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
} else {
    $input = $_POST;
}

$originalUrl = isset($input['original_url'])
    ? (string) $input['original_url']
    : (isset($input['url']) ? (string) $input['url'] : '');

$customSlug = isset($input['custom_slug'])
    ? trim((string) $input['custom_slug'])
    : (isset($input['slug']) ? trim((string) $input['slug']) : '');

$expiredInput = isset($input['expired_at']) ? (string) $input['expired_at'] : '';

$originalUrl = sanitize_url($originalUrl);

if (!is_safe_url($originalUrl)) {
    json_response([
        'success' => false,
        'error'   => 'URL tidak valid. Hanya URL dengan skema http/https yang diperbolehkan.',
    ], 422);
}

$pdo = get_db();

if ($customSlug !== '') {
    if (!is_valid_slug($customSlug)) {
        json_response([
            'success' => false,
            'error'   => 'Custom slug tidak valid. Gunakan 3-32 karakter huruf/angka/-/_ dan bukan kata yang dicadangkan.',
        ], 422);
    }

    $check = $pdo->prepare('SELECT COUNT(*) FROM links WHERE slug = :slug');
    $check->execute(['slug' => $customSlug]);
    if ((int) $check->fetchColumn() > 0) {
        json_response([
            'success' => false,
            'error'   => 'Slug sudah digunakan, silakan pilih slug lain.',
        ], 409);
    }

    $slug = $customSlug;
} else {
    $slug = generate_unique_slug($pdo);
}

$expiredAt = null;
if ($expiredInput !== '') {
    $expiredAt = normalize_expired_at($expiredInput);
    if ($expiredAt === null) {
        json_response([
            'success' => false,
            'error'   => 'Format expired_at tidak valid. Gunakan YYYY-MM-DD atau YYYY-MM-DD HH:MM:SS.',
        ], 422);
    }
}

$createdAt = date('Y-m-d H:i:s');

$stmt = $pdo->prepare(
    'INSERT INTO links (slug, original_url, created_at, expired_at, click_count)
     VALUES (:slug, :original_url, :created_at, :expired_at, 0)'
);
$stmt->execute([
    'slug'         => $slug,
    'original_url' => $originalUrl,
    'created_at'   => $createdAt,
    'expired_at'   => $expiredAt,
]);

json_response([
    'success'      => true,
    'slug'         => $slug,
    'short_url'    => base_url() . '/' . $slug,
    'original_url' => $originalUrl,
    'created_at'   => $createdAt,
    'expired_at'   => $expiredAt,
]);
