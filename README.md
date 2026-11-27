# KiosGame - Sistem Top Up Game (PHP + MySQL)

Proyek mata kuliah Digital Entrepreneurship. Toko top up game berbasis web dengan dashboard admin
untuk memantau omzet, laba, dan status pesanan.

## Cara menjalankan (XAMPP / Laragon)
1. Salin folder `topup-game` ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Nyalakan Apache dan MySQL.
3. Buka phpMyAdmin, pilih tab **Import**, unggah `database.sql`. Database `db_topup_game` terbuat otomatis beserta data contoh.
4. Jika perlu, sesuaikan `config/config.php` (user/password MySQL dan `BASE_URL`).
5. Buka `http://localhost/topup-game/` untuk toko dan `http://localhost/topup-game/admin/` untuk admin.

Login admin awal: **admin / admin123**. Ganti lewat menu Profil setelah masuk.
Butuh PHP 7.4 atau lebih baru dan MySQL 5.7 / MariaDB 10.3 atau lebih baru.

## Struktur file
```
topup-game/
  database.sql            skema 5 tabel + data contoh
  config/config.php       koneksi database dan pengaturan
  includes/               functions.php (PDO, CSRF, helper), header.php, footer.php
  index.php               katalog game
  topup.php               form pesanan (ID akun, nominal, metode bayar)
  invoice.php             detail pesanan, instruksi bayar, status
  cek.php                 cek pesanan dengan kode invoice
  admin/
    login.php, logout.php
    index.php             dashboard (omzet, laba, grafik, game terlaris)
    transaksi.php         daftar transaksi, filter, ubah status
    games.php             kelola game
    produk.php            kelola item, harga modal dan jual
    laporan.php           laporan periode, ekspor CSV, cetak
    profil.php            ganti password
  assets/css/style.css, assets/js/app.js
```

## Alur bisnis
Pelanggan memilih game, nominal, dan metode bayar, lalu mendapat kode invoice (status **menunggu pembayaran**).
Setelah membayar, pelanggan menekan "Saya sudah bayar" (status **menunggu verifikasi**).
Admin memeriksa mutasi pembayaran, memproses top up, lalu mengubah status menjadi **sukses** atau **gagal**.

## Rumus pemantauan usaha
- Omzet = jumlah `harga_jual` transaksi berstatus sukses
- Laba kotor = omzet - jumlah `harga_modal` transaksi sukses
- Margin = laba kotor / omzet
- Biaya admin pembayaran dibebankan ke pembeli dan tidak dihitung sebagai omzet

## Keamanan yang sudah diterapkan
Prepared statement PDO (anti SQL injection), `htmlspecialchars` pada semua output (anti XSS), token CSRF pada semua form POST,
`password_hash` / `password_verify`, `session_regenerate_id` saat login, dan harga dihitung ulang dari database saat pesanan dibuat.

## Pengembangan lanjutan (bisa ditulis di laporan)
Integrasi payment gateway (Midtrans/Xendit) untuk verifikasi otomatis, integrasi API supplier top up agar proses tanpa admin,
notifikasi WhatsApp, kupon diskon, dan akun pelanggan dengan riwayat pembelian.
