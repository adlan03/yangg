<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($username === 'admin' && $password === 'admin123') {
    $_SESSION['login'] = 1;
    $_SESSION['username'] = 'admin';
    header('Location: index.php');
    exit;
}

echo "<script>alert('Username atau password salah');</script>";
echo "<meta http-equiv='refresh' content='1;url=login.php'>";
