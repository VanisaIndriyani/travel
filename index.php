<?php
// =============================================
// HALAMAN UTAMA - LANDING PAGE MUSTIKA TRAVEL
// =============================================
require_once __DIR__ . '/includes/header.php';
global $DAFTAR_JURUSAN;
$listRuteAktif = get_rutes(true);
?>
<style>
    .armada-card-img { transition: transform .7s ease; }
    .armada-card:hover .armada-card-img { transform: scale(1.06); }
    .hero-photo {
        background-color: #070F1C;
        object-fit: cover;
        object-position: center 70%;
    }
    .hero-copy {
        position: relative;
    }
    .hero-copy::before {
        content: '';
        position: absolute;
        inset: -10% -8%;
        z-index: -1;
        pointer-events: none;
        background: radial-gradient(ellipse 70% 62% at 50% 40%, rgba(0,0,0,.36) 0%, rgba(0,0,0,.16) 52%, transparent 78%);
    }
    .hero-title {
        text-shadow: 0 2px 20px rgba(0,0,0,.78), 0 1px 4px rgba(0,0,0,.55);
    }
    .hero-body {
        text-shadow: 0 2px 16px rgba(0,0,0,.72), 0 1px 3px rgba(0,0,0,.5);
    }
    @media (prefers-reduced-motion: reduce) {
        .armada-card-img, .armada-card:hover .armada-card-img { transform: none; }
    }

    /* Carter: 2 kolom + kartu kompak di HP, layout lebar tetap di tablet/desktop */
    #carter .carter-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.625rem;
    }
    @media (min-width: 640px) {
        #carter .carter-grid { gap: 1.25rem; }
    }
    @media (min-width: 1024px) {
        #carter .carter-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    @media (max-width: 639px) {
        #carter .carter-card { border-radius: 1rem; }
        #carter .carter-card-photo { height: 5.5rem; }
        #carter .carter-card-badge-wrap {
            left: 0.4rem;
            bottom: 0.4rem;
        }
        #carter .carter-card-badge {
            font-size: 9px;
            padding: 0.15rem 0.4rem;
            gap: 0.25rem;
        }
        #carter .carter-card-body { padding: 0.55rem 0.6rem 0.7rem; }
        #carter .carter-card-icon { display: none; }
        #carter .carter-card-title {
            font-size: 0.82rem;
            line-height: 1.25;
            margin-bottom: 0.2rem;
        }
        #carter .carter-card-desc {
            font-size: 10px;
            line-height: 1.35;
            margin-bottom: 0.45rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-clamp: 2;
            overflow: hidden;
        }
        #carter .carter-card-fac-label { display: none; }
        #carter .carter-card-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            max-height: 2.35rem;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        #carter .carter-card-chips span {
            font-size: 8px;
            padding: 0.1rem 0.35rem;
            line-height: 1.3;
        }
        #carter .carter-card-btn {
            padding: 0.4rem 0.35rem;
            font-size: 10px;
            border-radius: 0.6rem;
            gap: 0.3rem;
        }
    }

    /* Jadwal: 2 kolom side-by-side sejak HP */
    #jadwal .jadwal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.625rem;
    }
    @media (min-width: 640px) {
        #jadwal .jadwal-grid { gap: 1.25rem; }
    }
    @media (min-width: 768px) {
        #jadwal .jadwal-grid { gap: 1.75rem; }
    }
    @media (max-width: 639px) {
        #jadwal .jadwal-card { border-radius: 1rem; padding: 0.75rem; }
        #jadwal .jadwal-chip {
            padding: 0.45rem 0.55rem;
            gap: 0.45rem;
            border-radius: 0.65rem;
        }
        #jadwal .jadwal-chip .w-8 { width: 1.6rem; height: 1.6rem; border-radius: 0.45rem; }
        #jadwal .jadwal-chip .font-serif { font-size: 0.95rem; }
    }

    /* Armada: 2 kolom kompak di HP, 2 kolom tablet, 3 kolom desktop */
    #armada .armada-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.625rem;
    }
    @media (min-width: 640px) {
        #armada .armada-grid { gap: 1.5rem; }
    }
    @media (min-width: 1280px) {
        #armada .armada-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1.75rem;
        }
    }
    @media (max-width: 639px) {
        #armada .fleet-card { border-radius: 1rem; }
        #armada .fleet-card-photo { height: 6.25rem; }
        #armada .fleet-card-badge-wrap {
            left: 0.4rem;
            bottom: 0.4rem;
        }
        #armada .fleet-card-badge {
            font-size: 9px;
            padding: 0.15rem 0.4rem;
            gap: 0.25rem;
        }
        #armada .fleet-card-body { padding: 0.55rem 0.6rem 0.7rem; }
        #armada .fleet-card-icon { display: none; }
        #armada .fleet-card-title {
            font-size: 0.82rem;
            line-height: 1.25;
            margin-bottom: 0.2rem;
        }
        #armada .fleet-card-desc {
            font-size: 10px;
            line-height: 1.35;
            margin-bottom: 0.45rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-clamp: 2;
            overflow: hidden;
        }
        #armada .fleet-card-fac-label { display: none; }
        #armada .fleet-card-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            max-height: 2.35rem;
            overflow: hidden;
        }
        #armada .fleet-card-chips span {
            font-size: 8px;
            padding: 0.1rem 0.35rem;
            line-height: 1.3;
        }
    }

    /* Layanan door-to-door: 2 kolom kompak di HP, 2 kolom tetap di tablet+ */
    #layanan .layanan-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.625rem;
        margin-bottom: 2rem;
    }
    @media (min-width: 640px) {
        #layanan .layanan-grid { gap: 1rem; }
    }
    @media (max-width: 639px) {
        #layanan .layanan-card { padding: 0.7rem 0.7rem 0.75rem; }
        #layanan .layanan-card-icon {
            width: 1.85rem;
            height: 1.85rem;
            font-size: 0.7rem;
            border-radius: 0.55rem;
            margin-bottom: 0.45rem;
        }
        #layanan .layanan-card-title {
            font-size: 0.72rem;
            line-height: 1.25;
            margin-bottom: 0.2rem;
        }
        #layanan .layanan-card-desc {
            font-size: 9.5px;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            line-clamp: 4;
            overflow: hidden;
        }
    }

    /* Jurusan: 2 kolom kompak di HP, 2 kolom tetap di tablet+ */
    #jurusan .jurusan-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.625rem;
    }
    @media (min-width: 768px) {
        #jurusan .jurusan-grid { gap: 1rem; }
    }
    @media (max-width: 639px) {
        #jurusan .jurusan-card { padding: 0.7rem 0.65rem 0.75rem; border-radius: 0.85rem; }
        #jurusan .jurusan-card-top { gap: 0.4rem; align-items: flex-start; }
        #jurusan .jurusan-card-icon {
            width: 1.7rem;
            height: 1.7rem;
            border-radius: 0.5rem;
            font-size: 0.65rem;
        }
        #jurusan .jurusan-card-route {
            flex-wrap: wrap;
            font-size: 0.7rem;
            gap: 0.2rem 0.3rem;
        }
        #jurusan .jurusan-card-meta {
            margin-top: 0.55rem;
            gap: 0.35rem;
            flex-wrap: wrap;
        }
        #jurusan .jurusan-card-avail {
            font-size: 9px;
            gap: 0.25rem;
        }
        #jurusan .jurusan-card-avail span { display: none; }
        #jurusan .jurusan-card-price {
            font-size: 10px;
            padding: 0.2rem 0.45rem;
        }
    }
