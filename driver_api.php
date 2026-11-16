<?php
// =============================================
// API FORM DRIVER (tanpa login admin)
// POST action=parse | action=save  + k=TOKEN
// =============================================
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/wa_booking_parser.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function driver_json(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    driver_json(['ok' => false, 'message' => 'Method tidak diizinkan.'], 405);
}

$token = (string)($_POST['k'] ?? $_GET['k'] ?? '');
if (!driver_token_ok($token)) {
    driver_json(['ok' => false, 'message' => 'Link tidak valid. Minta link baru ke admin.'], 403);
}

// Rate limit sederhana per IP (session)
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0');
$rlKey = 'driver_rl_' . md5($ip);
$now = time();
$bucket = $_SESSION[$rlKey] ?? ['n' => 0, 't' => $now];
if (($now - (int)$bucket['t']) > 600) {
    $bucket = ['n' => 0, 't' => $now];
}
$bucket['n'] = (int)$bucket['n'] + 1;
$_SESSION[$rlKey] = $bucket;
if ($bucket['n'] > 60) {
    driver_json(['ok' => false, 'message' => 'Terlalu banyak percobaan. Coba lagi 10 menit.'], 429);
}

if (!csrf_verify('post', false)) {
    driver_json(['ok' => false, 'message' => 'Token kadaluarsa. Muat ulang halaman.'], 403);
}

$action = trim((string)($_POST['action'] ?? 'parse'));

// ------------------------------------------------------------------
// PARSE: teks / screenshot → field
// ------------------------------------------------------------------
if ($action === 'parse') {
    $text = trim((string)($_POST['chat_text'] ?? ''));
    if (mb_strlen($text) > 12000) {
        $text = mb_substr($text, 0, 12000);
    }

    $aiAvail = wa_booking_ai_available();
    $image = null;

    if (isset($_FILES['screenshot']) && is_array($_FILES['screenshot'])) {
        $err = (int)($_FILES['screenshot']['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_NO_FILE) {
            if ($err !== UPLOAD_ERR_OK) {
                driver_json([
                    'ok' => false,
                    'message' => 'Gagal upload screenshot. Coba lagi atau tempel teks form.',
                    'ai_available' => $aiAvail,
                ]);
            }
            $tmp = (string)($_FILES['screenshot']['tmp_name'] ?? '');
            if ($tmp === '' || !is_uploaded_file($tmp)) {
                driver_json(['ok' => false, 'message' => 'File screenshot tidak valid.', 'ai_available' => $aiAvail]);
            }
            $size = (int)($_FILES['screenshot']['size'] ?? 0);
            if ($size <= 0 || $size > 8 * 1024 * 1024) {
                driver_json(['ok' => false, 'message' => 'Screenshot maksimal 8 MB.', 'ai_available' => $aiAvail]);
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmp) ?: '';
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!in_array($mime, $allowed, true)) {
                driver_json(['ok' => false, 'message' => 'Format gambar: JPG / PNG / WEBP / GIF.', 'ai_available' => $aiAvail]);
            }
            if (!$aiAvail) {
                driver_json([
                    'ok' => false,
                    'message' => 'Upload screenshot belum aktif. Tempel teks form saja.',
                    'ai_available' => false,
                ]);
            }
            $image = wa_booking_prepare_image_from_file($tmp, $mime);
            if ($image === null) {
                driver_json([
                    'ok' => false,
                    'message' => wa_booking_ai_last_error() ?: 'Gagal baca screenshot. Tempel teks form.',
                    'ai_available' => true,
                ]);
            }
        }
    }

    $result = wa_booking_parse($text, $image);
    $result['csrf'] = csrf_token();
    driver_json($result);
}

