<?php
// =============================================
// ADMIN DASHBOARD
// =============================================
require_once __DIR__ . '/includes/auth_check.php';
$active_menu = 'dashboard';
$page_title  = 'Dashboard';
require_once __DIR__ . '/includes/admin_header.php';

// === 1. Query Statistik ===
$today = date('Y-m-d');

// a. Total booking hari ini (semua status)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$todayBookings = (int)$stmt->fetchColumn();

// b. Pending (semua waktu)
$stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
$pending = (int)$stmt->fetchColumn();

// c. Confirmed (semua waktu)
$stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'");
$confirmed = (int)$stmt->fetchColumn();

// d. Pendapatan hari ini = sum total_harga booking status completed hari ini
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_harga), 0) FROM bookings WHERE status = 'completed' AND DATE(tanggal_berangkat) = ?");
$stmt->execute([$today]);
$todayRevenue = (int)$stmt->fetchColumn();

// e. Total bulan ini
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_harga), 0) FROM bookings WHERE status = 'completed' AND MONTH(tanggal_berangkat) = ? AND YEAR(tanggal_berangkat) = ?");
$stmt->execute([date('n'), date('Y')]);
$monthRevenue = (int)$stmt->fetchColumn();

// f. Total completed
$stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'");
$totalCompleted = (int)$stmt->fetchColumn();

// === 2. 10 Booking Terbaru ===
$stmt = $pdo->query("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 10");
$recentBookings = $stmt->fetchAll();

$jamNow = (int) date('G');
$sapaan = $jamNow < 11 ? 'Selamat pagi' : ($jamNow < 15 ? 'Selamat siang' : ($jamNow < 18 ? 'Selamat sore' : 'Selamat malam'));
$targetBulanan = 10000000;
$pctBulanan = $targetBulanan > 0 ? min(100, (int)(($monthRevenue / $targetBulanan) * 100)) : 0;
$nm = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
?>
<div class="no-print">
    <?php
    $flash = get_flash();
    if ($flash) echo '<div class="mb-5">' . $flash . '</div>';
    ?>

    <section class="dash-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div class="min-w-0">
                <div class="dash-hero-kicker">Dashboard · Mustika Travel</div>
                <h1 class="dash-hero-title"><?= $sapaan ?>, <em><?= e($admin_name) ?></em></h1>
                <p class="dash-hero-desc">
                    <?= nama_hari(date('Y-m-d')) ?>, <?= tgl_id(date('Y-m-d')) ?> · ringkasan operasional hari ini.
                </p>
            </div>
            <div class="dash-hero-actions">
                <a href="<?= BASE_URL ?>/admin/bookings.php" class="is-gold"><i class="fa-solid fa-plus"></i> Booking baru</a>
                <a href="<?= BASE_URL ?>/admin/bookings.php?status=pending" class="is-ghost"><i class="fa-regular fa-clock"></i> Pending (<?= $pending ?>)</a>
                <a href="<?= BASE_URL ?>/admin/laporan.php" class="is-ghost"><i class="fa-solid fa-chart-column"></i> Laporan</a>
            </div>
        </div>
    </section>
</div>

<?php
$aiReady = defined('OPENAI_API_KEY') && OPENAI_API_KEY !== '';
?>
<section class="ai-assist no-print" id="ai-asisten">
    <div class="ai-assist-head">
        <div class="ai-assist-ico"><i class="fa-solid fa-robot"></i></div>
        <div class="min-w-0 flex-1">
            <h2 class="ai-assist-title">Asisten hitung data</h2>
            <p class="ai-assist-desc">Tanya pendapatan / penumpang per rute &amp; periode. Contoh: <em>“Pendapatan Blora Surabaya bulan ini berapa?”</em></p>
        </div>
        <span class="ai-assist-badge"><?= $aiReady ? 'AI + lokal' : 'Hitung lokal' ?></span>
    </div>
    <div class="ai-assist-chips">
        <button type="button" class="ai-chip" data-q="Pendapatan Blora Surabaya bulan ini berapa?">Blora–Surabaya bulan ini</button>
        <button type="button" class="ai-chip" data-q="Total penumpang hari ini berapa?">Penumpang hari ini</button>
        <button type="button" class="ai-chip" data-q="Pendapatan semua rute bulan ini berapa?">Semua rute bulan ini</button>
        <button type="button" class="ai-chip" data-q="Berapa booking pending?">Booking pending</button>
    </div>
    <form id="aiAssistForm" class="ai-assist-form" autocomplete="off">
        <input type="text" id="aiAssistInput" name="question" maxlength="500" required
               placeholder="Contoh: cari data Blora Surabaya bulan ini total penumpang &amp; pendapatan…"
               class="ai-assist-input">
        <button type="submit" class="ai-assist-btn" id="aiAssistBtn">
            <i class="fa-solid fa-calculator"></i> Hitung
        </button>
    </form>
    <div id="aiAssistOut" class="ai-assist-out" hidden></div>