</style>

<!-- ============ SECTION 1: HERO ============ -->
<section id="beranda" class="relative overflow-hidden min-h-[640px] md:min-h-[760px] flex items-center bg-[#070F1C]">
    <img src="<?= BASE_URL ?>/mob1.jpeg"
         alt="Armada Mustika Travel"
         class="hero-photo absolute inset-0 z-0 w-full h-full object-cover object-[center_70%]"
         width="1600" height="2400"
         fetchpriority="high"
         decoding="async"
         onerror="this.onerror=null;this.removeAttribute('src');">
    <div class="absolute inset-x-0 bottom-0 h-28 z-0 pointer-events-none bg-gradient-to-t from-cream-50/55 to-transparent"></div>
    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-400/40 to-transparent z-10"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20 w-full pt-10 pb-28 md:pt-16 md:pb-36">
        <div class="animate-fade-in hero-copy mx-auto max-w-3xl text-center">
            <div class="inline-flex items-center justify-center gap-3 mb-5">
                <span class="hidden sm:block h-px w-10 bg-gold-300/80"></span>
                <p class="lux-eyebrow text-gold-300 hero-body">Travel terpercaya sejak 2020</p>
                <span class="hidden sm:block h-px w-10 bg-gold-300/80"></span>
            </div>

            <h1 class="hero-title mb-5 md:mb-6">
                <span class="block font-serif text-[2.7rem] md:text-5xl lg:text-[3.65rem] font-semibold leading-[1.08] tracking-tight text-cream-50">
                    Mustika <span class="italic text-gold-300">Travel</span>
                </span>
                <span class="mt-3 block font-serif text-[1.75rem] md:text-3xl lg:text-[2.2rem] font-medium text-cream-50 leading-snug">
                    Perjalanan yang terasa istimewa
                </span>
            </h1>

            <p class="hero-body text-[15px] md:text-base text-cream-50 mb-6 leading-relaxed max-w-xl mx-auto">
                Layanan travel <strong class="text-white font-semibold">door-to-door</strong> dengan armada terawat.
                Kami <span class="text-gold-300 font-semibold">jemput di rumah</span>, antar sampai alamat tujuan.
            </p>

            <div class="flex flex-wrap gap-2 mb-7 justify-center">
                <span class="px-3 py-1.5 rounded-full border border-gold-300 bg-white text-navy-900 text-[11px] font-semibold tracking-wide shadow-sm">Bus Pariwisata</span>
                <span class="px-3 py-1.5 rounded-full border border-cream-200 bg-white text-navy-900 text-[11px] font-semibold tracking-wide shadow-sm">Hiace</span>
                <span class="px-3 py-1.5 rounded-full border border-cream-200 bg-white text-navy-900 text-[11px] font-semibold tracking-wide shadow-sm">Avanza</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-8">
                <?php
                $badges = [
                    ['fa-shield-halved',  'Aman Terpercaya'],
                    ['fa-couch',          'Armada Nyaman'],
                    ['fa-clock',          'Tepat Waktu'],
                    ['fa-sack-dollar',    'Harga Bersahabat'],
                ];
                foreach ($badges as [$ico, $lbl]): ?>
                    <div class="border border-cream-200 rounded-full bg-white shadow-sm py-2.5 px-2.5 text-center text-[12px] font-semibold text-navy-900">
                        <i class="fa-solid <?= $ico ?> mr-1.5 text-gold-600 text-[11px]"></i><?= $lbl ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="flex flex-col sm:flex-row w-full sm:w-auto gap-3 mb-6 justify-center">
                <a href="<?= BASE_URL ?>/booking.php" class="shine-btn w-full sm:w-auto inline-flex items-center justify-center gap-2.5 font-bold px-7 py-3.5 rounded-xl bg-gradient-to-r from-gold-300 via-gold-400 to-gold-600 text-navy-900 shadow-gold-glow hover:-translate-y-0.5 transition">
                    <i class="fa-solid fa-ticket-simple"></i> Booking Tiket Sekarang
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', WA_ADMIN) ?>"
                   target="_blank" rel="noopener"
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-xl border border-navy-800/15 bg-white text-navy-900 font-semibold hover:border-gold-500/50 hover:bg-cream-50 transition shadow-sm">
                    <i class="fa-brands fa-whatsapp text-lg text-emerald-600"></i> Chat Admin WA
                </a>
            </div>

            <!-- Jadwal rute utama harian — hero -->
            <div class="mb-8 mx-auto max-w-lg rounded-2xl border border-white/40 bg-white/95 backdrop-blur-sm shadow-lg px-3.5 py-3.5 sm:px-4 sm:py-4 text-left">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-navy-800">
                        <i class="fa-solid fa-clock text-gold-600 mr-1.5"></i>Jadwal setiap hari
                    </p>
                    <a href="#jadwal" class="text-[10px] font-semibold text-gold-700 hover:text-gold-600 whitespace-nowrap">Detail →</a>
                </div>
                <div class="grid grid-cols-1 gap-2.5">
                    <div class="rounded-xl bg-cream-50 border border-cream-200 px-3 py-2.5">
                        <p class="text-[12px] font-bold text-navy-900 mb-1.5">
                            <i class="fa-solid fa-route text-gold-600 mr-1"></i>Blora → Surabaya
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-navy-900 text-cream-50 text-[12px] font-bold tracking-wide">08.00</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-navy-900 text-cream-50 text-[12px] font-bold tracking-wide">11.00</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-navy-900 text-cream-50 text-[12px] font-bold tracking-wide">20.00</span>
                        </div>
                    </div>
                    <div class="rounded-xl bg-cream-50 border border-cream-200 px-3 py-2.5">
                        <p class="text-[12px] font-bold text-navy-900 mb-1.5">
                            <i class="fa-solid fa-route text-gold-600 mr-1"></i>Surabaya → Blora
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-navy-900 text-cream-50 text-[12px] font-bold tracking-wide">10.00</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-navy-900 text-cream-50 text-[12px] font-bold tracking-wide">15.00</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-navy-900 text-cream-50 text-[12px] font-bold tracking-wide">20.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 max-w-lg mx-auto">
                <div class="text-center py-2.5 rounded-2xl bg-white border border-cream-200 shadow-sm">
                    <div class="font-serif text-2xl md:text-3xl text-gold-600">1.200+</div>
                    <div class="text-[10px] md:text-[11px] uppercase tracking-[0.16em] text-navy-700 mt-1">Pelanggan Puas</div>
                </div>
                <div class="text-center py-2.5 rounded-2xl bg-white border border-cream-200 shadow-sm">
                    <div class="font-serif text-2xl md:text-3xl text-navy-900"><?= count($listRuteAktif) ?>+</div>
                    <div class="text-[10px] md:text-[11px] uppercase tracking-[0.16em] text-navy-700 mt-1">Rute Tersedia</div>
                </div>
                <div class="text-center py-2.5 rounded-2xl bg-white border border-cream-200 shadow-sm">
                    <div class="font-serif text-2xl md:text-3xl text-navy-900">4.9★</div>
                    <div class="text-[10px] md:text-[11px] uppercase tracking-[0.16em] text-navy-700 mt-1">Rating Pelanggan</div>
                </div>
            </div>
        </div>
    </div>

    <div class="wave-bottom">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none" style="height: 78px; width: 100%;">
            <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".18" fill="#FDFBF7"></path>
            <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".4" fill="#FDFBF7"></path>
            <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" fill="#FDFBF7"></path>
        </svg>
    </div>
