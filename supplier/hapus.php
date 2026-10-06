<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = getKoneksi();
$id = (int) ($_GET['id'] ?? 0);

try {
    $stmt = $pdo->prepare('DELETE FROM supplier WHERE id_supplier = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        setPesan('sukses', 'Data supplier berhasil dihapus.');
    } else {
        setPesan('error', 'Data supplier tidak ditemukan.');
    }
} catch (PDOException $e) {
    setPesan('error', 'Supplier tidak dapat dihapus karena masih memiliki riwayat stok masuk.');
}

header('Location: index.php');
exit;
