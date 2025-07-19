<?php
// update_image_paths.php - Ejecutar UNA VEZ para corregir rutas existentes
require_once 'app/Models/Database.php';
require_once 'UploadHelper.php';

echo "<h1>🔧 Actualización de Rutas de Imágenes</h1>";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h2>1. Verificando productos existentes...</h2>";
    
    // Obtener todos los productos
    $stmt = $conn->prepare("SELECT id, name, cover_image_url FROM products ORDER BY id");
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    echo "Productos encontrados: " . count($products) . "<br><br>";
    
    echo "<h2>2. Analizando rutas de imágenes...</h2>";
    
    $updated = 0;
    $errors = 0;
    
    foreach ($products as $product) {
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px;'>";
        echo "<strong>Producto #{$product['id']}: {$product['name']}</strong><br>";
        echo "Imagen actual en BD: <code>{$product['cover_image_url']}</code><br>";
        
        // Si ya tiene la ruta completa, saltar
        if (strpos($product['cover_image_url'], 'uploads/') === 0) {
            echo "✅ Ya tiene ruta completa<br>";
        } else {
            // Buscar la ruta correcta
            $correctPath = UploadHelper::findImagePath($product['cover_image_url']);
            echo "Ruta corregida: <code>$correctPath</code><br>";
            
            if ($correctPath !== 'assets/img/no-image.jpg') {
                // Actualizar en BD
                try {
                    $updateStmt = $conn->prepare("UPDATE products SET cover_image_url = ? WHERE id = ?");
                    $updateStmt->execute([$correctPath, $product['id']]);
                    echo "✅ <span style='color: green;'>Actualizado exitosamente</span><br>";
                    $updated++;
                } catch (Exception $e) {
                    echo "❌ <span style='color: red;'>Error: {$e->getMessage()}</span><br>";
                    $errors++;
                }
            } else {
                echo "⚠️ <span style='color: orange;'>Imagen no encontrada</span><br>";
                $errors++;
            }
        }
        
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<h2>📊 Resumen:</h2>";
    echo "✅ Productos actualizados: <strong>$updated</strong><br>";
    echo "❌ Errores: <strong>$errors</strong><br>";
    echo "📁 Total procesados: <strong>" . count($products) . "</strong><br>";
    
    if ($updated > 0) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "🎉 <strong>¡Actualización completada!</strong><br>";
        echo "Las rutas de las imágenes han sido corregidas. Ahora tu catálogo debería mostrar las imágenes correctamente.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<h2>🔍 Verificación Manual:</h2>";
echo "<p>Puedes verificar manualmente las carpetas:</p>";
echo "<ul>";
echo "<li><code>/uploads/products/2025/07/</code></li>";
echo "<li><code>/admin/assets/uploads/</code></li>";
echo "<li><code>/uploads/</code> (archivos antiguos)</li>";
echo "</ul>";

echo "<p><strong>Siguiente paso:</strong> Una vez ejecutado este script, elimínalo por seguridad.</p>";
?>