</section>

<!-- ============ SECTION 2: 3 LANGKAH BOOKING ============ -->
<section class="relative z-10 -mt-8 md:-mt-12 pb-16 md:pb-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="card p-6 md:p-10 bg-white/90 backdrop-blur-sm relative overflow-hidden">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-40 h-px bg-gradient-to-r from-transparent via-gold-400 to-transparent"></div>
            <div class="text-center mb-8 md:mb-10">
                <p class="lux-eyebrow text-gold-600 mb-3">Proses reservasi</p>
                <h2 class="font-serif text-3xl md:text-4xl text-navy-900 font-semibold">Tiga langkah mudah</h2>
            </div>
            <div class="flex items-start">
                <div class="flex flex-col items-center flex-1">
                    <div class="w-14 h-14 rounded-full bg-navy-900 text-gold-300 flex items-center justify-center text-lg border border-gold-400/40 relative z-10">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <div class="mt-4 text-center px-1">
                        <div class="font-serif text-lg text-navy-900">1. Isi Data</div>
                        <div class="text-[12px] text-slate-500 mt-1 leading-tight">Lengkapi form pemesanan</div>
                    </div>
                </div>
                <div class="w-full h-px bg-gradient-to-r from-navy-800 via-gold-400 to-gold-400 -mx-1 mt-7 opacity-40"></div>
                <div class="flex flex-col items-center flex-1">
                    <div class="w-14 h-14 rounded-full bg-gold-400 text-navy-900 flex items-center justify-center text-lg relative z-10">
                        <i class="fa-brands fa-whatsapp"></i>
                    </div>
                    <div class="mt-4 text-center px-1">
                        <div class="font-serif text-lg text-navy-900">2. Konfirmasi WA</div>
                        <div class="text-[12px] text-slate-500 mt-1 leading-tight">Admin hubungi via WhatsApp</div>
                    </div>
                </div>
                <div class="w-full h-px bg-gradient-to-r from-gold-400 via-emerald-400 to-emerald-600 -mx-1 mt-7 opacity-40"></div>
                <div class="flex flex-col items-center flex-1">
                    <div class="w-14 h-14 rounded-full bg-navy-800 text-cream-50 flex items-center justify-center text-lg border border-white/10 relative z-10">
                        <i class="fa-solid fa-bus-simple"></i>
                    </div>
                    <div class="mt-4 text-center px-1">
                        <div class="font-serif text-lg text-navy-900">3. Berangkat Nyaman</div>
                        <div class="text-[12px] text-slate-500 mt-1 leading-tight">Driver jemput sesuai jadwal</div>
                    </div>
                </div>
            </div>
            <div class="mt-9 text-center">
                <a href="<?= BASE_URL ?>/booking.php" class="shine-btn inline-flex items-center gap-2.5 px-8 py-3.5 rounded-xl bg-navy-900 text-cream-50 hover:bg-navy-800 font-semibold transition shadow-lux">
                    <i class="fa-solid fa-ticket-simple text-gold-400"></i> Mulai Booking Sekarang
                    <i class="fa-solid fa-arrow-right text-xs text-gold-400"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ============ SECTION 3: JURUSAN + PELAYANAN ============ -->
