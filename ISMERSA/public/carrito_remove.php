<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/controllers/Site/FrontCartController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . public_url('carrito.php'));
  exit;
}

try {
  $controller = new FrontCartController();
  $redirect = $controller->removeFromPost($_POST);
  header('Location: ' . $redirect);
  exit;
} catch (Throwable $e) {
  header('Location: ' . public_url('carrito.php'));
  exit;
}
