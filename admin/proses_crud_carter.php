<?php
require_once __DIR__ . '/includes/auth_check.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect = BASE_URL . '/admin/carters.php';
ensure_carters_table();

function handle_upload_carter($fileData){
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
    $maxSize = 2 * 1024 * 1024;
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
        return ['ok'=>false, 'err'=>'Format gambar tidak diijinkan, pakai JPG, PNG, atau WEBP saja.'];
    }
    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
    if ($finfo) {
        $realMime = @finfo_file($finfo, $fileData['tmp_name']);
        finfo_close($finfo);
        if ($realMime && !in_array($realMime, $allowMime, true)) {
            return ['ok'=>false, 'err'=>'Tipe file asli tidak dideteksi sebagai gambar. Pakai JPG/PNG/WEBP asli.'];
        }
    }
    $targetDir = realpath(__DIR__ . '/../uploads/carter') ?: (__DIR__ . '/../uploads/carter');
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0755, true);
    }
    if (!is_dir($targetDir) || !is_writable($targetDir)) {
        return ['ok'=>false, 'err'=>'Folder uploads/carter tidak bisa ditulis.'];
    }
    $namaBaru = 'carter_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
    $targetPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $namaBaru;
    if (!@move_uploaded_file($fileData['tmp_name'], $targetPath)) {
        return ['ok'=>false, 'err'=>'Gagal memindahkan file gambar ke folder uploads.'];
    }
    @chmod($targetPath, 0644);
    return ['ok'=>true, 'path'=>'uploads/carter/' . $namaBaru];
}

function hapus_file_carter($pathRel){
    if(!$pathRel || trim($pathRel)==='') return false;
    if(stripos($pathRel,'uploads/')!==0 && stripos($pathRel,'/uploads/')===false) return false;
    $abs = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . ltrim(str_replace(['/','\\'], DIRECTORY_SEPARATOR, $pathRel), DIRECTORY_SEPARATOR);
    if(file_exists($abs) && is_file($abs)) return @unlink($abs);
    return false;
}

function carter_fitur_json(): string
{
    $fiturs = $_POST['fiturs'] ?? [];
    if (!is_array($fiturs)) $fiturs = [];
    $clean = [];
    foreach ($fiturs as $f) {
        $x = trim((string)$f);
        if ($x !== '') $clean[] = $x;
    }
    return json_encode($clean, JSON_UNESCAPED_UNICODE);
}

