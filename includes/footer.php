<?php
// =============================================
// FOOTER FRONTEND
// =============================================
global $DAFTAR_JURUSAN;
?>
<!-- ============ SECTION KONTAK ============ -->
<section id="kontak" class="py-16 md:py-20 bg-white relative overflow-hidden">
    <div class="absolute top-0 inset-x-0 h-px gold-divider"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-12 md:mb-14">
            <p class="lux-eyebrow text-gold-600 mb-3">Hubungi kami</p>
            <h2 class="font-serif text-3xl md:text-4xl text-navy-900 font-semibold mb-3 leading-tight">Kontak kami</h2>
            <p class="text-slate-600 max-w-2xl mx-auto text-sm md:text-base">Butuh bantuan atau info lebih lanjut? Hubungi admin kami via WhatsApp. Customer service kami siap melayani Anda 24 jam.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6 max-w-4xl mx-auto">
            <div class="card p-6 md:p-7 hover:-translate-y-1 transition-transform duration-300 bg-white group">
                <div class="w-12 h-12 rounded-2xl bg-navy-900 text-gold-300 flex items-center justify-center text-xl mb-4">
                    <i class="fa-brands fa-whatsapp"></i>
                </div>
                <h3 class="font-serif text-2xl text-navy-900 mb-1">WhatsApp Admin</h3>
                <p class="text-slate-500 text-sm mb-5 leading-relaxed">Pelayanan cepat & responsif, kirim pesan kapanpun.</p>
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', WA_ADMIN) ?>?text=Halo admin Mustika Travel, saya ingin bertanya mengenai jadwal dan harga tiket."
                   target="_blank" rel="noopener"
                   class="shine-btn w-full text-center inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-navy-900 hover:bg-navy-800 text-cream-50 font-semibold transition">
                    <i class="fa-brands fa-whatsapp text-gold-400"></i> Chat WA: <?= WA_ADMIN_DISPLAY ?>
                </a>
            </div>

            <div class="card p-6 md:p-7 hover:-translate-y-1 transition-transform duration-300 bg-white">
                <div class="w-12 h-12 rounded-2xl bg-cream-100 text-navy-800 flex items-center justify-center text-xl mb-4 border border-gold-200/70">
                    <i class="fa-solid fa-clock text-gold-600"></i>
                </div>
                <h3 class="font-serif text-2xl text-navy-900 mb-1">Jam Operasional</h3>
                <p class="text-slate-500 text-sm mb-5 leading-relaxed">Kami melayani setiap hari.</p>
                <ul class="space-y-2 p-4 rounded-2xl bg-cream-50 border border-cream-200">
                    <li class="flex items-center gap-2 text-navy-800 font-medium text-sm">
                        <span class="w-5 h-5 rounded-full bg-navy-900 text-gold-300 flex items-center justify-center shrink-0"><i class="fa-solid fa-check text-[9px]"></i></span>
                        Senin - Jumat : 05.00 - 21.00 WIB
                    </li>
                    <li class="flex items-center gap-2 text-navy-800 font-medium text-sm">
                        <span class="w-5 h-5 rounded-full bg-navy-900 text-gold-300 flex items-center justify-center shrink-0"><i class="fa-solid fa-check text-[9px]"></i></span>
                        Sabtu - Minggu : 05.00 - 22.00 WIB
                    </li>
                    <li class="flex items-center gap-2 text-navy-800 font-medium text-sm">
                        <span class="w-5 h-5 rounded-full bg-navy-900 text-gold-300 flex items-center justify-center shrink-0"><i class="fa-solid fa-check text-[9px]"></i></span>
                        Tanggal Merah : Tetap Buka
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ============ FOOTER ============ -->
<footer class="footer-gradient text-cream-100 pt-10 pb-5 md:pt-14 md:pb-7 relative overflow-hidden border-t border-gold-400/20">
    <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-gold-400/70 to-transparent"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8 mb-8 md:mb-10">
            <div class="col-span-2 md:col-span-2 lg:col-span-1">
                <div class="flex items-center gap-3 mb-4">
                    <img src="<?= BASE_URL ?>/logo.jpeg" alt="Logo Mustika Travel" class="w-11 h-11 md:w-12 md:h-12 rounded-full border border-gold-400/40 object-cover">
                    <div>
                        <div class="text-lg md:text-xl leading-tight">
                            <span class="text-cream-50 font-semibold">Mustika</span><br>
                            <span class="text-gold-300 italic font-serif text-xl">Travel</span>
                        </div>
                    </div>
                </div>
                <p class="text-cream-200/70 text-xs md:text-sm leading-relaxed mb-4 max-w-xs">
                    Layanan travel door to door terpercaya. Driver profesional, armada nyaman & aman sampai tujuan.
                </p>
                <div class="flex gap-2">
                    <a href="#" class="w-9 h-9 rounded-full border border-white/15 hover:border-gold-400/60 hover:text-gold-300 flex items-center justify-center transition text-cream-100" aria-label="Facebook">
                        <i class="fa-brands fa-facebook-f text-xs"></i></a>
                    <a href="#" class="w-9 h-9 rounded-full border border-white/15 hover:border-gold-400/60 hover:text-gold-300 flex items-center justify-center transition text-cream-100" aria-label="Instagram">
                        <i class="fa-brands fa-instagram text-xs"></i></a>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', WA_ADMIN) ?>" class="w-9 h-9 rounded-full border border-white/15 hover:border-gold-400/60 hover:text-gold-300 flex items-center justify-center transition text-cream-100" aria-label="WhatsApp">
                        <i class="fa-brands fa-whatsapp text-xs"></i></a>
                </div>
            </div>

            <div class="col-span-1">
                <h4 class="font-serif text-lg text-cream-50 mb-3 relative pb-2">
                    Tautan Utama
                    <span class="absolute bottom-0 left-0 h-px w-8 bg-gold-400"></span>
                </h4>
                <ul class="space-y-2 text-xs md:text-sm">
                    <?php
                    $links = [
                        [BASE_URL,'Beranda'],[BASE_URL . '/#jurusan','Jurusan'],
                        [BASE_URL . '/#jadwal','Jadwal'],
                        [BASE_URL . '/booking.php','Pemesanan'],[BASE_URL . '/#layanan','Layanan'],
                        [BASE_URL . '/#armada','Armada'],[BASE_URL . '/#carter','Carter'],
                        [BASE_URL . '/#kontak','Kontak']
                    ];
                    foreach ($links as [$href, $lbl]): ?>
                        <li><a href="<?= $href ?>" class="text-cream-200/70 hover:text-gold-300 transition flex items-center"><i class="fa-solid fa-angle-right mr-1.5 text-[10px] text-gold-400"></i><?= $lbl ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="col-span-1">
                <h4 class="font-serif text-lg text-cream-50 mb-3 relative pb-2">
                    Jurusan Populer
                    <span class="absolute bottom-0 left-0 h-px w-8 bg-gold-400"></span>
                </h4>
                <ul class="space-y-2 text-xs md:text-sm">
                    <?php
                    $rutePopuler = array_slice(function_exists('get_rutes') ? array_column(get_rutes(true), 'nama_rute') : $DAFTAR_JURUSAN, 0, 6);
                    foreach ($rutePopuler as $rute): ?>
                        <li class="text-cream-200/70 flex items-start gap-1.5"><i class="fa-solid fa-route text-[9px] text-gold-400 mt-1 shrink-0"></i><span class="leading-tight"><?= e($rute) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="col-span-2 lg:col-span-1">
                <h4 class="font-serif text-lg text-cream-50 mb-3 relative pb-2">
                    Hubungi Kami
                    <span class="absolute bottom-0 left-0 h-px w-8 bg-gold-400"></span>
                </h4>
                <ul class="space-y-2 text-xs md:text-sm">
                    <li class="flex items-start gap-3 p-2.5 rounded-xl bg-white/5 border border-white/10">
                        <div class="w-8 h-8 rounded-lg bg-gold-400/15 text-gold-300 flex items-center justify-center shrink-0">
                            <i class="fa-brands fa-whatsapp text-sm"></i>
                        </div>
                        <div>
                            <div class="text-cream-50 font-semibold"><?= WA_ADMIN_DISPLAY ?></div>
                            <div class="text-[10px] text-gold-300">Chat 24 jam</div>
                        </div>
                    </li>
                    <li class="hidden lg:flex items-start gap-3 p-2.5 rounded-xl bg-white/5 border border-white/10">
                        <div class="w-8 h-8 rounded-lg bg-gold-400/15 text-gold-300 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-envelope text-sm"></i>
                        </div>
                        <div class="pt-1 truncate max-w-[170px] text-cream-200/80"><?= e(EMAIL_ADMIN) ?></div>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</footer>

