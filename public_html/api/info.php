<?php
declare(strict_types=1);

/**
 * GET /api/info.php?slug=abc123
 *
 * Response JSON:
 *   { success, slug, short_url, original_url, created_at, expired_at, click_count, is_expired }
 */

require_once __DIR__ . '/../includes/functions.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'error' => 'Method not allowed. Gunakan GET.'], 405);
}

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';

if ($slug === '') {
    json_response(['success' => false, 'error' => 'Parameter slug wajib diisi.'], 422);
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT slug, original_url, created_at, expired_at, click_count FROM links WHERE slug = :slug');
$stmt->execute(['slug' => $slug]);
$row = $stmt->fetch();

if (!$row) {
    json_response(['success' => false, 'error' => 'Slug tidak ditemukan.'], 404);
}

json_response([
    'success'      => true,
    'slug'         => $row['slug'],
    'short_url'    => base_url() . '/' . $row['slug'],
    'original_url' => $row['original_url'],
    'created_at'   => $row['created_at'],
    'expired_at'   => $row['expired_at'],
    'click_count'  => (int) $row['click_count'],
    'is_expired'   => is_expired($row['expired_at']),
]);
