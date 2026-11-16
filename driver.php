<?php
// =============================================
// HALAMAN KHUSUS DRIVER (tanpa login admin)
// Link rahasia: /driver.php?k=TOKEN
// Impor form WA (teks / screenshot) → isi otomatis → simpan booking
// =============================================
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/wa_booking_parser.php';

$token = trim((string)($_GET['k'] ?? ''));
$ok = driver_token_ok($token);
$listRute = $ok ? get_rutes(true) : [];
$defaultHarga = $ok ? get_harga_rute_default() : HARGA_TIKET_DEFAULT;
$aiAvailable = $ok && wa_booking_ai_available();
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Form input booking driver Mustika Travel">
    <title>Input Driver · <?= e(SITE_NAME) ?></title>
    <link rel="icon" type="image/jpeg" href="<?= BASE_URL ?>/logo.jpeg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: { 300:'#D4B56A', 400:'#C9A227', 600:'#9A7B1F', 700:'#7A6118' },
                        navy: { 700:'#122544', 800:'#0F2240', 900:'#0A1628', 950:'#070F1C' },
                        cream: { 50:'#FDFBF7', 100:'#F7F3EB', 200:'#EDE6D6' }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
                        serif: ['Cormorant Garamond', 'Georgia', 'serif']
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;1,500&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
    <style>
        [x-cloak]{display:none!important}
        body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:#FDFBF7;color:#1e293b}
        .font-serif{font-family:'Cormorant Garamond',Georgia,serif}
        .drv-card{background:#fff;border:1.5px solid #E5D9B8;border-radius:1.1rem;box-shadow:0 12px 28px -20px rgba(10,22,40,.35)}
        .drv-label{display:block;font-size:.68rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:#64748b;margin-bottom:.28rem}
        .drv-input{width:100%;min-height:44px;border:1.5px solid #E5D9B8;border-radius:.75rem;padding:.55rem .75rem;font-size:.9rem;font-weight:600;background:#fff;color:#0A1628}
        .drv-input:focus{outline:2px solid #C9A227;outline-offset:1px;border-color:#C9A227}
        textarea.drv-input{min-height:72px;resize:vertical}
        .drv-btn{display:inline-flex;align-items:center;justify-content:center;gap:.45rem;min-height:46px;padding:.65rem 1.1rem;border-radius:.85rem;font-weight:800;font-size:.88rem}
        .drv-btn-primary{background:linear-gradient(135deg,#D4B56A,#C9A227 48%,#9A7B1F);color:#0A1628}
        .drv-btn-dark{background:#0A1628;color:#F5EBD0}
        .drv-btn-ghost{background:#fff;border:1.5px solid #E5D9B8;color:#0A1628}
        .drv-file{display:flex;align-items:center;gap:.6rem;min-height:48px;padding:.65rem .85rem;border:1.5px dashed #C9A227;border-radius:.85rem;background:#FFFDF5;cursor:pointer;font-size:.82rem;font-weight:700;color:#7A6118}
        .drv-err{margin-top:.75rem;padding:.7rem .85rem;border-radius:.75rem;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:.8rem;font-weight:700}
        .drv-ok{margin-top:.75rem;padding:.7rem .85rem;border-radius:.75rem;background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;font-size:.8rem;font-weight:700}
        .drv-banner{display:flex;gap:.55rem;align-items:flex-start;padding:.7rem .85rem;border-radius:.85rem;background:#eff6ff;border:1px solid #bfdbfe;color:#1e3a8a;font-size:.78rem;font-weight:600}
    </style>
</head>
<body class="min-h-screen">
<?php if (!$ok): ?>
    <main class="max-w-md mx-auto px-4 py-16 text-center">
        <div class="drv-card p-8">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-navy-900 text-gold-300 flex items-center justify-center text-xl mb-4">
                <i class="fa-solid fa-link-slash"></i>
            </div>
            <h1 class="font-serif text-2xl text-navy-900 mb-2">Link tidak valid</h1>
            <p class="text-sm text-slate-600 font-medium leading-relaxed">
                Halaman ini khusus driver. Minta <b>link yang benar</b> ke admin Mustika Travel.
            </p>
        </div>
    </main>
<?php else: ?>
<main class="max-w-xl mx-auto px-4 py-6 pb-16" x-data="driverForm()" x-cloak>
    <header class="mb-5">
        <div class="flex items-center gap-3 mb-3">
            <img src="<?= BASE_URL ?>/logo.jpeg" alt="" class="w-11 h-11 rounded-xl object-cover border border-cream-200"
                 onerror="this.style.display='none'">
            <div>
                <div class="text-[11px] font-extrabold uppercase tracking-wider text-gold-700">Khusus driver · tanpa login</div>
                <h1 class="font-serif text-2xl text-navy-900 leading-tight">Input Booking</h1>
            </div>
        </div>
        <p class="text-sm text-slate-600 font-medium leading-relaxed">
            Tempel form reservasi WA (atau upload screenshot yang sudah diisi) → data terisi otomatis → cek → simpan.
        </p>
    </header>

    <!-- STEP 1 -->
    <section class="drv-card p-4 sm:p-5 space-y-3" x-show="step === 1">
        <label class="drv-label">Tempel teks form WhatsApp</label>
        <textarea class="drv-input font-normal" rows="10" x-model="chatText"
                  placeholder=">> Mustika Travel <<&#10;&#10;FORM RESERVASI BOOKING ONLINE :&#10;&#10;Nama : Budi&#10;No.Hp : 0812...&#10;Alamat Penjemputan : ...&#10;Alamat Tujuan : ...&#10;Jumlah Seat/Kursi : 2&#10;Hari &Tanggal : 17 November 2026&#10;Estimasi Jam Jemput : 08.00&#10;Barang Bawaan : 1 koper"></textarea>

        <?php if ($aiAvailable): ?>
        <div>
            <label class="drv-label">Atau upload screenshot form (sudah terisi)</label>
            <label class="drv-file">
                <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" @change="onFile($event)">
                <i class="fa-solid fa-camera"></i>
                <span x-text="fileName || 'Pilih gambar JPG / PNG'"></span>
            </label>
            <button type="button" class="text-[11px] font-bold text-red-600 mt-1 underline" x-show="fileName" @click="clearFile()">Hapus gambar</button>
        </div>
        <?php else: ?>
        <p class="text-[11px] text-slate-500 font-semibold">Screenshot belum aktif di server — tempel teks form saja.</p>
        <?php endif; ?>

        <div>
            <label class="drv-label">Nama driver (opsional)</label>
            <input type="text" class="drv-input" x-model="driverName" placeholder="Mis. Arif" maxlength="40">
        </div>

        <div class="drv-err" x-show="error" x-text="error"></div>

        <button type="button" class="drv-btn drv-btn-primary w-full" :disabled="loading" @click="extract()">
            <i class="fa-solid" :class="loading ? 'fa-spinner fa-spin' : 'fa-wand-magic-sparkles'"></i>
            <span x-text="loading ? 'Membaca form…' : 'Isi otomatis'"></span>
        </button>
        <button type="button" class="drv-btn drv-btn-ghost w-full" @click="goManual()">
            <i class="fa-solid fa-pen"></i> Isi manual tanpa tempel
        </button>
    </section>

    <!-- STEP 2 -->
    <section class="space-y-3" x-show="step === 2">
        <div class="drv-banner">
            <i class="fa-solid fa-circle-check mt-0.5"></i>
            <div x-text="infoMsg || 'Periksa data di bawah, lalu simpan.'"></div>
        </div>

        <div class="drv-card p-4 sm:p-5 space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="sm:col-span-2">
                    <label class="drv-label">Nama *</label>
                    <input type="text" class="drv-input" x-model="form.nama" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="drv-label">No. HP *</label>
                    <input type="tel" class="drv-input" x-model="form.no_hp" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="drv-label">Alamat penjemputan *</label>
                    <textarea class="drv-input" rows="2" x-model="form.alamat_jemput"></textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="drv-label">Alamat tujuan *</label>
                    <textarea class="drv-input" rows="2" x-model="form.alamat_tujuan"></textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="drv-label">Rute</label>
                    <select class="drv-input" x-model.number="form.id_rute" @change="hitungTotal()">
                        <option value="0">— Custom / tanpa rute —</option>
                        <?php foreach ($listRute as $r): ?>
                            <option value="<?= (int)$r['id'] ?>" data-harga="<?= (int)$r['harga'] ?>">
                                <?= e($r['nama_rute']) ?> · <?= rupiah($r['harga']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="text-[11px] text-slate-500 mt-1 font-semibold" x-show="form.rute">
                        Deteksi: <span class="text-navy-900" x-text="form.rute"></span>
                    </div>
                </div>
                <div>
                    <label class="drv-label">Jumlah seat *</label>
                    <input type="number" min="1" max="50" class="drv-input" x-model.number="form.jumlah_kursi" @input="hitungTotal()">
                </div>
                <div>
                    <label class="drv-label">Area jemput</label>
                    <select class="drv-input" x-model="form.lokasi_jemput">
                        <option>Blora</option>
                        <option>Surabaya</option>
                        <option>Sidoarjo</option>
                        <option>Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="drv-label">Tanggal *</label>
                    <input type="date" class="drv-input" x-model="form.tanggal_berangkat">
                </div>
                <div>
                    <label class="drv-label">Jam jemput *</label>
                    <input type="time" class="drv-input" x-model="form.jam_jemput">
                </div>
                <div class="sm:col-span-2">
                    <label class="drv-label">Barang bawaan</label>
                    <input type="text" class="drv-input" x-model="form.barang_bawaan" placeholder="1 koper, dll">
                </div>
                <div class="sm:col-span-2">
                    <label class="drv-label">Pin Maps (opsional)</label>
                    <input type="url" class="drv-input" x-model="form.maps_link" placeholder="https://maps.app.goo.gl/...">
                </div>
                <div class="sm:col-span-2">
                    <label class="drv-label">Total harga</label>
                    <input type="number" min="0" step="1000" class="drv-input" x-model.number="form.total_harga">
                </div>
            </div>

            <div class="drv-err" x-show="error" x-text="error"></div>
            <div class="drv-ok" x-show="saveMsg" x-text="saveMsg"></div>

            <div class="flex flex-col sm:flex-row gap-2 pt-1">
                <button type="button" class="drv-btn drv-btn-ghost flex-1" @click="step=1; error=''; saveMsg=''" :disabled="saving">
                    ← Ubah teks / screenshot
                </button>
                <button type="button" class="drv-btn drv-btn-dark flex-1" :disabled="saving" @click="save()">
                    <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                    <span x-text="saving ? 'Menyimpan…' : 'Simpan booking'"></span>
                </button>
            </div>
        </div>

        <button type="button" class="drv-btn drv-btn-ghost w-full" x-show="saveMsg" @click="resetAll()">
            <i class="fa-solid fa-plus"></i> Input booking berikutnya
        </button>
    </section>
</main>

<script>
function driverForm() {
    const API = <?= json_encode(rtrim(BASE_URL, '/') . '/driver_api.php', JSON_UNESCAPED_UNICODE) ?>;
    const TOKEN = <?= json_encode($token, JSON_UNESCAPED_UNICODE) ?>;
    const DEFAULT_HARGA = <?= (int)$defaultHarga ?>;
    let csrf = <?= json_encode($csrf, JSON_UNESCAPED_UNICODE) ?>;
    const empty = () => ({
        nama: '', no_hp: '', alamat_jemput: '', alamat_tujuan: '',
        id_rute: 0, rute: '', jumlah_kursi: 1,
        tanggal_berangkat: '<?= date('Y-m-d') ?>',
        jam_jemput: '08:00', lokasi_jemput: 'Blora',
        barang_bawaan: '', maps_link: '', total_harga: DEFAULT_HARGA, catatan_admin: ''
    });
    return {
        step: 1,
        loading: false,
        saving: false,
        error: '',
        infoMsg: '',
        saveMsg: '',
        chatText: '',
        file: null,
        fileName: '',
        driverName: '',
        aiAvailable: <?= $aiAvailable ? 'true' : 'false' ?>,
        form: empty(),
        onFile(ev) {
            const f = ev.target.files && ev.target.files[0] ? ev.target.files[0] : null;
            this.file = f;
            this.fileName = f ? f.name : '';
            this.error = '';
        },
        clearFile() { this.file = null; this.fileName = ''; },
        goManual() {
            this.form = empty();
            this.infoMsg = 'Isi manual. Pastikan data benar sebelum simpan.';
            this.error = '';
            this.saveMsg = '';
            this.step = 2;
        },
        hitungTotal() {
            const hargaMap = <?= json_encode(array_column($listRute, 'harga', 'id'), JSON_UNESCAPED_UNICODE) ?>;
            let harga = DEFAULT_HARGA;
            const id = parseInt(this.form.id_rute, 10) || 0;
            if (id > 0 && hargaMap[id]) harga = parseInt(hargaMap[id], 10) || harga;
            const jml = Math.max(1, parseInt(this.form.jumlah_kursi, 10) || 1);
            this.form.total_harga = jml * harga;
        },
        async extract() {
            this.error = '';
            const text = (this.chatText || '').trim();
            if (!text && !this.file) {
                this.error = 'Tempel teks form WA dulu' + (this.aiAvailable ? ', atau pilih screenshot.' : '.');
                return;
            }
            if (!text && this.file && !this.aiAvailable) {
                this.error = 'Screenshot belum aktif. Tempel teks form saja.';
                return;
            }
            this.loading = true;
            try {
                const fd = new FormData();
                fd.append('csrf_token', csrf);
                fd.append('k', TOKEN);
                fd.append('action', 'parse');
                fd.append('chat_text', text);
                if (this.file) fd.append('screenshot', this.file);
                const res = await fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json();
                if (data.csrf) csrf = data.csrf;
                if (!data || !data.ok) {
                    this.error = (data && data.message) ? data.message : 'Gagal membaca form.';
                    return;
                }
                const f = data.fields || {};
                this.form = Object.assign(empty(), f, {
                    id_rute: parseInt(f.id_rute, 10) || 0,
                    jumlah_kursi: Math.max(1, parseInt(f.jumlah_kursi, 10) || 1),
                    total_harga: parseInt(f.total_harga, 10) || DEFAULT_HARGA,
                    lokasi_jemput: f.lokasi_jemput || 'Blora',
                    jam_jemput: (f.jam_jemput || '08:00').substring(0, 5)
                });
                this.infoMsg = data.message || 'Data terisi. Periksa dulu sebelum simpan.';
                this.step = 2;
                this.$nextTick(() => this.hitungTotal());
            } catch (e) {
                this.error = 'Koneksi gagal. Coba lagi atau isi manual.';
            } finally {
                this.loading = false;
            }
        },
        async save() {
            this.error = '';
            this.saveMsg = '';
            if ((this.form.nama || '').trim().length < 2) { this.error = 'Nama wajib diisi.'; return; }
            if (!(this.form.no_hp || '').trim()) { this.error = 'No. HP wajib diisi.'; return; }
            if (!(this.form.alamat_jemput || '').trim() || !(this.form.alamat_tujuan || '').trim()) {
                this.error = 'Alamat jemput & tujuan wajib.'; return;
            }
            this.saving = true;
            try {
                const fd = new FormData();
                fd.append('csrf_token', csrf);
                fd.append('k', TOKEN);
                fd.append('action', 'save');
                fd.append('driver_name', this.driverName || '');
                Object.keys(this.form).forEach(k => fd.append(k, this.form[k] ?? ''));
                const res = await fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json();
                if (data.csrf) csrf = data.csrf;
                if (!data || !data.ok) {
                    this.error = (data && data.message) ? data.message : 'Gagal menyimpan.';
                    return;
                }
                this.saveMsg = data.message || 'Tersimpan.';
            } catch (e) {
                this.error = 'Koneksi gagal saat simpan.';
            } finally {
                this.saving = false;
            }
        },
        resetAll() {
            this.step = 1;
            this.chatText = '';
            this.clearFile();
            this.form = empty();
            this.error = '';
            this.saveMsg = '';
            this.infoMsg = '';
        }
    };
}
</script>
<?php endif; ?>
</body>
</html>
