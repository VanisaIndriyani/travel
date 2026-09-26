<?php
// =============================================
// ADMIN - LAPORAN (HARIAN / MINGGUAN / BULANAN)
// =============================================
require_once __DIR__ . '/includes/auth_check.php';
$active_menu = 'laporan';
$page_title  = 'Laporan Keuangan';
require_once __DIR__ . '/includes/admin_header.php';

$flash = get_flash();
$csrf = csrf_token();

// Default tab
$tab = $_GET['tab'] ?? 'harian';
if (!in_array($tab, ['harian','mingguan','bulanan'])) $tab = 'harian';

$today = date('Y-m-d');

// ==================================================================
// TAB 1: LAPORAN HARIAN
// ==================================================================
$lHari_tanggal = $_GET['lhari_tgl'] ?? $today;
if (!DateTime::createFromFormat('Y-m-d', $lHari_tanggal)) $lHari_tanggal = $today;

$lHari_data = [];
$lHari_totalBooking = 0;
$lHari_totalPendapatan = 0;
try {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE status = 'completed' AND tanggal_berangkat = ? ORDER BY jam_jemput ASC");
    $stmt->execute([$lHari_tanggal]);
    $lHari_data = $stmt->fetchAll();
    foreach ($lHari_data as $b) {
        $lHari_totalBooking++;
        $lHari_totalPendapatan += (int)$b['total_harga'];
    }
} catch (PDOException $e) { /* abaikan */ }

// ==================================================================
// TAB 2: LAPORAN MINGGUAN
// ==================================================================
$lMing_tanggal = $_GET['lming_tgl'] ?? $today;
if (!DateTime::createFromFormat('Y-m-d', $lMing_tanggal)) $lMing_tanggal = $today;

// Hitung hari Senin dari minggu tsb
$dt = new DateTime($lMing_tanggal);
$dayOfWeek = (int)$dt->format('N'); // 1 = Senin, 7 = Minggu
$monday = clone $dt;
$monday->modify('-'. ($dayOfWeek - 1) .' days');
$sunday = clone $monday;
$sunday->modify('+6 days');
$mingguStart = $monday->format('Y-m-d');
$mingguEnd   = $sunday->format('Y-m-d');

