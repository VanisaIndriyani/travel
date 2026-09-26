-- =======================================================
-- Migrasi: Tabel setoran harian (driver + operasional + fee agen)
-- Jalankan sekali di phpMyAdmin / MySQL hosting.
-- Aman dijalankan ulang (CREATE IF NOT EXISTS).
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
