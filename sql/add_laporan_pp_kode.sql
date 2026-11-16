-- =======================================================
-- Migrasi: Setoran per 1 PP (pasangan berangkat+pulang)
-- Jalankan di phpMyAdmin hosting jika kolom pp_kode belum ada.
-- =======================================================

-- laporan_harian: satu baris per (tanggal + PP)
ALTER TABLE `laporan_harian`
  ADD COLUMN IF NOT EXISTS `pp_kode` VARCHAR(32) NOT NULL DEFAULT 'pp_11_20' AFTER `tanggal`;

-- Hapus unique lama (abaikan error jika sudah tidak ada)
-- ALTER TABLE `laporan_harian` DROP INDEX `unik_tanggal`;

-- Pastikan unique gabungan tanggal+pp
-- ALTER TABLE `laporan_harian` ADD UNIQUE KEY `unik_tgl_pp` (`tanggal`, `pp_kode`);

ALTER TABLE `laporan_fee_agen`
  ADD COLUMN IF NOT EXISTS `pp_kode` VARCHAR(32) NOT NULL DEFAULT 'pp_11_20' AFTER `tanggal`;

-- ALTER TABLE `laporan_fee_agen` DROP INDEX `unik_tgl_booking`;
-- ALTER TABLE `laporan_fee_agen` ADD UNIQUE KEY `unik_tgl_pp_booking` (`tanggal`, `pp_kode`, `booking_id`);

-- Catatan PP:
-- pp_08_15 = 08.00 berangkat + 15.00/18.00 pulang
-- pp_11_20 = 11.00 berangkat + 20.00 pulang
-- pp_20_10 = 20.00 berangkat + 10.00 pulang (besok)
--
-- App juga auto-ALTER saat ensure_laporan_settlement_tables() dipanggil.