// Ringkasan per hari
$lMing_perHari = [];
$namaHari = ['','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
for ($i = 1; $i <= 7; $i++) {
    $tgl = clone $monday;
    $tgl->modify('+'. ($i-1) .' days');
    $key = $tgl->format('Y-m-d');
    $lMing_perHari[$key] = [
        'tanggal' => $key,
        'nama_hari' => $namaHari[$i],
        'jml_booking' => 0,
        'total' => 0,
        'detail' => []
    ];
}

try {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE status = 'completed' AND tanggal_berangkat BETWEEN ? AND ? ORDER BY tanggal_berangkat ASC, jam_jemput ASC");
    $stmt->execute([$mingguStart, $mingguEnd]);
    while ($b = $stmt->fetch()) {
        $t = $b['tanggal_berangkat'];
        if (isset($lMing_perHari[$t])) {
            $lMing_perHari[$t]['jml_booking']++;
            $lMing_perHari[$t]['total'] += (int)$b['total_harga'];
            $lMing_perHari[$t]['detail'][] = $b;
        }
    }
} catch (PDOException $e) {}

$lMing_totalBooking    = array_sum(array_column($lMing_perHari, 'jml_booking'));
$lMing_totalPendapatan = array_sum(array_column($lMing_perHari, 'total'));

// ==================================================================
// TAB 3: LAPORAN BULANAN
// ==================================================================
$lBul_bulan = (int)($_GET['lbul_bulan'] ?? date('n'));
$lBul_tahun = (int)($_GET['lbul_tahun'] ?? date('Y'));
if ($lBul_bulan < 1 || $lBul_bulan > 12) $lBul_bulan = (int)date('n');
if ($lBul_tahun < 2020 || $lBul_tahun > 2040) $lBul_tahun = (int)date('Y');

$bulanStart = sprintf('%04d-%02d-01', $lBul_tahun, $lBul_bulan);
$bulanEnd   = date('Y-m-t', strtotime($bulanStart));
$jmlHariBulan = (int)date('t', strtotime($bulanStart));

$lBul_perTanggal = [];
$lBul_totalBooking = 0;
$lBul_totalPendapatan = 0;
$hariTeramaiBooking = 0;
$hariTeramaiTanggal = '';

try {
    $stmt = $pdo->prepare("SELECT tanggal_berangkat, COUNT(*) as jml, COALESCE(SUM(total_harga),0) as total
        FROM bookings WHERE status = 'completed' AND tanggal_berangkat BETWEEN ? AND ?
        GROUP BY tanggal_berangkat ORDER BY tanggal_berangkat ASC");
    $stmt->execute([$bulanStart, $bulanEnd]);
    while ($row = $stmt->fetch()) {
        $key = $row['tanggal_berangkat'];
        $lBul_perTanggal[$key] = [
            'tanggal' => $key,
            'jml_booking' => (int)$row['jml'],
            'total' => (int)$row['total'],
        ];
        $lBul_totalBooking += (int)$row['jml'];
        $lBul_totalPendapatan += (int)$row['total'];
        if ((int)$row['jml'] > $hariTeramaiBooking) {
            $hariTeramaiBooking = (int)$row['jml'];
            $hariTeramaiTanggal = $key;
        }
    }
} catch (PDOException $e) {}

$rataRataPerHari = $jmlHariBulan > 0 ? (int)round($lBul_totalPendapatan / $jmlHariBulan) : 0;
$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
?>
<div class="no-print">
    <?php if ($flash) echo '<div class="mb-5">' . $flash . '</div>'; ?>
    <section class="dash-hero">
        <div class="min-w-0">
            <div class="dash-hero-kicker">Keuangan · Booking selesai</div>
            <h1 class="dash-hero-title">Laporan <em>Keuangan</em></h1>
            <p class="dash-hero-desc">Harian, mingguan, dan bulanan — hanya status selesai.</p>
        </div>
    </section>
    <div class="bk-pills">
        <?php
        $tabs = [
            ['harian',  'fa-calendar-day',   'Harian', '?tab=harian&lhari_tgl='.urlencode($lHari_tanggal)],
            ['mingguan','fa-calendar-week',  'Mingguan','?tab=mingguan&lming_tgl='.urlencode($lMing_tanggal)],
            ['bulanan', 'fa-calendar',       'Bulanan', '?tab=bulanan&lbul_bulan='.$lBul_bulan.'&lbul_tahun='.$lBul_tahun],
        ];
        foreach ($tabs as [$kode, $ico, $lbl, $href]):
        ?>
            <a href="<?= $href ?>" class="bk-pill <?= $tab === $kode ? 'is-on' : '' ?>">
                <i class="fa-solid <?= $ico ?>"></i> <?= $lbl ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- =========================================
     TAB HARIAN
     ========================================= -->
<?php if ($tab === 'harian'): ?>
<div id="laporanHarian" class="lap-report card p-0 overflow-hidden">
    <div class="no-print filter-bar !mb-0 !rounded-none !shadow-none border-0 border-b border-cream-200">
        <form method="GET" class="filter-form">
            <input type="hidden" name="tab" value="harian">
            <div class="filter-field">
                <label class="form-label">Pilih tanggal</label>
                <input type="date" name="lhari_tgl" value="<?= $lHari_tanggal ?>" class="input-field">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Tampilkan</button>
                <button type="button" class="btn-success js-pdf"
                    data-pdf-target="#pdf-print"
                    data-pdf-file="Laporan_Harian_<?= e(date('d_F_Y', strtotime($lHari_tanggal))) ?>_Mustika_Travel"
                    data-pdf-orient="p"
                    data-pdf-judul="LAPORAN HARIAN"
                    data-pdf-periode="<?= e(nama_hari($lHari_tanggal) . ', ' . tgl_id($lHari_tanggal)) ?>"><i class="fa-solid fa-file-pdf"></i> PDF</button>
              
            </div>
        </form>
    </div>

    <div class="hidden print-only lap-print-head">
        <div class="lap-print-brand">Mustika Travel</div>
        <h2>Laporan Harian</h2>
        <p><?= e(nama_hari($lHari_tanggal)) ?>, <?= e(tgl_id($lHari_tanggal)) ?></p>
    </div>

    <div class="lap-kpi">
        <div class="dash-kpi-card">
            <div class="dash-kpi-ico" style="background:#122544"><i class="fa-regular fa-calendar"></i></div>
            <div class="dash-kpi-label">Tanggal</div>
            <div class="dash-kpi-value"><?= e(nama_hari($lHari_tanggal)) ?></div>
            <div class="text-xs text-slate-500 mt-1"><?= e(tgl_id($lHari_tanggal)) ?></div>
        </div>
        <div class="dash-kpi-card">
            <div class="dash-kpi-ico" style="background:#1A3358"><i class="fa-solid fa-ticket"></i></div>
            <div class="dash-kpi-label">Booking selesai</div>
            <div class="dash-kpi-value"><?= (int)$lHari_totalBooking ?></div>
        </div>
        <div class="dash-kpi-card is-wide lap-kpi-clickable" role="button" tabindex="0"
             onclick="window.modalSetoran && window.modalSetoran.open('<?= e($lHari_tanggal) ?>')"
             onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.modalSetoran&&window.modalSetoran.open('<?= e($lHari_tanggal) ?>')}">
            <div class="dash-kpi-ico" style="background:linear-gradient(135deg,#D4B56A,#9A7B1F)"><i class="fa-solid fa-coins"></i></div>
            <div class="dash-kpi-label">Pendapatan hari ini · ketuk untuk setoran</div>
            <div class="dash-kpi-value"><?= rupiah($lHari_totalPendapatan) ?></div>
            <?php if ($lHari_totalBooking > 0): ?>
            <div class="text-xs text-slate-500 mt-1">Rata-rata <?= rupiah((int)round($lHari_totalPendapatan / $lHari_totalBooking)) ?> / booking</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="no-print px-4 pb-3 sm:px-5 flex flex-col sm:flex-row gap-2">
        <button type="button" class="btn-primary w-full sm:w-auto"
                onclick="window.modalSetoran && window.modalSetoran.open('<?= e($lHari_tanggal) ?>')">
            <i class="fa-solid fa-receipt"></i> Detail setoran &amp; fee agen
        </button>
        <button type="button" class="btn-secondary w-full sm:w-auto js-lap-copy"
                data-tanggal="<?= e($lHari_tanggal) ?>">
            <i class="fa-regular fa-copy"></i> Salin ringkasan
        </button>
    </div>

    <!-- Tabel Detail -->
    <div class="table-wrap is-cards is-sticky-col !rounded-none !border-0">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Jam</th>
                    <th>Nama Penumpang</th>
                    <th>No. HP</th>
                    <th>Rute (Jemput → Tujuan)</th>
                    <th class="text-center">Kursi</th>
                    <th class="text-right">Harga</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lHari_data)): ?>
                    <tr><td colspan="8">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fa-solid fa-chart-column"></i></div>
                            <div class="font-semibold text-slate-600">Belum ada perjalanan selesai di tanggal ini.</div>
                        </div>
                    </td></tr>
                <?php else: $no=0; foreach($lHari_data as $b): $no++; ?>
                    <tr>
                        <td class="font-bold text-slate-600"><?= $no ?>.</td>
                        <td class="font-bold text-slate-900">#MT-<?= $b['id'] ?></td>
                        <td class="whitespace-nowrap"><span class="chip bg-blue-50 text-blue-700 !px-3 !py-1 font-bold"><i class="fa-regular fa-clock mr-1.5"></i><?= substr($b['jam_jemput'],0,5) ?></span></td>
                        <td class="font-semibold text-slate-900"><?= e($b['nama']) ?></td>
                        <td class="text-slate-600 text-sm"><?= e($b['no_hp']) ?></td>
                        <td class="text-xs leading-relaxed">
                            <div class="mb-1 flex gap-1.5 items-start"><i class="fa-solid fa-circle-dot text-primary-500 text-[8px] mt-1.5"></i><span><?= e(mb_strimwidth($b['alamat_jemput'],0,40,'...')) ?></span></div>
                            <div class="flex gap-1.5 items-start"><i class="fa-solid fa-location-dot text-red-500 text-[10px] mt-1"></i><span><?= e(mb_strimwidth($b['alamat_tujuan'],0,40,'...')) ?></span></div>
                        </td>
                        <td class="text-center font-bold text-slate-800"><?= $b['jumlah_kursi'] ?></td>
                        <td class="text-right font-extrabold text-primary-700 whitespace-nowrap"><?= rupiah($b['total_harga']) ?></td>
                    </tr>
                <?php endforeach; ?>
                    <tr class="lap-total-row">
                        <td colspan="7" class="text-right font-extrabold text-navy-900 text-sm uppercase tracking-wide !py-4">Total pendapatan hari ini</td>
                        <td class="text-right font-extrabold text-navy-900 text-lg whitespace-nowrap !py-4"><?= rupiah($lHari_totalPendapatan) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; /* end tab harian */ ?>

<!-- =========================================
     TAB MINGGUAN
     ========================================= -->