<section id="jurusan" class="py-16 md:py-24 bg-cream-50 relative overflow-hidden">
    <div class="absolute top-0 inset-x-0 h-px gold-divider opacity-70"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16">
            <div>
                <p class="lux-eyebrow text-gold-600 mb-3">Rute perjalanan · Travel reguler</p>
                <h2 class="font-serif text-3xl md:text-[2.5rem] text-navy-900 font-semibold mb-3 leading-tight">
                    Jurusan kami
                </h2>
                <p class="text-slate-600 mb-6 max-w-md text-sm leading-relaxed">
                    Tersedia <span class="font-semibold text-navy-800"><?= count($listRuteAktif) ?>+ pilihan jurusan</span> favorit dengan jadwal fleksibel setiap hari.
                 
                </p>

             

                <div class="jurusan-grid">
                    <?php foreach ($listRuteAktif as $i => $r):
                        $namaRute = $r['nama_rute'] ?? 'Custom';
                        $hargaRute = (int)($r['harga'] ?? $hargaPerOrg);
                        [$dari, $tuju] = array_pad(explode(' - ', $namaRute, 2), 2, $namaRute);
                    ?>
                        <div class="jurusan-card group p-5 rounded-2xl bg-white border border-cream-200 hover:border-gold-400/60 hover:shadow-lux transition duration-300 hover:-translate-y-1">
                            <div class="jurusan-card-top flex items-center gap-3">
                                <div class="jurusan-card-icon w-10 h-10 rounded-xl bg-navy-900 text-gold-300 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-road text-sm"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="jurusan-card-route font-semibold text-navy-900 text-sm flex items-center gap-2 leading-tight">
                                        <span class="truncate"><?= e($dari) ?></span>
                                        <span class="text-gold-500 text-[10px] shrink-0"><i class="fa-solid fa-arrow-right"></i></span>
                                        <span class="truncate"><?= e($tuju) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="jurusan-card-meta mt-4 flex items-center justify-between gap-3">
                                <div class="jurusan-card-avail text-[11px] text-slate-500 flex items-center gap-1.5">
                                    <i class="fa-regular fa-calendar-check text-gold-600"></i>
                                    <span>Tersedia Setiap Hari</span>
                                </div>
                                <div class="jurusan-card-price px-3 py-1.5 rounded-full bg-gold-50 text-navy-900 font-semibold text-xs border border-gold-200">
                                    <?= rupiah($hargaRute) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-8">
                    <a href="<?= BASE_URL ?>/booking.php" class="inline-flex items-center gap-2.5 px-7 py-3.5 rounded-xl bg-navy-900 text-cream-50 hover:bg-navy-800 font-semibold transition shine-btn">
                        <i class="fa-solid fa-bus text-gold-400"></i> Booking Rute Favoritmu
                        <i class="fa-solid fa-arrow-right text-xs text-gold-400"></i>
                    </a>
                </div>
            </div>

            <div id="layanan" class="relative">
                <p class="lux-eyebrow text-gold-600 mb-3">Door to door service</p>
                <h2 class="font-serif text-3xl md:text-[2.5rem] text-navy-900 font-semibold mb-3 leading-tight">
                    Pelayanan sampai depan pintu
                </h2>
                <p class="text-slate-600 mb-8 max-w-md text-sm leading-relaxed">
                    Kami <strong>jemput dan antar langsung ke alamat tujuan</strong> Anda. Tidak perlu turun di terminal, tidak perlu pindah kendaraan.
                    Lebih praktis, lebih nyaman.
                </p>

                <div class="layanan-grid">
                    <?php
                    $services = [
                        ['fa-house-chimney',      'Jemput di Lokasi Anda',    'Kami jemput langsung di depan rumah / lokasi Anda tanpa harus ke terminal.'],
                        ['fa-location-crosshairs','Antar Sampai Tujuan',      'Diantar sampai alamat tujuan LENGKAP, bukan cuma pinggir jalan / drop point.'],
                        ['fa-shield-halved',      'Aman & Terpercaya',        'Driver profesional, ramah, dan armada selalu bersih. Perjalanan Anda 100% aman.'],
                        ['fa-calendar-check',     'Jadwal Fleksibel',         'Booking mendadak H-1 tetap bisa! Jam jemput menyesuaikan kebutuhan Anda.'],
                    ];
                    foreach ($services as [$ico, $title, $desc]): ?>
                        <div class="layanan-card card p-5 hover:-translate-y-1 transition duration-300 group bg-white">
                            <div class="layanan-card-icon w-11 h-11 rounded-xl bg-cream-100 text-navy-800 flex items-center justify-center text-lg mb-3.5 border border-gold-200/70 group-hover:bg-navy-900 group-hover:text-gold-300 transition">
                                <i class="fa-solid <?= $ico ?>"></i>
                            </div>
                            <h3 class="layanan-card-title font-semibold text-navy-900 text-sm mb-1.5"><?= $title ?></h3>
                            <p class="layanan-card-desc text-[12px] text-slate-500 leading-relaxed"><?= $desc ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <blockquote class="relative card p-7 md:p-8 bg-navy-900 overflow-hidden">
                    <div class="absolute top-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-gold-400 to-transparent"></div>
                    <p class="font-serif text-2xl md:text-[1.85rem] italic text-cream-50 leading-snug">
                        “Perjalanan Anda, <span class="text-gold-300">prioritas kami.</span>”
                    </p>
                    <p class="mt-4 text-xs tracking-wide text-cream-200/70">
                        Tim Mustika Travel · Melayani dengan sepenuh hati
                    </p>
                    <div class="mt-6 flex items-center gap-3 text-gold-300">
                        <span class="w-10 h-10 rounded-full border border-gold-400/30 flex items-center justify-center"><i class="fa-solid fa-house-chimney"></i></span>
                        <span class="text-gold-400/40">——</span>
                        <span class="w-10 h-10 rounded-full border border-gold-400/30 flex items-center justify-center"><i class="fa-solid fa-van-shuttle"></i></span>
                        <span class="text-gold-400/40">——</span>
                        <span class="w-10 h-10 rounded-full border border-gold-400/30 flex items-center justify-center"><i class="fa-solid fa-location-dot"></i></span>
                    </div>
                </blockquote>
            </div>
        </div>
    </div>
