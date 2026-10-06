<?php
/**
 * Wajib di-include PALING ATAS pada setiap halaman yang hanya boleh
 * diakses oleh admin yang sudah login (KF-01).
 */

require_once __DIR__ . '/bootstrap.php';

if (empty($_SESSION['id_admin'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}
