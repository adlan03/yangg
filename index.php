<?php
session_start();

// Root proyek berada di lokasi file ini (htdocs pada hosting)
$rootPath = __DIR__;

$routes = [
    'dashboard'    => $rootPath . '/dashboard.php',
    'lihat_data'   => $rootPath . '/lihat_data.php',
    'export_excel' => $rootPath . '/export_excel.php',
    'masyarakat'   => $rootPath . '/masyarakat.php',
    'ceklogin'     => $rootPath . '/ceklogin.php',
];

$route = $_GET['page'] ?? 'dashboard';

if (!array_key_exists($route, $routes) || !file_exists($routes[$route])) {
    http_response_code(404);
    echo '<h1>404 - Halaman tidak ditemukan</h1>';
    exit;
}

require_once $routes[$route];
