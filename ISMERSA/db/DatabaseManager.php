<?php
/**
 * ISMERSA - Capa de acceso a datos (DAO)
 * Ubicación sugerida: /db/DatabaseManager.php
 *
 * Maneja:
 *  - Usuarios (login admin)
 *  - Estados (listado)
 *  - Productos (CRUD básico, filtros y paginación)
 *  - Imágenes de producto (alta/baja, principal única)
 */

declare(strict_types=1);

require_once __DIR__ . '/conexionBD.php';

final class DatabaseManager
{
    private PDO $db;

    public function __construct(?PDO $pdo = null)
    {
        $this->db = $pdo ?: ConexionBD::getPDO();
    }

    /* ============================
     *           USUARIOS
     * ============================ */

    /**
     * Autentica admin por email y password.
     * Devuelve array con datos del usuario (sin hash) o null si falla.
     */
    public function authenticateAdmin(string $email, string $password): ?array
    {
        $sql = "SELECT id_usuario, nombre, email, password_hash, is_admin
                FROM usuarios WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || (int)$user['is_admin'] !== 1) {
            return null;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        // No exponer el hash
        unset($user['password_hash']);
        return $user;
    }

    /* ============================
     *           ESTADOS
     * ============================ */

    /**
     * Lista todos los estados.
     */
    public function getEstados(): array
    {
        $stmt = $this->db->query("SELECT id_estado, nombre, descripcion, es_publicable FROM estados ORDER BY id_estado ASC");
        return $stmt->fetchAll();
    }

