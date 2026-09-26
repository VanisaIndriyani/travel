<?php
// =============================================
// HALAMAN KHUSUS FORM BOOKING (TERPISAH DARI INDEX LANDING)
// Mustika Travel - Form Pemesanan Tiket Online
// =============================================
require_once __DIR__ . '/includes/header.php';
global $DAFTAR_JURUSAN;

// Variable PHP KHUSUS FORM (dipindah dari index)
$listRuteAktif = get_rutes(true);
$hargaPerOrg   = get_harga_rute_default();
$ruteAwal      = $listRuteAktif[0] ?? ['id' => 0, 'nama_rute' => 'Custom', 'harga' => $hargaPerOrg];
?>
<!-- ============ SECTION 1: HERO BOOKING ============ -->
<section class="relative overflow-hidden pb-12 md:pb-16 pt-8 md:pt-10 min-h-[280px] md:min-h-[320px] flex items-center">
    <div class="absolute inset-0 z-0">
        <img src="<?= BASE_URL ?>/mob1.jpeg"
             alt="Background Booking Mustika Travel"
             class="w-full h-full object-cover object-center"
             onerror="this.onerror=null; this.style.background='linear-gradient(135deg,#0A1628,#1A3358)';this.removeAttribute('src');">
    </div>
    <div class="absolute inset-0 bg-gradient-to-r from-navy-950/92 via-navy-900/78 to-navy-900/45 z-0"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-navy-950 via-transparent to-navy-900/40 z-0"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20 w-full">
        <div class="text-center max-w-3xl mx-auto animate-fade-in">
            <p class="lux-eyebrow text-gold-300 mb-4">Reservasi online</p>
            <h1 class="mb-3">
                <span class="block font-serif text-3xl md:text-4xl lg:text-[2.6rem] font-semibold text-cream-50 leading-tight">
                    Mustika <span class="italic text-gold-300">Travel</span>
                </span>
                <span class="block font-serif text-xl md:text-2xl text-cream-100/90 mt-2">
                    Lengkapi data pemesanan Anda
                </span>
            </h1>
            <p class="text-sm md:text-base text-cream-200/80 leading-relaxed max-w-2xl mx-auto">
                Isi form di bawah ini dengan data yang valid. Admin kami akan <strong class="text-gold-300 font-semibold">segera hubungi via WhatsApp</strong> untuk konfirmasi jadwal & pembayaran.
            </p>

            <div class="flex flex-wrap items-center justify-center gap-2.5 md:gap-3 mt-6">
                <a href="<?= BASE_URL ?>/index.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-white/10 text-cream-50 border border-white/15 font-semibold text-xs md:text-sm hover:bg-white/15 hover:border-gold-400/40 transition">
                    <i class="fa-solid fa-arrow-left text-xs"></i> Kembali ke Beranda
                </a>
                <div class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-white/5 text-cream-100 border border-white/10 text-xs md:text-sm">
                    <i class="fa-solid fa-circle-check text-gold-400"></i> 100% Gratis Biaya Admin
                </div>
                <div class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-white/5 text-cream-100 border border-white/10 text-xs md:text-sm">
                    <i class="fa-brands fa-whatsapp text-emerald-400"></i> Admin WA 24 Jam
                </div>
            </div>
        </div>
    </div>

    <div class="wave-bottom">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none" style="height: 56px; width: 100%;">
            <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".18" fill="#FDFBF7"></path>
            <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".4" fill="#FDFBF7"></path>
            <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" fill="#FDFBF7"></path>
        </svg>
    </div>
</section>

<div class="h-[2px] gold-divider w-10/12 max-w-3xl mx-auto -mt-2 relative z-10"></div>