</section>
<script>
(function () {
    var form = document.getElementById('aiAssistForm');
    var input = document.getElementById('aiAssistInput');
    var out = document.getElementById('aiAssistOut');
    var btn = document.getElementById('aiAssistBtn');
    var url = <?= json_encode(BASE_URL . '/admin/proses_ai_tanya.php') ?>;

    document.querySelectorAll('.ai-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            input.value = chip.getAttribute('data-q') || '';
            form.requestSubmit();
        });
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var q = (input.value || '').trim();
        if (!q) return;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghitung…';
        out.hidden = false;
        out.className = 'ai-assist-out is-loading';
        out.textContent = 'Mengambil data dari database…';

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ question: q }),
            credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            out.className = 'ai-assist-out ' + (data.ok ? 'is-ok' : 'is-err');
            out.textContent = data.answer || 'Tidak ada jawaban.';
        })
        .catch(function () {
            out.className = 'ai-assist-out is-err';
            out.textContent = 'Gagal menghubungi server. Coba lagi.';
        })
        .finally(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-calculator"></i> Hitung';
        });
    });
})();
</script>

<div class="dash-kpi">
    <?php
    $stats = [
        ['Hari ini',            $todayBookings . ' booking',     'fa-calendar-day',  '#1A3358', BASE_URL.'/admin/bookings.php'],
        ['Menunggu',            $pending . ' booking',           'fa-clock',         '#B8952A', BASE_URL.'/admin/bookings.php?status=pending'],
        ['Terkonfirmasi',       $confirmed . ' booking',         'fa-circle-check',  '#2F4A6E', BASE_URL.'/admin/bookings.php?status=confirmed'],
        ['Perjalanan selesai',  $totalCompleted . ' trip',       'fa-check-double',  '#0F766E', BASE_URL.'/admin/laporan.php'],
        ['Pendapatan hari ini', rupiah($todayRevenue),           'fa-sack-dollar',   '#0A1628', BASE_URL.'/admin/laporan.php'],
    ];
    foreach ($stats as [$label, $value, $icon, $bg, $href]):
    ?>
        <a href="<?= $href ?>" class="dash-kpi-card">
            <div class="dash-kpi-ico" style="background: <?= $bg ?>"><i class="fa-solid <?= $icon ?>"></i></div>
            <div class="dash-kpi-label"><?= $label ?></div>
            <div class="dash-kpi-value"><?= $value ?></div>
        </a>
    <?php endforeach; ?>
</div>

