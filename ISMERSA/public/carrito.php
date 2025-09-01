<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/controllers/Site/FrontCartController.php';
require __DIR__ . '/partials/header.php';

$removed = isset($_GET['removed']) ? 1 : 0;

$controller = new FrontCartController();
$vm = $controller->getCartViewModel();
$items = $vm['items'];
$subtotal = $vm['subtotal'];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Tu carrito</h3>
  <a class="btn btn-outline-secondary" href="<?= public_url('productos.php') ?>">&larr; Seguir comprando</a>
</div>

<?php if ($removed): ?>
  <div class="alert alert-success">Producto eliminado del carrito.</div>
<?php endif; ?>

<?php if (empty($items)): ?>
  <div class="alert alert-info">Tu carrito está vacío.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th style="width:72px;"></th>
          <th>Producto</th>
          <th class="text-end">Precio</th>
          <th class="text-center">Cant.</th>
          <th class="text-end">Total</th>
          <th class="text-center">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): 
          $lineTotal = ((float)$it['precio']) * ((int)$it['qty']);
        ?>
          <tr>
            <td>
              <img src="<?= htmlspecialchars($it['img']) ?>"
                   alt="img"
                   class="rounded border"
                   style="width:64px;height:64px;object-fit:cover;">
            </td>
            <td>
              <div class="fw-semibold"><?= htmlspecialchars($it['nombre']) ?></div>
              <div class="text-muted small">#<?= (int)$it['id'] ?></div>
            </td>
            <td class="text-end">$<?= number_format((float)$it['precio'], 2) ?></td>
            <td class="text-center"><?= (int)$it['qty'] ?></td>
            <td class="text-end">$<?= number_format($lineTotal, 2) ?></td>
            <td class="text-center">
              <form method="post" action="<?= public_url('carrito_remove.php') ?>" onsubmit="return confirm('¿Eliminar este producto del carrito?');" class="d-inline">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                <button class="btn btn-sm btn-outline-danger">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="4" class="text-end">Subtotal</th>
          <th class="text-end">$<?= number_format($subtotal, 2) ?></th>
          <th></th>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="<?= public_url('productos.php') ?>" class="btn btn-outline-secondary">Seguir comprando</a>
    <a href="#" class="btn btn-primary" onclick="alert('Checkout próximamente'); return false;">Proceder al pago</a>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
