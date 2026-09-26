<?php
// =============================================
// EDIT PROFIL ADMIN — username & password
// =============================================
require_once __DIR__ . '/includes/auth_check.php';
$active_menu = 'profile';
$page_title  = 'Edit Profil';
require_once __DIR__ . '/includes/admin_header.php';

$flash = get_flash();
$usernameValue = old('username', $admin_data['username'] ?? $admin_user);
?>

<div class="no-print">
    <?php if ($flash) echo '<div class="mb-5">' . $flash . '</div>'; ?>

    <section class="dash-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div class="min-w-0">
                <div class="dash-hero-kicker">Akun · Keamanan</div>
                <h1 class="dash-hero-title">Edit <em>Profil</em></h1>
                <p class="dash-hero-desc">Ubah username atau password login. Password saat ini wajib diisi untuk menyimpan.</p>
            </div>
        </div>
    </section>

    <div class="max-w-xl mx-auto">
        <div class="card overflow-hidden">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-navy-900 to-navy-800 text-white flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-gold-300 to-gold-600 flex items-center justify-center text-navy-900 text-lg font-extrabold shadow-glow-gold shrink-0">
                    <?= strtoupper(mb_substr($admin_name, 0, 1)) ?>
                </div>
                <div class="min-w-0 leading-tight">
                    <div class="font-extrabold truncate"><?= e($admin_name) ?></div>
                    <div class="text-[11px] text-gold-200/90 font-semibold mt-0.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-at text-[10px]"></i>
                        <?= e($admin_data['username'] ?? $admin_user) ?>
                    </div>
                </div>
            </div>

            <form action="<?= BASE_URL ?>/admin/proses_profile.php" method="POST"
                  class="p-5 sm:p-6 space-y-5"
                  x-data="{ showCur:false, showNew:false, showConf:false }">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div>
                    <label class="form-label" for="username">Username</label>
                    <div class="flex items-center gap-2.5 px-3.5 rounded-xl bg-cream-50 border border-slate-200 focus-within:border-gold-400 focus-within:ring-4 focus-within:ring-gold-200/50 transition">
                        <i class="fa-solid fa-user text-gold-500 text-sm w-4 shrink-0"></i>
                        <input type="text" id="username" name="username" required maxlength="50" autocomplete="username"
                               value="<?= e($usernameValue) ?>"
                               pattern="[a-zA-Z0-9._\-]{3,50}"
                               title="3–50 karakter: huruf, angka, titik, underscore, atau strip"
                               placeholder="Username login"
                               class="flex-1 bg-transparent outline-none text-sm text-slate-900 placeholder:text-slate-400 py-3 min-h-[44px]">
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1.5">Huruf, angka, titik, underscore, atau strip · 3–50 karakter.</p>
                </div>

                <div class="rounded-2xl border border-gold-200/80 bg-gold-50/40 p-4 sm:p-5 space-y-4">
                    <div class="flex items-start gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-gold-100 text-gold-700 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-key text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-extrabold text-navy-900">Password baru <span class="font-medium text-slate-400">(opsional)</span></div>
                            <p class="text-[11px] text-slate-500 mt-0.5">Kosongkan jika hanya ingin mengganti username.</p>
                        </div>
                    </div>

                    <div>
                        <label class="form-label" for="password_baru">Password baru</label>
                        <div class="flex items-center gap-2.5 px-3.5 rounded-xl bg-white border border-slate-200 focus-within:border-gold-400 focus-within:ring-4 focus-within:ring-gold-200/50 transition">
                            <i class="fa-solid fa-lock text-gold-500 text-sm w-4 shrink-0"></i>
                            <input :type="showNew ? 'text':'password'" id="password_baru" name="password_baru"
                                   autocomplete="new-password" minlength="6"
                                   placeholder="Minimal 6 karakter"
                                   class="flex-1 bg-transparent outline-none text-sm text-slate-900 placeholder:text-slate-400 py-3 min-h-[44px]">
                            <button type="button" @click="showNew = !showNew" class="text-slate-400 hover:text-navy-700 text-sm w-10 h-10 shrink-0" aria-label="Tampilkan password baru">
                                <i class="fa-solid" :class="showNew ? 'fa-eye-slash':'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="form-label" for="password_confirm">Konfirmasi password baru</label>
                        <div class="flex items-center gap-2.5 px-3.5 rounded-xl bg-white border border-slate-200 focus-within:border-gold-400 focus-within:ring-4 focus-within:ring-gold-200/50 transition">
                            <i class="fa-solid fa-lock text-gold-500 text-sm w-4 shrink-0"></i>
                            <input :type="showConf ? 'text':'password'" id="password_confirm" name="password_confirm"
                                   autocomplete="new-password" minlength="6"
                                   placeholder="Ulangi password baru"
                                   class="flex-1 bg-transparent outline-none text-sm text-slate-900 placeholder:text-slate-400 py-3 min-h-[44px]">
                            <button type="button" @click="showConf = !showConf" class="text-slate-400 hover:text-navy-700 text-sm w-10 h-10 shrink-0" aria-label="Tampilkan konfirmasi">
                                <i class="fa-solid" :class="showConf ? 'fa-eye-slash':'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="password_saat_ini">
                        Password saat ini <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-2.5 px-3.5 rounded-xl bg-cream-50 border border-slate-200 focus-within:border-navy-500 focus-within:ring-4 focus-within:ring-navy-200/40 transition">
                        <i class="fa-solid fa-shield-halved text-navy-600 text-sm w-4 shrink-0"></i>
                        <input :type="showCur ? 'text':'password'" id="password_saat_ini" name="password_saat_ini" required
                               autocomplete="current-password"
                               placeholder="Wajib diisi untuk menyimpan"
                               class="flex-1 bg-transparent outline-none text-sm text-slate-900 placeholder:text-slate-400 py-3 min-h-[44px]">
                        <button type="button" @click="showCur = !showCur" class="text-slate-400 hover:text-navy-700 text-sm w-10 h-10 shrink-0" aria-label="Tampilkan password saat ini">
                            <i class="fa-solid" :class="showCur ? 'fa-eye-slash':'fa-eye'"></i>
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1.5">Diperlukan untuk keamanan sebelum menyimpan perubahan.</p>
                </div>

                <div class="flex flex-col-reverse sm:flex-row gap-2.5 sm:justify-end pt-1">
                    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn-secondary justify-center min-h-[44px]">
                        <i class="fa-solid fa-arrow-left"></i> Batal
                    </a>
                    <button type="submit" class="btn-success justify-center min-h-[44px]">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
clear_old();
require_once __DIR__ . '/includes/admin_footer.php';
