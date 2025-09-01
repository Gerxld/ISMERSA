<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/partials/header.php';

/* ===========================================================
   1) Conseguir PDO desde DatabaseManager (sin controllers)
=========================================================== */
$db  = new DatabaseManager();
$ref = new ReflectionClass(DatabaseManager::class);
$prop = $ref->getProperty('db');
$prop->setAccessible(true);
/** @var PDO $pdo */
$pdo = $prop->getValue($db);

/* ===========================================================
   2) Inputs GET (compatibles con PHP < 7.4)
=========================================================== */
$q         = trim((string)($_GET['q'] ?? ''));
$brandsSel = array_values(array_filter(
  (is_array($_GET['brand'] ?? null) ? $_GET['brand'] : []),
  function ($x) { return (string)$x !== ''; }
));
$makesSel  = array_values(array_filter(
  (is_array($_GET['make'] ?? null) ? $_GET['make'] : []),
  function ($x) { return (string)$x !== ''; }
));
$minPrecio = ($_GET['min'] ?? '') !== '' ? (float)$_GET['min'] : null;
$maxPrecio = ($_GET['max'] ?? '') !== '' ? (float)$_GET['max'] : null;
$order     = in_array(($_GET['order'] ?? ''), ['nuevo','precio_asc','precio_desc'], true) ? $_GET['order'] : 'nuevo';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 12;
$offset    = ($page - 1) * $perPage;

switch ($order) {
  case 'precio_asc':  $orderSql = 'p.precio ASC';  break;
  case 'precio_desc': $orderSql = 'p.precio DESC'; break;
  default:            $orderSql = 'p.publicado_en DESC';
}

