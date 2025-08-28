<?php
declare(strict_types=1);
require __DIR__ . '/../db/DatabaseManager.php';
require __DIR__ . '/partials/header.php';

$db = new DatabaseManager();

/* ===========================
   INPUTS
=========================== */
$q         = trim((string)($_GET['q']   ?? ''));
$marca     = trim((string)($_GET['marca'] ?? ''));
$minPrecio = ($_GET['min'] ?? '') !== '' ? (float)$_GET['min'] : null;
$maxPrecio = ($_GET['max'] ?? '') !== '' ? (float)$_GET['max'] : null;
$order     = in_array(($_GET['order'] ?? ''), ['nuevo','precio_asc','precio_desc'], true) ? $_GET['order'] : 'nuevo';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 12;
$offset    = ($page - 1) * $perPage;

/* ===========================
   CONEXIÓN PDO desde Manager
=========================== */
$ref = new ReflectionClass(DatabaseManager::class);
$prop = $ref->getProperty('db');
$prop->setAccessible(true);
$pdo = $prop->getValue(new DatabaseManager());

/* ===========================
   MARCAS (para selector)
=========================== */
$marcas = $pdo->query("SELECT DISTINCT marca FROM productos WHERE marca IS NOT NULL AND marca <> '' ORDER BY marca ASC")->fetchAll(PDO::FETCH_COLUMN);

/* ===========================
   QUERY LISTADO
=========================== */
switch ($order) {
    case 'precio_asc':
        $orderSql = 'p.precio ASC';
        break;
    case 'precio_desc':
        $orderSql = 'p.precio DESC';
        break;
    default:
        $orderSql = 'p.publicado_en DESC'; // nuevo
        break;
}


$where = [];
$params = [];

if ($q !== '') {
  $where[] = "(p.nombre LIKE :q OR p.descripcion LIKE :q)";
  $params[':q'] = '%'.$q.'%';
}
if ($marca !== '') {
  $where[] = "p.marca = :marca";
  $params[':marca'] = $marca;
}
if ($minPrecio !== null) {
  $where[] = "p.precio >= :minp";
  $params[':minp'] = $minPrecio;
}
if ($maxPrecio !== null) {
  $where[] = "p.precio <= :maxp";
  $params[':maxp'] = $maxPrecio;
}

$sqlBase = "FROM v_productos_publicados p"; // ya filtra estado=publicado y stock>0

// total
$sqlCount = "SELECT COUNT(*) ".$sqlBase.( $where ? " WHERE ".implode(" AND ", $where) : "" );
$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// datos
$sqlRows = "SELECT p.id_producto, p.sku, p.nombre, p.marca, p.precio, p.publicado_en
            ".$sqlBase.
            ( $where ? " WHERE ".implode(" AND ", $where) : "" ).
            " ORDER BY {$orderSql} LIMIT :lim OFFSET :off";
$stmt = $pdo->prepare($sqlRows);
foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset,  PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

// stmt para 1 imagen
$getImgStmt = $pdo->prepare("
  SELECT ruta FROM producto_imagenes
  WHERE producto_id = :pid
  ORDER BY es_principal DESC, orden ASC, id_imagen ASC
  LIMIT 1
");
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Catálogo</h3>
  <a class="btn btn-outline-secondary" href="index.php">&larr; Inicio</a>
</div>

<!-- FILTROS -->
<form method="get" class="card card-body shadow-sm mb-4">
  <div class="row g-3 align-items-end">
    <div class="col-md-4">
      <label class="form-label">Buscar</label>
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="Nombre o descripción">
    </div>
    <div class="col-md-3">
      <label class="form-label">Marca</label>
      <select name="marca" class="form-select">
        <option value="">Todas</option>
        <?php foreach ($marcas as $m): ?>
          <option value="<?= htmlspecialchars($m) ?>" <?= $marca===$m?'selected':'' ?>><?= htmlspecialchars($m) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Min $</label>
      <input type="number" step="0.01" name="min" value="<?= $minPrecio !== null ? htmlspecialchars((string)$minPrecio) : '' ?>" class="form-control">
    </div>
    <div class="col-md-2">
      <label class="form-label">Max $</label>
      <input type="number" step="0.01" name="max" value="<?= $maxPrecio !== null ? htmlspecialchars((string)$maxPrecio) : '' ?>" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label">Ordenar por</label>
      <select name="order" class="form-select">
        <option value="nuevo"       <?= $order==='nuevo'?'selected':'' ?>>Más nuevos</option>
        <option value="precio_asc"  <?= $order==='precio_asc'?'selected':'' ?>>Precio: menor a mayor</option>
        <option value="precio_desc" <?= $order==='precio_desc'?'selected':'' ?>>Precio: mayor a menor</option>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary w-100">Filtrar</button>
    </div>
    <div class="col-md-2">
      <a href="productos.php" class="btn btn-outline-secondary w-100">Limpiar</a>
    </div>
  </div>
</form>

<!-- RESULTADOS -->
<?php if ($total === 0): ?>
  <div class="alert alert-info">No se encontraron productos con los criterios indicados.</div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($rows as $r): ?>
  <?php
    $img = '../uploads/imagenes/placeholder.jpg';
    $getImgStmt->execute([':pid' => $r['id_producto']]);
    if ($im = $getImgStmt->fetch()) $img = $im['ruta'];
  ?>
  <div class="col-6 col-md-4 col-lg-3">
    <div class="card h-100 shadow-sm">
      <img src="<?= htmlspecialchars($img) ?>" class="card-img-top" alt="imagen producto" style="object-fit:cover;height:180px">
      <div class="card-body d-flex flex-column">
        <div class="small text-muted"><?= htmlspecialchars($r['marca'] ?? '') ?></div>
        <h6 class="card-title"><?= htmlspecialchars($r['nombre']) ?></h6>
        <div class="mt-auto fw-bold">$<?= number_format((float)$r['precio'], 2) ?></div>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<!-- PAGINACIÓN -->
<?php if ($totalPages > 1): ?>
<nav class="mt-4">
  <ul class="pagination justify-content-center">
    <?php
      $qs = $_GET; // mantener filtros
      $qs['page'] = max(1, $page-1);
      $prevUrl = '?'.http_build_query($qs);
      $qs['page'] = min($totalPages, $page+1);
      $nextUrl = '?'.http_build_query($qs);
    ?>
    <li class="page-item <?= $page<=1?'disabled':'' ?>">
      <a class="page-link" href="<?= $prevUrl ?>">Anterior</a>
    </li>
    <?php
      // páginas compactas
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

<?php require __DIR__ . '/partials/footer.php'; ?>
