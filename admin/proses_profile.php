<?php
// =============================================
// PROSES UPDATE PROFIL ADMIN (username + password)
// =============================================
require_once __DIR__ . '/includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/profile.php');
}

if (!csrf_verify()) {
    set_flash('error', 'Token kadaluarsa, silakan coba lagi.');
    redirect(BASE_URL . '/admin/profile.php');
}

$username         = trim((string)($_POST['username'] ?? ''));
$password_baru    = (string)($_POST['password_baru'] ?? '');
$password_confirm = (string)($_POST['password_confirm'] ?? '');
$password_saat_ini = (string)($_POST['password_saat_ini'] ?? '');

$_SESSION['old'] = ['username' => $username];

if ($username === '' || $password_saat_ini === '') {
    set_flash('error', 'Username dan password saat ini wajib diisi.');
    redirect(BASE_URL . '/admin/profile.php');
}

if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
    set_flash('error', 'Username harus 3–50 karakter (huruf, angka, titik, underscore, atau strip).');
    redirect(BASE_URL . '/admin/profile.php');
}

// Verifikasi password saat ini
if (!password_verify($password_saat_ini, $admin_data['password_hash'])) {
    set_flash('error', 'Password saat ini salah. Perubahan dibatalkan.');
    redirect(BASE_URL . '/admin/profile.php');
}

$ubahPassword = ($password_baru !== '');
if ($ubahPassword) {
    if (strlen($password_baru) < 6) {
        set_flash('error', 'Password baru minimal 6 karakter.');
        redirect(BASE_URL . '/admin/profile.php');
    }
    if ($password_baru !== $password_confirm) {
        set_flash('error', 'Konfirmasi password baru tidak cocok.');
        redirect(BASE_URL . '/admin/profile.php');
    }
}

// Username unik (kecuali milik sendiri)
try {
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ? LIMIT 1");
    $stmt->execute([$username, $admin_id]);
    if ($stmt->fetch()) {
        set_flash('error', 'Username sudah dipakai akun lain. Pilih username berbeda.');
        redirect(BASE_URL . '/admin/profile.php');
    }
} catch (PDOException $e) {
    set_flash('error', 'Gagal memeriksa username. Silakan coba lagi.');
    redirect(BASE_URL . '/admin/profile.php');
}

try {
    if ($ubahPassword) {
        $hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admins SET username = ?, password_hash = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$username, $hash, $admin_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE admins SET username = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$username, $admin_id]);
    }
} catch (PDOException $e) {
    set_flash('error', 'Gagal menyimpan profil. Silakan coba lagi.');
    redirect(BASE_URL . '/admin/profile.php');
}

$_SESSION['admin_username'] = $username;
clear_old();

$pesan = $ubahPassword
    ? 'Profil berhasil diperbarui. Username dan password sudah diganti.'
    : 'Profil berhasil diperbarui. Username sudah diganti.';
set_flash('success', $pesan);
redirect(BASE_URL . '/admin/profile.php');