<div class="dash-grid">
    <div class="dash-panel navy">
        <div class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-gold-200 mb-2">Pendapatan bulan <?= $nm[(int)date('n')] ?> <?= date('Y') ?></div>
        <div class="text-2xl md:text-3xl font-extrabold text-white break-all"><?= rupiah($monthRevenue) ?></div>
        <div class="flex items-center justify-between mt-4 mb-1.5 text-[11px] text-blue-100/75 font-semibold">
            <span>Target <?= rupiah($targetBulanan) ?></span>
            <span class="text-gold-200"><?= $pctBulanan ?>%</span>
        </div>
        <div class="dash-progress"><span style="width: <?= max($pctBulanan, 3) ?>%"></span></div>
        <div class="mt-3 text-[11px] text-blue-100/70"><?= $totalCompleted ?> perjalanan selesai · tandai booking <b class="text-gold-200">Selesai</b> agar masuk laporan.</div>
    </div>
    <div class="dash-panel">
        <div class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-gold-600 mb-3">Aksi cepat</div>
        <div class="dash-actions">
            <a class="dash-action" href="<?= BASE_URL ?>/admin/bookings.php"><i class="fa-solid fa-calendar-days"></i> Data booking</a>
            <a class="dash-action" href="<?= BASE_URL ?>/admin/rutes.php"><i class="fa-solid fa-map-location-dot"></i> Rute &amp; harga</a>
            <a class="dash-action" href="<?= BASE_URL ?>/admin/armadas.php"><i class="fa-solid fa-van-shuttle"></i> Armada</a>
            <a class="dash-action" href="<?= BASE_URL ?>/admin/carters.php"><i class="fa-solid fa-handshake"></i> Carter</a>
        </div>
    </div>
</div>

