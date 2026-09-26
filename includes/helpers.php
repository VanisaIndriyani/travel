<?php
// =============================================
// HELPER FUNCTIONS - fungsi umum dipakai disemua halaman
// =============================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Format angka menjadi Rupiah (Rp)
 * @param int $angka
 * @return string
 */
function rupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Redirect ke URL lain dengan pesan flash opsional
 * @param string $url
 * @return void
 */
function redirect($url)
{
    header("Location: " . $url);
    exit();
}

/**
 * Buat CSRF Token untuk keamanan form
 * @return string
 */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifikasi CSRF Token (dari POST default, atau GET jika diminta)
 * @param string $src 'post' atau 'get' - sumber token
 * @param bool $destroy apakah token dihapus setelah verifikasi (one-time use)
 * @return bool
 */
function csrf_verify($src = 'post', $destroy = true)
{
    $tokenSent = ($src === 'post') ? ($_POST['csrf_token'] ?? '') : ($_GET['csrf'] ?? '');
    if (empty($tokenSent) || empty($_SESSION['csrf_token']) || $tokenSent !== $_SESSION['csrf_token']) {
        return false;
    }
    if ($destroy) unset($_SESSION['csrf_token']);
    return true;
}

/**
 * Set Flash Message (ditampilkan sekali lalu hilang)
 * @param string $type = 'success' | 'error' | 'info' | 'warning'
 * @param string $pesan
 */
function set_flash($type, $pesan)
{
    $_SESSION['flash'] = [
        'type'  => $type,
        'pesan' => $pesan,
    ];
}

/**
 * Get Flash Message (tampilkan lalu hapus)
 * @return string|null HTML flash message atau null jika tidak ada
 */
function get_flash()
{
    if (empty($_SESSION['flash'])) return null;

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $colorMap = [
        'success' => 'bg-green-50 border-green-200 text-green-800 border-l-4 border-green-500',
        'error'   => 'bg-red-50 border-red-200 text-red-800 border-l-4 border-red-500',
        'info'    => 'bg-blue-50 border-blue-200 text-blue-800 border-l-4 border-blue-500',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800 border-l-4 border-yellow-500',
    ];
    $iconMap = [
        'success' => 'fa-circle-check',
        'error'   => 'fa-circle-xmark',
        'info'    => 'fa-circle-info',
        'warning' => 'fa-triangle-exclamation',
    ];
    $cls  = isset($colorMap[$flash['type']]) ? $colorMap[$flash['type']] : $colorMap['info'];
    $icon = isset($iconMap[$flash['type']]) ? $iconMap[$flash['type']] : 'fa-info-circle';

    return "
    <div x-data='{show:true}' x-show='show' x-transition
         class='{$cls} p-4 rounded-lg mb-6 shadow-sm flex items-start gap-3'>
        <i class='fa-solid {$icon} text-xl mt-0.5 flex-shrink-0'></i>
        <div class='flex-1 font-medium'>{$flash['pesan']}</div>
        <button @click='show=false' class='text-gray-500 hover:text-gray-800 transition'>
            <i class='fa-solid fa-xmark text-lg'></i>
        </button>
    </div>";
}

/**
 * Ambil old input (untuk mengisi ulang form jika validasi gagal)
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function old($key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

/**
 * Clear old input setelah dipakai
 */
function clear_old()
{
    unset($_SESSION['old']);
}

/**
 * Sanitasi output agar aman dari XSS
 * @param string $str
 * @return string
 */
function e($str)
{
    return htmlspecialchars(trim((string)$str), ENT_QUOTES, 'UTF-8');
}

/**
 * Dapatkan nama hari Indonesia dari tanggal
 * @param string|DateTime $tanggal format Y-m-d
 * @return string
 */
function nama_hari($tanggal)
{
    if (is_string($tanggal)) {
        $t = strtotime($tanggal);
    } else {
        $t = $tanggal->getTimestamp();
    }
    $hari = [
        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
        5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu'
    ];
    return $hari[date('w', $t)];
}

/**
 * Format tanggal Indonesia (contoh: 23 September 2026)
 * @param string|DateTime $tanggal
 * @return string
 */
