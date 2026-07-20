<?php
declare(strict_types=1);

/**
 * Semua request domain.com/{slug} diarahkan ke sini oleh .htaccess
 * (redirect.php?slug={slug}), lalu file ini melakukan redirect ke URL asli.
 */

require_once __DIR__ . '/includes/functions.php';

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';

$pdo = get_db();
$row = null;

if ($slug !== '') {
    $stmt = $pdo->prepare('SELECT * FROM links WHERE slug = :slug');
    $stmt->execute(['slug' => $slug]);
    $row = $stmt->fetch();
}

function render_message_page(string $title, string $message, int $statusCode): void
{
    http_response_code($statusCode);
    ?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title) ?></title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="container center-page">
    <div class="card message-card">
        <div class="status-code"><?= $statusCode ?></div>
        <h1><?= htmlspecialchars($title) ?></h1>
        <p><?= htmlspecialchars($message) ?></p>
        <a class="btn" href="/">Kembali ke Beranda</a>
    </div>
</div>
</body>
</html>
<?php
    exit;
}

if (!$row) {
    render_message_page(
        'Link Tidak Ditemukan',
        'Link pendek yang Anda tuju tidak ditemukan atau sudah dihapus.',
        404
    );
}

if (is_expired($row['expired_at'])) {
    render_message_page(
        'Link Sudah Kedaluwarsa',
        'Link pendek ini sudah melewati batas waktu (expired) dan tidak dapat digunakan lagi.',
        410
    );
}

// Catat klik lalu redirect ke URL asli
$update = $pdo->prepare('UPDATE links SET click_count = click_count + 1 WHERE id = :id');
$update->execute(['id' => $row['id']]);

header('Location: ' . $row['original_url'], true, 302);
exit;
