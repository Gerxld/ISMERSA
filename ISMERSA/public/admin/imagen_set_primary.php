<?php
declare(strict_types=1);
require __DIR__ . '/../partials/auth.php';
require_admin();
require __DIR__ . '/../../db/DatabaseManager.php';

$pid = isset($_POST['pid']) ? (int)$_POST['pid'] : 0;
$iid = isset($_POST['iid']) ? (int)$_POST['iid'] : 0;
if ($pid<=0 || $iid<=0) { header('Location: productos.php'); exit; }

$db = new DatabaseManager();
$db->setPrimaryImage($pid, $iid);

header('Location: producto_form.php?edit='.$pid);
