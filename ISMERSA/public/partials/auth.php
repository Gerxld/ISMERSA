<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function require_admin(): void {
  if (empty($_SESSION['is_admin'])) {
    // ✅ Ruta relativa
    header('Location: ../login.php');
    exit;
  }
}
