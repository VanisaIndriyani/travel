<?php
// =============================================
// AI Asisten Dashboard — hitung / cari data booking
// Tanpa API key: parser lokal. Dengan OPENAI_API_KEY: intent lebih fleksibel.
// =============================================

require_once __DIR__ . '/wa_booking_parser.php';

/**
 * Jawab pertanyaan admin tentang data booking / pendapatan.
 *
 * @return array{ok:bool,answer:string,method:string,data?:array,error?:string}
 */
function admin_ai_jawab(PDO $pdo, string $question): array
{
    $question = trim(preg_replace('/\s+/u', ' ', $question) ?? '');
    if ($question === '') {
        return [
            'ok' => false,
            'answer' => 'Tulis pertanyaan dulu, contoh: “Pendapatan Blora Surabaya bulan ini berapa?”',
            'method' => 'none',
        ];
    }
    if (mb_strlen($question) > 500) {
        return [
            'ok' => false,
            'answer' => 'Pertanyaan terlalu panjang (maks 500 karakter).',
            'method' => 'none',
        ];
    }

    $intent = admin_ai_parse_intent_rules($question);

    if (wa_booking_ai_available()) {
        $aiIntent = admin_ai_parse_intent_openai($question);
        if (is_array($aiIntent)) {
            $intent = array_merge($intent, array_filter($aiIntent, static fn($v) => $v !== null && $v !== ''));
            $method = 'ai+rules';
        } else {
            $method = 'rules';
        }
    } else {
        $method = 'rules';
    }

    $result = admin_ai_run_query($pdo, $intent);
    $answer = admin_ai_format_answer($intent, $result);

    return [
        'ok' => true,
        'answer' => $answer,
        'method' => $method,
        'data' => $result,
        'intent' => $intent,
    ];
}

/**
 * Intent default dari teks Indonesia.
 *
 * @return array{metric:string,period:string,date_from:?string,date_to:?string,rute:?string,status:string}
 */
function admin_ai_parse_intent_rules(string $q): array
{
    $low = mb_strtolower($q);
    $today = date('Y-m-d');

    $metric = 'ringkas';
    if (preg_match('/pendapatan|omzet|pemasukan|total\s*uang|berapa\s*duit/u', $low)) {
        $metric = 'pendapatan';
    } elseif (preg_match('/penumpang|kursi|pax|orang/u', $low)) {
        $metric = 'penumpang';
    } elseif (preg_match('/berapa\s*booking|jumlah\s*booking|total\s*booking|banyak\s*booking/u', $low)) {
        $metric = 'booking';
    } elseif (preg_match('/pending|menunggu/u', $low)) {
        $metric = 'pending';
    }

    $period = 'bulan_ini';
    $dateFrom = date('Y-m-01');
    $dateTo = $today;

    if (preg_match('/hari\s*ini|today/u', $low)) {
        $period = 'hari_ini';
        $dateFrom = $today;
        $dateTo = $today;
    } elseif (preg_match('/kemarin/u', $low)) {
        $period = 'kemarin';
        $dateFrom = date('Y-m-d', strtotime('-1 day'));
        $dateTo = $dateFrom;
    } elseif (preg_match('/minggu\s*ini|pekan\s*ini/u', $low)) {
        $period = 'minggu_ini';
        $dateFrom = date('Y-m-d', strtotime('monday this week'));
        $dateTo = $today;
    } elseif (preg_match('/bulan\s*lalu|bulan\s*kemarin/u', $low)) {
        $period = 'bulan_lalu';
        $dateFrom = date('Y-m-01', strtotime('first day of last month'));
        $dateTo = date('Y-m-t', strtotime('last day of last month'));
    } elseif (preg_match('/tahun\s*ini/u', $low)) {
        $period = 'tahun_ini';
        $dateFrom = date('Y-01-01');
        $dateTo = $today;
    } elseif (preg_match('/bulan\s*ini/u', $low)) {
        $period = 'bulan_ini';
        $dateFrom = date('Y-m-01');
        $dateTo = $today;
    }

    // Tanggal eksplisit: 01/09/2026 - 30/09/2026 atau 2026-09-01
    if (preg_match('/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})\s*(?:s\.?d\.?|sampai|–|-|hingga)\s*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/u', $low, $m)) {
        $a = admin_ai_parse_date($m[1]);
        $b = admin_ai_parse_date($m[2]);
        if ($a && $b) {
            $period = 'custom';
            $dateFrom = $a;
            $dateTo = $b;
        }
    }

    $rute = null;
    // Blora - Surabaya / Blora Surabaya / blora–surabaya
    if (preg_match('/(blora)\s*[-–—]?\s*(surabaya|sidoarjo|denpasar|semarang|malang|solo|yogyakarta|jogja)/u', $low, $m)) {
        $rute = 'Blora - ' . admin_ai_title_city($m[2]);
    } elseif (preg_match('/(surabaya|sidoarjo|denpasar|semarang|malang|solo|yogyakarta|jogja)\s*[-–—]?\s*(blora)/u', $low, $m)) {
        $rute = admin_ai_title_city($m[1]) . ' - Blora';
    } elseif (preg_match('/rute\s+([a-zA-Z\s]+?\s*[-–—]\s*[a-zA-Z\s]+)/u', $q, $m)) {
        $rute = trim(preg_replace('/\s+/', ' ', str_replace(['–', '—'], '-', $m[1])));
    }

    $status = 'completed';
    if (preg_match('/semua\s*status|all\s*status/u', $low)) {
        $status = 'all';
    } elseif (preg_match('/pending|menunggu/u', $low)) {
        $status = 'pending';
        if ($metric === 'ringkas') {
            $metric = 'pending';
        }
        // Pending biasanya “berapa sekarang”, bukan filter tanggal ketat
        if (!preg_match('/hari\s*ini|kemarin|minggu|bulan|tahun|s\.?d\.?|sampai/u', $low)) {
            $period = 'semua';
            $dateFrom = '2000-01-01';
            $dateTo = $today;
        }
    } elseif (preg_match('/terkonfirmasi|confirmed/u', $low)) {
        $status = 'confirmed';
    }

    return [
        'metric' => $metric,
        'period' => $period,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'rute' => $rute,
        'status' => $status,
    ];
}

