<?php
/**
 * Procesador de eliminación de documentos
 * Maneja la eliminación segura de archivos y registros
 */

require_once 'config.php';

// Verificar autenticación
requerirAutenticacion();

// Configurar headers para respuesta JSON
header('Content-Type: application/json');

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Respuesta por defecto
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Obtener y validar ID del documento
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception('ID de documento no válido');
    }
    
    $db = Database::getInstance();
    
    // Obtener información del documento antes de eliminar
    $sql_info = "SELECT id, nombre_original, ruta_archivo, usuario_id 
                 FROM documentos 
                 WHERE id = ?";
    $stmt_info = $db->query($sql_info, [$id]);
    $documento = $stmt_info->fetch();
    
    if (!$documento) {
        throw new Exception('Documento no encontrado');
    }
    
    // Verificar que el documento pertenezca al usuario actual
    if ($documento['usuario_id'] != $_SESSION['usuario_id']) {
        throw new Exception('No tienes permisos para eliminar este documento');
    }
    
    // Iniciar transacción para asegurar consistencia
    $db->getConnection()->beginTransaction();
    
    try {
        // 1. Eliminar el archivo físico del servidor
        $filepath = UPLOAD_PATH . $documento['ruta_archivo'];
        if (file_exists($filepath)) {
            if (!unlink($filepath)) {
                throw new Exception('No se puede eliminar el archivo físico del servidor');
            }
        }
        
        // 2. Eliminar el registro de la base de datos
        $sql_delete = "DELETE FROM documentos WHERE id = ? AND usuario_id = ?";
        $stmt_delete = $db->query($sql_delete, [$id, $_SESSION['usuario_id']]);
        
        if ($stmt_delete->rowCount() === 0) {
            throw new Exception('No se pudo eliminar el registro del documento');
        }
        
        // 3. Registrar actividad
        registrarActividad(
            $_SESSION['usuario_id'], 
            'delete', 
            "Eliminó el documento: {$documento['nombre_original']}"
        );
        
        // Confirmar transacción
        $db->getConnection()->commit();
        
        $response['success'] = true;
        $response['message'] = 'Documento eliminado correctamente';
        $response['data'] = [
            'id' => $id,
            'nombre' => $documento['nombre_original']
        ];
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $db->getConnection()->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error en delete.php: " . $e->getMessage());
}

// Enviar respuesta JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>
