<?php
if (session_status() === PHP_SESSION_NONE) session_start();

/* Calcula base de /public aunque estés dentro de /public/admin */
$SCRIPT_DIR  = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$PUBLIC_BASE = preg_replace('#/admin$#', '', $SCRIPT_DIR);

/* Helper para rutas relativas a /public */
function public_url(string $path): string {
  global $PUBLIC_BASE;
  return $PUBLIC_BASE . '/' . ltrim($path, '/');
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>ISMERSA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 (CSS) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <!-- Font Awesome (íconos) -->
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-8lWbC6VmB4V+n1mDgWw9K+KfFkY2/NVYbM53YgYudXPtXvUK+VpBNNYhQck1K6QdQnq3MXLm38vP8ncr/yjR3g=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer" />

  <style>
    /* Hover de iconos sociales (se usa en el footer) */
    .social-link { color:#aaa; transition: color .3s ease, transform .2s ease; }
    .social-link:hover { transform: scale(1.08); }
    .social-link.instagram:hover { color:#E4405F; } /* Instagram */
    .social-link.facebook:hover  { color:#1877F2; } /* Facebook */
  </style>
</head>

<!-- min-vh-100 + d-flex => sticky footer; bg-light = fondo claro -->
<body class="d-flex flex-column min-vh-100 bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="<?= public_url('index.php') ?>">ISMERSA</a>

    <div class="d-flex">
      <?php if(!empty($_SESSION['is_admin'])): ?>
        <a class="btn btn-sm btn-outline-light me-2" href="<?= public_url('admin/index.php') ?>">Dashboard</a>
        <a class="btn btn-sm btn-danger" href="<?= public_url('logout.php') ?>">Salir</a>
      <?php else: ?>
        <!-- Botón “camuflado” para login (mismo color que navbar) -->
        <a class="btn btn-sm"
           style="background-color:#212529;border:none;color:#212529;"
           href="<?= public_url('login.php') ?>">Admin</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- La página va aquí; flex-grow-1 para empujar el footer abajo -->
<main class="container flex-grow-1 py-4">
