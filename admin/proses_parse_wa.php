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

$image = null;
if (!empty($_FILES['screenshot']['tmp_name']) && is_uploaded_file($_FILES['screenshot']['tmp_name'])) {
    $err = (int)($_FILES['screenshot']['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_OK) {
        $size = (int)($_FILES['screenshot']['size'] ?? 0);
        if ($size > 4 * 1024 * 1024) {
            echo json_encode([
                'ok' => false,
                'message' => 'Screenshot maksimal 4 MB.',
                'ai_available' => wa_booking_ai_available(),
                'vision_available' => wa_booking_ai_available(),
            ]);
            exit;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['screenshot']['tmp_name']) ?: '';
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mime, $allowed, true)) {
            echo json_encode([
                'ok' => false,
                'message' => 'Format gambar harus JPG, PNG, WEBP, atau GIF.',
                'ai_available' => wa_booking_ai_available(),
                'vision_available' => wa_booking_ai_available(),
            ]);
            exit;
        }
        if (!wa_booking_ai_available()) {
            echo json_encode([
                'ok' => false,
                'message' => 'Screenshot butuh OPENAI_API_KEY di .env. Tempel teks chat untuk mode tanpa AI.',
                'ai_available' => false,
                'vision_available' => false,
            ]);
            exit;
        }
        $bin = file_get_contents($_FILES['screenshot']['tmp_name']);
        if ($bin !== false && $bin !== '') {
            $image = [
                'mime' => $mime,
                'base64' => base64_encode($bin),
            ];
        }
    }
}

$result = wa_booking_parse($text, $image);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
exit;
