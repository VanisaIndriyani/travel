<?php
// =============================================
// WA → Booking parser (rule-based + opsional AI)
// Dipakai admin Impor dari WA. Key API hanya di server.
// Tanpa OPENAI_API_KEY / AI_API_KEY: fallback regex tetap jalan.
// =============================================

/**
 * Struktur field default untuk preview form booking.
 */
function wa_booking_empty_fields(): array
{
    return [
        'nama'              => '',
        'no_hp'             => '',
        'alamat_jemput'     => '',
        'alamat_tujuan'     => '',
        'rute'              => '',
        'id_rute'           => 0,
        'tanggal_berangkat' => date('Y-m-d'),
        'jumlah_kursi'      => 1,
        'jam_jemput'        => '06:00',
        'jadwal_jemput'     => '',
        'lokasi_jemput'     => 'Blora',
        'barang_bawaan'     => '',
        'maps_link'         => '',
        'catatan_admin'     => '',
        'status'            => 'confirmed',
        'total_harga'       => 0,
    ];
}

/**
 * Orchestrator: AI (jika key ada) + fallback rules, lalu match rute DB.
 *
 * @param string $text Teks chat WA
 * @param array|null $image ['mime'=>string,'base64'=>string] opsional
 * @return array{ok:bool,method:string,fields:array,message?:string,ai_available:bool,vision_available:bool}
 */
function wa_booking_parse(string $text, ?array $image = null): array
{
    $text = trim($text);
    $hasImage = is_array($image)
        && !empty($image['base64'])
        && !empty($image['mime']);
    $aiAvailable = wa_booking_ai_available();
    $visionOk = $aiAvailable; // gpt-4o-mini & kompatibel vision

    if ($text === '' && !$hasImage) {
        return [
            'ok' => false,
            'method' => 'none',
            'fields' => wa_booking_empty_fields(),
            'message' => 'Tempel teks chat WA, atau upload screenshot (butuh API key).',
            'ai_available' => $aiAvailable,
            'vision_available' => $visionOk,
        ];
    }

    if ($text === '' && $hasImage && !$aiAvailable) {
        return [
            'ok' => false,
            'method' => 'none',
            'fields' => wa_booking_empty_fields(),
            'message' => 'Screenshot butuh OPENAI_API_KEY di .env. Untuk tanpa AI, tempel teks chat saja.',
            'ai_available' => false,
            'vision_available' => false,
        ];
    }

    $rules = $text !== '' ? wa_booking_parse_rules($text) : wa_booking_empty_fields();
    $method = 'rules';
    $merged = $rules;

    if ($aiAvailable && ($text !== '' || $hasImage)) {
        $ai = wa_booking_parse_ai($text, $hasImage ? $image : null);
        if (is_array($ai)) {
            $merged = wa_booking_merge_fields($ai, $rules);
            $method = ($text !== '' && wa_booking_fields_have_data($rules)) ? 'ai+rules' : 'ai';
        } elseif ($text === '' && $hasImage) {
            return [
                'ok' => false,
                'method' => 'none',
                'fields' => wa_booking_empty_fields(),
                'message' => 'Gagal membaca screenshot via AI. Tempel teks chat sebagai alternatif.',
                'ai_available' => true,
                'vision_available' => true,
            ];
        }
    }

    $fields = wa_booking_normalize_fields($merged);

    return [
        'ok' => true,
        'method' => $method,
        'fields' => $fields,
        'message' => wa_booking_method_label($method),
        'ai_available' => $aiAvailable,
        'vision_available' => $visionOk,
    ];
}

function wa_booking_ai_available(): bool
{
    return defined('OPENAI_API_KEY') && OPENAI_API_KEY !== '';
}

