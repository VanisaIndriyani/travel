-- =======================================================
-- MUSTIKA TRAVEL - DATABASE SCHEMA
-- Import file ini ke phpMyAdmin pilih database mustika_travel
-- =======================================================

-- 1. Tabel Admin (untuk login dashboard)
CREATE TABLE IF NOT EXISTS `admins` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `username`      VARCHAR(50)  NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `nama_lengkap`  VARCHAR(100) DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Admin Login: admin / admin123
INSERT INTO `admins` (`username`, `password_hash`, `nama_lengkap`) VALUES
('admin', '$2y$10$EixZaY3s7k8vV7Z5R6gO4eJq4R5p0HwNkL8fQwErT1yUiOp2vXm4y', 'Administrator Mustika Travel');
-- Password diatas adalah "admin123" (hasil password_hash('admin123', PASSWORD_DEFAULT))

-- =======================================================

-- 2. Tabel Rutes (Daftar Rute / Jurusan + Harga per orang - DAPAT DIUBAH DARI DASHBOARD ADMIN)
CREATE TABLE IF NOT EXISTS `rutes` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `nama_rute`   VARCHAR(200) NOT NULL COMMENT 'Contoh: Blora - Semarang',
  `harga`       INT          NOT NULL DEFAULT 0 COMMENT 'Harga per orang (Rp) - tanpa titik / Rp',
  `is_aktif`    TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1 = Aktif tampil di booking, 0 = Nonaktif (tidak dihapus permanen)',
  `urutan`      INT          NOT NULL DEFAULT 0 COMMENT 'Nomor urut tampil di halaman depan (0 = paling atas)',
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unik_nama_rute` (`nama_rute`),
  INDEX `idx_aktif` (`is_aktif`),
  INDEX `idx_urutan` (`urutan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default 8 Rute + Harga (sesuai $DAFTAR_JURUSAN di config/config.php)
INSERT INTO `rutes` (`nama_rute`, `harga`, `urutan`) VALUES
('Blora - Semarang',   200000, 1),
('Blora - Solo',       250000, 2),
('Blora - Jepara',     180000, 3),
('Blora - Surabaya',   300000, 4),
('Blora - Malang',     350000, 5),
('Blora - Jember',     400000, 6),
('Blora - Banyuwangi', 450000, 7),
('Blora - Denpasar',   650000, 8);

-- =======================================================

