<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = getKoneksi();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM buku WHERE id_buku = ?');
$stmt->execute([$id]);
$buku = $stmt->fetch();

if (!$buku) {
    setPesan('error', 'Data buku tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$error = [];
$form = $buku;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['kode_buku'] = trim($_POST['kode_buku'] ?? '');
    $form['judul'] = trim($_POST['judul'] ?? '');
    $form['penulis'] = trim($_POST['penulis'] ?? '');
    $form['penerbit'] = trim($_POST['penerbit'] ?? '');
    $form['harga_jual'] = trim($_POST['harga_jual'] ?? '');
    $form['stok_minimum'] = trim($_POST['stok_minimum'] ?? '');

    if ($form['kode_buku'] === '') {
        $error[] = 'Kode buku wajib diisi.';
    }
    if ($form['judul'] === '') {
        $error[] = 'Judul buku wajib diisi.';
    }
    if ($form['harga_jual'] === '' || !is_numeric($form['harga_jual']) || (float) $form['harga_jual'] < 0) {
        $error[] = 'Harga jual wajib diisi dengan angka dan tidak boleh negatif.';
    }
    if ($form['stok_minimum'] === '' || !ctype_digit((string) $form['stok_minimum'])) {
        $error[] = 'Stok minimum wajib diisi dengan angka bulat dan tidak boleh negatif.';
    }

    if (empty($error)) {
        $cek = $pdo->prepare('SELECT COUNT(*) FROM buku WHERE kode_buku = ? AND id_buku <> ?');
        $cek->execute([$form['kode_buku'], $id]);
        if ((int) $cek->fetchColumn() > 0) {
            $error[] = 'Kode buku sudah digunakan oleh buku lain.';
        }
    }

    if (empty($error)) {
        try {
            $upd = $pdo->prepare(
                'UPDATE buku SET kode_buku=?, judul=?, penulis=?, penerbit=?, harga_jual=?, stok_minimum=?
                 WHERE id_buku=?'
            );
            $upd->execute([
                $form['kode_buku'],
                $form['judul'],
                $form['penulis'],
                $form['penerbit'],
                $form['harga_jual'],
                $form['stok_minimum'],
                $id,
            ]);
            setPesan('sukses', 'Data buku berhasil diperbarui.');
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error[] = 'Gagal menyimpan perubahan. Kode buku mungkin sudah dipakai.';
        }
    }
}

$judulHalaman = 'Edit Buku';
$activeMenu = 'buku';
require __DIR__ . '/../includes/header.php';
?>

<div class="card card-stat">
  <div class="card-body">
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($error as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Kode Buku <span class="text-danger">*</span></label>
        <input type="text" name="kode_buku" class="form-control" required maxlength="20"
               value="<?= htmlspecialchars($form['kode_buku']) ?>">
      </div>
      <div class="col-md-8">
        <label class="form-label">Judul <span class="text-danger">*</span></label>
        <input type="text" name="judul" class="form-control" required maxlength="200"
               value="<?= htmlspecialchars($form['judul']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Penulis</label>
        <input type="text" name="penulis" class="form-control" maxlength="100"
               value="<?= htmlspecialchars($form['penulis']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Penerbit</label>
        <input type="text" name="penerbit" class="form-control" maxlength="100"
               value="<?= htmlspecialchars($form['penerbit']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Harga Jual <span class="text-danger">*</span></label>
        <div class="input-group">
          <span class="input-group-text">Rp</span>
          <input type="number" name="harga_jual" class="form-control" required min="0" step="0.01"
                 value="<?= htmlspecialchars($form['harga_jual']) ?>">
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Stok Minimum <span class="text-danger">*</span></label>
        <input type="number" name="stok_minimum" class="form-control" required min="0" step="1"
               value="<?= htmlspecialchars($form['stok_minimum']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Stok Saat Ini</label>
        <input type="text" class="form-control" value="<?= (int) $buku['stok'] ?>" disabled>
        <div class="form-text">Ubah lewat menu Stok Masuk / Penjualan.</div>
      </div>
      <div class="col-12 mt-4">
        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Perubahan</button>
        <a href="index.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
