<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/../db/conexionBD.php';

try {
    $pdo = ConexionBD::getPDO();
    echo "<h2>✅ Conectado</h2>";
    echo "<pre>";
    echo "Servidor: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . PHP_EOL;
    echo "Driver  : " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . PHP_EOL;
    echo "</pre>";
} catch (Throwable $e) {
    echo "<h2>❌ Error</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
