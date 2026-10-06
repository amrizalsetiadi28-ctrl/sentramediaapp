<?php
/**
 * Header bersama. Sebelum include file ini, halaman boleh mengisi:
 *   $judulHalaman = 'Judul Halaman';
 *   $activeMenu   = 'dashboard' | 'buku' | 'supplier' | 'stok_masuk' | 'penjualan' | 'laporan'
 */
$judulHalaman = $judulHalaman ?? 'Sistem Persediaan & Penjualan Buku';
$activeMenu = $activeMenu ?? '';

function menuAktif(string $nama, string $active): string
{
    return $nama === $active ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($judulHalaman) ?> - CV. Sentra Media</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark sidebar-top d-lg-none">
  <div class="container-fluid">
    <span class="navbar-brand fw-bold"><i class="bi bi-book-half"></i> Sentra Media</span>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuMobile">
      <span class="navbar-toggler-icon"></span>
    </button>
  </div>
</nav>

<div class="d-flex app-wrapper">
  <aside class="sidebar collapse d-lg-block" id="menuMobile">
    <div class="sidebar-brand d-none d-lg-flex">
      <i class="bi bi-book-half"></i> <span>Sentra Media</span>
    </div>
    <ul class="nav flex-column sidebar-nav">
      <li class="nav-item">
        <a class="nav-link <?= menuAktif('dashboard', $activeMenu) ?>" href="<?= BASE_URL ?>/dashboard.php">
          <i class="bi bi-speedometer2"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= menuAktif('buku', $activeMenu) ?>" href="<?= BASE_URL ?>/buku/index.php">
          <i class="bi bi-journal-bookmark"></i> Data Buku
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= menuAktif('supplier', $activeMenu) ?>" href="<?= BASE_URL ?>/supplier/index.php">
          <i class="bi bi-truck"></i> Data Supplier
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= menuAktif('stok_masuk', $activeMenu) ?>" href="<?= BASE_URL ?>/stok_masuk/index.php">
          <i class="bi bi-box-arrow-in-down"></i> Stok Masuk
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= menuAktif('penjualan', $activeMenu) ?>" href="<?= BASE_URL ?>/penjualan/index.php">
          <i class="bi bi-cart-check"></i> Penjualan
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= menuAktif('laporan', $activeMenu) ?>" href="<?= BASE_URL ?>/laporan/penjualan.php">
          <i class="bi bi-file-earmark-bar-graph"></i> Laporan
        </a>
      </li>
      <li class="nav-item mt-3">
        <a class="nav-link text-danger" href="<?= BASE_URL ?>/logout.php" onclick="return confirm('Keluar dari sistem?');">
          <i class="bi bi-box-arrow-right"></i> Logout
        </a>
      </li>
    </ul>
  </aside>

  <main class="main-content flex-grow-1">
    <div class="topbar d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><?= htmlspecialchars($judulHalaman) ?></h5>
      <span class="text-muted small">
        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin') ?>
      </span>
    </div>
    <div class="content-area">
      <?php tampilkanPesan(); ?>
