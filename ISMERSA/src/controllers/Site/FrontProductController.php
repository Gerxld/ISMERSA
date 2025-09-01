<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../db/DatabaseManager.php';
require_once __DIR__ . '/../../lib/helpers.php';

final class FrontProductController
{
    private DatabaseManager $db;

    public function __construct(?DatabaseManager $db = null)
    {
        $this->db = $db ?: new DatabaseManager();
    }

    /**
     * ViewModel para la ficha de producto (solo datos, sin HTML).
     * @throws InvalidArgumentException si el id es inválido
     * @throws RuntimeException si el producto no existe
     */
    public function getDetailViewModel(int $id): array
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID inválido');
        }

        $producto = $this->db->getProductById($id);
        if (!$producto) {
            throw new RuntimeException('Producto no encontrado');
        }

        $imagenes = $producto['imagenes'] ?? [];
        $imgPrincipal = '../uploads/imagenes/placeholder.jpg';
        if (!empty($imagenes)) {
            $imgPrincipal = $imagenes[0]['ruta']; // orden: principal primero
        }

        return [
            'producto'     => $producto,
            'imagenes'     => $imagenes,
            'imgPrincipal' => $imgPrincipal,
        ];
    }
}