    /**
     * Obtiene ID de estado por nombre (ej. 'publicado').
     */
    public function getEstadoIdByNombre(string $nombre): ?int
    {
        $stmt = $this->db->prepare("SELECT id_estado FROM estados WHERE nombre = :n LIMIT 1");
        $stmt->execute([':n' => $nombre]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id_estado'] : null;
    }

    /* ============================
     *           PRODUCTOS
     * ============================ */

    /**
     * Crea producto + (opcional) imágenes en una transacción.
     *
     * $data = [
     *   'sku'?, 'nombre', 'descripcion'?, 'marca'?,
     *   'cantidad', 'precio', 'estado_id'?, 'peso_kg'?, 'ubicacion'?
     * ]
     * $images = [
     *   ['ruta' => '/uploads/imagenes/xxx.jpg', 'alt_text' => '...', 'es_principal' => 1|0, 'orden' => 1],
     *   ...
     * ]
     */
    public function createProduct(array $data, array $images = []): int
    {
        $this->db->beginTransaction();
        try {
            // Normaliza y valida mínimos
            $nombre = trim((string)($data['nombre'] ?? ''));
            if ($nombre === '') {
                throw new InvalidArgumentException('El nombre del producto es obligatorio.');
            }
            $cantidad = max(0, (int)($data['cantidad'] ?? 0));
            $precio = (float)($data['precio'] ?? 0);
            if ($precio < 0) {
                $precio = 0.0;
            }

            $sql = "INSERT INTO productos (sku, nombre, descripcion, marca, cantidad, precio, estado_id, peso_kg, ubicacion)
                    VALUES (:sku, :nombre, :descripcion, :marca, :cantidad, :precio, :estado_id, :peso_kg, :ubicacion)";
            $stmt = $this->db->prepare($sql);

            // Si no envían estado, usar 'inactivo'
            $estadoId = $data['estado_id'] ?? $this->getEstadoIdByNombre('inactivo') ?? 2;

            $stmt->execute([
                ':sku'         => $data['sku']         ?? null,
                ':nombre'      => $nombre,
                ':descripcion' => $data['descripcion'] ?? null,
                ':marca'       => $data['marca']       ?? null,
                ':cantidad'    => $cantidad,
                ':precio'      => $precio,
                ':estado_id'   => $estadoId,
                ':peso_kg'     => $data['peso_kg']     ?? null,
                ':ubicacion'   => $data['ubicacion']   ?? null,
            ]);

            $productId = (int)$this->db->lastInsertId();

            // Inserta imágenes (si las hay)
            if (!empty($images)) {
                $this->insertImages($productId, $images, /*replacePrincipal*/ true);
            }

            $this->db->commit();
            return $productId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza producto por ID (solo campos presentes).
     */
    public function updateProduct(int $productId, array $fields): bool
    {
        if ($productId <= 0) return false;
        if (empty($fields)) return true;

        $allowed = ['sku','nombre','descripcion','marca','cantidad','precio','estado_id','peso_kg','ubicacion'];
        $sets = [];
        $params = [':id' => $productId];

        foreach ($fields as $k => $v) {
            if (!in_array($k, $allowed, true)) continue;
            $sets[] = "{$k} = :{$k}";
            // Normalizaciones básicas
            if ($k === 'cantidad') $v = max(0, (int)$v);
            if ($k === 'precio')   $v = max(0.0, (float)$v);
            $params[":{$k}"] = $v;
        }

        if (empty($sets)) return true;

        $sql = "UPDATE productos SET " . implode(', ', $sets) . " WHERE id_producto = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Obtiene un producto por ID con sus imágenes (ordenadas).
     */
    public function getProductById(int $productId): ?array
    {
        $sql = "SELECT p.*, e.nombre AS estado_nombre
                FROM productos p
                JOIN estados e ON e.id_estado = p.estado_id
                WHERE p.id_producto = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $productId]);
        $prod = $stmt->fetch();
        if (!$prod) return null;

        $imgs = $this->getImagesByProduct($productId);
        $prod['imagenes'] = $imgs;
        return $prod;
    }

    /**
     * Lista productos con filtros y paginación.
     * $filters = ['q'?, 'marca'?, 'estado'?, 'minPrecio'?, 'maxPrecio'?]
     */
    public function listProducts(array $filters = [], int $limit = 20, int $offset = 0, string $orderBy = 'p.creado_en DESC'): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = "(p.nombre LIKE :q OR p.descripcion LIKE :q)";
            $params[':q'] = '%' . trim((string)$filters['q']) . '%';
        }
        if (!empty($filters['marca'])) {
            $where[] = "p.marca = :marca";
            $params[':marca'] = $filters['marca'];
        }
        if (!empty($filters['estado'])) {
            $where[] = "e.nombre = :estado";
            $params[':estado'] = $filters['estado'];
        }
        if (isset($filters['minPrecio'])) {
            $where[] = "p.precio >= :minPrecio";
            $params[':minPrecio'] = (float)$filters['minPrecio'];
        }
        if (isset($filters['maxPrecio'])) {
            $where[] = "p.precio <= :maxPrecio";
            $params[':maxPrecio'] = (float)$filters['maxPrecio'];
        }

        $sql = "SELECT p.id_producto, p.sku, p.nombre, p.marca, p.precio, p.cantidad, e.nombre AS estado_nombre, p.creado_en, p.actualizado_en
                FROM productos p
                JOIN estados e ON e.id_estado = p.estado_id";
        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY {$orderBy} LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        // bind de LIMIT/OFFSET como enteros
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  max(1, $limit),  PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);

        $stmt->execute();
        $rows = $stmt->fetchAll();

