<?php
require_once __DIR__ . '/includes/auth_check.php';
$active_menu = 'armadas';
$page_title  = 'Koleksi Armada';
require_once __DIR__ . '/includes/admin_header.php';

$flash = get_flash();

$armadas = get_armadas(false);
$totalAktif = 0;
foreach ($armadas as $a) { if((int)$a['is_aktif']===1) $totalAktif++; }
$totalNonaktif = count($armadas) - $totalAktif;

// Load old input (jika error saat tambah/edit)
$old = [];
if (isset($_SESSION['old_input'])) {
    $old = $_SESSION['old_input'];
}
// Parse old fiturs dari POST
$oldFiturJson = '[]';
if (!empty($old) && isset($old['fiturs']) && is_array($old['fiturs'])) {
    $oldFiturJson = json_encode(array_values($old['fiturs']), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);
}
$oldJson = json_encode($old, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);
?>

<div class="no-print">
    <?php if ($flash) echo '<div class="mb-6">' . $flash . '</div>'; ?>

    <section class="dash-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div class="min-w-0">
                <div class="dash-hero-kicker">Master data · Armada</div>
                <h1 class="dash-hero-title">Koleksi <em>Armada</em></h1>
                <p class="dash-hero-desc"><?= count($armadas) ?> unit · <?= (int)$totalAktif ?> aktif · <?= (int)$totalNonaktif ?> tersembunyi.</p>
            </div>
            <div class="dash-hero-actions">
                <button type="button" onclick="window.modalTambah.open()" class="is-gold"><i class="fa-solid fa-plus"></i> Tambah armada</button>
            </div>
        </div>
    </section>

    <!-- GRID KARTU ARMADA (PREVIEW LANGSUNG!) -->
    <?php if (empty($armadas)): ?>
        <div class="card empty-state">
            <div class="empty-state-icon"><i class="fa-solid fa-car-side"></i></div>
            <div class="font-semibold text-slate-600 mb-1">Belum ada armada yang dibuat.</div>
            <div class="text-sm">Klik <button type="button" onclick="window.modalTambah.open()" class="text-navy-800 underline font-bold">Tambah armada baru</button> untuk mulai.</div>
        </div>
    <?php else: ?>
        <div class="catalog-grid">
            <?php
            $nomor = 1;
            foreach ($armadas as $a):
                $a_json = htmlspecialchars(json_encode($a), ENT_QUOTES, 'UTF-8');
                $aktif = (int)$a['is_aktif'] === 1;
                $fiturs = parse_fitur_armada($a['fitur_list'] ?? '');
                $img = trim($a['gambar_url'] ?? '');
                $isHttp = (stripos($img, 'http') === 0);
                $isUpload = (stripos($img, 'uploads/') === 0 || stripos($img, '/uploads/') !== false);
                if ($img === '') {
                    $imgSrc = 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=700&q=80';
                } elseif ($isUpload) {
                    $imgSrc = BASE_URL . '/' . ltrim($img, '/');
                } elseif ($isHttp) {
                    $imgSrc = $img;
                } else {
                    $imgSrc = "https://images.unsplash.com/{$img}?w=700&q=80";
                }
                $grad = trim($a['warna_grad'] ?? 'from-primary-600 to-blue-800');
                $iconFa = trim($a['icon_fa'] ?? 'fa-car-side');
            ?>
                <div class="catalog-card group">
                    <!-- Gambar + badge status + crown -->
                    <div class="relative h-40 md:h-44 overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br <?= $grad ?> opacity-25 z-10"></div>
                        <img src="<?= e($imgSrc) ?>" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=700&q=80';"
                             alt="<?= e($a['nama_armada']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        <!-- Status aktif badge -->
                        <div class="absolute top-3 left-3 z-20">
                            <?php if ($aktif): ?>
                                <span class="badge bg-emerald-50 text-emerald-700 border-emerald-200 !py-0.5 !px-2.5 !text-[10px]">
                                    <i class="fa-solid fa-circle-check mr-1 text-[8px]"></i>Aktif
                                </span>
                            <?php else: ?>
                                <span class="badge bg-slate-100 text-slate-500 border-slate-200 !py-0.5 !px-2.5 !text-[10px]">
                                    <i class="fa-solid fa-eye-slash mr-1 text-[8px]"></i>Nonaktif
                                </span>
                            <?php endif; ?>
                        </div>
                        <!-- Kapasitas badge -->
                        <div class="absolute bottom-3 left-3 z-20">
                            <div class="px-3 py-1.5 rounded-full bg-[#0A1628]/90 backdrop-blur text-[#F5EBD0] font-black text-[11px] border border-white/20 shadow-card flex items-center gap-1.5">
                                <i class="fa-solid fa-users text-gold-400 text-[10px]"></i>
                                <?= e($a['kapasitas']) ?>
                            </div>
                        </div>
                        <div class="absolute bottom-3 right-3 z-20">
                            <div class="w-7 h-7 rounded-full bg-gold-500 text-navy-950 font-black text-[10px] flex items-center justify-center border-2 border-white shadow-card">
                                #<?= $nomor++ ?>
                            </div>
                        </div>
                    </div>
                    <!-- Body deskripsi + nama -->
                    <div class="p-4 flex-1 flex flex-col">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br <?= $grad ?> text-white flex items-center justify-center shadow-md shrink-0 border border-white">
                                    <i class="fa-solid <?= $iconFa ?> text-sm"></i>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-black text-slate-900 text-sm leading-tight truncate"><?= e($a['nama_armada']) ?></h3>
                                    <div class="text-[10px] text-slate-400 mt-0.5">ID #<?= $a['id'] ?? 0 ?> · Urut <?= (int)($a['urutan'] ?? 99) ?></div>
                                </div>
                            </div>
                        </div>
                        <p class="text-[11px] md:text-xs text-slate-600 leading-snug mb-3 line-clamp-2 min-h-[2rem]">
                            <?= e(mb_strimwidth($a['deskripsi'] ?? '', 0, 120, '...')) ?>
                        </p>
                        <!-- Fitur preview chips -->
                        <div class="mt-auto">
                            <?php if (!empty($fiturs)): ?>
                                <div class="flex flex-wrap gap-1 mb-3">
                                    <?php
                                    $showFitur = array_slice($fiturs, 0, 4);
                                    $sisa = count($fiturs) - 4;
                                    foreach ($showFitur as $f):
                                    ?>
                                        <span class="catalog-chip">
                                            <i class="fa-solid fa-check text-[7px] text-gold-600"></i>
                                            <?= e(mb_strimwidth($f, 0, 15, '..')) ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if ($sisa > 0): ?>
                                        <span class="catalog-chip is-more">+<?= $sisa ?> lagi</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <!-- Action buttons -->
                            <div class="flex items-center justify-between pt-2.5 border-t border-cream-200">
                                <button type="button" onclick="window.modalEdit.open(<?= $a_json ?>)" class="btn-secondary !min-h-[40px] !px-3 text-xs" title="Edit data armada">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </button>
                                <div class="flex items-center gap-1.5">
                                    <form method="POST" action="<?= BASE_URL ?>/admin/proses_crud_armada.php" class="inline-block">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="toggle-aktif">
                                        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                        <button type="submit"
                                                class="btn-icon <?= $aktif ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?>"
                                                title="<?= $aktif ? 'Nonaktifkan (sembunyikan dari customer)' : 'Aktifkan (tampilkan di customer)' ?>">
                                            <i class="fa-solid <?= $aktif ? 'fa-eye-slash' : 'fa-eye' ?> text-[11px]"></i>
                                        </button>
                                    </form>
                                    <button type="button"
                                            data-hapus-id="<?= (int)$a['id'] ?>"
                                            data-hapus-nama="<?= e($a['nama_armada']) ?>"
                                            class="js-hapus btn-icon bg-red-50 text-red-600 border-red-200" title="Hapus">
                                        <i class="fa-solid fa-trash text-[11px]"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Info Box -->
    <div class="mt-6 dash-panel">
        <div class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-gold-600 mb-2">Catatan foto</div>
        <ul class="text-sm text-slate-600 space-y-1.5 font-medium">
            <li>Upload JPG/PNG/WEBP maks 2MB, atau isi URL / ID Unsplash.</li>
            <li>Toggle mata menyembunyikan armada dari halaman depan tanpa hapus data.</li>
        </ul>
    </div>
