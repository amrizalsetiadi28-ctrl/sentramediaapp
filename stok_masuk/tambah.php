<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = getKoneksi();
$error = [];

$daftarSupplier = $pdo->query('SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier')->fetchAll();
$daftarBuku = $pdo->query('SELECT id_buku, kode_buku, judul FROM buku ORDER BY judul')->fetchAll();

$form = [
    'tanggal_masuk' => date('Y-m-d'),
    'no_faktur' => '',
    'id_supplier' => '',
    'keterangan' => '',
];

if (empty($daftarSupplier)) {
    $error[] = 'Belum ada data supplier. Tambahkan supplier terlebih dahulu sebelum mencatat stok masuk.';
}
if (empty($daftarBuku)) {
    $error[] = 'Belum ada data buku. Tambahkan data buku terlebih dahulu sebelum mencatat stok masuk.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $form['tanggal_masuk'] = trim($_POST['tanggal_masuk'] ?? '');
    $form['no_faktur'] = trim($_POST['no_faktur'] ?? '');
    $form['id_supplier'] = (int) ($_POST['id_supplier'] ?? 0);
    $form['keterangan'] = trim($_POST['keterangan'] ?? '');

    $idBukuArr = $_POST['id_buku'] ?? [];
    $jumlahArr = $_POST['jumlah'] ?? [];
    $hargaArr = $_POST['harga_beli'] ?? [];

    if ($form['tanggal_masuk'] === '' || !strtotime($form['tanggal_masuk'])) {
        $error[] = 'Tanggal masuk wajib diisi dengan format yang benar.';
    }
    if ($form['id_supplier'] <= 0) {
        $error[] = 'Supplier wajib dipilih.';
    }

    $baris = [];
    foreach ($idBukuArr as $i => $idBuku) {
        $idBuku = (int) $idBuku;
        $jumlah = isset($jumlahArr[$i]) ? (int) $jumlahArr[$i] : 0;
        $harga = isset($hargaArr[$i]) ? $hargaArr[$i] : '';

        if ($idBuku <= 0) {
            continue;
        }
        if ($jumlah <= 0) {
            $error[] = 'Jumlah pada baris ke-' . ($i + 1) . ' harus lebih dari 0.';
            continue;
        }
        if ($harga === '' || !is_numeric($harga) || (float) $harga < 0) {
            $error[] = 'Harga beli pada baris ke-' . ($i + 1) . ' tidak valid.';
            continue;
        }
        $baris[] = ['id_buku' => $idBuku, 'jumlah' => $jumlah, 'harga_beli' => (float) $harga];
    }

    if (empty($baris)) {
        $error[] = 'Minimal harus ada satu buku yang dicatat dalam stok masuk ini.';
    }

    if (empty($error)) {
        try {
            $pdo->beginTransaction();

            $stmtHeader = $pdo->prepare(
                'INSERT INTO stok_masuk (no_faktur, tanggal_masuk, id_supplier, id_admin, keterangan)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmtHeader->execute([
                $form['no_faktur'] !== '' ? $form['no_faktur'] : null,
                $form['tanggal_masuk'],
                $form['id_supplier'],
                $_SESSION['id_admin'],
                $form['keterangan'] !== '' ? $form['keterangan'] : null,
            ]);
            $idStokMasuk = (int) $pdo->lastInsertId();

            $stmtDetail = $pdo->prepare(
                'INSERT INTO detail_stok_masuk (id_stok_masuk, id_buku, jumlah, harga_beli) VALUES (?, ?, ?, ?)'
            );
            $stmtUpdateStok = $pdo->prepare('UPDATE buku SET stok = stok + ? WHERE id_buku = ?');

            foreach ($baris as $b) {
                $stmtDetail->execute([$idStokMasuk, $b['id_buku'], $b['jumlah'], $b['harga_beli']]);
                $stmtUpdateStok->execute([$b['jumlah'], $b['id_buku']]);
            }

            $pdo->commit();
            setPesan('sukses', 'Stok masuk berhasil dicatat dan stok buku sudah diperbarui.');
            header('Location: detail.php?id=' . $idStokMasuk);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error[] = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
        }
    }
}

$judulHalaman = 'Catat Stok Masuk';
$activeMenu = 'stok_masuk';
require __DIR__ . '/../includes/header.php';
?>