</section>

<div class="h-px gold-divider w-10/12 max-w-3xl mx-auto"></div>

<!-- ============ SECTION JADWAL KEBERANGKATAN ============ -->
<section id="jadwal" class="py-16 md:py-24 relative overflow-hidden bg-white">
    <div class="absolute top-0 inset-x-0 h-px gold-divider opacity-70"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-10 md:mb-14">
            <p class="lux-eyebrow text-gold-600 mb-3">Keberangkatan harian</p>
            <h2 class="font-serif text-3xl md:text-4xl lg:text-[2.7rem] text-navy-900 font-semibold mb-4 leading-tight">
                Jadwal penjemputan
            </h2>
            <p class="text-slate-600 md:text-base leading-relaxed max-w-2xl mx-auto">
                Start setiap hari dari <strong class="text-navy-900">Blora</strong> dan <strong class="text-navy-900">Surabaya</strong>.
                Pilih jam yang paling nyaman, lalu booking online — admin konfirmasi via WhatsApp.
            </p>
        </div>

        <div class="jadwal-grid max-w-4xl mx-auto">
            <!-- Blora -->
            <article class="jadwal-card group relative overflow-hidden rounded-2xl md:rounded-3xl border border-cream-200 bg-cream-50 p-4 sm:p-6 md:p-8 hover:border-gold-400/50 hover:shadow-lux transition duration-500">
                <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-gold-400/70 to-transparent opacity-0 group-hover:opacity-100 transition"></div>
                <div class="flex items-start gap-3 mb-4 md:mb-5">
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-navy-900 text-gold-300 flex items-center justify-center shrink-0 border border-gold-400/30">
                        <i class="fa-solid fa-city text-sm md:text-base"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] md:text-[11px] uppercase tracking-[0.14em] text-gold-600 font-semibold mb-0.5">Start dari</p>
                        <h3 class="font-serif text-xl sm:text-2xl md:text-[1.75rem] text-navy-900 leading-tight">Blora</h3>
                    </div>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-500 mb-3 md:mb-4 leading-relaxed">Kota-kota &amp; door-to-door area Blora</p>
                <div class="flex flex-col gap-2">
                    <?php foreach (['08.00', '11.00', '20.00'] as $jam): ?>
                        <div class="jadwal-chip flex items-center gap-2.5 px-3 py-2.5 md:px-4 md:py-3 rounded-xl bg-white border border-cream-200 group-hover:border-gold-200/80 transition">
                            <span class="w-8 h-8 rounded-lg bg-navy-900 text-gold-300 flex items-center justify-center shrink-0">
                                <i class="fa-regular fa-clock text-xs"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="font-serif text-lg md:text-xl text-navy-900 leading-none">Jam <?= $jam ?></div>
                                <div class="text-[10px] text-slate-500 mt-0.5">WIB · setiap hari</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <!-- Surabaya -->
            <article class="jadwal-card group relative overflow-hidden rounded-2xl md:rounded-3xl border border-cream-200 bg-cream-50 p-4 sm:p-6 md:p-8 hover:border-gold-400/50 hover:shadow-lux transition duration-500">
                <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-gold-400/70 to-transparent opacity-0 group-hover:opacity-100 transition"></div>
                <div class="flex items-start gap-3 mb-4 md:mb-5">
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-navy-900 text-gold-300 flex items-center justify-center shrink-0 border border-gold-400/30">
                        <i class="fa-solid fa-plane-departure text-sm md:text-base"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] md:text-[11px] uppercase tracking-[0.14em] text-gold-600 font-semibold mb-0.5">Start dari</p>
                        <h3 class="font-serif text-xl sm:text-2xl md:text-[1.75rem] text-navy-900 leading-tight">Surabaya</h3>
                    </div>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-500 mb-3 md:mb-4 leading-relaxed">Juanda · Sidoarjo · Surabaya / Gresik</p>
                <div class="flex flex-col gap-2">
                    <?php foreach (['10.00', '15.00', '20.00'] as $jam): ?>
                        <div class="jadwal-chip flex items-center gap-2.5 px-3 py-2.5 md:px-4 md:py-3 rounded-xl bg-white border border-cream-200 group-hover:border-gold-200/80 transition">
                            <span class="w-8 h-8 rounded-lg bg-navy-900 text-gold-300 flex items-center justify-center shrink-0">
                                <i class="fa-regular fa-clock text-xs"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="font-serif text-lg md:text-xl text-navy-900 leading-none">Jam <?= $jam ?></div>
                                <div class="text-[10px] text-slate-500 mt-0.5">WIB · setiap hari</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </div>

        <div class="text-center mt-8 md:mt-10">
            <a href="<?= BASE_URL ?>/booking.php" class="shine-btn inline-flex items-center gap-2.5 px-7 py-3.5 rounded-xl bg-navy-900 text-cream-50 hover:bg-navy-800 font-semibold transition shadow-lux">
                <i class="fa-solid fa-ticket text-gold-400"></i> Booking sesuai jadwal
                <i class="fa-solid fa-arrow-right text-xs text-gold-400"></i>
            </a>
            <p class="text-[11px] text-slate-500 mt-3">Jadwal bisa menyesuaikan unit &amp; permintaan — admin akan konfirmasi.</p>
        </div>
    </div>
