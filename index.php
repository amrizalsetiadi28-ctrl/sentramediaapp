<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!empty($_SESSION['id_admin'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
