# Mustika Travel - Website Booking Online

## Overview
- **Summary**: Website pemesanan tiket travel online untuk Mustika Travel dengan halaman depan (landing page) yang menarik, form booking, dan dashboard admin untuk mengelola data booking serta laporan.
- **Purpose**: Mempermudah calon penumpang booking tiket secara online, memudahkan admin mengelola data booking (termasuk input manual dari WA), dan menyediakan laporan keuangan per hari/minggu/bulan.
- **Target Users**:
  1. Calon penumpang (end user) yang ingin pesan tiket travel via website
  2. Admin Mustika Travel yang mengelola booking dan laporan

## Goals
- Halaman depan dengan desain modern, premium, tema biru (sesuai referensi gambar), menampilkan branding Mustika Travel
- Form booking online dengan semua field yang dibutuhkan (Nama, No.Hp, Alamat Jemput, Tujuan, Jumlah Kursi, Tanggal, Jam Jemput, Barang Bawaan)
- Data booking otomatis masuk ke dashboard admin
- Admin bisa login, lihat list booking, tambah booking manual (dari WA), edit/hapus booking, ubah status
- Fitur laporan pendapatan per hari, minggu, bulan dengan visualisasi yang bagus
- Tampilan responsif 100%: nyaman dipakai di laptop dan HP (mobile-friendly)
- Pakai logo Mustika Travel yang sudah ada (logo.jpeg)

## Non-Goals
- Tidak ada fitur payment gateway / pembayaran online (booking hanya reservasi, pembayaran via WA/transfer manual)
- Tidak ada fitur registrasi user end-user (hanya admin yang punya akun login)
- Tidak ada sistem otomatis kirim notifikasi WA (admin handle manual)

## Background & Context
- Project folder: `d:\APLIKASI\laragon\www\SEPTEMBER\Travel`
- File yang sudah tersedia: `logo.jpeg` (logo Mustika Travel)
- Environment: Laragon (PHP + MySQL + Apache), jadi stack teknologinya PHP Native + MySQL untuk kesederhanaan deploy & maintenance
- Preferensi user: desain modern, premium, tema biru dengan detail bagus; modal pake Alpine.js; sangat perhatikan responsif di HP

## Functional Requirements

### Frontend / Landing Page (End User)
- **FR-1**: Menampilkan Hero section dengan logo, slogan, dan gambar armada travel (bus, hiace, avanza)
- **FR-2**: Menampilkan section "Pemesanan Tiket" dengan form booking online (8 field)
- **FR-3**: Form booking berisi:
  - Nama lengkap
  - Nomor HP/WhatsApp
  - Alamat Penjemputan
  - Alamat Tujuan
  - Jumlah Seat/Kursi (angka, min 1)
  - Hari & Tanggal Keberangkatan
  - Estimasi Jam Jemput
  - Keterangan Barang Bawaan
- **FR-4**: Menampilkan info harga tiket: Rp 200.000 / orang & kebijakan pembatalan 50%
- **FR-5**: Setelah submit booking sukses, muncul pesan sukses + instruksi lanjut hubungi WA admin
- **FR-6**: Menampilkan section Jurusan Kami (rute travel populer)
- **FR-7**: Menampilkan section Pelayanan Door to Door
- **FR-8**: Menampilkan section Kontak (nomor WA admin, alamat kantor - placeholder, bisa diatur nanti)
- **FR-9**: Ada tombol/link menuju halaman login admin di area footer/header (tersembunyi tapi bisa diakses)
- **FR-10**: Validasi form client-side (semua field wajib, format nomor HP, jumlah kursi minimal 1)

### Backend / Dashboard Admin
- **FR-11**: Halaman login admin dengan username & password
- **FR-12**: Session-based auth, logout otomatis jika browser ditutup / timeout
- **FR-13**: Dashboard admin menampilkan ringkasan: total booking hari ini, total pendapatan hari ini, booking menunggu konfirmasi
- **FR-14**: Halaman "Data Booking": list semua booking dengan filter status (Pending, Terkonfirmasi, Selesai, Dibatalkan), filter tanggal
- **FR-15**: Admin bisa **Tambah Booking Manual** (modal form, untuk input booking dari customer WA)
- **FR-16**: Admin bisa **Edit** data booking (ubah status, perbaiki data)
- **FR-17**: Admin bisa **Hapus** data booking
- **FR-18**: Setiap booking punya field `total_harga` (jumlah kursi x 200.000) otomatis
- **FR-19**: Setiap booking punya field `status`: pending, confirmed, completed, cancelled
- **FR-20**: Halaman "Laporan":
  - Laporan **Per Hari**: pilih tanggal, tampilkan list booking hari itu + total pendapatan
  - Laporan **Per Minggu**: pilih minggu, tampilkan ringkasan per hari + total
  - Laporan **Per Bulan**: pilih bulan & tahun, tampilkan ringkasan per tanggal + total pendapatan bulan itu
- **FR-21**: Laporan bisa di-print / di-export tampilan yang rapi (print-friendly CSS)