// ------------------------------------------------------------------
// SAVE: simpan booking dari form driver
// ------------------------------------------------------------------
if ($action === 'save') {
    ensure_bookings_maps_link_column();
    ensure_bookings_source_driver();

    $nama = trim((string)($_POST['nama'] ?? ''));
    $no_hp = trim((string)($_POST['no_hp'] ?? ''));
    $alamat_jemput = trim((string)($_POST['alamat_jemput'] ?? ''));
    $alamat_tujuan = trim((string)($_POST['alamat_tujuan'] ?? ''));
    $id_rute_post = (int)($_POST['id_rute'] ?? 0);
    $jumlah_kursi = max(1, min(50, (int)($_POST['jumlah_kursi'] ?? 1)));
    $tanggal_berangkat = trim((string)($_POST['tanggal_berangkat'] ?? ''));
    $jam_jemput = trim((string)($_POST['jam_jemput'] ?? ''));
    $barang_bawaan = trim((string)($_POST['barang_bawaan'] ?? ''));
    $total_harga = (int)($_POST['total_harga'] ?? 0);
    $catatan = trim((string)($_POST['catatan_admin'] ?? ''));
    $lokasi_jemput = trim((string)($_POST['lokasi_jemput'] ?? 'Blora'));
    $lokValid = ['Blora', 'Surabaya', 'Sidoarjo', 'Lainnya'];
    if (!in_array($lokasi_jemput, $lokValid, true)) {
        $lokasi_jemput = strlen($lokasi_jemput) > 1 ? substr($lokasi_jemput, 0, 60) : 'Blora';
    }
    $jadwal_jemput = trim((string)($_POST['jadwal_jemput'] ?? ''));
    if (strlen($jadwal_jemput) > 255) $jadwal_jemput = substr($jadwal_jemput, 0, 255);
    $maps_link = sanitize_maps_link($_POST['maps_link'] ?? '');
    $driver_note = trim((string)($_POST['driver_name'] ?? ''));

    if (strlen($jam_jemput) === 5) $jam_jemput .= ':00';

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

    $err = [];
    if (mb_strlen($nama) < 2) $err[] = 'Nama minimal 2 karakter';
    if ($no_hp === '') $err[] = 'No. HP wajib';
    if ($alamat_jemput === '' || $alamat_tujuan === '') $err[] = 'Alamat jemput & tujuan wajib';
    if (!DateTime::createFromFormat('Y-m-d', $tanggal_berangkat)) $err[] = 'Tanggal tidak valid';
    if ($jam_jemput === '') $err[] = 'Jam jemput wajib';
    if ($total_harga <= 0) $err[] = 'Total harga belum valid';
    if ($err) {
        driver_json(['ok' => false, 'message' => implode('. ', $err), 'csrf' => csrf_token()], 422);
    }

    $prefix = '[Driver]';
    if ($driver_note !== '') $prefix .= ' ' . mb_substr($driver_note, 0, 40);
    $catatan_admin = trim($prefix . ($catatan !== '' ? ' · ' . $catatan : ''));
    if (mb_strlen($catatan_admin) > 500) $catatan_admin = mb_substr($catatan_admin, 0, 500);

    $source = ensure_bookings_source_driver() ? 'driver' : 'manual';
    $hasMaps = ensure_bookings_maps_link_column();

    try {
        if ($hasMaps) {
            $sql = "INSERT INTO bookings
            (nama, no_hp, alamat_jemput, maps_link, alamat_tujuan, jumlah_kursi, tanggal_berangkat, jam_jemput,
             barang_bawaan, total_harga, status, source, catatan_admin,
             id_rute, rute, harga_rute_saat_booking, lokasi_jemput, jadwal_jemput)
            VALUES (?,?,?,?,?,?,?,?,?,?, 'confirmed', ?, ?,
                    ?,?,?, ?,?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nama, $no_hp, $alamat_jemput, ($maps_link !== '' ? $maps_link : null), $alamat_tujuan,
                $jumlah_kursi, $tanggal_berangkat, $jam_jemput, $barang_bawaan, $total_harga,
                $source, $catatan_admin,
                $id_rute_final, $nama_rute_final, $harga_rute_final, $lokasi_jemput, $jadwal_jemput,
            ]);
        } else {
            $sql = "INSERT INTO bookings
            (nama, no_hp, alamat_jemput, alamat_tujuan, jumlah_kursi, tanggal_berangkat, jam_jemput,
             barang_bawaan, total_harga, status, source, catatan_admin,
             id_rute, rute, harga_rute_saat_booking, lokasi_jemput, jadwal_jemput)
            VALUES (?,?,?,?,?,?,?,?,?, 'confirmed', ?, ?,
                    ?,?,?, ?,?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nama, $no_hp, $alamat_jemput, $alamat_tujuan,
                $jumlah_kursi, $tanggal_berangkat, $jam_jemput, $barang_bawaan, $total_harga,
                $source, $catatan_admin,
                $id_rute_final, $nama_rute_final, $harga_rute_final, $lokasi_jemput, $jadwal_jemput,
            ]);
        }
        $id = (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        // Fallback source=manual jika ENUM belum ter-update
        if ($source === 'driver' && str_contains($e->getMessage(), 'source')) {
            try {
                $sql = "INSERT INTO bookings
                (nama, no_hp, alamat_jemput, alamat_tujuan, jumlah_kursi, tanggal_berangkat, jam_jemput,
                 barang_bawaan, total_harga, status, source, catatan_admin,
                 id_rute, rute, harga_rute_saat_booking, lokasi_jemput, jadwal_jemput)
                VALUES (?,?,?,?,?,?,?,?,?, 'confirmed', 'manual', ?,
                        ?,?,?, ?,?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $nama, $no_hp, $alamat_jemput, $alamat_tujuan,
                    $jumlah_kursi, $tanggal_berangkat, $jam_jemput, $barang_bawaan, $total_harga,
                    $catatan_admin,
                    $id_rute_final, $nama_rute_final, $harga_rute_final, $lokasi_jemput, $jadwal_jemput,
                ]);
                $id = (int)$pdo->lastInsertId();
            } catch (PDOException $e2) {
                driver_json(['ok' => false, 'message' => 'Gagal simpan: ' . $e2->getMessage(), 'csrf' => csrf_token()], 500);
            }
        } else {
            driver_json(['ok' => false, 'message' => 'Gagal simpan: ' . $e->getMessage(), 'csrf' => csrf_token()], 500);
        }
    }

    driver_json([
        'ok' => true,
        'message' => 'Booking #MT-' . $id . ' tersimpan. Status: Terkonfirmasi.',
        'booking_id' => $id,
        'csrf' => csrf_token(),
    ]);
}

driver_json(['ok' => false, 'message' => 'Aksi tidak dikenal.'], 400);
