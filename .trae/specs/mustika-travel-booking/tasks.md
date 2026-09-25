# Mustika Travel - Implementation Plan

Urutan task dari infrastructure → backend → frontend → admin → laporan.

---

## Task 1: Setup Infrastructure - Struktur Folder, Config DB, Skema SQL
- **Status**: `pending`
- **Priority**: high
- **Depends On**: None
- **Description**:
  - Buat struktur folder utama: `config/`, `includes/`, `admin/`, `assets/css`, `assets/js`
  - Buat `config/database.php`: koneksi MySQL dengan PDO + prepared statements (aman SQL Injection)
  - Buat `config/config.php`: variabel global (nama website, harga tiket, WA admin, dll)
  - Buat `includes/helpers.php`: function helper (base_url, rupiah format, redirect, session flash, sanitize)
  - Buat file `database.sql` berisi:
    1. Tabel `admins`: id, username, password_hash, created_at
    2. Tabel `bookings`: id, nama, no_hp, alamat_jemput, alamat_tujuan, jumlah_kursi, tanggal_berangkat, jam_jemput, barang_bawaan, total_harga, status (enum: pending/confirmed/completed/cancelled), catatan_admin, source (enum: online/manual), created_at, updated_at
    3. Insert default admin: username `admin`, password `admin123` (hash dengan password_hash)
  - Buat `includes/header.php` & `includes/footer.php` untuk template frontend
  - Buat `admin/includes/admin_header.php` & `admin/includes/admin_footer.php` untuk template admin
- **Acceptance Criteria Addressed**: AC-11, AC-13
- **Test Requirements**:
  - `rule` TR-1.1: Buka `config/database.php` lewat browser → tidak ada error, koneksi DB berhasil (tes dengan script kecil `test_db.php`)
  - `rule` TR-1.2: Jalankan `database.sql` di phpMyAdmin → kedua tabel terbuat, ada 1 record admin
  - `rubric` TR-1.3: Struktur folder rapi; scale 1-5; anchors 1=berantakan 3=lumayan 5=sangat jelas; threshold >=4; evidence: `tree` command output
- **Notes**: Gunakan PDO, jangan mysql_* atau mysqli_* procedural.

---

## Task 2: Backend - Proses Submit Booking Frontend (Online Booking)
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 1
- **Description**:
  - Buat `process_booking.php`: handle POST dari form booking
  - Sanitasi semua input (htmlspecialchars, trim)
  - Validasi server-side: semua field required, jumlah_kursi >=1, tanggal valid, no_hp numeric
  - Hitung otomatis `total_harga` = `jumlah_kursi * HARGA_TIKET` (dari config)
  - Insert ke DB (source=online, status=pending)
  - Pakai CSRF token (session) untuk security
  - Jika berhasil: set session flash "success" → redirect ke index.php#booking dengan pesan sukses
  - Jika gagal validasi: set session flash "error" + old input → redirect kembali
- **Acceptance Criteria Addressed**: AC-2, AC-3
- **Test Requirements**:
  - `rule` TR-2.1: Submit form booking dengan data lengkap via POST → record baru di bookings dengan source=online, status=pending, total_harga benar
  - `rule` TR-2.2: Submit form dengan field nama kosong → tidak ada insert, pesan error muncul
  - `rule` TR-2.3: Submit dengan jumlah_kursi=3 → total_harga = 600000

---

## Task 3: Frontend - Landing Page (index.php) Full Section + Responsif
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 2
- **Description**:
  - Buat `index.php` dengan struktur section:
    1. **Navbar**: logo Mustika Travel (logo.jpeg) + menu (Beranda, Jurusan, Pemesanan, Layanan, Kontak) + sticky navbar + mobile hamburger menu
    2. **Hero Section**: Background gradient biru muda, text besar "Mustika Travel" + slogan "Perjalanan Aman, Nyaman, Sampai Tujuan" + badge fitur (Aman Terpercaya, Armada Nyaman, Tepat Waktu) + ilustrasi/gambar armada (pakai CSS art / SVG sederhana atau gambar dari unsplash via URL)
    3. **Search / Booking Banner**: Box putih rounded shadow dengan icon + text "Pemesanan Tiket"
    4. **Form Booking Online**: Semua 8 field + text "Tiket: Rp 200.000 / Org" + "Pembatalan tiket 50%" + tombol submit "Booking Sekarang" dengan style biru tegas + hover
    5. **Section Jurusan Kami**: Title + daftar rute (Blora-Surabaya, Blora-Banyuwangi, Blora-Malang, Blora-Denpasar, Blora-Jember, Blora-Jepara) dengan icon panah
    6. **Section Pelayanan Door to Door**: Title + 4 icon fitur: Jemput di Lokasi, Antar Sampai Tujuan, Aman & Terpercaya, Fleksibel
    7. **Section Kontak**: Kartu info WA, alamat kantor
    8. **Footer**: Copyright + link "Admin Login" ke `admin/login.php`
  - Pakai **Tailwind CSS CDN** untuk styling
  - Pakai **Font Awesome CDN** untuk icon
  - Pakai **Alpine.js CDN** untuk: mobile menu dropdown, flash message auto-hide, dll
  - Tampilkan session flash message (success/error) di atas form jika ada
  - Tambahkan validasi client-side dengan HTML5 required + custom JS message
  - Buat breakpoint responsif untuk sm (<640px), md (<768px), lg (<1024px)
  - Di HP: form full width, navbar jadi hamburger menu, section jurusan jadi 2 kolom, text kecil tapi masih terbaca
