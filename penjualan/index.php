<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = getKoneksi();

$daftar = $pdo->query(
    "SELECT p.id_penjualan, p.no_transaksi, p.tanggal_transaksi, p.nama_pembeli,
            p.metode_pembayaran, p.total_harga, a.nama_lengkap AS nama_admin
     FROM penjualan p
     JOIN admin a ON a.id_admin = p.id_admin
     ORDER BY p.tanggal_transaksi DESC, p.id_penjualan DESC"
)->fetchAll();

$judulHalaman = 'Penjualan';
$activeMenu = 'penjualan';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-end mb-3">
  <a href="tambah.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Catat Penjualan</a>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>No. Transaksi</th>
        <th>Tanggal</th>
        <th>Pembeli</th>
        <th>Metode Bayar</th>
        <th class="text-end">Total</th>
        <th>Dicatat Oleh</th>
        <th class="text-center">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($daftar)): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada riwayat penjualan.</td></tr>
      <?php else: ?>
        <?php foreach ($daftar as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['no_transaksi']) ?></td>
            <td><?= formatTanggalWaktu($r['tanggal_transaksi']) ?></td>
            <td><?= htmlspecialchars($r['nama_pembeli'] ?: '-') ?></td>
            <td><?= htmlspecialchars($r['metode_pembayaran'] ?: '-') ?></td>
            <td class="text-end"><?= formatRupiah($r['total_harga']) ?></td>
            <td><?= htmlspecialchars($r['nama_admin']) ?></td>
            <td class="text-center">
              <a href="detail.php?id=<?= (int) $r['id_penjualan'] ?>" class="btn btn-sm btn-outline-secondary">
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
