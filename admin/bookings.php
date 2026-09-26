<?php
// =============================================
// ADMIN - DATA BOOKINGS (LIST + FILTER + MODAL TAMBAH & EDIT)
// =============================================
require_once __DIR__ . '/includes/auth_check.php';
ensure_bookings_maps_link_column();
$active_menu = 'bookings';
$page_title  = 'Data Booking';
require_once __DIR__ . '/includes/admin_header.php';

$flash = get_flash();

// === 1. FILTER ===
$f_status    = $_GET['status']    ?? ''; // pending, confirmed, completed, cancelled, ''=semua
$f_tgl_dari  = $_GET['tgl_dari']  ?? '';
$f_tgl_sampai= $_GET['tgl_sampai']?? '';
$f_search    = trim($_GET['q']    ?? '');

$where  = [];
$params = [];
if (!empty($f_status) && in_array($f_status, ['pending','confirmed','completed','cancelled'])) {
    $where[]  = "status = ?";
    $params[] = $f_status;
}
if (!empty($f_tgl_dari)) {
    $where[]  = "tanggal_berangkat >= ?";
    $params[] = $f_tgl_dari;
}
if (!empty($f_tgl_sampai)) {
    $where[]  = "tanggal_berangkat <= ?";
    $params[] = $f_tgl_sampai;
}
if (!empty($f_search)) {
    $like    = "%{$f_search}%";
    // Untuk pencarian kode booking MT-4: strip non digit, biar ketemu by ID
    $searchAngka = preg_replace('/[^0-9]/', '', $f_search);
    if(empty($searchAngka)) $searchAngka = null;

    $where[] = "(
        CAST(id AS CHAR) LIKE ?
        OR nama LIKE ? OR no_hp LIKE ?
        OR rute LIKE ?
        OR lokasi_jemput LIKE ?
        OR jadwal_jemput LIKE ? OR DATE_FORMAT(jam_jemput,'%H:%i') LIKE ?
        OR alamat_jemput LIKE ? OR alamat_tujuan LIKE ?
        OR barang_bawaan LIKE ? OR catatan_admin LIKE ?
        OR status LIKE ? OR source LIKE ?
        " . ($searchAngka ? " OR id = ? " : "") . "
    )";

    $arr = [$like,$like,$like, $like, $like, $like,$like, $like,$like, $like,$like, $like,$like];
    if($searchAngka) $arr[] = (int)$searchAngka;
    $params = array_merge($params, $arr);
}
$sqlWhere = count($where) ? " WHERE " . implode(" AND ", $where) : "";

// Total untuk pagination (ambil 500 terbaru, cukup untuk kebutuhan)
$sqlCount = "SELECT COUNT(*) FROM bookings {$sqlWhere}";
$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$totalData = (int)$stmt->fetchColumn();

// Data
$sqlData = "SELECT * FROM bookings {$sqlWhere} ORDER BY created_at DESC, id DESC LIMIT 500";
$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// === 2. Jika ada ?edit=ID => load data untuk modal edit ===
$editBooking = null;
$autoOpenEdit = false;
if (!empty($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_GET['edit']]);
    $editBooking = $stmt->fetch();
    if ($editBooking) $autoOpenEdit = true;
}
$defaultHarga = get_harga_rute_default();
$listRuteAdmin = get_rutes(false);
// Impor dari WA: AI opsional (OPENAI_API_KEY / AI_API_KEY di .env); tanpa key tetap pakai parser lokal.
$waAiAvailable = defined('OPENAI_API_KEY') && OPENAI_API_KEY !== '';
?>

