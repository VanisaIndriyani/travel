-- =======================================================
-- Migrasi: kolom maps_link (pin Google Maps opsional)
-- Jalankan sekali di phpMyAdmin / MySQL jika auto-ALTER gagal.
-- Aman dijalankan ulang: cek dulu apakah kolom sudah ada.
-- =======================================================

-- Cek: SHOW COLUMNS FROM bookings LIKE 'maps_link';
-- Jika kosong, jalankan:

ALTER TABLE `bookings`
  ADD COLUMN `maps_link` VARCHAR(500) NULL DEFAULT NULL
  COMMENT 'Link Google Maps pin lokasi jemput (opsional)'
  AFTER `alamat_jemput`;
