<?php
declare(strict_types=1);

/** Protege endpoints admin */
function require_admin_guard(): void {
  if (empty($_SESSION['is_admin'])) {
    header('Location: ' . public_url('login.php'));
    exit;
  }
}

/** Resuelve URL relativa a /public */
function public_url(string $path): string {
  $scriptDir  = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/');
  $publicBase = preg_replace('#/admin$#', '', $scriptDir);
  return $publicBase . '/' . ltrim($path, '/');
}

/** CSRF mínimo */
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
  }
  return $_SESSION['csrf'];
}
function check_csrf(string $token): void {
  if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
    http_response_code(400);
    exit('CSRF token inválido');
  }
}
