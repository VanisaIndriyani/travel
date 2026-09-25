<?php
// =============================================
// LOGOUT ADMIN
// =============================================
require_once __DIR__ . '/../includes/helpers.php';

// Hapus session admin
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);

// Hancurkan session (opsional) tapi hati-hati: session untuk flash message juga hilang
// Jadi lebih baik tidak destroy penuh, biar flash bisa jalan
$_SESSION = [];
session_destroy();

// Start session baru untuk flash
session_start();
set_flash('info', 'Anda telah logout. Terima kasih 🙏');
redirect(BASE_URL . '/admin/login.php');
