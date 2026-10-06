<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!empty($_SESSION['id_admin'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $pdo = getKoneksi();
        $stmt = $pdo->prepare('SELECT * FROM admin WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['id_admin'] = $admin['id_admin'];
            $_SESSION['nama_lengkap'] = $admin['nama_lengkap'];
            $_SESSION['username'] = $admin['username'];
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        }

        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Sistem Persediaan & Penjualan Buku</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-wrapper">
  <div class="card login-card shadow-lg">
    <div class="card-body p-4">
      <div class="text-center mb-4">
        <i class="bi bi-book-half" style="font-size:2.5rem;color:#2f5233;"></i>
        <h5 class="mt-2 mb-0 fw-bold">CV. Sentra Media</h5>
        <small class="text-muted">Sistem Persediaan &amp; Penjualan Buku</small>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" class="auto-focus" autocomplete="off">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success w-100">
          <i class="bi bi-box-arrow-in-right"></i> Masuk
        </button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