</div>

<!-- ===========================================
     MODAL TAMBAH ARMADA BARU (Alpine.js + Dynamic Fiturs!)
     =========================================== -->
<div x-data="modalTambahArmada()" x-init="window.modalTambah = $data">
    <template x-if="show">
        <div class="admin-modal-overlay" x-transition.opacity>
            <div @click="close()" class="absolute inset-0"></div>
            <div class="admin-modal w-full max-w-3xl" @click.stop>
                <div class="admin-modal-head">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="admin-modal-ico"><i class="fa-solid fa-plus"></i></div>
                        <div class="min-w-0">
                            <h3>Tambah armada</h3>
                            <p>Foto, kapasitas, dan fasilitas — tampil di halaman depan jika aktif</p>
                        </div>
                    </div>
                    <button type="button" @click="close()" class="admin-modal-close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form method="POST" action="<?= BASE_URL ?>/admin/proses_crud_armada.php" class="flex-1 min-h-0 flex flex-col" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="tambah">
                    <div class="admin-modal-body space-y-4">
                        <div class="admin-modal-section">Identitas</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="form-label">Nama armada *</label>
                                <input name="nama_armada" required type="text" class="input-field"
                                       placeholder="Misal: Toyota Hiace Premio / Bus Pariwisata Premium"
                                       x-model="form.nama_armada">
                            </div>
                            <div>
                                <label class="form-label">Icon (Font Awesome)</label>
                                <input name="icon_fa" type="text" class="input-field"
                                       placeholder="fa-van-shuttle / fa-bus-simple / fa-car-side"
                                       x-model="form.icon_fa">
                                <div class="text-[11px] text-slate-400 mt-1">Cek: fontawesome.com/icons</div>
                            </div>
                            <div>
                                <label class="form-label">Kapasitas penumpang *</label>
                                <input name="kapasitas" required type="text" class="input-field"
                                       placeholder="Misal: Kapasitas 10-15 Kursi"
                                       x-model="form.kapasitas">
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" rows="3" class="input-field !resize-y"
                                          placeholder="Deskripsi keunggulan armada ini (boleh panjang)"
                                          x-model="form.deskripsi"></textarea>
                            </div>
                            <div>
                                <label class="form-label">Warna gradien</label>
                                <input name="warna_grad" type="text" class="input-field"
                                       placeholder="from-blue-600 to-primary-800"
                                       x-model="form.warna_grad">
                            </div>
                            <div>
                                <label class="form-label">Urutan tampil</label>
                                <input name="urutan" type="number" min="1" class="input-field"
                                       placeholder="99"
                                       x-model.number="form.urutan">
                            </div>
                            <div class="md:col-span-2">
                                <div class="admin-modal-section !pt-0 mb-2">Foto armada</div>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2.5">
                                    <label class="form-label !mb-0">Gambar *</label>
                                    <div class="mode-tabs">
                                        <button type="button" @click="form.gambar_mode='upload'"
                                                :class="form.gambar_mode === 'upload' ? 'is-on' : ''"
                                                class="mode-tab">
                                            <i class="fa-solid fa-cloud-arrow-up mr-1"></i> Upload file
                                        </button>
                                        <button type="button" @click="form.gambar_mode='url'"
                                                :class="form.gambar_mode === 'url' ? 'is-on' : ''"
                                                class="mode-tab">
                                            <i class="fa-solid fa-link mr-1"></i> URL / Unsplash
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2.5">
                                        <div x-show="form.gambar_mode === 'upload'" x-transition>
                                            <label class="block cursor-pointer relative group">
                                                <div class="upload-drop">
                                                    <i class="fa-solid fa-cloud-arrow-up text-3xl text-gold-600 mb-2 block"></i>
                                                    <div class="text-xs md:text-sm font-bold text-navy-900 mb-1" x-show="!preview_gambar">
                                                        Klik untuk pilih gambar (maks 2MB)
                                                    </div>
                                                    <div class="text-xs md:text-sm font-bold text-navy-800 mb-1" x-show="preview_gambar" x-cloak>
                                                        <i class="fa-solid fa-file-image mr-1"></i> Ganti gambar
                                                    </div>
                                                    <div class="text-[10px] text-slate-500">Format JPG · PNG · WEBP · JPEG</div>
                                                </div>
                                                <input name="gambar_file" @change="previewUpload($event)"
                                                       accept="image/jpeg,image/jpg,image/png,image/webp"
                                                       type="file" class="sr-only">
                                            </label>
                                            <div class="text-[11px] text-slate-500 mt-1.5">Landscape HD, fokus ke body kendaraan.</div>
                                        </div>
                                        <div x-show="form.gambar_mode === 'url'" x-transition>
                                            <label class="form-label">URL / ID Unsplash</label>
                                            <input name="gambar_url" type="text" class="input-field"
                                                   placeholder="photo-XXXX  ATAU  https://....jpg"
                                                   x-model="form.gambar_url">
                                            <div class="text-[11px] text-slate-400 mt-1">Kosongkan jika memakai upload file.</div>
                                        </div>
                                    </div>
                                    <div class="upload-preview">
                                        <template x-if="preview_gambar">
                                            <img :src="preview_gambar" class="absolute inset-0 w-full h-full object-cover" alt="Preview Gambar">
                                        </template>
                                        <template x-if="!preview_gambar && form.gambar_mode === 'url' && form.gambar_url">
                                            <img :src="resolveGambar(form.gambar_url)"
                                                 onerror="this.onerror=null;this.classList.add('opacity-20')"
                                                 class="absolute inset-0 w-full h-full object-cover" alt="Preview URL">
                                        </template>
                                        <template x-if="!preview_gambar && !(form.gambar_mode === 'url' && form.gambar_url)">
                                            <div class="text-center text-slate-400 p-5">
                                                <i class="fa-regular fa-image text-4xl mb-2 opacity-40"></i>
                                                <div class="text-xs font-semibold">Preview gambar</div>
                                                <div class="text-[10px] mt-0.5">Upload atau isi URL</div>
                                            </div>
                                        </template>
                                        <div x-show="preview_gambar" x-cloak
                                             class="absolute top-2.5 left-2.5 z-10 inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-navy-900/90 text-cream-100 text-[10px] font-black">
                                            <i class="fa-solid fa-check text-[9px] text-gold-400"></i> Siap upload
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Status</label>
                                <div class="mt-0.5">
                                    <label class="inline-flex items-center gap-2.5 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 cursor-pointer hover:bg-emerald-100 transition w-full">
                                        <input type="checkbox" name="is_aktif" x-model="form.is_aktif"
                                               class="w-5 h-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                        <span class="font-bold text-emerald-700 text-sm">Aktif (tampil di customer)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <div class="flex items-center justify-between mb-2.5">
                                <div class="admin-modal-section !pt-0">Fasilitas</div>
                                <button type="button" @click="tambahFitur()" class="btn-secondary !min-h-[36px] !px-3 text-xs">
                                    <i class="fa-solid fa-plus"></i> Tambah
                                </button>
                            </div>
                            <div class="space-y-2">
                                <template x-for="(v, idx) in form.fiturs" :key="idx">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-check text-emerald-600 text-xs w-4 shrink-0"></i>
                                        <input type="text" name="fiturs[]" class="input-field"
                                               placeholder="Contoh: AC Dingin / Reclining Seat / Snack & Air Mineral"
                                               x-model="form.fiturs[idx]">
                                        <button type="button" @click="hapusFitur(idx)"
                                                class="w-9 h-9 rounded-md bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 shrink-0 inline-flex items-center justify-center transition"
                                                title="Hapus fasilitas ini"
                                                :disabled="form.fiturs.length <= 1">
                                            <i class="fa-solid fa-trash text-[11px]"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            <div class="text-[11px] text-slate-400 mt-1.5 font-medium">Kosongkan semua lalu klik Tambah Fasilitas jika ingin reset list.</div>
                        </div>
                    </div>
                    <!-- Footer -->
                    <div class="admin-modal-foot">
                        <button type="button" @click="close()" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Simpan armada</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<!-- ===========================================
     MODAL EDIT ARMADA (Alpine.js + Dynamic Fiturs!)
     =========================================== -->
