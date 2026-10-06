<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = getKoneksi();

$stokTerkini = $pdo->query(
    'SELECT kode_buku, judul, stok, stok_minimum FROM buku ORDER BY judul ASC'
)->fetchAll();

$tanggalMulai = trim($_GET['dari'] ?? date('Y-m-01'));
$tanggalAkhir = trim($_GET['sampai'] ?? date('Y-m-d'));

if (!strtotime($tanggalMulai)) {
    $tanggalMulai = date('Y-m-01');
}
if (!strtotime($tanggalAkhir)) {
    $tanggalAkhir = date('Y-m-d');
}
if ($tanggalMulai > $tanggalAkhir) {
    [$tanggalMulai, $tanggalAkhir] = [$tanggalAkhir, $tanggalMulai];
}

$stmt = $pdo->prepare(
    "SELECT sm.tanggal_masuk, sm.no_faktur, s.nama_supplier,
            SUM(d.jumlah) AS total_jumlah, SUM(d.jumlah * d.harga_beli) AS total_nilai
     FROM stok_masuk sm
     JOIN supplier s ON s.id_supplier = sm.id_supplier
     JOIN detail_stok_masuk d ON d.id_stok_masuk = sm.id_stok_masuk
     WHERE sm.tanggal_masuk BETWEEN ? AND ?
     GROUP BY sm.id_stok_masuk, sm.tanggal_masuk, sm.no_faktur, s.nama_supplier
     ORDER BY sm.tanggal_masuk ASC"
);
$stmt->execute([$tanggalMulai, $tanggalAkhir]);
$riwayatMasuk = $stmt->fetchAll();

$judulHalaman = 'Laporan Persediaan';
$activeMenu = 'laporan';
require __DIR__ . '/../includes/header.php';
?>

<ul class="nav nav-pills mb-3 no-print">
  <li class="nav-item"><a class="nav-link" href="penjualan.php">Laporan Penjualan</a></li>
  <li class="nav-item"><a class="nav-link active" href="persediaan.php">Laporan Persediaan</a></li>
</ul>

<div class="card card-stat mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="mb-0">Stok Terkini</h6>
      <button type="button" onclick="window.print()" class="btn btn-outline-primary btn-sm no-print">
        <i class="bi bi-printer"></i> Cetak Laporan
      </button>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead class="table-light">
          <tr><th>Kode</th><th>Judul Buku</th><th class="text-end">Stok</th><th class="text-end">Stok Minimum</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if (empty($stokTerkini)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data buku.</td></tr>
          <?php else: ?>
            <?php foreach ($stokTerkini as $b): ?>
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
                <td class="text-end"><?= (int) $b['stok'] ?></td>
                <td class="text-end"><?= (int) $b['stok_minimum'] ?></td>
                <td><?= $badge ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card card-stat">
  <div class="card-body">
    <h6 class="mb-3">Riwayat Stok Masuk</h6>
    <form method="get" class="row g-3 align-items-end mb-3 no-print">
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
    </form>
    <p class="text-muted small">Periode: <?= formatTanggal($tanggalMulai) ?> s.d. <?= formatTanggal($tanggalAkhir) ?></p>

    <div class="table-responsive">
      <table class="table table-bordered">
        <thead class="table-light">
          <tr><th>Tanggal</th><th>No. Faktur</th><th>Supplier</th><th class="text-end">Jumlah Eksemplar</th><th class="text-end">Nilai</th></tr>
        </thead>
        <tbody>
          <?php if (empty($riwayatMasuk)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada stok masuk pada periode ini.</td></tr>
          <?php else: ?>
            <?php foreach ($riwayatMasuk as $r): ?>
              <tr>
                <td><?= formatTanggal($r['tanggal_masuk']) ?></td>
                <td><?= htmlspecialchars($r['no_faktur'] ?: '-') ?></td>
                <td><?= htmlspecialchars($r['nama_supplier']) ?></td>
                <td class="text-end"><?= (int) $r['total_jumlah'] ?></td>
                <td class="text-end"><?= formatRupiah($r['total_nilai']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