/* ===========================================================
   3) Opciones de filtros y conteos
   (Requiere la vista v_productos_publicados)
=========================================================== */
$productBrands = $pdo->query("
  SELECT DISTINCT marca_producto
  FROM productos
  WHERE marca_producto IS NOT NULL AND marca_producto <> ''
  ORDER BY marca_producto ASC
")->fetchAll(PDO::FETCH_COLUMN);

$carMakes = $pdo->query("
  SELECT DISTINCT marca_vehiculo
  FROM productos
  WHERE marca_vehiculo IS NOT NULL AND marca_vehiculo <> ''
  ORDER BY marca_vehiculo ASC
")->fetchAll(PDO::FETCH_COLUMN);

$brandCounts = $pdo->query("
  SELECT marca_producto, COUNT(*) AS cnt
  FROM v_productos_publicados
  WHERE marca_producto IS NOT NULL AND marca_producto <> ''
  GROUP BY marca_producto
  ORDER BY marca_producto ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$makeCounts = $pdo->query("
  SELECT marca_vehiculo, COUNT(*) AS cnt
  FROM v_productos_publicados
  WHERE marca_vehiculo IS NOT NULL AND marca_vehiculo <> ''
  GROUP BY marca_vehiculo
  ORDER BY marca_vehiculo ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

/* ===========================================================
   4) WHERE dinámico + total + filas
=========================================================== */
$where  = [];
$params = [];

if ($q !== '') {
  $where[] = "(p.nombre LIKE :q OR p.descripcion LIKE :q)";
  $params[':q'] = '%'.$q.'%';
}
if (!empty($brandsSel)) {
  $in = [];
  foreach ($brandsSel as $i => $b) { $k=":b$i"; $in[]=$k; $params[$k]=$b; }
  $where[] = "p.marca_producto IN (".implode(',', $in).")";
}
if (!empty($makesSel)) {
  $in = [];
  foreach ($makesSel as $i => $m) { $k=":m$i"; $in[]=$k; $params[$k]=$m; }
  $where[] = "p.marca_vehiculo IN (".implode(',', $in).")";
}
if ($minPrecio !== null) { $where[] = "p.precio >= :minp"; $params[':minp'] = $minPrecio; }
if ($maxPrecio !== null) { $where[] = "p.precio <= :maxp"; $params[':maxp'] = $maxPrecio; }

$sqlBase = "FROM v_productos_publicados p";

$sqlCount = "SELECT COUNT(*) $sqlBase".($where ? " WHERE ".implode(" AND ",$where) : "");
$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$sqlRows = "SELECT p.id_producto, p.sku, p.nombre,
                   p.marca_producto, p.marca_vehiculo,
                   p.precio, p.publicado_en
            $sqlBase ".($where ? " WHERE ".implode(" AND ",$where) : "")."
            ORDER BY $orderSql LIMIT :lim OFFSET :off";
$stmt = $pdo->prepare($sqlRows);
foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->bindValue(':lim',$perPage,PDO::PARAM_INT);
$stmt->bindValue(':off',$offset,PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 5) 1 imagen por producto (principal si existe) */
$imgStmt = $pdo->prepare("
  SELECT ruta FROM producto_imagenes
  WHERE producto_id = :pid
  ORDER BY es_principal DESC, orden ASC, id_imagen ASC
  LIMIT 1
");
foreach ($rows as &$r) {
  $img = '../uploads/imagenes/placeholder.jpg';
  $imgStmt->execute([':pid'=>$r['id_producto']]);
  if ($im = $imgStmt->fetch(PDO::FETCH_ASSOC)) $img = $im['ruta'];
  $r['_img'] = $img;
}
unset($r);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Catálogo</h3>
  <a class="btn btn-outline-secondary" href="<?= public_url('index.php') ?>">&larr; Inicio</a>
</div>

<div class="row g-4">
  <!-- Sidebar de filtros -->
  <aside class="col-12 col-lg-3">
    <h5 class="mb-3">Filtros</h5>

    <form id="filtersForm" method="get">
      <input type="hidden" name="q"   value="<?= htmlspecialchars($q) ?>">
      <input type="hidden" name="min" value="<?= $minPrecio !== null ? htmlspecialchars((string)$minPrecio) : '' ?>">
      <input type="hidden" name="max" value="<?= $maxPrecio !== null ? htmlspecialchars((string)$maxPrecio) : '' ?>">
      <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">

      <div class="accordion" id="filtersAccordion">
        <!-- Brand -->
        <div class="accordion-item">
          <h2 class="accordion-header" id="brandHead">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#brandCollapse" aria-expanded="true" aria-controls="brandCollapse">
              Brand
            </button>
          </h2>
          <div id="brandCollapse" class="accordion-collapse collapse show" aria-labelledby="brandHead">
            <div class="accordion-body p-0">
              <ul class="list-unstyled mb-0 py-2">
                <?php foreach ($productBrands as $m):
                  $checked = in_array($m, $brandsSel, true);
                  $count   = (int)($brandCounts[$m] ?? 0);
                ?>
                  <li class="d-flex align-items-center px-2">
                    <label class="w-100 py-2 d-flex align-items-center" style="cursor:pointer">
                      <input
                        type="checkbox"
                        class="form-check-input me-2 brand-check"
                        name="brand[]"
                        value="<?= htmlspecialchars($m) ?>"
                        <?= $checked ? 'checked' : '' ?>
                      >
                      <span class="flex-grow-1"><?= htmlspecialchars($m) ?></span>
                      <span class="text-muted ms-2">(<?= $count ?>)</span>
                    </label>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>

        <!-- Make -->
        <div class="accordion-item mt-2">
          <h2 class="accordion-header" id="makeHead">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#makeCollapse" aria-expanded="false" aria-controls="makeCollapse">
              Make
            </button>
          </h2>
          <div id="makeCollapse" class="accordion-collapse collapse" aria-labelledby="makeHead">
            <div class="accordion-body p-0">
              <ul class="list-unstyled mb-0 py-2">
                <?php foreach ($carMakes as $m):
                  $checked = in_array($m, $makesSel, true);
                  $count   = (int)($makeCounts[$m] ?? 0);
                ?>
                  <li class="d-flex align-items-center px-2">
                    <label class="w-100 py-2 d-flex align-items-center" style="cursor:pointer">
                      <input
                        type="checkbox"
                        class="form-check-input me-2 make-check"
                        name="make[]"
                        value="<?= htmlspecialchars($m) ?>"
                        <?= $checked ? 'checked' : '' ?>
                      >
                      <span class="flex-grow-1"><?= htmlspecialchars($m) ?></span>
                      <span class="text-muted ms-2">(<?= $count ?>)</span>
                    </label>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <?php if (!empty($brandsSel) || !empty($makesSel)): ?>
        <div class="mt-3">
          <a class="btn btn-sm btn-outline-secondary" href="<?= public_url('productos.php') ?>?<?= http_build_query([
            'q'=>$q,'min'=>$minPrecio,'max'=>$maxPrecio,'order'=>$order
          ]) ?>">Limpiar filtros</a>
        </div>
      <?php endif; ?>
    </form>
  </aside>

  <!-- Lista -->
  <section class="col-12 col-lg-9">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
      <div class="text-muted"><?= (int)$total ?> resultado(s)</div>

      <form method="get" class="d-flex gap-2">
        <?php foreach(($brandsSel ?? []) as $b): ?>
          <input type="hidden" name="brand[]" value="<?= htmlspecialchars($b) ?>">
        <?php endforeach; ?>
        <?php foreach(($makesSel ?? []) as $b): ?>
          <input type="hidden" name="make[]" value="<?= htmlspecialchars($b) ?>">
        <?php endforeach; ?>
        <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
        <input type="hidden" name="min" value="<?= $minPrecio !== null ? htmlspecialchars((string)$minPrecio) : '' ?>">
        <input type="hidden" name="max" value="<?= $maxPrecio !== null ? htmlspecialchars((string)$maxPrecio) : '' ?>">

        <select name="order" class="form-select" onchange="this.form.submit()">
          <option value="nuevo"       <?= $order==='nuevo'?'selected':'' ?>>Más nuevos</option>
          <option value="precio_asc"  <?= $order==='precio_asc'?'selected':'' ?>>Precio: menor a mayor</option>
          <option value="precio_desc" <?= $order==='precio_desc'?'selected':'' ?>>Precio: mayor a menor</option>
        </select>
      </form>
    </div>

    <?php if ($total === 0): ?>
      <div class="alert alert-info">No se encontraron productos con los criterios indicados.</div>
    <?php endif; ?>

    <div class="row g-3">
    <?php foreach ($rows as $r): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <div class="card h-100 shadow-sm position-relative">
          <img src="<?= htmlspecialchars($r['_img']) ?>" class="card-img-top" alt="imagen producto" style="object-fit:cover;height:180px">
          <div class="card-body d-flex flex-column">
            <div class="small text-muted">
              <?= htmlspecialchars($r['marca_producto'] ?? '') ?>
              <?php if (!empty($r['marca_vehiculo'])): ?>
                <span class="text-muted"> · <?= htmlspecialchars($r['marca_vehiculo']) ?></span>
              <?php endif; ?>
            </div>
            <h6 class="card-title mb-2"><?= htmlspecialchars($r['nombre']) ?></h6>
            <div class="mt-auto fw-bold">$<?= number_format((float)$r['precio'], 2) ?></div>
          </div>

          <!-- Enlace al detalle -->
          <a class="stretched-link"
             href="<?= public_url('detalle_producto.php') . '?id=' . (int)$r['id_producto'] ?>"
             aria-label="Ver <?= htmlspecialchars($r['nombre']) ?>"></a>
        </div>
      </div>
    <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="mt-4">
      <ul class="pagination justify-content-center">
        <?php
          $qs = $_GET;
          $qs['page'] = max(1, $page-1);
          $prevUrl = '?'.http_build_query($qs);
          $qs['page'] = min($totalPages, $page+1);
          $nextUrl = '?'.http_build_query($qs);
        ?>
        <li class="page-item <?= $page<=1?'disabled':'' ?>">
          <a class="page-link" href="<?= $prevUrl ?>">Anterior</a>
        </li>
        <?php
          $start = max(1, $page-2);
          $end   = min($totalPages, $page+2);
          for ($p=$start; $p<=$end; $p++):
            $qs['page'] = $p;
            $url = '?'.http_build_query($qs);
        ?>
          <li class="page-item <?= $p===$page?'active':'' ?>">
            <a class="page-link" href="<?= $url ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
          <a class="page-link" href="<?= $nextUrl ?>">Siguiente</a>
        </li>
      </ul>
    </nav>
    <?php endif; ?>
  </section>
</div>

<!-- Auto-submit checkboxes -->
<script>
  (function () {
    var form = document.getElementById('filtersForm');
    if (!form) return;
    form.addEventListener('change', function (e) {
      if (e.target && (e.target.classList.contains('brand-check') || e.target.classList.contains('make-check'))) {
        // Reinicia a la página 1 y envía
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'page';
        input.value = '1';
        form.appendChild(input);
        form.submit();
      }
    });
  })();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
