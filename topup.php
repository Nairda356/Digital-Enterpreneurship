<?php
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['game'] ?? '';
$st = db()->prepare('SELECT * FROM games WHERE slug = ? AND aktif = 1');
$st->execute([$slug]);
$game = $st->fetch();
if (!$game) {
    flash('Game tidak ditemukan.', 'err');
    redirect(url('index.php'));
}

$st = db()->prepare('SELECT * FROM produk WHERE game_id = ? AND aktif = 1 ORDER BY harga_jual');
$st->execute([$game['id']]);
$daftarProduk = $st->fetchAll();
$daftarMetode = db()->query('SELECT * FROM metode_pembayaran WHERE aktif = 1 ORDER BY id')->fetchAll();

$err = [];
$old = ['user_game_id' => '', 'server_game' => '', 'nama' => '', 'kontak' => '', 'produk' => '', 'metode' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old['user_game_id'] = trim($_POST['user_game_id'] ?? '');
    $old['server_game']  = trim($_POST['server_game'] ?? '');
    $old['nama']         = trim($_POST['nama'] ?? '');
    $old['kontak']       = preg_replace('/[\s\-]/', '', $_POST['kontak'] ?? '');
    $old['produk']       = (int) ($_POST['produk'] ?? 0);
    $old['metode']       = (int) ($_POST['metode'] ?? 0);

    if (!preg_match('/^[A-Za-z0-9#_.\-]{4,40}$/', $old['user_game_id'])) {
        $err['user_game_id'] = $game['label_id'] . ' harus 4-40 karakter (huruf, angka, #, _, . atau -).';
    }
    if ($game['butuh_server'] && !preg_match('/^[A-Za-z0-9 ]{1,20}$/', $old['server_game'])) {
        $err['server_game'] = ($game['label_server'] ?: 'Server') . ' wajib diisi.';
    }
    if (!preg_match('/^(\+62|62|0)8[0-9]{8,12}$/', $old['kontak'])) {
        $err['kontak'] = 'Nomor WhatsApp tidak valid. Contoh: 081234567890.';
    }
    if (mb_strlen($old['nama']) > 80) {
        $err['nama'] = 'Nama maksimal 80 karakter.';
    }

    // Harga selalu dibaca ulang dari database, bukan dari data yang dikirim browser
    $p = db()->prepare('SELECT * FROM produk WHERE id = ? AND game_id = ? AND aktif = 1');
    $p->execute([$old['produk'], $game['id']]);
    $produk = $p->fetch();
    if (!$produk) {
        $err['produk'] = 'Pilih nominal item terlebih dahulu.';
    }
    $m = db()->prepare('SELECT * FROM metode_pembayaran WHERE id = ? AND aktif = 1');
    $m->execute([$old['metode']]);
    $metode = $m->fetch();
    if (!$metode) {
        $err['metode'] = 'Pilih metode pembayaran.';
    }

    if (!$err) {
        $kode = buat_kode();
        $ins = db()->prepare(
            'INSERT INTO transaksi (kode, game_id, produk_id, metode_id, user_game_id, server_game, nama_pelanggan,
                                    kontak, harga_modal, harga_jual, biaya_admin, total)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $ins->execute([
            $kode, $game['id'], $produk['id'], $metode['id'], $old['user_game_id'],
            $game['butuh_server'] ? $old['server_game'] : null,
            $old['nama'] !== '' ? $old['nama'] : null, $old['kontak'],
            $produk['harga_modal'], $produk['harga_jual'], $metode['biaya_admin'],
            $produk['harga_jual'] + $metode['biaya_admin'],
        ]);
        flash('Pesanan dibuat. Simpan kode invoice ' . $kode . ' untuk memantau status.');
        redirect(url('invoice.php?kode=' . $kode));
    }
}

$judul = 'Top up ' . $game['nama'];
require __DIR__ . '/includes/header.php';
?>
<div class="crumb"><a href="<?= e(url('index.php')) ?>">Beranda</a> / <?= e($game['nama']) ?></div>

