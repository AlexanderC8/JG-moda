<?php
// DEBUG TEMPORAL - Activar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Configuración básica del Admin
define('ADMIN_ROOT_PATH', __DIR__ . '/');
define('ADMIN_APP_PATH', ADMIN_ROOT_PATH . 'app/');
define('ADMIN_VIEWS_PATH', ADMIN_APP_PATH . 'Views/');
define('ADMIN_CONTROLLERS_PATH', ADMIN_APP_PATH . 'Controllers/');

// Función de autoload para controladores del admin
function autoloadAdminController($className) {
    $file = ADMIN_CONTROLLERS_PATH . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
}
spl_autoload_register('autoloadAdminController');

// Obtener la ruta solicitada (remover /admin del inicio)
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remover /admin del path para obtener la ruta relativa
$adminPath = '/admin';
if (strpos($path, $adminPath) === 0) {
    $path = substr($path, strlen($adminPath));
}

$path = trim($path, '/');
$path = strtok($path, '?');

// Si no hay path específico, ir al login
if (empty($path)) {
    $path = '';
}

// Rutas del admin
$routes = [
    '' => ['controller' => 'AuthController', 'method' => 'showLogin'],           // /admin/ → Login
    'login' => ['controller' => 'AuthController', 'method' => 'showLogin'],      // /admin/login → Login
    'dashboard' => ['controller' => 'DashboardController', 'method' => 'index'], // /admin/dashboard → Dashboard
    'logout' => ['controller' => 'AuthController', 'method' => 'logout'],
    'auth' => ['controller' => 'AuthController', 'method' => 'authenticate'],
    
    // Gestión de productos
    'products' => ['controller' => 'ProductsController', 'method' => 'index'],
    'products/create' => ['controller' => 'ProductsController', 'method' => 'create'],
    'products/edit' => ['controller' => 'ProductsController', 'method' => 'edit'],
    
    // Gestión de pedidos
    'orders' => ['controller' => 'OrderControllerManager', 'method' => 'index'],
    'orders/details' => ['controller' => 'OrderControllerManager', 'method' => 'details'],
    
    // Gestión de inventario
    'inventory' => ['controller' => 'InventoryController', 'method' => 'index'],
    'inventory/reports' => ['controller' => 'InventoryController', 'method' => 'reports'],
    
    // Gestión de cupones
    'coupons' => ['controller' => 'CouponController', 'method' => 'index'],
    'coupons/reports' => ['controller' => 'CouponController', 'method' => 'reports'],
    
    // Gestión de banners
    'banners' => ['controller' => 'BannerController', 'method' => 'index'],
    
    // Gastos
    'expenses' => ['controller' => 'ExpenseController', 'method' => 'index'],
    
    // Configuración
    'settings' => ['controller' => 'SettingsController', 'method' => 'index'],
    
    // Reportes de ventas
    'sales/reports' => ['controller' => 'SalesController', 'method' => 'reports'],
];

// Función para rutas dinámicas del admin
function matchAdminDynamicRoute($path) {
    $segments = explode('/', $path);
    
    // Rutas con ID: /products/edit/123, /orders/details/456
    if (count($segments) === 3 && is_numeric($segments[2])) {
        $_GET['id'] = $segments[2];
        
        switch ($segments[0]) {
            case 'products':
                if ($segments[1] === 'edit') {
                    return ['controller' => 'ProductsController', 'method' => 'edit'];
                }
                break;
            case 'orders':
                if ($segments[1] === 'details') {
                    return ['controller' => 'OrderControllerManager', 'method' => 'details'];
                }
                break;
        }
    }
    
    return null;
}

// Buscar ruta
$route = null;
if (isset($routes[$path])) {
    $route = $routes[$path];
} else {
    $route = matchAdminDynamicRoute($path);
}

// Si no se encuentra la ruta, mostrar 404
if (!$route) {
    http_response_code(404);
    echo '<h1>404 - Página no encontrada</h1><p>La página del admin que buscas no existe.</p><a href="/admin">Volver al login</a>';
    exit;
}

// Ejecutar controlador
$controllerName = $route['controller'];
$methodName = $route['method'];

if (!class_exists($controllerName)) {
    die("Error: Controlador de admin '$controllerName' no encontrado.<br>Archivo esperado: " . ADMIN_CONTROLLERS_PATH . $controllerName . '.php');
}

$controller = new $controllerName();

if (!method_exists($controller, $methodName)) {
    die("Error: Método '$methodName' no encontrado en '$controllerName'.");
}

try {
    $controller->$methodName();
} catch (Exception $e) {
    echo '<h1>Error del servidor (Admin)</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    error_log("Admin error: " . $e->getMessage());
}
?>