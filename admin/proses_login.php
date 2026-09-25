<?php
// =============================================
// PROSES LOGIN ADMIN
// =============================================
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/database.php';

// Hanya POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(BASE_URL . '/admin/login.php');

// CSRF
if (!csrf_verify()) {
    set_flash('error', 'Token kadaluarsa, silakan coba lagi.');
    redirect(BASE_URL . '/admin/login.php');
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$_SESSION['old'] = ['username' => $username];

// Validasi input
if (empty($username) || empty($password)) {
    set_flash('error', 'Username dan password wajib diisi.');
    redirect(BASE_URL . '/admin/login.php');
}

// Cari user
try {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
} catch (PDOException $e) {
    set_flash('error', 'DB Error: ' . $e->getMessage());
    redirect(BASE_URL . '/admin/login.php');
}

// Test password
if ($admin && password_verify($password, $admin['password_hash'])) {
    // Sukses! Set session
    $_SESSION['admin_id']       = (int)$admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    clear_old();
    set_flash('success', '🎉 Login berhasil! Selamat datang, ' . e($admin['username']) . '.');
    redirect(BASE_URL . '/admin/dashboard.php');
} else {
    set_flash('error', '❌ Username atau password salah. Silakan coba lagi.');
    redirect(BASE_URL . '/admin/login.php');
}