<?php
$opsiStatus = [
    'pending'   => 'Pending / Menunggu',
    'confirmed' => 'Terkonfirmasi',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
];
$lokOpsis = [
    ['Blora','Kota Blora & sekitarnya','fa-city'],
    ['Surabaya','Kota Surabaya','fa-building'],
    ['Sidoarjo','Kab. Sidoarjo','fa-industry'],
    ['Lainnya','Luar area 3 di atas','fa-map-marked-alt'],
];
?>
<div class="no-print">
    <?php if ($flash) echo '<div class="mb-5">' . $flash . '</div>'; ?>

    <section class="dash-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div class="min-w-0">
                <div class="dash-hero-kicker">Operasional · Booking</div>
                <h1 class="dash-hero-title">Data <em>Booking</em></h1>
                <p class="dash-hero-desc"><?= (int)$totalData ?> pemesanan ditampilkan · online &amp; manual.</p>
            </div>
            <div class="dash-hero-actions">
                <button type="button" onclick="window.modalWaImport.open()" class="is-ghost"><i class="fa-brands fa-whatsapp"></i> Impor dari WA</button>
                <button type="button" onclick="window.modalTambah.open()" class="is-gold"><i class="fa-solid fa-plus"></i> Tambah booking</button>
            </div>
        </div>
    </section>

    <div class="bk-pills">
        <a href="<?= BASE_URL ?>/admin/bookings.php" class="bk-pill <?= $f_status === '' ? 'is-on' : '' ?>">Semua</a>
        <?php
        $pillPendek = ['pending'=>'Pending','confirmed'=>'Konfirmasi','completed'=>'Selesai','cancelled'=>'Batal'];
        foreach ($opsiStatus as $v => $l):
        ?>
            <a href="<?= BASE_URL ?>/admin/bookings.php?status=<?= $v ?>" class="bk-pill <?= $f_status === $v ? 'is-on' : '' ?>"><?= $pillPendek[$v] ?? $l ?></a>
        <?php endforeach; ?>
    </div>

    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <div class="filter-field">
                <label class="form-label">Status</label>
                <select name="status" class="input-field">
                    <option value="">Semua status</option>
                    <?php foreach ($opsiStatus as $v => $l) {
                        $sel = ($f_status === $v) ? 'selected' : '';
                        echo "<option value=\"{$v}\" {$sel}>{$l}</option>";
                    } ?>
                </select>
            </div>
            <div class="filter-field">
                <label class="form-label">Dari</label>
                <input type="date" name="tgl_dari" value="<?= e($f_tgl_dari) ?>" class="input-field">
            </div>
            <div class="filter-field">
                <label class="form-label">Sampai</label>
                <input type="date" name="tgl_sampai" value="<?= e($f_tgl_sampai) ?>" class="input-field">
            </div>
            <div class="filter-field is-grow">
                <label class="form-label">Cari</label>
                <input type="text" name="q" value="<?= e($f_search) ?>" placeholder="Kode, nama, HP, rute..." class="input-field">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
                <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card p-0 overflow-hidden">
    <div class="bk-mobile">
        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fa-solid fa-inbox"></i></div>
                <div class="font-semibold text-slate-600">Tidak ada data booking.</div>
                <div class="text-sm mt-1">Coba ubah filter atau <button type="button" onclick="window.modalTambah.open()" class="text-navy-800 underline font-bold">tambah manual</button>.</div>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $b):
                [$sLabel, $sClass] = status_booking($b['status']);
                $b_json_m = htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8');
            ?>
                <article class="bk-card">
                    <div class="flex items-start justify-between gap-1 mb-1">
                        <div class="min-w-0">
                            <div class="font-extrabold text-navy-900 text-[11px]">#MT-<?= (int)$b['id'] ?></div>
                            <div class="text-[12px] font-bold text-slate-800 mt-0.5 truncate"><?= e($b['nama']) ?></div>
                        </div>
                        <span class="badge <?= $sClass ?> !text-[9px] !px-1.5 !py-0.5 shrink-0"><?= $sLabel ?></span>
                    </div>
                    <div class="text-[10px] text-slate-600 space-y-0.5">
                        <div><?= tgl_id($b['tanggal_berangkat']) ?> · <?= substr($b['jam_jemput'],0,5) ?></div>
                        <div class="font-semibold text-navy-800 truncate"><?= !empty($b['rute']) ? e($b['rute']) : 'Rute custom' ?></div>
                        <div class="font-extrabold text-navy-900"><?= rupiah($b['total_harga']) ?></div>
                        <?php if (!empty($b['maps_link'])): ?>
                            <div>
                                <a href="<?= e($b['maps_link']) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 underline">
                                    <i class="fa-solid fa-map-location-dot"></i> Buka di Maps
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-wrap gap-1 mt-2">
                        <button type="button" onclick="window.modalDetail.open(<?= $b_json_m ?>)" class="btn-icon bg-cream-100 text-navy-800 border-gold-200" title="Detail"><i class="fa-regular fa-eye text-[11px]"></i></button>
                        <button type="button" onclick="window.modalEdit.open(<?= $b_json_m ?>)" class="btn-icon bg-navy-50 text-navy-800 border-navy-200" title="Edit"><i class="fa-solid fa-pen text-[11px]"></i></button>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$b['no_hp']) ?>" target="_blank" rel="noopener" class="btn-icon bg-emerald-50 text-emerald-700 border-emerald-200" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                        <button type="button" data-hapus-id="<?= (int)$b['id'] ?>" class="js-hapus btn-icon bg-red-50 text-red-600 border-red-200" title="Hapus"><i class="fa-solid fa-trash text-[11px]"></i></button>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="bk-desktop table-wrap is-sticky-col !rounded-none !border-0">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Tanggal Brkt</th>
                    <th>Rute</th>
                    <th>Lokasi</th>
                    <th>Jadwal</th>
                    <th>Nama / No.HP</th>
                    <th>Alamat Jemput → Tujuan</th>
                    <th>Kursi</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Src</th>
                    <th class="no-print">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="12">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="fa-solid fa-inbox"></i></div>
                                <div class="font-semibold text-slate-600">Tidak ada data booking.</div>
                                <div class="text-sm mt-1">Coba ubah filter atau <button type="button" onclick="window.modalTambah.open()" class="text-navy-800 underline font-bold">tambah manual</button>.</div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                <?php foreach ($bookings as $b):
                    [$sLabel, $sClass] = status_booking($b['status']);
                    [$srcLabel, $srcClass] = source_booking($b['source']);
                    $b_json = htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8');
                ?>
                    <tr>
                        <td class="font-extrabold text-slate-900 whitespace-nowrap">#MT-<?= $b['id'] ?></td>
                        <td class="whitespace-nowrap">
                            <div class="text-sm font-semibold text-slate-800"><?= tgl_id($b['tanggal_berangkat']) ?></div>
                            <div class="text-xs text-slate-500"><i class="fa-regular fa-clock text-[10px] mr-0.5 text-slate-400"></i><?= substr($b['jam_jemput'],0,5) ?></div>
                            <div class="text-[10px] text-slate-400 mt-1">Input: <?= substr($b['created_at'],0,10) ?></div>
                        </td>
                        <td class="whitespace-nowrap">
                            <?php if (!empty($b['rute'])): ?>
                                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gradient-to-br from-blue-50 to-primary-50 border border-primary-200">
                                    <i class="fa-solid fa-route text-[10px] text-primary-600"></i>
                                    <span class="font-bold text-primary-800 text-[12px]"><?= e(mb_strimwidth($b['rute'], 0, 28, '...')) ?></span>
                                </div>
                                <?php if (!empty($b['harga_rute_saat_booking'])): ?>
                                    <div class="text-[10px] text-slate-400 mt-1 font-semibold pl-1">@ <?= rupiah($b['harga_rute_saat_booking']) ?>/org</div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-slate-50 text-slate-500 border-slate-200"><i class="fa-solid fa-pen mr-1 text-[10px]"></i>Custom</span>
                            <?php endif; ?>
                        </td>
                        <!-- LOKASI JEMPUT BARU -->
                        <td>
                            <?php
                                $lok2 = $b['lokasi_jemput'] ?? 'Blora';
                                $isBlora2 = $lok2 === 'Blora';
                                $lokClass2 = $isBlora2
                                    ? 'bg-blue-50 border-blue-200 text-blue-700'
                                    : 'bg-emerald-50 border-emerald-200 text-emerald-700';
                                $lokIcon2 = $isBlora2 ? 'fa-city' : 'fa-plane-departure';
                            ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border text-[11px] font-black <?= $lokClass2 ?>">
                                <i class="fa-solid <?= $lokIcon2 ?> text-[9px]"></i>
                                <?= e($lok2) ?>
                            </span>
                        </td>
                        <!-- JADWAL JEMPUT BARU -->
                        <td>
                            <?php if (!empty($b['jadwal_jemput'])): ?>
                                <div class="text-[11px] font-bold text-slate-800 leading-snug max-w-[220px]">
                                    <i class="fa-regular fa-clock text-[10px] text-amber-600 mr-0.5"></i>
                                    <?= e(mb_strimwidth($b['jadwal_jemput'], 0, 65, '...')) ?>
                                </div>
                            <?php else: ?>
                                <div class="text-[10px] text-slate-400 italic">
                                    (jadwal lama · <?= substr($b['jam_jemput'],0,5) ?>)
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="font-bold text-slate-900 text-sm"><?= e($b['nama']) ?></div>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$b['no_hp']) ?>" target="_blank" rel="noopener"
                               class="text-xs text-green-600 hover:text-green-700 flex items-center gap-1 mt-0.5 font-semibold">
                                <i class="fa-brands fa-whatsapp"></i><?= e($b['no_hp']) ?>
                            </a>
                            <?php if($b['catatan_admin']): ?>
                                <div class="mt-1 text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-2 py-1 inline-block">
                                    <i class="fa-regular fa-note-sticky mr-1 text-[10px] text-amber-600"></i><?= e(mb_strimwidth($b['catatan_admin'],0,30,'...')) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-xs leading-snug text-slate-700 max-w-[260px]">
                            <div class="mb-1.5 flex gap-1.5 items-start">
                                <i class="fa-solid fa-circle-dot text-primary-500 text-[8px] mt-1.5 flex-shrink-0"></i>
                                <span><?= e(mb_strimwidth($b['alamat_jemput'],0,60,'...')) ?></span>
                            </div>
                            <?php if (!empty($b['maps_link'])): ?>
                                <div class="mb-1.5 pl-3">
                                    <a href="<?= e($b['maps_link']) ?>" target="_blank" rel="noopener"
                                       class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 hover:text-emerald-800 underline">
                                        <i class="fa-solid fa-map-location-dot text-[10px]"></i> Buka di Maps
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div class="flex gap-1.5 items-start">
                                <i class="fa-solid fa-location-dot text-red-500 text-[10px] mt-1 flex-shrink-0"></i>
                                <span><?= e(mb_strimwidth($b['alamat_tujuan'],0,60,'...')) ?></span>
                            </div>
                            <?php if($b['barang_bawaan']): ?>
                                <div class="mt-1.5 text-[10px] text-slate-500"><i class="fa-solid fa-suitcase-rolling mr-1 text-[9px] text-slate-400"></i><?= e(mb_strimwidth($b['barang_bawaan'],0,40,'...')) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center font-bold text-slate-800"><?= $b['jumlah_kursi'] ?></td>
                        <td class="font-extrabold text-primary-700 whitespace-nowrap"><?= rupiah($b['total_harga']) ?></td>
                        <td><span class="badge <?= $sClass ?> whitespace-nowrap"><?= $sLabel ?></span></td>
                        <td><span class="badge <?= $srcClass ?>"><?= $srcLabel ?></span></td>
                        <td class="no-print whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <button type="button" onclick="window.modalDetail.open(<?= $b_json ?>)" class="btn-icon bg-cream-100 text-navy-800 border-gold-200" title="Detail"><i class="fa-regular fa-eye text-[12px]"></i></button>
                                <button type="button" onclick="window.modalEdit.open(<?= $b_json ?>)" class="btn-icon bg-navy-50 text-navy-800 border-navy-200" title="Edit"><i class="fa-solid fa-pen"></i></button>
                                <button type="button" onclick="window.modalHapus.open(<?= (int)$b['id'] ?>)" class="btn-icon bg-red-50 text-red-600 border-red-200" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===========================================
     MODAL IMPOR DARI WA (teks / screenshot → preview → simpan)
     Tanpa OPENAI_API_KEY: parser lokal. Screenshot butuh AI vision.
     =========================================== -->
