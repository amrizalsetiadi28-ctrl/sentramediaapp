<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = getKoneksi();

$kataKunci = trim($_GET['q'] ?? '');

if ($kataKunci !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM buku
         WHERE kode_buku LIKE :k OR judul LIKE :k OR penulis LIKE :k OR penerbit LIKE :k
         ORDER BY judul ASC"
    );
    $stmt->execute(['k' => '%' . $kataKunci . '%']);
} else {
    $stmt = $pdo->query('SELECT * FROM buku ORDER BY judul ASC');
}
$daftarBuku = $stmt->fetchAll();

$judulHalaman = 'Data Buku';
$activeMenu = 'buku';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <form method="get" class="d-flex gap-2">
    <input type="text" name="q" class="form-control" placeholder="Cari kode, judul, penulis, atau penerbit..."
           value="<?= htmlspecialchars($kataKunci) ?>" style="min-width:280px;">
    <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
    <?php if ($kataKunci !== ''): ?>
      <a href="index.php" class="btn btn-outline-secondary">Reset</a>
    <?php endif; ?>
  </form>
  <a href="tambah.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Tambah Buku</a>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>Kode</th>
        <th>Judul</th>
        <th>Penulis</th>
        <th>Penerbit</th>
        <th class="text-end">Harga Jual</th>
        <th class="text-end">Stok</th>
        <th>Status</th>
        <th class="text-center">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($daftarBuku)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data buku.</td></tr>
      <?php else: ?>
        <?php foreach ($daftarBuku as $b): ?>
          <?php
            if ($b['stok'] <= 0) {
                $badge = '<span class="badge badge-stok-habis">Habis</span>';
            } elseif ($b['stok'] <= $b['stok_minimum']) {
                $badge = '<span class="badge badge-stok-menipis">Perlu Dipesan Ulang</span>';
            } else {
                $badge = '<span class="badge badge-stok-aman">Aman</span>';
            }
          ?>
          <tr>
            <td><?= htmlspecialchars($b['kode_buku']) ?></td>
            <td><?= htmlspecialchars($b['judul']) ?></td>
            <td><?= htmlspecialchars($b['penulis'] ?: '-') ?></td>
            <td><?= htmlspecialchars($b['penerbit'] ?: '-') ?></td>
            <td class="text-end"><?= formatRupiah($b['harga_jual']) ?></td>
            <td class="text-end"><?= (int) $b['stok'] ?></td>
            <td><?= $badge ?></td>
            <td class="text-center">
              <a href="edit.php?id=<?= (int) $b['id_buku'] ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil-square"></i>
              </a>
              <a href="hapus.php?id=<?= (int) $b['id_buku'] ?>" class="btn btn-sm btn-outline-danger"
                 onclick="return confirm('Hapus buku &quot;<?= htmlspecialchars(addslashes($b['judul'])) ?>&quot;? Data ini tidak dapat dikembalikan.');">
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
