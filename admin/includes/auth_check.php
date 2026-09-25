<?php
// =============================================
// AUTH CHECK - dipakai di SEMUA halaman admin KECUALI login.php
// =============================================
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/database.php';

// Jika belum login, tendang ke login
if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_username'])) {
    set_flash('warning', 'Silakan login terlebih dahulu untuk mengakses halaman admin.');
    redirect(BASE_URL . '/admin/login.php');
}

$admin_id   = (int)$_SESSION['admin_id'];
$admin_user = $_SESSION['admin_username'];

// Ambil data admin lengkap (untuk tampilkan nama dll)
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
$stmt->execute([$admin_id]);
$admin_data = $stmt->fetch();
if (!$admin_data) {
    // Akun tidak valid, logout
    session_destroy();
    redirect(BASE_URL . '/admin/login.php');
}
$admin_name = $admin_data['nama_lengkap'] ?: $admin_data['username'];