<div x-data="modalWaImportData()" x-init="window.modalWaImport = $data">
    <template x-if="show">
        <div class="admin-modal-overlay" x-transition.opacity>
            <div @click="close()" class="absolute inset-0"></div>
            <div class="admin-modal wa-import-modal w-full max-w-full sm:max-w-3xl" @click.stop>
                <div class="admin-modal-head">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="admin-modal-ico"><i class="fa-brands fa-whatsapp"></i></div>
                        <div class="min-w-0">
                            <h3>Impor dari WhatsApp</h3>
                            <p x-text="step === 1 ? 'Tempel chat atau upload screenshot' : 'Periksa data, lalu simpan ke pemesanan'"></p>
                        </div>
                    </div>
                    <button type="button" @click="close()" class="admin-modal-close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <!-- STEP 1: input -->
                <div class="admin-modal-body" x-show="step === 1">
                    <label class="form-label">Teks chat WhatsApp *</label>
                    <textarea class="input-field wa-import-ta" rows="8" x-model="chatText"
                              placeholder="Contoh:&#10;Nama: Budi Santoso&#10;HP: 081234567890&#10;Jemput: Jl. Pemuda No.12, Blora&#10;Tujuan: Jl. Ahmad Yani, Surabaya&#10;Rute: Blora - Surabaya&#10;Tanggal: 28/09/2026&#10;Kursi: 2&#10;Jam: 08.00&#10;Barang: 1 koper"></textarea>

                    <div class="mt-4" x-show="aiAvailable">
                        <label class="form-label">Screenshot chat <span class="text-slate-400 font-normal text-xs">(opsional, butuh Vision API)</span></label>
                        <label class="wa-import-file">
                            <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" @change="onFile($event)">
                            <i class="fa-solid fa-image"></i>
                            <span x-text="fileName || 'Pilih gambar JPG / PNG'"></span>
                        </label>
                        <button type="button" class="text-[11px] font-bold text-red-600 mt-1.5 underline" x-show="fileName" @click="clearFile()">Hapus gambar</button>
                    </div>

                    <div class="wa-import-err" x-show="error" x-text="error"></div>
                </div>

                <!-- STEP 2: preview form (sama field dengan tambah booking) -->
                <form method="POST" action="<?= BASE_URL ?>/admin/proses_tambah_booking.php"
                      class="flex-1 min-h-0 flex flex-col" x-show="step === 2" @submit="saving = true">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <div class="admin-modal-body grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2 wa-import-banner is-preview">
                            <i class="fa-solid fa-circle-check"></i>
                            <div class="text-[11px] leading-relaxed" x-text="infoMsg"></div>
                        </div>

                        <div class="admin-modal-section">Penumpang</div>
                        <div>
                            <label class="form-label">Nama Lengkap *</label>
                            <input name="nama" required type="text" class="input-field" x-model="form.nama">
                        </div>
                        <div>
                            <label class="form-label">No. HP / WA *</label>
                            <input name="no_hp" required type="tel" class="input-field" x-model="form.no_hp">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Alamat Penjemputan *</label>
                            <textarea name="alamat_jemput" required rows="2" class="input-field resize-none" x-model="form.alamat_jemput"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Pin Maps <span class="text-slate-400 font-normal text-xs">(opsional)</span></label>
                            <input name="maps_link" type="url" class="input-field" x-model="form.maps_link"
                                   placeholder="https://maps.app.goo.gl/... atau link Google Maps">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Alamat Tujuan *</label>
                            <textarea name="alamat_tujuan" required rows="2" class="input-field resize-none" x-model="form.alamat_tujuan"></textarea>
                        </div>

                        <div class="admin-modal-section">Rute &amp; keberangkatan</div>
                        <div class="md:col-span-2">
                            <label class="form-label">Pilih rute perjalanan</label>
                            <select name="id_rute" class="input-field !py-3 font-bold text-slate-800"
                                    x-model.number="form.id_rute" @change="hitungTotalFromRute">
                                <option value="0">— Tanpa Rute (Custom / Harga Khusus) —</option>
                                <?php foreach ($listRuteAdmin as $rRA):
                                    $isAktif = (int)($rRA['is_aktif'] ?? 1) === 1;
                                    $labelAktif = $isAktif ? '' : '  [Nonaktif]';
                                ?>
                                    <option value="<?= (int)($rRA['id'] ?? 0) ?>"
                                            data-harga="<?= (int)$rRA['harga'] ?>"
                                            data-nama="<?= e($rRA['nama_rute']) ?>">
                                        🛣️ <?= e($rRA['nama_rute']) ?> · <?= rupiah($rRA['harga']) ?><?= $labelAktif ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="text-[11px] text-slate-500 mt-1" x-show="form.rute">
                                Deteksi rute: <b class="text-navy-800" x-text="form.rute"></b>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Jumlah Kursi *</label>
                            <input name="jumlah_kursi" required type="number" min="1" max="50" class="input-field"
                                   x-model.number="form.jumlah_kursi" @input="hitungTotalFromRute">
                        </div>
                        <div>
                            <label class="form-label">Tanggal Berangkat *</label>
                            <input name="tanggal_berangkat" required type="date" class="input-field" x-model="form.tanggal_berangkat">
                        </div>
                        <div>
                            <label class="form-label">Jam Jemput *</label>
                            <input name="jam_jemput" required type="time" class="input-field" x-model="form.jam_jemput">
                        </div>
                        <div>
                            <label class="form-label">Status *</label>
                            <select name="status" class="input-field" x-model="form.status">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Terkonfirmasi</option>
                                <option value="completed">Selesai</option>
                                <option value="cancelled">Dibatalkan</option>
                            </select>
                        </div>

                        <div class="admin-modal-section">Lokasi &amp; jadwal</div>
                        <div class="md:col-span-2">
                            <label class="form-label">Lokasi area penjemputan *</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5">
                                <?php foreach ($lokOpsis as [$lokVal,$lokDesc,$lokIco]): ?>
                                    <label>
                                        <input type="radio" name="lokasi_jemput" value="<?= $lokVal ?>" x-model="form.lokasi_jemput" class="peer sr-only">
                                        <span class="bk-loc">
                                            <span class="flex items-start gap-2">
                                                <i class="fa-solid <?= $lokIco ?> text-gold-600 mt-0.5 text-xs"></i>
                                                <span>
                                                    <span class="block text-xs font-extrabold text-navy-900"><?= $lokVal ?></span>
                                                    <span class="block text-[10px] text-slate-500 mt-0.5"><?= $lokDesc ?></span>
                                                </span>
                                            </span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label flex items-center gap-1.5">
                                <i class="fa-regular fa-clock text-amber-600"></i> Jadwal Keberangkatan *
                            </label>
                            <select name="jadwal_jemput" x-model="form.jadwal_jemput" required class="input-field !py-3 font-semibold">
                                <option value="">— Pilih jadwal sesuai lokasi penjemputan —</option>
                                <optgroup x-show="form.lokasi_jemput === 'Blora'" label="Blora">
                                    <option value="Jam 08.00 — Kota-kota Penjemputan area Blora">08.00 · Kota-kota Penjemputan</option>
                                    <option value="Jam 11.00 — (KHUSUS!) Door to Door SEMUA KECAMATAN BLORA (Unit Hiace)">11.00 · Door to Door SEMUA KECAMATAN BLORA</option>
                                    <option value="Jam 20.00 — Kota-kota Penjemputan area Blora">20.00 · Kota-kota Penjemputan</option>
                                </optgroup>
                                <optgroup x-show="(form.lokasi_jemput === 'Surabaya' || form.lokasi_jemput === 'Sidoarjo')" label="Surabaya / Sidoarjo">
                                    <option value="Jam 10.00 — Start dari Bandara Juanda (Surabaya)">10.00 · Start dari Bandara Juanda</option>
                                    <option value="Jam 15.00 — Start dari Bandara Juanda (Surabaya)">15.00 · Start dari Bandara Juanda</option>
                                    <option value="Jam 20.00 — (KHUSUS!) Start dari Sidoarjo · Door to Door SEMUA KECAMATAN Sidoarjo + Surabaya/Gresik">20.00 · Door to Door KHUSUS</option>
                                </optgroup>
                                <optgroup x-show="form.lokasi_jemput === 'Lainnya'" label="Lainnya">
                                    <option value="Jam 06.00 — Jadwal Khusus Lokasi Lainnya">06.00 · Jadwal Khusus</option>
                                    <option value="Jam 08.00 — Jadwal Khusus Lokasi Lainnya">08.00 · Jadwal Khusus</option>
                                    <option value="Jam 12.00 — Jadwal Khusus Lokasi Lainnya">12.00 · Jadwal Khusus</option>
                                    <option value="Jam 20.00 — Jadwal Khusus Lokasi Lainnya">20.00 · Jadwal Khusus</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="admin-modal-section">Pembayaran &amp; catatan</div>
                        <div class="md:col-span-2">
                            <label class="form-label">Barang bawaan</label>
                            <input name="barang_bawaan" type="text" class="input-field" x-model="form.barang_bawaan">
                        </div>
                        <div>
                            <label class="form-label">Total harga (Rp) *</label>
                            <input name="total_harga" required type="number" min="0" class="input-field" x-model.number="form.total_harga">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Catatan Admin</label>
                            <textarea name="catatan_admin" rows="2" class="input-field resize-none" x-model="form.catatan_admin"></textarea>
                        </div>
                    </div>
                    <div class="admin-modal-foot">
                        <button type="button" @click="step = 1" class="btn-secondary" :disabled="saving">
                            <i class="fa-solid fa-arrow-left"></i> Ubah chat
                        </button>
                        <button type="submit" class="btn-success" :disabled="saving">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span x-text="saving ? 'Menyimpan…' : 'Simpan ke pemesanan'"></span>
                        </button>
                    </div>
                </form>

                <div class="admin-modal-foot" x-show="step === 1">
                    <button type="button" @click="close()" class="btn-secondary">Batal</button>
                    <button type="button" class="btn-primary" @click="extract()" :disabled="loading">
                        <i class="fa-solid" :class="loading ? 'fa-spinner fa-spin' : 'fa-wand-magic-sparkles'"></i>
                        <span x-text="loading ? 'Mengekstrak…' : 'Isi otomatis dari chat'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

