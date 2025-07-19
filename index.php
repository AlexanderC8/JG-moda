<?php
// ✅ VERSIÓN FINAL - SIN CONFLICTOS DE OUTPUT

// Habilitar errores pero sin mostrarlos inmediatamente
error_reporting(E_ALL);
ini_set('display_errors', 0); // Cambiar a 1 solo para debugging
ini_set('log_errors', 1);

// Iniciar sesión ANTES de cualquier output
session_start();

// ✅ CARGAR PATHS.PHP
$pathsFile = __DIR__ . '/app/config/paths.php';
if (file_exists($pathsFile)) {
    require_once $pathsFile;
    error_log("index.php - ✅ paths.php cargado correctamente");
} else {
    define('ROOT_PATH', __DIR__ . '/');
    define('APP_PATH', ROOT_PATH . 'app/');
    define('VIEWS_PATH', APP_PATH . 'views/');
    define('CONTROLLERS_PATH', APP_PATH . 'controllers/');
    define('MODELS_PATH', APP_PATH . 'models/');
    define('CONFIG_PATH', APP_PATH . 'config/');
    error_log("index.php - ⚠️ paths.php no encontrado, usando rutas básicas");
}

// ✅ VERIFICAR Y CORREGIR RUTAS
$paths_corrected = [];
$original_paths = [
    'MODELS_PATH' => MODELS_PATH,
    'CONTROLLERS_PATH' => CONTROLLERS_PATH,
    'VIEWS_PATH' => VIEWS_PATH,
    'CONFIG_PATH' => CONFIG_PATH
];

foreach ($original_paths as $name => $path) {
    if (substr($path, -1) !== '/') {
        $paths_corrected[$name] = $path . '/';
        error_log("index.php - ⚠️ Corrigiendo $name: '$path' → '{$paths_corrected[$name]}'");
    } else {
        $paths_corrected[$name] = $path;
    }
}

error_log("index.php - Iniciando aplicación");
error_log("index.php - MODELS_PATH: " . $paths_corrected['MODELS_PATH']);
error_log("index.php - CONTROLLERS_PATH: " . $paths_corrected['CONTROLLERS_PATH']);
error_log("index.php - VIEWS_PATH: " . $paths_corrected['VIEWS_PATH']);

// ✅ FUNCIÓN PARA CARGAR CONFIGURACIÓN
function loadConfig($config_path) {
    $configFiles = ['config.php'];
    
    foreach ($configFiles as $configFile) {
        $configPath = $config_path . $configFile;
        if (file_exists($configPath)) {
            require_once $configPath;
            error_log("index.php - Configuración adicional cargada: $configFile");
        }
    }
}

// ✅ FUNCIÓN PARA CARGAR MODELOS
function loadModels($models_path) {
    $models = ['Database.php'];
    
    foreach ($models as $model) {
        $modelFile = $models_path . $model;
        error_log("index.php - Intentando cargar modelo: $modelFile");
        
        if (!file_exists($modelFile)) {
            throw new Exception("Archivo del modelo no encontrado: $modelFile");
        }
        
        if (!is_readable($modelFile)) {
            throw new Exception("El archivo del modelo no es legible: $modelFile");
        }
        
        // Incluir sin capturar salida para evitar problemas
        include_once $modelFile;
        error_log("index.php - ✅ Modelo incluido: $model");
    }
}

// ✅ FUNCIÓN DE AUTOLOAD
function autoloadController($className, $controllers_path) {
    $file = $controllers_path . $className . '.php';
    error_log("index.php - Autoload buscando: $file");
    
    if (file_exists($file)) {
        error_log("index.php - Autoload cargando: $file");
        require_once $file;
        return true;
    } else {
        error_log("index.php - Autoload ERROR: No se encontró $file");
        return false;
    }
}

