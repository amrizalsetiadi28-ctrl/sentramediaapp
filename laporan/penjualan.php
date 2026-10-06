<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = getKoneksi();

$tanggalMulai = trim($_GET['dari'] ?? date('Y-m-01'));
$tanggalAkhir = trim($_GET['sampai'] ?? date('Y-m-d'));

if (!strtotime($tanggalMulai)) {
    $tanggalMulai = date('Y-m-01');
}
if (!strtotime($tanggalAkhir)) {
    $tanggalAkhir = date('Y-m-d');
}
// Jika pengguna menukar tanggal, tetap tampilkan hasil yang benar
if ($tanggalMulai > $tanggalAkhir) {
    [$tanggalMulai, $tanggalAkhir] = [$tanggalAkhir, $tanggalMulai];
}

$stmt = $pdo->prepare(
    "SELECT p.*, a.nama_lengkap AS nama_admin
     FROM penjualan p
     JOIN admin a ON a.id_admin = p.id_admin
     WHERE DATE(p.tanggal_transaksi) BETWEEN ? AND ?
     ORDER BY p.tanggal_transaksi ASC"
);
$stmt->execute([$tanggalMulai, $tanggalAkhir]);
$transaksi = $stmt->fetchAll();

$totalPeriode = array_sum(array_column($transaksi, 'total_harga'));

$stmtPerBuku = $pdo->prepare(
    "SELECT b.kode_buku, b.judul, SUM(d.jumlah) AS total_jumlah, SUM(d.subtotal) AS total_subtotal
     FROM detail_penjualan d
     JOIN penjualan p ON p.id_penjualan = d.id_penjualan
     JOIN buku b ON b.id_buku = d.id_buku
     WHERE DATE(p.tanggal_transaksi) BETWEEN ? AND ?
     GROUP BY d.id_buku, b.kode_buku, b.judul
     ORDER BY total_jumlah DESC"
);
$stmtPerBuku->execute([$tanggalMulai, $tanggalAkhir]);
$ringkasanBuku = $stmtPerBuku->fetchAll();

$judulHalaman = 'Laporan Penjualan';
$activeMenu = 'laporan';
require __DIR__ . '/../includes/header.php';
?>

<ul class="nav nav-pills mb-3 no-print">
  <li class="nav-item"><a class="nav-link active" href="penjualan.php">Laporan Penjualan</a></li>
  <li class="nav-item"><a class="nav-link" href="persediaan.php">Laporan Persediaan</a></li>
</ul>

<div class="card card-stat mb-3 no-print">
  <div class="card-body">
    <form method="get" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label">Dari Tanggal</label>
        <input type="date" name="dari" class="form-control" value="<?= htmlspecialchars($tanggalMulai) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Sampai Tanggal</label>
        <input type="date" name="sampai" class="form-control" value="<?= htmlspecialchars($tanggalAkhir) ?>">
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-success"><i class="bi bi-funnel"></i> Tampilkan</button>
      </div>
      <div class="col-md-3 text-end">
        <button type="button" onclick="window.print()" class="btn btn-outline-primary">
          <i class="bi bi-printer"></i> Cetak Laporan
        </button>
      </div>
    </form>
  </div>
</div>

<div class="card card-stat mb-3">
  <div class="card-body">
    <h6 class="mb-1">Laporan Penjualan</h6>
    <p class="text-muted small mb-3">
      Periode: <?= formatTanggal($tanggalMulai) ?> s.d. <?= formatTanggal($tanggalAkhir) ?>
    </p>

    <div class="table-responsive">
      <table class="table table-bordered">
        <thead class="table-light">
          <tr>
            <th>No. Transaksi</th><th>Tanggal</th><th>Pembeli</th>
            <th>Metode Bayar</th><th class="text-end">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($transaksi)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada transaksi pada periode ini.</td></tr>
          <?php else: ?>
            <?php foreach ($transaksi as $t): ?>
              <tr>
                <td><?= htmlspecialchars($t['no_transaksi']) ?></td>
                <td><?= formatTanggalWaktu($t['tanggal_transaksi']) ?></td>
                <td><?= htmlspecialchars($t['nama_pembeli'] ?: '-') ?></td>
                <td><?= htmlspecialchars($t['metode_pembayaran'] ?: '-') ?></td>
                <td class="text-end"><?= formatRupiah($t['total_harga']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <?php if (!empty($transaksi)): ?>
        <tfoot>
          <tr><th colspan="4" class="text-end">Total Periode</th><th class="text-end"><?= formatRupiah($totalPeriode) ?></th></tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>
</div>

<div class="card card-stat">
  <div class="card-body">
    <h6 class="mb-3">Ringkasan Penjualan per Buku</h6>
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead class="table-light">
          <tr><th>Kode</th><th>Judul Buku</th><th class="text-end">Jumlah Terjual</th><th class="text-end">Total</th></tr>
        </thead>
        <tbody>
          <?php if (empty($ringkasanBuku)): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada data.</td></tr>
          <?php else: ?>
            <?php foreach ($ringkasanBuku as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['kode_buku']) ?></td>
                <td><?= htmlspecialchars($r['judul']) ?></td>
                <td class="text-end"><?= (int) $r['total_jumlah'] ?></td>
                <td class="text-end"><?= formatRupiah($r['total_subtotal']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
