<?php
require_once __DIR__ . '/includes/auth_check.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect = BASE_URL . '/admin/armadas.php';

/**
 * Handle upload gambar armada → return path relatif (uploads/armada/xxx.jpg) atau empty string
 * @param array $fileData $_FILES['gambar_file']
 * @return array [ok:bool, path?:string, err?:string]
 */
function handle_upload_armada($fileData){
    if (!$fileData || !isset($fileData['error']) || (int)$fileData['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok'=>false, 'err'=>'no_file'];
    }
    $errCode = (int)$fileData['error'];
    if ($errCode !== UPLOAD_ERR_OK) {
        $mapErr = [
            UPLOAD_ERR_INI_SIZE=>'Ukuran gambar terlalu besar (melebihi batas server PHP)',
            UPLOAD_ERR_FORM_SIZE=>'Ukuran gambar terlalu besar (melebihi batas form)',
            UPLOAD_ERR_PARTIAL=>'Gambar hanya terupload sebagian',
            UPLOAD_ERR_NO_TMP_DIR=>'Server error: folder tmp hilang',
            UPLOAD_ERR_CANT_WRITE=>'Server error: gagal tulis disk',
            UPLOAD_ERR_EXTENSION=>'Upload diblokir ekstensi PHP',
        ];
        return ['ok'=>false, 'err'=>($mapErr[$errCode] ?? 'Upload gagal, kode error '.$errCode)];
    }
    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($fileData['size'] > $maxSize) {
        return ['ok'=>false, 'err'=>'Ukuran gambar maksimal 2MB, yang diupload '.round($fileData['size']/1024/1024,1).'MB'];
    }
    $allowExt = ['jpg','jpeg','png','webp'];
    $allowMime = ['image/jpeg','image/jpg','image/png','image/webp'];
    $nama = $fileData['name'] ?? '';
    $ext = strtolower(pathinfo($nama, PATHINFO_EXTENSION));
    if ($ext === 'jpeg') $ext = 'jpg';
    $mime = $fileData['type'] ?? '';
    if (!in_array($ext, $allowExt, true) || !in_array($mime, $allowMime, true)) {
        return ['ok'=>false, 'err'=>'Format gambar tidak diijinkan, pakai JPG, PNG, atau WEBP saja. Diupload: '.($ext ?: '(unknown)')];
    }
    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
    if ($finfo) {
        $realMime = @finfo_file($finfo, $fileData['tmp_name']);
        finfo_close($finfo);
        if ($realMime && !in_array($realMime, $allowMime, true)) {
            return ['ok'=>false, 'err'=>'Tipe file asli tidak dideteksi sebagai gambar (real mime: '.$realMime.'). Pakai gambar JPG/PNG/WEBP asli.'];
        }
    }
    $targetDir = realpath(__DIR__ . '/../uploads/armada') ?: (__DIR__ . '/../uploads/armada');
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0755, true);
    }
    if (!is_dir($targetDir) || !is_writable($targetDir)) {
        return ['ok'=>false, 'err'=>'Folder uploads/armada tidak bisa ditulis (cek permission folder ya kaak.)'];
    }
    $baseNama = preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($nama, PATHINFO_FILENAME));
    if (!$baseNama) $baseNama = 'armada';
    $baseNama = substr($baseNama, 0, 40);
    $namaBaru = 'armada_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
    $targetPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $namaBaru;
    if (!@move_uploaded_file($fileData['tmp_name'], $targetPath)) {
        return ['ok'=>false, 'err'=>'Gagal memindahkan file gambar ke folder uploads.'];
    }
    @chmod($targetPath, 0644);
    return ['ok'=>true, 'path'=>'uploads/armada/' . $namaBaru];
}