<!-- ===========================================
     MODAL TAMBAH BOOKING MANUAL (Alpine.js)
     =========================================== -->
<div x-data="modalTambahData()" x-init="window.modalTambah = $data">
    <template x-if="show">
        <div class="admin-modal-overlay" x-transition.opacity>
            <div @click="close()" class="absolute inset-0"></div>
            <div class="admin-modal w-full max-w-full sm:max-w-3xl" @click.stop>
                <div class="admin-modal-head">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="admin-modal-ico"><i class="fa-solid fa-plus"></i></div>
                        <div class="min-w-0">
                            <h3>Tambah booking manual</h3>
                            <p>Input dari WhatsApp / telepon</p>
                        </div>
                    </div>
                    <button type="button" @click="close()" class="admin-modal-close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form method="POST" action="<?= BASE_URL ?>/admin/proses_tambah_booking.php" class="flex-1 min-h-0 flex flex-col">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <div class="admin-modal-body grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="admin-modal-section">Penumpang</div>
                        <div>
                            <label class="form-label">Nama Lengkap *</label>
                            <input name="nama" required type="text" class="input-field" x-model="form.nama">
                        </div>
                        <div>
                            <label class="form-label">No. HP / WA *</label>
                            <input name="no_hp" required type="tel" class="input-field" x-model="form.no_hp">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Alamat Penjemputan *</label>
                            <textarea name="alamat_jemput" required rows="2" class="input-field resize-none" x-model="form.alamat_jemput"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">
                                Pin Maps
                                <span class="text-slate-400 font-normal text-xs ml-1">(opsional)</span>
                            </label>
                            <input name="maps_link" type="url" class="input-field" x-model="form.maps_link"
                                   placeholder="https://www.google.com/maps?q=... atau link share Maps">
                            <div x-show="form.maps_link" class="mt-1.5">
                                <a :href="form.maps_link" target="_blank" rel="noopener" class="text-xs font-bold text-emerald-700 underline">
                                    <i class="fa-solid fa-external-link mr-1"></i>Buka di Maps
                                </a>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Alamat Tujuan *</label>
                            <textarea name="alamat_tujuan" required rows="2" class="input-field resize-none" x-model="form.alamat_tujuan"></textarea>
                        </div>
                        <div class="admin-modal-section">Rute &amp; keberangkatan</div>
                        <div class="md:col-span-2">
                            <label class="form-label">
                                Pilih rute perjalanan
                                <span class="text-slate-400 font-normal text-xs ml-1">(harga otomatis, bisa diubah)</span>
                            </label>
                            <select name="id_rute" class="input-field !py-3 font-bold text-slate-800"
                                    x-model.number="form.id_rute" @change="hitungTotalFromRute">
                                <option value="0">— Tanpa Rute (Custom / Harga Khusus) —</option>
                                <?php foreach ($listRuteAdmin as $rRA):
                                    $isAktif = (int)($rRA['is_aktif'] ?? 1) === 1;
                                    $labelAktif = $isAktif ? '' : '  [Nonaktif]';
                                ?>
                                    <option value="<?= (int)($rRA['id'] ?? 0) ?>"
                                            data-harga="<?= (int)$rRA['harga'] ?>"
                                            data-nama="<?= e($rRA['nama_rute']) ?>">
                                        🛣️ <?= e($rRA['nama_rute']) ?> · <?= rupiah($rRA['harga']) ?><?= $labelAktif ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Jumlah Kursi *</label>
                            <input name="jumlah_kursi" required type="number" min="1" max="50" class="input-field"
                                   x-model.number="form.jumlah_kursi" @input="hitungTotalFromRute">
                        </div>
                        <div>
                            <label class="form-label">Tanggal Berangkat *</label>
                            <input name="tanggal_berangkat" required type="date" class="input-field"
                                   x-model="form.tanggal_berangkat" :value="form.tanggal_berangkat">
                        </div>
                        <div>
                            <label class="form-label">Jam Jemput *</label>
                            <input name="jam_jemput" required type="time" class="input-field" x-model="form.jam_jemput">
                        </div>
                        <div>
                            <label class="form-label">Status *</label>
                            <select name="status" class="input-field" x-model="form.status">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Terkonfirmasi</option>
                                <option value="completed">Selesai</option>
                                <option value="cancelled">Dibatalkan</option>
                            </select>
                        </div>
                        <div class="admin-modal-section">Lokasi &amp; jadwal</div>
                        <div class="md:col-span-2">
                            <label class="form-label">Lokasi area penjemputan *</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5">
                                <?php foreach ($lokOpsis as [$lokVal,$lokDesc,$lokIco]): ?>
                                    <label>
                                        <input type="radio" name="lokasi_jemput" value="<?= $lokVal ?>" x-model="form.lokasi_jemput" class="peer sr-only">
                                        <span class="bk-loc">
                                            <span class="flex items-start gap-2">
                                                <i class="fa-solid <?= $lokIco ?> text-gold-600 mt-0.5 text-xs"></i>
                                                <span>
                                                    <span class="block text-xs font-extrabold text-navy-900"><?= $lokVal ?></span>
                                                    <span class="block text-[10px] text-slate-500 mt-0.5"><?= $lokDesc ?></span>
                                                </span>
                                            </span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- JADWAL JEMPUT (TAMBAHAN BUG FIX + DINAMIS Alpine) -->
                        <div class="md:col-span-2">
                            <label class="form-label flex items-center gap-1.5">
                                <i class="fa-regular fa-clock text-amber-600"></i> Jadwal Keberangkatan *
                            </label>
                            <select name="jadwal_jemput" x-model="form.jadwal_jemput" required class="input-field !py-3 font-semibold">
                                <option value="">— Pilih jadwal sesuai lokasi penjemputan —</option>
                                <optgroup x-show="form.lokasi_jemput === 'Blora'" label="Blora (Jam Penjemputan)">
                                    <option value="Jam 08.00 — Kota-kota Penjemputan area Blora">08.00 · Kota-kota Penjemputan</option>
                                    <option value="Jam 11.00 — (KHUSUS!) Door to Door SEMUA KECAMATAN BLORA (Unit Hiace)">11.00 · Door to Door SEMUA KECAMATAN BLORA (KHUSUS!)</option>
                                    <option value="Jam 20.00 — Kota-kota Penjemputan area Blora">20.00 · Kota-kota Penjemputan</option>
                                </optgroup>
                                <optgroup x-show="(form.lokasi_jemput === 'Surabaya' || form.lokasi_jemput === 'Sidoarjo')" label="Surabaya / Sidoarjo (Start Keberangkatan)">
                                    <option value="Jam 10.00 — Start dari Bandara Juanda (Surabaya)">10.00 · Start dari Bandara Juanda</option>
                                    <option value="Jam 15.00 — Start dari Bandara Juanda (Surabaya)">15.00 · Start dari Bandara Juanda</option>
                                    <option value="Jam 20.00 — (KHUSUS!) Start dari Sidoarjo · Door to Door SEMUA KECAMATAN Sidoarjo + Surabaya/Gresik">20.00 · Door to Door KHUSUS Sidoarjo/SBY/Gresik</option>
                                </optgroup>
                                <optgroup x-show="form.lokasi_jemput === 'Lainnya'" label="Lokasi Lainnya (jadwal bebas)">
                                    <option value="Jam 06.00 — Jadwal Khusus Lokasi Lainnya">06.00 · Jadwal Khusus</option>
                                    <option value="Jam 08.00 — Jadwal Khusus Lokasi Lainnya">08.00 · Jadwal Khusus</option>
                                    <option value="Jam 12.00 — Jadwal Khusus Lokasi Lainnya">12.00 · Jadwal Khusus</option>
                                    <option value="Jam 20.00 — Jadwal Khusus Lokasi Lainnya">20.00 · Jadwal Khusus</option>
                                </optgroup>
                            </select>
                            <div x-show="form.lokasi_jemput === 'Blora' && form.jadwal_jemput.includes('11.00')" x-transition class="mt-2 p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-[11px] text-amber-800 leading-relaxed font-semibold">
                                ⚡ <b>KHUSUS 11.00 Blora:</b> Door to Door ke <b>SEMUA KECAMATAN BLORA</b> pakai Unit Hiace (tidak perlu ke terminal)!
                            </div>
                            <div x-show="(form.lokasi_jemput === 'Surabaya' || form.lokasi_jemput === 'Sidoarjo') && form.jadwal_jemput.includes('20.00')" x-transition class="mt-2 p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-[11px] text-emerald-800 leading-relaxed font-semibold">
                                ⚡ <b>KHUSUS 20.00 SBY/SDA:</b> Door to Door ke <b>SEMUA KECAMATAN Sidoarjo + Surabaya/Gresik</b>!
                            </div>
                        </div>
                        <div class="admin-modal-section">Pembayaran &amp; catatan</div>
                        <div class="md:col-span-2">
                            <label class="form-label">Barang bawaan</label>
                            <input name="barang_bawaan" type="text" class="input-field" x-model="form.barang_bawaan"
                                   placeholder="Misal: 1 koper, 2 kardus">
                        </div>
                        <div>
                            <label class="form-label">
                                Total harga (Rp) *
                                <span class="text-slate-400 font-normal text-xs ml-1">boleh diedit</span>
                            </label>
                            <input name="total_harga" required type="number" min="0" class="input-field" x-model.number="form.total_harga">
                            <div class="text-[11px] text-slate-500 mt-1">
                                <span class="text-green-600 font-semibold">Preview: </span>
                                <span x-text="'Rp ' + Number(form.total_harga||0).toLocaleString('id-ID')">Rp 0</span>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Catatan Admin</label>
                            <textarea name="catatan_admin" rows="2" class="input-field resize-none" x-model="form.catatan_admin"
                                      placeholder="Misal: Lunas transfer, dp 50%, dll"></textarea>
                        </div>
                    </div>
                    <!-- Footer -->
                    <div class="admin-modal-foot">
                        <button type="button" @click="close()" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-success">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<!-- ===========================================
     MODAL EDIT BOOKING (Alpine.js)
     =========================================== -->