</section>

<div class="h-px gold-divider w-10/12 max-w-3xl mx-auto"></div>

<!-- ============ SECTION 4: ARMADA ============ -->
<section id="armada" class="py-16 md:py-24 relative overflow-hidden bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-12 md:mb-16">
            <p class="lux-eyebrow text-gold-600 mb-3">Koleksi armada</p>
            <h2 class="font-serif text-3xl md:text-4xl lg:text-[2.7rem] text-navy-900 font-semibold mb-4 leading-tight">
                Armada Mustika Travel
            </h2>
            <p class="text-slate-600 md:text-base leading-relaxed max-w-2xl mx-auto">
                Semua armada dirawat rutin, <strong class="text-navy-900">bersih, nyaman, dan ber AC dingin</strong>.
                Tersedia berbagai pilihan kendaraan sesuai kebutuhan jumlah penumpang Anda.
            </p>
        </div>

        <div class="armada-grid">
            <?php
            $listArmadaFront = get_armadas(true);
            if (empty($listArmadaFront)): ?>
                <div class="col-span-2 xl:col-span-3 text-center py-12 text-slate-500">
                    Koleksi armada akan ditampilkan di sini.
                </div>
            <?php endif;
            foreach ($listArmadaFront as $a):
                $ico      = trim($a['icon_fa'] ?? 'fa-car-side');
                $nama     = $a['nama_armada'] ?? 'Armada Mustika';
                $kapasitas= $a['kapasitas'] ?? 'Kapasitas 5-7 Kursi';
                $desc     = $a['deskripsi'] ?? '';
                $grad     = trim($a['warna_grad'] ?? 'from-navy-800 to-navy-950');
                $fiturs   = parse_fitur_armada($a['fitur_list'] ?? '');
                $imgSrc   = resolve_gambar_src($a['gambar_url'] ?? '');
            ?>
                <article class="fleet-card armada-card group bg-cream-50 rounded-3xl overflow-hidden shadow-card border border-cream-200 hover:border-gold-400/50 hover:shadow-lux transition duration-500 flex flex-col">
                    <div class="fleet-card-photo relative h-44 sm:h-48 md:h-52 overflow-hidden">
                        <img src="<?= e($imgSrc) ?>"
                             alt="<?= e($nama) ?> Mustika Travel"
                             class="armada-card-img w-full h-full object-cover"
                             onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=700&q=80';">
                        <div class="absolute inset-0 bg-gradient-to-t from-navy-950/50 to-transparent"></div>
                        <div class="fleet-card-badge-wrap absolute bottom-3 left-3 z-10">
                            <div class="fleet-card-badge px-3 py-1.5 rounded-full bg-cream-50/95 text-navy-900 font-semibold text-xs border border-white/70 flex items-center gap-1.5">
                                <i class="fa-solid fa-users text-gold-600"></i>
                                <?= e($kapasitas) ?>
                            </div>
                        </div>
                    </div>

                    <div class="fleet-card-body p-4 sm:p-5 md:p-6 flex flex-col flex-1">
                        <div class="fleet-card-icon w-10 h-10 rounded-xl bg-gradient-to-br <?= e($grad) ?> text-white flex items-center justify-center mb-3">
                            <i class="fa-solid <?= e($ico) ?> text-sm"></i>
                        </div>
                        <h3 class="fleet-card-title font-serif text-xl sm:text-2xl text-navy-900 leading-tight mb-2"><?= e($nama) ?></h3>
                        <p class="fleet-card-desc text-sm text-slate-600 leading-relaxed mb-4"><?= e($desc) ?></p>

                        <div class="mt-auto">
                            <div class="fleet-card-fac-label text-[11px] uppercase tracking-[0.14em] text-slate-500 mb-2 flex items-center gap-2">
                                <i class="fa-solid fa-circle-check text-gold-600 text-[10px]"></i> Fasilitas termasuk
                            </div>
                            <?php if (!empty($fiturs)): ?>
                                <div class="fleet-card-chips flex flex-wrap gap-1.5">
                                    <?php foreach ($fiturs as $f): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-white border border-cream-200 text-[11px] font-medium text-navy-800">
                                            <?= e($f) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div class="h-px gold-divider w-10/12 max-w-3xl mx-auto"></div>

