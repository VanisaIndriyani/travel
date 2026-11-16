<?php
/**
 * Seeder demo: 4 penumpang jam 11.00 Blora - Surabaya (tanggal besok)
 * Buka sekali (login admin):
 *   http://localhost/SEPTEMBER/Travel/admin/seed_demo_trip.php
 * Lalu otomatis ke Laporan Harian tanggal tersebut.
 */
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/helpers.php';

$tanggal = date('Y-m-d', strtotime('+1 day'));
$jam = '11:00:00';
$ruteNama = 'Blora - Surabaya';
$lokasi = 'Blora';
$jadwal = 'Jam 11.00 — Kota-kota sekitar Blora (door to door)';
$harga = defined('HARGA_TIKET_DEFAULT') ? (int)HARGA_TIKET_DEFAULT : 150000;

$idRute = null;
try {
    $st = $pdo->prepare("SELECT id, nama_rute, harga FROM rutes WHERE nama_rute LIKE ? OR nama_rute LIKE ? LIMIT 1");
    $st->execute(['%Blora%Surabaya%', '%Blora - Surabaya%']);
    $rte = $st->fetch(PDO::FETCH_ASSOC);
    if ($rte) {
        $idRute = (int)$rte['id'];
        $ruteNama = $rte['nama_rute'];
        $harga = (int)$rte['harga'] > 0 ? (int)$rte['harga'] : $harga;
    }
} catch (Throwable $e) {
    // lanjut tanpa id_rute
}

$penumpang = [
    ['Rendi', '081111110001', 'Jl. Pemuda No.1, Blora', 'Jl. Ahmad Yani, Surabaya'],
    ['Sinta', '081111110002', 'Jl. Merdeka No.2, Blora', 'Jl. Basuki Rahmat, Surabaya'],
    ['Desi',  '081111110003', 'Desa Ngawen RT 01, Blora', 'Rungkut, Surabaya'],
    ['Fatwa', '081111110004', 'Cepu, Blora', 'Gubeng, Surabaya'],
];

$marker = '[SEED DEMO TRIP 11.00]';
$inserted = 0;
$skipped = 0;
$errors = [];

ensure_bookings_maps_link_column();

foreach ($penumpang as [$nama, $hp, $jemput, $tujuan]) {
    try {
        // Hindari dobel jika seeder dijalankan ulang
        $cek = $pdo->prepare("SELECT id FROM bookings WHERE nama = ? AND tanggal_berangkat = ? AND jam_jemput = ? AND catatan_admin LIKE ? LIMIT 1");
        $cek->execute([$nama, $tanggal, $jam, $marker . '%']);
        if ($cek->fetch()) {
            $skipped++;
            continue;
        }

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
            $nama,
            $hp,
            $jemput,
            null,
            $tujuan,
            1,
            $tanggal,
            $jam,
            '',
            $harga,
            'confirmed',
            $marker . ' Demo daftar berangkat',
            $idRute,
            $ruteNama,
            $harga,
            $lokasi,
            $jadwal,
        ]);
        $inserted++;
    } catch (Throwable $e) {
        // Fallback tanpa maps_link
        try {
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
                $nama, $hp, $jemput, $tujuan, 1, $tanggal, $jam,
                '', $harga, 'confirmed', $marker . ' Demo daftar berangkat',
                $idRute, $ruteNama, $harga, $lokasi, $jadwal,
            ]);
            $inserted++;
        } catch (Throwable $e2) {
            $errors[] = $nama . ': ' . $e2->getMessage();
        }
    }
}

$msg = "Seeder jam 11.00 Blora–Surabaya ({$tanggal}): +{$inserted} data"
    . ($skipped ? ", {$skipped} sudah ada" : '')
    . ($errors ? '. Error: ' . implode('; ', $errors) : '');
set_flash($errors ? 'error' : 'success', $msg);

redirect(BASE_URL . '/admin/laporan.php?tab=harian&lhari_tgl=' . urlencode($tanggal));
