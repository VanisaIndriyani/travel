<?php
// =============================================
// PROCESS BOOKING ONLINE - dari form di index.php
// =============================================
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

// Hanya terima method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/booking.php');
}

// === 1. Cek CSRF Token ===
if (!csrf_verify()) {
    set_flash('error', 'Token keamanan kadaluarsa, silakan refresh halaman dan coba lagi.');
    redirect(BASE_URL . '/booking.php');
}

// === 2. Ambil & Sanitasi Input ===
$_SESSION['old'] = $_POST; // simpan old input dulu

$nama             = trim($_POST['nama']             ?? '');
$no_hp            = trim($_POST['no_hp']            ?? '');
$alamat_jemput    = trim($_POST['alamat_jemput']    ?? '');
$alamat_tujuan    = trim($_POST['alamat_tujuan']    ?? '');
$jumlah_kursi     = (int)($_POST['jumlah_kursi']    ?? 0);
$tanggal_berangkat= trim($_POST['tanggal_berangkat']?? '');
$jam_jemput       = '00:00:00';
$barang_bawaan    = trim($_POST['barang_bawaan']    ?? '');
$id_rute_post     = (int)($_POST['id_rute']         ?? 0);

$lokasi_jemput_post = trim($_POST['lokasi_jemput'] ?? 'Blora');
$lokasi_jemput    = in_array($lokasi_jemput_post, ['Blora', 'Surabaya']) ? $lokasi_jemput_post : 'Blora';
$jadwal_jemput    = trim($_POST['jadwal_jemput']   ?? '');

// === 3. Validasi ===
$errors = [];

if (strlen($nama) < 2)                      $errors[] = 'Nama lengkap minimal 2 karakter.';
if (!preg_match('/^[0-9+]+$/', $no_hp) || strlen($no_hp) < 10 || strlen($no_hp) > 16)
    $errors[] = 'No.HP/WA format tidak valid (minimal 10 angka).';
if (strlen($alamat_jemput) < 5)             $errors[] = 'Alamat penjemputan wajib diisi dengan lengkap.';
if (strlen($alamat_tujuan) < 3)             $errors[] = 'Alamat tujuan wajib diisi.';
if ($jumlah_kursi < 1)                      $errors[] = 'Jumlah kursi minimal 1 orang.';
if ($id_rute_post <= 0)                     $errors[] = 'Silakan pilih Rute Perjalanan terlebih dahulu ya kaak.';
if (!DateTime::createFromFormat('Y-m-d', $tanggal_berangkat))
    $errors[] = 'Format tanggal berangkat tidak valid.';
else {
    $tgl = new DateTime($tanggal_berangkat);
    $today = new DateTime('today');
    if ($tgl < $today) $errors[] = 'Tanggal berangkat tidak boleh hari kemarin.';
}
if (strlen($jadwal_jemput) < 10)             $errors[] = 'Silakan pilih Jadwal Penjemputan terlebih dahulu.';

// Jika ada error, abort
if (!empty($errors)) {
    set_flash('error', implode('<br>', $errors));
    redirect(BASE_URL . '/booking.php');
}

// === 3b. Dapatkan data RUTE dipilih (jika ID valid) & simpan harga SAAT BOOKING ===
$id_rute_final     = null;
$nama_rute_final   = '';
$harga_rute_final  = 0;

$ruteDipilih = get_rute_by_id($id_rute_post);
if ($ruteDipilih) {
    $id_rute_final    = (int)$ruteDipilih['id'];
    $nama_rute_final  = $ruteDipilih['nama_rute'];
    $harga_rute_final = (int)$ruteDipilih['harga'];
}
// Backward compat: jika id tidak ketemu tapi admin custom rute, coba cari dari alamat tujuan
if ($harga_rute_final <= 0) {
    $cariHarga = cari_harga_rute_by_nama($alamat_tujuan);
    if ($cariHarga > 0) $harga_rute_final = $cariHarga;
}
// Last resort fallback ke default HARGA_TIKET_CONST
if ($harga_rute_final <= 0) $harga_rute_final = HARGA_TIKET_DEFAULT;
if (empty($nama_rute_final)) $nama_rute_final = 'Rute Custom';

// === 4. Hitung Total Harga (sesuai RUTE DIPILIH, bukan default lagi!) ===
$total_harga = $jumlah_kursi * $harga_rute_final;
if ($total_harga <= 0) $total_harga = $jumlah_kursi * HARGA_TIKET_DEFAULT;