<!-- ============ SECTION 2: FORM BOOKING + STEP INDICATOR ============ -->
<section class="relative z-10 -mt-6 md:-mt-10 pb-16 md:pb-24">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Flash Messages -->
        <?php
        $flash = get_flash();
        if ($flash) echo $flash;
        ?>

        <div class="mb-8 md:mb-10 max-w-3xl mx-auto">
            <div class="flex items-start">
                <div class="flex flex-col items-center flex-1">
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-full bg-navy-900 text-gold-300 flex items-center justify-center text-lg border border-gold-400/40">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <div class="mt-3 text-center px-1">
                        <div class="font-serif text-base md:text-lg text-navy-900">1. Isi Data</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">Lengkapi form pemesanan</div>
                    </div>
                </div>
                <div class="w-full h-px bg-gradient-to-r from-navy-800 via-gold-400 to-gold-400 -mx-1 mt-6 md:mt-7 opacity-40"></div>
                <div class="flex flex-col items-center flex-1">
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-full bg-gold-400 text-navy-900 flex items-center justify-center text-lg">
                        <i class="fa-brands fa-whatsapp"></i>
                    </div>
                    <div class="mt-3 text-center px-1">
                        <div class="font-serif text-base md:text-lg text-navy-900">2. Konfirmasi WA</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">Admin hubungi via WhatsApp</div>
                    </div>
                </div>
                <div class="w-full h-px bg-gradient-to-r from-gold-400 to-navy-800 -mx-1 mt-6 md:mt-7 opacity-40"></div>
                <div class="flex flex-col items-center flex-1">
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-full bg-navy-800 text-cream-50 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-bus-simple"></i>
                    </div>
                    <div class="mt-3 text-center px-1">
                        <div class="font-serif text-base md:text-lg text-navy-900">3. Berangkat Nyaman</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">Driver jemput sesuai jadwal</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-5 md:p-8 lg:p-11 relative overflow-hidden">
            <div class="absolute top-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-gold-400 to-transparent"></div>

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 md:mb-8 border-b border-cream-200 pb-6 relative z-10">
                <div class="flex items-center gap-3 md:gap-4">
                    <div class="w-11 h-11 md:w-12 md:h-12 rounded-xl bg-navy-900 text-gold-300 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-bus-simple"></i>
                    </div>
                    <div>
                        <p class="lux-eyebrow text-gold-600">Reservasi online</p>
                        <h2 class="font-serif text-xl md:text-2xl text-navy-900 mt-0.5">Form pemesanan tiket</h2>
                        <p class="text-[11px] md:text-xs text-slate-500 mt-0.5">Isi data dengan lengkap — tanpa biaya admin.</p>
                    </div>
                </div>
                <div class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-cream-50 border border-gold-200 text-navy-800 font-semibold text-xs md:text-sm">
                    <i class="fa-solid fa-circle-check text-gold-600 text-xs"></i>
                    Terima kasih
                </div>
            </div>

            <form action="<?= BASE_URL ?>/process_booking.php" method="POST" class="space-y-5 md:space-y-6 relative z-10" novalidate
                  x-data="formJadwal()"
                  x-init="initLokasi('<?= e(old('lokasi_jemput')?:'Blora') ?>','<?= e(old('jadwal_jemput')?:'') ?>','<?= e(old('maps_link')?:'') ?>')">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="lokasi_jemput" :value="lokasi">
                <input type="hidden" name="maps_link" :value="mapsLink">

                <div class="flex flex-wrap items-center gap-3 mb-3 p-4 md:p-5 rounded-2xl bg-cream-50 border border-gold-200/70">
                    <div class="flex items-center gap-2 text-navy-800 bg-white border border-gold-200 px-4 py-2.5 rounded-xl text-xs md:text-sm font-medium">
                        <i class="fa-solid fa-triangle-exclamation text-gold-600 text-sm"></i>
                        <span><?= PEMBATALAN_INFO ?></span>
                    </div>
                </div>

                <!-- Field Grid - 1 LAYER STYLING -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">
                    <!-- Nama -->
                    <div>
                        <label class="form-label" for="nama">
                            <i class="fa-solid fa-user-large text-gold-600 mr-1.5"></i> Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="nama" name="nama" required minlength="2"
                               value="<?= e(old('nama')) ?>"
                               placeholder="Contoh: Budi Santoso"
                               class="input-field" autofocus>
                    </div>
                    <!-- No HP -->
                    <div>
                        <label class="form-label" for="no_hp">
                            <i class="fa-solid fa-mobile-screen text-gold-600 mr-1.5"></i> No. HP / WhatsApp <span class="text-red-500">*</span>
                        </label>
                        <input type="tel" id="no_hp" name="no_hp" required pattern="[0-9+]{10,16}"
                               value="<?= e(old('no_hp')) ?>"
                               placeholder="Contoh: 0812 3456 7890"
                               class="input-field">
                    </div>
                    <!-- Alamat Jemput -->
                    <div class="md:col-span-2">
                        <label class="form-label" for="alamat_jemput">
                            <i class="fa-solid fa-house-chimney text-gold-600 mr-1.5"></i> Alamat Penjemputan <span class="text-red-500">*</span>
                        </label>
                        <textarea id="alamat_jemput" name="alamat_jemput" required rows="2"
                                  placeholder="Contoh: Jl. Pemuda No.12, RT 02/RW 04, Blora, Jawa Tengah"
                                  class="input-field resize-none"><?= e(old('alamat_jemput')) ?></textarea>
                    </div>
                    <!-- Alamat Tujuan -->
                    <div class="md:col-span-2">
                        <label class="form-label" for="alamat_tujuan">
                            <i class="fa-solid fa-location-dot text-gold-600 mr-1.5"></i> Alamat Tujuan <span class="text-red-500">*</span>
                        </label>
                        <textarea id="alamat_tujuan" name="alamat_tujuan" required rows="2"
                                  placeholder="Contoh: Jl. Ahmad Yani No.45, Kota tujuan (depan toko / landmark)"
                                  class="input-field resize-none"><?= e(old('alamat_tujuan')) ?></textarea>
                    </div>
                    <!-- Pilih Rute Perjalanan -->
                    <div class="md:col-span-2">
                        <label class="form-label" for="id_rute">
                            <i class="fa-solid fa-map-location-dot text-gold-600 mr-1.5"></i> Pilih Rute Perjalanan <span class="text-red-500">*</span>
                        </label>
                        <select id="id_rute" name="id_rute" required
                                class="input-field font-bold text-slate-800 bg-white"
                                onchange="hitungTotal()">
                            <?php if (empty($listRuteAktif)): ?>
                                <option value="0">— Belum ada rute aktif, hubungi admin ya kaak —</option>
                            <?php else: ?>
                                <option value="" disabled selected hidden>— Pilih rute tujuan perjalanan Anda —</option>
                                <?php foreach ($listRuteAktif as $r):
                                    $hargaRupiah = rupiah($r['harga']);
                                    $selected = (old('id_rute') !== null && (int)old('id_rute') === (int)($r['id'] ?? 0)) ? 'selected' : '';
                                    if (empty($selected) && old('id_rute') === null && isset($ruteAwal['id']) && (int)$ruteAwal['id'] === (int)($r['id'] ?? 0)) {
                                        $selected = 'selected';
                                    }
                                ?>
                                    <option value="<?= (int)($r['id'] ?? 0) ?>"
                                            data-harga="<?= (int)$r['harga'] ?>"
                                            data-nama="<?= e($r['nama_rute']) ?>"
                                            <?= $selected ?>>
                                        🛣️ <?= e($r['nama_rute']) ?> · <?= $hargaRupiah ?> / org
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="flex items-center gap-2 mt-1.5 text-[11px] font-semibold text-slate-500">
                            <i class="fa-solid fa-circle-info text-gold-600"></i>
                            Harga tiket BEDA tiap rute ya kaak. Admin bisa update harga sewaktu-waktu ✔️
                        </div>
                    </div>
                    <!-- Jumlah Kursi -->
                    <div>
                        <label class="form-label" for="jumlah_kursi">
                            <i class="fa-solid fa-people-group text-gold-600 mr-1.5"></i> Jumlah Seat / Kursi <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" id="jumlah_kursi" name="jumlah_kursi" required min="1" max="50"
                                   value="<?= old('jumlah_kursi') ?: '1' ?>"
                                   class="input-field" oninput="hitungTotal()">
                            <div class="absolute right-2 md:right-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-600 font-black px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 pointer-events-none">org</div>
                        </div>
                    </div>
                    <!-- Tanggal Berangkat -->
                    <div>
                        <label class="form-label" for="tanggal_berangkat">
                            <i class="fa-solid fa-calendar-day text-gold-600 mr-1.5"></i> Hari & Tanggal Berangkat <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="tanggal_berangkat" name="tanggal_berangkat" required
                               value="<?= old('tanggal_berangkat') ?: date('Y-m-d') ?>"
                               min="<?= date('Y-m-d') ?>"
                               class="input-field">
                    </div>
                    <!-- Pin lokasi di Maps (OPSIONAL) -->
                    <div class="md:col-span-2">
                        <label class="form-label">
                            <i class="fa-solid fa-map-location-dot text-gold-600 mr-1.5"></i>
                            Pin lokasi di Maps
                            <span class="ml-1.5 inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200 text-[10px] font-bold uppercase tracking-wide text-slate-500">Opsional</span>
                        </label>
                        <p class="text-[11px] md:text-xs text-slate-500 mb-2.5 leading-relaxed">
                            Bantu driver menemukan titik jemput. Alamat teks di atas tetap wajib — pin Maps hanya tambahan.
                        </p>
                        <div class="rounded-2xl border border-cream-200 bg-cream-50/80 p-3.5 md:p-4 space-y-3">
                            <div class="flex flex-col sm:flex-row gap-2">
                                <button type="button" @click="ambilLokasiSaya()"
                                        :disabled="geoLoading"
                                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-navy-900 text-cream-50 text-sm font-semibold hover:bg-navy-800 transition disabled:opacity-60">
                                    <i class="fa-solid" :class="geoLoading ? 'fa-spinner fa-spin' : 'fa-location-crosshairs'"></i>
                                    <span x-text="geoLoading ? 'Mengambil lokasi…' : 'Ambil lokasi saya'"></span>
                                </button>
                                <button type="button" @click="showManualCoords = !showManualCoords"
                                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-cream-200 text-navy-800 text-sm font-semibold hover:border-gold-400/60 transition">
                                    <i class="fa-solid fa-pen-to-square text-gold-600"></i> Isi lat/lng manual
                                </button>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1" for="maps_paste">Atau tempel link Google Maps</label>
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <input type="url" id="maps_paste" x-model="mapsPaste"
                                           placeholder="https://maps.app.goo.gl/... atau https://www.google.com/maps/..."
                                           class="input-field flex-1 text-sm">
                                    <button type="button" @click="applyPaste()"
                                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-white border border-cream-200 text-navy-800 text-sm font-semibold hover:border-gold-400/60 transition shrink-0">
                                        <i class="fa-solid fa-check text-emerald-600"></i> Pakai link
                                    </button>
                                </div>
                            </div>
                            <div x-show="showManualCoords" x-cloak x-transition class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Latitude</label>
                                    <input type="text" inputmode="decimal" x-model="mapsLat" placeholder="-6.9175" class="input-field text-sm">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Longitude</label>
                                    <input type="text" inputmode="decimal" x-model="mapsLng" placeholder="112.7325" class="input-field text-sm">
                                </div>
                                <div class="flex items-end">
                                    <button type="button" @click="applyCoords()"
                                            class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-navy-900 text-cream-50 text-sm font-semibold hover:bg-navy-800 transition">
                                        <i class="fa-solid fa-link"></i> Buat link
                                    </button>
                                </div>
                            </div>
                            <p x-show="geoError" x-cloak class="text-[11px] md:text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2" x-text="geoError"></p>
                            <div x-show="mapsLink" x-cloak x-transition
                                 class="flex flex-col sm:flex-row sm:items-center gap-2 p-3 rounded-xl bg-white border border-emerald-200">
                                <div class="flex-1 min-w-0">
                                    <div class="text-[10px] font-bold uppercase tracking-wide text-emerald-700 mb-0.5">Preview pin</div>
                                    <a :href="mapsLink" target="_blank" rel="noopener"
                                       class="text-sm font-semibold text-navy-800 hover:text-gold-600 break-all underline decoration-gold-300/60"
                                       x-text="mapsLink"></a>
                                </div>
                                <div class="flex gap-2 shrink-0">
                                    <a :href="mapsLink" target="_blank" rel="noopener"
                                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold hover:bg-emerald-100 transition">
                                        <i class="fa-solid fa-external-link"></i> Buka di Maps
                                    </a>
                                    <button type="button" @click="clearMaps()"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs font-bold hover:bg-red-100 transition">
                                        <i class="fa-solid fa-xmark"></i> Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Jadwal Penjemputan (semua area — area diambil dari jadwal yang dipilih) -->
                    <div class="md:col-span-2">
                        <label class="form-label" for="jadwal_jemput">
                            <i class="fa-solid fa-clock-rotate-left text-gold-600 mr-1.5"></i> Pilih Jadwal Penjemputan <span class="text-red-500">*</span>
                        </label>
                        <select id="jadwal_jemput" name="jadwal_jemput" required x-model="jadwal"
                                class="input-field font-bold text-slate-800 bg-white">
                            <option value="" disabled>— Pilih jadwal penjemputan —</option>
                            <optgroup label="📌 Jadwal Penjemputan Blora">
                                <option value="Jam 08.00 - Kota-kota Penjemputan Blora">🌆 Jam 08.00 — Kota-kota Penjemputan (Blora)</option>
                                <option value="Jam 11.00 - Door to Door Semua Kecamatan Blora (Unit Hiace)">🚐 Jam 11.00 — Door to Door SEMUA KECAMATAN BLORA (Unit Hiace)</option>
                                <option value="Jam 20.00 - Kota-kota Penjemputan Blora">🌙 Jam 20.00 — Kota-kota Penjemputan (Blora)</option>
                            </optgroup>
                            <optgroup label="📌 Jadwal Penjemputan Surabaya / Sidoarjo">
                                <option value="Jam 10.00 - Start dari Bandara Juanda (Surabaya)">✈️ Jam 10.00 — Start dari Bandara Juanda (Surabaya)</option>
                                <option value="Jam 15.00 - Start dari Bandara Juanda (Surabaya)">✈️ Jam 15.00 — Start dari Bandara Juanda (Surabaya)</option>
                                <option value="Jam 20.00 - Start dari Sidoarjo (Door to Door Sidoarjo + Surabaya/Gresik)">🚐 Jam 20.00 — Start dari Sidoarjo (Door to Door Sidoarjo + Surabaya/Gresik)</option>
                            </optgroup>
                        </select>
                        <div x-show="lokasi === 'Blora'" x-transition
                             class="flex items-start gap-2 mt-2.5 p-3.5 rounded-2xl bg-cream-50 border border-gold-200 text-[11px] md:text-xs font-medium text-navy-800">
                            <i class="fa-solid fa-circle-info text-amber-600 mt-0.5 shrink-0"></i>
                            <div>💡 <strong>Jam 11.00 khusus D2D:</strong> Penjemputan mencakup <strong>SEMUA KECAMATAN YANG ADA DI BLORA</strong> menggunakan Unit Hiace. Nyaman & langsung antar sampai rumah!</div>
                        </div>
                        <div x-show="lokasi === 'Surabaya'" x-transition
                             class="flex items-start gap-2 mt-2.5 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-[11px] md:text-xs font-bold text-emerald-800">
                            <i class="fa-solid fa-circle-info text-emerald-600 mt-0.5 shrink-0"></i>
                            <div>💡 <strong>Jam 20.00 khusus D2D:</strong> Penjemputan mencakup <strong>SEMUA KECAMATAN SIDOARJO & SURABAYA/GRESIK</strong>.</div>
                        </div>
                    </div>
                    <!-- Barang Bawaan -->
                    <div class="md:col-span-2">
                        <label class="form-label" for="barang_bawaan">
                            <i class="fa-solid fa-suitcase-rolling text-gold-600 mr-1.5"></i> Barang Bawaan <span class="text-slate-400 font-normal">(opsional)</span>
                        </label>
                        <input type="text" id="barang_bawaan" name="barang_bawaan"
                               value="<?= e(old('barang_bawaan')) ?>"
                               placeholder="Contoh: 1 koper besar, 2 kardus, 1 tas ransel"
                               class="input-field">
                    </div>
                </div>

                <div class="relative p-5 md:p-6 rounded-2xl bg-navy-900 text-cream-50 flex justify-center md:justify-end overflow-hidden">
                    <div class="absolute top-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-gold-400 to-transparent"></div>
                    <button type="submit" class="shine-btn bg-gradient-to-r from-gold-300 via-gold-400 to-gold-600 text-navy-900 font-bold px-7 py-3.5 md:px-8 md:py-4 rounded-xl shadow-gold-glow transition transform hover:-translate-y-0.5 flex items-center gap-2 justify-center text-sm md:text-base relative z-10">
                        <i class="fa-solid fa-paper-plane"></i> Booking Sekarang
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- ============ SCRIPT KHUSUS FORM BOOKING ============ -->
<style>[x-cloak]{display:none!important}</style>
<script>
/* Hitung total harga realtime - BERDASARKAN RUTE DIPILIH */
const HARGA_DEFAULT_FALLBACK = <?= (int)$hargaPerOrg ?>;
const RUTE_AWAL_NAMA = <?= json_encode($ruteAwal['nama_rute'] ?? 'Pilih Rute') ?>;

