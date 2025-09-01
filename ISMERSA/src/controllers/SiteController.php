<?php
declare(strict_types=1);

class SiteController {
  public static function catalogData(array $query): array {
    $db  = new DatabaseManager();

    $ref = new ReflectionClass(DatabaseManager::class);
    $prop = $ref->getProperty('db'); $prop->setAccessible(true);
    /** @var PDO $pdo */
    $pdo = $prop->getValue($db);

    $q           = trim((string)($query['q'] ?? ''));
    // multi-selección brand (marca del producto)
    $brandsSel   = array_values(array_filter(
      is_array($query['brand'] ?? null) ? $query['brand'] : [],
      fn($x)=> (string)$x !== ''
    ));
    // multi-selección make (marca del vehículo)
    $makesSel    = array_values(array_filter(
      is_array($query['make'] ?? null) ? $query['make'] : [],
      fn($x)=> (string)$x !== ''
    ));
    $minPrecio   = ($query['min'] ?? '') !== '' ? (float)$query['min'] : null;
    $maxPrecio   = ($query['max'] ?? '') !== '' ? (float)$query['max'] : null;
    $order       = in_array(($query['order'] ?? ''), ['nuevo','precio_asc','precio_desc'], true) ? $query['order'] : 'nuevo';
    $page        = max(1, (int)($query['page'] ?? 1));
    $perPage     = 12;
    $offset      = ($page - 1) * $perPage;

    switch ($order) {
      case 'precio_asc':  $orderSql = 'p.precio ASC';  break;
      case 'precio_desc': $orderSql = 'p.precio DESC'; break;
      default:            $orderSql = 'p.publicado_en DESC';
    }

    // Listas maestras (todas las opciones disponibles en productos)
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

    // Conteos sobre publicados (vista ya filtra estado y stock)
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

    // WHERE dinámico para el listado
    $where=[]; $params=[];
    if ($q!==''){ $where[]="(p.nombre LIKE :q OR p.descripcion LIKE :q)"; $params[':q']='%'.$q.'%'; }

    if (!empty($brandsSel)) {
      $in=[]; foreach($brandsSel as $i=>$b){ $k=":b$i"; $in[]=$k; $params[$k]=$b; }
      $where[] = "p.marca_producto IN (".implode(',', $in).")";
    }
    if (!empty($makesSel)) {
      $in=[]; foreach($makesSel as $i=>$m){ $k=":m$i"; $in[]=$k; $params[$k]=$m; }
      $where[] = "p.marca_vehiculo IN (".implode(',', $in).")";
    }
    if ($minPrecio!==null){ $where[]="p.precio >= :minp"; $params[':minp']=$minPrecio; }
    if ($maxPrecio!==null){ $where[]="p.precio <= :maxp"; $params[':maxp']=$maxPrecio; }

    $sqlBase = "FROM v_productos_publicados p";

    // total
    $sqlCount = "SELECT COUNT(*) $sqlBase".($where?" WHERE ".implode(" AND ",$where):"");
    $stmt = $pdo->prepare($sqlCount); $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $totalPages = max(1, (int)ceil($total/$perPage));

    // filas
    $sqlRows = "SELECT p.id_producto, p.sku, p.nombre,
                       p.marca_producto, p.marca_vehiculo,
                       p.precio, p.publicado_en
                $sqlBase ".($where?" WHERE ".implode(" AND ",$where):"").
               " ORDER BY $orderSql LIMIT :lim OFFSET :off";
    $stmt = $pdo->prepare($sqlRows);
    foreach($params as $k=>$v) $stmt->bindValue($k,$v);
    $stmt->bindValue(':lim',$perPage,PDO::PARAM_INT);
    $stmt->bindValue(':off',$offset,PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 1 imagen por producto
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

    return compact(
      'q','brandsSel','makesSel','minPrecio','maxPrecio','order','page','perPage','offset',
      'productBrands','carMakes','brandCounts','makeCounts','rows','total','totalPages'
    );
  }
}