function wa_booking_method_label(string $method): string
{
    return match ($method) {
        'ai' => 'Diekstrak dengan AI. Periksa preview sebelum simpan.',
        'ai+rules' => 'Diekstrak dengan AI (dilengkapi parser lokal). Periksa preview sebelum simpan.',
        'rules' => 'Diekstrak dengan parser lokal (tanpa AI). Periksa & lengkapi field yang kosong.',
        default => 'Siap diperiksa.',
    };
}

function wa_booking_fields_have_data(array $f): bool
{
    foreach (['nama', 'no_hp', 'alamat_jemput', 'alamat_tujuan', 'rute'] as $k) {
        if (trim((string)($f[$k] ?? '')) !== '') return true;
    }
    return false;
}

/**
 * Isi kosong di $primary dari $fallback.
 */
function wa_booking_merge_fields(array $primary, array $fallback): array
{
    $out = wa_booking_empty_fields();
    foreach ($out as $k => $_) {
        $p = $primary[$k] ?? null;
        $f = $fallback[$k] ?? null;
        if ($k === 'jumlah_kursi' || $k === 'id_rute' || $k === 'total_harga') {
            $pv = (int)$p;
            $fv = (int)$f;
            $out[$k] = $pv > 0 ? $pv : ($fv > 0 ? $fv : (int)$out[$k]);
            continue;
        }
        $ps = is_string($p) ? trim($p) : (string)$p;
        $fs = is_string($f) ? trim($f) : (string)$f;
        if ($ps !== '' && $ps !== '0') {
            $out[$k] = $ps;
        } elseif ($fs !== '' && $fs !== '0') {
            $out[$k] = $fs;
        }
    }
    return $out;
}

/**
 * Parser rule-based untuk format WA Indonesia umum (label: nilai / bebas).
 */