<?php if ($tab === 'mingguan'): ?>
<div id="laporanMingguan" class="lap-report card p-0 overflow-hidden">
    <div class="no-print filter-bar !mb-0 !rounded-none !shadow-none border-0 border-b border-cream-200">
        <form method="GET" class="filter-form">
            <input type="hidden" name="tab" value="mingguan">
            <div class="filter-field">
                <label class="form-label">Tanggal dalam minggu</label>
                <input type="date" name="lming_tgl" value="<?= $lMing_tanggal ?>" class="input-field">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Tampilkan</button>
                <button type="button" class="btn-success js-pdf"
                    data-pdf-target="#pdf-print"
                    data-pdf-file="Laporan_Mingguan_<?= e(date('d_F_Y', strtotime($mingguStart))) ?>_sd_<?= e(date('d_F_Y', strtotime($mingguEnd))) ?>_Mustika_Travel"
                    data-pdf-orient="p"
                    data-pdf-judul="LAPORAN MINGGUAN"
                    data-pdf-periode="<?= e(tgl_id($mingguStart) . ' s/d ' . tgl_id($mingguEnd)) ?>"><i class="fa-solid fa-file-pdf"></i> PDF</button>
                <button type="button" onclick="window.print()" class="btn-secondary"><i class="fa-solid fa-print"></i> Cetak</button>
            </div>
        </form>
        <div class="text-xs font-bold text-navy-800 mt-2">Periode <?= e(tgl_id($mingguStart)) ?> s/d <?= e(tgl_id($mingguEnd)) ?> · <span class="text-gold-700 font-extrabold">ketuk baris</span> untuk detail setoran</div>
    </div>

    <div class="hidden print-only lap-print-head">
        <div class="lap-print-brand">Mustika Travel</div>
        <h2>Laporan Mingguan</h2>
        <p><?= e(tgl_id($mingguStart)) ?> — <?= e(tgl_id($mingguEnd)) ?></p>
    </div>

    <div class="lap-kpi lap-kpi-3">
        <div class="dash-kpi-card">
            <div class="dash-kpi-ico" style="background:#122544"><i class="fa-solid fa-ticket"></i></div>
            <div class="dash-kpi-label">Booking minggu ini</div>
            <div class="dash-kpi-value"><?= (int)$lMing_totalBooking ?></div>
        </div>
        <div class="dash-kpi-card">
            <div class="dash-kpi-ico" style="background:linear-gradient(135deg,#D4B56A,#9A7B1F)"><i class="fa-solid fa-coins"></i></div>
            <div class="dash-kpi-label">Pendapatan</div>
            <div class="dash-kpi-value"><?= rupiah($lMing_totalPendapatan) ?></div>
        </div>
        <div class="dash-kpi-card">
            <div class="dash-kpi-ico" style="background:#1A3358"><i class="fa-solid fa-chart-line"></i></div>
            <div class="dash-kpi-label">Rata-rata / hari</div>
            <div class="dash-kpi-value"><?= rupiah($lMing_totalBooking > 0 ? (int)round($lMing_totalPendapatan / 7) : 0) ?></div>
        </div>
    </div>

    <!-- Ringkasan per hari -->
    <div class="table-wrap is-cards lap-days-wrap !rounded-none !border-0">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Hari</th>
                    <th>Tanggal</th>
                    <th class="text-center">Jml</th>
                    <th class="text-right">Total Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lMing_perHari as $item): ?>
                    <tr class="lap-row-clickable" role="button" tabindex="0"
                        data-tanggal="<?= e($item['tanggal']) ?>"
                        title="Buka detail setoran <?= e($item['nama_hari']) ?>">
                        <td class="font-bold text-slate-900" data-label="Hari">
                            <span class="inline-flex items-center gap-1.5">
                                <?= $item['nama_hari'] ?>
                                <i class="fa-solid fa-chevron-right text-[10px] text-gold-600 opacity-70 no-print lap-row-chevron"></i>
                            </span>
                        </td>
                        <td class="text-slate-700" data-label="Tanggal"><?= tgl_id($item['tanggal']) ?></td>
                        <td class="text-center font-bold <?= $item['jml_booking'] > 0 ? 'text-primary-700' : 'text-slate-400' ?>" data-label="Jml"><?= $item['jml_booking'] ?></td>
                        <td class="text-right font-extrabold whitespace-nowrap <?= $item['total'] > 0 ? 'text-green-700' : 'text-slate-400' ?>" data-label="Total Pendapatan">
                            <span class="lap-day-actions">
                                <span class="lap-pendapatan-link"><?= rupiah($item['total']) ?></span>
                                <button type="button" class="lap-mini-btn js-lap-copy no-print" data-tanggal="<?= e($item['tanggal']) ?>" title="Salin ringkasan setoran" aria-label="Salin">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="lap-total-row">
                    <td colspan="2" class="text-right font-extrabold text-navy-900 uppercase tracking-wide">Total minggu ini</td>
                    <td class="text-center font-extrabold text-navy-900 text-lg"><?= $lMing_totalBooking ?></td>
                    <td class="text-right font-extrabold text-navy-900 text-lg whitespace-nowrap"><?= rupiah($lMing_totalPendapatan) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; /* end tab mingguan */ ?>

<!-- =========================================
     TAB BULANAN
     ========================================= -->