<div class="dash-recent">
    <div class="dash-recent-head no-print">
        <div>
            <h2 class="text-base md:text-lg font-extrabold text-navy-900 leading-tight">10 booking terbaru</h2>
            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Update <?= date('H:i') ?> WIB</p>
        </div>
        <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn-primary text-xs">
            <i class="fa-solid fa-arrow-right"></i> Kelola semua
        </a>
    </div>

    <?php if (empty($recentBookings)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fa-solid fa-inbox"></i></div>
            <div class="text-sm font-bold text-slate-600">Belum ada data booking</div>
            <div class="text-xs text-slate-400 mt-0.5">Booking pertama akan muncul di sini.</div>
        </div>
    <?php else: ?>
        <div class="dash-booking-mobile">
            <?php foreach ($recentBookings as $b):
                [$sLabel, $sClass] = status_booking($b['status']);
                $punyaNamaRute = !empty($b['rute']);
            ?>
                <article class="dash-mcard">
                    <div class="dash-mcard-top">
                        <div>
                            <div class="font-extrabold text-navy-900">#MT-<?= (int)$b['id'] ?></div>
                            <div class="text-[11px] font-bold text-slate-800 mt-0.5"><?= e($b['nama']) ?></div>
                        </div>
                        <span class="badge <?= $sClass ?>"><?= $sLabel ?></span>
                    </div>
                    <div class="text-[11px] text-slate-600 space-y-0.5">
                        <div><?= tgl_id($b['tanggal_berangkat']) ?> · <?= substr($b['jam_jemput'],0,5) ?> WIB</div>
                        <div class="font-semibold text-navy-700"><?= $punyaNamaRute ? e($b['rute']) : e(mb_strimwidth($b['alamat_tujuan'] ?? '', 0, 42, '...')) ?></div>
                        <div class="font-extrabold text-navy-900 pt-1"><?= rupiah($b['total_harga']) ?> · <?= (int)$b['jumlah_kursi'] ?> org</div>
                    </div>
                    <div class="flex gap-2 mt-3">
                        <a href="<?= BASE_URL ?>/admin/bookings.php?edit=<?= (int)$b['id'] ?>" class="btn-icon bg-navy-50 text-navy-800 border-navy-200" title="Edit"><i class="fa-solid fa-pen text-[11px]"></i></a>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $b['no_hp']) ?>" target="_blank" rel="noopener" class="btn-icon bg-emerald-50 text-emerald-700 border-emerald-200" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="dash-booking-desktop table-wrap is-sticky-col !rounded-none !border-0">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Tanggal</th>
                        <th>Nama Penumpang</th>
                        <th>Rute</th>
                        <th>Lokasi</th>
                        <th>Jadwal</th>
                        <th class="text-center">Kursi</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th class="no-print text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentBookings as $b):
                        [$sLabel, $sClass] = status_booking($b['status']);
                        [$srcLabel, $srcClass] = source_booking($b['source']);
                        $punyaNamaRute = !empty($b['rute']);
                        $namaRute = $punyaNamaRute ? e($b['rute']) : null;
                        $hargaPerOrang = !empty($b['harga_rute_saat_booking']) ? (int)$b['harga_rute_saat_booking'] : 0;
                        $lok = $b['lokasi_jemput'] ?? 'Blora';
                        $isBlora = $lok === 'Blora';
                        $lokClass = $isBlora ? 'bg-blue-50 border-blue-200 text-blue-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700';
                        $lokIcon = $isBlora ? 'fa-city' : 'fa-plane-departure';
                    ?>
                        <tr>
                            <td><span class="font-black text-navy-900 tracking-tight">#MT-<?= $b['id'] ?></span></td>
                            <td>
                                <div class="text-sm font-semibold text-slate-800"><?= tgl_id($b['tanggal_berangkat']) ?></div>
                                <div class="text-xs text-slate-500 mt-0.5"><i class="fa-regular fa-clock text-[9px]"></i> <?= substr($b['jam_jemput'],0,5) ?> WIB</div>
                            </td>
                            <td>
                                <div class="font-bold text-slate-900 text-sm"><?= e($b['nama']) ?></div>
                                <div class="text-xs text-slate-500 mt-0.5"><i class="fa-solid fa-phone text-[9px] text-navy-500"></i> <?= e($b['no_hp']) ?></div>
                                <div class="mt-1"><span class="badge <?= $srcClass ?> !py-0 !px-2 !text-[9px]"><?= $srcLabel ?></span></div>
                            </td>
                            <td>
                                <?php if ($punyaNamaRute): ?>
                                    <div class="inline-flex items-center gap-1.5 w-fit px-2.5 py-1 rounded-lg bg-cream-100 border border-gold-200">
                                        <i class="fa-solid fa-route text-[10px] text-gold-600"></i>
                                        <span class="text-xs font-bold text-navy-800"><?= $namaRute ?></span>
                                    </div>
                                    <?php if ($hargaPerOrang > 0): ?>
                                        <div class="text-[10px] font-bold text-gold-600 mt-1">@ <?= rupiah($hargaPerOrang) ?>/org</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-xs text-slate-600 leading-snug max-w-[220px]">
                                        <div class="mb-0.5"><?= e(mb_strimwidth($b['alamat_jemput'],0,40,'...')) ?></div>
                                        <div><?= e(mb_strimwidth($b['alamat_tujuan'],0,40,'...')) ?></div>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border text-xs font-black <?= $lokClass ?>">
                                    <i class="fa-solid <?= $lokIcon ?> text-[9px]"></i><?= e($lok) ?>
                                </span>
                                <?php if (!empty($b['maps_link'])): ?>
                                    <div class="mt-1">
                                        <a href="<?= e($b['maps_link']) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 underline">
                                            <i class="fa-solid fa-map-location-dot"></i> Maps
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($b['jadwal_jemput'])): ?>
                                    <div class="text-xs font-bold text-slate-800 max-w-[190px]"><?= e(mb_strimwidth($b['jadwal_jemput'], 0, 50, '...')) ?></div>
                                <?php else: ?>
                                    <div class="text-[10px] text-slate-400 italic">jadwal lama · <?= substr($b['jam_jemput'],0,5) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="inline-flex px-2.5 py-1 rounded-lg bg-cream-100 text-navy-800 font-black text-sm border border-cream-200"><?= $b['jumlah_kursi'] ?> <span class="font-medium text-[10px] ml-1 text-slate-500">org</span></span>
                            </td>
                            <td class="font-extrabold text-navy-800 whitespace-nowrap"><?= rupiah($b['total_harga']) ?></td>
                            <td><span class="badge <?= $sClass ?>"><?= $sLabel ?></span></td>
                            <td class="no-print text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="<?= BASE_URL ?>/admin/bookings.php?edit=<?= $b['id'] ?>" title="Edit booking" class="btn-icon bg-navy-50 text-navy-800 border-navy-200"><i class="fa-solid fa-pen text-[11px]"></i></a>
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $b['no_hp']) ?>" target="_blank" rel="noopener" title="Chat WA penumpang" class="btn-icon bg-emerald-50 text-emerald-700 border-emerald-200"><i class="fa-brands fa-whatsapp"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
