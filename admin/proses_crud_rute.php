<?php
require_once __DIR__ . '/includes/auth_check.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect = BASE_URL . '/admin/rutes.php';

// ============ ACTIONS POST (tambah, edit, toggle-aktif) ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        set_flash('error', 'Token keamanan kadaluarsa. Silakan coba lagi.');
        redirect($redirect);
    }

    // ============== ACTION: TAMBAH RUTE ==============
    if ($action === 'tambah') {
        $nama_rute    = trim($_POST['nama_rute'] ?? '');
        $harga        = (int)($_POST['harga'] ?? 0);
        $urutan       = (int)($_POST['urutan'] ?? 99);
        $is_aktif     = isset($_POST['is_aktif']) ? 1 : 0;

        $err = [];
        if (strlen($nama_rute) < 3) $err[] = 'Nama rute minimal 3 karakter';
        if ($harga <= 0) $err[] = 'Harga rute wajib diisi (lebih dari 0)';
        if ($urutan < 0) $urutan = 99;

        if (!empty($err)) {
            set_flash('error', implode('<br>', $err));
            clear_old();
            foreach ($_POST as $k => $v) old($k, $v);
            redirect($redirect);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO rutes (nama_rute, harga, urutan, is_aktif, created_at, updated_at) VALUES (?,?,?,?, NOW(), NOW())");
            $stmt->execute([$nama_rute, $harga, $urutan, $is_aktif]);
            set_flash('success', "✅ Rute <b>" . e($nama_rute) . "</b> berhasil ditambahkan!");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), '1062') !== false) {
                set_flash('error', "Nama rute <b>" . e($nama_rute) . "</b> sudah ada, pakai nama lain ya kaak.");
            } else {
                set_flash('error', 'DB Error: ' . $e->getMessage());
            }
        }
        redirect($redirect);
    }

    // ============== ACTION: EDIT RUTE ==============
    if ($action === 'edit') {
        $id           = (int)($_POST['id'] ?? 0);
        $nama_rute    = trim($_POST['nama_rute'] ?? '');
        $harga        = (int)($_POST['harga'] ?? 0);
        $urutan       = (int)($_POST['urutan'] ?? 99);
        $is_aktif     = isset($_POST['is_aktif']) ? 1 : 0;

        $err = [];
        if ($id <= 0) $err[] = 'ID rute tidak valid';
        if (strlen($nama_rute) < 3) $err[] = 'Nama rute minimal 3 karakter';
        if ($harga <= 0) $err[] = 'Harga rute wajib diisi';
        if ($urutan < 0) $urutan = 99;

        if (!empty($err)) {
            set_flash('error', implode('<br>', $err));
            redirect($redirect);
        }

        try {
            $stmt = $pdo->prepare("UPDATE rutes SET nama_rute=?, harga=?, urutan=?, is_aktif=?, updated_at=NOW() WHERE id=? LIMIT 1");
            $stmt->execute([$nama_rute, $harga, $urutan, $is_aktif, $id]);
            if ($stmt->rowCount() > 0) {
                set_flash('success', "✅ Rute <b>" . e($nama_rute) . "</b> berhasil diperbarui!");
            } else {
                set_flash('warning', "Tidak ada perubahan data pada rute ID #{$id}.");
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), '1062') !== false) {
                set_flash('error', "Nama rute <b>" . e($nama_rute) . "</b> sudah ada, pakai nama lain ya kaak.");
            } else {
                set_flash('error', 'DB Error: ' . $e->getMessage());
            }
        }
        redirect($redirect);
    }

    // ============== ACTION: TOGGLE AKTIF (from link GET via POST form) ==============
    if ($action === 'toggle-aktif') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            set_flash('error', 'ID rute tidak valid.');
            redirect($redirect);
        }
        try {
            $stmt = $pdo->prepare("UPDATE rutes SET is_aktif = IF(is_aktif=1,0,1), updated_at=NOW() WHERE id=? LIMIT 1");
            $stmt->execute([$id]);
            $stmt = $pdo->prepare("SELECT nama_rute, is_aktif FROM rutes WHERE id=? LIMIT 1");
            $stmt->execute([$id]);
            $r = $stmt->fetch();
            if ($r) {
                $label = (int)$r['is_aktif'] === 1 ? 'di-<b class="text-emerald-600">AKTIFKAN</b>' : 'di-<b class="text-slate-500">NONAKTIFKAN</b>';
                set_flash('success', "✅ Rute <b>" . e($r['nama_rute']) . "</b> berhasil {$label}!");
            }
        } catch (PDOException $e) {
            set_flash('error', 'DB Error: ' . $e->getMessage());
        }
        redirect($redirect);
    }
}

// ============ ACTION GET: HAPUS RUTE (dengan konfirmasi Alpine) ============
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'hapus') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        set_flash('error', 'ID rute tidak valid.');
        redirect($redirect);
    }
    if (!csrf_verify('get', false)) {
        set_flash('error', 'Token keamanan tidak valid. Kembali lalu coba lagi.');
        redirect($redirect);
    }

    try {
        $stmt = $pdo->prepare("SELECT nama_rute FROM rutes WHERE id=? LIMIT 1");
        $stmt->execute([$id]);
        $r = $stmt->fetch();

        $stmt = $pdo->prepare("DELETE FROM rutes WHERE id=? LIMIT 1");
        $ok = $stmt->execute([$id]);
        if ($ok && $stmt->rowCount() > 0) {
            set_flash('success', "🗑️ Rute <b>" . e($r['nama_rute'] ?? "#{$id}") . "</b> berhasil dihapus permanen.");
        } else {
            set_flash('warning', "Tidak ada rute dengan ID tersebut (mungkin sudah terhapus).");
        }
    } catch (PDOException $e) {
        set_flash('error', 'DB Error: ' . $e->getMessage());
    }
    redirect($redirect);
}

// Fallback: tanpa action
set_flash('error', 'Aksi tidak dikenali.');
redirect($redirect);