<?php if ($tab === 'bulanan'): ?>
<div id="laporanBulanan" class="lap-report card p-0 overflow-hidden">
    <div class="no-print filter-bar !mb-0 !rounded-none !shadow-none border-0 border-b border-cream-200">
        <form method="GET" class="filter-form">
            <input type="hidden" name="tab" value="bulanan">
            <div class="filter-field">
                <label class="form-label">Bulan</label>
                <select name="lbul_bulan" class="input-field">
                    <?php for ($i=1; $i<=12; $i++):
                        $sel = ($i === $lBul_bulan) ? 'selected' : '';
                        echo "<option value=\"{$i}\" {$sel}>{$namaBulan[$i]}</option>";
                    endfor; ?>
                </select>
            </div>
            <div class="filter-field">
                <label class="form-label">Tahun</label>
                <select name="lbul_tahun" class="input-field">
                    <?php $yNow = (int)date('Y'); for ($y = $yNow - 3; $y <= $yNow + 2; $y++):
                        $sel = ($y === $lBul_tahun) ? 'selected' : '';
                        echo "<option value=\"{$y}\" {$sel}>{$y}</option>";
                    endfor; ?>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Tampilkan</button>
                <button type="button" class="btn-success js-pdf"
                    data-pdf-target="#pdf-print"
                    data-pdf-file="Laporan_Bulanan_<?= e($namaBulan[$lBul_bulan]) ?>_<?= (int)$lBul_tahun ?>_Mustika_Travel"
                    data-pdf-orient="p"
                    data-pdf-judul="LAPORAN BULANAN"
                    data-pdf-periode="<?= e('Bulan ' . $namaBulan[$lBul_bulan] . ' Tahun ' . $lBul_tahun) ?>"><i class="fa-solid fa-file-pdf"></i> PDF</button>
                <button type="button" onclick="window.print()" class="btn-secondary"><i class="fa-solid fa-print"></i> Cetak</button>
            </div>
        </form>
    </div>

    <div class="hidden print-only lap-print-head">
        <div class="lap-print-brand">Mustika Travel</div>
        <h2>Laporan Bulanan</h2>
        <p>Bulan <?= e($namaBulan[$lBul_bulan]) ?> Tahun <?= (int)$lBul_tahun ?></p>
    </div>

    <div class="lap-kpi">
        <div class="dash-kpi-card">
            <div class="dash-kpi-ico" style="background:#122544"><i class="fa-regular fa-calendar"></i></div>
            <div class="dash-kpi-label">Periode</div>
            <div class="dash-kpi-value"><?= e($namaBulan[$lBul_bulan]) ?></div>
            <div class="text-xs text-slate-500 mt-1"><?= (int)$lBul_tahun ?> · <?= (int)$jmlHariBulan ?> hari</div>
        </div>
        <div class="dash-kpi-card">
            <div class="dash-kpi-ico" style="background:#1A3358"><i class="fa-solid fa-ticket"></i></div>
            <div class="dash-kpi-label">Booking selesai</div>
            <div class="dash-kpi-value"><?= (int)$lBul_totalBooking ?></div>
        </div>
        <div class="dash-kpi-card">
            <div class="dash-kpi-ico" style="background:linear-gradient(135deg,#D4B56A,#9A7B1F)"><i class="fa-solid fa-coins"></i></div>
            <div class="dash-kpi-label">Pendapatan</div>
            <div class="dash-kpi-value"><?= rupiah($lBul_totalPendapatan) ?></div>
        </div>
        <div class="dash-kpi-card">
            <div class="dash-kpi-ico" style="background:#0A1628"><i class="fa-solid fa-fire"></i></div>
            <div class="dash-kpi-label">Hari teramai</div>
            <div class="dash-kpi-value"><?= $hariTeramaiTanggal ? e(tgl_id($hariTeramaiTanggal)) : '-' ?></div>
            <?php if ($hariTeramaiBooking > 0): ?>
                <div class="text-xs text-slate-500 mt-1"><?= (int)$hariTeramaiBooking ?> booking · rata <?= rupiah($rataRataPerHari) ?>/hari</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabel per tanggal -->
    <div class="table-wrap is-cards lap-days-wrap !rounded-none !border-0">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Hari</th>
                    <th class="text-center">Jml</th>
                    <th class="text-right">Total Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lBul_perTanggal)): ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="fa-solid fa-chart-column"></i></div>
                                <div class="font-semibold text-slate-600">Belum ada data perjalanan selesai di bulan ini.</div>
                            </div>
                        </td>
                    </tr>
                <?php else:
                    foreach ($lBul_perTanggal as $item): ?>
                        <tr class="lap-row-clickable" role="button" tabindex="0"
                            data-tanggal="<?= e($item['tanggal']) ?>"
                            title="Buka detail setoran <?= e(tgl_id($item['tanggal'])) ?>">
                            <td class="font-bold text-slate-900" data-label="Tanggal">
                                <span class="inline-flex items-center gap-1.5">
                                    <?= tgl_id($item['tanggal']) ?>
                                    <i class="fa-solid fa-chevron-right text-[10px] text-gold-600 opacity-70 no-print lap-row-chevron"></i>
                                </span>
                            </td>
                            <td class="text-slate-600" data-label="Hari"><?= nama_hari($item['tanggal']) ?></td>
                            <td class="text-center font-bold text-primary-700" data-label="Jml"><?= $item['jml_booking'] ?></td>
                            <td class="text-right font-extrabold text-green-700 whitespace-nowrap" data-label="Total Pendapatan">
                                <span class="lap-day-actions">
                                    <span class="lap-pendapatan-link"><?= rupiah($item['total']) ?></span>
                                    <button type="button" class="lap-mini-btn js-lap-copy no-print" data-tanggal="<?= e($item['tanggal']) ?>" title="Salin ringkasan setoran" aria-label="Salin">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr class="lap-total-row">
                    <td colspan="2" class="text-right font-extrabold text-navy-900 uppercase tracking-wide text-sm">
                        Total <?= e($namaBulan[$lBul_bulan]) ?> <?= (int)$lBul_tahun ?>
                    </td>
                    <td class="text-center font-extrabold text-navy-900 text-lg"><?= $lBul_totalBooking ?></td>
                    <td class="text-right font-extrabold text-navy-900 text-xl whitespace-nowrap"><?= rupiah($lBul_totalPendapatan) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; /* end tab bulanan */ ?>

<?php
$pdfJudul = 'LAPORAN HARIAN';
$pdfPeriode = nama_hari($lHari_tanggal) . ', ' . tgl_id($lHari_tanggal);
if ($tab === 'mingguan') {
    $pdfJudul = 'LAPORAN MINGGUAN';
    $pdfPeriode = tgl_id($mingguStart) . ' s/d ' . tgl_id($mingguEnd);
} elseif ($tab === 'bulanan') {
    $pdfJudul = 'LAPORAN BULANAN';
    $pdfPeriode = 'Bulan ' . $namaBulan[$lBul_bulan] . ' Tahun ' . $lBul_tahun;
}
$pdfRataHari = ($tab === 'harian' && $lHari_totalBooking > 0)
    ? rupiah((int)round($lHari_totalPendapatan / $lHari_totalBooking))
    : (($tab === 'mingguan') ? rupiah($lMing_totalBooking > 0 ? (int)round($lMing_totalPendapatan / 7) : 0) : rupiah($rataRataPerHari));
