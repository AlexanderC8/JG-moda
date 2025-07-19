<?php
// fix_image_paths.php - Solución definitiva para las imágenes
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Corrección Final de Rutas de Imágenes</h1>";

try {
    require_once 'app/Models/Database.php';
    
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h2>📋 Paso 1: Verificando imágenes en /admin/assets/uploads/</h2>";
    
    // Ruta donde están realmente las imágenes
    $admin_uploads_path = $_SERVER['DOCUMENT_ROOT'] . '/admin/assets/uploads/';
    
    if (!is_dir($admin_uploads_path)) {
        die("❌ Error: No se encuentra el directorio $admin_uploads_path");
    }
    
    echo "✅ Directorio encontrado: <code>$admin_uploads_path</code><br>";
    
    // Obtener todas las imágenes del directorio admin
    $admin_images = glob($admin_uploads_path . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
    
    echo "<h3>🖼️ Imágenes encontradas en admin:</h3>";
    $admin_image_list = [];
    
    foreach ($admin_images as $image_path) {
        $filename = basename($image_path);
        $admin_image_list[] = $filename;
        echo "• <code>$filename</code><br>";
    }
    
    echo "<br><strong>Total imágenes en admin: " . count($admin_image_list) . "</strong><br>";
    
    echo "<h2>📋 Paso 2: Productos en base de datos</h2>";
    
    // Obtener productos de la BD
    $stmt = $conn->prepare("SELECT id, name, cover_image_url FROM products ORDER BY id");
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    echo "<h3>📊 Analizando productos:</h3>";
    
    $updated_count = 0;
    $not_found_count = 0;
    
    foreach ($products as $product) {
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<strong>Producto #{$product['id']}: {$product['name']}</strong><br>";
        echo "Imagen en BD: <code>{$product['cover_image_url']}</code><br>";
        
        $image_name = $product['cover_image_url'];
        
        // Verificar si la imagen existe en admin/assets/uploads/
        if (in_array($image_name, $admin_image_list)) {
            echo "✅ <span style='color: green;'>¡ENCONTRADA en admin/assets/uploads/!</span><br>";
            
            // Nueva ruta correcta
            $new_path = "admin/assets/uploads/" . $image_name;
            echo "Nueva ruta: <code>$new_path</code><br>";
            
            // Actualizar en BD
            try {
                $updateStmt = $conn->prepare("UPDATE products SET cover_image_url = ? WHERE id = ?");
                $updateStmt->execute([$new_path, $product['id']]);
                
                echo "🎉 <span style='color: green; font-weight: bold;'>¡ACTUALIZADO EXITOSAMENTE!</span><br>";
                $updated_count++;
                
            } catch (Exception $e) {
                echo "❌ <span style='color: red;'>Error actualizando: {$e->getMessage()}</span><br>";
            }
            
        } else {
            echo "❌ <span style='color: red;'>No encontrada</span><br>";
            
            // Buscar nombres similares
            echo "🔍 Buscando similares...<br>";
            $found_similar = false;
            
            foreach ($admin_image_list as $admin_image) {
                similar_text(strtolower($image_name), strtolower($admin_image), $percent);
                if ($percent > 70) {
                    echo "&nbsp;&nbsp;📸 Posible coincidencia: <code>$admin_image</code> (similitud: " . round($percent, 1) . "%)<br>";
                    $found_similar = true;
                }
            }
            
            if (!$found_similar) {
                echo "&nbsp;&nbsp;⚠️ No se encontraron coincidencias<br>";
            }
            
            $not_found_count++;
        }
        
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<h2>📊 RESUMEN FINAL</h2>";
    
    echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h3>✅ ¡CORRECCIÓN COMPLETADA!</h3>";
    echo "<ul>";
    echo "<li><strong>Productos actualizados:</strong> $updated_count</li>";
    echo "<li><strong>Productos sin imagen:</strong> $not_found_count</li>";
    echo "<li><strong>Total productos:</strong> " . count($products) . "</li>";
    echo "</ul>";
    echo "</div>";
    
    if ($updated_count > 0) {
        echo "<div style='background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>🎯 Siguiente Paso:</h4>";
        echo "<p>¡Las rutas han sido corregidas! Ahora tu catálogo debería mostrar las imágenes correctamente.</p>";
        echo "<p><strong>Ruta actualizada:</strong> <code>admin/assets/uploads/imagen.png</code></p>";
        echo "</div>";
        
        echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>💡 Recomendación:</h4>";
        echo "<p>Para futuras subidas, considera mover las imágenes a <code>/uploads/products/2025/07/</code> para mejor organización.</p>";
        echo "</div>";
    }
    
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>🚨 IMPORTANTE:</h4>";
    echo "<p>Una vez que confirmes que todo funciona, <strong>elimina este archivo</strong> por seguridad.</p>";
    echo "</div>";
    
    // Mostrar algunas imágenes de ejemplo para verificar
    if ($updated_count > 0) {
        echo "<h2>🖼️ Verificación Visual</h2>";
        echo "<p>Algunas imágenes actualizadas para verificar:</p>";
        
        $stmt = $conn->prepare("SELECT id, name, cover_image_url FROM products WHERE cover_image_url LIKE 'admin/assets/uploads/%' LIMIT 3");
        $stmt->execute();
        $sample_products = $stmt->fetchAll();
        
        foreach ($sample_products as $sample) {
            echo "<div style='display: inline-block; margin: 10px; text-align: center; border: 1px solid #ddd; padding: 10px; border-radius: 5px;'>";
            echo "<strong>{$sample['name']}</strong><br>";
            echo "<img src='/{$sample['cover_image_url']}' alt='{$sample['name']}' style='max-width: 150px; max-height: 150px; margin: 10px 0;' onerror=\"this.style.display='none'; this.nextSibling.style.display='block';\">";
            echo "<div style='display: none; color: red;'>❌ Error cargando imagen</div>";
            echo "<br><small><code>{$sample['cover_image_url']}</code></small>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ ERROR</h3>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}
?>