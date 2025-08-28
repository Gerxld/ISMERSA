<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();

require __DIR__ . '/../db/DatabaseManager.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');
    try {
        $db = new DatabaseManager();
        $user = $db->authenticateAdmin($email, $pass);
        if ($user) {
            $_SESSION['admin_id']   = $user['id_usuario'];
            $_SESSION['admin_name'] = $user['nombre'];
            $_SESSION['is_admin']   = 1;

            // ✅ Ruta relativa: funciona tanto en /public como detrás de subcarpetas (ismersa2/ISMERSA/...)
            header('Location: admin/index.php');
            exit;
        } else {
            $err = 'Credenciales inválidas o usuario no es admin.';
        }
    } catch (Throwable $e) {
        $err = 'Error interno al autenticar.';
    }
}

// Solo si no se hizo redirect, renderizamos la página:
require __DIR__ . '/partials/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="mb-3">Acceso Admin</h4>
        <?php if($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        <form method="post" autocomplete="off">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input name="password" type="password" class="form-control" required>
          </div>
          <button class="btn btn-primary w-100">Entrar</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