// === 5. Insert ke Database (tambah kolom rute + lokasi & jadwal jemput!) ===
try {
    $sql = "INSERT INTO bookings
            (nama, no_hp, alamat_jemput, alamat_tujuan, jumlah_kursi, tanggal_berangkat,
             jam_jemput, barang_bawaan, lokasi_jemput, jadwal_jemput, total_harga, status, source,
             id_rute, rute, harga_rute_saat_booking)
            VALUES
            (?,?,?,?,?,?,?,?,?,?,?,?, 'online',
             ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        $nama, $no_hp, $alamat_jemput, $alamat_tujuan, $jumlah_kursi,
        $tanggal_berangkat, $jam_jemput, $barang_bawaan, $lokasi_jemput, $jadwal_jemput,
        $total_harga, 'pending',
        $id_rute_final, $nama_rute_final, $harga_rute_final
    ]);
    $booking_id = $pdo->lastInsertId();
} catch (PDOException $e) {
    set_flash('error', 'Database error: ' . $e->getMessage());
    redirect(BASE_URL . '/booking.php');
}

if (!$ok || empty($booking_id)) {
    set_flash('error', 'Gagal menyimpan booking. Silakan coba lagi.');
    redirect(BASE_URL . '/booking.php');
}

// === 6. Berhasil! ===
clear_old();
$wa_nomor = preg_replace('/[^0-9]/', '', WA_ADMIN);
$wa_pesan = rawurlencode(
    "Halo Admin Mustika Travel,\n\n".
    "Saya baru saja melakukan booking online dengan data berikut:\n".
    "--------------------------------\n".
    "*Nama:* {$nama}\n".
    "*No.HP:* {$no_hp}\n".
    "*Rute Perjalanan:* {$nama_rute_final}\n".
    "*Harga Tiket:* ".rupiah($harga_rute_final)." / org\n".
    "*Lokasi Jemput:* {$lokasi_jemput}\n".
    "*Jadwal Penjemputan:* {$jadwal_jemput}\n".
    "*Alamat Jemput:* {$alamat_jemput}\n".
    "*Alamat Tujuan:* {$alamat_tujuan}\n".
    "*Jumlah Kursi:* {$jumlah_kursi} orang\n".
    "*Tanggal:* ".tgl_id($tanggal_berangkat)."\n".
    "*Total Harga:* ".rupiah($total_harga)."\n".
    "--------------------------------\n".
    "Mohon dikonfirmasi ya, terima kasih 🙏"
);

$pesan = '
<div class="space-y-3">
    <div class="font-bold text-lg">🎉 Booking Berhasil!</div>
    <div class="text-sm">Terima kasih, booking Anda dengan kode <strong class="text-primary-700">#MT-'. $booking_id .'</strong> telah tercatat. Silakan hubungi admin WhatsApp untuk konfirmasi & pembayaran DP.</div>
    <div class="font-semibold text-sm space-y-1.5 bg-gradient-to-br from-primary-50 to-amber-50 border border-primary-200 rounded-xl p-4">
        <div class="flex justify-between gap-3"><span class="text-slate-500">Rute:</span><span class="font-black text-primary-800">'. e($nama_rute_final) .'</span></div>
        <div class="flex justify-between gap-3"><span class="text-slate-500">📌 Lokasi:</span><span class="font-bold text-slate-800">'. e($lokasi_jemput) .'</span></div>
        <div class="flex flex-col gap-0.5"><span class="text-slate-500">⏰ Jadwal Jemput:</span><span class="font-bold text-[12px] text-amber-700 leading-tight">'. e($jadwal_jemput) .'</span></div>
        <div class="border-t border-amber-200/60 my-1"></div>
        <div class="flex justify-between gap-3"><span class="text-slate-500">Harga:</span><span class="font-bold text-slate-800">'. rupiah($harga_rute_final) .' / orang</span></div>
        <div class="flex justify-between gap-3"><span class="text-slate-500">Total Bayar:</span><span class="font-black text-xl text-amber-700">'. rupiah($total_harga) .'</span></div>
        <div class="text-[11px] text-slate-400 pt-1">('. $jumlah_kursi .' kursi x '. rupiah($harga_rute_final) .'/org)</div>
    </div>
    <a href="https://wa.me/'. $wa_nomor .'?text='. $wa_pesan .'" target="_blank" rel="noopener"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-green-500 hover:bg-green-600 text-white font-bold text-sm transition shadow-md">
        <i class="fa-brands fa-whatsapp text-lg"></i>
        Konfirmasi via WA Admin ('. WA_ADMIN_DISPLAY .')
    </a>
</div>';
set_flash('success', $pesan);
redirect(BASE_URL . '/booking.php');
