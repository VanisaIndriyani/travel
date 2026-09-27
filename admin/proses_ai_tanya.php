<?php
// =============================================
// Endpoint: tanya AI asisten dashboard (JSON)
// =============================================
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/admin_ai_assistant.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'answer' => 'Method tidak diizinkan.']);
    exit;
}

$q = '';
$ct = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
if (stripos($ct, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $j = json_decode($raw ?: '', true);
    if (is_array($j) && isset($j['question'])) {
        $q = (string)$j['question'];
    }
} else {
    $q = (string)($_POST['question'] ?? '');
}

$result = admin_ai_jawab($pdo, $q);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
