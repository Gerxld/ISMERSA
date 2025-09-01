<?php
declare(strict_types=1);
require __DIR__ . '/../../src/bootstrap.php';
require_admin_guard();

require __DIR__ . '/../partials/header.php';

$db   = new DatabaseManager();
$kpis = $db->getKpis();
$low  = $db->getLowStock(3);
$last = $db->getRecentProducts(8);
?>
<h3 class="mb-3">Dashboard ISMERSA</h3>

<div class="row g-3">
  <div class="col-sm-6 col-lg-3">
    <div class="card shadow-sm"><div class="card-body">
      <div class="text-muted small">Publicados (con stock)</div>
      <div class="fs-3 fw-bold"><?= (int)$kpis['publicados'] ?></div>
    </div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card shadow-sm"><div class="card-body">
      <div class="text-muted small">Productos totales</div>
      <div class="fs-3 fw-bold"><?= (int)$kpis['productos'] ?></div>
    </div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card shadow-sm"><div class="card-body">
      <div class="text-muted small">Marcas</div>
      <div class="fs-3 fw-bold"><?= (int)$kpis['marcas'] ?></div>
    </div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card shadow-sm"><div class="card-body">
      <div class="text-muted small">Imágenes</div>
      <div class="fs-3 fw-bold"><?= (int)$kpis['imagenes'] ?></div>
    </div></div>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-4 mb-2">
  <h5 class="mb-0">Visión rápida</h5>
  <div class="d-flex gap-2">
    <a class="btn btn-primary" href="<?= public_url('admin/producto_form.php') ?>">Agregar producto</a>
    <a class="btn btn-outline-secondary" href="<?= public_url('admin/productos.php') ?>">Gestionar productos</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-white fw-semibold">Stock bajo (≤ 3)</div>
      <div class="card-body">
        <?php if (!$low): ?>
          <div class="text-muted">No hay productos con stock bajo.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead><tr><th>ID</th><th>SKU</th><th>Nombre</th><th>Marca</th><th>Cant.</th></tr></thead>
              <tbody>
                <?php foreach($low as $p): ?>
                  <tr>
                    <td><?= (int)$p['id_producto'] ?></td>
                    <td><?= htmlspecialchars($p['sku'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= htmlspecialchars($p['marca'] ?? '') ?></td>
                    <td class="fw-bold"><?= (int)$p['cantidad'] ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-white fw-semibold">Últimos actualizados</div>
      <div class="card-body">
        <?php if (!$last): ?>
          <div class="text-muted">Aún no hay productos.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead><tr><th>Nombre</th><th>Marca</th><th>Precio</th><th>Cant.</th><th>Estado</th></tr></thead>
              <tbody>
                <?php foreach($last as $p): ?>
                  <tr>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= htmlspecialchars($p['marca'] ?? '') ?></td>
                    <td>$<?= number_format((float)$p['precio'], 2) ?></td>
                    <td><?= (int)$p['cantidad'] ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($p['estado']) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
