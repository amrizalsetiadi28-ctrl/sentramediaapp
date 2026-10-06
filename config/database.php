<?php
/**
 * Koneksi database menggunakan PDO.
 * Sesuaikan konstanta di bawah ini jika konfigurasi MySQL Anda berbeda.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'sentra_media');
define('DB_USER', 'root');
define('DB_PASS', '');

function getKoneksi(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die('Koneksi database gagal. Pastikan MySQL sudah berjalan dan database "sentra_media" '
                . 'sudah diimpor dari database.sql. Detail: ' . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}
