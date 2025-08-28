<?php
declare(strict_types=1);
require __DIR__ . '/../partials/auth.php';
require_admin();
require __DIR__ . '/../../db/DatabaseManager.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$accion = $_POST['accion'] ?? '';

if ($id <= 0 || !in_array($accion, ['publicar','ocultar','archivar'], true)) {
    header('Location: productos.php');
    exit;
}

$db = new DatabaseManager();
switch ($accion) {
    case 'publicar': $db->setProductEstadoByNombre($id, 'publicado'); break;
    case 'ocultar' : $db->setProductEstadoByNombre($id, 'inactivo');  break;
    case 'archivar': $db->setProductEstadoByNombre($id, 'archivado'); break;
}
header('Location: productos.php');
