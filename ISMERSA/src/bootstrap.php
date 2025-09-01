<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

define('BASE_PATH', dirname(__DIR__));         // …/ISMERSA
define('PUBLIC_PATH', BASE_PATH . '/public');  // …/ISMERSA/public
define('SRC_PATH',    BASE_PATH . '/src');

require BASE_PATH . '/db/DatabaseManager.php';
require SRC_PATH   . '/lib/helpers.php';

// Controladores
require SRC_PATH . '/controllers/SiteController.php';
require SRC_PATH . '/controllers/AuthController.php';
require SRC_PATH . '/controllers/Admin/ProductController.php';