function carter_simpan_old(): void
{
    $_SESSION['old_input'] = $_POST;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        set_flash('error', 'Token keamanan kadaluarsa. Silakan coba lagi.');
        redirect($redirect);
    }

    if ($action === 'tambah') {
        $nama_unit   = trim($_POST['nama_unit'] ?? '');
        $icon_fa     = trim($_POST['icon_fa'] ?? 'fa-car');
        $kapasitas   = trim($_POST['kapasitas'] ?? '');
        $deskripsi   = trim($_POST['deskripsi'] ?? '');
        $warna_grad  = trim($_POST['warna_grad'] ?? 'from-navy-800 to-navy-950');
        $gambar_url  = trim($_POST['gambar_url'] ?? '');
        $urutan      = (int)($_POST['urutan'] ?? 99);
        $up = ['ok'=>false, 'err'=>'no_file'];
        if (isset($_FILES['gambar_file'])) {
            $up = handle_upload_carter($_FILES['gambar_file']);
            if (!$up['ok'] && $up['err'] !== 'no_file') {
                set_flash('error', 'Upload Gambar Gagal: ' . e($up['err']));
                carter_simpan_old();
                redirect($redirect);
            }
        }
        $gambarFinal = $up['ok'] ? $up['path'] : ($gambar_url ?: '');
        $fitur_list_json = carter_fitur_json();
        $err = [];
        if (strlen($nama_unit) < 3) $err[] = 'Nama unit carter minimal 3 karakter';
        if (strlen($kapasitas) < 2) $err[] = 'Kapasitas minimal 2 karakter';
        if ($urutan < 0) $urutan = 99;
        if (!empty($err)) {
            set_flash('error', implode('<br>', $err));
            carter_simpan_old();
            if ($up['ok']) hapus_file_carter($gambarFinal);
            redirect($redirect);
        }
        try {
            $stmt = $pdo->prepare("INSERT INTO carters (nama_unit,icon_fa,kapasitas,deskripsi,warna_grad,gambar_url,fitur_list,urutan,is_aktif,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,1,NOW(),NOW())");
            $stmt->execute([$nama_unit,$icon_fa,$kapasitas,$deskripsi,$warna_grad,$gambarFinal,$fitur_list_json,$urutan]);
            unset($_SESSION['old_input']);
            set_flash('success', "Paket carter <b>" . e($nama_unit) . "</b> berhasil ditambahkan.");
        } catch (PDOException $e) {
            if ($up['ok']) hapus_file_carter($gambarFinal);
            if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), '1062') !== false) {
                set_flash('error', "Nama unit <b>" . e($nama_unit) . "</b> sudah ada, pakai nama lain.");
            } else {
                set_flash('error', 'DB Error: ' . $e->getMessage());
            }
        }
        redirect($redirect);
    }

    if ($action === 'edit') {
        $id          = (int)($_POST['id'] ?? 0);
        $nama_unit   = trim($_POST['nama_unit'] ?? '');
        $icon_fa     = trim($_POST['icon_fa'] ?? 'fa-car');
        $kapasitas   = trim($_POST['kapasitas'] ?? '');
        $deskripsi   = trim($_POST['deskripsi'] ?? '');
        $warna_grad  = trim($_POST['warna_grad'] ?? 'from-navy-800 to-navy-950');
        $gambar_url  = trim($_POST['gambar_url'] ?? '');
        $gambar_lama = trim($_POST['gambar_lama'] ?? '');
        $urutan      = (int)($_POST['urutan'] ?? 99);
        $is_aktif    = isset($_POST['is_aktif']) ? 1 : 0;
        $up = ['ok'=>false, 'err'=>'no_file'];
        if (isset($_FILES['gambar_file'])) {
            $up = handle_upload_carter($_FILES['gambar_file']);
            if (!$up['ok'] && $up['err'] !== 'no_file') {
                set_flash('error', 'Upload Gambar Gagal: ' . e($up['err']));
                redirect($redirect);
            }
        }
        $perluHapusLama = false;
        if ($up['ok']) {
            $gambarFinal = $up['path'];
            $perluHapusLama = true;
        } elseif ($gambar_url !== '') {
            $gambarFinal = $gambar_url;
            if ($gambar_lama !== '' && $gambar_lama !== $gambarFinal &&
                (stripos($gambar_lama, 'uploads/') === 0 || stripos($gambar_lama, '/uploads/') !== false)) {
                $perluHapusLama = true;
            }
        } else {
            $gambarFinal = $gambar_lama;
        }
        $fitur_list_json = carter_fitur_json();
        $err = [];
        if ($id <= 0) $err[] = 'ID carter tidak valid';
        if (strlen($nama_unit) < 3) $err[] = 'Nama unit carter minimal 3 karakter';
        if (strlen($kapasitas) < 2) $err[] = 'Kapasitas minimal 2 karakter';
        if ($urutan < 0) $urutan = 99;
        if (!empty($err)) {
            if ($up['ok']) hapus_file_carter($gambarFinal);
            set_flash('error', implode('<br>', $err));
            redirect($redirect);
        }
        try {
            $stmt = $pdo->prepare("UPDATE carters SET nama_unit=?,icon_fa=?,kapasitas=?,deskripsi=?,warna_grad=?,gambar_url=?,fitur_list=?,urutan=?,is_aktif=?,updated_at=NOW() WHERE id=? LIMIT 1");
            $stmt->execute([$nama_unit,$icon_fa,$kapasitas,$deskripsi,$warna_grad,$gambarFinal,$fitur_list_json,$urutan,$is_aktif,$id]);
            if ($stmt->rowCount() > 0) {
                if ($perluHapusLama && $gambar_lama !== '') hapus_file_carter($gambar_lama);
                unset($_SESSION['old_input']);
                set_flash('success', "Paket carter <b>" . e($nama_unit) . "</b> berhasil diperbarui.");
            } else {
                if ($up['ok']) hapus_file_carter($gambarFinal);
                set_flash('warning', "Tidak ada perubahan data pada carter ID #{$id}.");
            }
        } catch (PDOException $e) {
            if ($up['ok']) hapus_file_carter($gambarFinal);
            if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), '1062') !== false) {
                set_flash('error', "Nama unit <b>" . e($nama_unit) . "</b> sudah ada, pakai nama lain.");
            } else {
                set_flash('error', 'DB Error: ' . $e->getMessage());
            }
        }
        redirect($redirect);
    }

    if ($action === 'toggle-aktif') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            set_flash('error', 'ID carter tidak valid.');
            redirect($redirect);
        }
        try {
            $stmt = $pdo->prepare("UPDATE carters SET is_aktif = IF(is_aktif=1,0,1), updated_at=NOW() WHERE id=? LIMIT 1");
            $stmt->execute([$id]);
            $stmt = $pdo->prepare("SELECT nama_unit, is_aktif FROM carters WHERE id=? LIMIT 1");
            $stmt->execute([$id]);
            $r = $stmt->fetch();
            if ($r) {
                $label = (int)$r['is_aktif'] === 1 ? 'diaktifkan' : 'dinonaktifkan';
                set_flash('success', "Paket carter <b>" . e($r['nama_unit']) . "</b> berhasil {$label}.");
            }
        } catch (PDOException $e) {
            set_flash('error', 'DB Error: ' . $e->getMessage());
        }
        redirect($redirect);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'hapus') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        set_flash('error', 'ID carter tidak valid.');
        redirect($redirect);
    }
    if (!csrf_verify('get', false)) {
        set_flash('error', 'Token keamanan tidak valid. Kembali lalu coba lagi.');
        redirect($redirect);
    }
    try {
        $stmt = $pdo->prepare("SELECT nama_unit, gambar_url FROM carters WHERE id=? LIMIT 1");
        $stmt->execute([$id]);
        $r = $stmt->fetch();
        $stmt = $pdo->prepare("DELETE FROM carters WHERE id=? LIMIT 1");
        $ok = $stmt->execute([$id]);
        if ($ok && $stmt->rowCount() > 0) {
            if (!empty($r['gambar_url'])) hapus_file_carter($r['gambar_url']);
            set_flash('success', "Paket carter <b>" . e($r['nama_unit'] ?? "#{$id}") . "</b> berhasil dihapus.");
        } else {
            set_flash('warning', "Tidak ada paket carter dengan ID tersebut.");
        }
    } catch (PDOException $e) {
        set_flash('error', 'DB Error: ' . $e->getMessage());
    }
    redirect($redirect);
}

set_flash('error', 'Aksi tidak dikenali.');
redirect($redirect);
