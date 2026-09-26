<?php
// =============================================
// MUSTIKA TRAVEL - GLOBAL CONFIGURATION
// Edit semua setting dasar disini aja ya kaak
// =============================================

// 1. Identitas Website
define('SITE_NAME', 'Mustika Travel');
define('SITE_TAGLINE', 'Perjalanan Aman, Nyaman, Sampai Tujuan');
define('SITE_FULL_NAME', 'Mustika Travel - Travel Door to Door Terpercaya');

// 2. Harga Tiket Default (harga dasar per orang untuk booking online)
// Catatan: Admin bisa edit manual total_harga tiap booking di dashboard
define('HARGA_TIKET_DEFAULT', 200000); // Rp 200.000 / org

// 3. Kontak Admin - EDIT DISINI YA KAAK
define('WA_ADMIN', '62882003082936'); // Ganti nomor WA admin disini
define('WA_ADMIN_DISPLAY', '+62 882-0030-82936'); // Tampilan di website
define('ALAMAT_KANTOR', 'Jl. Contoh No. 123, Blora, Jawa Tengah'); // Ganti alamat disini
define('EMAIL_ADMIN', 'admin@mustikatravel.com');

// 4. Daftar Jurusan / Rute Travel (DEFAULT AWAL SAAT INSTALL)
// NANTI SETELAH INSTALL, ADMIN BISA TAMBAH / EDIT / HAPUS & GANTI HARGA DARI DASHBOARD ADMIN LANGSUNG!
// Format: 'Nama Rute (asal - tujuan)' => harga per orang (integer, TANPA TITIK / RP)
$DAFTAR_JURUSAN = [
    'Blora - Semarang'   => 200000,
    'Blora - Solo'       => 250000,
    'Blora - Jepara'     => 180000,
    'Blora - Surabaya'   => 300000,
    'Blora - Malang'     => 350000,
    'Blora - Jember'     => 400000,
    'Blora - Banyuwangi' => 450000,
    'Blora - Denpasar'   => 650000,
];
// (NOTE: $DAFTAR_JURUSAN di atas HANYA DIPAKAI SEKALI SAAT install.php import default ke tabel `rutes` database)

// 5. Info Pembatalan
define('PEMBATALAN_INFO', 'Pembatalan tiket dikenakan denda 50% dari harga tiket');

// 6. Default Admin Login Credential (default pertama install)
// Setelah login, sebaiknya ganti password di database ya
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', 'admin123');

// 7. URL Path — AUTO-DETECT (jangan hardcode localhost!)
// Berjalan di Laragon (…/SEPTEMBER/Travel) maupun hosting (…/travel) tanpa edit manual.
// Opsional override: define('BASE_URL_OVERRIDE', 'https://domain.com/travel'); sebelum require config.
if (!function_exists('mustika_detect_base_url')) {
    function mustika_detect_base_url(): string
    {
        if (defined('BASE_URL_OVERRIDE') && is_string(BASE_URL_OVERRIDE) && BASE_URL_OVERRIDE !== '') {
            return rtrim(BASE_URL_OVERRIDE, '/');
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');

        $basePath = '';
        $detected = false;
        $projectRoot = realpath(__DIR__ . '/..');
        $docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;

        if ($projectRoot && $docRoot) {
            $projectRootNorm = str_replace('\\', '/', $projectRoot);
            $docRootNorm = rtrim(str_replace('\\', '/', $docRoot), '/');
            // stripos: Windows path case-insensitive
            if ($docRootNorm !== '' && stripos($projectRootNorm, $docRootNorm) === 0) {
                $basePath = substr($projectRootNorm, strlen($docRootNorm));
                $detected = true;
            }
        }

        // Fallback: dari SCRIPT_NAME (index.php / booking.php / admin/*.php)
        if (!$detected) {
            $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
            $dir = str_replace('\\', '/', dirname($script));
            // Jika request dari /admin/…, naik 1 level ke root project
            if (strcasecmp(basename($dir), 'admin') === 0) {
                $dir = str_replace('\\', '/', dirname($dir));
            }
            if ($dir === '/' || $dir === '.' || $dir === '\\') {
                $basePath = '';
            } else {
                $basePath = $dir;
            }
        }

        $basePath = '/' . trim(str_replace('\\', '/', (string)$basePath), '/');
        if ($basePath === '/') {
            $basePath = '';
        }

        return rtrim($scheme . '://' . $host . $basePath, '/');
    }
}

if (!defined('BASE_URL')) {
    define('BASE_URL', mustika_detect_base_url());
}

// 8. Session Start (untuk flash message & admin login)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// =============================================
// 9. AI / OpenAI-compatible (opsional — Impor dari WA)
// Tanpa key: parser rule-based tetap jalan (tempel teks chat).
// Dengan key: ekstraksi lebih akurat; screenshot butuh model vision.
// Set di file .env di root project (jangan commit key):
//   OPENAI_API_KEY=sk-...   (atau AI_API_KEY=...)
//   AI_BASE_URL=https://api.openai.com/v1   (opsional)
//   AI_MODEL=gpt-4o-mini                    (opsional)
// =============================================
if (!function_exists('mustika_load_dotenv')) {
    /**
     * Load .env dari path absolut (tidak bergantung cwd).
     * File .env mengisi variabel yang belum ada ATAU kosong di environment —
     * penting di hosting: kadang OPENAI_API_KEY sudah "ada" tapi string kosong.
     */
    function mustika_load_dotenv(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) return;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, '=') === false) continue;
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if ($name === '') continue;
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }
            $existing = getenv($name);
            if ($existing === false) {
                $existing = $_ENV[$name] ?? $_SERVER[$name] ?? false;
            }
            // Isi jika belum ada, atau ada tapi kosong (sering di shared hosting)
            if ($existing === false || $existing === '') {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Path absolut ke root project — aman dari admin/ maupun CLI (cwd beda)
$mustikaEnvCandidates = [
    dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env',
    (realpath(dirname(__DIR__)) ?: dirname(__DIR__)) . DIRECTORY_SEPARATOR . '.env',
];
foreach (array_unique($mustikaEnvCandidates) as $mustikaEnvPath) {
    mustika_load_dotenv($mustikaEnvPath);
}

if (!function_exists('mustika_env')) {
    function mustika_env(string $name, string $default = ''): string
    {
        $v = getenv($name);
        if ($v === false || $v === '') {
            $v = $_ENV[$name] ?? $_SERVER[$name] ?? $default;
        }
        return is_string($v) ? $v : $default;
    }
}

if (!defined('OPENAI_API_KEY')) {
    $aiKey = mustika_env('OPENAI_API_KEY', mustika_env('AI_API_KEY', ''));
    define('OPENAI_API_KEY', trim($aiKey));
}
if (!defined('AI_BASE_URL')) {
    $aiBase = mustika_env('AI_BASE_URL', 'https://api.openai.com/v1');
    define('AI_BASE_URL', rtrim($aiBase !== '' ? $aiBase : 'https://api.openai.com/v1', '/'));
}
if (!defined('AI_MODEL')) {
    $aiModel = mustika_env('AI_MODEL', 'gpt-4o-mini');
    define('AI_MODEL', $aiModel !== '' ? $aiModel : 'gpt-4o-mini');
}
