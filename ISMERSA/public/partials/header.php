<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../src/lib/helpers.php';

$cartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
  foreach ($_SESSION['cart'] as $c) {
    $cartCount += (int)$c['qty'];
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>ISMERSA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <!-- Brand -->
    <a class="navbar-brand fw-bold" href="<?= public_url('index.php') ?>">ISMERSA</a>

    <div class="d-flex align-items-center gap-2">
      <!-- Carrito -->
      <a href="<?= public_url('carrito.php') ?>" class="btn btn-sm btn-outline-light position-relative">
        <i class="bi bi-cart"></i>
        <?php if ($cartCount > 0): ?>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?= $cartCount ?>
          </span>
        <?php endif; ?>
      </a>

      <?php if(!empty($_SESSION['is_admin'])): ?>
        <!-- Admin -->
        <a class="btn btn-sm btn-outline-light" href="<?= public_url('admin/index.php') ?>">Dashboard</a>
        <a class="btn btn-sm btn-danger" href="<?= public_url('logout.php') ?>">Salir</a>
      <?php else: ?>
        <!-- Botón login invisible (admin only knows) -->
        <a class="btn btn-sm text-white-50"
           style="background-color:#212529;border:none;"
           href="<?= public_url('login.php') ?>">
           <!-- vacío -->
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<main class="container py-4 flex-grow-1">
