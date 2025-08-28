<?php
declare(strict_types=1);
require __DIR__ . '/../partials/auth.php';
require_admin();
require __DIR__ . '/../../db/DatabaseManager.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { header('Location: productos.php'); exit; }

$db = new DatabaseManager();
$db->deleteProduct($id);
header('Location: productos.php');
