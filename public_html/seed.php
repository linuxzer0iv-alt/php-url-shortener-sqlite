<?php
declare(strict_types=1);

/**
 * SEEDER — mengisi database dengan data contoh untuk testing/demo.
 *
 * CARA PAKAI:
 *
 * 1) Lewat CLI (kalau hosting punya akses SSH / Terminal cPanel):
 *      php seed.php
 *
 * 2) Lewat browser (kalau tidak ada akses SSH), one-time, harus pakai key:
 *      https://domain-anda.com/seed.php?key=GANTI_KEY_INI
 *
 * ⚠️ PENTING — HAPUS FILE INI SETELAH SELESAI DIPAKAI.
 *    File ini bisa mengisi/mereset data lewat browser dan sengaja dibuat
 *    seminimal mungkin fiturnya, tapi tetap sebaiknya TIDAK dibiarkan
 *    nongkrong permanen di server production. Setelah seeding selesai,
 *    hapus public_html/seed.php lewat File Manager / SSH.
 */

require_once __DIR__ . '/includes/functions.php';

// Ganti nilai ini sebelum upload ke server jika ingin menjalankan lewat browser.
// Kalau dijalankan lewat CLI, key ini tidak diperiksa sama sekali.
const SEED_KEY = 'ganti-key-rahasia-ini';

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    $key = $_GET['key'] ?? '';
    if (!hash_equals(SEED_KEY, (string) $key)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo "403 Forbidden.\n";
        echo "Jalankan lewat CLI (php seed.php) atau tambahkan ?key=SEED_KEY_YANG_BENAR\n";
        echo "(ganti konstanta SEED_KEY di dalam file ini terlebih dahulu).\n";
        exit;
    }
}

$pdo = get_db();

// Opsional: reset tabel dulu sebelum seed ulang.
// Kirim ?reset=1 (browser) atau argumen "reset" (CLI) kalau mau bersih dulu.
$shouldReset = $isCli
    ? in_array('reset', $argv ?? [], true)
    : (($_GET['reset'] ?? '') === '1');

if ($shouldReset) {
    $pdo->exec('DELETE FROM links');
}

// Data contoh: mix slug custom, slug otomatis, permanen, dan expired.
$samples = [
    [
        'slug' => 'anthropic',
        'original_url' => 'https://www.anthropic.com',
        'expired_at' => null, // permanen
    ],
    [
        'slug' => 'claude-docs',
        'original_url' => 'https://docs.claude.com',
        'expired_at' => null,
    ],
    [
        'slug' => 'promo-lama',
        'original_url' => 'https://example.com/promo-2024',
        'expired_at' => '2024-12-31 23:59:59', // sengaja expired, buat contoh halaman 410
    ],
    [
        'slug' => null, // biar di-generate otomatis
        'original_url' => 'https://github.com',
        'expired_at' => null,
    ],
    [
        'slug' => null,
        'original_url' => 'https://www.php.net/manual/en/book.pdo.php',
        'expired_at' => date('Y-m-d 23:59:59', strtotime('+30 days')), // expired 30 hari lagi
    ],
];

$insert = $pdo->prepare(
    'INSERT INTO links (slug, original_url, created_at, expired_at, click_count)
     VALUES (:slug, :original_url, :created_at, :expired_at, :click_count)'
);

$inserted = [];
$skipped = [];

foreach ($samples as $row) {
    $slug = $row['slug'] ?? generate_unique_slug($pdo);

    // Skip kalau slug sudah ada (misal seeder dijalankan dua kali tanpa reset)
    $check = $pdo->prepare('SELECT COUNT(*) FROM links WHERE slug = :slug');
    $check->execute(['slug' => $slug]);
    if ((int) $check->fetchColumn() > 0) {
        $skipped[] = $slug;
        continue;
    }

    $insert->execute([
        'slug' => $slug,
        'original_url' => $row['original_url'],
        'created_at' => date('Y-m-d H:i:s'),
        'expired_at' => $row['expired_at'],
        'click_count' => random_int(0, 50),
    ]);

    $inserted[] = $slug;
}

$summary = [
    'success' => true,
    'inserted' => $inserted,
    'skipped_existing' => $skipped,
    'total_rows_now' => (int) $pdo->query('SELECT COUNT(*) FROM links')->fetchColumn(),
];

if ($isCli) {
    echo "Seeding selesai.\n";
    echo "Ditambahkan (" . count($inserted) . "): " . implode(', ', $inserted) . "\n";
    if ($skipped) {
        echo "Dilewati, sudah ada (" . count($skipped) . "): " . implode(', ', $skipped) . "\n";
    }
    echo "Total baris di tabel links sekarang: {$summary['total_rows_now']}\n";
    echo "\nJangan lupa hapus file seed.php dari server production setelah selesai.\n";
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($summary, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    echo "\n\n";
    echo "PERINGATAN: hapus file public_html/seed.php sekarang setelah selesai seeding.\n";
}