## Non-Functional Requirements
- **NFR-1**: Responsif 100%: layout menyesuaikan layar desktop (>=1024px), tablet (768-1023px), mobile (<768px). Di HP semua elemen kecil & tidak kepotong.
- **NFR-2**: Desain premium & modern: tema biru (biru tua, biru muda), shadow lembut, card dengan radius, icon Font Awesome, efek hover.
- **NFR-3**: Kinerja: loading halaman < 2 detik di jaringan normal (pakai CDN untuk CSS/JS, optimasi gambar)
- **NFR-4**: Keamanan: SQL injection protection (prepared statements PDO), CSRF token di form, XSS protection (htmlspecialchars)
- **NFR-5**: Maintainability: struktur folder rapi, kode berkomentar, pisahkan config/db/helpers
- **NFR-6**: Konsistensi UI: desain konsisten di semua halaman (warna, font, spacing, button style)
- **NFR-7**: Kompatibel browser: Chrome, Firefox, Safari, Edge versi terbaru

## Constraints
- **Technical**:
  - Stack: PHP 7.4+ (Native), MySQL 5.7+, HTML5, CSS3 (Tailwind CSS via CDN), JavaScript (Alpine.js untuk modal & interaksi)
  - Tidak boleh pakai framework Laravel/CodeIgniter (biar lightweight di Laragon, PHP Native)
  - Semua UI modal pake Alpine.js (sesuai preferensi user)
  - Font Awesome untuk icon
- **Business**:
  - Harga tiket fix: Rp 200.000 / orang (hardcoded, bisa diubah di config nanti)
  - Denda pembatalan 50% (hanya ditampilkan sebagai info, tidak dihitung otomatis di laporan - admin hitung manual)
- **Dependencies**:
  - Koneksi MySQL via PDO
  - Tailwind CSS CDN
  - Font Awesome CDN
  - Alpine.js CDN

## Assumptions
- Admin setidaknya punya 1 akun default: username `admin`, password `admin123` (bisa ganti nanti)
- Nomor WA admin: **placeholder 0812-XXXX-XXXX** (nanti user isi sendiri)
- Data rute/jurusan di halaman depan: contoh Blora - Surabaya, Blora - Malang, dll. sesuai referensi gambar (bisa diedit nanti)
- Untuk status booking "Selesai" dihitung sebagai pendapatan di laporan (booking cancelled tidak masuk perhitungan)

## Acceptance Criteria

### AC-1: Landing Page menampilkan semua section lengkap
- **Type**: `rule`
- **Given**: User membuka halaman `index.php` di browser
- **When**: Halaman selesai loading
- **Then**: Muncul section: Header (Logo + Navbar), Hero (Mustika Travel + armada), Pencarian/Pemesanan Tiket, Jurusan Kami, Pelayanan Door to Door, Form Booking Lengkap, Footer, dan link admin
- **Pass Condition**: Semua 8 section terlihat & gambar logo muncul
- **Evidence**: Screenshot halaman index

### AC-2: Form booking bisa submit data dan tersimpan ke database
- **Type**: `rule`
- **Given**: User mengisi semua field form booking dengan data valid
- **When**: User klik tombol "Booking Sekarang"
- **Then**: Data tersimpan ke tabel `bookings` di database, muncul pesan sukses "Booking berhasil, silakan hubungi WA admin untuk konfirmasi"
- **Pass Condition**: Record baru ada di tabel bookings dengan data sesuai input
- **Evidence**: Cek phpMyAdmin / query SELECT pada tabel bookings

### AC-3: Form booking validasi client-side (field wajib)
- **Type**: `rule`
- **Given**: User submit form booking dengan 1+ field kosong
- **When**: Klik tombol submit
- **Then**: Muncul pesan error di field yang kosong & form tidak terkirim
- **Pass Condition**: Tidak ada data baru masuk DB, pesan error muncul
- **Evidence**: Screenshot pesan validasi

### AC-4: Admin login berhasil dengan credential benar
- **Type**: `rule`
- **Given**: Admin membuka `admin/login.php`, input username `admin` password `admin123`
- **When**: Klik "Login"
- **Then**: Redirect ke `admin/dashboard.php` dengan session aktif
- **Pass Condition**: Berada di halaman dashboard & bisa lihat menu admin
- **Evidence**: Screenshot dashboard admin

### AC-5: Admin bisa tambah booking manual via modal
- **Type**: `rule`
- **Given**: Admin sudah login di halaman Data Booking
- **When**: Klik tombol "Tambah Booking Manual", isi form modal, submit
- **Then**: Data booking baru tersimpan, list booking ter-update, total harga otomatis = jumlah kursi x 200.000
- **Pass Condition**: Record baru ada di DB, list bertambah, total_harga benar
- **Evidence**: Screenshot list booking setelah tambah

