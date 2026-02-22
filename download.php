<?php
/**
 * Gestor de descarga de documentos
 * Maneja la descarga segura de archivos con control de acceso
 */

require_once 'config.php';

// Verificar autenticación
requerirAutenticacion();

try {
    // Obtener y validar ID del documento
    $id = intval($_GET['id'] ?? 0);
    $preview = isset($_GET['preview']) && $_GET['preview'] == '1';
    
    if ($id <= 0) {
        throw new Exception('ID de documento no válido');
    }
    
    $db = Database::getInstance();
    
    // Obtener información del documento
    $sql = "SELECT id, nombre_original, ruta_archivo, extension, tipo_archivo, tamano_archivo, usuario_id 
            FROM documentos 
            WHERE id = ?";
    $stmt = $db->query($sql, [$id]);
    $documento = $stmt->fetch();
    
    if (!$documento) {
        throw new Exception('Documento no encontrado');
    }
    
    // Verificar que el documento pertenezca al usuario actual
    if ($documento['usuario_id'] != $_SESSION['usuario_id']) {
        throw new Exception('No tienes permisos para acceder a este documento');
    }
    
    // Construir ruta completa del archivo
    $filepath = UPLOAD_PATH . $documento['ruta_archivo'];
    
    // Verificar que el archivo exista
    if (!file_exists($filepath)) {
        throw new Exception('El archivo físico no existe en el servidor');
    }
    
    // Verificar que el archivo sea legible
    if (!is_readable($filepath)) {
        throw new Exception('No se puede leer el archivo');
    }
    
    // Obtener información del archivo
    $filesize = filesize($filepath);
    $filename = $documento['nombre_original'];
    $mimetype = $documento['tipo_archivo'];
    
    // Si es preview y es imagen, mostrar inline
    if ($preview && in_array(strtolower($documento['extension']), ['jpg', 'jpeg', 'png', 'gif'])) {
        header('Content-Type: ' . $mimetype);
        header('Content-Length: ' . $filesize);
        header('Cache-Control: public, max-age=3600'); // Cache por 1 hora
        header('Pragma: public');
        
        // Registrar actividad de visualización
        registrarActividad(
            $_SESSION['usuario_id'], 
            'view', 
            "Visualizó el documento: {$filename}"
        );
    } else {
        // Descarga normal
        // Limpiar cualquier salida previa
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Configurar headers para descarga
        header('Content-Type: ' . $mimetype);
        header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
        header('Content-Length: ' . $filesize);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Registrar actividad de descarga
        registrarActividad(
            $_SESSION['usuario_id'], 
            'download', 
            "Descargó el documento: {$filename}"
        );
    }
    
    // Deshabilitar time limit para archivos grandes
    set_time_limit(0);
    
    // Leer y enviar el archivo
    $handle = fopen($filepath, 'rb');
    if ($handle === false) {
        throw new Exception('No se puede abrir el archivo para lectura');
    }
    
    // Enviar el archivo en chunks para manejar archivos grandes
    $chunk_size = 8192; // 8KB chunks
    while (!feof($handle) && connection_status() == CONNECTION_NORMAL) {
        echo fread($handle, $chunk_size);
        flush();
    }
    
    fclose($handle);
    exit;
    
} catch (Exception $e) {
    // En caso de error, mostrar página de error o redirigir
    error_log("Error en download.php: " . $e->getMessage());
    
    // Si es una petición AJAX, devolver JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // Redirigir con mensaje de error
        $_SESSION['flash_message'] = 'Error al descargar el documento: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
        header('Location: index.php');
    }
    exit;
}
?>