<form method="post" class="order" id="form-order" novalidate>
  <?= csrf_field() ?>
  <div class="order-main">
    <div class="order-head" style="--warna: <?= e($game['warna']) ?>">
      <span class="game-mark"><?= e(inisial($game['nama'])) ?></span>
      <div><h1><?= e($game['nama']) ?></h1><p class="muted"><?= e($game['publisher']) ?></p></div>
    </div>

    <fieldset class="card">
      <legend>Data akun game</legend>
      <label class="field">
        <span><?= e($game['label_id']) ?></span>
        <input class="input" name="user_game_id" value="<?= e($old['user_game_id']) ?>" required autocomplete="off">
        <?php if (isset($err['user_game_id'])): ?><small class="err"><?= e($err['user_game_id']) ?></small><?php endif; ?>
      </label>
      <?php if ($game['butuh_server']): ?>
      <label class="field">
        <span><?= e($game['label_server'] ?: 'Server') ?></span>
        <input class="input" name="server_game" value="<?= e($old['server_game']) ?>" required autocomplete="off">
        <?php if (isset($err['server_game'])): ?><small class="err"><?= e($err['server_game']) ?></small><?php endif; ?>
      </label>
      <?php endif; ?>
    </fieldset>

    <fieldset class="card">
      <legend>Pilih nominal</legend>
      <?php if (isset($err['produk'])): ?><small class="err"><?= e($err['produk']) ?></small><?php endif; ?>
      <?php if (!$daftarProduk): ?><p class="empty">Belum ada item untuk game ini.</p><?php endif; ?>
      <div class="choice-grid">
        <?php foreach ($daftarProduk as $p): ?>
          <label class="choice">
            <input type="radio" name="produk" value="<?= (int) $p['id'] ?>" data-harga="<?= (int) $p['harga_jual'] ?>"
                   data-nama="<?= e($p['nama_item']) ?>" <?= $old['produk'] == $p['id'] ? 'checked' : '' ?>>
            <span class="choice-box"><b><?= e($p['nama_item']) ?></b><span><?= e(rupiah($p['harga_jual'])) ?></span></span>
          </label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <fieldset class="card">
      <legend>Metode pembayaran</legend>
      <?php if (isset($err['metode'])): ?><small class="err"><?= e($err['metode']) ?></small><?php endif; ?>
      <div class="choice-list">
        <?php foreach ($daftarMetode as $m): ?>
          <label class="choice">
            <input type="radio" name="metode" value="<?= (int) $m['id'] ?>" data-biaya="<?= (int) $m['biaya_admin'] ?>"
                   data-nama="<?= e($m['nama']) ?>" <?= $old['metode'] == $m['id'] ? 'checked' : '' ?>>
            <span class="choice-box row"><b><?= e($m['nama']) ?></b>
              <span><?= $m['biaya_admin'] > 0 ? 'Biaya ' . e(rupiah($m['biaya_admin'])) : 'Tanpa biaya' ?></span></span>
          </label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <fieldset class="card">
      <legend>Kontak pembeli</legend>
      <label class="field">
        <span>Nomor WhatsApp</span>
        <input class="input" name="kontak" inputmode="tel" value="<?= e($old['kontak']) ?>" placeholder="081234567890" required>
        <?php if (isset($err['kontak'])): ?><small class="err"><?= e($err['kontak']) ?></small><?php endif; ?>
      </label>
      <label class="field">
        <span>Nama (boleh dikosongkan)</span>
        <input class="input" name="nama" value="<?= e($old['nama']) ?>" maxlength="80">
        <?php if (isset($err['nama'])): ?><small class="err"><?= e($err['nama']) ?></small><?php endif; ?>
      </label>
    </fieldset>
  </div>

  <aside class="summary card">
    <h2>Ringkasan</h2>
    <dl>
      <dt>Game</dt><dd><?= e($game['nama']) ?></dd>
      <dt>Item</dt><dd id="s-item">Belum dipilih</dd>
      <dt>Harga</dt><dd id="s-harga">-</dd>
      <dt>Metode</dt><dd id="s-metode">Belum dipilih</dd>
      <dt>Biaya admin</dt><dd id="s-biaya">-</dd>
    </dl>
    <div class="total"><span>Total bayar</span><strong id="s-total">Rp 0</strong></div>
    <button class="btn btn-primary btn-block" type="submit">Buat pesanan</button>
  </aside>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
