<?php
declare(strict_types=1);
require __DIR__ . '/../partials/auth.php';
require_admin();
require __DIR__ . '/../../db/DatabaseManager.php';

$iid = isset($_POST['iid']) ? (int)$_POST['iid'] : 0;
if ($iid<=0) { header('Location: productos.php'); exit; }

$db = new DatabaseManager();
$db->deleteImage($iid);

// Vuelve a la edición del producto (intentamos inferir pid)
$pid = isset($_POST['pid']) ? (int)$_POST['pid'] : 0;
if (!$pid) {
    // buscar producto dueño
    // truco simple:
    // (si quieres exactitud, agrega hidden pid en el form)
    $pid = (int)($_GET['pid'] ?? 0);
}
$dest = $pid ? 'producto_form.php?edit='.$pid : 'productos.php';
header('Location: '.$dest);
