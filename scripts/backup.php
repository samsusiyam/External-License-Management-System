<?php

/**
 * ELMS Database Backup (CLI)
 *
 * Creates a timestamped SQL dump in storage/backups and prunes
 * dumps older than the retention window. Intended to be run daily
 * via cron / Windows Task Scheduler.
 *
 * Usage:
 *   php scripts/backup.php
 *   php scripts/backup.php --retain-days=14
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;

$retainDays = 7;
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--retain-days=(\d+)$/', $arg, $m)) {
        $retainDays = (int) $m[1];
    }
}

$host = (string) Config::get('db.host');
$port = (int) Config::get('db.port');
$name = (string) Config::get('db.name');
$user = (string) Config::get('db.user');
$pass = (string) Config::get('db.pass');

$backupDir = dirname(__DIR__) . '/storage/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0775, true);
}

$stamp = date('Y-m-d_His');
$outFile = $backupDir . "/elms_{$name}_{$stamp}.sql";

// Locate mysqldump: prefer XAMPP path on Windows, else rely on PATH.
$candidates = [
    'C:\\xampp\\mysql\\bin\\mysqldump.exe',
    '/usr/bin/mysqldump',
    '/usr/local/bin/mysqldump',
    'mysqldump',
];
$dump = null;
foreach ($candidates as $c) {
    if ($c === 'mysqldump' || is_file($c)) {
        $dump = $c;
        break;
    }
}
if ($dump === null) {
    fwrite(STDERR, "[fail] mysqldump not found.\n");
    exit(1);
}

// Build command. Pass password via env to avoid it showing in process list.
putenv('MYSQL_PWD=' . $pass);
$cmd = sprintf(
    '%s --host=%s --port=%d --user=%s --single-transaction --routines --databases %s',
    escapeshellarg($dump),
    escapeshellarg($host),
    $port,
    escapeshellarg($user),
    escapeshellarg($name)
);

echo "[..] Dumping {$name} -> {$outFile}\n";
$handle = popen($cmd . ' 2>&1', 'r');
if ($handle === false) {
    fwrite(STDERR, "[fail] Could not start mysqldump.\n");
    exit(1);
}
$data = stream_get_contents($handle);
$code = pclose($handle);

if ($code !== 0 || $data === false || str_contains((string) $data, 'Got error')) {
    fwrite(STDERR, "[fail] mysqldump error:\n" . $data . "\n");
    exit(1);
}

file_put_contents($outFile, $data);
$size = round(filesize($outFile) / 1024, 1);
echo "[ok] Backup written ({$size} KB).\n";

// Prune old backups.
$cutoff = time() - ($retainDays * 86400);
$pruned = 0;
foreach (glob($backupDir . '/elms_*.sql') ?: [] as $f) {
    if (filemtime($f) < $cutoff) {
        @unlink($f);
        $pruned++;
    }
}
echo "[ok] Pruned {$pruned} backup(s) older than {$retainDays} days.\n";
echo "[done] Backup complete.\n";