function hapus_file_armada($pathRel){
    if(!$pathRel || trim($pathRel)==='') return false;
    if(stripos($pathRel,'uploads/')!==0 && stripos($pathRel,'/uploads/')===false) return false;
    $abs = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . ltrim(str_replace(['/','\\'], DIRECTORY_SEPARATOR, $pathRel), DIRECTORY_SEPARATOR);
    if(file_exists($abs) && is_file($abs)) return @unlink($abs);
    return false;
}

// ============ ACTIONS POST (tambah, edit, toggle-aktif) ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        set_flash('error', 'Token keamanan kadaluarsa. Silakan coba lagi.');
        redirect($redirect);
    }

    // ============== ACTION: TAMBAH ARMADA ==============
    if ($action === 'tambah') {
        $nama_armada = trim($_POST['nama_armada'] ?? '');
        $icon_fa     = trim($_POST['icon_fa'] ?? 'fa-car-side');
        $kapasitas   = trim($_POST['kapasitas'] ?? 'Kapasitas 5-7 Kursi');
        $deskripsi   = trim($_POST['deskripsi'] ?? '');
        $warna_grad  = trim($_POST['warna_grad'] ?? 'from-primary-600 to-blue-800');
        $gambar_url  = trim($_POST['gambar_url'] ?? '');
        $gambar_lama = trim($_POST['gambar_lama'] ?? '');
        $urutan      = (int)($_POST['urutan'] ?? 99);
        $is_aktif    = isset($_POST['is_aktif']) ? 1 : 0;

        // --- Handle UPLOAD GAMBAR ---
        $up = ['ok'=>false, 'err'=>'no_file'];
        if (isset($_FILES['gambar_file'])) {
            $up = handle_upload_armada($_FILES['gambar_file']);
            if (!$up['ok'] && $up['err'] !== 'no_file') {
                set_flash('error', 'Upload Gambar Gagal: ' . e($up['err']));
                clear_old();
                foreach ($_POST as $k => $v) old($k, $v);
                redirect($redirect);
            }
        }
        // Final path gambar:
        // - Jika ada upload berhasil → pakai path upload
        // - Else, jika gambar_url diisi (mode URL) → pakai gambar_url
        // - Else → empty string (nanti fallback Unsplash pas render)
        if ($up['ok']) {
            $gambarFinal = $up['path'];
        } else {
            $gambarFinal = $gambar_url ?: '';
        }

        $fiturs      = $_POST['fiturs'] ?? [];
        if (!is_array($fiturs)) $fiturs = [];
        $fiturs_clean = [];
        foreach ($fiturs as $f) {
            $x = trim((string)$f);
            if ($x !== '') $fiturs_clean[] = $x;
        }
        $fitur_list_json = json_encode($fiturs_clean, JSON_UNESCAPED_UNICODE);

        $err = [];
        if (strlen($nama_armada) < 3) $err[] = 'Nama armada minimal 3 karakter';
        if (strlen($kapasitas) < 3) $err[] = 'Kapasitas armada minimal 3 karakter';
        if ($urutan < 0) $urutan = 99;

        if (!empty($err)) {
            set_flash('error', implode('<br>', $err));
            clear_old();
            foreach ($_POST as $k => $v) old($k, $v);
            if ($up['ok']) hapus_file_armada($gambarFinal);
            redirect($redirect);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO armadas (nama_armada,icon_fa,kapasitas,deskripsi,warna_grad,gambar_url,fitur_list,urutan,is_aktif,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,1,NOW(),NOW())");
            $stmt->execute([$nama_armada,$icon_fa,$kapasitas,$deskripsi,$warna_grad,$gambarFinal,$fitur_list_json,$urutan]);
            $flashIcon = $up['ok'] ? ' (Gambar berhasil diupload ✔️)' : '';
            set_flash('success', "✅ Armada <b>" . e($nama_armada) . "</b> berhasil ditambahkan!" . $flashIcon);
        } catch (PDOException $e) {
            if ($up['ok']) hapus_file_armada($gambarFinal);
            if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), '1062') !== false) {
                set_flash('error', "Nama armada <b>" . e($nama_armada) . "</b> sudah ada, pakai nama lain ya kaak.");
            } else {
                set_flash('error', 'DB Error: ' . $e->getMessage());
            }
        }
        redirect($redirect);
    }

    // ============== ACTION: EDIT ARMADA ==============
    if ($action === 'edit') {
        $id          = (int)($_POST['id'] ?? 0);
        $nama_armada = trim($_POST['nama_armada'] ?? '');
        $icon_fa     = trim($_POST['icon_fa'] ?? 'fa-car-side');
        $kapasitas   = trim($_POST['kapasitas'] ?? 'Kapasitas 5-7 Kursi');
        $deskripsi   = trim($_POST['deskripsi'] ?? '');
        $warna_grad  = trim($_POST['warna_grad'] ?? 'from-primary-600 to-blue-800');
        $gambar_url  = trim($_POST['gambar_url'] ?? '');
        $gambar_lama = trim($_POST['gambar_lama'] ?? '');
        $urutan      = (int)($_POST['urutan'] ?? 99);
        $is_aktif    = isset($_POST['is_aktif']) ? 1 : 0;

        // --- Handle UPLOAD GAMBAR ---
        $up = ['ok'=>false, 'err'=>'no_file'];
        if (isset($_FILES['gambar_file'])) {
            $up = handle_upload_armada($_FILES['gambar_file']);
            if (!$up['ok'] && $up['err'] !== 'no_file') {
                set_flash('error', 'Upload Gambar Gagal: ' . e($up['err']));
                redirect($redirect);
            }
        }
        // Logic gambar final untuk edit:
        // - Jika upload berhasil → pakai path upload baru
        // - Else jika gambar_url diisi → pakai itu (mode URL)
        // - Else → TETAPKAN gambar_lama (JANGAN dihapus / dikosongkan!)
        $perluHapusLama = false;
        if ($up['ok']) {
            $gambarFinal = $up['path'];
            $perluHapusLama = true;
        } elseif ($gambar_url !== '') {
            $gambarFinal = $gambar_url;
            // Hapus gambar_lama HANYA JIKA gambar_lama ADALAH uploads lokal (uploads/...) DAN berbeda dengan url baru ini)
            if ($gambar_lama !== '' && $gambar_lama !== $gambarFinal &&
                (stripos($gambar_lama, 'uploads/') === 0 || stripos($gambar_lama, '/uploads/') !== false)) {
                $perluHapusLama = true;
            }
        } else {
            // Tanpa upload & tanpa URL → tetap pakai gambar_lama
            $gambarFinal = $gambar_lama;
        }

        $fiturs      = $_POST['fiturs'] ?? [];
        if (!is_array($fiturs)) $fiturs = [];
        $fiturs_clean = [];
        foreach ($fiturs as $f) {
            $x = trim((string)$f);
            if ($x !== '') $fiturs_clean[] = $x;
        }
        $fitur_list_json = json_encode($fiturs_clean, JSON_UNESCAPED_UNICODE);

        $err = [];
        if ($id <= 0) $err[] = 'ID armada tidak valid';
        if (strlen($nama_armada) < 3) $err[] = 'Nama armada minimal 3 karakter';
        if (strlen($kapasitas) < 3) $err[] = 'Kapasitas armada minimal 3 karakter';
        if ($urutan < 0) $urutan = 99;

        if (!empty($err)) {
            if ($up['ok']) hapus_file_armada($gambarFinal);
            set_flash('error', implode('<br>', $err));
            redirect($redirect);
        }

        try {
            $stmt = $pdo->prepare("UPDATE armadas SET nama_armada=?,icon_fa=?,kapasitas=?,deskripsi=?,warna_grad=?,gambar_url=?,fitur_list=?,urutan=?,is_aktif=?,updated_at=NOW() WHERE id=? LIMIT 1");
            $stmt->execute([$nama_armada,$icon_fa,$kapasitas,$deskripsi,$warna_grad,$gambarFinal,$fitur_list_json,$urutan,$is_aktif,$id]);
            if ($stmt->rowCount() > 0) {
                $updIcon = '';
                if ($perluHapusLama && $gambar_lama !== '' && ($up['ok'] || $gambar_url !== '')) {
                    $dihapus = hapus_file_armada($gambar_lama);
                    $updIcon = $up['ok'] ? ' (Gambar diperbarui ✔️)' : ' (Gambar diganti via URL ✔️)';
                } elseif ($up['ok']) {
                    $updIcon = ' (Gambar berhasil diupload ✔️)';
                }
                set_flash('success', "✅ Armada <b>" . e($nama_armada) . "</b> berhasil diperbarui!" . $updIcon);
            } else {
                // Tidak ada perubahan data → hapus file baru kalo keburu upload
                if ($up['ok']) hapus_file_armada($gambarFinal);
                set_flash('warning', "Tidak ada perubahan data pada armada ID #{$id}.");
            }
        } catch (PDOException $e) {
            if ($up['ok']) hapus_file_armada($gambarFinal);
            if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), '1062') !== false) {
                set_flash('error', "Nama armada <b>" . e($nama_armada) . "</b> sudah ada, pakai nama lain ya kaak.");
            } else {
                set_flash('error', 'DB Error: ' . $e->getMessage());
            }
        }
        redirect($redirect);
    }

    // ============== ACTION: TOGGLE AKTIF ==============
    if ($action === 'toggle-aktif') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            set_flash('error', 'ID armada tidak valid.');
            redirect($redirect);
        }
        try {
            $stmt = $pdo->prepare("UPDATE armadas SET is_aktif = IF(is_aktif=1,0,1), updated_at=NOW() WHERE id=? LIMIT 1");
            $stmt->execute([$id]);
            $stmt = $pdo->prepare("SELECT nama_armada, is_aktif FROM armadas WHERE id=? LIMIT 1");
            $stmt->execute([$id]);
            $r = $stmt->fetch();
            if ($r) {
                $label = (int)$r['is_aktif'] === 1 ? 'di-<b class="text-emerald-600">AKTIFKAN</b>' : 'di-<b class="text-slate-500">NONAKTIFKAN</b>';
                set_flash('success', "✅ Armada <b>" . e($r['nama_armada']) . "</b> berhasil {$label}!");
            }
        } catch (PDOException $e) {
            set_flash('error', 'DB Error: ' . $e->getMessage());
        }
        redirect($redirect);
    }
}

