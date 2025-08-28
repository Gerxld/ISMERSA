<?php
declare(strict_types=1);

final class ConexionBD
{
    private static ?PDO $pdo = null;



    public static function getPDO(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = getenv('ISMERSA_DB_HOST') ?: '127.0.0.1';
        $port = (int)(getenv('ISMERSA_DB_PORT') ?: '3306');
        $dbname = getenv('ISMERSA_DB_NAME') ?: 'ismersa';
        $user = getenv('ISMERSA_DB_USER') ?: 'root';
        $pass = getenv('ISMERSA_DB_PASS') ?: 'Iamperra507d';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        // Opciones seguras
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone = '+00:00'"
        ];

        try {
            self::$pdo = new PDO($dsn, $user, $pass, $options);
            return self::$pdo;
        } catch (PDOException $e) {
            
            http_response_code(500);
            exit('Error de conexión a la base de datos.');
        }
    }

    /**
     * Cierra la conexión (opcional).
     */
    public static function close(): void
    {
        self::$pdo = null;
    }

    private function __construct() {}
    private function __clone() {}
}