function admin_ai_title_city(string $c): string
{
    $c = mb_strtolower(trim($c));
    $map = [
        'blora' => 'Blora',
        'surabaya' => 'Surabaya',
        'sidoarjo' => 'Sidoarjo',
        'denpasar' => 'Denpasar',
        'semarang' => 'Semarang',
        'malang' => 'Malang',
        'solo' => 'Solo',
        'yogyakarta' => 'Yogyakarta',
        'jogja' => 'Yogyakarta',
    ];
    return $map[$c] ?? mb_convert_case($c, MB_CASE_TITLE, 'UTF-8');
}

function admin_ai_parse_date(string $raw): ?string
{
    $raw = str_replace('/', '-', trim($raw));
    if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{2,4})$/', $raw, $m)) {
        $y = (int)$m[3];
        if ($y < 100) {
            $y += 2000;
        }
        $d = sprintf('%04d-%02d-%02d', $y, (int)$m[2], (int)$m[1]);
        $dt = DateTime::createFromFormat('Y-m-d', $d);
        return ($dt && $dt->format('Y-m-d') === $d) ? $d : null;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
        return $raw;
    }
    return null;
}

/**
 * @return array<string,mixed>|null
 */
function admin_ai_parse_intent_openai(string $question): ?array
{
    $today = date('Y-m-d');
    $sys = <<<SYS
Kamu asisten admin Mustika Travel. Ubah pertanyaan bahasa Indonesia jadi JSON intent saja (tanpa markdown).
Field:
- metric: pendapatan | penumpang | booking | pending | ringkas
- period: hari_ini | kemarin | minggu_ini | bulan_ini | bulan_lalu | tahun_ini | custom
- date_from: YYYY-MM-DD
- date_to: YYYY-MM-DD
- rute: string seperti "Blora - Surabaya" atau null
- status: completed | pending | confirmed | all
Hari ini: {$today}. Pendapatan default status completed. Jika user bilang bulan ini tanpa tanggal, period=bulan_ini.
SYS;

    $payload = [
        'model' => defined('AI_MODEL') && AI_MODEL !== '' ? AI_MODEL : 'gpt-4o-mini',
        'temperature' => 0,
        'max_tokens' => 200,
        'messages' => [
            ['role' => 'system', 'content' => $sys],
            ['role' => 'user', 'content' => $question],
        ],
        'response_format' => ['type' => 'json_object'],
    ];

    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        return null;
    }

    $base = defined('AI_BASE_URL') && AI_BASE_URL !== ''
        ? rtrim(AI_BASE_URL, '/')
        : 'https://api.openai.com/v1';
    $url = $base . '/chat/completions';

    $ch = curl_init($url);
    if ($ch === false) {
        return null;
    }
    $opts = [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_CONNECTTIMEOUT => 15,
    ] + wa_booking_curl_ssl_opts();
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno || $raw === false || $http >= 400) {
        return null;
    }

    $j = json_decode($raw, true);
    $content = $j['choices'][0]['message']['content'] ?? '';
    if (!is_string($content) || $content === '') {
        return null;
    }
    $parsed = json_decode($content, true);
    if (!is_array($parsed)) {
        return null;
    }

    $out = [];
    foreach (['metric', 'period', 'date_from', 'date_to', 'rute', 'status'] as $k) {
        if (isset($parsed[$k]) && $parsed[$k] !== '') {
            $out[$k] = is_string($parsed[$k]) ? trim($parsed[$k]) : $parsed[$k];
        }
    }
    return $out;
}

