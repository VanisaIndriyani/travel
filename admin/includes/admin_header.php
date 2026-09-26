<?php
// =============================================
// ADMIN HEADER + SIDEBAR (UPGRADED PREMIUM - v3)
// File ini otomatis load di DASHBOARD, DATA BOOKINGS, dan LAPORAN
// NOTIFIKASI REAL + MOBILE NOTIF BUTTON + RESPONSIF HP TOTAL
// =============================================
if (!isset($admin_name)) die('auth_check.php required');
$active = $active_menu ?? 'dashboard';

// ============== QUERY NOTIFIKASI REAL (DUMMY 3 HAPUS!) ==============
$todayNotif = date('Y-m-d');
// Jumlah booking PENDING (yang butuh perhatian admin)
$stmtNotif1 = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
$stmtNotif1->execute();
$notifPending = (int)$stmtNotif1->fetchColumn();
// Jumlah booking HARI INI (semua status, aktivitas baru)
$stmtNotif2 = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = ?");
$stmtNotif2->execute([$todayNotif]);
$notifToday = (int)$stmtNotif2->fetchColumn();
// Total badge (prioritaskan pending, klo pending 0 pake today baru 0)
$notifBadgeTotal = max($notifPending, min(99, $notifPending + $notifToday));
// List 5 booking PENDING TERBARU buat ditampilkan di dropdown
$stmtNotif3 = $pdo->query("SELECT id, nama, rute, total_harga, created_at, lokasi_jemput, jadwal_jemput FROM bookings WHERE status='pending' ORDER BY created_at DESC LIMIT 5");
$notifList = $stmtNotif3 ? $stmtNotif3->fetchAll() : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= isset($page_title) ? e($page_title) . ' - ' : '' ?> Admin <?= SITE_NAME ?></title>
    <link rel="icon" type="image/jpeg" href="<?= BASE_URL ?>/logo.jpeg">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontSize: {
                        'xs':   ['0.7rem',  { lineHeight: '1rem' }],
                        'sm':   ['0.8rem',  { lineHeight: '1.2rem' }],
                        'base': ['0.9rem',  { lineHeight: '1.5rem' }],
                        'lg':   ['1rem',    { lineHeight: '1.6rem' }],
                        'xl':   ['1.15rem', { lineHeight: '1.75rem' }],
                        '2xl':  ['1.35rem', { lineHeight: '1.9rem' }],
                        '3xl':  ['1.65rem', { lineHeight: '2.2rem' }],
                        '4xl':  ['2.1rem',  { lineHeight: '2.6rem' }],
                        '5xl':  ['2.7rem',  { lineHeight: '3.1rem' }],
                    },
                    colors: {
                        primary: {50:'#F4F6F8',100:'#E2E8EE',200:'#C5D0DC',300:'#8A9BB0',400:'#5A7190',500:'#2F4A6E',600:'#1A3358',700:'#122544',800:'#0F2240',900:'#0A1628'},
                        gold: {50:'#FBF6E9',100:'#F5EBD0',200:'#E8D5A3',300:'#D4B56A',400:'#C9A227',500:'#B8952A',600:'#9A7B1F',700:'#7A6118'},
                        navy: {50:'#F4F6F8',100:'#E2E8EE',200:'#C5D0DC',500:'#2F4A6E',600:'#1A3358',700:'#122544',800:'#0F2240',900:'#0A1628',950:'#070F1C'},
                        cream: {50:'#FDFBF7',100:'#F7F3EB',200:'#EDE6D6',300:'#E0D4BC'},
                        darkblue:'#0A1628',
                    },
                    fontFamily: { sans: ['Plus Jakarta Sans','Inter','system-ui','Arial','sans-serif'], serif: ['Cormorant Garamond','Georgia','serif'] },
                    boxShadow: {
                        'soft':     '0 10px 40px -10px rgba(10, 22, 40, 0.15)',
                        'glow':     '0 0 40px -10px rgba(18, 37, 68, 0.45)',
                        'glow-gold':'0 0 30px -5px rgba(201,162,39,0.45)',
                        'card':     '0 18px 44px -28px rgba(10, 22, 40, 0.28)',
                        'card-lg':  '0 24px 60px -28px rgba(10, 22, 40, 0.35)',
                        'inner-soft':'inset 0 1px 0 0 rgba(255,255,255,0.06)',
                    }
                }
            }
        }
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('admin/assets/admin.css') ?>">
</head>
<body class="admin-body" :class="sidebar_open ? 'overflow-hidden lg:overflow-auto' : ''" x-data="{ sidebar_open: false, notif_open: false }" @keydown.escape.window="sidebar_open = false; notif_open = false">

