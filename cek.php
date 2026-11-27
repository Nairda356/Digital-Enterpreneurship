<?php
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $kode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['kode'] ?? ''));
    if ($kode === '') {
        flash('Masukkan kode invoice.', 'err');
        redirect(url('cek.php'));
    }
    redirect(url('invoice.php?kode=' . $kode));
}

$judul = 'Cek pesanan';
require __DIR__ . '/includes/header.php';
?>
<div class="card narrow">
  <h1>Cek status pesanan</h1>
  <p class="muted">Kode invoice dikirim setelah kamu membuat pesanan, contohnya KG261109AB2C.</p>
  <form method="post">
    <?= csrf_field() ?>
    <label class="field">
      <span>Kode invoice</span>
      <input class="input" name="kode" required autocomplete="off" maxlength="20">
    </label>
    <button class="btn btn-primary" type="submit">Cek status</button>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
