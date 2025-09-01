<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/controllers/Site/FrontCartController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . public_url('productos.php'));
  exit;
}

try {
  $controller = new FrontCartController();
  $redirect = $controller->addFromPost($_POST);
  header('Location: ' . $redirect);
  exit;
} catch (Throwable $e) {
  header('Location: ' . public_url('productos.php'));
  exit;
}
