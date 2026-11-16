<?php
// =============================================
// ADMIN - DETAIL SETORAN per 1 PP (JSON API)
// Booking berangkat/pulang DIPILIH MANUAL oleh admin.
// GET  ?tanggal=YYYY-MM-DD&list=1
// GET  ?tanggal=YYYY-MM-DD&pp=pp_08_15
// POST action=save + pp_kode + fees[]
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
$ppDefs = mustika_pp_definitions();

/** Tanggal yang boleh dipakai untuk pool booking 1 PP. */
function lap_pp_dates(string $tanggal, array $pp): array
{
    $dates = [$tanggal];
    if (!empty($pp['pulang_next_day'])) {
        $dates[] = date('Y-m-d', strtotime($tanggal . ' +1 day'));
    }
    return $dates;
}

/**
 * Pool booking yang bisa dipilih admin (confirmed + completed).
 * Tidak di-filter jam — admin yang tentukan berangkat/pulang.
 */
function lap_fetch_pool(PDO $pdo, string $tanggal, array $pp): array
{
    $dates = lap_pp_dates($tanggal, $pp);
    $placeholders = implode(',', array_fill(0, count($dates), '?'));
    $stmt = $pdo->prepare("SELECT * FROM bookings
        WHERE status IN ('confirmed','completed')
          AND tanggal_berangkat IN ($placeholders)
        ORDER BY tanggal_berangkat ASC, jam_jemput ASC, id ASC");
    $stmt->execute($dates);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function lap_map_booking_line(array $b, ?array $saved = null): array
{
    $id = (int)$b['id'];
    $kursi = max(1, (int)$b['jumlah_kursi']);
    $hargaSatuan = (int)($b['harga_rute_saat_booking'] ?? 0);
    $total = (int)$b['total_harga'];
    if ($hargaSatuan <= 0 && $kursi > 0) {
        $hargaSatuan = (int)round($total / $kursi);
    }
    $arah = null;
    $fee = 0;
    $selected = false;
    if ($saved) {
        $selected = true;
        $arah = ($saved['arah'] ?? '') === 'pulang' ? 'pulang' : 'berangkat';
        $fee = (int)($saved['fee_agen'] ?? 0);
    }
    return [
        'booking_id'    => $id,
        'nama'          => (string)$b['nama'],
        'no_hp'         => (string)$b['no_hp'],
        'jam'           => substr((string)$b['jam_jemput'], 0, 5),
        'tanggal'       => (string)$b['tanggal_berangkat'],
        'tanggal_id'    => tgl_id((string)$b['tanggal_berangkat']),
        'rute'          => (string)($b['rute'] ?? ''),
        'lokasi_jemput' => (string)($b['lokasi_jemput'] ?? ''),
        'status'        => (string)($b['status'] ?? ''),
        'jumlah_kursi'  => $kursi,
        'harga_satuan'  => $hargaSatuan,
        'total_harga'   => $total,
        'fee_agen'      => $fee,
        'arah'          => $arah, // null = belum dipilih
        'selected'      => $selected,
    ];
}

function lap_fees_map(PDO $pdo, string $tanggal, string $ppKode): array
{
    $feesByBooking = [];
    try {
        $stmt = $pdo->prepare("SELECT booking_id, fee_agen, arah FROM laporan_fee_agen WHERE tanggal = ? AND pp_kode = ?");
        $stmt->execute([$tanggal, $ppKode]);
        while ($f = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $feesByBooking[(int)$f['booking_id']] = [
                'fee_agen' => (int)$f['fee_agen'],
                'arah'     => $f['arah'] === 'pulang' ? 'pulang' : 'berangkat',
            ];
        }
    } catch (PDOException $e) { /* abaikan */ }
    return $feesByBooking;
}

function lap_used_in_other_pp(PDO $pdo, string $tanggal, string $ppKode): array
{
    $used = [];
    try {
        $stmt = $pdo->prepare("SELECT booking_id, pp_kode, arah FROM laporan_fee_agen WHERE tanggal = ? AND pp_kode <> ?");
        $stmt->execute([$tanggal, $ppKode]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $used[(int)$r['booking_id']] = [
                'pp_kode' => (string)$r['pp_kode'],
                'arah'    => $r['arah'] === 'pulang' ? 'pulang' : 'berangkat',
            ];
        }
    } catch (PDOException $e) { /* abaikan */ }
    return $used;
}

// ------------------------------------------------------------------
// GET
// ------------------------------------------------------------------
if ($method === 'GET') {
    $tanggal = trim($_GET['tanggal'] ?? '');
    if (!DateTime::createFromFormat('Y-m-d', $tanggal)) {
        json_out(['ok' => false, 'error' => 'Tanggal tidak valid.'], 400);
    }

    // List semua PP untuk tanggal
    if (!empty($_GET['list'])) {
        $list = [];
        foreach ($ppDefs as $kode => $pp) {
            $fees = lap_fees_map($pdo, $tanggal, $kode);
            $nBrg = 0;
            $nPlg = 0;
            $kursi = 0;
            $omzet = 0;
            $driver = '';
            try {
                $st = $pdo->prepare("SELECT driver_name FROM laporan_harian WHERE tanggal = ? AND pp_kode = ? LIMIT 1");
                $st->execute([$tanggal, $kode]);
                $driver = (string)($st->fetchColumn() ?: '');
            } catch (PDOException $e) { /* abaikan */ }

            if ($fees) {
                $ids = array_keys($fees);
                try {
                    $ph = implode(',', array_fill(0, count($ids), '?'));
                    $st = $pdo->prepare("SELECT id, jumlah_kursi, total_harga FROM bookings WHERE id IN ($ph)");
                    $st->execute($ids);
                    $byId = [];
                    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                        $byId[(int)$row['id']] = $row;
                    }
                    foreach ($fees as $bid => $f) {
                        if ($f['arah'] === 'pulang') $nPlg++; else $nBrg++;
                        if (!empty($byId[$bid])) {
                            $kursi += max(1, (int)$byId[$bid]['jumlah_kursi']);
                            $omzet += (int)$byId[$bid]['total_harga'];
                        }
                    }
                } catch (PDOException $e) { /* abaikan */ }
            }

            $list[] = [
                'pp_kode'   => $kode,
                'label'     => $pp['label'],
                'ringkas'   => $pp['ringkas'],
                'driver'    => $driver,
                'berangkat' => $nBrg,
                'pulang'    => $nPlg,
                'kursi'     => $kursi,
                'omzet'     => $omzet,
                'tersimpan' => ($nBrg + $nPlg) > 0 || $driver !== '',
            ];
        }
        json_out([
            'ok'         => true,
            'tanggal'    => $tanggal,
            'tanggal_id' => tgl_id($tanggal),
            'hari'       => nama_hari($tanggal),
            'pp_list'    => $list,
            'csrf'       => csrf_token(),
        ]);
    }

    $ppKode = trim((string)($_GET['pp'] ?? $_GET['pp_kode'] ?? 'pp_11_20'));
    $pp = mustika_pp_get($ppKode);
    if (!$pp) {
        json_out(['ok' => false, 'error' => 'Kode PP tidak dikenal.'], 400);
    }

    try {
        $pool = lap_fetch_pool($pdo, $tanggal, $pp);
    } catch (PDOException $e) {
        json_out(['ok' => false, 'error' => 'Gagal ambil booking.'], 500);
    }

    $settlement = [
        'tanggal'     => $tanggal,
        'pp_kode'     => $ppKode,
        'driver_name' => '',
        'bbm'         => 0,
        'toll'        => 0,
        'fee_ops'     => 0,
        'ops_lain'    => 0,
        'notes'       => '',
    ];
    try {
        $stmt = $pdo->prepare("SELECT * FROM laporan_harian WHERE tanggal = ? AND pp_kode = ? LIMIT 1");
        $stmt->execute([$tanggal, $ppKode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $settlement = [
                'tanggal'     => $tanggal,
                'pp_kode'     => $ppKode,
                'driver_name' => (string)$row['driver_name'],
                'bbm'         => (int)$row['bbm'],
                'toll'        => (int)$row['toll'],
                'fee_ops'     => (int)$row['fee_ops'],
                'ops_lain'    => (int)$row['ops_lain'],
                'notes'       => (string)($row['notes'] ?? ''),
            ];
        }
    } catch (PDOException $e) { /* abaikan */ }

    $feesByBooking = lap_fees_map($pdo, $tanggal, $ppKode);
    $usedOther = lap_used_in_other_pp($pdo, $tanggal, $ppKode);

    $available = [];
    foreach ($pool as $b) {
        $id = (int)$b['id'];
        $line = lap_map_booking_line($b, $feesByBooking[$id] ?? null);
        if (isset($usedOther[$id]) && !$line['selected']) {
            $otherPp = mustika_pp_get($usedOther[$id]['pp_kode']);
            $line['used_elsewhere'] = true;
            $line['used_label'] = $otherPp ? $otherPp['label'] : $usedOther[$id]['pp_kode'];
        } else {
            $line['used_elsewhere'] = false;
            $line['used_label'] = '';
        }
        $available[] = $line;
    }

    // Booking tersimpan tapi sudah tidak di pool (status berubah) — tetap tampilkan
    $poolIds = array_column($available, 'booking_id');
    foreach ($feesByBooking as $bid => $saved) {
        if (in_array($bid, $poolIds, true)) continue;
        try {
            $st = $pdo->prepare("SELECT * FROM bookings WHERE id = ? LIMIT 1");
            $st->execute([$bid]);
            $b = $st->fetch(PDO::FETCH_ASSOC);
            if ($b) {
                $line = lap_map_booking_line($b, $saved);
                $line['used_elsewhere'] = false;
                $line['used_label'] = '';
                $available[] = $line;
            }
        } catch (PDOException $e) { /* abaikan */ }
    }

    json_out([
        'ok'         => true,
        'tanggal'    => $tanggal,
        'tanggal_id' => tgl_id($tanggal),
        'hari'       => nama_hari($tanggal),
        'pp_kode'    => $ppKode,
        'pp_label'   => $pp['label'],
        'pp_ringkas' => $pp['ringkas'],
        'settlement' => $settlement,
        'available'  => $available,
        'csrf'       => csrf_token(),
    ]);
}

// ------------------------------------------------------------------
// POST: simpan settlement 1 PP (hanya booking yang dipilih manual)
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

    $ppKode = trim((string)($json['pp_kode'] ?? $json['pp'] ?? ''));
    $pp = mustika_pp_get($ppKode);
    if (!$pp) {
        json_out(['ok' => false, 'error' => 'Pilih PP dulu (pagi / siang / malam).'], 400);
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

    $selected = [];
    try {
        $pool = lap_fetch_pool($pdo, $tanggal, $pp);
        $allowedIds = [];
        foreach ($pool as $b) {
            $allowedIds[(int)$b['id']] = true;
        }
        // Izinkan juga booking yang sudah tersimpan sebelumnya (status berubah)
        foreach (lap_fees_map($pdo, $tanggal, $ppKode) as $bid => $_) {
            $allowedIds[$bid] = true;
        }

        foreach ($fees as $f) {
            if (!is_array($f)) continue;
            $bid = (int)($f['booking_id'] ?? 0);
            if ($bid <= 0 || empty($allowedIds[$bid])) continue;
            $arah = (($f['arah'] ?? '') === 'pulang') ? 'pulang' : 'berangkat';
            $selected[$bid] = [
                'fee_agen' => max(0, (int)($f['fee_agen'] ?? 0)),
                'arah'     => $arah,
            ];
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO laporan_harian
            (tanggal, pp_kode, driver_name, bbm, toll, fee_ops, ops_lain, notes, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
              driver_name = VALUES(driver_name),
              bbm = VALUES(bbm),
              toll = VALUES(toll),
              fee_ops = VALUES(fee_ops),
              ops_lain = VALUES(ops_lain),
              notes = VALUES(notes),
              updated_at = NOW()");
        $stmt->execute([$tanggal, $ppKode, $driver, $bbm, $toll, $feeOps, $opsLain, $notes !== '' ? $notes : null]);

        // Ganti penuh: hapus pilihan lama, tulis ulang yang dipilih manual
        $del = $pdo->prepare("DELETE FROM laporan_fee_agen WHERE tanggal = ? AND pp_kode = ?");
        $del->execute([$tanggal, $ppKode]);

        if ($selected) {
            $stmtFee = $pdo->prepare("INSERT INTO laporan_fee_agen
                (tanggal, pp_kode, booking_id, fee_agen, arah, created_at, updated_at)
                VALUES (?,?,?,?,?, NOW(), NOW())");
            foreach ($selected as $bid => $f) {
                $stmtFee->execute([$tanggal, $ppKode, $bid, $f['fee_agen'], $f['arah']]);
            }
        }

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        json_out(['ok' => false, 'error' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
    }

    $n = count($selected ?? []);
    json_out([
        'ok'      => true,
        'message' => 'Setoran ' . $pp['label'] . ' · ' . tgl_id($tanggal) . ' tersimpan (' . $n . ' booking dipilih).',
        'csrf'    => csrf_token(),
    ]);
}

json_out(['ok' => false, 'error' => 'Method tidak diizinkan.'], 405);
