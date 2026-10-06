<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = getKoneksi();
$error = [];

$daftarBuku = $pdo->query(
    'SELECT id_buku, kode_buku, judul, harga_jual, stok FROM buku ORDER BY judul'
)->fetchAll();

$form = [
    'nama_pembeli' => '',
    'metode_pembayaran' => 'Tunai',
];

if (empty($daftarBuku)) {
    $error[] = 'Belum ada data buku. Tambahkan data buku terlebih dahulu sebelum mencatat penjualan.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $form['nama_pembeli'] = trim($_POST['nama_pembeli'] ?? '');
    $form['metode_pembayaran'] = trim($_POST['metode_pembayaran'] ?? '');

    $idBukuArr = $_POST['id_buku'] ?? [];
    $jumlahArr = $_POST['jumlah'] ?? [];

    if ($form['nama_pembeli'] === '') {
        $error[] = 'Nama pembeli wajib diisi.';
    }
    if ($form['metode_pembayaran'] === '') {
        $error[] = 'Metode pembayaran wajib dipilih.';
    }

    // Gabungkan jumlah per buku (jika buku yang sama dipilih di lebih dari satu baris)
    $permintaan = [];
    foreach ($idBukuArr as $i => $idBuku) {
        $idBuku = (int) $idBuku;
        $jumlah = isset($jumlahArr[$i]) ? (int) $jumlahArr[$i] : 0;

        if ($idBuku <= 0) {
            continue;
        }
        if ($jumlah <= 0) {
            $error[] = 'Jumlah pada baris ke-' . ($i + 1) . ' harus lebih dari 0.';
            continue;
        }
        $permintaan[$idBuku] = ($permintaan[$idBuku] ?? 0) + $jumlah;
    }

    if (empty($permintaan)) {
        $error[] = 'Minimal harus ada satu buku yang dijual.';
    }

    if (empty($error)) {
        try {
            $pdo->beginTransaction();

            // Kunci baris buku yang terlibat agar stok tidak berubah oleh transaksi lain
            // di saat yang bersamaan, lalu periksa ketersediaannya (harga diambil dari
            // basis data saat ini, bukan dari input pengguna, agar tidak bisa dimanipulasi).
            $stmtCek = $pdo->prepare('SELECT * FROM buku WHERE id_buku = ? FOR UPDATE');
            $bukuTerpilih = [];
            foreach ($permintaan as $idBuku => $jumlahDiminta) {
                $stmtCek->execute([$idBuku]);
                $b = $stmtCek->fetch();
                if (!$b) {
                    throw new RuntimeException('Salah satu buku yang dipilih tidak ditemukan.');
                }
                if ($jumlahDiminta > (int) $b['stok']) {
                    throw new RuntimeException(
                        'Stok buku "' . $b['judul'] . '" tidak mencukupi. Sisa stok: ' . (int) $b['stok']
                        . ', diminta: ' . $jumlahDiminta . '.'
                    );
                }
                $bukuTerpilih[$idBuku] = $b;
            }

            $totalHarga = 0;
            foreach ($permintaan as $idBuku => $jumlahDiminta) {
                $totalHarga += $jumlahDiminta * (float) $bukuTerpilih[$idBuku]['harga_jual'];
            }

            $stmtHeader = $pdo->prepare(
                'INSERT INTO penjualan (no_transaksi, tanggal_transaksi, nama_pembeli, metode_pembayaran, total_harga, id_admin)
                 VALUES (?, NOW(), ?, ?, ?, ?)'
            );

            // Nomor transaksi dibuat otomatis. Diberi mekanisme coba ulang untuk
            // menghindari kemungkinan (sangat kecil) dua transaksi mendapat nomor
            // yang sama apabila disimpan pada saat yang nyaris bersamaan.
            $percobaan = 0;
            while (true) {
                $percobaan++;
                $noTransaksi = buatNomor($pdo, 'penjualan', 'no_transaksi', 'TRX');
                try {
                    $stmtHeader->execute([
                        $noTransaksi,
                        $form['nama_pembeli'],
                        $form['metode_pembayaran'],
                        $totalHarga,
                        $_SESSION['id_admin'],
                    ]);
                    break;
                } catch (PDOException $exDup) {
                    if ($exDup->getCode() === '23000' && $percobaan < 3) {
                        continue;
                    }
                    throw $exDup;
                }
            }
            $idPenjualan = (int) $pdo->lastInsertId();

            $stmtDetail = $pdo->prepare(
                'INSERT INTO detail_penjualan (id_penjualan, id_buku, jumlah, harga_satuan, subtotal)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmtUpdateStok = $pdo->prepare('UPDATE buku SET stok = stok - ? WHERE id_buku = ?');

            foreach ($permintaan as $idBuku => $jumlahDiminta) {
                $hargaSatuan = (float) $bukuTerpilih[$idBuku]['harga_jual'];
                $subtotal = $jumlahDiminta * $hargaSatuan;
                $stmtDetail->execute([$idPenjualan, $idBuku, $jumlahDiminta, $hargaSatuan, $subtotal]);
                $stmtUpdateStok->execute([$jumlahDiminta, $idBuku]);
            }

            $pdo->commit();
            setPesan('sukses', 'Transaksi penjualan berhasil disimpan dan stok sudah diperbarui.');
            header('Location: detail.php?id=' . $idPenjualan);
            exit;
        } catch (RuntimeException $e) {
            $pdo->rollBack();
            $error[] = $e->getMessage();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error[] = 'Terjadi kesalahan saat menyimpan transaksi. Silakan coba lagi.';
        }
    }
}

$judulHalaman = 'Catat Penjualan';
$activeMenu = 'penjualan';
require __DIR__ . '/../includes/header.php';
?>

<div class="card card-stat">
  <div class="card-body">
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($error as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <?php if (!empty($daftarBuku)): ?>
    <form method="post" class="auto-focus" id="formPenjualan">
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label">Nama Pembeli <span class="text-danger">*</span></label>
          <input type="text" name="nama_pembeli" class="form-control" required maxlength="100"
                 value="<?= htmlspecialchars($form['nama_pembeli']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
          <select name="metode_pembayaran" class="form-select" required>
            <?php foreach (['Tunai', 'Transfer', 'Lainnya'] as $m): ?>
              <option value="<?= $m ?>" <?= $form['metode_pembayaran'] === $m ? 'selected' : '' ?>><?= $m ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <hr>
      <h6 class="mb-3">Daftar Buku yang Terjual</h6>
      <div class="table-responsive">
        <table class="table table-bordered align-middle" id="tabelDetail">
          <thead class="table-light">
            <tr>
              <th style="min-width:260px;">Buku</th>
              <th style="width:110px;">Stok</th>
              <th style="width:120px;">Jumlah</th>
              <th style="width:160px;">Harga Satuan</th>
              <th style="width:160px;" class="text-end">Subtotal</th>
              <th style="width:60px;"></th>
            </tr>
          </thead>
          <tbody id="bodyDetail"></tbody>
          <tfoot>
            <tr>
              <td colspan="4" class="text-end fw-bold">Total</td>
              <td class="text-end fw-bold" id="totalKeseluruhan">Rp 0</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <button type="button" class="btn btn-outline-secondary btn-sm" id="btnTambahBaris">
        <i class="bi bi-plus-lg"></i> Tambah Baris
      </button>
      <p class="text-muted small mt-2 mb-0">
        Harga dan total akan dihitung ulang oleh sistem berdasarkan data terkini saat transaksi disimpan.
        Transaksi otomatis ditolak apabila jumlah yang diminta melebihi stok yang tersedia.
      </p>

      <div class="mt-4">
        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Penjualan</button>
        <a href="index.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>

    <template id="templateBaris">
      <tr class="baris-detail">
        <td>
          <select name="id_buku[]" class="form-select select-buku" required>
            <option value="">-- Pilih Buku --</option>
            <?php foreach ($daftarBuku as $b): ?>
              <option value="<?= (int) $b['id_buku'] ?>"
                      data-harga="<?= (float) $b['harga_jual'] ?>"
                      data-stok="<?= (int) $b['stok'] ?>">
                <?= htmlspecialchars($b['kode_buku'] . ' - ' . $b['judul']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </td>
        <td class="text-end stok-baris">-</td>
        <td><input type="number" name="jumlah[]" class="form-control input-jumlah" min="1" step="1" required></td>
        <td class="text-end harga-baris">Rp 0</td>
        <td class="text-end subtotal-baris">Rp 0</td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-baris"><i class="bi bi-x-lg"></i></button>
        </td>
      </tr>
    </template>

    <script>
      const bodyDetail = document.getElementById('bodyDetail');
      const template = document.getElementById('templateBaris');
      const totalEl = document.getElementById('totalKeseluruhan');

      function hitungBaris(tr) {
        const select = tr.querySelector('.select-buku');
        const opt = select.options[select.selectedIndex];
        const harga = opt && opt.value ? parseFloat(opt.dataset.harga) : 0;
        const stok = opt && opt.value ? parseInt(opt.dataset.stok, 10) : null;
        const jumlahInput = tr.querySelector('.input-jumlah');
        const jumlah = parseFloat(jumlahInput.value) || 0;

        tr.querySelector('.harga-baris').textContent = 'Rp ' + formatAngka(harga);
        tr.querySelector('.stok-baris').textContent = stok === null ? '-' : stok;
        tr.querySelector('.subtotal-baris').textContent = 'Rp ' + formatAngka(harga * jumlah);

        if (stok !== null && jumlah > stok) {
          jumlahInput.classList.add('is-invalid');
        } else {
          jumlahInput.classList.remove('is-invalid');
        }
      }

      function hitungUlangTotal() {
        let total = 0;
        document.querySelectorAll('#bodyDetail tr.baris-detail').forEach(function (tr) {
          hitungBaris(tr);
          const select = tr.querySelector('.select-buku');
          const opt = select.options[select.selectedIndex];
          const harga = opt && opt.value ? parseFloat(opt.dataset.harga) : 0;
          const jumlah = parseFloat(tr.querySelector('.input-jumlah').value) || 0;
          total += harga * jumlah;
        });
        totalEl.textContent = 'Rp ' + formatAngka(total);
      }

      function tambahBaris() {
        const clone = template.content.cloneNode(true);
        const tr = clone.querySelector('tr');
        tr.querySelector('.select-buku').addEventListener('change', hitungUlangTotal);
        tr.querySelector('.input-jumlah').addEventListener('input', hitungUlangTotal);
        tr.querySelector('.btn-hapus-baris').addEventListener('click', function () {
          tr.remove();
          hitungUlangTotal();
        });
        bodyDetail.appendChild(clone);
      }

      document.getElementById('btnTambahBaris').addEventListener('click', tambahBaris);
      document.getElementById('formPenjualan').addEventListener('submit', function (e) {
        if (bodyDetail.querySelectorAll('tr.baris-detail').length === 0) {
          e.preventDefault();
          alert('Tambahkan minimal satu baris buku terlebih dahulu.');
        }
      });

      tambahBaris();
    </script>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