/**
 * @param array $intent
 * @return array{jumlah_booking:int,total_penumpang:int,total_pendapatan:int,date_from:string,date_to:string,rute:?string,status:string}
 */
function admin_ai_run_query(PDO $pdo, array $intent): array
{
    $from = $intent['date_from'] ?? date('Y-m-01');
    $to = $intent['date_to'] ?? date('Y-m-d');
    $status = $intent['status'] ?? 'completed';
    $rute = $intent['rute'] ?? null;

    $where = ['tanggal_berangkat BETWEEN ? AND ?'];
    $params = [$from, $to];

    if ($status !== 'all') {
        $where[] = 'status = ?';
        $params[] = $status;
    }

    if (is_string($rute) && $rute !== '') {
        // cocokkan longgar: "Blora - Surabaya" / "Blora–Surabaya" / mengandung kedua kota
        $where[] = '(rute LIKE ? OR rute LIKE ? OR (alamat_jemput LIKE ? AND alamat_tujuan LIKE ?) OR (alamat_tujuan LIKE ? AND alamat_jemput LIKE ?))';
        $norm = str_replace(['–', '—'], '-', $rute);
        $parts = preg_split('/\s*-\s*/', $norm, 2);
        $a = trim($parts[0] ?? '');
        $b = trim($parts[1] ?? '');
        $params[] = '%' . $norm . '%';
        $params[] = '%' . str_replace(' - ', '-', $norm) . '%';
        $params[] = '%' . $a . '%';
        $params[] = '%' . $b . '%';
        $params[] = '%' . $a . '%';
        $params[] = '%' . $b . '%';
    }

    $sql = 'SELECT COUNT(*) AS jumlah_booking,
                   COALESCE(SUM(jumlah_kursi), 0) AS total_penumpang,
                   COALESCE(SUM(total_harga), 0) AS total_pendapatan
            FROM bookings WHERE ' . implode(' AND ', $where);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'jumlah_booking' => (int)($row['jumlah_booking'] ?? 0),
        'total_penumpang' => (int)($row['total_penumpang'] ?? 0),
        'total_pendapatan' => (int)($row['total_pendapatan'] ?? 0),
        'date_from' => $from,
        'date_to' => $to,
        'rute' => $rute,
        'status' => $status,
    ];
}

function admin_ai_format_answer(array $intent, array $data): string
{
    $metric = $intent['metric'] ?? 'ringkas';
    $ruteLabel = !empty($data['rute']) ? $data['rute'] : 'semua rute';
    $periode = tgl_id($data['date_from']);
    if ($data['date_from'] !== $data['date_to']) {
        $periode .= ' s.d. ' . tgl_id($data['date_to']);
    }
    $statusLabel = [
        'completed' => 'selesai',
        'pending' => 'pending',
        'confirmed' => 'terkonfirmasi',
        'all' => 'semua status',
    ][$data['status']] ?? $data['status'];

    $lines = [];
    $lines[] = "📊 Hasil hitung Mustika Travel";
    $lines[] = "Rute: {$ruteLabel}";
    $lines[] = "Periode: {$periode}";
    $lines[] = "Filter status: {$statusLabel}";
    $lines[] = '';

    if ($metric === 'pendapatan') {
        $lines[] = '💰 Pendapatan: ' . rupiah($data['total_pendapatan']);
        $lines[] = '👥 Penumpang: ' . number_format($data['total_penumpang'], 0, ',', '.') . ' org';
        $lines[] = '🎫 Booking: ' . number_format($data['jumlah_booking'], 0, ',', '.');
    } elseif ($metric === 'penumpang') {
        $lines[] = '👥 Total penumpang: ' . number_format($data['total_penumpang'], 0, ',', '.') . ' org';
        $lines[] = '🎫 Booking: ' . number_format($data['jumlah_booking'], 0, ',', '.');
        $lines[] = '💰 Pendapatan terkait: ' . rupiah($data['total_pendapatan']);
    } elseif ($metric === 'booking' || $metric === 'pending') {
        $lines[] = '🎫 Jumlah booking: ' . number_format($data['jumlah_booking'], 0, ',', '.');
        $lines[] = '👥 Penumpang: ' . number_format($data['total_penumpang'], 0, ',', '.') . ' org';
        $lines[] = '💰 Total: ' . rupiah($data['total_pendapatan']);
    } else {
        $lines[] = '💰 Pendapatan: ' . rupiah($data['total_pendapatan']);
        $lines[] = '👥 Penumpang: ' . number_format($data['total_penumpang'], 0, ',', '.') . ' org';
        $lines[] = '🎫 Booking: ' . number_format($data['jumlah_booking'], 0, ',', '.');
    }

    if ($data['jumlah_booking'] === 0) {
        $lines[] = '';
        $lines[] = 'Belum ada data cocok. Cek status booking (laporan memakai status Selesai) atau nama rute di data.';
    }

    return implode("\n", $lines);
}
