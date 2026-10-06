<?php
require_once __DIR__ . '/includes/auth.php';

$pdo = getKoneksi();

// --- Statistik ringkas ---
$totalBuku = (int) $pdo->query('SELECT COUNT(*) FROM buku')->fetchColumn();
$totalStok = (int) $pdo->query('SELECT COALESCE(SUM(stok),0) FROM buku')->fetchColumn();

$stmtHariIni = $pdo->prepare(
    "SELECT COUNT(*) AS jumlah_transaksi, COALESCE(SUM(total_harga),0) AS total
     FROM penjualan WHERE DATE(tanggal_transaksi) = CURDATE()"
);
$stmtHariIni->execute();
$penjualanHariIni = $stmtHariIni->fetch();

$stmtBulanIni = $pdo->prepare(
    "SELECT COALESCE(SUM(total_harga),0) AS total
     FROM penjualan WHERE YEAR(tanggal_transaksi) = YEAR(CURDATE())
       AND MONTH(tanggal_transaksi) = MONTH(CURDATE())"
);
$stmtBulanIni->execute();
$penjualanBulanIni = $stmtBulanIni->fetchColumn();

// --- Buku yang perlu dipesan ulang (KF-06) ---
$bukuPesanUlang = $pdo->query(
    "SELECT kode_buku, judul, stok, stok_minimum FROM buku
     WHERE stok <= stok_minimum ORDER BY stok ASC LIMIT 10"
)->fetchAll();

$jumlahPesanUlang = (int) $pdo->query(
    'SELECT COUNT(*) FROM buku WHERE stok <= stok_minimum'
)->fetchColumn();

// --- Buku terlaris (KF-08) ---
$bukuTerlaris = $pdo->query(
    "SELECT b.judul, SUM(dp.jumlah) AS total_terjual
     FROM detail_penjualan dp
     JOIN buku b ON b.id_buku = dp.id_buku
     GROUP BY dp.id_buku, b.judul
     ORDER BY total_terjual DESC
     LIMIT 5"
)->fetchAll();

// --- Perkembangan penjualan 7 hari terakhir (KF-08) ---
$labelHari = [];
$dataHari = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i day"));
    $labelHari[] = date('d/m', strtotime($tgl));

    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(total_harga),0) FROM penjualan WHERE DATE(tanggal_transaksi) = ?'
    );
    $stmt->execute([$tgl]);
    $dataHari[] = (float) $stmt->fetchColumn();
}

$judulHalaman = 'Dashboard';
$activeMenu = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-md-3 col-sm-6">
    <div class="card card-stat">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="icon-box" style="background:#2f5233;"><i class="bi bi-journal-bookmark"></i></div>
        <div>
          <div class="text-muted small">Total Judul Buku</div>
          <div class="fs-5 fw-bold"><?= number_format($totalBuku) ?></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="card card-stat">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="icon-box" style="background:#0d6efd;"><i class="bi bi-boxes"></i></div>
        <div>
          <div class="text-muted small">Total Stok (eksemplar)</div>
          <div class="fs-5 fw-bold"><?= number_format($totalStok) ?></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="card card-stat">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="icon-box" style="background:#c9a24b;"><i class="bi bi-cash-coin"></i></div>
        <div>
          <div class="text-muted small">Penjualan Hari Ini</div>
          <div class="fs-6 fw-bold"><?= formatRupiah($penjualanHariIni['total']) ?></div>
          <div class="text-muted small"><?= (int) $penjualanHariIni['jumlah_transaksi'] ?> transaksi</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="card card-stat">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="icon-box" style="background:#dc3545;"><i class="bi bi-exclamation-triangle"></i></div>
        <div>
          <div class="text-muted small">Perlu Dipesan Ulang</div>
          <div class="fs-5 fw-bold"><?= number_format($jumlahPesanUlang) ?> judul</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card card-stat h-100">
      <div class="card-body">
        <h6 class="mb-3">Perkembangan Penjualan (7 Hari Terakhir)</h6>
        <canvas id="chartPenjualan" height="140"></canvas>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card card-stat h-100">
      <div class="card-body">
        <h6 class="mb-3">Buku Terlaris</h6>
        <?php if (empty($bukuTerlaris)): ?>
          <p class="text-muted small mb-0">Belum ada data penjualan.</p>
        <?php else: ?>
          <ol class="mb-0 ps-3">
            <?php foreach ($bukuTerlaris as $b): ?>
              <li class="mb-2">
                <?= htmlspecialchars($b['judul']) ?>
                <span class="badge bg-secondary"><?= (int) $b['total_terjual'] ?> terjual</span>
              </li>
            <?php endforeach; ?>
          </ol>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-12">
    <div class="card card-stat">
      <div class="card-body">
        <h6 class="mb-3"><i class="bi bi-exclamation-triangle text-danger"></i> Buku Perlu Dipesan Ulang</h6>
        <?php if (empty($bukuPesanUlang)): ?>
          <p class="text-muted small mb-0">Tidak ada buku yang stoknya mencapai batas minimum.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Kode</th><th>Judul</th><th class="text-end">Stok</th><th class="text-end">Stok Minimum</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($bukuPesanUlang as $b): ?>
                  <tr>
                    <td><?= htmlspecialchars($b['kode_buku']) ?></td>
                    <td><?= htmlspecialchars($b['judul']) ?></td>
                    <td class="text-end"><?= (int) $b['stok'] ?></td>
                    <td class="text-end"><?= (int) $b['stok_minimum'] ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('chartPenjualan');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?= json_encode($labelHari) ?>,
    datasets: [{
      label: 'Total Penjualan (Rp)',
      data: <?= json_encode($dataHari) ?>,
      borderColor: '#2f5233',
      backgroundColor: 'rgba(47,82,51,0.12)',
      tension: 0.3,
      fill: true,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: {
        ticks: {
          callback: function(value) { return 'Rp ' + value.toLocaleString('id-ID'); }
        }
      }
    }
  }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
