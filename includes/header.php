<?php
// =============================================
// HEADER FRONTEND - dipakai di halaman utama index.php
// =============================================
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="description" content="<?= SITE_FULL_NAME ?> - Layanan travel door to door terpercaya. Booking tiket online mudah & cepat.">
    <title><?= SITE_NAME ?> - Travel Door to Door Terpercaya</title>

    <!-- Favicon pakai logo project -->
    <link rel="icon" type="image/jpeg" href="<?= BASE_URL ?>/logo.jpeg">

    <!-- ==== Tailwind CSS CDN (styling cepat modern) ==== -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontSize: {
                        'xs':   ['0.72rem', { lineHeight: '1.05rem' }],
                        'sm':   ['0.83rem', { lineHeight: '1.25rem' }],
                        'base': ['0.94rem', { lineHeight: '1.55rem' }],
                        'lg':   ['1.06rem', { lineHeight: '1.7rem' }],
                        'xl':   ['1.22rem', { lineHeight: '1.85rem' }],
                        '2xl':  ['1.45rem', { lineHeight: '2rem' }],
                        '3xl':  ['1.8rem',  { lineHeight: '2.4rem' }],
                        '4xl':  ['2.35rem', { lineHeight: '2.8rem' }],
                        '5xl':  ['3rem',    { lineHeight: '3.4rem' }],
                    },
                    colors: {
                        primary:    { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a'},
                        secondary:  { 50:'#ecfeff',100:'#cffafe',200:'#a5f3fc',300:'#67e8f9',400:'#22d3ee',500:'#06b6d4',600:'#0891b2'},
                        mustard:    { 400:'#fbbf24',500:'#f59e0b',600:'#d97706'},
                        darkblue:   '#002D62',
                        gold: {
                            50:'#FBF6E9',100:'#F5EBD0',200:'#E8D5A3',300:'#D4B56A',
                            400:'#C9A227',500:'#B8952A',600:'#9A7B1F',700:'#7A6118',
                            800:'#5C4912',900:'#3D300C'
                        },
                        navy: {
                            50:'#F4F6F8',100:'#E2E8EE',200:'#C5D0DC',300:'#8A9BB0',
                            400:'#5A7190',500:'#2F4A6E',600:'#1A3358',700:'#122544',
                            800:'#0F2240',900:'#0A1628',950:'#070F1C'
                        },
                        cream: {
                            50:'#FDFBF7',100:'#F7F3EB',200:'#EDE6D6',300:'#E0D4BC',
                            400:'#C9B896',500:'#A8926A'
                        },
                    },
                    fontFamily: {
                        sans:  ['Plus Jakarta Sans', 'Inter', 'system-ui', 'Arial', 'sans-serif'],
                        serif: ['Cormorant Garamond', 'Playfair Display', 'Georgia', 'serif'],
                    },
                    boxShadow: {
                        'soft': '0 10px 40px -10px rgba(37, 99, 235, 0.15)',
                        'glow': '0 0 30px -5px rgba(59, 130, 246, 0.3)',
                        'card': '0 4px 20px -2px rgba(0, 0, 0, 0.08)',
                        'lux':  '0 24px 60px -28px rgba(10, 22, 40, 0.35)',
                        'gold-glow': '0 12px 28px -10px rgba(184, 149, 42, 0.45)',
                    },
                    animation: {
                        'float': 'float 5s ease-in-out infinite',
                        'fade-in': 'fadeIn 0.7s ease-out',
                    },
                    keyframes: {
                        float: {
                            '0%,100%': { transform: 'translateY(0px)' },
                            '50%':     { transform: 'translateY(-12px)' },
                        },
                        fadeIn: {
                            '0%':   { opacity: 0, transform: 'translateY(14px)' },
                            '100%': { opacity: 1, transform: 'translateY(0)' },
                        }
                    }
                }
            }
        }
    </script>

    <!-- ==== Font Awesome CDN (icon) ==== -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- ==== Google Font ==== -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- ==== Alpine.js CDN (interaktif modal, menu, flash) ==== -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>

    <!-- ==== Custom CSS Global ==== -->
    <style>
        html { scroll-behavior: smooth; font-size: 15px; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            background: #FDFBF7;
            color: #1e293b;
        }
        ::selection { background: #E8D5A3; color: #0A1628; }
        :focus-visible {
            outline: 2px solid #C9A227;
            outline-offset: 3px;
        }
        .font-serif { font-family: 'Cormorant Garamond', Georgia, serif; }
        .hero-gradient { background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 45%, #ffffff 100%); }
        .footer-gradient { background: linear-gradient(160deg, #0A1628 0%, #122544 55%, #0F2240 100%); }
        .btn-primary {
            background: linear-gradient(135deg, #D4B56A 0%, #C9A227 48%, #9A7B1F 100%);
            color: #0A1628;
            font-weight: 700;
            border-radius: 0.75rem;
            transition: all 0.25s ease;
            box-shadow: 0 10px 24px -10px rgba(184, 149, 42, 0.55);
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 14px 28px -10px rgba(184, 149, 42, 0.65); }
        .btn-primary:active { transform: translateY(0); }
        .card {
            background: #fff;
            border-radius: 1.15rem;
            box-shadow: 0 18px 44px -28px rgba(10, 22, 40, 0.28);
            border: 1px solid rgba(200, 184, 150, 0.28);
        }
        .chip { display: inline-flex; align-items: center; padding: 0.28rem 0.7rem; border-radius: 9999px; font-size: 0.78rem; font-weight: 600;}
        .shine-btn { position: relative; overflow: hidden; }
        .shine-btn::after {
            content: ''; position: absolute; top: -50%; left: -60%;
            width: 40%; height: 200%;
            background: linear-gradient(120deg, transparent, rgba(255,255,255,.38), transparent);
            transform: rotate(20deg); transition: all .8s ease;
            pointer-events: none;
        }
        .shine-btn:hover::after { left: 130%; }
        .gold-divider {
            background: linear-gradient(90deg, transparent 0%, rgba(201,162,39,.35) 28%, rgba(201,162,39,.9) 50%, rgba(201,162,39,.35) 72%, transparent 100%);
        }
        .lux-eyebrow {
            letter-spacing: 0.22em;
            text-transform: uppercase;
            font-weight: 600;
            font-size: 0.68rem;
        }
        .input-field {
            width: 100%;
            padding: 0.78rem 1rem;
            border: 2px solid #cbd5e1;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
            border-bottom-left-radius: 16px;
            border-bottom-right-radius: 16px;
            background: #ffffff;
            background-clip: padding-box;
            transition: all .22s ease;
            font-size: 0.9rem;
            color: #0f172a;
            box-sizing: border-box;
            display: block;
            -webkit-appearance: none;
            appearance: none;
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            transform: translateZ(0);
        }
        .input-field::placeholder {
            color: #94a3b8;
            font-weight: 500;
        }
        .input-field:hover {
            border-color: #D4B56A;
        }
        .input-field:focus {
            outline: none !important;
            outline-offset: 0;
            border-color: #C9A227;
            box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.18), 0 10px 24px -14px rgba(10, 22, 40, 0.18);
            background: #FDFBF7;
        }
        select.input-field {
            background-color: #ffffff;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23475569' stroke-width='2.2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 1rem auto;
            padding-right: 2.8rem;
        }
        textarea.input-field {
            line-height: 1.55;
        }
        label.form-label { display:block; font-weight: 700; font-size: 0.85rem; color: #1e293b; margin-bottom: 0.45rem; padding-left: 2px; }
        .wave-bottom { position: absolute; bottom: -1px; left: 0; width: 100%; overflow: hidden; line-height: 0;}
        .nav-link {
            position: relative;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            left: 1rem; right: 1rem; bottom: 0.35rem;
            height: 1px;
            background: #C9A227;
            transform: scaleX(0);
            transform-origin: center;
            transition: transform .25s ease;
        }
        .nav-link:hover::after,
        .nav-link:focus-visible::after,
        .nav-link.is-active::after { transform: scaleX(1); }
        .nav-link.is-active {
            color: #0A1628;
            background: rgba(201, 162, 39, 0.14);
        }
        #mobile-nav a.is-active {
            background: rgba(201, 162, 39, 0.16);
            color: #0A1628;
        }
        a.btn-primary.is-active {
            box-shadow: 0 0 0 2px #FDFBF7, 0 0 0 4px #C9A227, 0 12px 28px -10px rgba(184, 149, 42, 0.65);
        }
    </style>
</head>
<body class="bg-cream-50 text-slate-800">

<a href="#beranda" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] focus:bg-navy-900 focus:text-cream-100 focus:px-4 focus:py-2 focus:rounded-lg">
    Loncat ke konten
</a>

<!-- ============ NAVBAR ============ -->
<nav x-data="{ open: false, scrolled: false }"
     @scroll.window="scrolled = (window.pageYOffset > 20)"
     :class="scrolled ? 'bg-cream-50/95 shadow-[0_10px_30px_-18px_rgba(10,22,40,0.35)] backdrop-blur-md border-gold-200/70' : 'bg-cream-50/80 backdrop-blur-md border-cream-200/80'"
     class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 md:h-20">
            <!-- Logo -->
            <a href="<?= BASE_URL ?>" class="flex items-center gap-2 md:gap-3 group">
                <img src="<?= BASE_URL ?>/logo.jpeg" alt="Logo Mustika Travel"
                     class="h-10 w-10 md:h-12 md:w-12 rounded-full object-cover shadow-md border border-gold-300/70 ring-1 ring-gold-200"
                     width="48" height="48" decoding="async">
                <div class="leading-tight">
                    <div class="text-xl md:text-[1.65rem] font-semibold tracking-tight">
                        <span class="text-navy-900">Mustika</span>
                        <span class="text-gold-600 italic font-serif">Travel</span>
                    </div>
                </div>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden lg:flex items-center gap-0.5">
                <?php
                $currentScript = basename(parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?: '');
                $isBookingPage = ($currentScript === 'booking.php');
                $nav = [
                    [BASE_URL,                     'Beranda',  'fa-house',            'beranda'],
                    [BASE_URL . '/#jurusan',       'Jurusan',  'fa-bus',               'jurusan'],
                    [BASE_URL . '/#jadwal',        'Jadwal',   'fa-clock',             'jadwal'],
                    [BASE_URL . '/booking.php',    'Pemesanan','fa-calendar-check',    'pemesanan'],
                    [BASE_URL . '/#layanan',       'Layanan',  'fa-shield-halved',     'layanan'],
                    [BASE_URL . '/#armada',        'Armada',   'fa-van-shuttle',       'armada'],
                    [BASE_URL . '/#carter',        'Carter',   'fa-truck-pickup',      'carter'],
                    [BASE_URL . '/#kontak',        'Kontak',   'fa-phone',             'kontak'],
                ];
                foreach ($nav as [$href, $label, $icon, $navKey]):
                    $navActive = $isBookingPage && $navKey === 'pemesanan';
                ?>
                    <a href="<?= $href ?>"
                       data-nav="<?= $navKey ?>"
                       class="nav-link flex items-center gap-2 px-3.5 py-2 rounded-xl text-navy-700 hover:text-navy-900 font-semibold text-[13px] tracking-wide transition<?= $navActive ? ' is-active' : '' ?>"
                       <?= $navActive ? 'aria-current="page"' : '' ?>>
                        <i class="fa-solid <?= $icon ?> text-[11px] text-gold-600"></i> <?= $label ?>
                    </a>
                <?php endforeach; ?>
                <a href="<?= BASE_URL ?>/booking.php"
                   data-nav="booking"
                   class="btn-primary shine-btn ml-3 px-5 py-2.5 text-sm flex items-center gap-2<?= $isBookingPage ? ' is-active' : '' ?>"
                   <?= $isBookingPage ? 'aria-current="page"' : '' ?>>
                    <i class="fa-solid fa-ticket"></i> Booking Sekarang
                </a>
            </div>

            <!-- Hamburger Mobile -->
            <button @click="open = !open"
                    class="lg:hidden p-2 rounded-lg hover:bg-cream-200 text-navy-800"
                    :aria-expanded="open.toString()"
                    aria-controls="mobile-nav"
                    aria-label="Menu">
                <i class="fa-solid text-xl" :class="open ? 'fa-xmark' : 'fa-bars'"></i>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-nav" x-show="open" x-transition @click.outside="open = false"
             class="lg:hidden pb-4 -mt-1 space-y-1">
            <?php foreach ($nav as [$href, $label, $icon, $navKey]):
                $navActive = $isBookingPage && $navKey === 'pemesanan';
            ?>
                <a href="<?= $href ?>" @click="open = false"
                   data-nav="<?= $navKey ?>"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-navy-800 hover:bg-gold-50 hover:text-navy-900<?= $navActive ? ' is-active' : '' ?>"
                   <?= $navActive ? 'aria-current="page"' : '' ?>>
                    <i class="fa-solid <?= $icon ?> w-5 text-gold-600"></i> <?= $label ?>
                </a>
            <?php endforeach; ?>
            <a href="<?= BASE_URL ?>/booking.php" @click="open = false"
               data-nav="booking"
               class="btn-primary shine-btn w-full px-5 py-3 mt-2 justify-center flex items-center gap-2<?= $isBookingPage ? ' is-active' : '' ?>"
               <?= $isBookingPage ? 'aria-current="page"' : '' ?>>
                <i class="fa-solid fa-ticket"></i> Booking Sekarang
            </a>
        </div>
    </div>
</nav>
<div class="h-16 md:h-20"></div> <!-- spacer for fixed navbar -->
