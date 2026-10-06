<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = getKoneksi();
$kataKunci = trim($_GET['q'] ?? '');

if ($kataKunci !== '') {
    $stmt = $pdo->prepare('SELECT * FROM supplier WHERE nama_supplier LIKE ? ORDER BY nama_supplier ASC');
    $stmt->execute(['%' . $kataKunci . '%']);
} else {
    $stmt = $pdo->query('SELECT * FROM supplier ORDER BY nama_supplier ASC');
}
$daftarSupplier = $stmt->fetchAll();

$judulHalaman = 'Data Supplier';
$activeMenu = 'supplier';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <form method="get" class="d-flex gap-2">
    <input type="text" name="q" class="form-control" placeholder="Cari nama supplier..."
           value="<?= htmlspecialchars($kataKunci) ?>" style="min-width:260px;">
    <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
    <?php if ($kataKunci !== ''): ?>
      <a href="index.php" class="btn btn-outline-secondary">Reset</a>
    <?php endif; ?>
  </form>
  <a href="tambah.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Tambah Supplier</a>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>Nama Supplier</th>
        <th>Alamat</th>
        <th>No. Telepon</th>
        <th class="text-center">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($daftarSupplier)): ?>
        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data supplier.</td></tr>
      <?php else: ?>
        <?php foreach ($daftarSupplier as $s): ?>
          <tr>
            <td><?= htmlspecialchars($s['nama_supplier']) ?></td>
            <td><?= htmlspecialchars($s['alamat'] ?: '-') ?></td>
            <td><?= htmlspecialchars($s['no_telepon'] ?: '-') ?></td>
            <td class="text-center">
              <a href="edit.php?id=<?= (int) $s['id_supplier'] ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil-square"></i>
              </a>
              <a href="hapus.php?id=<?= (int) $s['id_supplier'] ?>" class="btn btn-sm btn-outline-danger"
                 onclick="return confirm('Hapus supplier &quot;<?= htmlspecialchars(addslashes($s['nama_supplier'])) ?>&quot;?');">
                <i class="bi bi-trash"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
