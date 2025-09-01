<?php
declare(strict_types=1);
require __DIR__ . '/../../src/bootstrap.php';
require_admin_guard();

$data = ProductController::list($_GET);
extract($data, EXTR_OVERWRITE); // $productos, $estados, $filters
$csrf = csrf_token();

require __DIR__ . '/../partials/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Gestionar productos</h3>
  <a class="btn btn-primary" href="<?= public_url('admin/producto_form.php') ?>">Agregar producto</a>
</div>

<form class="card card-body shadow-sm mb-3" method="get">
  <div class="row g-2">
    <div class="col-md-6">
      <input class="form-control" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="Buscar por nombre o descripción">
    </div>
    <div class="col-md-3">
      <select class="form-select" name="estado">
        <option value="">Todos los estados</option>
        <?php foreach($estados as $e): $n=$e['nombre']; ?>
          <option value="<?= htmlspecialchars($n) ?>" <?= (isset($filters['estado']) && $filters['estado']===$n)?'selected':'' ?>>
            <?= htmlspecialchars($n) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <button class="btn btn-outline-primary w-100">Filtrar</button>
    </div>
  </div>
</form>

<div class="table-responsive">
<table class="table table-striped align-middle">
  <thead>
    <tr>
      <th>ID</th><th>SKU</th><th>Nombre</th><th>Marca</th><th>Precio</th><th>Cant.</th><th>Estado</th><th class="text-end">Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($productos as $p): ?>
      <tr>
        <td><?= (int)$p['id_producto'] ?></td>
        <td><?= htmlspecialchars($p['sku'] ?? '') ?></td>
        <td><?= htmlspecialchars($p['nombre']) ?></td>
        <td><?= htmlspecialchars($p['marca'] ?? '') ?></td>
        <td>$<?= number_format((float)$p['precio'],2) ?></td>
        <td><?= (int)$p['cantidad'] ?></td>
        <td><span class="badge bg-secondary"><?= htmlspecialchars($p['estado_nombre']) ?></span></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-secondary" href="<?= public_url('admin/producto_form.php') ?>?edit=<?= (int)$p['id_producto'] ?>">Editar</a>

          <form action="<?= public_url('admin/producto_estado.php') ?>" method="post" class="d-inline">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int)$p['id_producto'] ?>">
            <input type="hidden" name="accion" value="publicar">
            <button class="btn btn-sm btn-success" title="Publicar">Publicar</button>
          </form>

          <form action="<?= public_url('admin/producto_estado.php') ?>" method="post" class="d-inline">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int)$p['id_producto'] ?>">
            <input type="hidden" name="accion" value="ocultar">
            <button class="btn btn-sm btn-warning" title="Ocultar">Ocultar</button>
          </form>

          <form action="<?= public_url('admin/producto_estado.php') ?>" method="post" class="d-inline">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int)$p['id_producto'] ?>">
            <input type="hidden" name="accion" value="archivar">
            <button class="btn btn-sm btn-outline-dark" title="Archivar">Archivar</button>
          </form>

          <form action="<?= public_url('admin/producto_delete.php') ?>" method="post" class="d-inline" onsubmit="return confirm('¿Eliminar producto? Esta acción no se puede deshacer.');">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int)$p['id_producto'] ?>">
            <button class="btn btn-sm btn-danger">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
