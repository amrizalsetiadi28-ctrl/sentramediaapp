<?php
/**
 * Jalankan file ini SEKALI lewat browser setelah mengimpor database.sql,
 * untuk membuat akun admin default dengan password yang sudah di-hash
 * dengan aman (password_hash bawaan PHP).
 *
 * PENTING: Hapus file ini dari server setelah admin berhasil dibuat.
 */

require_once __DIR__ . '/includes/bootstrap.php';

$pdo = getKoneksi();
$stmt = $pdo->query('SELECT COUNT(*) FROM admin');
$jumlahAdmin = (int) $stmt->fetchColumn();

$pesan = '';
$sukses = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $jumlahAdmin === 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $insert = $pdo->prepare(
        'INSERT INTO admin (nama_lengkap, username, password) VALUES (?, ?, ?)'
    );
    $insert->execute(['Administrator', 'admin', $hash]);
    $sukses = true;
    $jumlahAdmin = 1;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup Admin Awal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:520px;">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h5 class="mb-3">Setup Akun Admin Awal</h5>

      <?php if ($sukses): ?>
        <div class="alert alert-success">
          Akun admin berhasil dibuat.<br>
          Username: <strong>admin</strong><br>
          Password: <strong>admin123</strong>
        </div>
        <p class="text-danger small">
          Demi keamanan, segera <strong>hapus file setup_admin.php ini</strong> dari server
          dan ganti password default setelah login pertama kali.
        </p>
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-success w-100">Ke Halaman Login</a>

      <?php elseif ($jumlahAdmin > 0): ?>
        <div class="alert alert-warning">
          Akun admin sudah ada di database, sehingga setup ini tidak dijalankan lagi
          (untuk mencegah duplikasi). Silakan login seperti biasa.
        </div>
        <p class="text-danger small">
          Jangan lupa hapus file <code>setup_admin.php</code> ini dari server.
        </p>
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-success w-100">Ke Halaman Login</a>

      <?php else: ?>
        <p>
          Belum ada akun admin di database. Klik tombol di bawah untuk membuat
          akun admin default (username: <strong>admin</strong>, password: <strong>admin123</strong>).
        </p>
        <form method="post">
          <button type="submit" class="btn btn-success w-100">Buat Akun Admin Default</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