<div x-data="modalEditArmada()" x-init="window.modalEdit = $data">
    <template x-if="show">
        <div class="admin-modal-overlay" x-transition.opacity>
            <div @click="close()" class="absolute inset-0"></div>
            <div class="admin-modal w-full max-w-3xl" @click.stop>
                <div class="admin-modal-head">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="admin-modal-ico"><i class="fa-solid fa-pen"></i></div>
                        <div class="min-w-0">
                            <h3>Edit armada <span x-text="'#' + form.id">#0</span></h3>
                            <p>Foto, fasilitas, dan tema — upload baru untuk ganti gambar</p>
                        </div>
                    </div>
                    <button type="button" @click="close()" class="admin-modal-close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form method="POST" action="<?= BASE_URL ?>/admin/proses_crud_armada.php" class="flex-1 min-h-0 flex flex-col" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" x-model.number="form.id">
                    <input type="hidden" name="gambar_lama" x-model="form.gambar_lama">
                    <div class="admin-modal-body space-y-4">
                        <div class="admin-modal-section">Identitas</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="form-label">Nama armada *</label>
                                <input name="nama_armada" required type="text" class="input-field" x-model="form.nama_armada">
                            </div>
                            <div>
                                <label class="form-label">Icon (Font Awesome)</label>
                                <input name="icon_fa" type="text" class="input-field" x-model="form.icon_fa">
                            </div>
                            <div>
                                <label class="form-label">Kapasitas penumpang *</label>
                                <input name="kapasitas" required type="text" class="input-field" x-model="form.kapasitas">
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" rows="3" class="input-field !resize-y" x-model="form.deskripsi"></textarea>
                            </div>
                            <div>
                                <label class="form-label">Warna gradien</label>
                                <input name="warna_grad" type="text" class="input-field" x-model="form.warna_grad">
                            </div>
                            <div>
                                <label class="form-label">Urutan tampil</label>
                                <input name="urutan" type="number" min="1" class="input-field" x-model.number="form.urutan">
                            </div>
                            <div class="md:col-span-2">
                                <div class="admin-modal-section !pt-0 mb-2">Foto armada</div>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2.5">
                                    <label class="form-label !mb-0">Gambar</label>
                                    <div class="mode-tabs">
                                        <button type="button" @click="form.gambar_mode='upload'"
                                                :class="form.gambar_mode === 'upload' ? 'is-on' : ''"
                                                class="mode-tab">
                                            <i class="fa-solid fa-cloud-arrow-up mr-1"></i> Upload file
                                        </button>
                                        <button type="button" @click="form.gambar_mode='url'"
                                                :class="form.gambar_mode === 'url' ? 'is-on' : ''"
                                                class="mode-tab">
                                            <i class="fa-solid fa-link mr-1"></i> URL / Unsplash
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2.5">
                                        <div x-show="form.gambar_mode === 'upload'" x-transition>
                                            <label class="block cursor-pointer relative group">
                                                <div class="upload-drop">
                                                    <i class="fa-solid fa-cloud-arrow-up text-3xl text-gold-600 mb-2 block"></i>
                                                    <div class="text-xs md:text-sm font-bold text-navy-900 mb-1" x-show="!preview_gambar">
                                                        Klik untuk pilih gambar baru
                                                    </div>
                                                    <div class="text-xs md:text-sm font-bold text-navy-800 mb-1" x-show="preview_gambar" x-cloak>
                                                        <i class="fa-solid fa-file-image mr-1"></i> Ganti gambar
                                                    </div>
                                                    <div class="text-[10px] text-slate-500">Kosongkan jika tidak ganti · maks 2MB</div>
                                                </div>
                                                <input name="gambar_file" @change="previewUpload($event)"
                                                       accept="image/jpeg,image/jpg,image/png,image/webp"
                                                       type="file" class="sr-only">
                                            </label>
                                            <div x-show="form.gambar_lama" x-cloak class="text-[11px] text-navy-800 mt-1.5 bg-cream-50 border border-cream-200 rounded-lg p-2">
                                                <i class="fa-solid fa-circle-info text-gold-600 mr-1"></i>
                                                Gambar lama tetap dipakai sampai ada upload baru.
                                            </div>
                                        </div>
                                        <div x-show="form.gambar_mode === 'url'" x-transition>
                                            <label class="form-label">URL / ID Unsplash</label>
                                            <input name="gambar_url" type="text" class="input-field"
                                                   placeholder="photo-XXXX  ATAU  https://....jpg"
                                                   x-model="form.gambar_url">
                                            <div class="text-[11px] text-slate-400 mt-1">Isi URL, atau pakai upload untuk file lokal.</div>
                                        </div>
                                    </div>
                                    <div class="upload-preview">
                                        <template x-if="preview_gambar">
                                            <img :src="preview_gambar" class="absolute inset-0 w-full h-full object-cover" alt="Preview Baru">
                                        </template>
                                        <template x-if="!preview_gambar && form.gambar_lama">
                                            <div class="absolute inset-0">
                                                <img :src="resolveGambar(form.gambar_lama)"
                                                     onerror="this.onerror=null;this.classList.add('opacity-20')"
                                                     class="absolute inset-0 w-full h-full object-cover" alt="Gambar Lama">
                                                <div class="absolute top-2.5 left-2.5 z-10 inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-[#0A1628]/90 text-[#F5EBD0] text-[10px] font-black">
                                                    <i class="fa-solid fa-image text-gold-400 mr-1 text-[9px]"></i> Saat ini
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!preview_gambar && !form.gambar_lama && form.gambar_mode === 'url' && form.gambar_url">
                                            <img :src="resolveGambar(form.gambar_url)"
                                                 onerror="this.onerror=null;this.classList.add('opacity-20')"
                                                 class="absolute inset-0 w-full h-full object-cover" alt="Preview URL">
                                        </template>
                                        <template x-if="!preview_gambar && !form.gambar_lama && !(form.gambar_mode === 'url' && form.gambar_url)">
                                            <div class="text-center text-slate-400 p-5">
                                                <i class="fa-regular fa-image text-4xl mb-2 opacity-40"></i>
                                                <div class="text-xs font-semibold">Belum ada gambar</div>
                                                <div class="text-[10px] mt-0.5">Upload atau isi URL</div>
                                            </div>
                                        </template>
                                        <div x-show="preview_gambar" x-cloak
                                             class="absolute top-2.5 right-2.5 z-10 inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-navy-900/90 text-cream-100 text-[10px] font-black">
                                            <i class="fa-solid fa-rotate text-[9px] text-gold-400"></i> Baru
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Status</label>
                                <div class="mt-0.5">
                                    <label class="inline-flex items-center gap-2.5 px-4 py-3 rounded-xl border cursor-pointer transition w-full"
                                           :class="form.is_aktif ? 'bg-emerald-50 border-emerald-200' : 'bg-slate-50 border-slate-200'">
                                        <input type="checkbox" name="is_aktif" x-model="form.is_aktif"
                                               class="w-5 h-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                        <span class="font-bold text-sm" :class="form.is_aktif ? 'text-emerald-700' : 'text-slate-500'">
                                            <span x-text="form.is_aktif ? 'Aktif (tampil di customer)' : 'Nonaktif (tersembunyi)'">Aktif</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <div class="flex items-center justify-between mb-2.5">
                                <div class="admin-modal-section !pt-0">Fasilitas</div>
                                <button type="button" @click="tambahFitur()" class="btn-secondary !min-h-[36px] !px-3 text-xs">
                                    <i class="fa-solid fa-plus"></i> Tambah
                                </button>
                            </div>
                            <div class="space-y-2">
                                <template x-for="(v, idx) in form.fiturs" :key="idx">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-check text-emerald-600 text-xs w-4 shrink-0"></i>
                                        <input type="text" name="fiturs[]" class="input-field"
                                               placeholder="Contoh: AC Dingin / Reclining Seat / Snack & Air Mineral"
                                               x-model="form.fiturs[idx]">
                                        <button type="button" @click="hapusFitur(idx)"
                                                class="w-9 h-9 rounded-md bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 shrink-0 inline-flex items-center justify-center transition"
                                                title="Hapus fasilitas ini"
                                                :disabled="form.fiturs.length <= 1">
                                            <i class="fa-solid fa-trash text-[11px]"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    <!-- Footer -->
                    <div class="admin-modal-foot">
                        <button type="button" @click="close()" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<div x-data="modalHapusArmada()" x-init="window.modalHapus = $data">
    <template x-if="show">
        <div class="admin-modal-overlay" x-transition.opacity>
            <div @click="close()" class="absolute inset-0"></div>
            <div class="admin-modal w-full max-w-md" @click.stop>
                <div class="admin-modal-head">
                    <div class="flex items-center gap-3">
                        <div class="admin-modal-ico" style="background:#7F1D1D;color:#FECACA"><i class="fa-solid fa-trash"></i></div>
                        <div>
                            <h3>Hapus armada?</h3>
                            <p>Foto di server juga ikut dihapus</p>
                        </div>
                    </div>
                    <button type="button" @click="close()" class="admin-modal-close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="admin-modal-body text-sm text-slate-600">
                    Yakin hapus <b class="text-navy-900" x-text="nama"></b> secara permanen?
                </div>
                <div class="admin-modal-foot">
                    <button type="button" @click="close()" class="btn-secondary">Batal</button>
                    <a :href="hapusUrl" class="btn-danger">Ya, hapus</a>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