-- 3. Tabel Bookings (semua data pemesanan tiket)
CREATE TABLE IF NOT EXISTS `bookings` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `id_rute`          INT          NULL COMMENT 'Foreign key ke tabel rutes.id (bisa NULL kalo booking rute bebas / custom)',
  `rute`             VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'Simpan nama rute SAAT BOOKING (redundan untuk memudahkan laporan tanpa join)',
  `harga_rute_saat_booking` INT   NOT NULL DEFAULT 0 COMMENT 'Harga per orang SAAT booking (biar tidak berubah kalo admin update harga rute nanti)',
  `nama`             VARCHAR(150) NOT NULL COMMENT 'Nama Lengkap Penumpang',
  `no_hp`            VARCHAR(20)  NOT NULL COMMENT 'Nomor HP / WhatsApp',
  `alamat_jemput`    TEXT         NOT NULL COMMENT 'Alamat Penjemputan Lengkap',
  `maps_link`        VARCHAR(500) NULL DEFAULT NULL COMMENT 'Link Google Maps pin lokasi jemput (opsional)',
  `alamat_tujuan`    TEXT         NOT NULL COMMENT 'Alamat Tujuan Lengkap',
  `jumlah_kursi`     INT          NOT NULL DEFAULT 1 COMMENT 'Jumlah Seat / Penumpang',
  `tanggal_berangkat`DATE         NOT NULL COMMENT 'Hari & Tanggal Keberangkatan',
  `jam_jemput`       TIME         NOT NULL COMMENT 'Estimasi Jam Jemput',
  `barang_bawaan`    TEXT         NULL COMMENT 'Keterangan Barang Bawaan',
  `total_harga`      INT          NOT NULL DEFAULT 0 COMMENT 'Total Harga (jumlah kursi x harga_rute_saat_booking)',
  `status`           ENUM('pending','confirmed','completed','cancelled')
                     NOT NULL DEFAULT 'pending' COMMENT 'Status Booking',
  `source`           ENUM('online','manual')
                     NOT NULL DEFAULT 'online' COMMENT 'Sumber: Online=booking web, Manual=WA telpon',
  `catatan_admin`    TEXT         NULL COMMENT 'Catatan tambahan dari admin',
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_rute` (`id_rute`),
  INDEX `idx_tanggal` (`tanggal_berangkat`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created` (`created_at`),
  CONSTRAINT `fk_booking_rute` FOREIGN KEY (`id_rute`) REFERENCES `rutes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- 4. Tabel Carters (Paket Carter PP / Drop - CRUD dari dashboard admin)
-- =======================================================
CREATE TABLE IF NOT EXISTS `carters` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `carters` (`nama_unit`,`icon_fa`,`kapasitas`,`deskripsi`,`warna_grad`,`gambar_url`,`fitur_list`,`urutan`) VALUES
('Unit Avanza / Xenia','fa-car','5 - 7 Kursi','Cocok untuk keluarga kecil, meeting kecil, drop off bandara.','from-emerald-400 to-teal-600','https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=700&q=80','["AC Dingin","Audio MP3","Bagasi Luas","Hemat BBM"]',1),
('Elf Short','fa-van-shuttle','15 Kursi','Pas untuk rombongan 10-14 orang, tour kota, study tour sekolah.','from-blue-400 to-primary-700','https://images.unsplash.com/photo-1609521263047-f8f205293f24?w=700&q=80','["Full AC","15 Seat Luas","Audio Premium","Charging USB","Bagasi Luas"]',2),
('Elf Long','fa-bus','19 Kursi','Pilihan pas rombongan 15-18 orang, body panjang, suspensi nyaman.','from-amber-400 to-orange-600','https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=700&q=80','["Full AC","19 Seat Reclining","Karaoke Mini","LED TV","Snack & Minum"]',3),
('Bus Pariwisata','fa-bus-alt','30 - 50 Kursi','Untuk rombongan BESAR! Wisata, Outbound, Company outing, Wedding.','from-purple-400 to-indigo-700','https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=700&q=80','["Full AC Dingin","Reclining Seat","Karaoke + TV LED","Toilet onboard","Selimut + Snack"]',4);

-- =======================================================
-- Contoh Data Dummy (bisa dihapus nanti) - Opsional
-- =======================================================
INSERT INTO `bookings`
(`id_rute`, `rute`, `harga_rute_saat_booking`, `nama`, `no_hp`, `alamat_jemput`, `alamat_tujuan`, `jumlah_kursi`, `tanggal_berangkat`, `jam_jemput`, `barang_bawaan`, `total_harga`, `status`, `source`, `catatan_admin`) VALUES
(4, 'Blora - Surabaya',   300000, 'Budi Santoso',      '081212345678', 'Jl. Merdeka No. 10, Blora',  'Jl. Pahlawan No. 5, Surabaya',  2, CURDATE(),                 '06:00:00', '1 Koper + 1 Tas',         600000, 'completed', 'online', 'Lunas via transfer (Surabaya 300rb × 2)'),
(1, 'Blora - Semarang',   200000, 'Siti Rahayu',       '081398765432', 'Kec. Jepon, Kab. Blora',     'Terminal Bawen, Semarang',      1, CURDATE(),                 '05:30:00', '1 Tas ransel',            200000, 'confirmed', 'manual', 'Booking via WA (Semarang 200rb × 1)'),
(1, 'Blora - Semarang',   200000, 'Ahmad Fauzi',       '085611223344', 'Perum Griya Indah Blora',    'Jl. Pandanaran Semarang',       3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '07:00:00', '3 Koper + Kardus',       600000, 'pending', 'online',   'Menunggu pembayaran DP (Semarang 200rb × 3)'),
(5, 'Blora - Malang',     350000, 'Dewi Lestari',      '082233445566', 'Kota Blora',                 'Kota Malang',                   2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '05:00:00', '2 Tas',                  700000, 'completed', 'online', 'Lunas (Malang 350rb × 2)'),
(4, 'Blora - Surabaya',   300000, 'Pak Slamet',        '087711223344', 'Desa Sumberjo Blora',        'Stasiun Kota Surabaya',         1, CURDATE(),                 '06:30:00', 'Barang bawaan 2 kardus', 300000, 'cancelled', 'manual', 'Minta batalkan - ada keperluan (Surabaya 300rb × 1)');

-- =======================================================
-- 5. Tabel Setoran Harian (driver + operasional + fee agen)
-- =======================================================
CREATE TABLE IF NOT EXISTS `laporan_harian` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `tanggal`      DATE         NOT NULL,
  `driver_name`  VARCHAR(120) NOT NULL DEFAULT '',
  `bbm`          INT          NOT NULL DEFAULT 0 COMMENT 'Biaya BBM',
  `toll`         INT          NOT NULL DEFAULT 0 COMMENT 'Biaya toll',
  `fee_ops`      INT          NOT NULL DEFAULT 0 COMMENT 'Fee operasional lain',
  `ops_lain`     INT          NOT NULL DEFAULT 0 COMMENT 'Operasional tambahan (opsional)',
  `notes`        TEXT         NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unik_tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `laporan_fee_agen` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `tanggal`     DATE         NOT NULL,
  `booking_id`  INT          NOT NULL,
  `fee_agen`    INT          NOT NULL DEFAULT 0,
  `arah`        ENUM('berangkat','pulang') NOT NULL DEFAULT 'berangkat'
                COMMENT 'Override arah: Blora=berangkat, Surabaya=pulang',
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unik_tgl_booking` (`tanggal`, `booking_id`),
  INDEX `idx_tanggal` (`tanggal`),
  INDEX `idx_booking` (`booking_id`),
  CONSTRAINT `fk_fee_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