?>
<div id="pdf-print" aria-hidden="true">
    <div class="pdf-doc">
        <div class="pdf-kop">
            <img src="<?= e(BASE_URL) ?>/logo.jpeg" alt="Logo" class="pdf-logo">
            <div class="pdf-kop-text">
                <div class="pdf-kop-kicker">Travel door to door</div>
                <div class="pdf-kop-name"><?= e(SITE_NAME) ?></div>
            </div>
        </div>
        <div class="pdf-titlebar">
            <h1><?= e($pdfJudul) ?></h1>
            <div class="pdf-periode">Periode: <?= e($pdfPeriode) ?></div>
        </div>

        <?php if ($tab === 'harian'): ?>
        <table class="pdf-kpi">
            <tr>
                <td><span>Tanggal</span><b><?= e(nama_hari($lHari_tanggal)) ?></b><small><?= e(tgl_id($lHari_tanggal)) ?></small></td>
                <td><span>Booking selesai</span><b><?= (int)$lHari_totalBooking ?></b></td>
                <td><span>Pendapatan</span><b><?= rupiah($lHari_totalPendapatan) ?></b></td>
                <td><span>Rata-rata / booking</span><b><?= $lHari_totalBooking > 0 ? rupiah((int)round($lHari_totalPendapatan / $lHari_totalBooking)) : '-' ?></b></td>
            </tr>
        </table>
        <table class="pdf-table">
            <thead>
                <tr>
                    <th class="c" style="width:6%">No</th>
                    <th style="width:12%">Kode</th>
                    <th style="width:9%">Jam</th>
                    <th style="width:20%">Nama</th>
                    <th>Rute</th>
                    <th class="c" style="width:8%">Kursi</th>
                    <th class="r" style="width:16%">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lHari_data)): ?>
                    <tr><td colspan="7" class="pdf-empty">Belum ada perjalanan selesai pada periode ini.</td></tr>
                <?php else: $no=0; foreach ($lHari_data as $b): $no++; ?>
                    <tr>
                        <td class="c"><?= $no ?></td>
                        <td>#MT-<?= (int)$b['id'] ?></td>
                        <td><?= e(substr($b['jam_jemput'],0,5)) ?></td>
                        <td><?= e($b['nama']) ?></td>
                        <td><?= !empty($b['rute']) ? e($b['rute']) : e(mb_strimwidth(($b['alamat_jemput'] ?? '').' → '.($b['alamat_tujuan'] ?? ''), 0, 42, '...')) ?></td>
                        <td class="c"><?= (int)$b['jumlah_kursi'] ?></td>
                        <td class="r"><?= rupiah($b['total_harga']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="r">Total pendapatan</td>
                    <td class="r"><?= rupiah($lHari_totalPendapatan) ?></td>
                </tr>
            </tfoot>
        </table>

        <?php elseif ($tab === 'mingguan'): ?>
        <table class="pdf-kpi">
            <tr>
                <td><span>Periode</span><b>7 hari</b><small><?= e($pdfPeriode) ?></small></td>
                <td><span>Booking selesai</span><b><?= (int)$lMing_totalBooking ?></b></td>
                <td><span>Pendapatan</span><b><?= rupiah($lMing_totalPendapatan) ?></b></td>
                <td><span>Rata-rata / hari</span><b><?= rupiah($lMing_totalBooking > 0 ? (int)round($lMing_totalPendapatan / 7) : 0) ?></b></td>
            </tr>
        </table>
        <table class="pdf-table">
            <thead>
                <tr>
                    <th>Hari</th>
                    <th>Tanggal</th>
                    <th class="c" style="width:18%">Booking</th>
                    <th class="r" style="width:24%">Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lMing_perHari as $item): ?>
                    <tr>
                        <td><?= e($item['nama_hari']) ?></td>
                        <td><?= e(tgl_id($item['tanggal'])) ?></td>
                        <td class="c"><?= (int)$item['jml_booking'] ?></td>
                        <td class="r"><?= rupiah($item['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="r">Total minggu ini</td>
                    <td class="c"><?= (int)$lMing_totalBooking ?></td>
                    <td class="r"><?= rupiah($lMing_totalPendapatan) ?></td>
                </tr>
            </tfoot>
        </table>
        <?php
        $mingAll = [];
        foreach ($lMing_perHari as $item) {
            foreach ($item['detail'] as $d) $mingAll[] = $d;
        }
        if (!empty($mingAll)):
        ?>
        <div class="pdf-sub">Rincian booking</div>
        <table class="pdf-table">
            <thead>
                <tr>
                    <th class="c" style="width:6%">No</th>
                    <th style="width:12%">Kode</th>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>Rute</th>
                    <th class="r" style="width:16%">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=0; foreach ($mingAll as $d): $no++; ?>
                    <tr>
                        <td class="c"><?= $no ?></td>
                        <td>#MT-<?= (int)$d['id'] ?></td>
                        <td><?= e(tgl_id($d['tanggal_berangkat'])) ?> <?= e(substr($d['jam_jemput'],0,5)) ?></td>
                        <td><?= e($d['nama']) ?></td>
                        <td><?= !empty($d['rute']) ? e($d['rute']) : 'Custom' ?></td>
                        <td class="r"><?= rupiah($d['total_harga']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php else: ?>
        <table class="pdf-kpi">
            <tr>
                <td><span>Periode</span><b><?= e($namaBulan[$lBul_bulan]) ?></b><small><?= (int)$lBul_tahun ?> · <?= (int)$jmlHariBulan ?> hari</small></td>
                <td><span>Booking selesai</span><b><?= (int)$lBul_totalBooking ?></b></td>
                <td><span>Pendapatan</span><b><?= rupiah($lBul_totalPendapatan) ?></b></td>
                <td><span>Hari teramai</span><b><?= $hariTeramaiTanggal ? e(tgl_id($hariTeramaiTanggal)) : '-' ?></b><small><?= $hariTeramaiBooking ? (int)$hariTeramaiBooking . ' booking' : '' ?></small></td>
            </tr>
        </table>
        <table class="pdf-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Hari</th>
                    <th class="c" style="width:18%">Booking</th>
                    <th class="r" style="width:24%">Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lBul_perTanggal)): ?>
                    <tr><td colspan="4" class="pdf-empty">Belum ada perjalanan selesai di bulan ini.</td></tr>
                <?php else: foreach ($lBul_perTanggal as $item): ?>
                    <tr>
                        <td><?= e(tgl_id($item['tanggal'])) ?></td>
                        <td><?= e(nama_hari($item['tanggal'])) ?></td>
                        <td class="c"><?= (int)$item['jml_booking'] ?></td>
                        <td class="r"><?= rupiah($item['total']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="r">Total <?= e($namaBulan[$lBul_bulan]) ?> <?= (int)$lBul_tahun ?></td>
                    <td class="c"><?= (int)$lBul_totalBooking ?></td>
                    <td class="r"><?= rupiah($lBul_totalPendapatan) ?></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>

        <div class="pdf-footnote">Dicetak admin <?= e(SITE_NAME) ?> · <?= e(tgl_id(date('Y-m-d'))) ?> <?= date('H:i') ?> WIB · Kertas F4</div>
    </div>
</div>

<!-- =========================================
     MODAL DETAIL SETORAN HARIAN
     ========================================= -->
<div class="no-print" x-data="modalSetoranData()" x-init="window.modalSetoran = $data">
    <template x-if="show">
        <div class="admin-modal-overlay" x-transition.opacity>
            <div @click="close()" class="absolute inset-0"></div>
            <div class="admin-modal lap-settle-modal w-full max-w-full sm:max-w-3xl lg:max-w-4xl" @click.stop>
                <div class="admin-modal-head">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="admin-modal-ico"><i class="fa-solid fa-receipt"></i></div>
                        <div class="min-w-0">
                            <h3>Detail Setoran · 1 PP</h3>
                            <p x-text="(hari || '') + (tanggalId ? ', ' + tanggalId : '')"></p>
                        </div>
                    </div>
                    <button type="button" @click="close()" class="admin-modal-close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="admin-modal-body space-y-4">
                    <div x-show="loading" class="py-10 text-center text-slate-500 text-sm">
                        <i class="fa-solid fa-spinner fa-spin text-lg text-navy-800"></i>
                        <div class="mt-2 font-semibold">Memuat data…</div>
                    </div>

                    <div x-show="!loading && error" class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-semibold" x-text="error"></div>

                    <template x-if="!loading && !error">
                        <div class="space-y-4">
                            <div>
                                <label class="form-label">Driver</label>
                                <input type="text" class="input-field" x-model="driver_name" placeholder="Nama driver (mis. Arif)" maxlength="120">
                            </div>

                            <!-- BERANGKAT -->
                            <div>
                                <div class="admin-modal-section !pt-0">Berangkat <span class="font-normal normal-case tracking-normal text-slate-400" x-text="'· ' + berangkat.length + ' booking'"></span></div>
                                <div class="lap-pax-list" x-show="berangkat.length === 0">
                                    <div class="lap-pax-empty">Tidak ada booking berangkat.</div>
                                </div>
                                <div class="lap-pax-list" x-show="berangkat.length > 0">
                                    <template x-for="b in berangkat" :key="'b'+b.booking_id">
                                        <div class="lap-pax-card">
                                            <div class="lap-pax-top">
                                                <div class="min-w-0">
                                                    <div class="lap-pax-name" x-text="b.nama"></div>
                                                    <div class="lap-pax-meta" x-text="'#MT-' + b.booking_id + ' · ' + b.jam"></div>
                                                </div>
                                                <select class="input-field lap-pax-arah"
                                                        :value="b.arah"
                                                        @change="setArah(b, $event.target.value)">
                                                    <option value="berangkat">Berangkat</option>
                                                    <option value="pulang">Pulang</option>
                                                </select>
                                            </div>
                                            <div class="lap-pax-ticket" x-text="fmtTicket(b) + ' · ' + b.jumlah_kursi + ' Penumpang'"></div>
                                            <div class="lap-pax-fee">
                                                <label>Fee Agen</label>
                                                <input type="number" min="0" step="1000" inputmode="numeric"
                                                       x-model.number="b.fee_agen" @input="recalc()">
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- PULANG -->
                            <div>
                                <div class="admin-modal-section">Pulang <span class="font-normal normal-case tracking-normal text-slate-400" x-text="'· ' + pulang.length + ' booking'"></span></div>
                                <div class="lap-pax-list" x-show="pulang.length === 0">
                                    <div class="lap-pax-empty">Tidak ada booking pulang.</div>
                                </div>
                                <div class="lap-pax-list" x-show="pulang.length > 0">
                                    <template x-for="b in pulang" :key="'p'+b.booking_id">
                                        <div class="lap-pax-card">
                                            <div class="lap-pax-top">
                                                <div class="min-w-0">
                                                    <div class="lap-pax-name" x-text="b.nama"></div>
                                                    <div class="lap-pax-meta" x-text="'#MT-' + b.booking_id + ' · ' + b.jam"></div>
                                                </div>
                                                <select class="input-field lap-pax-arah"
                                                        :value="b.arah"
                                                        @change="setArah(b, $event.target.value)">
                                                    <option value="berangkat">Berangkat</option>
                                                    <option value="pulang">Pulang</option>
                                                </select>
                                            </div>
                                            <div class="lap-pax-ticket" x-text="fmtTicket(b) + ' · ' + b.jumlah_kursi + ' Penumpang'"></div>
                                            <div class="lap-pax-fee">
                                                <label>Fee Agen</label>
                                                <input type="number" min="0" step="1000" inputmode="numeric"
                                                       x-model.number="b.fee_agen" @input="recalc()">
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- RINGKASAN + OPS -->
                            <div class="lap-settle-box">
                                <div class="lap-settle-row">
                                    <span>Dari berangkat</span>
                                    <b x-text="rupiah(totalBerangkat)"></b>
                                </div>
                                <div class="lap-settle-row">
                                    <span>Dari pulang</span>
                                    <b x-text="rupiah(totalPulang)"></b>
                                </div>
                                <div class="lap-settle-row is-strong">
                                    <span>Total pendapatan</span>
                                    <b x-text="rupiah(totalPendapatan)"></b>
                                </div>
                                <div class="lap-settle-row text-slate-500">
                                    <span>Total fee agen (info)</span>
                                    <b x-text="rupiah(totalFeeAgen)"></b>
                                </div>
                            </div>

                            <div>
                                <div class="admin-modal-section">Kurangi operasional</div>
                                <div class="lap-ops-grid">
                                    <div>
                                        <label class="form-label">BBM</label>
                                        <input type="number" min="0" step="1000" inputmode="numeric" class="input-field" x-model.number="bbm" @input="recalc()">
                                    </div>
                                    <div>
                                        <label class="form-label">Toll</label>
                                        <input type="number" min="0" step="1000" inputmode="numeric" class="input-field" x-model.number="toll" @input="recalc()">
                                    </div>
                                    <div>
                                        <label class="form-label">Fee</label>
                                        <input type="number" min="0" step="1000" inputmode="numeric" class="input-field" x-model.number="fee_ops" @input="recalc()">
                                    </div>
                                    <div>
                                        <label class="form-label">Lainnya</label>
                                        <input type="number" min="0" step="1000" inputmode="numeric" class="input-field" x-model.number="ops_lain" @input="recalc()">
                                    </div>
                                </div>
                            </div>

                            <div class="lap-settle-box is-final">
                                <div class="lap-settle-row">
                                    <span>Total operasional</span>
                                    <b x-text="rupiah(totalOps)"></b>
                                </div>
                                <div class="lap-settle-row is-strong">
                                    <span>Net (pendapatan − ops)</span>
                                    <b x-text="rupiah(net)"></b>
                                </div>
                                <div class="lap-settle-row">
                                    <span>Hasil driver (30%)</span>
                                    <b x-text="rupiah(hasilDriver)"></b>
                                </div>
                                <div class="lap-settle-row is-setoran">
                                    <span>Sisa setoran</span>
                                    <b x-text="rupiah(sisaSetoran)"></b>
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Catatan</label>
                                <textarea class="input-field resize-none" rows="2" x-model="notes" placeholder="Opsional"></textarea>
                            </div>

                            <div class="lap-share-bar">
                                <button type="button" class="btn-secondary lap-share-btn" :disabled="loading || !!error" @click="copySummary()">
                                    <i class="fa-regular" :class="copied ? 'fa-circle-check' : 'fa-copy'"></i>
                                    <span x-text="copied ? 'Tersalin!' : 'Salin teks'"></span>
                                </button>
                                <button type="button" class="btn-secondary lap-share-btn" :disabled="loading || !!error" @click="downloadSummary()">
                                    <i class="fa-solid fa-download"></i> Download .txt
                                </button>
                                <a class="btn-success lap-share-btn" :href="waShareUrl()" target="_blank" rel="noopener"
                                   @click="if (!canShare()) { $event.preventDefault(); }">
                                    <i class="fa-brands fa-whatsapp"></i> Kirim WA
                                </a>
                            </div>

                            <div x-show="saveMsg" class="text-sm font-semibold" :class="saveOk ? 'text-emerald-700' : 'text-red-600'" x-text="saveMsg"></div>
                        </div>
                    </template>
                </div>

                <div class="admin-modal-foot lap-settle-foot">
                    <button type="button" @click="close()" class="btn-secondary">Tutup</button>
                    <button type="button" class="btn-primary" :disabled="loading || saving || !!error" @click="save()">
                        <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                        <span x-text="saving ? 'Menyimpan…' : 'Simpan setoran'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function modalSetoranData() {
    const API = <?= json_encode(BASE_URL . '/admin/proses_laporan_detail.php', JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
    const CSRF = <?= json_encode($csrf, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
    const SITE = <?= json_encode(SITE_NAME, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;

    return {
        show: false,
        loading: false,
        saving: false,
        error: '',
        saveMsg: '',
        saveOk: false,
        copied: false,
        tanggal: '',
        tanggalId: '',
        hari: '',
        csrf: CSRF,
        driver_name: '',
        bbm: 0,
        toll: 0,
        fee_ops: 0,
        ops_lain: 0,
        notes: '',
        bookings: [],
        totalBerangkat: 0,
        totalPulang: 0,
        totalPendapatan: 0,
        totalFeeAgen: 0,
        totalOps: 0,
        net: 0,
        hasilDriver: 0,
        sisaSetoran: 0,

        get berangkat() {
            return this.bookings.filter(b => b.arah === 'berangkat');
        },
        get pulang() {
            return this.bookings.filter(b => b.arah === 'pulang');
        },

        rupiah(n) {
            const v = parseInt(n || 0, 10);
            const sign = v < 0 ? '-' : '';
            return 'Rp ' + sign + Math.abs(v).toLocaleString('id-ID');
        },
        fmtTicket(b) {
            const h = parseInt(b.harga_satuan || 0, 10);
            const k = parseInt(b.jumlah_kursi || 1, 10);
            return h.toLocaleString('id-ID') + ' × ' + k + 'Pnp';
        },
        setArah(b, arah) {
            b.arah = arah === 'pulang' ? 'pulang' : 'berangkat';
            this.bookings = this.bookings.slice();
            this.recalc();
        },
        recalc() {
            let tb = 0, tp = 0, fee = 0;
            this.bookings.forEach(b => {
                const tot = parseInt(b.total_harga || 0, 10);
                fee += Math.max(0, parseInt(b.fee_agen || 0, 10));
                if (b.arah === 'pulang') tp += tot;
                else tb += tot;
            });
            this.totalBerangkat = tb;
            this.totalPulang = tp;
            this.totalPendapatan = tb + tp;
            this.totalFeeAgen = fee;
            const ops = Math.max(0, parseInt(this.bbm || 0, 10))
                + Math.max(0, parseInt(this.toll || 0, 10))
                + Math.max(0, parseInt(this.fee_ops || 0, 10))
                + Math.max(0, parseInt(this.ops_lain || 0, 10));
            this.totalOps = ops;
            this.net = this.totalPendapatan - ops;
            this.hasilDriver = Math.round(this.net * 0.3);
            this.sisaSetoran = this.net - this.hasilDriver;
        },
        linesFor(arah) {
            return this.bookings.filter(b => b.arah === arah).map(b => {
                const fee = Math.max(0, parseInt(b.fee_agen || 0, 10));
                return '- ' + b.nama + ' · ' + this.fmtTicket(b)
                    + ' · ' + b.jumlah_kursi + ' Pnp'
                    + (fee > 0 ? ' · Fee agen ' + this.rupiah(fee) : '');
            });
        },
        buildShareText() {
            const driver = (this.driver_name || '').trim() || '-';
            const tgl = ((this.hari || '') + (this.tanggalId ? ', ' + this.tanggalId : '')).trim() || this.tanggal;
            const br = this.linesFor('berangkat');
            const pl = this.linesFor('pulang');
            const opsParts = [];
            if (parseInt(this.bbm || 0, 10) > 0) opsParts.push('BBM ' + this.rupiah(this.bbm));
            if (parseInt(this.toll || 0, 10) > 0) opsParts.push('Toll ' + this.rupiah(this.toll));
            if (parseInt(this.fee_ops || 0, 10) > 0) opsParts.push('Fee ' + this.rupiah(this.fee_ops));
            if (parseInt(this.ops_lain || 0, 10) > 0) opsParts.push('Lainnya ' + this.rupiah(this.ops_lain));

            let txt = '';
            txt += '*SETORAN 1 PP — ' + SITE + '*\n';
            txt += 'Driver: ' + driver + '\n';
            txt += 'Tanggal: ' + tgl + '\n\n';
            txt += '*BERANGKAT* (' + br.length + ')\n';
            txt += (br.length ? br.join('\n') : '- (kosong)') + '\n';
            txt += 'Subtotal: ' + this.rupiah(this.totalBerangkat) + '\n\n';
            txt += '*PULANG* (' + pl.length + ')\n';
            txt += (pl.length ? pl.join('\n') : '- (kosong)') + '\n';
            txt += 'Subtotal: ' + this.rupiah(this.totalPulang) + '\n\n';
            txt += 'Total pendapatan: ' + this.rupiah(this.totalPendapatan) + '\n';
            if (this.totalFeeAgen > 0) txt += 'Total fee agen: ' + this.rupiah(this.totalFeeAgen) + '\n';
            txt += 'Operasional' + (opsParts.length ? ' (' + opsParts.join(' + ') + ')' : '') + ': ' + this.rupiah(this.totalOps) + '\n';
            txt += 'Net: ' + this.rupiah(this.net) + '\n';
            txt += 'Bagian driver 30%: ' + this.rupiah(this.hasilDriver) + '\n';
            txt += 'Sisa setoran: ' + this.rupiah(this.sisaSetoran) + '\n';
            if ((this.notes || '').trim()) txt += '\nCatatan: ' + this.notes.trim() + '\n';
            return txt.trim() + '\n';
        },
        canShare() {
            return !this.loading && !this.error && !!this.tanggal;
        },
        waShareUrl() {
            if (!this.canShare()) return '#';
            return 'https://wa.me/?text=' + encodeURIComponent(this.buildShareText());
        },
        async copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return;
            }
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        },
        async copySummary() {
            if (!this.canShare()) return;
            try {
                await this.copyToClipboard(this.buildShareText());
                this.copied = true;
                this.saveOk = true;
                this.saveMsg = 'Ringkasan tersalin. Tempel di WhatsApp driver.';
                setTimeout(() => { this.copied = false; }, 2000);
            } catch (e) {
                this.saveOk = false;
                this.saveMsg = 'Gagal menyalin. Coba Download .txt.';
            }
        },
        downloadSummary() {
            if (!this.canShare()) return;
            const blob = new Blob([this.buildShareText()], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            const safeTgl = (this.tanggal || 'setoran').replace(/[^\d-]/g, '');
            a.href = url;
            a.download = 'Setoran_' + safeTgl + '_Mustika_Travel.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            this.saveOk = true;
            this.saveMsg = 'File .txt siap diunduh.';
        },
        applyPayload(data) {
            this.hari = data.hari || '';
            this.tanggalId = data.tanggal_id || data.tanggal || this.tanggal;
            this.tanggal = data.tanggal || this.tanggal;
            const s = data.settlement || {};
            this.driver_name = s.driver_name || '';
            this.bbm = parseInt(s.bbm || 0, 10);
            this.toll = parseInt(s.toll || 0, 10);
            this.fee_ops = parseInt(s.fee_ops || 0, 10);
            this.ops_lain = parseInt(s.ops_lain || 0, 10);
            this.notes = s.notes || '';
            this.bookings = (data.bookings || []).map(b => ({
                ...b,
                fee_agen: parseInt(b.fee_agen || 0, 10),
                arah: b.arah === 'pulang' ? 'pulang' : 'berangkat'
            }));
            if (data.csrf) this.csrf = data.csrf;
            this.recalc();
        },
        async fetchTanggal(tanggal) {
            const res = await fetch(API + '?tanggal=' + encodeURIComponent(tanggal), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Gagal memuat');
            return data;
        },
        async open(tanggal) {
            if (!tanggal) return;
            this.show = true;
            this.loading = true;
            this.error = '';
            this.saveMsg = '';
            this.copied = false;
            this.tanggal = tanggal;
            this.bookings = [];
            document.body.style.overflow = 'hidden';
            try {
                const data = await this.fetchTanggal(tanggal);
                this.applyPayload(data);
            } catch (e) {
                this.error = e.message || 'Gagal memuat data.';
            } finally {
                this.loading = false;
            }
        },
        async copyTanggal(tanggal) {
            try {
                const data = await this.fetchTanggal(tanggal);
                // temporary apply without opening modal
                const prev = {
                    show: this.show,
                    tanggal: this.tanggal,
                    tanggalId: this.tanggalId,
                    hari: this.hari,
                    driver_name: this.driver_name,
                    bbm: this.bbm,
                    toll: this.toll,
                    fee_ops: this.fee_ops,
                    ops_lain: this.ops_lain,
                    notes: this.notes,
                    bookings: this.bookings,
                    csrf: this.csrf
                };
                this.applyPayload(data);
                await this.copyToClipboard(this.buildShareText());
                if (!prev.show) {
                    this.tanggal = prev.tanggal;
                    this.tanggalId = prev.tanggalId;
                    this.hari = prev.hari;
                    this.driver_name = prev.driver_name;
                    this.bbm = prev.bbm;
                    this.toll = prev.toll;
                    this.fee_ops = prev.fee_ops;
                    this.ops_lain = prev.ops_lain;
                    this.notes = prev.notes;
                    this.bookings = prev.bookings;
                    this.csrf = prev.csrf;
                    this.recalc();
                }
                alert('Ringkasan setoran ' + (data.tanggal_id || tanggal) + ' tersalin. Tempel di WhatsApp.');
            } catch (e) {
                alert(e.message || 'Gagal menyalin ringkasan.');
            }
        },
        close() {
            this.show = false;
            document.body.style.overflow = '';
        },
        async save() {
            if (this.saving || this.loading) return;
            this.saving = true;
            this.saveMsg = '';
            try {
                const payload = {
                    csrf_token: this.csrf,
                    tanggal: this.tanggal,
                    driver_name: this.driver_name,
                    bbm: parseInt(this.bbm || 0, 10),
                    toll: parseInt(this.toll || 0, 10),
                    fee_ops: parseInt(this.fee_ops || 0, 10),
                    ops_lain: parseInt(this.ops_lain || 0, 10),
                    notes: this.notes,
                    fees: this.bookings.map(b => ({
                        booking_id: b.booking_id,
                        fee_agen: parseInt(b.fee_agen || 0, 10),
                        arah: b.arah === 'pulang' ? 'pulang' : 'berangkat'
                    }))
                };
                const res = await fetch(API, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!data.ok) throw new Error(data.error || 'Gagal menyimpan');
                if (data.csrf) this.csrf = data.csrf;
                this.saveOk = true;
                this.saveMsg = data.message || 'Tersimpan.';
            } catch (e) {
                this.saveOk = false;
                this.saveMsg = e.message || 'Gagal menyimpan.';
            } finally {
                this.saving = false;
            }
        }
    };
}

document.addEventListener('click', function (e) {
    const copyBtn = e.target.closest('.js-lap-copy');
    if (copyBtn) {
        e.preventDefault();
        e.stopPropagation();
        const tgl = copyBtn.getAttribute('data-tanggal');
        if (tgl && window.modalSetoran) window.modalSetoran.copyTanggal(tgl);
        return;
    }
    const row = e.target.closest('tr.lap-row-clickable');
    if (!row || !window.modalSetoran) return;
    const tgl = row.getAttribute('data-tanggal');
    if (tgl) window.modalSetoran.open(tgl);
});
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const row = e.target.closest && e.target.closest('tr.lap-row-clickable');
    if (!row || !window.modalSetoran) return;
    if (e.target.closest && e.target.closest('.js-lap-copy')) return;
    e.preventDefault();
    const tgl = row.getAttribute('data-tanggal');
    if (tgl) window.modalSetoran.open(tgl);
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
