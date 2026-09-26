<?php
// =============================================
// PROSES TAMBAH BOOKING MANUAL
// =============================================
require_once __DIR__ . '/includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(BASE_URL . '/admin/bookings.php');
if (!csrf_verify()) {
    set_flash('error', 'Token kadaluarsa.');
    redirect(BASE_URL . '/admin/bookings.php');
}

// Ambil + sanitize
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
$barang_bawaan = trim($_POST['barang_bawaan'] ?? '');
$total_harga = (int)($_POST['total_harga'] ?? 0);
$catatan_admin = trim($_POST['catatan_admin'] ?? '');
// === LOKASI + JADWAL (SEBELUMNYA HILANG! BUG KRITIS!) ===
$lokasi_jemput = trim($_POST['lokasi_jemput'] ?? 'Blora');
$lokValid = ['Blora','Surabaya','Sidoarjo','Lainnya'];
if (!in_array($lokasi_jemput, $lokValid)) {
    $lokasi_jemput = strlen($lokasi_jemput) > 1 ? substr($lokasi_jemput,0,60) : 'Blora';
}
$jadwal_jemput = trim($_POST['jadwal_jemput'] ?? '');
if (strlen($jadwal_jemput) > 255) $jadwal_jemput = substr($jadwal_jemput,0,255);
$maps_link = sanitize_maps_link($_POST['maps_link'] ?? '');
ensure_bookings_maps_link_column();

if (strlen($jam_jemput) === 5) $jam_jemput .= ':00';

// === RESOLVE DATA RUTE ===
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
// Fallback: jika total_harga tidak diisi manual, hitung otomatis berdasarkan harga rute * kursi
if ($total_harga <= 0) {
    $hargaSatuan = ($harga_rute_final > 0) ? $harga_rute_final : HARGA_TIKET_DEFAULT;
    $total_harga = $jumlah_kursi * $hargaSatuan;
}

// Validasi minimum
$err = [];
if (strlen($nama) < 2) $err[] = 'Nama minimal 2 karakter';
if (empty($no_hp)) $err[] = 'No.HP wajib';
if (empty($alamat_jemput) || empty($alamat_tujuan)) $err[] = 'Alamat jemput & tujuan wajib';
if ($jumlah_kursi < 1) $err[] = 'Jumlah kursi minimal 1';
if (!DateTime::createFromFormat('Y-m-d', $tanggal_berangkat)) $err[] = 'Tanggal tidak valid';
if ($total_harga <= 0) $err[] = 'Total harga wajib diisi';

if (!empty($err)) {
    set_flash('error', implode('<br>', $err));
    redirect(BASE_URL . '/admin/bookings.php');
}

try {
    $hasMapsCol = ensure_bookings_maps_link_column();
    if ($hasMapsCol) {
        $sql = "INSERT INTO bookings
        (nama, no_hp, alamat_jemput, maps_link, alamat_tujuan, jumlah_kursi, tanggal_berangkat, jam_jemput,
         barang_bawaan, total_harga, status, source, catatan_admin,
         id_rute, rute, harga_rute_saat_booking,
         lokasi_jemput, jadwal_jemput)
        VALUES (?,?,?,?,?,?,?,?,?,?,?, 'manual', ?,
                ?,?,?,
                ?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nama, $no_hp, $alamat_jemput, ($maps_link !== '' ? $maps_link : null), $alamat_tujuan, $jumlah_kursi,
            $tanggal_berangkat, $jam_jemput, $barang_bawaan, $total_harga, $status, $catatan_admin,
            $id_rute_final, $nama_rute_final, $harga_rute_final,
            $lokasi_jemput, $jadwal_jemput
        ]);
    } else {
        $sql = "INSERT INTO bookings
        (nama, no_hp, alamat_jemput, alamat_tujuan, jumlah_kursi, tanggal_berangkat, jam_jemput,
         barang_bawaan, total_harga, status, source, catatan_admin,
         id_rute, rute, harga_rute_saat_booking,
         lokasi_jemput, jadwal_jemput)
        VALUES (?,?,?,?,?,?,?,?,?,?, 'manual', ?,
                ?,?,?,
                ?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nama, $no_hp, $alamat_jemput, $alamat_tujuan, $jumlah_kursi,
            $tanggal_berangkat, $jam_jemput, $barang_bawaan, $total_harga, $status, $catatan_admin,
            $id_rute_final, $nama_rute_final, $harga_rute_final,
            $lokasi_jemput, $jadwal_jemput
        ]);
    }
    $id = $pdo->lastInsertId();
    set_flash('success', "✅ Booking manual #MT-{$id} berhasil ditambahkan!" . ($nama_rute_final ? " (Rute: <b>".e($nama_rute_final)."</b>" : '') . ($lokasi_jemput ? " 📍<b>".e($lokasi_jemput)."</b>" : ''));
} catch (PDOException $e) {
    set_flash('error', 'DB Error: ' . $e->getMessage());
}
redirect(BASE_URL . '/admin/bookings.php');