<!-- ============ MOBILE TOPBAR (+ NOTIFIKASI HP!) ============ -->
<div class="lg:hidden no-print sticky top-0 z-40 admin-topbar flex items-center justify-between px-3 py-2.5">
    <div class="flex items-center gap-2.5">
        <button @click="sidebar_open = !sidebar_open"
                class="w-11 h-11 rounded-xl bg-navy-900 text-gold-200 hover:bg-navy-800 flex items-center justify-center transition active:scale-95 shrink-0"
                aria-label="Toggle menu">
            <i class="fa-solid text-base" :class="sidebar_open ? 'fa-xmark':'fa-bars'"></i>
        </button>
        <div class="flex items-center gap-2 min-w-0">
            <div class="relative shrink-0">
                <img src="<?= BASE_URL ?>/logo.jpeg" alt="Logo" class="w-8 h-8 rounded-lg object-cover border-2 border-white shadow-md">
                <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-gradient-to-br from-gold-400 to-gold-600 border-2 border-white flex items-center justify-center text-[7px] text-white shadow-gold-glow"><i class="fa-solid fa-crown"></i></span>
            </div>
            <div class="leading-tight min-w-0">
                <div class="text-[0.85rem] font-extrabold text-slate-900 truncate">
                    Mustika <span class="text-primary-700 italic font-serif">Travel</span>
                </div>
                <div class="text-[9px] text-slate-500 font-semibold">Admin</div>
            </div>
        </div>
    </div>
    <!-- NOTIFIKASI KHUSUS HP (SEBELUMNYA TIDAK ADA!) -->
    <div class="relative">
        <button @click="notif_open = !notif_open; sidebar_open=false" class="notif-btn" aria-label="Notifikasi">
            <i class="fa-regular fa-bell text-base"></i>
            <?php if ($notifBadgeTotal > 0): ?>
                <span class="notif-badge"><?= $notifBadgeTotal > 99 ? '99+' : $notifBadgeTotal ?></span>
            <?php endif; ?>
        </button>
        <div x-show="notif_open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
             @click.outside="notif_open = false"
             class="notif-drop">
            <div class="notif-head">
                <div class="min-w-0">
                    <h3><i class="fa-solid fa-bell text-gold-400"></i> Notifikasi</h3>
                    <p><?= $notifPending > 0 ? (int)$notifPending . ' booking menunggu' : 'Tidak ada booking pending'; ?> · <?= (int)$notifToday ?> hari ini</p>
                </div>
                <button type="button" @click="notif_open = false" class="notif-close" aria-label="Tutup"><i class="fa-solid fa-xmark text-xs"></i></button>
            </div>
            <div class="notif-list">
                <?php if (empty($notifList)): ?>
                    <div class="notif-empty">
                        <div class="notif-empty-ico"><i class="fa-solid fa-check"></i></div>
                        <b>Tidak ada pending</b>
                        <span>Belum ada booking yang menunggu konfirmasi.</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifList as $n):
                        $waktuLalu = time() - strtotime($n['created_at']);
                        if ($waktuLalu < 60) $waktuStr = "Baru saja";
                        elseif ($waktuLalu < 3600) $waktuStr = floor($waktuLalu/60) . " menit lalu";
                        elseif ($waktuLalu < 86400) $waktuStr = floor($waktuLalu/3600) . " jam lalu";
                        else $waktuStr = date('d/m', strtotime($n['created_at']));
                        $ruteSingkat = !empty($n['rute']) ? e(mb_strimwidth($n['rute'],0,26,'...')) : 'Rute custom';
                        $inisial = strtoupper(mb_substr(trim($n['nama'] ?? 'B'), 0, 1));
                    ?>
                        <a href="<?= BASE_URL ?>/admin/bookings.php?edit=<?= (int)$n['id'] ?>" @click="notif_open=false" class="notif-item">
                            <div class="notif-ava"><?= e($inisial) ?></div>
                            <div class="min-w-0 flex-1">
                                <div class="notif-item-top">
                                    <div class="notif-name"><?= e($n['nama']) ?> <span class="notif-code">#MT-<?= (int)$n['id'] ?></span></div>
                                    <span class="notif-time"><?= $waktuStr ?></span>
                                </div>
                                <div class="notif-rute"><?= $ruteSingkat ?></div>
                                <div class="notif-meta">
                                    <span class="notif-amt"><?= rupiah((int)$n['total_harga']) ?></span>
                                    <span class="notif-pill">Pending</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <a href="<?= BASE_URL ?>/admin/bookings.php?status=pending" @click="notif_open=false" class="notif-foot">
                Lihat semua booking pending <i class="fa-solid fa-arrow-right text-[10px] ml-1"></i>
            </a>
        </div>
    </div>