### AC-6: Admin bisa ubah status booking
- **Type**: `rule`
- **Given**: Ada booking dengan status "Pending" di list
- **When**: Admin klik edit, ganti status jadi "Terkonfirmasi", simpan
- **Then**: Status booking berubah di list & di DB
- **Pass Condition**: Status di DB = confirmed
- **Evidence**: Query SELECT cek field status

### AC-7: Laporan Per Hari akurat
- **Type**: `rule`
- **Given**: Ada 3 booking status Selesai (completed) pada tanggal hari ini dengan total harga Rp 400rb, Rp 600rb, Rp 200rb
- **When**: Admin buka Laporan Per Hari, pilih tanggal hari ini
- **Then**: Muncul 3 record booking, Total Pendapatan = Rp 1.200.000, booking cancelled/pending tidak masuk hitungan
- **Pass Condition**: Jumlah sesuai perhitungan
- **Evidence**: Screenshot laporan & hitung manual

### AC-8: Laporan Per Bulan menampilkan akumulasi per tanggal
- **Type**: `rule`
- **Given**: Ada booking tersebar di tanggal 1,5,10 bulan ini dengan status completed
- **When**: Admin buka Laporan Per Bulan, pilih bulan ini
- **Then**: Muncul ringkasan per tanggal (tanggal X: Y booking, Rp Z) + Total Bulan
- **Pass Condition**: Total bulan = sum semua tanggal, hanya status completed
- **Evidence**: Screenshot laporan bulan

### AC-9: Tampilan responsif - tampil benar di layar HP < 480px
- **Type**: `rule`
- **Given**: Buka halaman depan di browser dengan viewport 375px (iPhone SE)
- **When**: Scroll seluruh halaman
- **Then**: Semua elemen tidak terpotong horizontal, form input & tombol full-width, text terbaca dengan jelas, tombol cukup besar untuk diklik jari
- **Pass Condition**: Tidak ada horizontal scrollbar, semua konten masuk layar
- **Evidence**: Screenshot viewport 375px (setiap section)

### AC-10: Tampilan responsif - tampil benar di layar laptop 1366px
- **Type**: `rule`
- **Given**: Buka halaman depan di viewport 1366x768
- **When**: Scroll seluruh halaman
- **Then**: Layout 2 kolom untuk section besar (Jurusan + Pelayanan), gambar armada di kanan, tidak ada space berlebihan
- **Pass Condition**: Layout rapi, tidak hancur
- **Evidence**: Screenshot viewport desktop

### AC-11: Kualitas desain UI / UX (Landing + Admin)
- **Type**: `rubric`
- **Dimension**: Kualitas visual desain: modern, premium, sesuai referensi (tema biru Mustika Travel), spacing konsisten, shadow lembut, detail diperhatikan
- **Scale**: 1-5
- **Anchors**:
  1 = Desain sangat dasar, tidak menarik, banyak space tidak seimbang
  3 = Lumayan, tema biru ada, tapi banyak detail kurang (button tidak konsisten, icon tidak rapi, font jelek)
  5 = Sangat premium, modern, tema biru + aksen emas sedikit, glow shadow subtle, card radius pas, margin/padding sempurna, icon & text align rapi, seperti referensi gambar
- **Pass Threshold**: >= 4
- **Evidence**: Screenshot landing page + dashboard admin + review visual manual

### AC-12: Performa loading page < 2 detik
- **Type**: `rubric`
- **Dimension**: Kecepatan loading halaman index.php pertama kali di localhost (tanpa cache)
- **Scale**: 1-5
- **Anchors**:
  1 = > 5 detik, banyak file berat
  3 = 2-4 detik, normal
  5 = < 1,5 detik, sangat cepat
- **Pass Threshold**: >= 4
- **Evidence**: Chrome DevTools Network tab screenshot

### AC-13: Struktur kode rapi & maintainable
- **Type**: `rubric`
- **Dimension**: Struktur folder jelas, config terpisah, function helper ada, tidak ada kode bercampur aduk antara logic & view
- **Scale**: 1-5
- **Anchors**:
  1 = Semua file di root folder, tidak ada structure, kode sulit dibaca
  3 = Ada folder, tapi sebagian masih campur, kurang helper
  5 = Struktur jelas: config/, includes/, admin/, assets/, helpers.php, logic & view terpisah baik, komentar jelas
- **Pass Threshold**: >= 4
- **Evidence**: Tree struktur folder + review file

## Open Questions
- [ ] **Q1**: Nomor WhatsApp admin yang mau ditampilkan? (saat ini aku placeholder `0812-XXXX-XXXX`)
- [ ] **Q2**: Alamat kantor Mustika Travel yang mau ditampilkan di footer & kontak? (placeholder "Jl. Contoh No. 123, Blora")
- [ ] **Q3**: Daftar jurusan/rute travel yang ditampilkan di section "Jurusan Kami" - mau pakai contoh (Blora-Surabaya, Blora-Malang, dll) atau ada list khusus?
- [ ] **Q4**: Harga tiket Rp 200.000 / org - ini fix untuk semua rute atau nanti rute berbeda harga beda? (saat ini asumsi fix semua 200rb)
