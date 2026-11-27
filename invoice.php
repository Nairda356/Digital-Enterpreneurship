<?php
require_once __DIR__ . '/includes/functions.php';

$kode = strtoupper(trim($_GET['kode'] ?? ''));
$sql = 'SELECT t.*, g.nama AS game, g.label_id, g.label_server, g.warna, p.nama_item,
               m.nama AS metode, m.tujuan
          FROM transaksi t
          JOIN games g ON g.id = t.game_id
          JOIN produk p ON p.id = t.produk_id
          JOIN metode_pembayaran m ON m.id = t.metode_id
         WHERE t.kode = ?';
$st = db()->prepare($sql);
$st->execute([$kode]);
$t = $st->fetch();

if ($t && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'bayar' && $t['status'] === 'pending') {
        db()->prepare("UPDATE transaksi SET status = 'dibayar' WHERE id = ?")->execute([$t['id']]);
        flash('Terima kasih. Pembayaran kamu akan diverifikasi admin.');
    } elseif ($aksi === 'batal' && $t['status'] === 'pending') {
        db()->prepare("UPDATE transaksi SET status = 'batal' WHERE id = ?")->execute([$t['id']]);
        flash('Pesanan dibatalkan.');
    }
    redirect(url('invoice.php?kode=' . $t['kode']));
}

$judul = $t ? 'Invoice ' . $t['kode'] : 'Pesanan tidak ditemukan';
require __DIR__ . '/includes/header.php';

if (!$t): ?>
  <div class="card center">
    <h1>Pesanan tidak ditemukan</h1>
    <p class="muted">Periksa kembali kode invoice, lalu coba lagi.</p>
    <a class="btn btn-primary" href="<?= e(url('cek.php')) ?>">Cek pesanan lain</a>
  </div>
<?php else:
  $urut = ['pending', 'dibayar', 'sukses'];
  $posisi = array_search($t['status'], $urut, true);
?>
<div class="invoice card">
  <div class="invoice-head">
    <div>
      <p class="muted">Kode invoice</p>
      <h1 class="kode"><?= e($t['kode']) ?></h1>
    </div>
    <?= status_badge($t['status']) ?>
  </div>

  <?php if ($posisi !== false): ?>
  <ol class="timeline">
    <li class="<?= $posisi >= 0 ? 'done' : '' ?>">Pesanan dibuat</li>
    <li class="<?= $posisi >= 1 ? 'done' : '' ?>">Pembayaran dikirim</li>
    <li class="<?= $posisi >= 2 ? 'done' : '' ?>">Item masuk ke akun</li>
  </ol>
  <?php endif; ?>

  <dl class="detail">
    <dt>Game</dt><dd><?= e($t['game']) ?></dd>
    <dt><?= e($t['label_id']) ?></dt><dd><?= e($t['user_game_id']) ?></dd>
    <?php if ($t['server_game']): ?><dt><?= e($t['label_server'] ?: 'Server') ?></dt><dd><?= e($t['server_game']) ?></dd><?php endif; ?>
    <dt>Item</dt><dd><?= e($t['nama_item']) ?></dd>
    <dt>Harga</dt><dd><?= e(rupiah($t['harga_jual'])) ?></dd>
    <dt>Biaya admin</dt><dd><?= e(rupiah($t['biaya_admin'])) ?></dd>
    <dt>Total bayar</dt><dd><strong><?= e(rupiah($t['total'])) ?></strong></dd>
    <dt>Dibuat</dt><dd><?= e(tanggal($t['created_at'])) ?></dd>
  </dl>

  <?php if ($t['status'] === 'pending'): ?>
  <div class="pay-box">
    <h2>Cara bayar lewat <?= e($t['metode']) ?></h2>
    <p>Kirim tepat <strong><?= e(rupiah($t['total'])) ?></strong> ke:</p>
    <p class="pay-dest"><?= e($t['tujuan']) ?></p>
    <form method="post" class="actions">
      <?= csrf_field() ?>
      <button class="btn btn-primary" name="aksi" value="bayar">Saya sudah bayar</button>
      <button class="btn btn-ghost" name="aksi" value="batal" data-konfirmasi="Batalkan pesanan ini?">Batalkan pesanan</button>
    </form>
  </div>
  <?php elseif ($t['status'] === 'dibayar'): ?>
    <p class="notice">Admin sedang memeriksa pembayaranmu. Muat ulang halaman ini untuk melihat status terbaru.</p>
  <?php elseif ($t['status'] === 'sukses'): ?>
    <p class="notice notice-ok">Item sudah dikirim ke akun game kamu. Selamat bermain!</p>
  <?php elseif ($t['status'] === 'gagal'): ?>
    <p class="notice notice-err">Pesanan gagal diproses<?= $t['catatan'] ? ': ' . e($t['catatan']) : '' ?>. Hubungi admin lewat WhatsApp dengan menyebut kode invoice.</p>
  <?php endif; ?>
</div>
<?php endif;
require __DIR__ . '/includes/footer.php';
