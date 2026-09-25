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
        <div class="dash-kpi-card is-wide">
            <div class="dash-kpi-ico" style="background:linear-gradient(135deg,#D4B56A,#9A7B1F)"><i class="fa-solid fa-coins"></i></div>
            <div class="dash-kpi-label">Pendapatan hari ini</div>
            <div class="dash-kpi-value"><?= rupiah($lHari_totalPendapatan) ?></div>
            <?php if ($lHari_totalBooking > 0): ?>
            <div class="text-xs text-slate-500 mt-1">Rata-rata <?= rupiah((int)round($lHari_totalPendapatan / $lHari_totalBooking)) ?> / booking</div>
            <?php endif; ?>
        </div>
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
        <div class="text-xs font-bold text-navy-800 mt-2">Periode <?= e(tgl_id($mingguStart)) ?> s/d <?= e(tgl_id($mingguEnd)) ?></div>
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
    <div class="table-wrap is-sticky-col !rounded-none !border-0">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Hari</th>
                    <th>Tanggal</th>
                    <th class="text-center">Jml Booking</th>
                    <th class="text-right">Total Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lMing_perHari as $item): ?>
                    <tr>
                        <td class="font-bold text-slate-900"><?= $item['nama_hari'] ?></td>
                        <td class="text-slate-700"><?= tgl_id($item['tanggal']) ?></td>
                        <td class="text-center font-bold <?= $item['jml_booking'] > 0 ? 'text-primary-700' : 'text-slate-400' ?>"><?= $item['jml_booking'] ?></td>
                        <td class="text-right font-extrabold whitespace-nowrap <?= $item['total'] > 0 ? 'text-green-700' : 'text-slate-400' ?>"><?= rupiah($item['total']) ?></td>
                    </tr>
                    <!-- Detail tiap hari, jika ada booking -->
                    <?php if (!empty($item['detail'])): ?>
                        <tr class="lap-detail-row">
                            <td colspan="4" class="!py-3 !px-6">
                                <div class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gold-600 mb-1.5">
                                    Detail <?= e($item['nama_hari']) ?> (<?= e(tgl_id($item['tanggal'])) ?>)
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                                    <?php foreach ($item['detail'] as $d): ?>
                                        <div class="flex items-start justify-between bg-cream-50 border border-cream-200 rounded-lg px-3 py-2">
                                            <div>
                                                <span class="font-bold text-navy-900">#MT-<?= $d['id'] ?></span>
                                                <span class="text-slate-600 ml-2"><?= substr($d['jam_jemput'],0,5) ?> WIB</span>
                                                <div class="text-slate-600 mt-0.5"><?= e($d['nama']) ?> · <?= e($d['no_hp']) ?></div>
                                            </div>
                                            <div class="text-right ml-2 flex-shrink-0">
                                                <div class="font-bold text-navy-800 whitespace-nowrap"><?= rupiah($d['total_harga']) ?></div>
                                                <div class="text-slate-400 text-[10px]"><?= $d['jumlah_kursi'] ?> kursi</div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
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
    <div class="table-wrap is-cards is-sticky-col !rounded-none !border-0">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Hari</th>
                    <th class="text-center">Jumlah Booking</th>
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
                        <tr>
                            <td class="font-bold text-slate-900"><?= tgl_id($item['tanggal']) ?></td>
                            <td class="text-slate-600"><?= nama_hari($item['tanggal']) ?></td>
                            <td class="text-center font-bold text-primary-700"><?= $item['jml_booking'] ?></td>
                            <td class="text-right font-extrabold text-green-700 whitespace-nowrap"><?= rupiah($item['total']) ?></td>
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

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