- **Acceptance Criteria Addressed**: AC-1, AC-3, AC-9, AC-10, AC-11, AC-12
- **Test Requirements**:
  - `rule` TR-3.1: Buka index.php → semua 8 section terlihat, logo muncul
  - `rule` TR-3.2: Responsif 375px (Chrome DevTools device toolbar) → tidak ada horizontal scrollbar, semua tombol >=44px tinggi, text terbaca jelas
  - `rule` TR-3.3: Responsif 1366px → layout keren, hero tampil 2 sisi kiri text kanan gambar
  - `rubric` TR-3.4: Kualitas desain visual; scale 1-5; threshold >=4; evidence: screenshot penuh 2 ukuran layar

---

## Task 4: Backend - Admin Auth (Login + Logout)
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 1
- **Description**:
  - Buat `admin/login.php`: form login (username + password) + desain cantik (card centered, logo)
  - Buat `admin/proses_login.php`: cek username, verify password_hash, set $_SESSION['admin_id'] + $_SESSION['admin_username']
  - Buat `admin/logout.php`: destroy session → redirect ke login
  - Buat `admin/includes/auth_check.php`: cek session, jika belum login redirect ke login.php (di-include di semua halaman admin kecuali login)
  - CSRF token di form login
- **Acceptance Criteria Addressed**: AC-4
- **Test Requirements**:
  - `rule` TR-4.1: Login dengan admin/admin123 → masuk ke dashboard, session admin_id ada
  - `rule` TR-4.2: Login dengan password salah → stay di login, pesan error
  - `rule` TR-4.3: Akses `admin/dashboard.php` tanpa login → redirect ke login.php
  - `rule` TR-4.4: Klik logout → session hilang, kembali ke login

---

## Task 5: Backend + Frontend - Admin Dashboard
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 4
- **Description**:
  - Buat `admin/dashboard.php`:
    - Sidebar kiri (sticky): logo kecil + menu: Dashboard, Data Booking, Laporan, Logout
    - Header kanan: selamat datang admin, tanggal hari ini
    - 4 Stat Card: (1) Booking Hari Ini, (2) Booking Pending, (3) Booking Terkonfirmasi, (4) Pendapatan Hari Ini (hanya status completed)
    - Tabel "Booking Terbaru": 10 booking terakhir dengan kolom: Tanggal, Nama, Tujuan, Kursi, Total, Status, Aksi
    - Warna status: Pending=kuning, Confirmed=biru, Completed=hijau, Cancelled=merah
  - Query semua statistic dengan SQL yang benar (filter tanggal today = DATE(created_at) = CURDATE())
  - Desain card dengan icon, shadow, warna beda tiap stat
- **Acceptance Criteria Addressed**: AC-13, AC-11
- **Test Requirements**:
  - `rule` TR-5.1: Jika ada 2 booking completed hari ini, total pendapatan di dashboard card = SUM total_harga 2 record itu
  - `rule` TR-5.2: Jika ada 1 pending, card "Booking Pending" = 1
  - `rubric` TR-5.3: Desain dashboard clean, modern; threshold >=4; evidence: screenshot dashboard

---

## Task 6: Backend + Frontend - Admin CRUD Data Booking (List + Tambah Manual Modal + Edit + Hapus)
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 5
- **Description**:
  - Buat `admin/bookings.php`:
    - Halaman list semua booking
    - Filter: dropdown status (Semua, Pending, Confirmed, Completed, Cancelled), filter tanggal dari - sampai, button cari
    - Tabel booking lengkap dengan semua kolom
    - Tombol aksi: Edit (modal), Hapus (confirm dialog), Lihat Detail
    - Tombol hijau "➕ Tambah Booking Manual" (membuka modal)
  - Modal Tambah Booking Manual (Alpine.js + Tailwind):
    - Semua field form booking + tambahan field "Catatan Admin" + hidden source=manual
    - Submit modal via form POST ke `admin/proses_tambah_booking.php`
    - total_harga otomatis hitung di client-side JS + di server-side ulang
  - Modal Edit Booking:
    - Load data booking via parameter id, prefill semua field, bisa ubah status & catatan admin
    - Submit ke `admin/proses_edit_booking.php`
  - Hapus Booking: POST ke `admin/proses_hapus_booking.php` dengan konfirmasi JS
  - Semua proses CRUD pakai PDO prepared statement, redirect kembali ke bookings.php dengan flash message
