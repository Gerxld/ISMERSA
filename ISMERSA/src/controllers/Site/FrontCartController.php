<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../db/DatabaseManager.php';
require_once __DIR__ . '/../../lib/helpers.php';

final class FrontCartController
{
    private DatabaseManager $db;

    public function __construct(?DatabaseManager $db = null)
    {
        $this->db = $db ?: new DatabaseManager();
    }

    /** ViewModel del carrito para la vista pública */
    public function getCartViewModel(): array
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $items = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];

        $subtotal = 0.0;
        foreach ($items as $it) {
            $subtotal += ((float)$it['precio']) * ((int)$it['qty']);
        }

        return [
            'items'    => $items,
            'subtotal' => $subtotal,
            // si luego quieres impuestos/envíos, se agregan aquí
        ];
    }

    /** Añadir al carrito (ya lo tenías) */
    public function addFromPost(array $post): string
    {
        $token = (string)($post['csrf'] ?? '');
        check_csrf($token);

        $id  = (int)($post['id']  ?? 0);
        $qty = max(1, (int)($post['qty'] ?? 1));
        if ($id <= 0) throw new RuntimeException('ID inválido');

        $prod = $this->db->getProductById($id);
        if (!$prod) throw new RuntimeException('Producto no encontrado');

        $max = max(1, (int)$prod['cantidad']);
        $qty = min($qty, $max);

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $img = '../uploads/imagenes/placeholder.jpg';
        if (!empty($prod['imagenes'])) {
            $img = $prod['imagenes'][0]['ruta'];
        }

        if (!isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] = [
                'id'     => $id,
                'nombre' => $prod['nombre'],
                'precio' => (float)$prod['precio'],
                'qty'    => 0,
                'img'    => $img,
            ];
        }
        $_SESSION['cart'][$id]['qty'] = min($max, (int)$_SESSION['cart'][$id]['qty'] + $qty);

        return public_url('detalle_producto.php') . '?id=' . $id . '&added=1';
    }

    /** Eliminar un item del carrito (por POST con CSRF) */
    public function removeFromPost(array $post): string
    {
        $token = (string)($post['csrf'] ?? '');
        check_csrf($token);

        $id = (int)($post['id'] ?? 0);
        if ($id <= 0) throw new RuntimeException('ID inválido');

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!empty($_SESSION['cart'][$id])) {
            unset($_SESSION['cart'][$id]);
        }

        return public_url('carrito.php') . '?removed=1';
    }
}