function hitungTotal(){
    const selRute = document.getElementById('id_rute');
    let hargaPerOrg = HARGA_DEFAULT_FALLBACK;
    let namaRute   = RUTE_AWAL_NAMA;
    if (selRute && selRute.selectedOptions.length > 0) {
        const opt = selRute.selectedOptions[0];
        const h = parseInt(opt.dataset.harga);
        if (h > 0) hargaPerOrg = h;
        if (opt.dataset.nama) namaRute = opt.dataset.nama;
    }
    const kursiEl = document.getElementById('jumlah_kursi');
    const jml = Math.max(1, parseInt(kursiEl && kursiEl.value) || 1);
    const total = jml * hargaPerOrg;
    const totalEl = document.getElementById('totalTampil');
    const detailEl = document.getElementById('detailTampil');
    if (totalEl) totalEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
    if (detailEl) {
        detailEl.innerHTML =
            jml + ' kursi × <b class="text-yellow-300">' +
            'Rp ' + hargaPerOrg.toLocaleString('id-ID') + '</b>' +
            ' <span class="opacity-80 text-[11px] md:text-xs">(' + namaRute.replace(/</g, '&lt;').replace(/>/g, '&gt;') + ')</span>';
    }
}
if (document.getElementById('id_rute')) {
    document.getElementById('id_rute').addEventListener('change', hitungTotal);
}
document.getElementById('jumlah_kursi').addEventListener('change', hitungTotal);
document.getElementById('jumlah_kursi').addEventListener('input', hitungTotal);
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    hitungTotal();
} else {
    document.addEventListener('DOMContentLoaded', hitungTotal);
    setTimeout(hitungTotal, 100);
}
/* Alpine: jadwal + pin Maps opsional (tanpa Google Maps API key) */
function formJadwal() {
    return {
        lokasi: 'Blora',
        jadwal: '',
        mapsLink: '',
        mapsPaste: '',
        mapsLat: '',
        mapsLng: '',
        geoLoading: false,
        geoError: '',
        showManualCoords: false,
        initLokasi(defLok, defJad, defMaps) {
            this.jadwal = defJad && defJad.length > 3 ? defJad : 'Jam 08.00 - Kota-kota Penjemputan Blora';
            this.lokasi = this.inferLokasi(this.jadwal) || (defLok === 'Surabaya' ? 'Surabaya' : 'Blora');
            this.mapsLink = defMaps || '';
            if (this.mapsLink) this.mapsPaste = this.mapsLink;
            this.$watch('jadwal', (v) => {
                this.lokasi = this.inferLokasi(v);
            });
        },
        inferLokasi(jadwal) {
            const j = (jadwal || '').toLowerCase();
            if (j.includes('surabaya') || j.includes('sidoarjo') || j.includes('juanda') || j.includes('gresik')) {
                return 'Surabaya';
            }
            return 'Blora';
        },
        isValidMapsUrl(url) {
            if (!url || typeof url !== 'string') return false;
            return /^https?:\/\/(www\.)?(google\.[a-z.]+\/maps|maps\.google\.[a-z.]+|maps\.app\.goo\.gl|goo\.gl\/maps)/i.test(url.trim());
        },
        setMapsLink(url) {
            const u = (url || '').trim();
            if (!this.isValidMapsUrl(u)) {
                this.geoError = 'Link harus dari Google Maps (maps.google.com / maps.app.goo.gl).';
                return false;
            }
            this.mapsLink = u;
            this.mapsPaste = u;
            this.geoError = '';
            return true;
        },
        ambilLokasiSaya() {
            this.geoError = '';
            if (!navigator.geolocation) {
                this.geoError = 'Browser tidak mendukung GPS. Silakan tempel link Maps atau isi lat/lng manual.';
                this.showManualCoords = true;
                return;
            }
            this.geoLoading = true;
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.geoLoading = false;
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    this.mapsLat = String(lat);
                    this.mapsLng = String(lng);
                    this.setMapsLink('https://www.google.com/maps?q=' + lat + ',' + lng);
                },
                (err) => {
                    this.geoLoading = false;
                    this.showManualCoords = true;
                    if (err && err.code === 1) {
                        this.geoError = 'Akses lokasi ditolak. Tempel link Google Maps atau isi lat/lng manual di bawah.';
                    } else {
                        this.geoError = 'Gagal mengambil lokasi. Tempel link Maps atau isi lat/lng manual.';
                    }
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 }
            );
        },
        applyPaste() {
            this.geoError = '';
            if (!this.setMapsLink(this.mapsPaste)) {
                if (!(this.mapsPaste || '').trim()) {
                    this.geoError = 'Tempel link Google Maps terlebih dahulu.';
                }
            }
        },
        applyCoords() {
            this.geoError = '';
            const lat = parseFloat(String(this.mapsLat).replace(',', '.'));
            const lng = parseFloat(String(this.mapsLng).replace(',', '.'));
            if (!Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
                this.geoError = 'Latitude / longitude tidak valid.';
                return;
            }
            this.setMapsLink('https://www.google.com/maps?q=' + lat + ',' + lng);
        },
        clearMaps() {
            this.mapsLink = '';
            this.mapsPaste = '';
            this.mapsLat = '';
            this.mapsLng = '';
            this.geoError = '';
        }
    };
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