- **Acceptance Criteria Addressed**: AC-5, AC-6
- **Test Requirements**:
  - `rule` TR-6.1: Klik Tambah Manual → modal muncul, isi data, submit → record baru dengan source=manual tersimpan
  - `rule` TR-6.2: Edit booking status Pending → Confirmed → status di DB berubah menjadi confirmed
  - `rule` TR-6.3: Hapus booking id=X → record hilang dari list dan DB
  - `rule` TR-6.4: Filter status "Pending" → hanya tampil booking pending
  - `rubric` TR-6.5: Modal Alpine.js smooth, overlay, close di luar click; threshold >=4; evidence: video / screenshot modal terbuka

---

## Task 7: Backend + Frontend - Laporan Per Hari, Per Minggu, Per Bulan
- **Status**: `pending`
- **Priority**: high
- **Depends On**: Task 6
- **Description**:
  - Buat `admin/laporan.php` dengan tabs Alpine.js (3 tab: Harian / Mingguan / Bulanan)
  - **Tab Laporan Harian**:
    - Input: tanggal (default: hari ini), submit "Tampilkan"
    - Tabel: semua booking status=completed pada tanggal tsb (no, nama, rute, kursi, total harga, jam, status)
    - Info card: Total Booking, Total Pendapatan
    - Button Cetak (window.print()) dengan CSS print-friendly: sembunyikan sidebar, header, tombol; kertas A4, margin 1cm
  - **Tab Laporan Mingguan**:
    - Input: pilih tanggal (minggu itu), button tampilkan
    - Hitung tanggal mulai Minggu = Senin ~ Minggu dari minggu tsb
    - Tabel: Per Hari (Senin, Selasa, ..., Minggu) → Jumlah Booking, Total Pendapatan
    - + Detail booking (expandable / tabel dibawah)
    - Total Akhir Minggu
  - **Tab Laporan Bulanan**:
    - Input: pilih Bulan & Tahun
    - Tabel: Per Tanggal (1~31) → Jumlah Booking, Total Pendapatan (hanya tanggal yang ada booking tampil)
    - Info card: Total Bulan, Rata-rata per hari, Hari teramai
    - Total Akhir Bulan
  - Semua perhitungan pendapatan hanya booking dengan status = **completed**
  - Di setiap tabel, tambahkan icon print & download (PDF nanti user bisa save lewat print to PDF)
- **Acceptance Criteria Addressed**: AC-7, AC-8, AC-11
- **Test Requirements**:
  - `rule` TR-7.1: Insert 3 booking completed (200rb, 400rb, 600rb) pada tanggal yang sama → laporan harian total = 1.200.000
  - `rule` TR-7.2: Insert 1 booking cancelled pada tanggal yang sama → TIDAK masuk hitungan total
  - `rule` TR-7.3: Laporan Bulan September, ada 2 tanggal dengan booking → muncul 2 baris tanggal dengan total per tanggal + total bulan
  - `rule` TR-7.4: Klik tombol Cetak → print dialog muncul, preview layout rapi tanpa sidebar
  - `rubric` TR-7.5: Layout laporan clean, angka jelas, total tebal; threshold >=4; evidence: screenshot laporan

---

## Task 8: Polishing - Final Touch, UI Consistency, Print CSS, WA Number Correct
- **Status**: `pending`
- **Priority**: medium
- **Depends On**: Task 3, Task 7
- **Description**:
  - Cek semua link: navbar scroll ke section, footer admin link work
  - Ganti nomor WA placeholder di index.php & config sesuai jawaban user
  - Tambahkan CSS print: `@media print` untuk laporan, sembunyikan semua UI elemen, table full width, font serif untuk print
  - Animasi halus: scroll smooth, fade in section, button hover state
  - Format rupiah di semua tampilan harga (frontend & admin) pake helper rupiah()
  - Perbaiki margin/padding terakhir biar pixel perfect di HP & desktop
  - Cek semua flash message muncul dengan warna benar (hijau success, merah error)
  - Tambahkan favicon pakai logo
- **Acceptance Criteria Addressed**: AC-9, AC-10, AC-11, AC-12
- **Test Requirements**:
  - `rule` TR-8.1: Semua harga di website tampil dalam format "Rp 200.000" bukan "200000"
  - `rule` TR-8.2: Navbar menu "Jurusan" di klik → smooth scroll ke section Jurusan Kami
  - `rule` TR-8.3: Print laporan harian (Print to PDF) → layout rapi, tidak terpotong, footer & header print tidak ada UI admin
  - `rubric` TR-8.4: Keseluruhan UI konsisten warna biru, spacing, button style; threshold >=4; evidence: visual check seluruh page