<div x-data="modalEditData()" x-init="window.modalEdit = $data">
    <template x-if="show">
        <div class="admin-modal-overlay" x-transition.opacity>
            <div @click="close()" class="absolute inset-0"></div>
            <div class="admin-modal w-full max-w-full sm:max-w-3xl" @click.stop>
                <div class="admin-modal-head">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="admin-modal-ico"><i class="fa-solid fa-pen"></i></div>
                        <div class="min-w-0">
                            <h3>Edit booking <span x-text="'#MT-' + form.id">#MT-0</span></h3>
                            <p>Ubah data, status, atau catatan pembayaran</p>
                        </div>
                    </div>
                    <button type="button" @click="close()" class="admin-modal-close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form method="POST" action="<?= BASE_URL ?>/admin/proses_edit_booking.php" class="flex-1 min-h-0 flex flex-col">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id" x-model.number="form.id">
                    <div class="admin-modal-body grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="admin-modal-section">Penumpang</div>
                        <div>
                            <label class="form-label">Nama Lengkap *</label>
                            <input name="nama" required type="text" class="input-field" x-model="form.nama">
                        </div>
                        <div>
                            <label class="form-label">No. HP / WA *</label>
                            <input name="no_hp" required type="tel" class="input-field" x-model="form.no_hp">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Alamat Penjemputan *</label>
                            <textarea name="alamat_jemput" required rows="2" class="input-field resize-none" x-model="form.alamat_jemput"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">
                                Pin Maps
                                <span class="text-slate-400 font-normal text-xs ml-1">(opsional)</span>
                            </label>
                            <input name="maps_link" type="url" class="input-field" x-model="form.maps_link"
                                   placeholder="https://www.google.com/maps?q=... atau link share Maps">
                            <div x-show="form.maps_link" class="mt-1.5">
                                <a :href="form.maps_link" target="_blank" rel="noopener" class="text-xs font-bold text-emerald-700 underline">
                                    <i class="fa-solid fa-external-link mr-1"></i>Buka di Maps
                                </a>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Alamat Tujuan *</label>
                            <textarea name="alamat_tujuan" required rows="2" class="input-field resize-none" x-model="form.alamat_tujuan"></textarea>
                        </div>
                        <div class="admin-modal-section">Rute &amp; keberangkatan</div>
                        <div class="md:col-span-2">
                            <label class="form-label">
                                Pilih rute perjalanan
                                <span class="text-slate-400 font-normal text-xs ml-1">(harga otomatis, bisa diubah)</span>
                            </label>
                            <select name="id_rute" class="input-field !py-3 font-bold text-slate-800"
                                    x-model.number="form.id_rute" @change="hitungTotalFromRute">
                                <option value="0">— Tanpa Rute (Custom / Harga Khusus) —</option>
                                <?php foreach ($listRuteAdmin as $rRA):
                                    $isAktif = (int)($rRA['is_aktif'] ?? 1) === 1;
                                    $labelAktif = $isAktif ? '' : '  [Nonaktif]';
                                ?>
                                    <option value="<?= (int)($rRA['id'] ?? 0) ?>"
                                            data-harga="<?= (int)$rRA['harga'] ?>"
                                            data-nama="<?= e($rRA['nama_rute']) ?>">
                                        🛣️ <?= e($rRA['nama_rute']) ?> · <?= rupiah($rRA['harga']) ?><?= $labelAktif ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Jumlah Kursi *</label>
                            <input name="jumlah_kursi" required type="number" min="1" max="50" class="input-field"
                                   x-model.number="form.jumlah_kursi" @input="hitungTotalFromRute">
                        </div>
                        <div>
                            <label class="form-label">Tanggal Berangkat *</label>
                            <input name="tanggal_berangkat" required type="date" class="input-field" x-model="form.tanggal_berangkat">
                        </div>
                        <div>
                            <label class="form-label">Jam Jemput *</label>
                            <input name="jam_jemput" required type="time" class="input-field" x-model="form.jam_jemput">
                        </div>
                        <div class="admin-modal-section">Status &amp; sumber</div>
                        <div>
                            <label class="form-label">Status *</label>
                            <select name="status" class="input-field" x-model="form.status">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Terkonfirmasi</option>
                                <option value="completed">Selesai</option>
                                <option value="cancelled">Dibatalkan</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Source</label>
                            <select name="source" class="input-field" x-model="form.source">
                                <option value="online">Online (Website)</option>
                                <option value="manual">Manual (WA/Telp)</option>
                            </select>
                        </div>
                        <div class="admin-modal-section">Lokasi &amp; jadwal</div>
                        <div class="md:col-span-2">
                            <label class="form-label">Lokasi area penjemputan *</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5">
                                <?php foreach ($lokOpsis as [$lokVal,$lokDesc,$lokIco]): ?>
                                    <label>
                                        <input type="radio" name="lokasi_jemput" value="<?= $lokVal ?>" x-model="form.lokasi_jemput" class="peer sr-only">
                                        <span class="bk-loc">
                                            <span class="flex items-start gap-2">
                                                <i class="fa-solid <?= $lokIco ?> text-gold-600 mt-0.5 text-xs"></i>
                                                <span>
                                                    <span class="block text-xs font-extrabold text-navy-900"><?= $lokVal ?></span>
                                                    <span class="block text-[10px] text-slate-500 mt-0.5"><?= $lokDesc ?></span>
                                                </span>
                                            </span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- JADWAL JEMPUT (MODAL EDIT BUG FIX + DINAMIS!) -->
                        <div class="md:col-span-2">
                            <label class="form-label flex items-center gap-1.5">
                                <i class="fa-regular fa-clock text-amber-600"></i> Jadwal Keberangkatan *
                            </label>
                            <select name="jadwal_jemput" x-model="form.jadwal_jemput" required class="input-field !py-3 font-semibold">
                                <option value="">— Pilih jadwal sesuai lokasi penjemputan —</option>
                                <optgroup x-show="form.lokasi_jemput === 'Blora'" label="📍 Blora (Jam Penjemputan)">
                                    <option value="Jam 08.00 — Kota-kota Penjemputan area Blora">🕗 08.00 · Kota-kota Penjemputan</option>
                                    <option value="Jam 11.00 — (KHUSUS!) Door to Door SEMUA KECAMATAN BLORA (Unit Hiace)">🕚 11.00 · Door to Door SEMUA KECAMATAN BLORA (KHUSUS!)</option>
                                    <option value="Jam 20.00 — Kota-kota Penjemputan area Blora">🕗 20.00 · Kota-kota Penjemputan</option>
                                </optgroup>
                                <optgroup x-show="(form.lokasi_jemput === 'Surabaya' || form.lokasi_jemput === 'Sidoarjo')" label="✈️ Surabaya / Sidoarjo (Start)">
                                    <option value="Jam 10.00 — Start dari Bandara Juanda (Surabaya)">🕙 10.00 · Start dari Bandara Juanda</option>
                                    <option value="Jam 15.00 — Start dari Bandara Juanda (Surabaya)">🕞 15.00 · Start dari Bandara Juanda</option>
                                    <option value="Jam 20.00 — (KHUSUS!) Start dari Sidoarjo · Door to Door SEMUA KECAMATAN Sidoarjo + Surabaya/Gresik">🕗 20.00 · Door to Door KHUSUS Sidoarjo/SBY/Gresik</option>
                                </optgroup>
                                <optgroup x-show="form.lokasi_jemput === 'Lainnya'" label="📍 Lokasi Lainnya (jadwal bebas)">
                                    <option value="Jam 06.00 — Jadwal Khusus Lokasi Lainnya">🕕 06.00 · Jadwal Khusus</option>
                                    <option value="Jam 08.00 — Jadwal Khusus Lokasi Lainnya">🕗 08.00 · Jadwal Khusus</option>
                                    <option value="Jam 12.00 — Jadwal Khusus Lokasi Lainnya">🕛 12.00 · Jadwal Khusus</option>
                                    <option value="Jam 20.00 — Jadwal Khusus Lokasi Lainnya">🕗 20.00 · Jadwal Khusus</option>
                                </optgroup>
                            </select>
                            <div x-show="form.lokasi_jemput === 'Blora' && form.jadwal_jemput.includes('11.00')" x-transition class="mt-2 p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-[11px] text-amber-800 leading-relaxed font-semibold">
                                <i class="fa-solid fa-info-circle text-amber-600 mr-1"></i><b>KHUSUS 11.00 Blora:</b> Door to Door ke <b>SEMUA KECAMATAN BLORA</b> pakai Unit Hiace!
                            </div>
                            <div x-show="(form.lokasi_jemput === 'Surabaya' || form.lokasi_jemput === 'Sidoarjo') && form.jadwal_jemput.includes('20.00')" x-transition class="mt-2 p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-[11px] text-emerald-800 leading-relaxed font-semibold">
                                <i class="fa-solid fa-info-circle text-emerald-600 mr-1"></i><b>KHUSUS 20.00 SBY/SDA:</b> Door to Door ke <b>SEMUA KECAMATAN Sidoarjo + Surabaya/Gresik</b>!
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Barang Bawaan</label>
                            <input name="barang_bawaan" type="text" class="input-field" x-model="form.barang_bawaan">
                        </div>
                        <div>
                            <label class="form-label">
                                Total Harga (Rp) *
                                <span class="text-slate-400 font-normal text-xs ml-1">bisa di-edit manual</span>
                            </label>
                            <input name="total_harga" required type="number" min="0" class="input-field" x-model.number="form.total_harga">
                            <div class="text-[11px] text-slate-500 mt-1">
                                <span class="text-green-600 font-semibold">Preview: </span>
                                <span x-text="'Rp ' + Number(form.total_harga||0).toLocaleString('id-ID')">Rp 0</span>
                            </div>
                        </div>
                        <div class="admin-modal-section">Pembayaran &amp; catatan</div>
                        <div class="md:col-span-2">
                            <label class="form-label">Catatan admin / pembayaran</label>
                            <textarea name="catatan_admin" rows="2" class="input-field resize-none" x-model="form.catatan_admin" placeholder="Misal: Lunas transfer, DP 50%"></textarea>
                        </div>
                    </div>
                    <!-- Footer -->
                    <div class="admin-modal-foot !justify-between">
                        <div class="text-xs text-slate-500 font-medium">
                            Dibuat: <span x-text="form.created_at">-</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="close()" class="btn-secondary">Batal</button>
                            <button type="submit" class="btn-primary">
                                <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<div x-data="modalDetailData()" x-init="window.modalDetail = $data">
    <template x-if="show">
        <div class="admin-modal-overlay" x-transition.opacity>
            <div @click="close()" class="absolute inset-0"></div>
            <div class="admin-modal w-full max-w-lg" @click.stop>
                <div class="admin-modal-head">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="admin-modal-ico"><i class="fa-regular fa-eye"></i></div>
                        <div class="min-w-0">
                            <h3>Detail <span x-text="'#MT-' + (b.id || 0)"></span></h3>
                            <p>Ringkasan booking — edit untuk mengubah status</p>
                        </div>
                    </div>
                    <button type="button" @click="close()" class="admin-modal-close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="admin-modal-body space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><span class="text-slate-500">Penumpang</span><span class="font-bold text-navy-900 text-right" x-text="b.nama"></span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-500">No. HP</span><span class="font-semibold text-right" x-text="b.no_hp"></span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-500">Rute</span><span class="font-semibold text-right" x-text="b.rute || 'Custom'"></span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-500">Berangkat</span><span class="font-semibold text-right" x-text="(b.tanggal_berangkat || '') + ' · ' + (b.jam_jemput || '')"></span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-500">Lokasi</span><span class="font-semibold text-right" x-text="b.lokasi_jemput || 'Blora'"></span></div>
                    <div x-show="b.maps_link" class="flex justify-between gap-3 items-start">
                        <span class="text-slate-500 shrink-0">Pin Maps</span>
                        <a :href="b.maps_link" target="_blank" rel="noopener" class="font-bold text-emerald-700 underline text-right text-xs break-all">
                            <i class="fa-solid fa-map-location-dot mr-1"></i>Buka di Maps
                        </a>
                    </div>
                    <div class="flex justify-between gap-3"><span class="text-slate-500">Kursi</span><span class="font-semibold" x-text="b.jumlah_kursi"></span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-500">Total</span><span class="font-extrabold text-navy-900" x-text="'Rp ' + Number(b.total_harga||0).toLocaleString('id-ID')"></span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-500">Status</span><span class="font-bold capitalize" x-text="b.status"></span></div>
                    <div x-show="b.catatan_admin" class="p-3 rounded-xl bg-gold-50 border border-gold-200 text-xs text-amber-900">
                        <div class="font-bold mb-1">Catatan / pembayaran</div>
                        <div x-text="b.catatan_admin"></div>
                    </div>
                </div>
                <div class="admin-modal-foot !justify-between">
                    <button type="button" @click="close()" class="btn-secondary">Tutup</button>
                    <div class="flex gap-2">
                        <a :href="'https://wa.me/' + String(b.no_hp||'').replace(/[^0-9]/g,'')" target="_blank" rel="noopener" class="btn-success !min-h-[44px]"><i class="fa-brands fa-whatsapp"></i> WA</a>
                        <button type="button" @click="editFromDetail()" class="btn-primary">Edit</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<div x-data="modalHapusData()" x-init="window.modalHapus = $data">
    <template x-if="show">
        <div class="admin-modal-overlay" x-transition.opacity>
            <div @click="close()" class="absolute inset-0"></div>
            <div class="admin-modal w-full max-w-md" @click.stop>
                <div class="admin-modal-head">
                    <div class="flex items-center gap-3">
                        <div class="admin-modal-ico" style="background:#7F1D1D;color:#FECACA"><i class="fa-solid fa-trash"></i></div>
                        <div>
                            <h3>Hapus booking?</h3>
                            <p>Tindakan ini tidak bisa dibatalkan</p>
                        </div>
                    </div>
                    <button type="button" @click="close()" class="admin-modal-close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="admin-modal-body text-sm text-slate-600">
                    Yakin hapus <b class="text-navy-900" x-text="'#MT-' + id"></b>? Data booking akan dihapus permanen.
                </div>
                <div class="admin-modal-foot">
                    <button type="button" @click="close()" class="btn-secondary">Batal</button>
                    <a :href="hapusUrl" class="btn-danger">Ya, hapus</a>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function modalDetailData(){
    return {
        show: false,
        b: {},
        open(row){ this.b = row || {}; this.show = true; },
        close(){ this.show = false; },
        editFromDetail(){
            this.show = false;
            if (window.modalEdit) window.modalEdit.open(this.b);
        }
    };
}
function modalHapusData(){
    return {
        show: false,
        id: 0,
        hapusUrl: '',
        open(id){
            this.id = parseInt(id) || 0;
            this.hapusUrl = '<?= BASE_URL ?>/admin/proses_hapus_booking.php?id=' + this.id + '&csrf=<?= csrf_token() ?>';
            this.show = true;
        },
        close(){ this.show = false; }
    };
}
// ==== Modal Impor dari WA ====
function modalWaImportData(){
    const emptyForm = () => ({
        nama:'', no_hp:'', alamat_jemput:'', alamat_tujuan:'',
        id_rute: 0, rute:'',
        jumlah_kursi:1, tanggal_berangkat: '<?= date('Y-m-d') ?>',
        jam_jemput:'06:00', status:'confirmed', barang_bawaan:'',
        total_harga: <?= (int)$defaultHarga ?>, catatan_admin:'',
        lokasi_jemput:'Blora', jadwal_jemput:'', maps_link:''
    });
    return {
        show: false,
        step: 1,
        loading: false,
        saving: false,
        error: '',
        infoMsg: '',
        chatText: '',
        file: null,
        fileName: '',
        aiAvailable: <?= $waAiAvailable ? 'true' : 'false' ?>,
        defaultHarga: <?= (int)$defaultHarga ?>,
        form: emptyForm(),
        open(){
            this.step = 1;
            this.loading = false;
            this.saving = false;
            this.error = '';
            this.infoMsg = '';
            this.chatText = '';
            this.clearFile();
            this.form = emptyForm();
            this.show = true;
        },
        close(){ this.show = false; },
        onFile(ev){
            const f = ev.target.files && ev.target.files[0] ? ev.target.files[0] : null;
            this.file = f;
            this.fileName = f ? f.name : '';
            this.error = '';
        },
        clearFile(){
            this.file = null;
            this.fileName = '';
        },
        async extract(){
            this.error = '';
            const text = (this.chatText || '').trim();
            if (!text && !this.file) {
                this.error = 'Tempel teks chat WA dulu' + (this.aiAvailable ? ', atau pilih screenshot.' : '.');
                return;
            }
            if (!text && this.file && !this.aiAvailable) {
                this.error = 'Screenshot butuh API key. Tempel teks chat saja.';
                return;
            }
            this.loading = true;
            try {
                const fd = new FormData();
                fd.append('csrf_token', '<?= csrf_token() ?>');
                fd.append('chat_text', text);
                if (this.file) fd.append('screenshot', this.file);
                const res = await fetch('<?= BASE_URL ?>/admin/proses_parse_wa.php', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });
                const data = await res.json();
                if (!data || !data.ok) {
                    this.error = (data && data.message) ? data.message : 'Gagal mengekstrak data.';
                    return;
                }
                const f = data.fields || {};
                this.form = Object.assign(emptyForm(), f, {
                    id_rute: parseInt(f.id_rute) || 0,
                    jumlah_kursi: Math.max(1, parseInt(f.jumlah_kursi) || 1),
                    total_harga: parseInt(f.total_harga) || this.defaultHarga,
                    status: f.status || 'confirmed',
                    lokasi_jemput: f.lokasi_jemput || 'Blora',
                });
                this.infoMsg = data.message || 'Periksa data sebelum simpan.';
                this.step = 2;
                this.$nextTick(() => this.hitungTotalFromRute());
            } catch (e) {
                this.error = 'Koneksi gagal. Coba lagi atau isi manual.';
            } finally {
                this.loading = false;
            }
        },
        hitungTotalFromRute(){
            try {
                const rootEl = this.$root;
                const sel = rootEl ? rootEl.querySelector('select[name="id_rute"]') : null;
                let hargaPerOrg = this.defaultHarga;
                if (sel && sel.selectedOptions.length > 0) {
                    const h = parseInt(sel.selectedOptions[0].dataset.harga);
                    if (h > 0) hargaPerOrg = h;
                }
                const jml = Math.max(1, parseInt(this.form.jumlah_kursi) || 1);
                this.form.total_harga = jml * hargaPerOrg;
            } catch (e) {}
        }
    };
}
// ==== Alpine.js Data: Modal Tambah ====
function modalTambahData(){
    return {
        show: false,
        defaultHarga: <?= (int)$defaultHarga ?>,
        form: {
            nama:'', no_hp:'', alamat_jemput:'', alamat_tujuan:'',
            id_rute: 0,
            jumlah_kursi:1, tanggal_berangkat: '<?= date('Y-m-d') ?>',
            jam_jemput:'06:00', status:'confirmed', barang_bawaan:'',
            total_harga: <?= (int)$defaultHarga ?>, catatan_admin:'',
            lokasi_jemput:'Blora', jadwal_jemput:'', maps_link:''
        },
        open(){
            this.form = {
                nama:'', no_hp:'', alamat_jemput:'', alamat_tujuan:'',
                id_rute: 0,
                jumlah_kursi:1, tanggal_berangkat: '<?= date('Y-m-d') ?>',
                jam_jemput:'06:00', status:'confirmed', barang_bawaan:'',
                total_harga: this.defaultHarga, catatan_admin:'',
                lokasi_jemput:'Blora', jadwal_jemput:'', maps_link:''
            };
            this.show = true;
        },
        close(){ this.show = false; },
        hitungTotal(){
            const jml = Math.max(1, parseInt(this.form.jumlah_kursi) || 1);
            this.form.total_harga = jml * this.defaultHarga;
        },
        hitungTotalFromRute(){
            try {
                // Cari option yang terpilih dari dropdown dengan id_rute terpilih
                const rootEl = this.$root;
                const sel = rootEl ? rootEl.querySelector('select[name="id_rute"]') : null;
                let hargaPerOrg = this.defaultHarga;
                if (sel && sel.selectedOptions.length > 0) {
                    const opt = sel.selectedOptions[0];
                    const h = parseInt(opt.dataset.harga);
                    if (h > 0) hargaPerOrg = h;
                }
                const jml = Math.max(1, parseInt(this.form.jumlah_kursi) || 1);
                this.form.total_harga = jml * hargaPerOrg;
            } catch(e){
                this.hitungTotal();
            }
        }
    }
}

