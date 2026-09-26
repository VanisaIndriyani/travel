<?php
// =============================================
// ADMIN - DETAIL SETORAN HARIAN (JSON API)
// GET  ?tanggal=YYYY-MM-DD  → data booking + settlement
// POST action=save          → simpan driver/ops/fee agen
// =============================================
require_once __DIR__ . '/includes/auth_check.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function json_out(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!ensure_laporan_settlement_tables()) {
    json_out(['ok' => false, 'error' => 'Tabel settlement belum siap. Jalankan sql/add_laporan_settlement.sql di hosting.'], 500);
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ------------------------------------------------------------------
// GET: ambil detail per tanggal
// ------------------------------------------------------------------
if ($method === 'GET') {
    $tanggal = trim($_GET['tanggal'] ?? '');
    if (!DateTime::createFromFormat('Y-m-d', $tanggal)) {
        json_out(['ok' => false, 'error' => 'Tanggal tidak valid.'], 400);
    }

    $bookings = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE status = 'completed' AND tanggal_berangkat = ? ORDER BY jam_jemput ASC, id ASC");
        $stmt->execute([$tanggal]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        json_out(['ok' => false, 'error' => 'Gagal ambil booking.'], 500);
    }

    $settlement = [
        'tanggal'     => $tanggal,
        'driver_name' => '',
        'bbm'         => 0,
        'toll'        => 0,
        'fee_ops'     => 0,
        'ops_lain'    => 0,
        'notes'       => '',
    ];
    try {
        $stmt = $pdo->prepare("SELECT * FROM laporan_harian WHERE tanggal = ? LIMIT 1");
        $stmt->execute([$tanggal]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $settlement = [
                'tanggal'     => $tanggal,
                'driver_name' => (string)$row['driver_name'],
                'bbm'         => (int)$row['bbm'],
                'toll'        => (int)$row['toll'],
                'fee_ops'     => (int)$row['fee_ops'],
                'ops_lain'    => (int)$row['ops_lain'],
                'notes'       => (string)($row['notes'] ?? ''),
            ];
        }
    } catch (PDOException $e) { /* abaikan */ }

    $feesByBooking = [];
    try {
        $stmt = $pdo->prepare("SELECT booking_id, fee_agen, arah FROM laporan_fee_agen WHERE tanggal = ?");
        $stmt->execute([$tanggal]);
        while ($f = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $feesByBooking[(int)$f['booking_id']] = [
                'fee_agen' => (int)$f['fee_agen'],
                'arah'     => $f['arah'] === 'pulang' ? 'pulang' : 'berangkat',
            ];
        }
    } catch (PDOException $e) { /* abaikan */ }

    $lines = [];
    foreach ($bookings as $b) {
        $id = (int)$b['id'];
        $kursi = max(1, (int)$b['jumlah_kursi']);
        $hargaSatuan = (int)($b['harga_rute_saat_booking'] ?? 0);
        $total = (int)$b['total_harga'];
        if ($hargaSatuan <= 0 && $kursi > 0) {
            $hargaSatuan = (int)round($total / $kursi);
        }
        $saved = $feesByBooking[$id] ?? null;
        $arah = $saved['arah'] ?? infer_arah_booking($b);
        $lines[] = [
            'booking_id'    => $id,
            'nama'          => (string)$b['nama'],
            'no_hp'         => (string)$b['no_hp'],
            'jam'           => substr((string)$b['jam_jemput'], 0, 5),
            'rute'          => (string)($b['rute'] ?? ''),
            'lokasi_jemput' => (string)($b['lokasi_jemput'] ?? ''),
            'jumlah_kursi'  => $kursi,
            'harga_satuan'  => $hargaSatuan,
            'total_harga'   => $total,
            'fee_agen'      => $saved['fee_agen'] ?? 0,
            'arah'          => $arah,
            'arah_default'  => infer_arah_booking($b),
        ];
    }

    json_out([
        'ok'         => true,
        'tanggal'    => $tanggal,
        'tanggal_id' => tgl_id($tanggal),
        'hari'       => nama_hari($tanggal),
        'settlement' => $settlement,
        'bookings'   => $lines,
        'csrf'       => csrf_token(),
    ]);
}

// ------------------------------------------------------------------
// POST: simpan settlement
// ------------------------------------------------------------------
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw ?: '', true);
    if (!is_array($json)) {
        $json = $_POST;
    }

    $csrf = (string)($json['csrf_token'] ?? '');
    if ($csrf === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        json_out(['ok' => false, 'error' => 'CSRF token tidak valid. Refresh halaman lalu coba lagi.'], 403);
    }

    $tanggal = trim((string)($json['tanggal'] ?? ''));
    if (!DateTime::createFromFormat('Y-m-d', $tanggal)) {
        json_out(['ok' => false, 'error' => 'Tanggal tidak valid.'], 400);
    }

    $driver = trim((string)($json['driver_name'] ?? ''));
    if (mb_strlen($driver) > 120) $driver = mb_substr($driver, 0, 120);

    $bbm     = max(0, (int)($json['bbm'] ?? 0));
    $toll    = max(0, (int)($json['toll'] ?? 0));
    $feeOps  = max(0, (int)($json['fee_ops'] ?? 0));
    $opsLain = max(0, (int)($json['ops_lain'] ?? 0));
    $notes   = trim((string)($json['notes'] ?? ''));
    if (mb_strlen($notes) > 2000) $notes = mb_substr($notes, 0, 2000);

    $fees = $json['fees'] ?? [];
    if (!is_array($fees)) $fees = [];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO laporan_harian
            (tanggal, driver_name, bbm, toll, fee_ops, ops_lain, notes, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
              driver_name = VALUES(driver_name),
              bbm = VALUES(bbm),
              toll = VALUES(toll),
              fee_ops = VALUES(fee_ops),
              ops_lain = VALUES(ops_lain),
              notes = VALUES(notes),
              updated_at = NOW()");
        $stmt->execute([$tanggal, $driver, $bbm, $toll, $feeOps, $opsLain, $notes !== '' ? $notes : null]);

        $stmtFee = $pdo->prepare("INSERT INTO laporan_fee_agen
            (tanggal, booking_id, fee_agen, arah, created_at, updated_at)
            VALUES (?,?,?,?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
              fee_agen = VALUES(fee_agen),
              arah = VALUES(arah),
              updated_at = NOW()");

        $stmtCek = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND status = 'completed' AND tanggal_berangkat = ? LIMIT 1");

        foreach ($fees as $f) {
            if (!is_array($f)) continue;
            $bid = (int)($f['booking_id'] ?? 0);
            if ($bid <= 0) continue;
            $stmtCek->execute([$bid, $tanggal]);
            if (!$stmtCek->fetchColumn()) continue;

            $feeAgen = max(0, (int)($f['fee_agen'] ?? 0));
            $arah = (($f['arah'] ?? '') === 'pulang') ? 'pulang' : 'berangkat';
            $stmtFee->execute([$tanggal, $bid, $feeAgen, $arah]);
        }

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        json_out(['ok' => false, 'error' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
    }

    json_out([
        'ok'      => true,
        'message' => 'Setoran ' . tgl_id($tanggal) . ' berhasil disimpan.',
        'csrf'    => csrf_token(),
    ]);
}

json_out(['ok' => false, 'error' => 'Method tidak diizinkan.'], 405);