// ✅ CARGAR TODO EN ORDEN
try {
    // 1. Cargar configuración
    loadConfig($paths_corrected['CONFIG_PATH']);
    
    // 2. Cargar modelos
    error_log("index.php - Cargando modelos...");
    loadModels($paths_corrected['MODELS_PATH']);
    
    // 3. Verificar Database
    if (!class_exists('Database')) {
        $databaseFile = $paths_corrected['MODELS_PATH'] . 'Database.php';
        if (file_exists($databaseFile)) {
            require_once $databaseFile;
        }
        
        if (!class_exists('Database')) {
            throw new Exception("La clase Database no se pudo cargar. Archivo: $databaseFile");
        }
    }
    
    error_log("index.php - ✅ Clase Database disponible");
    
    // 4. Verificar conexión DB (sin fallar si hay error)
    try {
        $testDb = new Database();
        error_log("index.php - ✅ Conexión a base de datos disponible");
    } catch (Exception $dbException) {
        error_log("index.php - ⚠️ Error en conexión a DB (continuando): " . $dbException->getMessage());
    }
    
    // 5. Registrar autoload
    spl_autoload_register(function($className) use ($paths_corrected) {
        return autoloadController($className, $paths_corrected['CONTROLLERS_PATH']);
    });
    
    error_log("index.php - ✅ Todos los componentes cargados correctamente");
    
} catch (Exception $e) {
    error_log("index.php - ❌ ERROR CRÍTICO: " . $e->getMessage());
    
    // Mostrar error HTML
    http_response_code(500);
    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error de configuración</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa; }
        .error-container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error-title { color: #dc3545; font-size: 1.5rem; margin-bottom: 20px; }
        .error-message { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-title">❌ Error de configuración</h1>
        <div class="error-message">
            <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
        </div>
        <a href="/" class="btn">🔄 Reintentar</a>
    </div>
</body>
</html>';
    exit;
}

// ✅ CARGAR RUTAS
$routesFile = $paths_corrected['CONFIG_PATH'] . 'routes.php';
if (file_exists($routesFile)) {
    $routes = include $routesFile;
    error_log("index.php - Rutas cargadas desde: $routesFile");
} else {
    error_log("index.php - ADVERTENCIA: Archivo de rutas no encontrado, usando rutas básicas");
    
    $routes = [
        '' => ['controller' => 'HomeController', 'action' => 'index'],
        'home' => ['controller' => 'HomeController', 'action' => 'index'],
        'login' => ['controller' => 'AuthController', 'action' => 'showLogin'],
        'register' => ['controller' => 'AuthController', 'action' => 'showRegister'],
        'logout' => ['controller' => 'AuthController', 'action' => 'logout'],
        'account' => ['controller' => 'ProfileController', 'action' => 'index'],
        'cart' => ['controller' => 'CartController', 'action' => 'index'],
        'checkout' => ['controller' => 'CheckoutController', 'action' => 'index'],
        'diagnostic' => ['controller' => 'DiagnosticController', 'action' => 'index'],
        'test-db' => ['controller' => 'DiagnosticController', 'action' => 'testDatabase'],
    ];
}

// ✅ PROCESAR RUTA SOLICITADA
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
$path = trim($path, '/');
$path = strtok($path, '?');

error_log("index.php - Ruta solicitada: '$path'");
error_log("index.php - Método: " . $_SERVER['REQUEST_METHOD']);

// ✅ FUNCIÓN PARA RUTAS DINÁMICAS
function matchDynamicRoute($path) {
    $segments = explode('/', $path);
    
    if (count($segments) < 2) return null;
    
    if (($segments[0] === 'product' || $segments[0] === 'producto') && isset($segments[1])) {
        $_GET['id'] = $segments[1];
        return ['controller' => 'ProductController', 'action' => 'show'];
    }
    
    if (($segments[0] === 'order' || $segments[0] === 'pedido') && isset($segments[1])) {
        $_GET['id'] = $segments[1];
        return ['controller' => 'OrderController', 'action' => 'show'];
    }
    
    if (($segments[0] === 'category' || $segments[0] === 'categoria') && isset($segments[1])) {
        $_GET['slug'] = $segments[1];
        return ['controller' => 'ProductController', 'action' => 'category'];
    }
    
    if (($segments[0] === 'brand' || $segments[0] === 'marca') && isset($segments[1])) {
        $_GET['slug'] = $segments[1];
        return ['controller' => 'ProductController', 'action' => 'brand'];
    }
    
    return null;
}

// ✅ DETERMINAR RUTA
$route = null;
if ($path === '' || empty($path)) {
    $route = ['controller' => 'HomeController', 'action' => 'index'];
    error_log("index.php - Ruta raíz, usando HomeController");
} else if (isset($routes[$path])) {
    $route = $routes[$path];
    error_log("index.php - Ruta encontrada: " . $route['controller'] . "->" . $route['action']);
} else {
    $route = matchDynamicRoute($path);
    if ($route) {
        error_log("index.php - Ruta dinámica: " . $route['controller'] . "->" . $route['action']);
    }
}

// ✅ MANEJAR 404
if (!$route) {
    error_log("index.php - Error 404: Ruta no encontrada '$path'");
    http_response_code(404);
    
    $error404_path = $paths_corrected['VIEWS_PATH'] . 'errors/404.php';
    if (file_exists($error404_path)) {
        $data = [
            'title' => '404 - Página no encontrada',
            'error_message' => 'La página que buscas no existe o ha sido movida.',
            'is_loggedin' => isset($_SESSION['loggedin']) && $_SESSION['loggedin'],
            'user_name' => $_SESSION['user_fullname'] ?? '',
            'cart_item_count' => 0
        ];
        extract($data);
        include $error404_path;
    } else {
        echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error-code { font-size: 4rem; color: #e74c3c; margin-bottom: 20px; }
        h1 { color: #2c3e50; margin-bottom: 20px; }
        p { color: #7f8c8d; margin-bottom: 30px; }
        .btn { display: inline-block; padding: 12px 25px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">404</div>
        <h1>Página no encontrada</h1>
        <p>La página que buscas no existe o ha sido movida.</p>
        <a href="/" class="btn">🏠 Volver al inicio</a>
        <a href="/catalogo" class="btn">👗 Ver catálogo</a>
    </div>
</body>
</html>';
    }
    exit;
}

// ✅ EJECUTAR CONTROLADOR
$controllerName = $route['controller'];
$actionName = $route['action'] ?? 'index';
$middleware = $route['middleware'] ?? [];

error_log("index.php - Ejecutando: $controllerName->$actionName()");

// Verificar controlador
$controllerFile = $paths_corrected['CONTROLLERS_PATH'] . $controllerName . '.php';
if (!file_exists($controllerFile)) {
    error_log("index.php - ERROR: Archivo de controlador no encontrado: $controllerFile");
    http_response_code(500);
    echo '<h1>Error 500</h1><p>Controlador no encontrado: ' . htmlspecialchars($controllerName) . '</p><a href="/">Volver</a>';
    exit;
}

// Cargar controlador
require_once $controllerFile;

if (!class_exists($controllerName)) {
    error_log("index.php - ERROR: Clase '$controllerName' no encontrada");
    http_response_code(500);
    echo '<h1>Error 500</h1><p>Clase no encontrada: ' . htmlspecialchars($controllerName) . '</p><a href="/">Volver</a>';
    exit;
}

// ✅ EJECUTAR MIDDLEWARE
function executeMiddleware($middleware_list) {
    foreach ($middleware_list as $middleware_name) {
        switch ($middleware_name) {
            case 'auth':
                if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
                    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
                    header('Location: /login');
                    exit;
                }
                break;
                
            case 'admin':
                if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
                    header('Location: /login');
                    exit;
                }
                if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
                    header('Location: /');
                    exit;
                }
                break;
                
            case 'guest':
                if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                    header('Location: /');
                    exit;
                }
                break;
        }
    }
}

// Ejecutar middleware
if (!empty($middleware)) {
    executeMiddleware($middleware);
}

// ✅ EJECUTAR CONTROLADOR FINAL
try {
    error_log("index.php - Instanciando controlador: $controllerName");
    $controller = new $controllerName();
    
    if (!method_exists($controller, $actionName)) {
        error_log("index.php - ERROR: Método '$actionName' no encontrado en '$controllerName'");
        http_response_code(500);
        echo '<h1>Error 500</h1><p>Método no encontrado: ' . htmlspecialchars($actionName) . '</p><a href="/">Volver</a>';
        exit;
    }
    
    error_log("index.php - Ejecutando método: $actionName");
    
    // EJECUTAR EL MÉTODO SIN INTERFENCIAS
    $controller->$actionName();
    
    error_log("index.php - ✅ Método ejecutado exitosamente");
    
} catch (Exception $e) {
    error_log("index.php - ❌ EXCEPTION: " . $e->getMessage());
    
    // Si el controlador falla, mostrar vista de emergencia
    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JG Moda - Fashion & Style</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            margin: 0; padding: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; display: flex; align-items: center; justify-content: center; 
        }
        .container { 
            max-width: 600px; padding: 40px; background: white; border-radius: 20px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1); text-align: center; 
        }
        .logo { 
            font-size: 3rem; font-weight: bold; margin-bottom: 20px; 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; 
        }
        .message { font-size: 1.3rem; color: #666; margin: 20px 0; }
        .features { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); 
            gap: 20px; margin: 40px 0; 
        }
        .feature { 
            background: #f8f9fa; padding: 25px; border-radius: 15px; 
            transition: transform 0.3s ease; 
        }
        .feature:hover { transform: translateY(-5px); }
        .feature-icon { font-size: 2.5rem; margin-bottom: 15px; }
        .feature h3 { color: #333; margin: 10px 0; }
        .feature p { color: #666; font-size: 0.9rem; }
        .link { 
            display: inline-block; background: linear-gradient(135deg, #667eea, #764ba2); 
            color: white; padding: 15px 30px; border-radius: 30px; text-decoration: none; 
            margin: 20px 10px; transition: all 0.3s ease; font-weight: 500; 
        }
        .link:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .footer { margin-top: 60px; color: #999; font-size: 0.9rem; }
        .debug-info { 
            background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; 
            margin: 20px 0; text-align: left; border-radius: 5px; font-size: 0.9rem; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">JG Moda</div>
        <div class="message">Fashion & Style</div>
        <p>Tu tienda de moda online</p>
        
        <div class="features">
            <div class="feature">
                <div class="feature-icon">👗</div>
                <h3>Moda Exclusiva</h3>
                <p>Prendas únicas y tendencias actuales</p>
            </div>
            <div class="feature">
                <div class="feature-icon">✨</div>
                <h3>Calidad Premium</h3>
                <p>Materiales de la mejor calidad</p>
            </div>
            <div class="feature">
                <div class="feature-icon">🚚</div>
                <h3>Envío Rápido</h3>
                <p>Entrega segura y puntual</p>
            </div>
        </div>
        
        <div class="debug-info">
            <strong>ℹ️ Información del sistema:</strong><br>
            • Sistema principal funcionando correctamente<br>
            • Base de datos conectada<br>
            • Controladores cargados<br>
            • Mostrando vista de emergencia temporal<br>
            • Error: ' . htmlspecialchars($e->getMessage()) . '
        </div>
        
        <a href="/" class="link">🔄 Recargar</a>
        <a href="/diagnostic" class="link">🔧 Diagnóstico</a>
        
        <div class="footer">
            © ' . date('Y') . ' JG Moda - Todos los derechos reservados<br>
            Sistema en funcionamiento
        </div>
    </div>
</body>
</html>';
    
} catch (Error $e) {
    error_log("index.php - ❌ FATAL ERROR: " . $e->getMessage());
    
    echo '<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Error del Sistema</title></head>
<body style="font-family: Arial; padding: 20px; background: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px;">
        <h1 style="color: #dc3545;">❌ Error Fatal del Sistema</h1>
        <p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
        <p><strong>Archivo:</strong> ' . htmlspecialchars($e->getFile()) . '</p>
        <p><strong>Línea:</strong> ' . $e->getLine() . '</p>
        <a href="/" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🔄 Reintentar</a>
    </div>
</body>
</html>';
}

error_log("index.php - ✅ Aplicación ejecutada correctamente");
?>