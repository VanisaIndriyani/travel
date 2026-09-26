<?php
// =============================================
// PROSES EDIT BOOKING
// =============================================
require_once __DIR__ . '/includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(BASE_URL . '/admin/bookings.php');
if (!csrf_verify()) { set_flash('error','Token kadaluarsa'); redirect(BASE_URL . '/admin/bookings.php'); }

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { set_flash('error','ID booking tidak valid'); redirect(BASE_URL . '/admin/bookings.php'); }

$nama = trim($_POST['nama'] ?? '');
$no_hp = trim($_POST['no_hp'] ?? '');
$alamat_jemput = trim($_POST['alamat_jemput'] ?? '');
$alamat_tujuan = trim($_POST['alamat_tujuan'] ?? '');
$id_rute_post = (int)($_POST['id_rute'] ?? 0);
$jumlah_kursi = (int)($_POST['jumlah_kursi'] ?? 1);
$tanggal_berangkat = trim($_POST['tanggal_berangkat'] ?? '');
$jam_jemput = trim($_POST['jam_jemput'] ?? '');
$status = $_POST['status'] ?? 'pending';
if (!in_array($status, ['pending','confirmed','completed','cancelled'])) $status = 'pending';
$source = $_POST['source'] ?? 'manual';
if (!in_array($source, ['online','manual'])) $source = 'manual';
$barang_bawaan = trim($_POST['barang_bawaan'] ?? '');
$total_harga = (int)($_POST['total_harga'] ?? 0);
$catatan_admin = trim($_POST['catatan_admin'] ?? '');
// === LOKASI + JADWAL (BUG KRITIS DIPERBAIKI SEKARANG!) ===
$lokasi_jemput = trim($_POST['lokasi_jemput'] ?? 'Blora');
$lokValid = ['Blora','Surabaya','Sidoarjo','Lainnya'];
if (!in_array($lokasi_jemput, $lokValid)) {
    $lokasi_jemput = strlen($lokasi_jemput) && strlen($lokasi_jemput) > 1 ? substr($lokasi_jemput,0,60) : 'Blora';
}
$jadwal_jemput = trim($_POST['jadwal_jemput'] ?? '');
if (strlen($jadwal_jemput) > 255) $jadwal_jemput = substr($jadwal_jemput,0,255);
$maps_link = sanitize_maps_link($_POST['maps_link'] ?? '');

if (strlen($jam_jemput) === 5) $jam_jemput .= ':00';

// === RESOLVE DATA RUTE (jika admin pilih rute baru) ===
$id_rute_final = null;
$nama_rute_final = '';
$harga_rute_final = 0;
if ($id_rute_post > 0) {
    $rte = get_rute_by_id($id_rute_post);
    if ($rte) {
        $id_rute_final = (int)$rte['id'];
        $nama_rute_final = $rte['nama_rute'];
        $harga_rute_final = (int)$rte['harga'];
    }
}

if ($total_harga <= 0) {
    $hargaSatuan = ($harga_rute_final > 0) ? $harga_rute_final : HARGA_TIKET_DEFAULT;
    $total_harga = $jumlah_kursi * $hargaSatuan;
}

try {
    $hasMapsCol = ensure_bookings_maps_link_column();
    if ($hasMapsCol) {
        $sql = "UPDATE bookings SET
            nama = ?, no_hp = ?, alamat_jemput = ?, maps_link = ?, alamat_tujuan = ?,
            jumlah_kursi = ?, tanggal_berangkat = ?, jam_jemput = ?,
            barang_bawaan = ?, total_harga = ?, status = ?, source = ?,
            catatan_admin = ?, updated_at = NOW(),
            id_rute = ?, rute = ?, harga_rute_saat_booking = ?,
            lokasi_jemput = ?, jadwal_jemput = ?
            WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            $nama, $no_hp, $alamat_jemput, ($maps_link !== '' ? $maps_link : null), $alamat_tujuan,
            $jumlah_kursi, $tanggal_berangkat, $jam_jemput,
            $barang_bawaan, $total_harga, $status, $source,
            $catatan_admin,
            $id_rute_final, $nama_rute_final, $harga_rute_final,
            $lokasi_jemput, $jadwal_jemput,
            $id
        ]);
    } else {
        $sql = "UPDATE bookings SET
            nama = ?, no_hp = ?, alamat_jemput = ?, alamat_tujuan = ?,
            jumlah_kursi = ?, tanggal_berangkat = ?, jam_jemput = ?,
            barang_bawaan = ?, total_harga = ?, status = ?, source = ?,
            catatan_admin = ?, updated_at = NOW(),
            id_rute = ?, rute = ?, harga_rute_saat_booking = ?,
            lokasi_jemput = ?, jadwal_jemput = ?
            WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            $nama, $no_hp, $alamat_jemput, $alamat_tujuan,
            $jumlah_kursi, $tanggal_berangkat, $jam_jemput,
            $barang_bawaan, $total_harga, $status, $source,
            $catatan_admin,
            $id_rute_final, $nama_rute_final, $harga_rute_final,
            $lokasi_jemput, $jadwal_jemput,
            $id
        ]);
    }
    if ($ok) set_flash('success', "✅ Booking #MT-{$id} berhasil diperbarui!" . ($lokasi_jemput ? " 📍".e($lokasi_jemput) : ''));
    else set_flash('error', 'Gagal update booking.');
} catch (PDOException $e) {
    set_flash('error', 'DB Error: ' . $e->getMessage());
}
redirect(BASE_URL . '/admin/bookings.php');
