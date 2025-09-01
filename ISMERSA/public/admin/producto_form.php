<?php
declare(strict_types=1);
require __DIR__ . '/../../src/bootstrap.php';
require_admin_guard();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  ProductController::save($_POST, $_FILES);
}

$id = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$data = ProductController::getFormData($id);
extract($data, EXTR_OVERWRITE); // $estados, $producto

$csrf = csrf_token();
require __DIR__ . '/../partials/header.php';
?>
<div class="mb-3 d-flex justify-content-between align-items-center">
  <a class="btn btn-outline-secondary" href="<?= public_url('admin/productos.php') ?>">&larr; Volver</a>
  <?php if($producto): ?>
    <form action="<?= public_url('admin/producto_estado.php') ?>" method="post" class="d-inline">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="id" value="<?= (int)$producto['id_producto'] ?>">
      <input type="hidden" name="accion" value="publicar">
      <button class="btn btn-success">Publicar</button>
    </form>
  <?php endif; ?>
</div>

<h4><?= $producto ? 'Editar producto' : 'Nuevo producto' ?></h4>

<form method="post" enctype="multipart/form-data" class="row g-3">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
  <?php if($producto): ?>
    <input type="hidden" name="id" value="<?= (int)$producto['id_producto'] ?>">
  <?php endif; ?>

  <div class="col-md-3">
    <label class="form-label">SKU</label>
    <input name="sku" class="form-control" value="<?= htmlspecialchars($producto['sku'] ?? '') ?>">
  </div>
  <div class="col-md-5">
    <label class="form-label">Nombre *</label>
    <input name="nombre" required class="form-control" value="<?= htmlspecialchars($producto['nombre'] ?? '') ?>">
  </div>

  <div class="col-md-4">
    <label class="form-label">Marca del producto (Brand) *</label>
    <input name="marca_producto" required class="form-control" value="<?= htmlspecialchars($producto['marca_producto'] ?? '') ?>">
  </div>

  <div class="col-md-4">
    <label class="form-label">Marca del vehículo (Make)</label>
    <input name="marca_vehiculo" class="form-control" value="<?= htmlspecialchars($producto['marca_vehiculo'] ?? '') ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">Cantidad</label>
    <input name="cantidad" type="number" min="0" value="<?= isset($producto['cantidad'])?(int)$producto['cantidad']:0 ?>" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Precio</label>
    <input name="precio" type="number" step="0.01" min="0" value="<?= isset($producto['precio'])?htmlspecialchars((string)$producto['precio']):'0.00' ?>" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Peso (kg)</label>
    <input name="peso_kg" type="number" step="0.001" min="0" value="<?= htmlspecialchars($producto['peso_kg'] ?? '') ?>" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Ubicación</label>
    <input name="ubicacion" class="form-control" value="<?= htmlspecialchars($producto['ubicacion'] ?? '') ?>">
  </div>

  <div class="col-12">
    <label class="form-label">Descripción</label>
    <textarea name="descripcion" rows="3" class="form-control"><?= htmlspecialchars($producto['descripcion'] ?? '') ?></textarea>
  </div>

  <div class="col-md-4">
    <label class="form-label">Estado</label>
    <select name="estado_id" class="form-select">
      <?php foreach($estados as $e): ?>
        <option value="<?= (int)$e['id_estado'] ?>" <?= isset($producto['estado_id']) && (int)$producto['estado_id']===(int)$e['id_estado']?'selected':'' ?>>
          <?= htmlspecialchars($e['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-8">
    <label class="form-label">Imágenes (agregar)</label>
    <input type="file" name="fotos[]" class="form-control" multiple accept="image/*">
    <div class="form-text">Se aceptan JPEG, PNG, WEBP, GIF. La primera es principal al crear.</div>
  </div>

  <div class="col-12">
    <button class="btn btn-success">Guardar</button>
  </div>
</form>

<?php if ($producto && !empty($producto['imagenes'])): ?>
<hr class="my-4">
<h5>Imágenes</h5>
<div class="row g-3">
  <?php foreach ($producto['imagenes'] as $img): ?>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm">
        <img src="<?= htmlspecialchars($img['ruta']) ?>" class="card-img-top" style="object-fit:cover;height:160px">
        <div class="card-body d-flex justify-content-between">
          <form method="post" action="<?= public_url('admin/imagen_set_primary.php') ?>">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="pid" value="<?= (int)$producto['id_producto'] ?>">
            <input type="hidden" name="iid" value="<?= (int)$img['id_imagen'] ?>">
            <button class="btn btn-sm btn-outline-primary" <?= (int)$img['es_principal']===1?'disabled':'' ?>>Principal</button>
          </form>
          <form method="post" action="<?= public_url('admin/imagen_delete.php') ?>" onsubmit="return confirm('¿Eliminar imagen?');">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="pid" value="<?= (int)$producto['id_producto'] ?>">
            <input type="hidden" name="iid" value="<?= (int)$img['id_imagen'] ?>">
            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