<!-- ============ SECTION CARTER PP / DROP ============ -->
<section id="carter" class="py-16 md:py-24 relative overflow-hidden bg-cream-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-12 md:mb-16">
            <p class="lux-eyebrow text-gold-600 mb-3">Layanan carter</p>
            <h2 class="font-serif text-3xl md:text-4xl lg:text-[2.7rem] text-navy-900 font-semibold mb-4 leading-tight">
                Carter PP / Drop
            </h2>
            <p class="text-slate-600 md:text-base leading-relaxed max-w-2xl mx-auto">
                Butuh kendaraan khusus untuk <strong class="text-navy-900">rombongan, acara, tour, project</strong>?
                Kami sediakan paket carter pulang pergi (PP) atau drop off sesuai kebutuhan.
            </p>
        </div>

        <div class="carter-grid">
            <?php
            $listCarterFront = get_carters(true);
            if (empty($listCarterFront)): ?>
                <div class="col-span-2 lg:col-span-4 text-center py-12 text-slate-500">
                    Paket carter akan ditampilkan di sini.
                </div>
            <?php endif;
            foreach ($listCarterFront as $ct):
                $i   = trim($ct['icon_fa'] ?? 'fa-car');
                $n   = $ct['nama_unit'] ?? 'Unit Carter';
                $k   = $ct['kapasitas'] ?? '';
                $d   = $ct['deskripsi'] ?? '';
                $img = resolve_gambar_src($ct['gambar_url'] ?? '');
                $ft  = parse_fitur_armada($ct['fitur_list'] ?? '');
            ?>
                <article class="carter-card armada-card group bg-white rounded-3xl overflow-hidden shadow-card border border-cream-200 hover:border-gold-400/50 hover:shadow-lux transition duration-500 flex flex-col">
                    <div class="carter-card-photo relative h-40 md:h-44 overflow-hidden">
                        <img src="<?= e($img) ?>" alt="<?= e($n) ?>" class="armada-card-img w-full h-full object-cover" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=700&q=80';">
                        <div class="absolute inset-0 bg-gradient-to-t from-navy-950/45 to-transparent"></div>
                        <div class="carter-card-badge-wrap absolute bottom-2.5 left-3 z-10">
                            <div class="carter-card-badge px-2.5 py-1 rounded-full bg-cream-50/95 text-navy-900 font-semibold text-[11px] border border-white flex items-center gap-1.5">
                                <i class="fa-solid fa-users text-gold-600"></i> <?= e($k) ?>
                            </div>
                        </div>
                    </div>
                    <div class="carter-card-body p-4 md:p-5 flex flex-col flex-1">
                        <div class="carter-card-icon w-9 h-9 rounded-xl bg-navy-900 text-gold-300 flex items-center justify-center mb-3">
                            <i class="fa-solid <?= e($i) ?> text-sm"></i>
                        </div>
                        <h3 class="carter-card-title font-serif text-xl text-navy-900 leading-tight mb-1.5"><?= e($n) ?></h3>
                        <p class="carter-card-desc text-xs text-slate-600 leading-relaxed mb-4"><?= e($d) ?></p>
                        <div class="mt-auto">
                            <div class="carter-card-fac-label text-[11px] uppercase tracking-[0.14em] text-slate-500 mb-2">Fasilitas</div>
                            <div class="carter-card-chips flex flex-wrap gap-1.5 mb-4">
                                <?php foreach ($ft as $f): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-cream-50 border border-cream-200 text-[10px] font-medium text-navy-800">
                                        <?= e($f) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', WA_ADMIN) ?>?text=Halo admin Mustika Travel, saya mau CARTER unit <?= urlencode($n) ?>, mau tanya harga & ketersediaan ya kaak!"
                               target="_blank" rel="noopener"
                               class="carter-card-btn shine-btn w-full text-center inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-navy-900 text-cream-50 font-semibold hover:bg-navy-800 transition text-sm">
                                <i class="fa-brands fa-whatsapp text-gold-400"></i> Tanya Carter WA
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ SECTION KONTAK + FOOTER ada di includes/footer.php ============ -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>
