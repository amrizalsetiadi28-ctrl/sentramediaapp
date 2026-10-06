<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = getKoneksi();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT p.*, a.nama_lengkap AS nama_admin
     FROM penjualan p
     JOIN admin a ON a.id_admin = p.id_admin
     WHERE p.id_penjualan = ?"
);
$stmt->execute([$id]);
$header = $stmt->fetch();

if (!$header) {
    setPesan('error', 'Data penjualan tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$detail = $pdo->prepare(
    "SELECT d.*, b.kode_buku, b.judul
     FROM detail_penjualan d
     JOIN buku b ON b.id_buku = d.id_buku
     WHERE d.id_penjualan = ?
     ORDER BY d.id_detail_jual"
);
$detail->execute([$id]);
$baris = $detail->fetchAll();

$judulHalaman = 'Detail Penjualan';
$activeMenu = 'penjualan';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between mb-3 no-print">
  <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
  <button onclick="window.print()" class="btn btn-outline-primary"><i class="bi bi-printer"></i> Cetak Struk</button>
</div>

<div class="card card-stat">
  <div class="card-body">
    <h5 class="mb-3">Detail Transaksi Penjualan</h5>
    <div class="row mb-3">
      <div class="col-md-6">
        <table class="table table-borderless table-sm mb-0">
          <tr><td class="text-muted" style="width:160px;">No. Transaksi</td><td>: <?= htmlspecialchars($header['no_transaksi']) ?></td></tr>
          <tr><td class="text-muted">Tanggal</td><td>: <?= formatTanggalWaktu($header['tanggal_transaksi']) ?></td></tr>
          <tr><td class="text-muted">Dicatat Oleh</td><td>: <?= htmlspecialchars($header['nama_admin']) ?></td></tr>
        </table>
      </div>
      <div class="col-md-6">
        <table class="table table-borderless table-sm mb-0">
          <tr><td class="text-muted" style="width:160px;">Nama Pembeli</td><td>: <?= htmlspecialchars($header['nama_pembeli'] ?: '-') ?></td></tr>
          <tr><td class="text-muted">Metode Pembayaran</td><td>: <?= htmlspecialchars($header['metode_pembayaran'] ?: '-') ?></td></tr>
        </table>
      </div>
    </div>

    <div class="table-responsive mt-3">
      <table class="table table-bordered">
        <thead class="table-light">
          <tr>
            <th>Kode</th><th>Judul Buku</th><th class="text-end">Jumlah</th>
            <th class="text-end">Harga Satuan</th><th class="text-end">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($baris as $b): ?>
            <tr>
              <td><?= htmlspecialchars($b['kode_buku']) ?></td>
              <td><?= htmlspecialchars($b['judul']) ?></td>
              <td class="text-end"><?= (int) $b['jumlah'] ?></td>
              <td class="text-end"><?= formatRupiah($b['harga_satuan']) ?></td>
              <td class="text-end"><?= formatRupiah($b['subtotal']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="4" class="text-end">Total</th>
            <th class="text-end"><?= formatRupiah($header['total_harga']) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