// ==== Alpine.js Data: Modal Edit ====
function modalEditData(){
    const db = <?= $editBooking ? json_encode($editBooking) : 'null' ?>;
    return {
        show: <?= $autoOpenEdit ? 'true' : 'false' ?>,
        defaultHarga: <?= (int)$defaultHarga ?>,
        form: <?= $editBooking ? json_encode(array_merge([
            'id_rute' => 0,
            'rute' => '',
            'harga_rute_saat_booking' => 0,
            'lokasi_jemput' => 'Blora',
            'jadwal_jemput' => '',
            'maps_link' => '',
        ], $editBooking)) : '{id:0,nama:"",no_hp:"",alamat_jemput:"",alamat_tujuan:"",id_rute:0,jumlah_kursi:1,tanggal_berangkat:"'.date('Y-m-d').'",jam_jemput:"06:00",status:"pending",barang_bawaan:"",total_harga:'.$defaultHarga.',catatan_admin:"",source:"manual",created_at:"",rute:"",harga_rute_saat_booking:0,lokasi_jemput:"Blora",jadwal_jemput:"",maps_link:""}' ?>,
        open(b){
            this.form = Object.assign({
                catatan_admin:'', barang_bawaan:'', jam_jemput:'06:00',
                id_rute: 0, rute:'', harga_rute_saat_booking: 0,
                lokasi_jemput:'Blora', jadwal_jemput:'', maps_link:'',
            }, b || {});
            if (typeof this.form.jam_jemput === 'string' && this.form.jam_jemput.length === 8) {
                this.form.jam_jemput = this.form.jam_jemput.substring(0,5);
            }
            // Pastikan id_rute integer
            this.form.id_rute = parseInt(this.form.id_rute) || 0;
            // Pastikan lokasi_jemput fallback ke Blora kalo kosong
            if (!this.form.lokasi_jemput || this.form.lokasi_jemput === '') this.form.lokasi_jemput = 'Blora';
            if (!this.form.jadwal_jemput) this.form.jadwal_jemput = '';
            if (!this.form.maps_link) this.form.maps_link = '';
            this.show = true;
        },
        close(){ this.show = false; window.history.replaceState(null,'','<?= BASE_URL ?>/admin/bookings.php'); },
        hitungTotal(){
            const jml = Math.max(1, parseInt(this.form.jumlah_kursi) || 1);
            if (!this.form.total_harga) this.form.total_harga = jml * this.defaultHarga;
        },
        hitungTotalFromRute(){
            try {
                const rootEl = this.$root;
                const sel = rootEl ? rootEl.querySelector('select[name="id_rute"]') : null;
                let hargaPerOrg = 0;
                if (sel && sel.selectedOptions.length > 0) {
                    const opt = sel.selectedOptions[0];
                    hargaPerOrg = parseInt(opt.dataset.harga) || 0;
                }
                if (hargaPerOrg <= 0 && this.form.harga_rute_saat_booking > 0) {
                    hargaPerOrg = parseInt(this.form.harga_rute_saat_booking);
                }
                if (hargaPerOrg <= 0) hargaPerOrg = this.defaultHarga;
                const jml = Math.max(1, parseInt(this.form.jumlah_kursi) || 1);
                this.form.total_harga = jml * hargaPerOrg;
            } catch(e){
                this.hitungTotal();
            }
        }
    }
}
</script>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