// ============ ACTION GET: HAPUS ARMADA ============
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'hapus') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        set_flash('error', 'ID armada tidak valid.');
        redirect($redirect);
    }
    if (!csrf_verify('get', false)) {
        set_flash('error', 'Token keamanan tidak valid. Kembali lalu coba lagi.');
        redirect($redirect);
    }

    try {
        $stmt = $pdo->prepare("SELECT nama_armada, gambar_url FROM armadas WHERE id=? LIMIT 1");
        $stmt->execute([$id]);
        $r = $stmt->fetch();

        $stmt = $pdo->prepare("DELETE FROM armadas WHERE id=? LIMIT 1");
        $ok = $stmt->execute([$id]);
        if ($ok && $stmt->rowCount() > 0) {
            if (!empty($r['gambar_url'])) {
                hapus_file_armada($r['gambar_url']);
            }
            set_flash('success', "🗑️ Armada <b>" . e($r['nama_armada'] ?? "#{$id}") . "</b> berhasil dihapus permanen (gambar ikut dihapus).");
        } else {
            set_flash('warning', "Tidak ada armada dengan ID tersebut (mungkin sudah terhapus).");
        }
    } catch (PDOException $e) {
        set_flash('error', 'DB Error: ' . $e->getMessage());
    }
    redirect($redirect);
}

// Fallback: tanpa action
set_flash('error', 'Aksi tidak dikenali.');
redirect($redirect);