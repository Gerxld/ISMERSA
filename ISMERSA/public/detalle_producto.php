<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/controllers/Site/FrontProductController.php';
require __DIR__ . '/partials/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$added = isset($_GET['added']) ? 1 : 0;

try {
    $controller = new FrontProductController();
    $vm = $controller->getDetailViewModel($id);
} catch (Throwable $e) {
    header('Location: ' . public_url('productos.php'));
    exit;
}

$producto     = $vm['producto'];
$imagenes     = $vm['imagenes'];
$imgPrincipal = $vm['imgPrincipal'];
?>

<?php if ($added): ?>
<div class="alert alert-success d-flex justify-content-between align-items-center">
  <div><strong>¡Listo!</strong> Producto añadido al carrito.</div>
  <a class="btn btn-sm btn-outline-success" href="<?= public_url('carrito.php') ?>">Ver carrito y métodos de pago</a>
</div>
<?php endif; ?>

<div class="row g-4">
  <!-- Galería -->
  <div class="col-12 col-md-6">
    <div class="border rounded-3 overflow-hidden mb-3">
      <img id="mainImage"
           src="<?= htmlspecialchars($imgPrincipal) ?>"
           class="w-100"
           style="object-fit:cover;max-height:460px"
           alt="Imagen principal">
    </div>

    <?php if (count($imagenes) > 1): ?>
      <div class="d-flex gap-2 flex-wrap">
        <?php foreach ($imagenes as $img): ?>
          <img src="<?= htmlspecialchars($img['ruta']) ?>"
               class="border rounded thumb-sel"
               style="width:90px;height:90px;object-fit:cover;cursor:pointer"
               data-large="<?= htmlspecialchars($img['ruta']) ?>"
               alt="miniatura">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Información -->
  <div class="col-12 col-md-6">
    <h3 class="mb-1"><?= htmlspecialchars($producto['nombre']) ?></h3>

    <div class="text-muted mb-2">
      <span class="me-3">SKU: <?= htmlspecialchars($producto['sku'] ?? '—') ?></span>
      <?php if (!empty($producto['marca_producto'])): ?>
        <span class="me-3">Brand: <?= htmlspecialchars($producto['marca_producto']) ?></span>
      <?php endif; ?>
      <?php if (!empty($producto['marca_vehiculo'])): ?>
        <span class="me-3">Make: <?= htmlspecialchars($producto['marca_vehiculo']) ?></span>
      <?php endif; ?>
    </div>

    <div class="d-flex align-items-center mb-3">
      <div class="display-6 fw-bold me-3">$<?= number_format((float)$producto['precio'], 2) ?></div>
      <?php if ((int)$producto['cantidad'] > 0): ?>
        <span class="badge bg-success">En stock: <?= (int)$producto['cantidad'] ?></span>
      <?php else: ?>
        <span class="badge bg-danger">Sin stock</span>
      <?php endif; ?>
    </div>

    <?php if (!empty($producto['descripcion'])): ?>
      <p class="mb-4"><?= nl2br(htmlspecialchars((string)$producto['descripcion'])) ?></p>
    <?php endif; ?>

    <!-- Añadir al carrito -->
    <form class="row g-3" method="post" action="<?= public_url('carrito_add.php') ?>">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int)$producto['id_producto'] ?>">

      <div class="col-12 col-lg-5">
        <label class="form-label">Cantidad</label>
        <div class="input-group">
          <button class="btn btn-outline-secondary" type="button" id="qtyMinus">−</button>
          <input
            type="number"
            class="form-control text-center"
            name="qty" id="qtyInput"
            value="1"
            min="1"
            max="<?= max(1,(int)$producto['cantidad']) ?>">
          <button class="btn btn-outline-secondary" type="button" id="qtyPlus">+</button>
        </div>
      </div>

      <div class="col-12 col-lg-7 d-flex align-items-end">
        <button class="btn btn-danger btn-lg w-100" <?= (int)$producto['cantidad']<=0 ? 'disabled' : '' ?>>
          Añadir al carrito
        </button>
      </div>
    </form>

    <div class="mt-3">
      <a class="btn btn-outline-secondary" href="<?= public_url('productos.php') ?>">&larr; Volver al catálogo</a>
    </div>
  </div>
</div>

<script>
  document.querySelectorAll('.thumb-sel').forEach(function (el) {
    el.addEventListener('click', function () {
      const large = this.getAttribute('data-large');
      const main  = document.getElementById('mainImage');
      if (large && main) main.src = large;
    });
  });

  (function(){
    const minus = document.getElementById('qtyMinus');
    const plus  = document.getElementById('qtyPlus');
    const input = document.getElementById('qtyInput');
    minus?.addEventListener('click', ()=> {
      const v = Math.max(parseInt(input.value || '1',10)-1, parseInt(input.min||'1',10));
      input.value = v;
    });
    plus?.addEventListener('click', ()=> {
      const max = parseInt(input.max || '9999', 10);
      const v = Math.min(parseInt(input.value || '1',10)+1, max);
      input.value = v;
    });
  })();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
