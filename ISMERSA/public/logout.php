<?php
declare(strict_types=1);

// Iniciar sesión si aún no está
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vaciar variables de sesión
$_SESSION = [];

// Borrar cookie de sesión (por si aplica)
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

// Destruir sesión
session_destroy();

// Redirigir al inicio de /public
header('Location: index.php');
exit;
