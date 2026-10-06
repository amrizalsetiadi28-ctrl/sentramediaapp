<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = getKoneksi();
$error = [];
$form = ['nama_supplier' => '', 'alamat' => '', 'no_telepon' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['nama_supplier'] = trim($_POST['nama_supplier'] ?? '');
    $form['alamat'] = trim($_POST['alamat'] ?? '');
    $form['no_telepon'] = trim($_POST['no_telepon'] ?? '');

    if ($form['nama_supplier'] === '') {
        $error[] = 'Nama supplier wajib diisi.';
    }
    if ($form['no_telepon'] !== '' && !preg_match('/^[0-9+\-\s()]{6,20}$/', $form['no_telepon'])) {
        $error[] = 'Format nomor telepon tidak valid.';
    }

    if (empty($error)) {
        $stmt = $pdo->prepare('INSERT INTO supplier (nama_supplier, alamat, no_telepon) VALUES (?, ?, ?)');
        $stmt->execute([$form['nama_supplier'], $form['alamat'], $form['no_telepon']]);
        setPesan('sukses', 'Data supplier berhasil ditambahkan.');
        header('Location: index.php');
        exit;
    }
}

$judulHalaman = 'Tambah Supplier';
$activeMenu = 'supplier';
require __DIR__ . '/../includes/header.php';
?>

<div class="card card-stat">
  <div class="card-body">
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($error as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
    <form method="post" class="auto-focus row g-3">
      <div class="col-md-6">
        <label class="form-label">Nama Supplier <span class="text-danger">*</span></label>
        <input type="text" name="nama_supplier" class="form-control" required maxlength="100"
               value="<?= htmlspecialchars($form['nama_supplier']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">No. Telepon</label>
        <input type="text" name="no_telepon" class="form-control" maxlength="20"
               value="<?= htmlspecialchars($form['no_telepon']) ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Alamat</label>
        <textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($form['alamat']) ?></textarea>
      </div>
      <div class="col-12 mt-3">
        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan</button>
        <a href="index.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
