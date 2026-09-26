<?php
// =============================================
// ADMIN AJAX: parse chat WA → JSON field booking
// Key API tidak pernah dikirim ke frontend.
// CSRF: destroy=false agar token bisa dipakai Simpan form.
// =============================================
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/wa_booking_parser.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

if (!csrf_verify('post', false)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Token kadaluarsa. Muat ulang halaman.']);
    exit;
}

$text = trim((string)($_POST['chat_text'] ?? ''));
if (mb_strlen($text) > 12000) {
    $text = mb_substr($text, 0, 12000);
}

$aiAvail = wa_booking_ai_available();
$image = null;

if (isset($_FILES['screenshot']) && is_array($_FILES['screenshot'])) {
    $err = (int)($_FILES['screenshot']['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($err !== UPLOAD_ERR_NO_FILE) {
        $uploadMsgs = [
            UPLOAD_ERR_INI_SIZE => 'Screenshot melebihi upload_max_filesize PHP di server. Naikkan limit atau pakai gambar lebih kecil.',
            UPLOAD_ERR_FORM_SIZE => 'Screenshot melebihi batas form.',
            UPLOAD_ERR_PARTIAL => 'Upload screenshot terputus (partial). Coba lagi.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server tanpa folder tmp upload (UPLOAD_ERR_NO_TMP_DIR).',
            UPLOAD_ERR_CANT_WRITE => 'Server gagal menulis file upload ke disk.',
            UPLOAD_ERR_EXTENSION => 'Upload diblokir ekstensi PHP.',
        ];

        if ($err !== UPLOAD_ERR_OK) {
            echo json_encode([
                'ok' => false,
                'message' => $uploadMsgs[$err] ?? ('Gagal upload screenshot (kode ' . $err . ').'),
                'ai_available' => $aiAvail,
                'vision_available' => $aiAvail,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $tmp = (string)($_FILES['screenshot']['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            echo json_encode([
                'ok' => false,
                'message' => 'File screenshot tidak valid di server (tmp hilang). Cek permission / open_basedir.',
                'ai_available' => $aiAvail,
                'vision_available' => $aiAvail,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $size = (int)($_FILES['screenshot']['size'] ?? 0);
        // Batas input 8 MB; akan dikompres sebelum kirim ke API
        if ($size <= 0 || $size > 8 * 1024 * 1024) {
            echo json_encode([
                'ok' => false,
                'message' => 'Screenshot maksimal 8 MB (akan dikompres otomatis). Pakai PNG/JPG lebih kecil jika perlu.',
                'ai_available' => $aiAvail,
                'vision_available' => $aiAvail,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: '';
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mime, $allowed, true)) {
            echo json_encode([
                'ok' => false,
                'message' => 'Format gambar harus JPG, PNG, WEBP, atau GIF (terdeteksi: ' . ($mime ?: 'unknown') . ').',
                'ai_available' => $aiAvail,
                'vision_available' => $aiAvail,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!$aiAvail) {
            echo json_encode([
                'ok' => false,
                'message' => 'Upload screenshot tidak tersedia (OPENAI_API_KEY kosong di .env root). Tempel teks chat saja.',
                'ai_available' => false,
                'vision_available' => false,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        wa_booking_ai_last_error('');
        $image = wa_booking_prepare_image_from_file($tmp, $mime);
        if ($image === null) {
            $detail = wa_booking_ai_last_error();
            echo json_encode([
                'ok' => false,
                'message' => $detail !== ''
                    ? $detail
                    : 'Gagal memproses file screenshot. Tempel teks chat sebagai alternatif.',
                'ai_error' => $detail,
                'ai_available' => true,
                'vision_available' => true,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

$result = wa_booking_parse($text, $image);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
exit;
