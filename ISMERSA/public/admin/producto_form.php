<?php
declare(strict_types=1);
require __DIR__ . '/../partials/auth.php';
require_admin();
require __DIR__ . '/../../db/DatabaseManager.php';
require __DIR__ . '/../partials/header.php';

$db = new DatabaseManager();
$estados = $db->getEstados();

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$producto = $editId ? $db->getProductById($editId) : null;

$ok = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!empty($_POST['id'])) {
            // UPDATE
            $pid = (int)$_POST['id'];
            $db->updateProduct($pid, [
                'sku'       => $_POST['sku'] ?? null,
                'nombre'    => $_POST['nombre'] ?? '',
                'descripcion'=> $_POST['descripcion'] ?? null,
                'marca'     => $_POST['marca'] ?? null,
                'cantidad'  => $_POST['cantidad'] ?? 0,
                'precio'    => $_POST['precio'] ?? 0,
                'estado_id' => $_POST['estado_id'] ?? null,
                'peso_kg'   => $_POST['peso_kg'] ?? null,
                'ubicacion' => $_POST['ubicacion'] ?? null,
            ]);
            $ok = 'Producto actualizado.';

            // subir imágenes adicionales
            if (!empty($_FILES['fotos']['name'][0])) {
                $imgs = [];
                $files = $_FILES['fotos'];
                $total = count($files['name']);
                for ($i=0;$i<$total;$i++){
                    if ($files['error'][$i]!==UPLOAD_ERR_OK) continue;
                    $tmp  = $files['tmp_name'][$i];
                    $mime = @mime_content_type($tmp);
                    if (!in_array($mime, ['image/jpeg','image/png','image/webp','image/gif'], true)) continue;
                    $ext  = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    $safe = bin2hex(random_bytes(8)).'.'.$ext;
                    $destFs = __DIR__ . '/../../uploads/imagenes/' . $safe;
                    if (!move_uploaded_file($tmp, $destFs)) continue;
                    $rutaPublic = '../uploads/imagenes/' . $safe; // relativa desde /public/admin/*
                    $imgs[] = ['ruta'=>$rutaPublic, 'alt_text'=>$_POST['nombre'] ?? 'imagen', 'es_principal'=>0];
                }
                if ($imgs) $db->insertImages($pid, $imgs, false);
            }

            $producto = $db->getProductById($pid);
        } else {
            // CREATE
            $pid = $db->createProduct([
                'sku'       => $_POST['sku'] ?? null,
                'nombre'    => $_POST['nombre'] ?? '',
                'descripcion'=> $_POST['descripcion'] ?? null,
                'marca'     => $_POST['marca'] ?? null,
                'cantidad'  => $_POST['cantidad'] ?? 0,
                'precio'    => $_POST['precio'] ?? 0,
                'estado_id' => $_POST['estado_id'] ?? null,
                'peso_kg'   => $_POST['peso_kg'] ?? null,
                'ubicacion' => $_POST['ubicacion'] ?? null,
            ]);

            $imgs = [];
            if (!empty($_FILES['fotos']['name'][0])) {
                $files = $_FILES['fotos'];
                $total = count($files['name']);
                for ($i=0;$i<$total;$i++){
                    if ($files['error'][$i]!==UPLOAD_ERR_OK) continue;
                    $tmp  = $files['tmp_name'][$i];
                    $mime = @mime_content_type($tmp);
                    if (!in_array($mime, ['image/jpeg','image/png','image/webp','image/gif'], true)) continue;
                    $ext  = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    $safe = bin2hex(random_bytes(8)).'.'.$ext;
                    $destFs = __DIR__ . '/../../uploads/imagenes/' . $safe;
                    if (!move_uploaded_file($tmp, $destFs)) continue;
                    $rutaPublic = '../uploads/imagenes/' . $safe;
                    $imgs[] = ['ruta'=>$rutaPublic, 'alt_text'=>$_POST['nombre'] ?? 'imagen', 'es_principal'=> ($i===0?1:0), 'orden'=>$i+1];
                }
            }
            if ($imgs) $db->insertImages($pid, $imgs, true);

            $ok = 'Producto creado.';
            header('Location: '.public_url('admin/producto_form.php').'?edit='.$pid);
            exit;
        }
    } catch (Throwable $e) {
        $err = 'Error: '.$e->getMessage();
    }
}
?>
<div class="mb-3 d-flex justify-content-between align-items-center">
  <a class="btn btn-outline-secondary" href="<?= public_url('admin/productos.php') ?>">&larr; Volver</a>
  <?php if($producto): ?>
    <form action="<?= public_url('admin/producto_estado.php') ?>" method="post" class="d-inline">
      <input type="hidden" name="id" value="<?= (int)$producto['id_producto'] ?>">
      <input type="hidden" name="accion" value="publicar">
      <button class="btn btn-success">Publicar</button>
    </form>
  <?php endif; ?>
</div>

<h4><?= $producto ? 'Editar producto' : 'Nuevo producto' ?></h4>
<?php if($ok): ?><div class="alert alert-success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="row g-3">
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
    <label class="form-label">Marca</label>
    <input name="marca" class="form-control" value="<?= htmlspecialchars($producto['marca'] ?? '') ?>">
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

<?php if ($producto): ?>
<hr class="my-4">
<h5>Imágenes</h5>
<div class="row g-3">
  <?php foreach (($producto['imagenes'] ?? []) as $img): ?>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm">
        <img src="<?= htmlspecialchars($img['ruta']) ?>" class="card-img-top" style="object-fit:cover;height:160px">
        <div class="card-body d-flex justify-content-between">
          <form method="post" action="<?= public_url('admin/imagen_set_primary.php') ?>">
            <input type="hidden" name="pid" value="<?= (int)$producto['id_producto'] ?>">
            <input type="hidden" name="iid" value="<?= (int)$img['id_imagen'] ?>">
            <button class="btn btn-sm btn-outline-primary" <?= (int)$img['es_principal']===1?'disabled':'' ?>>Principal</button>
          </form>
          <form method="post" action="<?= public_url('admin/imagen_delete.php') ?>" onsubmit="return confirm('¿Eliminar imagen?');">
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
