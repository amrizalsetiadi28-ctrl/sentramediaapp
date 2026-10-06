<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = getKoneksi();
$id = (int) ($_GET['id'] ?? 0);

try {
    $stmt = $pdo->prepare('DELETE FROM buku WHERE id_buku = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        setPesan('sukses', 'Data buku berhasil dihapus.');
    } else {
        setPesan('error', 'Data buku tidak ditemukan.');
    }
} catch (PDOException $e) {
    // Error 1451: tidak bisa hapus karena masih dipakai di tabel lain (FK constraint)
    setPesan('error', 'Buku tidak dapat dihapus karena sudah memiliki riwayat stok masuk atau penjualan.');
}

header('Location: index.php');
exit;