const BASE_URL_ARMADA = <?= json_encode(BASE_URL, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
const DEFAULT_ARMADA_IMG = 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=700&q=80';
function resolveGambar(img){
    if(!img || !img.trim()) return DEFAULT_ARMADA_IMG;
    img = img.trim();
    if(img.indexOf('http') === 0) return img;
    if(img.indexOf('uploads/') === 0 || img.indexOf('/uploads/') !== -1){
        return BASE_URL_ARMADA + '/' + img.replace(/^\//,'');
    }
    return 'https://images.unsplash.com/' + img + '?w=700&q=80';
}
// ==== Alpine.js Data: Modal Tambah Armada ====
function modalTambahArmada(){
    const oldInput = <?= $oldJson ?>;
    const oldFiturs = <?= $oldFiturJson ?>;
    const useOld = Object.keys(oldInput).length > 0 && oldInput.action === 'tambah';
    return {
        show: useOld,
        preview_gambar: '',
        form: {
            nama_armada: useOld ? (oldInput.nama_armada ?? '') : '',
            icon_fa:     useOld ? (oldInput.icon_fa ?? 'fa-car-side') : 'fa-car-side',
            kapasitas:   useOld ? (oldInput.kapasitas ?? 'Kapasitas 5-7 Kursi') : 'Kapasitas 5-7 Kursi',
            deskripsi:   useOld ? (oldInput.deskripsi ?? '') : '',
            warna_grad:  useOld ? (oldInput.warna_grad ?? 'from-primary-600 to-blue-800') : 'from-primary-600 to-blue-800',
            gambar_mode: useOld ? ((oldInput.gambar_url && oldInput.gambar_url.trim()) ? 'url' : 'upload') : 'upload',
            gambar_url:  useOld ? (oldInput.gambar_url ?? '') : '',
            gambar_lama: '',
            urutan:      useOld ? (parseInt(oldInput.urutan) || 99) : 99,
            is_aktif:    useOld ? (oldInput.is_aktif === undefined ? true : !!oldInput.is_aktif) : true,
            fiturs:      useOld ? (Array.isArray(oldFiturs) && oldFiturs.length > 0 ? oldFiturs : ['AC Dingin']) : ['AC Dingin','Bagasi Luas'],
        },
        tambahFitur(){ this.form.fiturs.push(''); },
        hapusFitur(i){ if(this.form.fiturs.length > 1) this.form.fiturs.splice(i,1); },
        previewUpload(e){
            const file = e.target.files && e.target.files[0];
            if(!file) return;
            const reader = new FileReader();
            const self = this;
            reader.onload = function(ev){ self.preview_gambar = ev.target.result; };
            reader.readAsDataURL(file);
        },
        open(){
            this.form = {
                nama_armada:'',
                icon_fa:'fa-car-side',
                kapasitas:'Kapasitas 5-7 Kursi',
                deskripsi:'',
                warna_grad:'from-primary-600 to-blue-800',
                gambar_mode:'upload',
                gambar_url:'',
                gambar_lama:'',
                urutan:99,
                is_aktif:true,
                fiturs:['AC Dingin','Bagasi Luas'],
            };
            this.preview_gambar = '';
            this.show = true;
        },
        close(){ this.show = false; this.preview_gambar = ''; }
    }
}

// ==== Alpine.js Data: Modal Edit Armada ====
function modalEditArmada(){
    const oldInput = <?= $oldJson ?>;
    const oldFiturs = <?= $oldFiturJson ?>;
    const useOld = Object.keys(oldInput).length > 0 && oldInput.action === 'edit' && (intval = parseInt(oldInput.id||0)) > 0;
    return {
        show: useOld,
        preview_gambar: '',
        form: {
            id: useOld ? (parseInt(oldInput.id) || 0) : 0,
            nama_armada: useOld ? (oldInput.nama_armada ?? '') : '',
            icon_fa:     useOld ? (oldInput.icon_fa ?? 'fa-car-side') : 'fa-car-side',
            kapasitas:   useOld ? (oldInput.kapasitas ?? '') : '',
            deskripsi:   useOld ? (oldInput.deskripsi ?? '') : '',
            warna_grad:  useOld ? (oldInput.warna_grad ?? '') : '',
            gambar_mode: useOld ? ((oldInput.gambar_url && oldInput.gambar_url.trim()) ? 'url' : 'upload') : 'upload',
            gambar_url:  useOld ? (oldInput.gambar_url ?? '') : '',
            gambar_lama: useOld ? (oldInput.gambar_url ?? '') : '',
            urutan:      useOld ? (parseInt(oldInput.urutan) || 99) : 99,
            is_aktif:    useOld ? (oldInput.is_aktif === undefined ? true : !!oldInput.is_aktif) : true,
            fiturs:      useOld ? (Array.isArray(oldFiturs) && oldFiturs.length > 0 ? oldFiturs : ['']) : [''],
        },
        tambahFitur(){ this.form.fiturs.push(''); },
        hapusFitur(i){ if(this.form.fiturs.length > 1) this.form.fiturs.splice(i,1); },
        previewUpload(e){
            const file = e.target.files && e.target.files[0];
            if(!file) return;
            const reader = new FileReader();
            const self = this;
            reader.onload = function(ev){ self.preview_gambar = ev.target.result; };
            reader.readAsDataURL(file);
        },
        open(a){
            let arrFiturs = ['AC Dingin'];
            try {
                let raw = a.fitur_list || '';
                if (raw && typeof raw === 'string' && raw.trim() !== '') {
                    let parsed = JSON.parse(raw);
                    if (Array.isArray(parsed) && parsed.length > 0) arrFiturs = parsed;
                } else if (Array.isArray(a.fiturs) && a.fiturs.length > 0) {
                    arrFiturs = a.fiturs;
                }
            } catch(e) {}
            const gul = a.gambar_url || '';
            this.form = {
                id: parseInt(a.id) || 0,
                nama_armada: a.nama_armada || '',
                icon_fa: a.icon_fa || 'fa-car-side',
                kapasitas: a.kapasitas || '',
                deskripsi: a.deskripsi || '',
                warna_grad: a.warna_grad || 'from-primary-600 to-blue-800',
                gambar_mode: (gul && gul.trim()) ? ((gul.indexOf('http')===0 || gul.indexOf('photo-')===0) ? 'url' : 'upload') : 'upload',
                gambar_url: (gul && (gul.indexOf('http')===0 || gul.indexOf('photo-')===0)) ? gul : '',
                gambar_lama: gul,
                urutan: parseInt(a.urutan) || 99,
                is_aktif: (parseInt(a.is_aktif) === 1 || a.is_aktif === true || a.is_aktif === '1'),
                fiturs: arrFiturs,
            };
            this.preview_gambar = '';
            this.show = true;
        },
        close(){ this.show = false; this.preview_gambar = ''; }
    }
}

function modalHapusArmada(){
    return {
        show: false, id: 0, nama: '', hapusUrl: '',
        open(id, nama){
            this.id = parseInt(id) || 0;
            this.nama = nama || '';
            this.hapusUrl = <?= json_encode(BASE_URL . '/admin/proses_crud_armada.php?action=hapus&csrf=' . csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> + '&id=' + this.id;
            this.show = true;
        },
        close(){ this.show = false; }
    };
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