function tgl_id($tanggal)
{
    if (is_string($tanggal)) {
        $timestamp = strtotime($tanggal);
    } else {
        $timestamp = $tanggal->getTimestamp();
    }
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return date('j', $timestamp) . ' ' . $bulan[(int)date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

/**
 * Dapatkan label status booking dalam bahasa Indonesia dengan warna badge
 * @param string $status pending|confirmed|completed|cancelled
 * @return array [label, class_tailwind]
 */
function status_booking($status)
{
    $map = [
        'pending'   => ['Pending',           'bg-yellow-100 text-yellow-800 border-yellow-300'],
        'confirmed' => ['Terkonfirmasi',     'bg-blue-100 text-blue-800 border-blue-300'],
        'completed' => ['Selesai',           'bg-green-100 text-green-800 border-green-300'],
        'cancelled' => ['Dibatalkan',        'bg-red-100 text-red-800 border-red-300'],
    ];
    return $map[$status] ?? $map['pending'];
}

/**
 * Dapatkan label source booking
 * @param string $source online|manual
 * @return array [label, class_tailwind]
 */
function source_booking($source)
{
    return $source === 'manual'
        ? ['Manual (WA)', 'bg-purple-50 text-purple-700 border border-purple-200']
        : ['Online',       'bg-sky-50 text-sky-700 border border-sky-200'];
}

// ============================================================================
// HELPER RUTE / JURUSAN + HARGA (Admin bisa atur dari dashboard)
// ============================================================================

/**
 * Fallback JIKA tabel rutes BELUM ADA di DB (belum re-install).
 * Generate array rutes dari $DAFTAR_JURUSAN config dengan format standar.
 */
function _rutes_fallback_from_config(): array
{
    global $DAFTAR_JURUSAN;
    $result = [];
    $no = 1;
    foreach ((array)$DAFTAR_JURUSAN as $namaRute => $harga) {
        if (is_numeric($namaRute)) {
            // JIKA $DAFTAR_JURUSAN masih format array NUMERIC (bukan assosiatif harga) - kompatibilitas lama
            $namaRute = $harga;
            $harga = HARGA_TIKET_DEFAULT;
        }
        $result[] = [
            'id'        => 0, // id = 0 menandakan fallback config (bukan dari DB)
            'nama_rute' => trim((string)$namaRute),
            'harga'     => (int)$harga,
            'is_aktif'  => 1,
            'urutan'    => $no,
        ];
        $no++;
    }
    return $result;
}

/**
 * Cek apakah tabel rutes SUDAH ADA di database?
 */
function _tabel_rutes_ada(): bool
{
    global $pdo;
    if (empty($pdo)) return false;
    try {
        $cek = $pdo->query("SHOW TABLES LIKE 'rutes'")->fetchColumn();
        return !empty($cek);
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Ambil SEMUA daftar rute dari DATABASE tabel `rutes`, diurutkan nomor urut.
 * JIKA tabel rutes BELUM ADA → FALLBACK ke $DAFTAR_JURUSAN config (backward compatible)
 *
 * @param bool $aktif_only = true → hanya ambil rute dengan is_aktif=1
 * @return array [ ['id','nama_rute','harga','is_aktif','urutan'], ... ]
 */
function get_rutes(bool $aktif_only = true): array
{
    if (!_tabel_rutes_ada()) {
        $list = _rutes_fallback_from_config();
        if (!$aktif_only) return $list;
        return array_values(array_filter($list, function($r){
            return !empty($r['is_aktif']);
        }));
    }

    global $pdo;
    $sql = "SELECT * FROM rutes ";
    if ($aktif_only) $sql .= "WHERE is_aktif = 1 ";
    $sql .= "ORDER BY urutan ASC, id ASC";
    try {
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return _rutes_fallback_from_config();
    }
}

/**
 * Ambil 1 data rute berdasarkan ID (dari tabel rutes DB)
 * JIKA tidak ada → return null
 */
function get_rute_by_id(int $id): ?array
{
    if ($id <= 0 || !_tabel_rutes_ada()) return null;
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM rutes WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Dapatkan HARGA DEFAULT PER ORANG (fallback jika rute belum dipilih)
 * Urutan: rute pertama aktif → constant HARGA_TIKET_DEFAULT → 200000
 */
function get_harga_rute_default(): int
{
    $daftar = get_rutes(true);
    if (!empty($daftar) && !empty($daftar[0]['harga'])) return (int)$daftar[0]['harga'];
    return defined('HARGA_TIKET_DEFAULT') ? (int)HARGA_TIKET_DEFAULT : 200000;
}

/**
 * Cari HARGA rute BERDASARKAN NAMA RUTE (digunakan untuk backward compatible booking lama yang cuma simpan nama_rute tanpa id_rute)
 */
function cari_harga_rute_by_nama(string $nama_rute): int
{
    $nama_rute = trim($nama_rute);
    if ($nama_rute === '') return get_harga_rute_default();
    $daftar = get_rutes(false);
    foreach ($daftar as $r) {
        if (mb_strtolower(trim($r['nama_rute'])) === mb_strtolower($nama_rute)) {
            return (int)$r['harga'];
        }
    }
    return get_harga_rute_default();
}

// ============================================================================
// HELPER ARMADA / KENDARAAN (Admin bisa atur dari dashboard)
// ============================================================================

/**
 * Fallback JIKA tabel armadas BELUM ADA di DB (belum re-install).
 * Generate 3 default armada (Bus, Hiace, Avanza) dengan format standar.
 */
function _armadas_fallback_default(): array
{
    return [
        [
            'id'          => 0,
            'nama_armada' => 'Bus Pariwisata',
            'icon_fa'     => 'fa-bus-simple',
            'kapasitas'   => 'Kapasitas 30-40 Kursi',
            'deskripsi'   => 'Perjalanan rombongan besar, wisata, study tour, company outing. Full AC, reclining seat, karaoke, DVD player, toilet, selimut dan snack!',
            'warna_grad'  => 'from-blue-600 to-primary-800',
            'gambar_url'  => 'photo-1544620347-c4fd4a3d5957',
            'fitur_list'  => '["AC Dingin","Reclining Seat","Karaoke","Snack & Air Mineral","Toilet","TV LED 32\""]',
            'urutan'      => 1,
            'is_aktif'    => 1,
        ],
        [
            'id'          => 0,
            'nama_armada' => 'Toyota Hiace Premio',
            'icon_fa'     => 'fa-van-shuttle',
            'kapasitas'   => 'Kapasitas 10-15 Kursi',
            'deskripsi'   => 'Cocok untuk keluarga, rombongan kantor kecil, perjalanan wisata medium. Nyaman, bodi tinggi, suspensi empuk, suara mesin halus.',
            'warna_grad'  => 'from-emerald-500 to-teal-700',
            'gambar_url'  => 'photo-1609521263047-f8f205293f24',
            'fitur_list'  => '["Full AC","15 Seat Luas","Audio Premium","Charging USB","Selimut","Bagasi Luas"]',
            'urutan'      => 2,
            'is_aktif'    => 1,
        ],
        [
            'id'          => 0,
            'nama_armada' => 'Toyota Avanza / Xenia',
            'icon_fa'     => 'fa-car-side',
            'kapasitas'   => 'Kapasitas 5-7 Kursi',
            'deskripsi'   => 'Pilihan terbaik untuk keluarga kecil / perjalanan pribadi 2-6 orang. Hemat, lincah di kota, AC dingin, door-to-door sampai alamat tujuan.',
            'warna_grad'  => 'from-amber-400 to-orange-600',
            'gambar_url'  => 'photo-1621007947382-bb3c3994e3fb',
            'fitur_list'  => '["7 Seat MPV","AC Dingin","Audio MP3","Bagasi Kabin","Hemat BBM","Mudah Parkir"]',
            'urutan'      => 3,
            'is_aktif'    => 1,
        ],
    ];
}

/**
 * Cek apakah tabel armadas SUDAH ADA di database?
 */
function _tabel_armadas_ada(): bool
{
    global $pdo;
    if (empty($pdo)) return false;
    try {
        $cek = $pdo->query("SHOW TABLES LIKE 'armadas'")->fetchColumn();
        return !empty($cek);
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Ambil SEMUA daftar armada dari DATABASE tabel `armadas`, diurutkan nomor urut.
 * JIKA tabel armadas BELUM ADA → FALLBACK ke 3 default (backward compatible)
 *
 * @param bool $aktif_only = true → hanya ambil armada dengan is_aktif=1
 * @return array [ ['id','nama_armada','icon_fa','kapasitas','deskripsi','warna_grad','gambar_url','fitur_list'(json string),'urutan','is_aktif'], ... ]
 */
function get_armadas(bool $aktif_only = true): array
{
    if (!_tabel_armadas_ada()) {
        $list = _armadas_fallback_default();
        if (!$aktif_only) return $list;
        return array_values(array_filter($list, function($r){ return !empty($r['is_aktif']); }));
    }

    global $pdo;
    $sql = "SELECT * FROM armadas ";
    if ($aktif_only) $sql .= "WHERE is_aktif = 1 ";
    $sql .= "ORDER BY urutan ASC, id ASC";
    try {
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return _armadas_fallback_default();
    }
}

/**
 * Parse fitur_list (JSON) menjadi array PHP, dengan fallback jika invalid / kosong.
 */
function parse_fitur_armada($fiturListRaw): array
{
    if (empty($fiturListRaw)) return [];
    if (is_array($fiturListRaw)) return $fiturListRaw;
    try {
        $arr = json_decode((string)$fiturListRaw, true);
        if (is_array($arr) && !empty($arr)) return $arr;
    } catch (Throwable $e) {}
    return [];
}

/**
 * Ambil 1 data armada berdasarkan ID (dari tabel armadas DB)
 * JIKA tidak ada → return null
 */
function get_armada_by_id(int $id): ?array
{
    if ($id <= 0 || !_tabel_armadas_ada()) return null;
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM armadas WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Resolve path gambar (upload lokal / URL / Unsplash ID) menjadi src siap tampil.
 */
function resolve_gambar_src(string $img_id): string
{
    $img_id = trim($img_id);
    if ($img_id === '') {
        return 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=700&q=80&auto=format&fit=crop';
    }
    if (stripos($img_id, 'http') === 0) return $img_id;
    if (stripos($img_id, 'uploads/') === 0 || stripos($img_id, '/uploads/') !== false) {
        return rtrim(BASE_URL, '/') . '/' . ltrim($img_id, '/');
    }
    return 'https://images.unsplash.com/' . $img_id . '?w=700&q=80&auto=format&fit=crop';
}

// ============================================================================
// HELPER CARTER / PAKET PP-DROP (Admin bisa atur dari dashboard)
// ============================================================================

function _carters_fallback_default(): array
{
    return [
        [
            'id'         => 0,
            'nama_unit'  => 'Unit Avanza / Xenia',
            'icon_fa'    => 'fa-car',
            'kapasitas'  => '5 - 7 Kursi',
            'deskripsi'  => 'Cocok untuk keluarga kecil, meeting kecil, drop off bandara.',
            'warna_grad' => 'from-emerald-400 to-teal-600',
            'gambar_url' => 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=700&q=80',
            'fitur_list' => '["AC Dingin","Audio MP3","Bagasi Luas","Hemat BBM"]',
            'urutan'     => 1,
            'is_aktif'   => 1,
        ],
        [
            'id'         => 0,
            'nama_unit'  => 'Elf Short',
            'icon_fa'    => 'fa-van-shuttle',
            'kapasitas'  => '15 Kursi',
            'deskripsi'  => 'Pas untuk rombongan 10-14 orang, tour kota, study tour sekolah.',
            'warna_grad' => 'from-blue-400 to-primary-700',
            'gambar_url' => 'https://images.unsplash.com/photo-1609521263047-f8f205293f24?w=700&q=80',
            'fitur_list' => '["Full AC","15 Seat Luas","Audio Premium","Charging USB","Bagasi Luas"]',
            'urutan'     => 2,
            'is_aktif'   => 1,
        ],
        [
            'id'         => 0,
            'nama_unit'  => 'Elf Long',
            'icon_fa'    => 'fa-bus',
            'kapasitas'  => '19 Kursi',
            'deskripsi'  => 'Pilihan pas rombongan 15-18 orang, body panjang, suspensi nyaman.',
            'warna_grad' => 'from-amber-400 to-orange-600',
            'gambar_url' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=700&q=80',
            'fitur_list' => '["Full AC","19 Seat Reclining","Karaoke Mini","LED TV","Snack & Minum"]',
            'urutan'     => 3,
            'is_aktif'   => 1,
        ],
        [
            'id'         => 0,
            'nama_unit'  => 'Bus Pariwisata',
            'icon_fa'    => 'fa-bus-alt',
            'kapasitas'  => '30 - 50 Kursi',
            'deskripsi'  => 'Untuk rombongan BESAR! Wisata, Outbound, Company outing, Wedding.',
            'warna_grad' => 'from-purple-400 to-indigo-700',
            'gambar_url' => 'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=700&q=80',
            'fitur_list' => '["Full AC Dingin","Reclining Seat","Karaoke + TV LED","Toilet onboard","Selimut + Snack"]',
            'urutan'     => 4,
            'is_aktif'   => 1,
        ],
    ];
}

function _sql_create_carters(): string
{
    return "CREATE TABLE IF NOT EXISTS `carters` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `nama_unit` VARCHAR(200) NOT NULL,
      `icon_fa` VARCHAR(80) NOT NULL DEFAULT 'fa-car',
      `kapasitas` VARCHAR(80) NOT NULL DEFAULT '',
      `deskripsi` TEXT NULL,
      `warna_grad` VARCHAR(120) NOT NULL DEFAULT 'from-navy-800 to-navy-950',
      `gambar_url` VARCHAR(500) NOT NULL DEFAULT '',
      `fitur_list` TEXT NULL,
      `urutan` INT NOT NULL DEFAULT 0,
      `is_aktif` TINYINT(1) NOT NULL DEFAULT 1,
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY `unik_nama_unit` (`nama_unit`),
      INDEX `idx_aktif` (`is_aktif`),
      INDEX `idx_urutan` (`urutan`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
}

function _tabel_carters_ada(): bool
{
    global $pdo;
    if (empty($pdo)) return false;
    try {
        $cek = $pdo->query("SHOW TABLES LIKE 'carters'")->fetchColumn();
        return !empty($cek);
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Buat tabel carters + seed default jika belum ada (upgrade DB existing tanpa re-install).
 */
function ensure_carters_table(): bool
{
    global $pdo;
    if (empty($pdo)) return false;
    try {
        $pdo->exec(_sql_create_carters());
        $count = (int)$pdo->query("SELECT COUNT(*) FROM carters")->fetchColumn();
        if ($count === 0) {
            $stmt = $pdo->prepare("INSERT INTO carters (nama_unit,icon_fa,kapasitas,deskripsi,warna_grad,gambar_url,fitur_list,urutan,is_aktif,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,1,NOW(),NOW())");
            foreach (_carters_fallback_default() as $row) {
                $stmt->execute([
                    $row['nama_unit'], $row['icon_fa'], $row['kapasitas'], $row['deskripsi'],
                    $row['warna_grad'], $row['gambar_url'], $row['fitur_list'], $row['urutan'],
                ]);
            }
        }
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function get_carters(bool $aktif_only = true): array
{
    if (!_tabel_carters_ada()) {
        if (!ensure_carters_table()) {
            $list = _carters_fallback_default();
            if (!$aktif_only) return $list;
            return array_values(array_filter($list, function($r){ return !empty($r['is_aktif']); }));
        }
    }

    global $pdo;
    $sql = "SELECT * FROM carters ";
    if ($aktif_only) $sql .= "WHERE is_aktif = 1 ";
    $sql .= "ORDER BY urutan ASC, id ASC";
    try {
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return _carters_fallback_default();
    }
}

function get_carter_by_id(int $id): ?array
{
    if ($id <= 0) return null;
    if (!_tabel_carters_ada() && !ensure_carters_table()) return null;
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM carters WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Pastikan kolom maps_link ada di tabel bookings (upgrade DB tanpa re-install).
 * Aman dipanggil berulang; gagal → false (bisa jalankan sql/add_maps_link.sql manual).
 */
function ensure_bookings_maps_link_column(): bool
{
    global $pdo;
    if (empty($pdo)) return false;
    static $cached = null;
    if ($cached === true) return true;
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `bookings` LIKE 'maps_link'")->fetch(PDO::FETCH_ASSOC);
        if (!empty($cols)) {
            $cached = true;
            return true;
        }
        $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `maps_link` VARCHAR(500) NULL DEFAULT NULL COMMENT 'Link Google Maps pin lokasi jemput (opsional)' AFTER `alamat_jemput`");
        $cached = true;
        return true;
    } catch (Throwable $e) {
        $cached = false;
        return false;
    }
}

/**
 * Validasi & bersihkan URL Google Maps dari input penumpang/admin.
 * Kosong = OK (opsional). URL non-Maps ditolak → string kosong.
 */
function sanitize_maps_link(?string $url): string
{
    $url = trim((string)$url);
    if ($url === '') return '';
    if (strlen($url) > 500) $url = substr($url, 0, 500);
    if (!preg_match('#^https?://#i', $url)) return '';
    $ok = preg_match(
        '#^https?://(www\.)?(google\.[a-z.]+/maps|maps\.google\.[a-z.]+|maps\.app\.goo\.gl|goo\.gl/maps)#i',
        $url
    );
    return $ok ? $url : '';
}

/**
 * Infer area penjemputan dari teks jadwal (Blora / Surabaya) untuk kompatibilitas admin.
 */
function infer_lokasi_from_jadwal(string $jadwal): string
{
    $j = strtolower($jadwal);
    if (strpos($j, 'surabaya') !== false || strpos($j, 'sidoarjo') !== false
        || strpos($j, 'juanda') !== false || strpos($j, 'gresik') !== false) {
        return 'Surabaya';
    }
    return 'Blora';
}
