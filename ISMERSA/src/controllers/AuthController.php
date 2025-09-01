<?php
declare(strict_types=1);

class AuthController {
  public static function handleLogin(array $post): ?string {
    $email = trim($post['email'] ?? '');
    $pass  = (string)($post['password'] ?? '');
    try {
      $db = new DatabaseManager();
      $user = $db->authenticateAdmin($email, $pass);
      if ($user) {
        $_SESSION['admin_id']   = $user['id_usuario'];
        $_SESSION['admin_name'] = $user['nombre'];
        $_SESSION['is_admin']   = 1;
        header('Location: ' . public_url('admin/index.php'));
        exit;
      }
      return 'Credenciales inválidas o usuario no es admin.';
    } catch (Throwable $e) {
      return 'Error interno al autenticar.';
    }
  }

  public static function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
      $p = session_get_cookie_params();
      setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: ' . public_url('index.php'));
    exit;
  }
}