<div class="card card-stat">
  <div class="card-body">
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($error as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <?php if (!empty($daftarSupplier) && !empty($daftarBuku)): ?>
    <form method="post" class="auto-focus" id="formStokMasuk">
      <div class="row g-3 mb-3">
        <div class="col-md-3">
          <label class="form-label">Tanggal Masuk <span class="text-danger">*</span></label>
          <input type="date" name="tanggal_masuk" class="form-control" required
                 value="<?= htmlspecialchars($form['tanggal_masuk']) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">No. Faktur</label>
          <input type="text" name="no_faktur" class="form-control" maxlength="30"
                 value="<?= htmlspecialchars($form['no_faktur']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Supplier <span class="text-danger">*</span></label>
          <select name="id_supplier" class="form-select" required>
            <option value="">-- Pilih Supplier --</option>
            <?php foreach ($daftarSupplier as $s): ?>
              <option value="<?= (int) $s['id_supplier'] ?>" <?= (int) $form['id_supplier'] === (int) $s['id_supplier'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['nama_supplier']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Keterangan</label>
          <textarea name="keterangan" class="form-control" rows="2"><?= htmlspecialchars($form['keterangan']) ?></textarea>
        </div>
      </div>

      <hr>
      <h6 class="mb-3">Daftar Buku yang Masuk</h6>
      <div class="table-responsive">
        <table class="table table-bordered align-middle" id="tabelDetail">
          <thead class="table-light">
            <tr>
              <th style="min-width:260px;">Buku</th>
              <th style="width:120px;">Jumlah</th>
              <th style="width:180px;">Harga Beli / Buku</th>
              <th style="width:160px;" class="text-end">Subtotal</th>
              <th style="width:60px;"></th>
            </tr>
          </thead>
          <tbody id="bodyDetail"></tbody>
          <tfoot>
            <tr>
              <td colspan="3" class="text-end fw-bold">Total</td>
              <td class="text-end fw-bold" id="totalKeseluruhan">Rp 0</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <button type="button" class="btn btn-outline-secondary btn-sm" id="btnTambahBaris">
        <i class="bi bi-plus-lg"></i> Tambah Baris
      </button>

      <div class="mt-4">
        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Stok Masuk</button>
        <a href="index.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>

    <template id="templateBaris">
      <tr class="baris-detail">
        <td>
          <select name="id_buku[]" class="form-select select-buku" required>
            <option value="">-- Pilih Buku --</option>
            <?php foreach ($daftarBuku as $b): ?>
              <option value="<?= (int) $b['id_buku'] ?>"><?= htmlspecialchars($b['kode_buku'] . ' - ' . $b['judul']) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><input type="number" name="jumlah[]" class="form-control input-jumlah" min="1" step="1" required></td>
        <td><input type="number" name="harga_beli[]" class="form-control input-harga" min="0" step="0.01" required></td>
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

      function hitungUlangTotal() {
        let total = 0;
        document.querySelectorAll('#bodyDetail tr.baris-detail').forEach(function (baris) {
          const jumlah = parseFloat(baris.querySelector('.input-jumlah').value) || 0;
          const harga = parseFloat(baris.querySelector('.input-harga').value) || 0;
          const subtotal = jumlah * harga;
          baris.querySelector('.subtotal-baris').textContent = 'Rp ' + formatAngka(subtotal);
          total += subtotal;
        });
        totalEl.textContent = 'Rp ' + formatAngka(total);
      }

      function tambahBaris() {
        const clone = template.content.cloneNode(true);
        const tr = clone.querySelector('tr');
        tr.querySelector('.input-jumlah').addEventListener('input', hitungUlangTotal);
        tr.querySelector('.input-harga').addEventListener('input', hitungUlangTotal);
        tr.querySelector('.btn-hapus-baris').addEventListener('click', function () {
          tr.remove();
          hitungUlangTotal();
        });
        bodyDetail.appendChild(clone);
      }

      document.getElementById('btnTambahBaris').addEventListener('click', tambahBaris);
      document.getElementById('formStokMasuk').addEventListener('submit', function (e) {
        if (bodyDetail.querySelectorAll('tr.baris-detail').length === 0) {
          e.preventDefault();
          alert('Tambahkan minimal satu baris buku terlebih dahulu.');
        }
      });

      // Mulai dengan satu baris kosong
      tambahBaris();
    </script>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