function wa_booking_parse_rules(string $text): array
{
    $fields = wa_booking_empty_fields();
    $raw = str_replace(["\r\n", "\r"], "\n", $text);
    $raw = preg_replace("/[ \t]+/u", ' ', $raw) ?? $raw;

    // Maps link dulu (sebelum dipotong)
    if (preg_match('#(https?://(?:www\.)?(?:google\.[a-z.]+/maps|maps\.google\.[a-z.]+|maps\.app\.goo\.gl|goo\.gl/maps)[^\s<>"\']*)#i', $raw, $m)) {
        $fields['maps_link'] = sanitize_maps_link($m[1]);
        // Hapus URL dari teks agar tidak nyangkut di field lain
        $raw = str_replace($m[1], '', $raw);
    }

    $labeled = [
        'nama' => 'nama(?:\s*lengkap)?|penumpang|atas\s*nama|a\.?\s*n\.?',
        'no_hp' => 'no\.?\s*(?:hp|wa|whatsapp|telp|telepon)|hp|wa|whatsapp|telepon|kontak',
        'alamat_jemput' => 'alamat\s*(?:jemput|penjemputan|pickup|asal)|jemput(?:an)?|lokasi\s*jemput|pickup',
        'alamat_tujuan' => 'alamat\s*(?:tujuan|drop|sampai)|tujuan|drop\s*off|antar(?:an)?',
        'rute' => 'rute|jurusan|tujuan\s*perjalanan|trayek',
        'tanggal_berangkat' => 'tanggal(?:\s*berangkat)?|tgl(?:\s*berangkat)?|date|berangkat(?:\s*tgl)?',
        'jumlah_kursi' => 'jumlah(?:\s*kursi|\s*orang|\s*pax)?|kursi|seat|pax|orang',
        'jam_jemput' => 'jam(?:\s*jemput|\s*berangkat)?|waktu|pukul',
        'jadwal_jemput' => 'jadwal(?:\s*jemput|\s*berangkat)?',
        'barang_bawaan' => 'barang(?:\s*bawaan)?|bawaan|koper|luggage|kardus',
        'lokasi_jemput' => 'lokasi(?:\s*area)?(?:\s*jemput)?|area\s*jemput|kota\s*jemput',
        'catatan_admin' => 'catatan|note|keterangan|ket\.?|info\s*tambahan',
    ];

    foreach ($labeled as $field => $pattern) {
        if (preg_match('/(?:^|\n)\s*(?:' . $pattern . ')\s*[:\-=]\s*(.+?)(?=\n\s*(?:' . implode('|', $labeled) . ')\s*[:\-=]|\n\s*\n|$)/isu', $raw, $m)) {
            $val = trim($m[1]);
            $val = preg_replace('#https?://\S+#i', '', $val) ?? $val;
            $val = preg_replace('/\s+/u', ' ', $val) ?? $val;
            $val = trim($val);
            if ($val !== '') {
                $fields[$field] = $val;
            }
        }
    }

    // HP longgar jika belum ketemu
    if ($fields['no_hp'] === '' && preg_match('/(?:\+?62|0)\d[\d\s\-]{7,16}\d/', $raw, $m)) {
        $fields['no_hp'] = preg_replace('/\s+/', '', $m[0]) ?? $m[0];
    }

    // Nama: baris pertama mirip nama jika label tidak ada
    if ($fields['nama'] === '') {
        $lines = preg_split('/\n+/', $raw) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || preg_match('/https?:\/\//i', $line)) continue;
            if (preg_match('/^(?:' . implode('|', $labeled) . ')\s*[:\-=]/iu', $line)) continue;
            if (preg_match('/^\d/') || preg_match('/(?:\+?62|0)\d{8,}/', $line)) continue;
            if (mb_strlen($line) >= 3 && mb_strlen($line) <= 60 && preg_match('/^[\p{L}\s\.\'\-]+$/u', $line)) {
                $fields['nama'] = $line;
                break;
            }
        }
    }

    // Rute pola "Blora - Surabaya" / "Blora–Surabaya"
    if ($fields['rute'] === '' && preg_match('/\b([A-Za-zÀ-ÿ\.]+(?:\s+[A-Za-zÀ-ÿ\.]+)?)\s*[-–—]\s*([A-Za-zÀ-ÿ\.]+(?:\s+[A-Za-zÀ-ÿ\.]+)?)\b/u', $raw, $m)) {
        $a = trim($m[1]);
        $b = trim($m[2]);
        $skip = ['jam', 'tgl', 'tanggal', 'nama', 'hp', 'wa'];
        if (!in_array(mb_strtolower($a), $skip, true) && !in_array(mb_strtolower($b), $skip, true)) {
            $fields['rute'] = $a . ' - ' . $b;
        }
    }

    // Kursi: "2 orang" / "2 kursi" / "x2"
    if ((int)$fields['jumlah_kursi'] <= 1) {
        if (preg_match('/\b(\d{1,2})\s*(?:orang|kursi|pax|seat|penumpang)\b/iu', $raw, $m)
            || preg_match('/\bx\s*(\d{1,2})\b/iu', $raw, $m)) {
            $n = (int)$m[1];
            if ($n >= 1 && $n <= 50) $fields['jumlah_kursi'] = $n;
        }
    } else {
        if (preg_match('/(\d{1,2})/', (string)$fields['jumlah_kursi'], $m)) {
            $fields['jumlah_kursi'] = max(1, min(50, (int)$m[1]));
        }
    }

    // Tanggal berbagai format
    $fields['tanggal_berangkat'] = wa_booking_parse_tanggal(
        (string)$fields['tanggal_berangkat'],
        $raw
    );

    // Jam
    $jamSrc = (string)($fields['jam_jemput'] !== '06:00' ? $fields['jam_jemput'] : ($fields['jadwal_jemput'] ?: $raw));
    $jam = wa_booking_extract_jam($jamSrc);
    if ($jam) $fields['jam_jemput'] = $jam;
    elseif ($fields['jam_jemput'] === '06:00') {
        $jam2 = wa_booking_extract_jam($raw);
        if ($jam2) $fields['jam_jemput'] = $jam2;
    }

    // Lokasi area: prioritaskan alamat/lokasi jemput, jangan ikut nama kota tujuan
    $fields['lokasi_jemput'] = wa_booking_infer_lokasi(
        (string)$fields['lokasi_jemput'],
        (string)$fields['alamat_jemput'],
        (string)$fields['jadwal_jemput'],
        $raw
    );

    return $fields;
}

