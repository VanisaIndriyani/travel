<?php
require_once __DIR__ . '/includes/auth_check.php';
$active_menu = 'rutes';
$page_title  = 'Kelola Rute & Harga';
require_once __DIR__ . '/includes/admin_header.php';

$flash = get_flash();

$rutes = get_rutes(false);
$totalAktif = 0;
foreach ($rutes as $r) { if((int)$r['is_aktif']===1) $totalAktif++; }
$totalNonaktif = count($rutes) - $totalAktif;

// Load old input (jika error saat tambah/edit)
$old = [];
if (isset($_SESSION['old_input'])) {
    $old = $_SESSION['old_input'];
}
$oldJson = json_encode($old, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);
?>

<div class="no-print">
    <?php if ($flash) echo '<div class="mb-5">' . $flash . '</div>'; ?>

    <section class="dash-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div class="min-w-0">
                <div class="dash-hero-kicker">Master data · Rute</div>
                <h1 class="dash-hero-title">Kelola <em>Rute &amp; Harga</em></h1>
                <p class="dash-hero-desc"><?= count($rutes) ?> rute · <?= (int)$totalAktif ?> aktif · <?= (int)$totalNonaktif ?> tersembunyi dari customer.</p>
            </div>
            <div class="dash-hero-actions">
                <button type="button" onclick="window.modalTambah.open()" class="is-gold"><i class="fa-solid fa-plus"></i> Tambah rute</button>
            </div>
        </div>
    </section>

    <div class="card p-0 overflow-hidden">
        <div class="bk-mobile">
            <?php if (empty($rutes)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fa-solid fa-route"></i></div>
                    <div class="font-semibold text-slate-600">Belum ada rute.</div>
                    <div class="text-sm mt-1"><button type="button" onclick="window.modalTambah.open()" class="text-navy-800 underline font-bold">Tambah rute baru</button></div>
                </div>
            <?php else: ?>
                <?php foreach ($rutes as $r):
                    $r_json_m = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');
                    $aktif = (int)$r['is_aktif'] === 1;
                ?>
                    <article class="bk-card">
                        <div class="flex items-start justify-between gap-1 mb-1.5">
                            <div class="min-w-0">
                                <div class="font-extrabold text-navy-900 text-[12px] leading-tight truncate"><?= e($r['nama_rute']) ?></div>
                                <div class="text-[10px] text-slate-500 mt-0.5">#<?= (int)$r['id'] ?></div>
                            </div>
                            <?php if ($aktif): ?>
                                <span class="badge bg-emerald-50 text-emerald-700 border-emerald-200 !text-[9px] !px-1.5 !py-0.5 shrink-0">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-slate-100 text-slate-500 border-slate-200 !text-[9px] !px-1.5 !py-0.5 shrink-0">Off</span>
                            <?php endif; ?>
                        </div>
                        <div class="font-extrabold text-navy-900 text-[12px]"><?= rupiah($r['harga']) ?></div>
                        <div class="flex flex-wrap gap-1 mt-2">
                            <button type="button" onclick="window.modalDetail.open(<?= $r_json_m ?>)" class="btn-icon bg-cream-100 text-navy-800 border-gold-200" title="Detail"><i class="fa-regular fa-eye text-[11px]"></i></button>
                            <button type="button" onclick="window.modalEdit.open(<?= $r_json_m ?>)" class="btn-icon bg-navy-50 text-navy-800 border-navy-200" title="Edit"><i class="fa-solid fa-pen text-[11px]"></i></button>
                            <form method="POST" action="<?= BASE_URL ?>/admin/proses_crud_rute.php" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="toggle-aktif">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="btn-icon <?= $aktif ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?>" title="<?= $aktif ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                    <i class="fa-solid <?= $aktif ? 'fa-eye-slash' : 'fa-eye' ?> text-[11px]"></i>
                                </button>
                            </form>
                            <button type="button" data-hapus-id="<?= (int)$r['id'] ?>" data-hapus-nama="<?= e($r['nama_rute']) ?>" class="js-hapus btn-icon bg-red-50 text-red-600 border-red-200" title="Hapus"><i class="fa-solid fa-trash text-[11px]"></i></button>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="bk-desktop table-wrap is-sticky-col !rounded-none !border-0">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama rute</th>
                        <th>Harga / orang</th>
                        <th class="text-center">Urutan</th>
                        <th class="text-center">Status</th>
                        <th class="no-print text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rutes)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="fa-solid fa-route"></i></div>
                                    <div class="font-semibold text-slate-600">Belum ada rute yang dibuat.</div>
                                    <div class="text-sm mt-1"><button type="button" onclick="window.modalTambah.open()" class="text-navy-800 underline font-bold">Tambah rute baru</button></div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                    <?php $nomor = 1; foreach ($rutes as $r):
                        $r_json = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');
                        $aktif = (int)$r['is_aktif'] === 1;
                    ?>
                        <tr>
                            <td class="font-extrabold text-slate-500 text-center text-xs"><?= $nomor++ ?></td>
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-cream-100 border border-gold-200 text-gold-600 flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-location-arrow -rotate-45 text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-navy-900 text-sm"><?= e($r['nama_rute']) ?></div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">ID #<?= (int)($r['id'] ?? 0) ?> · update <?= substr($r['updated_at'] ?? $r['created_at'] ?? '-', 0, 10) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gold-50 border border-gold-200">
                                    <span class="text-[9px] font-black text-gold-700 bg-gold-100 px-1.5 py-0.5 rounded">TIKET</span>
                                    <span class="font-extrabold text-navy-900"><?= rupiah($r['harga']) ?></span>
                                </div>
                            </td>
                            <td class="text-center"><span class="font-bold text-navy-800 bg-cream-100 px-2.5 py-0.5 rounded text-sm"><?= (int)$r['urutan'] ?></span></td>
                            <td class="text-center">
                                <?php if ($aktif): ?>
                                    <span class="badge bg-emerald-50 text-emerald-700 border-emerald-200"><i class="fa-solid fa-circle-check mr-1 text-[9px]"></i>Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-slate-100 text-slate-500 border-slate-200"><i class="fa-solid fa-eye-slash mr-1 text-[9px]"></i>Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="no-print">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" onclick="window.modalDetail.open(<?= $r_json ?>)" class="btn-icon bg-cream-100 text-navy-800 border-gold-200" title="Detail"><i class="fa-regular fa-eye text-[12px]"></i></button>
                                    <button type="button" onclick="window.modalEdit.open(<?= $r_json ?>)" class="btn-icon bg-navy-50 text-navy-800 border-navy-200" title="Edit"><i class="fa-solid fa-pen"></i></button>
                                    <form method="POST" action="<?= BASE_URL ?>/admin/proses_crud_rute.php" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="toggle-aktif">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit" class="btn-icon <?= $aktif ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?>" title="<?= $aktif ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                            <i class="fa-solid <?= $aktif ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                        </button>
                                    </form>
                                    <button type="button" data-hapus-id="<?= (int)$r['id'] ?>" data-hapus-nama="<?= e($r['nama_rute']) ?>" class="js-hapus btn-icon bg-red-50 text-red-600 border-red-200" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 dash-panel">
        <div class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-gold-600 mb-2">Catatan</div>
        <ul class="text-sm text-slate-600 space-y-1.5 font-medium">
            <li>Toggle mata untuk menyembunyikan rute dari customer tanpa menghapus data.</li>
            <li>Urutan kecil tampil lebih atas di dropdown customer.</li>
            <li>Ubah harga tidak mengubah total booking lama.</li>
        </ul>
    </div>
</div>

<div x-data="modalTambahRute()" x-init="window.modalTambah = $data">
    <template x-if="show">
        <div class="admin-modal-overlay" x-transition.opacity>
            <div @click="close()" class="absolute inset-0"></div>
            <div class="admin-modal w-full max-w-xl" @click.stop>
                <div class="admin-modal-head">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="admin-modal-ico"><i class="fa-solid fa-plus"></i></div>
                        <div class="min-w-0">
                            <h3>Tambah rute baru</h3>
                            <p>Langsung tampil di customer jika aktif</p>
                        </div>
                    </div>
                    <button type="button" @click="close()" class="admin-modal-close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form method="POST" action="<?= BASE_URL ?>/admin/proses_crud_rute.php" class="flex-1 min-h-0 flex flex-col">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="tambah">
                    <div class="admin-modal-body space-y-4">
                        <div>
                            <label class="form-label">Nama rute *</label>
                            <input name="nama_rute" required type="text" class="input-field" placeholder="Misal: Blora - Yogyakarta" x-model="form.nama_rute">
                            <div class="text-[11px] text-slate-400 mt-1">Format: Kota Asal - Kota Tujuan</div>
                        </div>
                        <div>
                            <label class="form-label">Harga tiket per orang (Rp) *</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-extrabold text-sm pointer-events-none">Rp</span>
                                <input name="harga" required type="number" min="1000" step="1" class="input-field !pl-12" placeholder="350000" x-model.number="form.harga">
                            </div>
                            <div class="text-[11px] text-slate-500 mt-1">Preview: <b class="text-navy-800" x-text="'Rp ' + Number(form.harga||0).toLocaleString('id-ID')">Rp 0</b></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Urutan tampil</label>
                                <input name="urutan" type="number" min="1" class="input-field" placeholder="99" x-model.number="form.urutan">
                                <div class="text-[11px] text-slate-400 mt-1">Makin kecil = makin atas</div>
                            </div>
                            <div>
                                <label class="form-label">Status</label>
                                <label class="inline-flex items-center gap-2.5 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 cursor-pointer w-full min-h-[44px]">
                                    <input type="checkbox" name="is_aktif" x-model="form.is_aktif" class="w-5 h-5 rounded text-emerald-600">
                                    <span class="font-bold text-emerald-700 text-sm">Aktif di customer</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="admin-modal-foot">
                        <button type="button" @click="close()" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Simpan rute</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<div x-data="modalEditRute()" x-init="window.modalEdit = $data">
    <template x-if="show">
        <div class="admin-modal-overlay" x-transition.opacity>
            <div @click="close()" class="absolute inset-0"></div>
            <div class="admin-modal w-full max-w-xl" @click.stop>
                <div class="admin-modal-head">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="admin-modal-ico"><i class="fa-solid fa-pen"></i></div>
                        <div class="min-w-0">
                            <h3>Edit rute <span x-text="'#' + form.id">#0</span></h3>
                            <p>Nama, harga, urutan, dan status</p>
                        </div>
                    </div>
                    <button type="button" @click="close()" class="admin-modal-close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form method="POST" action="<?= BASE_URL ?>/admin/proses_crud_rute.php" class="flex-1 min-h-0 flex flex-col">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" x-model.number="form.id">
                    <div class="admin-modal-body space-y-4">
                        <div>
                            <label class="form-label">Nama rute *</label>
                            <input name="nama_rute" required type="text" class="input-field" x-model="form.nama_rute">
                        </div>
                        <div>
                            <label class="form-label">Harga tiket per orang (Rp) *</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-extrabold text-sm pointer-events-none">Rp</span>
                                <input name="harga" required type="number" min="1000" step="1" class="input-field !pl-12" x-model.number="form.harga">
                            </div>
                            <div class="text-[11px] text-slate-500 mt-1">Preview: <b class="text-navy-800" x-text="'Rp ' + Number(form.harga||0).toLocaleString('id-ID')">Rp 0</b> · booking lama tidak berubah</div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Urutan tampil</label>
                                <input name="urutan" type="number" min="1" class="input-field" x-model.number="form.urutan">
                            </div>
                            <div>
                                <label class="form-label">Status</label>
                                <label class="inline-flex items-center gap-2.5 px-4 py-3 rounded-xl border cursor-pointer w-full min-h-[44px]"
                                       :class="form.is_aktif ? 'bg-emerald-50 border-emerald-200' : 'bg-slate-50 border-slate-200'">
                                    <input type="checkbox" name="is_aktif" x-model="form.is_aktif" class="w-5 h-5 rounded text-emerald-600">
                                    <span class="font-bold text-sm" :class="form.is_aktif ? 'text-emerald-700' : 'text-slate-500'" x-text="form.is_aktif ? 'Aktif di customer' : 'Nonaktif (tersembunyi)'">Aktif</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="admin-modal-foot">
                        <button type="button" @click="close()" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<div x-data="modalDetailRute()" x-init="window.modalDetail = $data">
    <template x-if="show">
        <div class="admin-modal-overlay" x-transition.opacity>
            <div @click="close()" class="absolute inset-0"></div>
            <div class="admin-modal w-full max-w-lg" @click.stop>
                <div class="admin-modal-head">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="admin-modal-ico"><i class="fa-solid fa-route"></i></div>
                        <div class="min-w-0">
                            <h3 x-text="r.nama_rute || 'Detail rute'"></h3>
                            <p>Ringkasan rute &amp; harga</p>
                        </div>
                    </div>
                    <button type="button" @click="close()" class="admin-modal-close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="admin-modal-body space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><span class="text-slate-500">ID</span><span class="font-bold text-navy-900" x-text="'#' + (r.id || 0)"></span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-500">Harga / orang</span><span class="font-extrabold text-navy-900" x-text="'Rp ' + Number(r.harga||0).toLocaleString('id-ID')"></span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-500">Urutan</span><span class="font-semibold" x-text="r.urutan"></span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-500">Status</span><span class="font-bold" x-text="Number(r.is_aktif) === 1 ? 'Aktif' : 'Nonaktif'"></span></div>
                </div>
                <div class="admin-modal-foot !justify-between">
                    <button type="button" @click="close()" class="btn-secondary">Tutup</button>
                    <button type="button" @click="editFromDetail()" class="btn-primary">Edit rute</button>
                </div>
            </div>
        </div>
    </template>
</div>

<div x-data="modalHapusRute()" x-init="window.modalHapus = $data">
    <template x-if="show">
        <div class="admin-modal-overlay" x-transition.opacity>
            <div @click="close()" class="absolute inset-0"></div>
            <div class="admin-modal w-full max-w-md" @click.stop>
                <div class="admin-modal-head">
                    <div class="flex items-center gap-3">
                        <div class="admin-modal-ico" style="background:#7F1D1D;color:#FECACA"><i class="fa-solid fa-trash"></i></div>
                        <div>
                            <h3>Hapus rute?</h3>
                            <p>Booking lama tetap ada, id_rute dikosongkan</p>
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
function modalDetailRute(){
    return {
        show: false,
        r: {},
        open(row){ this.r = row || {}; this.show = true; },
        close(){ this.show = false; },
        editFromDetail(){
            this.show = false;
            if (window.modalEdit) window.modalEdit.open(this.r);
        }
    };
}
function modalHapusRute(){
    return {
        show: false, id: 0, nama: '', hapusUrl: '',
        open(id, nama){
            this.id = parseInt(id) || 0;
            this.nama = nama || '';
            this.hapusUrl = <?= json_encode(BASE_URL . '/admin/proses_crud_rute.php?action=hapus&csrf=' . csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> + '&id=' + this.id;
            this.show = true;
        },
        close(){ this.show = false; }
    };
}
function modalTambahRute(){
    const oldInput = <?= $oldJson ?>;
    return {
        show: Object.keys(oldInput).length > 0 && oldInput.action === 'tambah',
        form: {
            nama_rute: oldInput.nama_rute ?? '',
            harga: parseInt(oldInput.harga) || 0,
            urutan: parseInt(oldInput.urutan) || 99,
            is_aktif: oldInput.is_aktif === undefined ? true : !!oldInput.is_aktif,
        },
        open(){
            this.form = {
                nama_rute: 'Blora - ',
                harga: 200000,
                urutan: 99,
                is_aktif: true,
            };
            this.show = true;
        },
        close(){ this.show = false; }
    }
}

// ==== Alpine.js Data: Modal Edit Rute ====
function modalEditRute(){
    const oldInput = <?= $oldJson ?>;
    return {
        show: Object.keys(oldInput).length > 0 && oldInput.action === 'edit' && oldInput.id > 0,
        form: {
            id: parseInt(oldInput.id) || 0,
            nama_rute: oldInput.nama_rute ?? '',
            harga: parseInt(oldInput.harga) || 0,
            urutan: parseInt(oldInput.urutan) || 99,
            is_aktif: oldInput.is_aktif === undefined ? true : !!oldInput.is_aktif,
        },
        open(r){
            this.form = {
                id: parseInt(r.id) || 0,
                nama_rute: r.nama_rute || '',
                harga: parseInt(r.harga) || 0,
                urutan: parseInt(r.urutan) || 99,
                is_aktif: (parseInt(r.is_aktif) === 1 || r.is_aktif === true || r.is_aktif === '1'),
            };
            this.show = true;
        },
        close(){ this.show = false; }
    }
}

</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
