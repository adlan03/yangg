<?php
// Versi tanpa database: file ini hanya memuat konstanta jalur/URL.

// ====== PATH & URL BASE ======
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

if (!defined('BASE_URL')) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme  = $isHttps ? 'https' : 'http';

    if (isset($_SERVER['HTTP_HOST'])) {
        $host   = $_SERVER['HTTP_HOST'];
        $base   = trim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
        $prefix = $base === '' ? '' : $base . '/';
        define('BASE_URL', $scheme . '://' . $host . '/' . $prefix);
    } else {
        define('BASE_URL', 'http://localhost/');
    }
}

// Versi tanpa database: tidak ada koneksi database yang dibuat di sini.
