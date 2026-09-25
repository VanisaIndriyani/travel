<?php
// =============================================
// DATABASE CONNECTION - PDO (aman dari SQL Injection)
// Default setting Laragon: host=localhost, user=root, pw=kosong
// =============================================

require_once __DIR__ . '/config.php';

// Setting DB - Edit disini jika perlu
$DB_HOST = 'localhost';
$DB_NAME = 'mustika_travel';   // Nama database (jangan lupa buat di phpMyAdmin ya)
$DB_USER = 'root';             // Default Laragon = root
$DB_PASS = '';                 // Default Laragon = kosong (kalau XAMPP biasanya juga kosong)

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Jika koneksi gagal, tampilkan pesan yang mudah dimengerti
    die("
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #fee2e2; background: #fef2f2; border-radius: 8px;'>
        <h2 style='color: #991b1b; margin-top: 0;'>⚠️ Database Error</h2>
        <p><strong>Website Mustika Travel belum bisa dijalankan.</strong></p>
        <p>Penyebab: Gagal konek ke database <strong>'mustika_travel'</strong>.</p>
        <hr style='border-color: #fecaca;'>
        <p><strong>Cara memperbaiki (1-2-3):</strong></p>
        <ol style='line-height: 1.7;'>
            <li>Buka <strong>phpMyAdmin</strong> Laragon (biasanya http://localhost/phpmyadmin)</li>
            <li>Buat database baru bernama <code style='background:#fff; padding: 2px 6px; border-radius:4px;'>mustika_travel</code></li>
            <li>Import file <code style='background:#fff; padding: 2px 6px; border-radius:4px;'>database.sql</code> yang ada di folder project</li>
        </ol>
        <p style='color: #6b7280; font-size: 0.9em;'>Detail error: " . htmlspecialchars($e->getMessage()) . "</p>
    </div>
    ");
}