        return $rows;
    }

    /**
     * Cuenta productos (útil para paginación).
     */
    public function countProducts(array $filters = []): int
    {
        $where = [];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = "(p.nombre LIKE :q OR p.descripcion LIKE :q)";
            $params[':q'] = '%' . trim((string)$filters['q']) . '%';
        }
        if (!empty($filters['marca'])) {
            $where[] = "p.marca = :marca";
            $params[':marca'] = $filters['marca'];
        }
        if (!empty($filters['estado'])) {
            $where[] = "e.nombre = :estado";
            $params[':estado'] = $filters['estado'];
        }
        if (isset($filters['minPrecio'])) {
            $where[] = "p.precio >= :minPrecio";
            $params[':minPrecio'] = (float)$filters['minPrecio'];
        }
        if (isset($filters['maxPrecio'])) {
            $where[] = "p.precio <= :maxPrecio";
            $params[':maxPrecio'] = (float)$filters['maxPrecio'];
        }

        $sql = "SELECT COUNT(*) AS c
                FROM productos p
                JOIN estados e ON e.id_estado = p.estado_id";
        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['c'] ?? 0);
    }

    /* ============================
     *     IMÁGENES DE PRODUCTO
     * ============================ */

    /**
     * Inserta múltiples imágenes.
     * Si $replacePrincipal = true, asegura que si alguna es principal,
     * anula cualquier otra principal existente antes de insertar.
     */
    public function insertImages(int $productId, array $images, bool $replacePrincipal = false): void
    {
        if ($productId <= 0 || empty($images)) return;

        // ¿Alguna marcada principal?
        $hasPrincipal = false;
        foreach ($images as $img) {
            if (!empty($img['es_principal'])) {
                $hasPrincipal = true;
                break;
            }
        }
        if ($replacePrincipal && $hasPrincipal) {
            // Quita principal previo
            $this->db->prepare("UPDATE producto_imagenes SET es_principal = 0 WHERE producto_id = :pid")
                ->execute([':pid' => $productId]);
        }

        // Obtener próximo orden por defecto
        $nextOrder = $this->getNextImageOrder($productId);

        $sql = "INSERT INTO producto_imagenes (producto_id, ruta, alt_text, es_principal, orden)
                VALUES (:pid, :ruta, :alt, :principal, :orden)";
        $stmt = $this->db->prepare($sql);

        foreach ($images as $img) {
            $ruta = trim((string)($img['ruta'] ?? ''));
            if ($ruta === '') continue;

            $orden = isset($img['orden']) ? max(1, (int)$img['orden']) : $nextOrder++;
            $stmt->execute([
                ':pid'       => $productId,
                ':ruta'      => $ruta,
                ':alt'       => $img['alt_text'] ?? null,
                ':principal' => !empty($img['es_principal']) ? 1 : 0,
                ':orden'     => $orden,
            ]);
        }
    }

    /**
     * Obtiene imágenes de un producto (principal primero).
     */
    public function getImagesByProduct(int $productId): array
    {
        $sql = "SELECT id_imagen, ruta, alt_text, es_principal, orden, creado_en
                FROM producto_imagenes
                WHERE producto_id = :pid
                ORDER BY es_principal DESC, orden ASC, id_imagen ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $productId]);
        return $stmt->fetchAll();
    }

    /**
     * Fija una imagen como principal (transacción).
     * Reordena esa imagen a orden 1 si ya estaba más abajo.
     */
    public function setPrimaryImage(int $productId, int $imageId): bool
    {
        $this->db->beginTransaction();
        try {
            // Validar pertenencia
            $chk = $this->db->prepare("SELECT id_imagen, orden FROM producto_imagenes WHERE id_imagen = :iid AND producto_id = :pid LIMIT 1");
            $chk->execute([':iid' => $imageId, ':pid' => $productId]);
            $row = $chk->fetch();
            if (!$row) {
                throw new RuntimeException('Imagen no encontrada para el producto.');
            }

            $this->db->prepare("UPDATE producto_imagenes SET es_principal = 0 WHERE producto_id = :pid")
                ->execute([':pid' => $productId]);

            $this->db->prepare("UPDATE producto_imagenes SET es_principal = 1 WHERE id_imagen = :iid")
                ->execute([':iid' => $imageId]);

            // Llevar a orden 1 para consistencia visual
            if ((int)$row['orden'] !== 1) {
                $this->db->prepare("UPDATE producto_imagenes SET orden = 1 WHERE id_imagen = :iid")
                    ->execute([':iid' => $imageId]);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Agrega una imagen individual.
     */
    public function addImage(int $productId, string $ruta, ?string $altText = null, bool $isPrincipal = false, ?int $orden = null): int
    {
        if ($productId <= 0 || trim($ruta) === '') {
            throw new InvalidArgumentException('Parámetros de imagen inválidos.');
        }

        $this->db->beginTransaction();
        try {
            if ($isPrincipal) {
                $this->db->prepare("UPDATE producto_imagenes SET es_principal = 0 WHERE producto_id = :pid")
                    ->execute([':pid' => $productId]);
            }

            if ($orden === null) {
                $orden = $this->getNextImageOrder($productId);
            } else {
                $orden = max(1, (int)$orden);
            }

            $stmt = $this->db->prepare(
                "INSERT INTO producto_imagenes (producto_id, ruta, alt_text, es_principal, orden)
                 VALUES (:pid, :ruta, :alt, :principal, :orden)"
            );
            $stmt->execute([
                ':pid'       => $productId,
                ':ruta'      => $ruta,
                ':alt'       => $altText,
                ':principal' => $isPrincipal ? 1 : 0,
                ':orden'     => $orden,
            ]);

            $newId = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $newId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Elimina una imagen por ID.
     */
    public function deleteImage(int $imageId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM producto_imagenes WHERE id_imagen = :iid");
        return $stmt->execute([':iid' => $imageId]);
    }

    /**
     * Obtiene el siguiente valor de orden para imágenes de un producto.
     */
    private function getNextImageOrder(int $productId): int
    {
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(orden), 0) + 1 AS nextOrd FROM producto_imagenes WHERE producto_id = :pid");
        $stmt->execute([':pid' => $productId]);
        $row = $stmt->fetch();
        return (int)($row['nextOrd'] ?? 1);
    }

    /* ============================
    *        ADMIN KPIs
    * ============================ */
    public function getKpis(): array {
        // publicados con stock, total productos, total marcas, imágenes
        $published = $this->db->query("SELECT COUNT(*) FROM v_productos_publicados")->fetchColumn() ?: 0;
        $totalProd = $this->db->query("SELECT COUNT(*) FROM productos")->fetchColumn() ?: 0;
        $marcas    = $this->db->query("SELECT COUNT(DISTINCT marca) FROM productos WHERE marca IS NOT NULL AND marca<>''")->fetchColumn() ?: 0;
        $images    = $this->db->query("SELECT COUNT(*) FROM producto_imagenes")->fetchColumn() ?: 0;

        return [
            'publicados' => (int)$published,
            'productos'  => (int)$totalProd,
            'marcas'     => (int)$marcas,
            'imagenes'   => (int)$images,
        ];
    }

    public function getLowStock(int $threshold = 3): array {
        $stmt = $this->db->prepare("
            SELECT id_producto, sku, nombre, marca, cantidad
            FROM productos
            WHERE cantidad <= :t
            ORDER BY cantidad ASC, actualizado_en DESC
            LIMIT 10
        ");
        $stmt->execute([':t' => $threshold]);
        return $stmt->fetchAll();
    }

    public function getRecentProducts(int $limit = 8): array {
        $stmt = $this->db->prepare("
            SELECT p.id_producto, p.nombre, p.marca, p.precio, p.cantidad, e.nombre AS estado
            FROM productos p
            JOIN estados e ON e.id_estado = p.estado_id
            ORDER BY p.actualizado_en DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function setProductEstadoByNombre(int $productId, string $estadoNombre): bool {
        $stmt = $this->db->prepare("
            UPDATE productos SET estado_id = (SELECT id_estado FROM estados WHERE nombre = :n LIMIT 1)
            WHERE id_producto = :id
        ");
        return $stmt->execute([':n' => $estadoNombre, ':id' => $productId]);
    }

    public function deleteProduct(int $productId): bool {
        // Borra producto y sus imágenes (FK ON DELETE CASCADE ya las elimina)
        $stmt = $this->db->prepare("DELETE FROM productos WHERE id_producto = :id");
        return $stmt->execute([':id' => $productId]);
    }

}
