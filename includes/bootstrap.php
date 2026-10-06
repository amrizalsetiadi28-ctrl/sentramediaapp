<?php
/**
 * File ini di-include di SETIAP halaman (baik yang butuh login maupun
 * tidak, seperti login.php). Isinya: mulai session, koneksi database,
 * fungsi bantu, dan penentuan BASE_URL secara otomatis supaya seluruh
 * link/redirect tetap benar walaupun nama folder proyek diubah oleh
 * pengguna saat disalin ke htdocs.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (!defined('BASE_URL')) {
    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $currentDir  = str_replace('\\', '/', realpath(dirname($_SERVER['SCRIPT_FILENAME'])));

    if ($currentDir === $projectRoot || $projectRoot === false || $currentDir === false) {
        define('BASE_URL', '.');
    } else {
        $relative = trim(str_replace($projectRoot, '', $currentDir), '/');
        $depth = $relative === '' ? 0 : (substr_count($relative, '/') + 1);
        define('BASE_URL', $depth > 0 ? rtrim(str_repeat('../', $depth), '/') : '.');
    }
}
