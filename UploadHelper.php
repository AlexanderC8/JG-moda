<?php
class UploadHelper {
    
    /**
     * Genera la ruta de upload organizizada por fecha
     */
    public static function generateUploadPath($type = 'products') {
        $year = date('Y');
        $month = date('m');
        return "uploads/{$type}/{$year}/{$month}/";
    }
    
    /**
     * Crea las carpetas necesarias si no existen
     */
    public static function ensureDirectoryExists($path) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $path;
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        return $fullPath;
    }
    
    /**
     * Sube un archivo y retorna la ruta relativa para guardar en BD
     */
    public static function uploadFile($file, $type = 'products', $customName = null) {
        // Validaciones básicas
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            throw new Exception('No se recibió archivo');
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir archivo: ' . $file['error']);
        }
        
        // Generar ruta organizada
        $uploadPath = self::generateUploadPath($type);
        $fullUploadPath = self::ensureDirectoryExists($uploadPath);
        
        // Generar nombre único si no se proporciona
        if ($customName) {
            $fileName = $customName;
        } else {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        }
        
        // Mover archivo
        $fullFilePath = $fullUploadPath . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $fullFilePath)) {
            throw new Exception('Error al mover archivo');
        }
        
        // Retornar ruta relativa para guardar en BD
        return $uploadPath . $fileName;
    }
    
    /**
     * Busca una imagen en diferentes ubicaciones posibles
     * ACTUALIZADO para incluir admin/assets/uploads/
     */
    public static function findImagePath($imageName) {
        // Si ya viene con ruta completa, devolverla
        if (strpos($imageName, '/') !== false) {
            return $imageName;
        }
        
        // Lista de ubicaciones donde buscar (en orden de prioridad)
        $search_locations = [
            // 1. Ubicación actual del admin
            'admin/assets/uploads/' . $imageName,
            
            // 2. Ubicación organizada actual
            self::generateUploadPath('products') . $imageName,
            
            // 3. Búsqueda en meses anteriores (últimos 6 meses)
            // Se añaden dinámicamente abajo
            
            // 4. Uploads root (archivos antiguos)
            'uploads/' . $imageName,
            
            // 5. Raíz del proyecto
            $imageName
        ];
        
        // Añadir búsqueda en meses anteriores
        for ($i = 1; $i <= 6; $i++) {
            $date = new DateTime();
            $date->modify("-{$i} month");
            $year = $date->format('Y');
            $month = $date->format('m');
            
            $search_locations[] = "uploads/products/{$year}/{$month}/{$imageName}";
        }
        
        // Buscar en cada ubicación
        foreach ($search_locations as $location) {
            $full_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $location;
            if (file_exists($full_path)) {
                return $location;
            }
        }
        
        // Si no se encuentra, devolver imagen por defecto
        return 'assets/img/no-image.jpg';
    }
    
    /**
     * Obtener URL completa de una imagen
     */
    public static function getImageUrl($imagePath) {
        if (empty($imagePath)) {
            return '/assets/img/no-image.jpg';
        }
        
        // Si ya es una URL completa, devolverla
        if (strpos($imagePath, 'http') === 0) {
            return $imagePath;
        }
        
        // Añadir barra inicial si no la tiene
        if (strpos($imagePath, '/') !== 0) {
            $imagePath = '/' . $imagePath;
        }
        
        return $imagePath;
    }
    
    /**
     * Verificar si una imagen existe
     */
    public static function imageExists($imagePath) {
        if (empty($imagePath)) {
            return false;
        }
        
        // Remover barra inicial para construir ruta del servidor
        $serverPath = ltrim($imagePath, '/');
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $serverPath;
        
        return file_exists($fullPath);
    }
    
    /**
     * Obtener información de una imagen
     */
    public static function getImageInfo($imagePath) {
        if (!self::imageExists($imagePath)) {
            return null;
        }
        
        $serverPath = ltrim($imagePath, '/');
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $serverPath;
        
        $info = getimagesize($fullPath);
        $fileSize = filesize($fullPath);
        
        return [
            'width' => $info[0],
            'height' => $info[1],
            'type' => $info['mime'],
            'size' => $fileSize,
            'size_formatted' => self::formatBytes($fileSize)
        ];
    }
    
    /**
     * Formatear bytes en formato legible
     */
    private static function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Mover imagen desde admin a ubicación organizada
     */
    public static function moveImageToOrganized($currentPath, $type = 'products') {
        // Verificar que la imagen actual existe
        if (!self::imageExists($currentPath)) {
            throw new Exception('Imagen origen no encontrada');
        }
        
        // Generar nueva ubicación
        $newPath = self::generateUploadPath($type);
        $newFullPath = self::ensureDirectoryExists($newPath);
        
        // Obtener nombre del archivo
        $fileName = basename($currentPath);
        $newFilePath = $newFullPath . $fileName;
        $newRelativePath = $newPath . $fileName;
        
        // Mover archivo
        $currentFullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($currentPath, '/');
        
        if (rename($currentFullPath, $newFilePath)) {
            return $newRelativePath;
        } else {
            throw new Exception('Error moviendo archivo');
        }
    }
    
    /**
     * Limpiar rutas de imágenes no utilizadas
     */
    public static function cleanUnusedImages($conn) {
        try {
            // Obtener todas las imágenes referenciadas en la BD
            $stmt = $conn->prepare("
                SELECT cover_image_url, image_2, image_3, image_4, image_5 
                FROM products 
                WHERE cover_image_url IS NOT NULL
            ");
            $stmt->execute();
            $products = $stmt->fetchAll();
            
            $used_images = [];
            foreach ($products as $product) {
                $images = [$product['cover_image_url'], $product['image_2'], $product['image_3'], $product['image_4'], $product['image_5']];
                foreach ($images as $image) {
                    if (!empty($image)) {
                        $used_images[] = basename($image);
                    }
                }
            }
            
            // Buscar archivos huérfanos en admin/assets/uploads/
            $admin_uploads = $_SERVER['DOCUMENT_ROOT'] . '/admin/assets/uploads/';
            if (is_dir($admin_uploads)) {
                $all_files = glob($admin_uploads . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
                $orphaned = [];
                
                foreach ($all_files as $file) {
                    $fileName = basename($file);
                    if (!in_array($fileName, $used_images)) {
                        $orphaned[] = $fileName;
                    }
                }
                
                return $orphaned;
            }
            
            return [];
            
        } catch (Exception $e) {
            error_log("Error limpiando imágenes: " . $e->getMessage());
            return [];
        }
    }
}