/**
 * Infer Blora / Surabaya / Sidoarjo / Lainnya dari teks jemput (bukan tujuan).
 */
function wa_booking_infer_lokasi(string $lokField, string $alamatJemput, string $jadwal, string $raw): string
{
    $priority = trim($lokField . ' ' . $alamatJemput . ' ' . $jadwal);
    $scan = $priority !== '' ? $priority : $raw;
    // Ambil baris yang mengandung kata jemput/dari bila ada
    if (preg_match('/(?:jemput|penjemputan|pickup|dari)\s*[:\-=]?\s*([^\n]+)/iu', $raw, $m)) {
        $scan = $m[1] . ' ' . $priority;
    }
    $s = mb_strtolower($scan);
    if (preg_match('/\bsidoarjo\b/u', $s)) return 'Sidoarjo';
    if (preg_match('/\bsurabaya\b|\bjuanda\b|\bgresik\b/u', $s)) return 'Surabaya';
    if (preg_match('/\bblora\b/u', $s)) return 'Blora';
    // Fallback: jika hanya rute "X - Y" dan X = Blora
    if (preg_match('/\bblora\s*[-–—]/iu', $raw)) return 'Blora';
    if (preg_match('/\b(?:surabaya|sidoarjo)\s*[-–—]/iu', $raw)) {
        return preg_match('/\bsidoarjo\s*[-–—]/iu', $raw) ? 'Sidoarjo' : 'Surabaya';
    }
    return 'Blora';
}

/**
 * Parse tanggal dari string field atau seluruh teks.
 */
