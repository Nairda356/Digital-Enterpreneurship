<?php
$judul = 'Top up game cepat dan murah';
require __DIR__ . '/includes/header.php';

$games = db()->query(
    "SELECT g.*, MIN(p.harga_jual) AS mulai
       FROM games g
       LEFT JOIN produk p ON p.game_id = g.id AND p.aktif = 1
      WHERE g.aktif = 1
      GROUP BY g.id
      ORDER BY g.nama"
)->fetchAll();

$sukses = (int) db()->query("SELECT COUNT(*) FROM transaksi WHERE status = 'sukses'")->fetchColumn();
?>
<section class="hero">
  <div>
    <h1>Top up game, saldo masuk tanpa ribet.</h1>
    <p class="lead">Pilih game, isi ID akun, bayar. Pesanan dicek admin dan statusnya bisa kamu pantau kapan saja dengan kode invoice.</p>
    <div class="hero-stat"><strong><?= number_format($sukses, 0, ',', '.') ?></strong> pesanan sudah berhasil diproses</div>
  </div>
  <ol class="steps">
    <li><b>Pilih game</b><span>dan nominal item yang kamu mau</span></li>
    <li><b>Isi ID akun</b><span>pastikan ID dan server sudah benar</span></li>
    <li><b>Bayar</b><span>lewat QRIS, e-wallet, atau transfer bank</span></li>
  </ol>
</section>

<section>
  <div class="section-head">
    <h2>Pilih game</h2>
    <input type="search" id="cari-game" class="input input-search" placeholder="Cari nama game" aria-label="Cari nama game">
  </div>
  <div class="game-grid" id="game-grid">
    <?php foreach ($games as $g): ?>
      <a class="game-tile" data-nama="<?= e(strtolower($g['nama'])) ?>"
         href="<?= e(url('topup.php?game=' . $g['slug'])) ?>" style="--warna: <?= e($g['warna']) ?>">
        <span class="game-mark"><?= e(inisial($g['nama'])) ?></span>
        <span class="game-name"><?= e($g['nama']) ?></span>
        <span class="game-pub"><?= e($g['publisher']) ?></span>
        <span class="game-price"><?= $g['mulai'] ? 'Mulai ' . e(rupiah($g['mulai'])) : 'Belum ada item' ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <p class="empty" id="game-kosong" hidden>Game tidak ditemukan. Coba kata kunci lain.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
