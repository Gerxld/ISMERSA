<?php
declare(strict_types=1);

class ProductController {
  public static function list(array $query): array {
    require_admin_guard();
    $db = new DatabaseManager();
    $filters = [];
    if (!empty($query['q']))      $filters['q']      = trim((string)$query['q']);
    if (!empty($query['estado'])) $filters['estado'] = trim((string)$query['estado']);
    $productos = $db->listProducts($filters, 200, 0, 'p.actualizado_en DESC');
    $estados   = $db->getEstados();
    return compact('productos','estados','filters');
  }

  public static function getFormData(?int $id): array {
    require_admin_guard();
    $db = new DatabaseManager();
    $estados = $db->getEstados();
    $producto = $id ? $db->getProductById($id) : null;
    return compact('estados','producto');
  }

  public static function save(array $post, array $files): void {
    require_admin_guard();
    check_csrf($post['csrf'] ?? '');
    $db = new DatabaseManager();

    $data = [
      'sku'            => $post['sku'] ?? null,
      'nombre'         => $post['nombre'] ?? '',
      'descripcion'    => $post['descripcion'] ?? null,
      'marca_producto' => $post['marca_producto'] ?? '',
      'marca_vehiculo' => $post['marca_vehiculo'] ?? null,
      'cantidad'       => $post['cantidad'] ?? 0,
      'precio'         => $post['precio'] ?? 0,
      'estado_id'      => $post['estado_id'] ?? null,
      'peso_kg'        => $post['peso_kg'] ?? null,
      'ubicacion'      => $post['ubicacion'] ?? null,
    ];

    if (!empty($post['id'])) {
      $pid = (int)$post['id'];
      $db->updateProduct($pid, $data);
      self::uploadImages($pid, $files, false, $post['nombre'] ?? 'imagen');
      header('Location: ' . public_url('admin/producto_form.php').'?edit='.$pid);
      exit;
    } else {
      $pid = $db->createProduct($data);
      self::uploadImages($pid, $files, true,  $post['nombre'] ?? 'imagen');
      header('Location: ' . public_url('admin/producto_form.php').'?edit='.$pid);
      exit;
    }
  }

  public static function setEstado(array $post): void {
    require_admin_guard();
    check_csrf($post['csrf'] ?? '');
    $id = isset($post['id']) ? (int)$post['id'] : 0;
    $accion = $post['accion'] ?? '';
    if ($id<=0 || !in_array($accion, ['publicar','ocultar','archivar'], true)) {
      header('Location: ' . public_url('admin/productos.php')); exit;
    }
    $db = new DatabaseManager();
    switch ($accion) {
      case 'publicar': $db->setProductEstadoByNombre($id,'publicado'); break;
      case 'ocultar' : $db->setProductEstadoByNombre($id,'inactivo');  break;
      case 'archivar': $db->setProductEstadoByNombre($id,'archivado'); break;
    }
    header('Location: ' . public_url('admin/productos.php'));
  }

  public static function delete(array $post): void {
    require_admin_guard();
    check_csrf($post['csrf'] ?? '');
    $id = isset($post['id']) ? (int)$post['id'] : 0;
    if ($id>0) (new DatabaseManager())->deleteProduct($id);
    header('Location: ' . public_url('admin/productos.php'));
  }

  public static function setPrimary(array $post): void {
    require_admin_guard();
    check_csrf($post['csrf'] ?? '');
    $pid = (int)($post['pid'] ?? 0);
    $iid = (int)($post['iid'] ?? 0);
    if ($pid>0 && $iid>0) (new DatabaseManager())->setPrimaryImage($pid,$iid);
    header('Location: ' . public_url('admin/producto_form.php').'?edit='.$pid);
  }

  public static function deleteImage(array $post): void {
    require_admin_guard();
    check_csrf($post['csrf'] ?? '');
    $iid = (int)($post['iid'] ?? 0);
    $pid = (int)($post['pid'] ?? 0);
    if ($iid>0) (new DatabaseManager())->deleteImage($iid);
    $dest = $pid ? 'admin/producto_form.php?edit='.$pid : 'admin/productos.php';
    header('Location: ' . public_url($dest));
  }

  /** subir imágenes y guardar rutas relativas */
  private static function uploadImages(int $pid, array $files, bool $firstAsPrimary, string $alt): void {
    if (empty($files['fotos']['name'][0])) return;
    $imgs=[]; $f=$files['fotos']; $n=count($f['name']);
    for($i=0;$i<$n;$i++){
      if ($f['error'][$i]!==UPLOAD_ERR_OK) continue;
      $tmp=$f['tmp_name'][$i]; $mime=@mime_content_type($tmp);
      if (!in_array($mime,['image/jpeg','image/png','image/webp','image/gif'],true)) continue;
      $ext=strtolower(pathinfo($f['name'][$i],PATHINFO_EXTENSION));
      $safe=bin2hex(random_bytes(8)).'.'.$ext;
      $destFs = BASE_PATH . '/uploads/imagenes/' . $safe;
      if (!move_uploaded_file($tmp,$destFs)) continue;
      $rutaPublic = '../uploads/imagenes/'.$safe; // visible desde /public/admin/*
      $imgs[] = [
        'ruta'=>$rutaPublic,
        'alt_text'=>$alt,
        'es_principal'=> ($firstAsPrimary && $i===0?1:0),
        'orden'=>$i+1
      ];
    }
    if ($imgs) (new DatabaseManager())->insertImages($pid,$imgs,$firstAsPrimary);
  }
}
