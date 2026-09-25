<?php
// =============================================
// MUSTIKA TRAVEL - GLOBAL CONFIGURATION
// Edit semua setting dasar disini aja ya kaak
// =============================================

// 1. Identitas Website
define('SITE_NAME', 'Mustika Travel');
define('SITE_TAGLINE', 'Perjalanan Aman, Nyaman, Sampai Tujuan');
define('SITE_FULL_NAME', 'Mustika Travel - Travel Door to Door Terpercaya');

// 2. Harga Tiket Default (harga dasar per orang untuk booking online)
// Catatan: Admin bisa edit manual total_harga tiap booking di dashboard
define('HARGA_TIKET_DEFAULT', 200000); // Rp 200.000 / org

// 3. Kontak Admin - EDIT DISINI YA KAAK
define('WA_ADMIN', '62882003082936'); // Ganti nomor WA admin disini
define('WA_ADMIN_DISPLAY', '+62 882-0030-82936'); // Tampilan di website
define('ALAMAT_KANTOR', 'Jl. Contoh No. 123, Blora, Jawa Tengah'); // Ganti alamat disini
define('EMAIL_ADMIN', 'admin@mustikatravel.com');

// 4. Daftar Jurusan / Rute Travel (DEFAULT AWAL SAAT INSTALL)
// NANTI SETELAH INSTALL, ADMIN BISA TAMBAH / EDIT / HAPUS & GANTI HARGA DARI DASHBOARD ADMIN LANGSUNG!
// Format: 'Nama Rute (asal - tujuan)' => harga per orang (integer, TANPA TITIK / RP)
$DAFTAR_JURUSAN = [
    'Blora - Semarang'   => 200000,
    'Blora - Solo'       => 250000,
    'Blora - Jepara'     => 180000,
    'Blora - Surabaya'   => 300000,
    'Blora - Malang'     => 350000,
    'Blora - Jember'     => 400000,
    'Blora - Banyuwangi' => 450000,
    'Blora - Denpasar'   => 650000,
];
// (NOTE: $DAFTAR_JURUSAN di atas HANYA DIPAKAI SEKALI SAAT install.php import default ke tabel `rutes` database)

// 5. Info Pembatalan
define('PEMBATALAN_INFO', 'Pembatalan tiket dikenakan denda 50% dari harga tiket');

// 6. Default Admin Login Credential (default pertama install)
// Setelah login, sebaiknya ganti password di database ya
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', 'admin123');

// 7. URL Path (sesuaikan jika domain berbeda / subfolder)
// Kalau di Laragon biasanya localhost atau nama_project.test
// PENTING: JANGAN SAMPAI ADA AKHIR GARIS MIRING (/) DI AKHIR!
define('BASE_URL', 'http://localhost/SEPTEMBER/Travel');

// 8. Session Start (untuk flash message & admin login)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');
