<?php
/**
 * Procesador de carga de documentos
 * Maneja la subida de archivos individuales o múltiples
 */

require_once 'config.php';

// Verificar autenticación
requerirAutenticacion();

// Configurar headers para respuesta JSON
header('Content-Type: application/json');

// Respuesta por defecto
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Verificar si hay archivos para procesar
    if (!isset($_FILES['documentFiles']) || empty($_FILES['documentFiles']['name'][0])) {
        throw new Exception('No se han seleccionado archivos para cargar');
    }
    
    // Validar datos del formulario
    $descripcion = sanitize($_POST['descripcion'] ?? '');
    $fechaVencimiento = $_POST['fechaVencimiento'] ?? '';
    
    if (empty($fechaVencimiento)) {
        throw new Exception('La fecha de vencimiento es obligatoria');
    }
    
    if (!validarFecha($fechaVencimiento)) {
        throw new Exception('La fecha de vencimiento no es válida');
    }
    
    // Verificar que la fecha sea futura
    $vencimiento = new DateTime($fechaVencimiento);
    $hoy = new DateTime();
    if ($vencimiento <= $hoy) {
        throw new Exception('La fecha de vencimiento debe ser futura');
    }
    
    // Crear directorio de uploads si no existe
    if (!file_exists(UPLOAD_PATH)) {
        if (!mkdir(UPLOAD_PATH, 0755, true)) {
            throw new Exception('No se puede crear el directorio de uploads');
        }
    }
    
    $db = Database::getInstance();
    $archivos_procesados = [];
    $errores_archivos = [];
    
    // Procesar cada archivo
    $files = $_FILES['documentFiles'];
    $file_count = count($files['name']);
    
    for ($i = 0; $i < $file_count; $i++) {
        // Verificar si hay error en el archivo
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errores_archivos[] = [
                'nombre' => $files['name'][$i],
                'error' => getUploadErrorMessage($files['error'][$i])
            ];
            continue;
        }
        
        $tmp_name = $files['tmp_name'][$i];
        $name = $files['name'][$i];
        $size = $files['size'][$i];
        $type = $files['type'][$i];
        
        // Validar tamaño del archivo
        if ($size > MAX_FILE_SIZE) {
            $errores_archivos[] = [
                'nombre' => $name,
                'error' => 'El archivo excede el tamaño máximo permitido (10MB)'
            ];
            continue;
        }
        
        // Obtener extensión y validar
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!array_key_exists($extension, ALLOWED_EXTENSIONS)) {
            $errores_archivos[] = [
                'nombre' => $name,
                'error' => 'Tipo de archivo no permitido'
            ];
            continue;
        }
        
        // Validar tipo MIME
        $expected_type = ALLOWED_EXTENSIONS[$extension];
        if ($type !== $expected_type && !in_array($type, getAlternativeMimeTypes($extension))) {
            $errores_archivos[] = [
                'nombre' => $name,
                'error' => 'El tipo de archivo no coincide con la extensión'
            ];
            continue;
        }
        
        // Generar nombre único para el archivo
        $uuid = generateUUID();
        $filename = $uuid . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
        $filepath = UPLOAD_PATH . $filename;
        
        // Mover archivo al directorio de uploads
        if (!move_uploaded_file($tmp_name, $filepath)) {
            $errores_archivos[] = [
                'nombre' => $name,
                'error' => 'Error al mover el archivo al servidor'
            ];
            continue;
        }
        
        // Calcular estado basado en fecha de vencimiento
        $estado = calcularEstado($fechaVencimiento);
        
        // Insertar en la base de datos
        $sql = "INSERT INTO documentos (
                    nombre_original, 
                    ruta_archivo, 
                    fecha_vencimiento, 
                    descripcion, 
                    estado, 
                    usuario_id, 
                    tamano_archivo, 
                    tipo_archivo, 
                    extension, 
                    uuid
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $name,
            $filename,
            $fechaVencimiento,
            $descripcion,
            $estado,
            $_SESSION['usuario_id'],
            $size,
            $type,
            $extension,
            $uuid
        ];
        
        $stmt = $db->query($sql, $params);
        $documento_id = $db->lastInsertId();
        
        // Registrar actividad
        registrarActividad($_SESSION['usuario_id'], 'upload', "Subió el documento: {$name}");
        
        $archivos_procesados[] = [
            'id' => $documento_id,
            'nombre' => $name,
            'uuid' => $uuid,
            'tamano' => formatFileSize($size),
            'estado' => $estado
        ];
    }
    
    // Construir respuesta
    if (!empty($archivos_procesados)) {
        $response['success'] = true;
        $response['message'] = sprintf(
            'Se cargaron %d archivos correctamente%s',
            count($archivos_procesados),
            !empty($errores_archivos) ? sprintf(' con %d errores', count($errores_archivos)) : ''
        );
        $response['data'] = [
            'archivos_procesados' => $archivos_procesados,
            'errores_archivos' => $errores_archivos,
            'total_procesados' => count($archivos_procesados),
            'total_errores' => count($errores_archivos)
        ];
    } else {
        $response['message'] = 'No se pudo procesar ningún archivo';
        if (!empty($errores_archivos)) {
            $response['message'] .= ': ' . implode('; ', array_column($errores_archivos, 'error'));
        }
        $response['data'] = [
            'errores_archivos' => $errores_archivos
        ];
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error en upload.php: " . $e->getMessage());
}

// Enviar respuesta JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;

/**
 * Función para obtener mensaje de error de upload
 */
function getUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'El archivo excede el tamaño máximo permitido por el servidor';
        case UPLOAD_ERR_FORM_SIZE:
            return 'El archivo excede el tamaño máximo permitido por el formulario';
        case UPLOAD_ERR_PARTIAL:
            return 'El archivo se subió parcialmente';
        case UPLOAD_ERR_NO_FILE:
            return 'No se seleccionó ningún archivo';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Falta el directorio temporal del servidor';
        case UPLOAD_ERR_CANT_WRITE:
            return 'No se puede escribir el archivo en el disco';
        case UPLOAD_ERR_EXTENSION:
            return 'Una extensión de PHP detuvo la subida del archivo';
        default:
            return 'Error desconocido al subir el archivo';
    }
}

/**
 * Función para obtener tipos MIME alternativos permitidos
 */
function getAlternativeMimeTypes($extension) {
    $alternatives = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'doc' => ['application/msword'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document']
    ];
    
    return $alternatives[$extension] ?? [];
}
?>
