<?php
// =============================================
// PROSES HAPUS BOOKING (GET dengan CSRF token dari link)
// =============================================
require_once __DIR__ . '/includes/auth_check.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('error', 'ID booking tidak valid.');
    redirect(BASE_URL . '/admin/bookings.php');
}

// Cek CSRF dari query param (GET). Jangan destroy token biar halaman booking tetap punya token valid lainnya.
if (!csrf_verify('get', false)) {
    set_flash('error', 'Token keamanan tidak valid. Kembali ke halaman booking lalu coba lagi.');
    redirect(BASE_URL . '/admin/bookings.php');
}

try {
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
    $ok = $stmt->execute([$id]);
    if ($ok && $stmt->rowCount() > 0) {
        set_flash('success', "🗑️ Booking #MT-{$id} berhasil dihapus.");
    } else {
        set_flash('warning', "Tidak ada booking dengan ID tersebut (mungkin sudah terhapus).");
    }
} catch (PDOException $e) {
    set_flash('error', 'DB Error: ' . $e->getMessage());
}
redirect(BASE_URL . '/admin/bookings.php');