<script>
/* Smooth anchor scroll - dikombinasikan dengan CSS scroll-behavior */
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const t = document.querySelector(a.getAttribute('href'));
        if (t) {
            e.preventDefault();
            const navH = document.querySelector('nav').offsetHeight;
            window.scroll({ top: t.offsetTop - navH - 10, behavior: 'smooth' });
        }
    });
});

/* Nav aktif: halaman booking + scroll-spy / hash di homepage */
(function () {
    const links = document.querySelectorAll('[data-nav]');
    if (!links.length) return;

    const sectionIds = ['beranda', 'jurusan', 'jadwal', 'layanan', 'armada', 'carter', 'kontak'];
    const path = (location.pathname || '').replace(/\/+$/, '');
    const isBooking = /booking\.php$/i.test(path);

    function setActive(key) {
        const bookingOn = (key === 'pemesanan' || key === 'booking');
        links.forEach(el => {
            const nav = el.getAttribute('data-nav');
            const on = nav === key || (bookingOn && (nav === 'pemesanan' || nav === 'booking'));
            el.classList.toggle('is-active', on);
            if (on) el.setAttribute('aria-current', 'page');
            else el.removeAttribute('aria-current');
        });
    }

    if (isBooking) {
        setActive('pemesanan');
        return;
    }

    function fromHash() {
        const h = (location.hash || '#beranda').replace('#', '');
        setActive(sectionIds.includes(h) ? h : 'beranda');
    }

    fromHash();
    window.addEventListener('hashchange', fromHash);

    const sections = sectionIds.map(id => document.getElementById(id)).filter(Boolean);
    if (!sections.length) return;

    const observer = new IntersectionObserver((entries) => {
        const visible = entries
            .filter(e => e.isIntersecting)
            .sort((a, b) => b.intersectionRatio - a.intersectionRatio);
        if (!visible[0]) return;
        const id = visible[0].target.id;
        if (location.hash !== '#' + id && id !== 'beranda') {
            history.replaceState(null, '', '#' + id);
        } else if (id === 'beranda' && location.hash && location.hash !== '#beranda') {
            history.replaceState(null, '', location.pathname + location.search);
        }
        setActive(id);
    }, { rootMargin: '-22% 0px -58% 0px', threshold: [0.12, 0.3, 0.55] });

    sections.forEach(s => observer.observe(s));

    links.forEach(el => {
        el.addEventListener('click', () => {
            const key = el.getAttribute('data-nav');
            if (sectionIds.includes(key)) setActive(key);
        });
    });
})();
</script>
<?php
// Bersihkan old input SETELAH ditampilkan di form (jika ada)
clear_old();
?>
</body>
</html>
