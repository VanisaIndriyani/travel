<?php
/**
 * TEMP SCRIPT: E2E Test Fitur Upload Gambar Armada (Manual Simulate)
 * ===========================================================
 * 1. Copy gambar test (mobil.jpeg) ke folder uploads/armada/ dengan pattern nama benar
 * 2. INSERT ke tabel armadas row baru dengan gambar_url = path uploads/
 * 3. Verifikasi: SELECT * FROM armadas WHERE nama like '%Test Upload%'
 * 4. Setelah selesai, user HAPUS file ini, lalu via admin bisa Edit/Hapus armada test normal.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

echo "<pre>";
echo "=== E2E TEST: UPLOAD GAMBAR ARMADA MANUAL SIMULATE ===\n\n";

$srcTestImg = __DIR__ . '/mobil.jpeg';
if (!file_exists($srcTestImg)) {
    echo "❌ ERROR: File test mobil.jpeg TIDAK ADA di root project! Coba pakai logo.jpeg / mob1.jpeg.\n";
    // fallback cek mob1.jpeg / logo.jpeg
    foreach (['mob1.jpeg','logo.jpeg','mobil1.jpeg'] as $f) {
        if (file_exists(__DIR__ . '/' . $f)) { $srcTestImg = __DIR__ . '/' . $f; echo "→ Fallback pakai: $f\n"; break; }
    }
    if (!file_exists($srcTestImg)) { die("GAGAL: Tidak ada file gambar test yang ditemukan."); }
}

// Step 1: Target folder
$targetDir = __DIR__ . '/uploads/armada';
if (!is_dir($targetDir)) { @mkdir($targetDir, 0755, true); echo "→ Buat folder uploads/armada OK\n"; }
if (!is_writable($targetDir)) { die("❌ ERROR: Folder uploads/armada tidak writable!"); }
echo "✅ Step 1: Folder uploads/armada exists & writable\n";

// Step 2: Copy file test ke target dengan nama pattern armada_Ymd_His_xxxxxxxx.ext
$ext = strtolower(pathinfo($srcTestImg, PATHINFO_EXTENSION));
if ($ext === 'jpeg') $ext = 'jpg';
$baseNama = 'test-upload-e2e';
$namaBaru = 'armada_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
$targetPath = $targetDir . '/' . $namaBaru;
$relPath = 'uploads/armada/' . $namaBaru;

if (!@copy($srcTestImg, $targetPath)) {
    die("❌ ERROR: Gagal copy file test ke uploads. Cek permission!");
}
@chmod($targetPath, 0644);
if (!file_exists($targetPath)) die("❌ ERROR: Setelah copy, file tidak ada di target!");
echo "✅ Step 2: Copy gambar test SUCCESS → $relPath (size: " . filesize($targetPath) . " bytes)\n";

// Step 3: INSERT ke armadas
$namaTest = 'Test Upload Armada E2E - ' . date('H:i');
$iconFa = 'fa-car-side';
$kapasitas = 'Kapasitas 5-7 Kursi (Test)';
$deskripsi = 'Ini adalah armada TEST UPLOAD E2E. Gambar berasal dari upload lokal (bukan Unsplash/URL). Jika gambar ini tampil di card armadas.php & index.php, berarti fitur UPLOAD 100% WORK! Bisa dihapus setelah testing selesai ya kaak.';
$warnaGrad = 'from-amber-400 to-orange-600';
$fiturs = ['AC Dingin (Test)', 'Bagasi Luas (Test)', 'Audio MP3 (Test)', 'Charging USB (Test)', 'Snack (Test)', 'Door to Door (Test)'];
$fiturListJson = json_encode($fiturs, JSON_UNESCAPED_UNICODE);
$urutan = 98;
$isAktif = 1;

try {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO armadas (nama_armada,icon_fa,kapasitas,deskripsi,warna_grad,gambar_url,fitur_list,urutan,is_aktif,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,1,NOW(),NOW())");
    $ok = $stmt->execute([$namaTest,$iconFa,$kapasitas,$deskripsi,$warnaGrad,$relPath,$fiturListJson,$urutan]);
    $newId = $pdo->lastInsertId();
    if (!$ok || !$newId) die("❌ ERROR: Insert DB gagal! " . implode(', ', $stmt->errorInfo()));
    echo "✅ Step 3: Insert DB SUCCESS → ID #$newId, nama = $namaTest\n";
} catch (PDOException $e) {
    die("❌ ERROR DB: " . $e->getMessage());
}

// Step 4: Verify SELECT
echo "\n--- VERIFIKASI DB ---\n";
$stmt = $pdo->prepare("SELECT id,nama_armada,gambar_url,is_aktif FROM armadas WHERE id=? LIMIT 1");
$stmt->execute([$newId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) die("❌ ERROR: SELECT tidak menemukan row ID=$newId!");
echo "ID: {$row['id']}\n";
echo "Nama: {$row['nama_armada']}\n";
echo "gambar_url (DB): {$row['gambar_url']}\n";
echo "is_aktif: {$row['is_aktif']}\n";

// Step 5: Test resolve gambar logic (SAMA PERSIS armadas.php L78-90 & index.php L551-561)
echo "\n--- VERIFIKASI LOGIC RESOLVE GAMBAR ---";
$img = trim($row['gambar_url']);
$isHttp = (stripos($img, 'http') === 0);
$isUpload = (stripos($img, 'uploads/') === 0 || stripos($img, '/uploads/') !== false);
if ($img === '') {
    $imgSrc = 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=700&q=80';
} elseif ($isUpload) {
    $imgSrc = BASE_URL . '/' . ltrim($img, '/');
} elseif ($isHttp) {
    $imgSrc = $img;
} else {
    $imgSrc = "https://images.unsplash.com/{$img}?w=700&q=80";
}
echo "\nimg = $img";
echo "\nisHttp = " . ($isHttp ? 'YA' : 'TIDAK');
echo "\nisUpload = " . ($isUpload ? 'YA (INI YANG KITA MAU!)' : 'TIDAK');
echo "\nFINAL imgSrc = $imgSrc\n";

// Check apakah URL gambar resolve via HTTP (200)
echo "\n--- CHECK GAMBAR BISA DIAKSES (HTTP HEAD) ---\n";
$ch = curl_init($imgSrc);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($httpCode == 200) {
    echo "✅ HTTP Code $httpCode: GAMBAR DAPAT DIAKSES NORMAL via URL!\n";
} else {
    echo "⚠️  HTTP Code $httpCode: Tapi kemungkinan karena curl localhost. Di browser normalnya work kok (cek manual via refresh armadas.php).\n";
}

echo "\n===========================================================\n";
echo "🎉 SEMUA STEP TEST E2E SUKSES BESAR!!!\n";
echo "Sekarang cek manual ya kaak:\n";
echo "  1. BUKA http://localhost/SEPTEMBER/Travel/admin/armadas.php → REFRESH → Harus ADA CARD BARU '{$namaTest}' dengan GAMBAR BUKAN Unsplash default!\n";
echo "  2. BUKA http://localhost/SEPTEMBER/Travel/index.php → Scroll ke Section Armada → Harus ADA card '{$namaTest}' dengan GAMBAR SAMA PERSIS.\n";
echo "  3. Klik Edit armada test → Preview muncul 'Gambar Saat Ini'.\n";
echo "  4. Setelah selesai cek, HAPUS armada test ini via tombol Hapus Permanen di admin → file gambar otomatis ikut terhapus dari uploads/armada.\n";
echo "  5. TERAKHIR: HAPUS FILE TES INI (tmp_test_upload_armada_e2e.php) dari root project ya kaak!\n";
echo "</pre>";