</div>

<div class="flex min-h-screen">
    <!-- ============ OVERLAY LATAR SIDEBAR MOBILE (KLIL = TUTUP SIDEBAR) ============ -->
    <div x-show="sidebar_open"
         x-cloak
         x-transition.opacity.duration.250ms
         @click="sidebar_open = false; notif_open=false"
         class="fixed inset-0 z-[45] bg-navy-950/55 backdrop-blur-[2px] cursor-pointer lg:hidden">
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 text-white/90 text-[10px] font-bold tracking-widest uppercase bg-black/40 backdrop-blur-md px-4 py-1.5 rounded-full border border-white/20">
            Ketuk area gelap untuk menutup
        </div>
    </div>

    <!-- ============ SIDEBAR ============ -->
    <aside
        :class="sidebar_open ? 'is-open' : ''"
        class="admin-sidebar no-print fixed lg:sticky top-0 left-0 h-screen z-50 flex flex-col sidebar-bg text-white shadow-card-lg">

        <!-- Logo Header Sidebar (Desktop) -->
        <div class="hidden lg:flex items-center gap-3.5 px-5 pt-6 pb-5 mb-1 border-b border-white/10 relative z-10">
            <div class="relative flex-shrink-0">
                <img src="<?= BASE_URL ?>/logo.jpeg" alt="Logo Mustika Travel"
                     onerror="this.onerror=null;this.style.background='linear-gradient(135deg,#2563eb,#1d4ed8)';this.innerHTML='<span style=\\'font-size:20px;color:#fff;font-weight:800\\'>MT</span>'"
                     class="w-13 h-13 w-[52px] h-[52px] rounded-2xl object-cover border-[3px] border-white/20 shadow-glow">
                <!-- crown badge -->
                <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-gradient-to-br from-gold-300 to-gold-600 flex items-center justify-center border-2 border-[#172554] shadow-gold-glow">
                    <i class="fa-solid fa-crown text-[10px] text-white"></i>
                </div>
            </div>
            <div class="leading-tight min-w-0">
                <div class="text-[1.1rem] font-extrabold tracking-tight">
                    Mustika <span class="italic font-serif text-gold-400">Travel</span>
                </div>
                <div class="text-[11px] text-blue-200/80 font-semibold flex items-center gap-1.5 mt-0.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.7)]"></span>
                    Dashboard Administrator
                </div>
            </div>
        </div>

        <!-- === HEADER SIDEBAR KHUSUS MOBILE: LOGO + TOMBOL CLOSE X BESAR (JELAS DILIHAT USER!) === -->
        <div class="flex lg:hidden items-center justify-between gap-3 px-4 pt-4 pb-3.5 mb-2 border-b border-white/10 relative z-10">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="relative shrink-0">
                    <img src="<?= BASE_URL ?>/logo.jpeg" alt="Logo" class="w-10 h-10 rounded-xl object-cover border-[2.5px] border-white/20 shadow-glow">
                    <span class="absolute -bottom-0.5 -right-0.5 w-4.5 h-4.5 rounded-full bg-gradient-to-br from-gold-300 to-gold-600 border-2 border-[#172554] flex items-center justify-center shadow-gold-glow">
                        <i class="fa-solid fa-crown text-[8px] text-white"></i>
                    </span>
                </div>
                <div class="leading-tight min-w-0">
                    <div class="text-[0.95rem] font-extrabold tracking-tight truncate">
                        Mustika <span class="italic font-serif text-gold-400">Travel</span>
                    </div>
                    <div class="text-[10px] text-blue-200/75 font-bold flex items-center gap-1 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Menu Admin
                    </div>
                </div>
            </div>
            <!-- ❌ TOMBOL TUTUP SIDEBAR UTAMA (MOBILE!) yang JELAS -->
            <button @click="sidebar_open = false; notif_open=false"
                    class="w-11 h-11 rounded-2xl bg-white/10 hover:bg-red-500/90 text-white flex items-center justify-center transition active:scale-90 border border-white/15 shrink-0"
                    aria-label="Tutup menu sidebar">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- MENU -->
        <nav class="flex-1 px-3 py-2 space-y-0.5 overflow-y-auto overflow-x-hidden relative z-10">
            <?php
            $menus = [
                ['dashboard','Dashboard',         'fa-gauge-high',         BASE_URL.'/admin/dashboard.php'],
                ['bookings', 'Data Booking',      'fa-calendar-days',      BASE_URL.'/admin/bookings.php'],
                ['rutes',    'Kelola Rute & Harga','fa-map-location-dot',  BASE_URL.'/admin/rutes.php'],
                ['armadas',  'Koleksi Armada',    'fa-van-shuttle',        BASE_URL.'/admin/armadas.php'],
                ['carters',  'Layanan Carter',    'fa-handshake',          BASE_URL.'/admin/carters.php'],
                ['laporan',  'Laporan Keuangan',  'fa-chart-column',       BASE_URL.'/admin/laporan.php'],
            ];
            ?>
            <div class="sidebar-group">Menu utama</div>
            <?php foreach ($menus as [$key,$label,$icon,$url]):
                $cls = ($active === $key) ? 'active' : '';
            ?>
                <a href="<?= $url ?>" class="sidebar-link <?= $cls ?>" @click="sidebar_open = false">
                    <span class="sidebar-ico"><i class="fa-solid <?= $icon ?>"></i></span>
                    <span class="sidebar-link-text"><?= $label ?></span>
                    <?php if ($active === $key): ?>
                        <i class="fa-solid fa-chevron-right text-[10px] text-gold-300 shrink-0"></i>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>

            <div class="sidebar-divider"></div>

            <div class="sidebar-group">Lainnya</div>
            <a href="<?= BASE_URL ?>/index.php" target="_blank" rel="noopener" class="sidebar-link" @click="sidebar_open = false">
                <span class="sidebar-ico"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
                <span class="sidebar-link-text">Lihat Website</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/logout.php"
               onclick="return confirm('Yakin ingin logout dari dashboard Mustika Travel?')"
               class="sidebar-link" style="color:#FECACA">
                <span class="sidebar-ico"><i class="fa-solid fa-right-from-bracket"></i></span>
                <span class="sidebar-link-text">Logout</span>
            </a>
        </nav>

        <!-- User info card (bottom sidebar) -->
        <div class="p-4 border-t border-white/10 relative z-10">
            <div class="flex items-center gap-3.5 px-3 py-3 rounded-2xl bg-white/5 backdrop-blur-sm border border-white/10 hover:bg-white/10 transition">
                <div class="relative flex-shrink-0">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-lg font-extrabold shadow-glow border-2 border-white/10">
                        <?= strtoupper(mb_substr($admin_name, 0, 1)) ?>
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full bg-emerald-500 border-2 border-[#172554] flex items-center justify-center text-[8px] text-white">
                        <i class="fa-solid fa-check"></i>
                    </span>
                </div>
                <div class="min-w-0 flex-1 leading-tight">
                    <div class="text-sm font-extrabold text-white truncate"><?= e($admin_name) ?></div>
                    <div class="text-[11px] text-blue-200/70 font-medium flex items-center gap-1.5">
                        <i class="fa-solid fa-id-card text-[10px]"></i>
                        Super Administrator
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- ============ MAIN CONTENT WRAPPER ============ -->
    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <!-- ============ DESKTOP TOP BAR ============ -->
        <header class="no-print admin-topbar px-6 lg:px-8 py-4 hidden lg:flex items-center justify-between sticky top-0 z-20">
            <div class="flex items-center gap-3 text-sm font-semibold text-slate-600">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-50 to-blue-100 text-primary-700 flex items-center justify-center shadow-sm">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
                <div class="leading-tight">
                    <div class="text-[11px] text-slate-400 font-bold uppercase tracking-wider">Hari Ini</div>
                    <div class="text-slate-900 font-extrabold"><?= nama_hari(date('Y-m-d')) ?>, <span class="text-primary-700"><?= tgl_id(date('Y-m-d')) ?></span></div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- Quick SEARCH AKTIF (submit ke bookings.php?q=) -->
                <?php
                $qGet = $_GET['q'] ?? '';
                $quickVal = !empty($qGet) ? trim($qGet) : '';
                ?>
                <form action="<?= BASE_URL ?>/admin/bookings.php" method="GET"
                      x-ref="quickSearchForm"
                      id="quickSearchForm"
                      class="hidden xl:flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100/80 w-[22rem] hover:bg-white hover:shadow-md hover:border hover:blue-100 border-0 transition group border border-transparent cursor-text">
                    <button type="submit" class="text-slate-400 group-focus-within:text-primary-600 transition cursor-pointer group-hover:text-primary-700" title="Klik untuk Cari / Enter">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </button>
                    <input
                        type="text" id="quickSearchInput" name="q" value="<?= e($quickVal) ?>"
                        placeholder="Cari Kode Booking (MT-4) / Nama / No HP / Rute"
                        class="bg-transparent outline-none flex-1 text-sm font-semibold placeholder:text-slate-400 text-slate-800"
                        x-ref="quickSearchInput"
                    >
                    <kbd @click.prevent="() => { $refs.quickSearchInput.focus(); $refs.quickSearchInput.select(); }"
                         class="text-[10px] font-extrabold bg-white px-1.5 py-0.5 rounded text-slate-500 border border-slate-200 cursor-pointer hover:bg-blue-50 hover:border-primary-300 select-none transition"
                         title="Shortcut: Tekan Ctrl+K / ⌘K untuk langsung cari cepat">
                        ⌘K
                    </kbd>
                </form>

                <!-- Global Keyboard SHORTCUT Ctrl+K (⌘K) = Focus ke quick search global -->
                <script>
                (function(){
                    // Shortcut Global: Ctrl+K (Windows/Linux) atau Cmd+K (Mac) = Fokus Quick Search
                    document.addEventListener('keydown', function(e){
                        const isMac = navigator.platform.toUpperCase().includes('MAC');
                        const shortcut = isMac ? (e.metaKey && !e.ctrlKey) : (e.ctrlKey && !e.metaKey);
                        if(shortcut && (e.key==='k' || e.key==='K')){
                            e.preventDefault();
                            const inp = document.getElementById('quickSearchInput');
                            if(inp){ inp.scrollIntoView({behavior:'smooth', block:'center'}); setTimeout(function(){inp.focus(); inp.select();},80); }
                        }
                    });
                })();
                </script>

                <!-- NOTIFIKASI REAL (BUKAN DUMMY LAGI!) -->
                <div class="relative">
                    <button @click="notif_open = !notif_open" class="notif-btn" title="Notifikasi" aria-label="Notifikasi">
                        <i class="fa-regular fa-bell text-lg"></i>
                        <?php if ($notifBadgeTotal > 0): ?>
                            <span class="notif-badge"><?= $notifBadgeTotal > 99 ? '99+' : $notifBadgeTotal ?></span>
                        <?php endif; ?>
                    </button>
                    <div x-show="notif_open" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                         @click.outside="notif_open = false"
                         class="notif-drop">
                        <div class="notif-head">
                            <div class="min-w-0">
                                <h3><i class="fa-solid fa-bell text-gold-400"></i> Notifikasi</h3>
                                <p><?= $notifPending > 0 ? (int)$notifPending . ' booking menunggu' : 'Tidak ada booking pending'; ?> · <?= (int)$notifToday ?> hari ini</p>
                            </div>
                            <button type="button" @click="notif_open = false" class="notif-close" aria-label="Tutup"><i class="fa-solid fa-xmark text-xs"></i></button>
                        </div>
                        <div class="notif-list">
                            <?php if (empty($notifList)): ?>
                                <div class="notif-empty">
                                    <div class="notif-empty-ico"><i class="fa-solid fa-check"></i></div>
                                    <b>Tidak ada pending</b>
                                    <span>Belum ada booking yang menunggu konfirmasi.</span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifList as $n):
                                    $waktuLalu = time() - strtotime($n['created_at']);
                                    if ($waktuLalu < 60) $waktuStr = "Baru saja";
                                    elseif ($waktuLalu < 3600) $waktuStr = floor($waktuLalu/60) . " menit lalu";
                                    elseif ($waktuLalu < 86400) $waktuStr = floor($waktuLalu/3600) . " jam lalu";
                                    else $waktuStr = date('d/m', strtotime($n['created_at']));
                                    $ruteSingkat2 = !empty($n['rute']) ? e(mb_strimwidth($n['rute'],0,30,'...')) : 'Rute custom';
                                    $inisial = strtoupper(mb_substr(trim($n['nama'] ?? 'B'), 0, 1));
                                ?>
                                    <a href="<?= BASE_URL ?>/admin/bookings.php?edit=<?= (int)$n['id'] ?>" @click="notif_open=false" class="notif-item">
                                        <div class="notif-ava"><?= e($inisial) ?></div>
                                        <div class="min-w-0 flex-1">
                                            <div class="notif-item-top">
                                                <div class="notif-name"><?= e($n['nama']) ?> <span class="notif-code">#MT-<?= (int)$n['id'] ?></span></div>
                                                <span class="notif-time"><?= $waktuStr ?></span>
                                            </div>
                                            <div class="notif-rute"><?= $ruteSingkat2 ?></div>
                                            <div class="notif-meta">
                                                <span class="notif-amt"><?= rupiah((int)$n['total_harga']) ?></span>
                                                <span class="notif-pill">Pending</span>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <a href="<?= BASE_URL ?>/admin/bookings.php?status=pending" @click="notif_open=false" class="notif-foot">
                            Lihat semua booking pending <i class="fa-solid fa-arrow-right text-[10px] ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Profile pill -->
                <div class="flex items-center gap-3 pl-3 pr-2 py-1.5 rounded-2xl bg-white border border-slate-200 shadow-sm hover:shadow-md transition cursor-pointer">
                    <div class="text-right leading-tight hidden md:block">
                        <div class="text-sm font-extrabold text-slate-900"><?= e($admin_name) ?></div>
                        <div class="text-[11px] text-gold-600 font-bold flex items-center justify-end gap-1">
                            <i class="fa-solid fa-crown text-[10px]"></i> Owner
                        </div>
                    </div>
                    <div class="relative">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white font-extrabold shadow-md">
                            <?= strtoupper(mb_substr($admin_name, 0, 1)) ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- ============ CONTENT AREA ============ -->
        <div class="main-content flex-1 p-4 sm:p-6 lg:p-8 max-w-[1600px] w-full mx-auto overflow-x-hidden">
