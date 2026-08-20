<?php

declare(strict_types=1);

// ============================================================
// Konfigurasi Database - Sesuaikan dengan environment Anda
// ============================================================

// Pilihan:
// 1. Local dev (XAMPP)      : host=127.0.0.1, user=root,     pass=kosong
// 2. Production/testing di sini: host=127.0.0.1, user=simrs,     pass=simrs123

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'simrs');
define('DB_USER', 'simrs');          // ubah ke 'root' untuk XAMPP
define('DB_PASS', 'simrs123');       // ubah ke '' (kosong) untuk XAMPP
define('DB_CHARSET', 'utf8mb4');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
