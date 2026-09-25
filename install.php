<?php
// =============================================
// INSTALLER - Jalankan SEKALI untuk membuat database & tabel otomatis
// Cara pakai: Buka http://localhost/NAMA_FOLDER/install.php di browser
// Setelah sukses, file install.php SEBAIKNYA dihapus ya kaak!
// =============================================
require_once __DIR__ . '/config/config.php';

$step = 1;
$msg = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['install'])) {
    $DB_HOST = 'localhost';
    $DB_NAME = trim($_POST['db_name'] ?? 'mustika_travel');
    $DB_USER = trim($_POST['db_user'] ?? 'root');
    $DB_PASS = $_POST['db_pass'] ?? '';

    // Step 1: Koneksi ke MySQL tanpa pilih database dulu
    try {
        $pdo_root = new PDO("mysql:host={$DB_HOST};charset=utf8mb4", $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $msg = "✅ Berhasil koneksi ke MySQL server."; $msgType = 'success';

        // Step 2: Buat database jika belum ada
        $pdo_root->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo_root->exec("USE `{$DB_NAME}`;");
        $msg .= "<br>✅ Database `{$DB_NAME}` siap digunakan.";
        $step = 2;

        // Step 3a: Buat tabel admins
        $sqlAdmins = "CREATE TABLE IF NOT EXISTS `admins` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `username` VARCHAR(50) NOT NULL UNIQUE,
          `password_hash` VARCHAR(255) NOT NULL,
          `nama_lengkap` VARCHAR(100) DEFAULT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo_root->exec($sqlAdmins);

        // Insert default admin jika belum ada (username: admin, password: admin123)
        $cek = $pdo_root->query("SELECT COUNT(*) FROM admins WHERE username = 'admin'")->fetchColumn();
        if ($cek == 0) {
            $hash_admin123 = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo_root->exec("INSERT INTO admins (username, password_hash, nama_lengkap) VALUES
                ('admin', '{$hash_admin123}', 'Administrator Mustika Travel')");
        }
        $msg .= "<br>✅ Tabel `admins` siap. Default login: <strong>admin</strong> / <strong>admin123</strong>";

        // Step 3b: Buat tabel rutes (Daftar Rute + Harga per orang - bisa diatur dari dashboard admin)
        $sqlRutes = "CREATE TABLE IF NOT EXISTS `rutes` (
          `id`          INT AUTO_INCREMENT PRIMARY KEY,
          `nama_rute`   VARCHAR(200) NOT NULL,
          `harga`       INT          NOT NULL DEFAULT 0,
          `is_aktif`    TINYINT(1)   NOT NULL DEFAULT 1,
          `urutan`      INT          NOT NULL DEFAULT 0,
          `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          UNIQUE KEY `unik_nama_rute` (`nama_rute`),
          INDEX `idx_aktif` (`is_aktif`),
          INDEX `idx_urutan` (`urutan`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo_root->exec($sqlRutes);

        // Insert default rutes dari $DAFTAR_JURUSAN (config.php) jika tabel kosong
        $cekRutes = $pdo_root->query("SELECT COUNT(*) FROM rutes")->fetchColumn();
        if ($cekRutes == 0 && !empty($GLOBALS['DAFTAR_JURUSAN'])) {
            global $DAFTAR_JURUSAN;
            $no = 1;
            foreach ($DAFTAR_JURUSAN as $namaRute => $hargaRute) {
                $namaRute = addslashes(trim($namaRute));
                $hargaRute = (int)$hargaRute;
                $pdo_root->exec("INSERT INTO rutes (nama_rute, harga, urutan) VALUES ('{$namaRute}', {$hargaRute}, {$no})");
                $no++;
            }
        }
        $msg .= "<br>✅ Tabel `rutes` siap + import " . count($DAFTAR_JURUSAN) . " rute default (harga tiap rute BISA DIUBAH dari dashboard admin!)";

        // Step 3c: Buat tabel bookings (ditambah kolom id_rute, rute, harga_rute_saat_booking)
        $sqlBookings = "CREATE TABLE IF NOT EXISTS `bookings` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `id_rute`          INT          NULL,
          `rute`             VARCHAR(200) NOT NULL DEFAULT '',
          `harga_rute_saat_booking` INT   NOT NULL DEFAULT 0,
          `nama` VARCHAR(150) NOT NULL,
          `no_hp` VARCHAR(20) NOT NULL,
          `alamat_jemput` TEXT NOT NULL,
          `alamat_tujuan` TEXT NOT NULL,
          `jumlah_kursi` INT NOT NULL DEFAULT 1,
          `tanggal_berangkat` DATE NOT NULL,
          `jam_jemput` TIME NOT NULL,
          `barang_bawaan` TEXT NULL,
          `total_harga` INT NOT NULL DEFAULT 0,
          `status` ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
          `source` ENUM('online','manual') NOT NULL DEFAULT 'online',
          `catatan_admin` TEXT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          INDEX `idx_rute` (`id_rute`),
          INDEX `idx_tanggal` (`tanggal_berangkat`),
          INDEX `idx_status` (`status`),
          INDEX `idx_created` (`created_at`),
          CONSTRAINT `fk_booking_rute` FOREIGN KEY (`id_rute`) REFERENCES `rutes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo_root->exec($sqlBookings);

        // Tambah kolom baru ke bookings JIKA kolom belum ada (untuk upgrade existing DB bukan dari install fresh)
        try { $pdo_root->exec("ALTER TABLE `bookings` ADD COLUMN `id_rute` INT NULL FIRST"); } catch (Exception $e) {}
        try { $pdo_root->exec("ALTER TABLE `bookings` ADD COLUMN `rute` VARCHAR(200) NOT NULL DEFAULT '' AFTER `id_rute`"); } catch (Exception $e) {}
        try { $pdo_root->exec("ALTER TABLE `bookings` ADD COLUMN `harga_rute_saat_booking` INT NOT NULL DEFAULT 0 AFTER `rute`"); } catch (Exception $e) {}

        $msg .= "<br>✅ Tabel `bookings` siap + kolom id_rute & rute & harga_saat_booking ditambahkan.";

        $sqlCarters = "CREATE TABLE IF NOT EXISTS `carters` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `nama_unit` VARCHAR(200) NOT NULL,
          `icon_fa` VARCHAR(80) NOT NULL DEFAULT 'fa-car',
          `kapasitas` VARCHAR(80) NOT NULL DEFAULT '',
          `deskripsi` TEXT NULL,
          `warna_grad` VARCHAR(120) NOT NULL DEFAULT 'from-navy-800 to-navy-950',
          `gambar_url` VARCHAR(500) NOT NULL DEFAULT '',
          `fitur_list` TEXT NULL,
          `urutan` INT NOT NULL DEFAULT 0,
          `is_aktif` TINYINT(1) NOT NULL DEFAULT 1,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          UNIQUE KEY `unik_nama_unit` (`nama_unit`),
          INDEX `idx_aktif` (`is_aktif`),
          INDEX `idx_urutan` (`urutan`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo_root->exec($sqlCarters);
        $cekCarters = (int)$pdo_root->query("SELECT COUNT(*) FROM carters")->fetchColumn();
        if ($cekCarters === 0) {
            $seedCarters = [
                ['Unit Avanza / Xenia','fa-car','5 - 7 Kursi','Cocok untuk keluarga kecil, meeting kecil, drop off bandara.','from-emerald-400 to-teal-600','https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=700&q=80','["AC Dingin","Audio MP3","Bagasi Luas","Hemat BBM"]',1],
                ['Elf Short','fa-van-shuttle','15 Kursi','Pas untuk rombongan 10-14 orang, tour kota, study tour sekolah.','from-blue-400 to-primary-700','https://images.unsplash.com/photo-1609521263047-f8f205293f24?w=700&q=80','["Full AC","15 Seat Luas","Audio Premium","Charging USB","Bagasi Luas"]',2],
                ['Elf Long','fa-bus','19 Kursi','Pilihan pas rombongan 15-18 orang, body panjang, suspensi nyaman.','from-amber-400 to-orange-600','https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=700&q=80','["Full AC","19 Seat Reclining","Karaoke Mini","LED TV","Snack & Minum"]',3],
                ['Bus Pariwisata','fa-bus-alt','30 - 50 Kursi','Untuk rombongan BESAR! Wisata, Outbound, Company outing, Wedding.','from-purple-400 to-indigo-700','https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=700&q=80','["Full AC Dingin","Reclining Seat","Karaoke + TV LED","Toilet onboard","Selimut + Snack"]',4],
            ];
            $insC = $pdo_root->prepare("INSERT INTO carters (nama_unit,icon_fa,kapasitas,deskripsi,warna_grad,gambar_url,fitur_list,urutan,is_aktif) VALUES (?,?,?,?,?,?,?,?,1)");
            foreach ($seedCarters as $sc) $insC->execute($sc);
        }
        $msg .= "<br>✅ Tabel `carters` siap (paket Carter PP/Drop bisa diubah dari dashboard admin).";
        $step = 3;

        // Step 5: Update config/database.php dengan data koneksi ini
        $db_php = __DIR__ . '/config/database.php';
        $content = file_get_contents($db_php);
        $content = preg_replace("/\$DB_NAME = '[^']+';/", "\$DB_NAME = '{$DB_NAME}';", $content);
        $content = preg_replace("/\$DB_USER = '[^']+';/",  "\$DB_USER = '{$DB_USER}';",  $content);
        $content = preg_replace("/\$DB_PASS = '[^']*';/",  "\$DB_PASS = '{$DB_PASS}';",  $content);
        file_put_contents($db_php, $content);
        $msg .= "<br>✅ File config/database.php otomatis di-update sesuai setting.";
        $step = 4;

        // Selesai
        $msg .= "<br><br><strong class='text-green-700 text-lg'>🎉 INSTALASI BERHASIL!</strong>".
                "<br>Anda sekarang bisa:".
                "<ul class='list-disc pl-5 space-y-1 mt-1'>".
                "<li><a href='index.php' class='text-blue-600 font-semibold underline'>Lihat Halaman Depan</a> (Booking Online)</li>".
                "<li><a href='admin/login.php' class='text-blue-600 font-semibold underline'>Login Admin</a> (admin / admin123)</li>".
                "</ul>".
                "<br><span class='text-orange-600 text-sm'>⚠️ Catatan: Setelah ini sebaiknya <strong>HAPUS file install.php</strong> untuk keamanan ya!</span>";

    } catch (PDOException $e) {
        $msg = "❌ Error: " . $e->getMessage();
        $msgType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalasi - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>body{font-family: system-ui, -apple-system, sans-serif;}</style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-sky-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-xl">
        <div class="bg-white rounded-2xl shadow-2xl p-8 border border-slate-100">
            <div class="flex items-center gap-4 mb-6">
                <img src="logo.jpeg" alt="logo" class="w-14 h-14 rounded-full border-4 border-blue-100 object-cover shadow">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900">Install Mustika Travel</h1>
                    <p class="text-sm text-slate-500">Setup otomatis database & tabel - 1 menit selesai</p>
                </div>
            </div>

            <!-- Steps -->
            <div class="flex items-center gap-2 mb-6 text-xs font-semibold">
                <?php foreach (['Koneksi DB','Buat DB','Buat Tabel','Selesai'] as $i=>$lbl): $n = $i+1; $cls = ($n <= $step) ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-500';?>
                    <div class="flex items-center gap-1.5">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center <?= $cls ?>"><?= $n ?></div>
                        <span class="<?= ($n <= $step) ? 'text-slate-900' : 'text-slate-400' ?> hidden sm:block"><?= $lbl ?></span>
                    </div>
                    <?php if($i<3):?><div class="flex-1 h-0.5 <?= ($n<$step)?'bg-blue-400':'bg-slate-200' ?> mx-1"></div><?php endif; ?>
                <?php endforeach; ?>
            </div>

            <?php if($msg): ?>
                <div class="mb-5 p-4 rounded-lg text-sm border
                    <?= ($msgType==='success') ? 'bg-green-50 border-green-200 text-green-800'
                                               : 'bg-red-50 border-red-200 text-red-800' ?>">
                    <?= $msg ?>
                </div>
            <?php endif; ?>

            <?php if($step < 4): ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="install" value="1">
                <div>
                    <label class="block text-sm font-semibold mb-1 text-slate-700">Nama Database</label>
                    <input name="db_name" value="mustika_travel" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1 text-slate-700">Username MySQL <span class="text-slate-400">(Laragon/XAMPP default: root)</span></label>
                    <input name="db_user" value="root" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1 text-slate-700">Password MySQL <span class="text-slate-400">(Laragon/XAMPP default: KOSONGI)</span></label>
                    <input name="db_pass" type="text" value="" placeholder="Kosongkan jika tidak ada password" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold shadow-lg hover:shadow-xl transition">
                    <i class="fa-solid fa-rocket mr-2"></i> Jalankan Instalasi Sekarang
                </button>
            </form>
            <p class="mt-4 text-xs text-slate-500 text-center">
                ⚡ Instalasi ini hanya dijalankan <strong>SEKALI SAJA</strong>. Setelah sukses, hapus file <code class="bg-slate-100 px-1.5 py-0.5 rounded">install.php</code>.
            </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
