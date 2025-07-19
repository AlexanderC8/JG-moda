<?php
// HERRAMIENTA DE DEBUG PARA ADMIN
// Crear este archivo como: htdocs/admin/debug.php
// Acceder en: https://jg-moda.great-site.net/admin/debug.php

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.debug-section { background: white; margin: 20px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.debug-title { color: #333; border-bottom: 2px solid #007cba; padding-bottom: 10px; margin-bottom: 15px; }
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.warning { color: #ffc107; font-weight: bold; }
.path { background: #f8f9fa; padding: 5px 10px; border-radius: 4px; font-family: monospace; }
pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
</style>";

echo "<h1>🔍 DEBUG DE RUTAS - ADMIN JG MODA</h1>";

// 1. INFORMACIÓN DEL SERVIDOR
echo "<div class='debug-section'>";
echo "<h2 class='debug-title'>📡 Información del Servidor</h2>";
echo "<strong>URL Actual:</strong> <span class='path'>" . $_SERVER['REQUEST_URI'] . "</span><br>";
echo "<strong>Documento Root:</strong> <span class='path'>" . $_SERVER['DOCUMENT_ROOT'] . "</span><br>";
echo "<strong>Script Actual:</strong> <span class='path'>" . __FILE__ . "</span><br>";
echo "<strong>Directorio Actual:</strong> <span class='path'>" . __DIR__ . "</span><br>";
echo "</div>";

// 2. VERIFICAR ESTRUCTURA DE CARPETAS
echo "<div class='debug-section'>";
echo "<h2 class='debug-title'>📁 Estructura de Carpetas</h2>";

$paths_to_check = [
    'Admin principal (mayúscula)' => $_SERVER['DOCUMENT_ROOT'] . '/Admin',
    'admin nuevo (minúscula)' => $_SERVER['DOCUMENT_ROOT'] . '/admin',
    'htdocs/Admin' => __DIR__ . '/../Admin',
    'htdocs/admin' => __DIR__ . '/../admin',
    'Carpeta actual (admin)' => __DIR__,
];

foreach ($paths_to_check as $name => $path) {
    if (is_dir($path)) {
        echo "✅ <span class='success'>EXISTE</span> - $name: <span class='path'>$path</span><br>";
    } else {
        echo "❌ <span class='error'>NO EXISTE</span> - $name: <span class='path'>$path</span><br>";
    }
}
echo "</div>";

// 3. VERIFICAR ARCHIVOS ESPECÍFICOS
echo "<div class='debug-section'>";
echo "<h2 class='debug-title'>📄 Archivos Específicos</h2>";

$files_to_check = [
    'index.php admin' => __DIR__ . '/index.php',
    '.htaccess admin' => __DIR__ . '/.htaccess',
    'BaseController' => __DIR__ . '/app/Controllers/BaseController.php',
    'AuthController' => __DIR__ . '/app/Controllers/AuthController.php',
    'Vista login' => __DIR__ . '/app/Views/auth/login.php',
    'CSS admin (Admin/assets)' => $_SERVER['DOCUMENT_ROOT'] . '/Admin/assets/css/admin_login.css',
    'CSS admin (admin/public)' => __DIR__ . '/public/assets/css/admin_login.css',
];

foreach ($files_to_check as $name => $path) {
    if (file_exists($path)) {
        echo "✅ <span class='success'>EXISTE</span> - $name: <span class='path'>$path</span><br>";
    } else {
        echo "❌ <span class='error'>NO EXISTE</span> - $name: <span class='path'>$path</span><br>";
    }
}
echo "</div>";

// 4. VERIFICAR PERMISOS
echo "<div class='debug-section'>";
echo "<h2 class='debug-title'>🔒 Permisos de Archivos</h2>";

$permission_files = [
    'Carpeta admin' => __DIR__,
    'index.php' => __DIR__ . '/index.php',
    '.htaccess' => __DIR__ . '/.htaccess',
];

foreach ($permission_files as $name => $path) {
    if (file_exists($path)) {
        $perms = fileperms($path);
        $perms_str = substr(sprintf('%o', $perms), -4);
        echo "$name: <span class='path'>$perms_str</span> ";
        
        if (is_readable($path)) {
            echo "<span class='success'>LEGIBLE</span> ";
        } else {
            echo "<span class='error'>NO LEGIBLE</span> ";
        }
        
        if (is_dir($path) && is_executable($path)) {
            echo "<span class='success'>EJECUTABLE</span>";
        } elseif (is_file($path)) {
            echo "<span class='success'>ARCHIVO OK</span>";
        }
        echo "<br>";
    }
}
echo "</div>";

// 5. RUTAS CSS POSIBLES
echo "<div class='debug-section'>";
echo "<h2 class='debug-title'>🎨 Rutas CSS Posibles</h2>";

$css_paths = [
    'Absoluta Admin' => '/Admin/assets/css/admin_login.css',
    'Relativa ../Admin' => '../Admin/assets/css/admin_login.css',
    'Relativa ../../Admin' => '../../Admin/assets/css/admin_login.css',
    'Relativa ../../../Admin' => '../../../Admin/assets/css/admin_login.css',
    'Relativa ../../../../Admin' => '../../../../Admin/assets/css/admin_login.css',
    'Public assets' => 'public/assets/css/admin_login.css',
];

echo "<strong>Desde la vista:</strong> <span class='path'>/admin/app/Views/auth/login.php</span><br><br>";

foreach ($css_paths as $name => $relative_path) {
    // Calcular la ruta absoluta para verificar
    $base_path = $_SERVER['DOCUMENT_ROOT'];
    if (strpos($relative_path, '/') === 0) {
        // Ruta absoluta
        $full_path = $base_path . $relative_path;
    } else {
        // Ruta relativa desde la vista
        $view_dir = $base_path . '/admin/app/Views/auth/';
        $full_path = realpath($view_dir . $relative_path);
    }
    
    echo "<strong>$name:</strong> <span class='path'>$relative_path</span> → ";
    if ($full_path && file_exists($full_path)) {
        echo "<span class='success'>✅ EXISTE</span>";
    } else {
        echo "<span class='error'>❌ NO EXISTE</span>";
    }
    echo "<br>";
}
echo "</div>";

// 6. CONTENIDO DEL .HTACCESS
echo "<div class='debug-section'>";
echo "<h2 class='debug-title'>⚙️ Contenido del .htaccess</h2>";

$htaccess_path = __DIR__ . '/.htaccess';
if (file_exists($htaccess_path)) {
    echo "<strong>Archivo:</strong> <span class='path'>$htaccess_path</span><br>";
    echo "<pre>" . htmlspecialchars(file_get_contents($htaccess_path)) . "</pre>";
} else {
    echo "<span class='error'>❌ .htaccess no encontrado</span>";
}
echo "</div>";

// 7. VARIABLES DE SESIÓN
echo "<div class='debug-section'>";
echo "<h2 class='debug-title'>🔐 Variables de Sesión</h2>";

session_start();
if (!empty($_SESSION)) {
    echo "<pre>" . htmlspecialchars(print_r($_SESSION, true)) . "</pre>";
} else {
    echo "<span class='warning'>⚠️ No hay variables de sesión</span>";
}
echo "</div>";

// 8. VARIABLES $_SERVER IMPORTANTES
echo "<div class='debug-section'>";
echo "<h2 class='debug-title'>🌐 Variables del Servidor</h2>";

$server_vars = [
    'HTTP_HOST', 'SERVER_NAME', 'REQUEST_URI', 'SCRIPT_NAME', 
    'REQUEST_METHOD', 'QUERY_STRING', 'DOCUMENT_ROOT'
];

foreach ($server_vars as $var) {
    echo "<strong>$var:</strong> <span class='path'>" . ($_SERVER[$var] ?? 'NO DEFINIDA') . "</span><br>";
}
echo "</div>";

echo "<div class='debug-section'>";
echo "<h2 class='debug-title'>🚀 Próximos Pasos Recomendados</h2>";
echo "<ol>";
echo "<li>Verifica que los archivos marcados con ❌ existan en las rutas correctas</li>";
echo "<li>Usa la ruta CSS que aparezca con ✅ EXISTE</li>";
echo "<li>Si hay problemas de permisos, ajústalos a 755 para carpetas y 644 para archivos</li>";
echo "<li>Si el .htaccess está vacío o mal configurado, reemplázalo</li>";
echo "</ol>";
echo "</div>";
?>