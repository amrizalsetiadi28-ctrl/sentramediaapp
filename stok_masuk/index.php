<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = getKoneksi();

$daftar = $pdo->query(
    "SELECT sm.id_stok_masuk, sm.no_faktur, sm.tanggal_masuk, sm.keterangan,
            s.nama_supplier, a.nama_lengkap AS nama_admin,
            (SELECT COALESCE(SUM(d.jumlah),0) FROM detail_stok_masuk d WHERE d.id_stok_masuk = sm.id_stok_masuk) AS total_eksemplar
     FROM stok_masuk sm
     JOIN supplier s ON s.id_supplier = sm.id_supplier
     JOIN admin a ON a.id_admin = sm.id_admin
     ORDER BY sm.tanggal_masuk DESC, sm.id_stok_masuk DESC"
)->fetchAll();

$judulHalaman = 'Stok Masuk';
$activeMenu = 'stok_masuk';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-end mb-3">
  <a href="tambah.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Catat Stok Masuk</a>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>Tanggal</th>
        <th>No. Faktur</th>
        <th>Supplier</th>
        <th class="text-end">Total Eksemplar</th>
        <th>Dicatat Oleh</th>
        <th class="text-center">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($daftar)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat stok masuk.</td></tr>
      <?php else: ?>
        <?php foreach ($daftar as $r): ?>
          <tr>
            <td><?= formatTanggal($r['tanggal_masuk']) ?></td>
            <td><?= htmlspecialchars($r['no_faktur'] ?: '-') ?></td>
            <td><?= htmlspecialchars($r['nama_supplier']) ?></td>
            <td class="text-end"><?= (int) $r['total_eksemplar'] ?></td>
            <td><?= htmlspecialchars($r['nama_admin']) ?></td>
            <td class="text-center">
              <a href="detail.php?id=<?= (int) $r['id_stok_masuk'] ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-eye"></i> Detail
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
