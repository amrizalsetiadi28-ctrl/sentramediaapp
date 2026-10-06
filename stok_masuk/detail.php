<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = getKoneksi();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT sm.*, s.nama_supplier, s.alamat, s.no_telepon, a.nama_lengkap AS nama_admin
     FROM stok_masuk sm
     JOIN supplier s ON s.id_supplier = sm.id_supplier
     JOIN admin a ON a.id_admin = sm.id_admin
     WHERE sm.id_stok_masuk = ?"
);
$stmt->execute([$id]);
$header = $stmt->fetch();

if (!$header) {
    setPesan('error', 'Data stok masuk tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$detail = $pdo->prepare(
    "SELECT d.*, b.kode_buku, b.judul
     FROM detail_stok_masuk d
     JOIN buku b ON b.id_buku = d.id_buku
     WHERE d.id_stok_masuk = ?
     ORDER BY d.id_detail_masuk"
);
$detail->execute([$id]);
$baris = $detail->fetchAll();

$totalEksemplar = array_sum(array_column($baris, 'jumlah'));
$totalNilai = array_sum(array_map(fn($b) => $b['jumlah'] * $b['harga_beli'], $baris));

$judulHalaman = 'Detail Stok Masuk';
$activeMenu = 'stok_masuk';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between mb-3 no-print">
  <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
  <button onclick="window.print()" class="btn btn-outline-primary"><i class="bi bi-printer"></i> Cetak</button>
</div>

<div class="card card-stat">
  <div class="card-body">
    <h5 class="mb-3">Detail Stok Masuk</h5>
    <div class="row mb-3">
      <div class="col-md-6">
        <table class="table table-borderless table-sm mb-0">
          <tr><td class="text-muted" style="width:140px;">Tanggal</td><td>: <?= formatTanggal($header['tanggal_masuk']) ?></td></tr>
          <tr><td class="text-muted">No. Faktur</td><td>: <?= htmlspecialchars($header['no_faktur'] ?: '-') ?></td></tr>
          <tr><td class="text-muted">Dicatat Oleh</td><td>: <?= htmlspecialchars($header['nama_admin']) ?></td></tr>
        </table>
      </div>
      <div class="col-md-6">
        <table class="table table-borderless table-sm mb-0">
          <tr><td class="text-muted" style="width:140px;">Supplier</td><td>: <?= htmlspecialchars($header['nama_supplier']) ?></td></tr>
          <tr><td class="text-muted">Alamat</td><td>: <?= htmlspecialchars($header['alamat'] ?: '-') ?></td></tr>
          <tr><td class="text-muted">Telepon</td><td>: <?= htmlspecialchars($header['no_telepon'] ?: '-') ?></td></tr>
        </table>
      </div>
    </div>
    <?php if (!empty($header['keterangan'])): ?>
      <p><span class="text-muted">Keterangan:</span> <?= nl2br(htmlspecialchars($header['keterangan'])) ?></p>
    <?php endif; ?>

    <div class="table-responsive mt-3">
      <table class="table table-bordered">
        <thead class="table-light">
          <tr>
            <th>Kode</th><th>Judul Buku</th><th class="text-end">Jumlah</th>
            <th class="text-end">Harga Beli</th><th class="text-end">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($baris as $b): ?>
            <tr>
              <td><?= htmlspecialchars($b['kode_buku']) ?></td>
              <td><?= htmlspecialchars($b['judul']) ?></td>
              <td class="text-end"><?= (int) $b['jumlah'] ?></td>
              <td class="text-end"><?= formatRupiah($b['harga_beli']) ?></td>
              <td class="text-end"><?= formatRupiah($b['jumlah'] * $b['harga_beli']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="2" class="text-end">Total</th>
            <th class="text-end"><?= (int) $totalEksemplar ?> eks.</th>
            <th></th>
            <th class="text-end"><?= formatRupiah($totalNilai) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
