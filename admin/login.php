<?php
require_once __DIR__ . '/../includes/helpers.php';

if (!empty($_SESSION['admin_id'])) {
    redirect(BASE_URL . '/admin/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Login Admin - <?= SITE_NAME ?></title>
    <link rel="icon" type="image/jpeg" href="<?= BASE_URL ?>/logo.jpeg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {50:'#F4F6F8',100:'#E2E8EE',500:'#2F4A6E',600:'#1A3358',700:'#122544',800:'#0F2240',900:'#0A1628'},
                        gold: {50:'#FBF6E9',200:'#E8D5A3',300:'#D4B56A',400:'#C9A227',500:'#B8952A',600:'#9A7B1F'},
                        navy: {700:'#122544',800:'#0F2240',900:'#0A1628'},
                        cream: {50:'#FDFBF7',100:'#F7F3EB'},
                    },
                    fontFamily: { sans: ['Plus Jakarta Sans','system-ui','sans-serif'], serif: ['Cormorant Garamond','Georgia','serif'] }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/admin.css">
</head>
<body class="admin-body">

    <div class="login-shell">
        <aside class="login-brand">
            <div>
                <div class="flex items-center gap-3 mb-10">
                    <img src="<?= BASE_URL ?>/logo.jpeg" alt="Logo" class="w-14 h-14 rounded-2xl object-cover border-2 border-gold-400/40 shadow-glow-gold">
                    <div>
                        <div class="text-xl font-extrabold tracking-tight">Mustika <span class="italic font-serif text-gold-300">Travel</span></div>
                        <div class="text-[11px] uppercase tracking-[0.18em] text-gold-200/80 font-semibold">Administrator</div>
                    </div>
                </div>
                <p class="text-[11px] uppercase tracking-[0.22em] text-gold-300 font-semibold mb-3">Panel Internal</p>
                <h2 class="font-serif text-4xl leading-tight text-white mb-4">Kelola perjalanan<br>dengan tenang.</h2>
                <p class="text-blue-100/75 text-sm leading-relaxed max-w-sm">Booking, rute, armada, carter, dan laporan keuangan — satu dashboard yang rapi untuk operasional harian Mustika Travel.</p>
            </div>
            <div class="text-xs text-blue-200/60 flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                <?= SITE_TAGLINE ?>
            </div>
        </aside>

        <main class="login-panel">
            <div class="login-card">
                <div class="flex flex-col items-center mb-6 text-center">
                    <img src="<?= BASE_URL ?>/logo.jpeg" alt="Logo" class="w-14 h-14 rounded-xl object-cover border-2 border-gold-200 shadow-sm mb-3 md:hidden">
                    <p class="text-[11px] uppercase tracking-[0.2em] text-gold-600 font-bold mb-1">Masuk Dashboard</p>
                    <h1 class="text-2xl font-extrabold text-navy-900">Login Admin</h1>
                    <p class="text-xs text-slate-500 mt-1"><?= SITE_NAME ?></p>
                </div>

                <?php
                $flash = get_flash();
                if ($flash) echo '<div class="mb-5">' . $flash . '</div>';
                ?>

                <form action="<?= BASE_URL ?>/admin/proses_login.php" method="POST" x-data="{ showPw:false }" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div>
                        <label class="form-label">Username</label>
                        <div class="flex items-center gap-2.5 px-3.5 rounded-xl bg-cream-50 border border-slate-200 focus-within:border-gold-400 focus-within:ring-4 focus-within:ring-gold-200/50 transition">
                            <i class="fa-solid fa-user text-gold-500 text-sm w-4"></i>
                            <input type="text" name="username" required autofocus
                                   value="<?= e(old('username')) ?>"
                                   placeholder="Masukkan username"
                                   class="flex-1 bg-transparent outline-none text-sm text-slate-900 placeholder:text-slate-400 py-3 min-h-[44px]">
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Password</label>
                        <div class="flex items-center gap-2.5 px-3.5 rounded-xl bg-cream-50 border border-slate-200 focus-within:border-gold-400 focus-within:ring-4 focus-within:ring-gold-200/50 transition">
                            <i class="fa-solid fa-lock text-gold-500 text-sm w-4"></i>
                            <input :type="showPw ? 'text':'password'" name="password" required
                                   placeholder="Masukkan password"
                                   class="flex-1 bg-transparent outline-none text-sm text-slate-900 placeholder:text-slate-400 py-3 min-h-[44px]">
                            <button type="button" @click="showPw = !showPw" class="text-slate-400 hover:text-navy-700 text-sm w-10 h-10">
                                <i class="fa-solid" :class="showPw ? 'fa-eye-slash':'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-success w-full py-3 text-sm">
                        <i class="fa-solid fa-right-to-bracket"></i> Login
                    </button>
                </form>

                <div class="mt-5 pt-4 border-t border-slate-100 text-center">
                    <a href="<?= BASE_URL ?>/index.php" class="text-xs text-slate-500 hover:text-navy-800 transition font-medium inline-flex items-center gap-1.5 min-h-[44px]">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke halaman utama
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
    <?php clear_old(); ?>
</body>
</html>