function wa_booking_parse_tanggal(string $fieldVal, string $fullText): string
{
    $candidates = [$fieldVal, $fullText];
    $bulan = [
        'januari' => 1, 'jan' => 1, 'january' => 1,
        'februari' => 2, 'feb' => 2, 'february' => 2,
        'maret' => 3, 'mar' => 3, 'march' => 3,
        'april' => 4, 'apr' => 4,
        'mei' => 5, 'may' => 5,
        'juni' => 6, 'jun' => 6, 'june' => 6,
        'juli' => 7, 'jul' => 7, 'july' => 7,
        'agustus' => 8, 'agu' => 8, 'agt' => 8, 'aug' => 8, 'august' => 8,
        'september' => 9, 'sep' => 9, 'sept' => 9,
        'oktober' => 10, 'okt' => 10, 'oct' => 10, 'october' => 10,
        'november' => 11, 'nov' => 11,
        'desember' => 12, 'des' => 12, 'dec' => 12, 'december' => 12,
    ];

    foreach ($candidates as $src) {
        $src = trim($src);
        if ($src === '') continue;

        if (preg_match('/\b(20\d{2})-(\d{2})-(\d{2})\b/', $src, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        }
        if (preg_match('/\b(\d{1,2})[\/\-.](\d{1,2})[\/\-.](20\d{2})\b/', $src, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }
        if (preg_match('/\b(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2})\b/', $src, $m)) {
            $y = 2000 + (int)$m[3];
            return sprintf('%04d-%02d-%02d', $y, (int)$m[2], (int)$m[1]);
        }
        $blPat = implode('|', array_map('preg_quote', array_keys($bulan)));
        if (preg_match('/\b(\d{1,2})\s+(' . $blPat . ')\s*(20\d{2})?\b/iu', $src, $m)) {
            $mo = $bulan[mb_strtolower($m[2])] ?? 0;
            $y = !empty($m[3]) ? (int)$m[3] : (int)date('Y');
            if ($mo > 0) return sprintf('%04d-%02d-%02d', $y, $mo, (int)$m[1]);
        }
    }

    return date('Y-m-d');
}

function wa_booking_extract_jam(string $text): ?string
{
    if (preg_match('/\b([01]?\d|2[0-3])[.:]([0-5]\d)\b/', $text, $m)) {
        return sprintf('%02d:%02d', (int)$m[1], (int)$m[2]);
    }
    if (preg_match('/\b(?:jam|pukul)\s*([01]?\d|2[0-3])\b/iu', $text, $m)) {
        return sprintf('%02d:00', (int)$m[1]);
    }
    return null;
}

/**
 * Normalisasi + match rute DB + jadwal opsi admin.
 */
function wa_booking_normalize_fields(array $fields): array
{
    $out = wa_booking_empty_fields();
    foreach ($out as $k => $def) {
        if (array_key_exists($k, $fields)) {
            $out[$k] = $fields[$k];
        }
    }

    $out['nama'] = trim((string)$out['nama']);
    $out['no_hp'] = preg_replace('/[^\d+]/', '', (string)$out['no_hp']) ?? '';
    $out['alamat_jemput'] = trim((string)$out['alamat_jemput']);
    $out['alamat_tujuan'] = trim((string)$out['alamat_tujuan']);
    $out['rute'] = trim((string)$out['rute']);
    $out['barang_bawaan'] = trim((string)$out['barang_bawaan']);
    $out['catatan_admin'] = trim((string)$out['catatan_admin']);
    $out['maps_link'] = sanitize_maps_link((string)$out['maps_link']);
    $out['jumlah_kursi'] = max(1, min(50, (int)$out['jumlah_kursi']));

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$out['tanggal_berangkat'])) {
        $out['tanggal_berangkat'] = wa_booking_parse_tanggal((string)$out['tanggal_berangkat'], (string)$out['tanggal_berangkat']);
    }

    $jam = wa_booking_extract_jam((string)$out['jam_jemput']);
    $out['jam_jemput'] = $jam ?: '06:00';

    $lokValid = ['Blora', 'Surabaya', 'Sidoarjo', 'Lainnya'];
    if (!in_array($out['lokasi_jemput'], $lokValid, true)) {
        $out['lokasi_jemput'] = 'Blora';
    }

    // Match rute ke DB
    $matched = wa_booking_match_rute((string)$out['rute'], get_rutes(false));
    if ($matched) {
        $out['id_rute'] = (int)$matched['id'];
        $out['rute'] = (string)$matched['nama_rute'];
        $harga = (int)$matched['harga'];
        $out['total_harga'] = $harga * (int)$out['jumlah_kursi'];
    } else {
        $out['id_rute'] = (int)($out['id_rute'] ?? 0);
        if ((int)$out['total_harga'] <= 0) {
            $out['total_harga'] = get_harga_rute_default() * (int)$out['jumlah_kursi'];
        }
    }

    // Cocokkan jadwal opsi admin dari jam + lokasi
    $out['jadwal_jemput'] = wa_booking_match_jadwal(
        (string)$out['jadwal_jemput'],
        (string)$out['jam_jemput'],
        (string)$out['lokasi_jemput']
    );

    if (!in_array($out['status'], ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
        $out['status'] = 'confirmed';
    }

    return $out;
}

/**
 * Fuzzy match nama rute ke daftar DB.
 */
function wa_booking_match_rute(string $hint, array $rutes): ?array
{
    $hint = trim($hint);
    if ($hint === '' || empty($rutes)) return null;

    $norm = static function (string $s): string {
        $s = mb_strtolower(trim($s));
        $s = str_replace(['–', '—', '/', '\\'], '-', $s);
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;
        $s = preg_replace('/\s*-\s*/', '-', $s) ?? $s;
        return $s;
    };

    $h = $norm($hint);
    $best = null;
    $bestScore = 0;

    foreach ($rutes as $r) {
        $nama = (string)($r['nama_rute'] ?? '');
        if ($nama === '') continue;
        $n = $norm($nama);
        $score = 0;
        if ($n === $h) {
            $score = 100;
        } elseif (str_contains($n, $h) || str_contains($h, $n)) {
            $score = 80;
        } else {
            // Bandingkan token kota
            $ht = array_values(array_filter(preg_split('/[-,\s]+/', $h) ?: []));
            $nt = array_values(array_filter(preg_split('/[-,\s]+/', $n) ?: []));
            if (count($ht) >= 2 && count($nt) >= 2) {
                $overlap = count(array_intersect($ht, $nt));
                if ($overlap >= 2) $score = 70;
                elseif ($overlap === 1 && (str_contains($n, $ht[0]) || str_contains($n, $ht[count($ht) - 1]))) {
                    $score = 45;
                }
            }
            similar_text($h, $n, $pct);
            if ($pct > $score) $score = (int)$pct;
        }
        if ($score > $bestScore) {
            $bestScore = $score;
            $best = $r;
        }
    }

    return ($best && $bestScore >= 45) ? $best : null;
}

/**
 * Pilih value jadwal_jemput yang cocok dengan dropdown admin.
 */
function wa_booking_match_jadwal(string $existing, string $jam, string $lokasi): string
{
    $options = [
        'Blora' => [
            '08:00' => 'Jam 08.00 — Kota-kota Penjemputan area Blora',
            '11:00' => 'Jam 11.00 — (KHUSUS!) Door to Door SEMUA KECAMATAN BLORA (Unit Hiace)',
            '20:00' => 'Jam 20.00 — Kota-kota Penjemputan area Blora',
        ],
        'Surabaya' => [
            '10:00' => 'Jam 10.00 — Start dari Bandara Juanda (Surabaya)',
            '15:00' => 'Jam 15.00 — Start dari Bandara Juanda (Surabaya)',
            '20:00' => 'Jam 20.00 — (KHUSUS!) Start dari Sidoarjo · Door to Door SEMUA KECAMATAN Sidoarjo + Surabaya/Gresik',
        ],
        'Sidoarjo' => [
            '10:00' => 'Jam 10.00 — Start dari Bandara Juanda (Surabaya)',
            '15:00' => 'Jam 15.00 — Start dari Bandara Juanda (Surabaya)',
            '20:00' => 'Jam 20.00 — (KHUSUS!) Start dari Sidoarjo · Door to Door SEMUA KECAMATAN Sidoarjo + Surabaya/Gresik',
        ],
        'Lainnya' => [
            '06:00' => 'Jam 06.00 — Jadwal Khusus Lokasi Lainnya',
            '08:00' => 'Jam 08.00 — Jadwal Khusus Lokasi Lainnya',
            '12:00' => 'Jam 12.00 — Jadwal Khusus Lokasi Lainnya',
            '20:00' => 'Jam 20.00 — Jadwal Khusus Lokasi Lainnya',
        ],
    ];

    if ($existing !== '') {
        foreach ($options as $group) {
            foreach ($group as $val) {
                if ($existing === $val || str_contains($val, $existing) || str_contains($existing, substr($val, 0, 12))) {
                    return $val;
                }
            }
        }
    }

    $map = $options[$lokasi] ?? $options['Blora'];
    if (isset($map[$jam])) return $map[$jam];

    // Jam terdekat di opsi
    $jamMin = ((int)substr($jam, 0, 2)) * 60 + (int)substr($jam, 3, 2);
    $bestVal = '';
    $bestDiff = PHP_INT_MAX;
    foreach ($map as $k => $val) {
        $m = ((int)substr($k, 0, 2)) * 60 + (int)substr($k, 3, 2);
        $d = abs($m - $jamMin);
        if ($d < $bestDiff) {
            $bestDiff = $d;
            $bestVal = $val;
        }
    }
    return $bestVal;
}

/**
 * Panggil OpenAI-compatible Chat Completions (teks dan/atau vision).
 *
 * @return array|null fields atau null jika gagal
 */
function wa_booking_parse_ai(string $text, ?array $image = null): ?array
{
    if (!wa_booking_ai_available()) return null;

    $ruteNames = [];
    foreach (get_rutes(false) as $r) {
        if (!empty($r['nama_rute'])) $ruteNames[] = $r['nama_rute'];
    }
    $ruteList = $ruteNames ? implode(', ', $ruteNames) : '(tidak ada daftar rute)';

    $system = <<<'SYS'
Kamu asisten admin travel Indonesia. Ekstrak data pemesanan dari chat WhatsApp atau screenshot chat.
Balas HANYA JSON valid (tanpa markdown) dengan kunci:
nama, no_hp, alamat_jemput, alamat_tujuan, rute, tanggal_berangkat (YYYY-MM-DD), jumlah_kursi (int),
jam_jemput (HH:MM 24 jam), jadwal_jemput, lokasi_jemput (Blora|Surabaya|Sidoarjo|Lainnya),
barang_bawaan, maps_link, catatan_admin.
Jika tidak ada info, isi string kosong atau jumlah_kursi=1. Jangan mengarang nomor HP.
SYS;

    $userText = "Daftar rute DB (cocokkan nama jika memungkinkan): {$ruteList}\n\n";
    if ($text !== '') {
        $userText .= "Teks chat:\n" . mb_substr($text, 0, 8000);
    } else {
        $userText .= "Ekstrak data booking dari gambar screenshot chat WhatsApp.";
    }

    $content = [];
    $content[] = ['type' => 'text', 'text' => $userText];
    if ($image && !empty($image['base64']) && !empty($image['mime'])) {
        $mime = preg_replace('/[^a-z0-9.+\-\/]/i', '', (string)$image['mime']) ?: 'image/jpeg';
        $content[] = [
            'type' => 'image_url',
            'image_url' => [
                'url' => 'data:' . $mime . ';base64,' . $image['base64'],
            ],
        ];
    }

    $payload = [
        'model' => AI_MODEL,
        'temperature' => 0.1,
        'response_format' => ['type' => 'json_object'],
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $content],
        ],
    ];

    $url = AI_BASE_URL . '/chat/completions';
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($json === false) return null;

    $raw = null;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . OPENAI_API_KEY,
            ],
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);
        $raw = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $code < 200 || $code >= 300) {
            // Retry tanpa response_format (beberapa provider tidak support)
            unset($payload['response_format']);
            $json2 = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . OPENAI_API_KEY,
                ],
                CURLOPT_POSTFIELDS => $json2,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
            ]);
            $raw = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($raw === false || $code < 200 || $code >= 300) return null;
        }
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer " . OPENAI_API_KEY . "\r\n",
                'content' => $json,
                'timeout' => 60,
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) return null;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) return null;
    $contentOut = $decoded['choices'][0]['message']['content'] ?? '';
    if (!is_string($contentOut) || $contentOut === '') return null;

    // Strip ```json fences jika ada
    $contentOut = trim($contentOut);
    if (preg_match('/^```(?:json)?\s*([\s\S]*?)```$/i', $contentOut, $m)) {
        $contentOut = trim($m[1]);
    }

    $data = json_decode($contentOut, true);
    if (!is_array($data)) return null;

    $out = wa_booking_empty_fields();
    foreach ($out as $k => $_) {
        if (!array_key_exists($k, $data)) continue;
        if ($k === 'jumlah_kursi' || $k === 'id_rute' || $k === 'total_harga') {
            $out[$k] = (int)$data[$k];
        } else {
            $out[$k] = is_scalar($data[$k]) ? trim((string)$data[$k]) : '';
        }
    }
    return $out;
}
