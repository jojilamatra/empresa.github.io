<?php
/**
 * API para lista de documentos vía AJAX
 * Proporciona datos para la tabla principal y estadísticas
 */

require_once 'config.php';

// Verificar autenticación
requerirAutenticacion();

// Configurar headers para respuesta JSON (solo si no se han enviado headers)
if (!headers_sent()) {
    header('Content-Type: application/json');
}

// Respuesta por defecto
$response = [
    'success' => false,
    'message' => '',
    'documentos' => [],
    'estadisticas' => null
];

try {
    $db = Database::getInstance();
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Obtener lista de documentos del usuario
            $sql = "SELECT 
                        id, 
                        nombre_original, 
                        fecha_vencimiento, 
                        descripcion, 
                        estado, 
                        fecha_subida, 
                        tamano_archivo, 
                        extension,
                        uuid
                    FROM documentos 
                    WHERE usuario_id = ? 
                    ORDER BY fecha_subida DESC";
            
            $stmt = $db->query($sql, [$_SESSION['usuario_id']]);
            $documentos = $stmt->fetchAll();
            
            // Procesar cada documento para la respuesta
            $documentos_procesados = [];
            foreach ($documentos as $doc) {
                $fecha_vencimiento = new DateTime($doc['fecha_vencimiento']);
                $hoy = new DateTime();
                $diferencia = $hoy->diff($fecha_vencimiento);
                
                // Determinar clase CSS y texto para el estado
                $estado_clase = $doc['estado'] == 'vigente' ? 'success' : 
                               ($doc['estado'] == 'por_vencer' ? 'warning' : 'danger');
                $estado_texto = ucfirst(str_replace('_', ' ', $doc['estado']));
                
                // Calcular días restantes
                if ($fecha_vencimiento < $hoy) {
                    $dias_restantes_texto = 'Vencido hace ' . $diferencia->days . ' días';
                    $dias_restantes_clase = 'text-danger';
                } elseif ($diferencia->days == 0) {
                    $dias_restantes_texto = 'Vence hoy';
                    $dias_restantes_clase = 'text-warning';
                } elseif ($diferencia->days == 1) {
                    $dias_restantes_texto = 'Vence mañana';
                    $dias_restantes_clase = 'text-warning';
                } else {
                    $dias_restantes_texto = $diferencia->days . ' días restantes';
                    $dias_restantes_clase = $diferencia->days <= 30 ? 'text-warning' : 'text-success';
                }
                
                $documentos_procesados[] = [
                    'id' => $doc['id'],
                    'nombre_original' => $doc['nombre_original'],
                    'descripcion' => $doc['descripcion'],
                    'fecha_vencimiento' => $doc['fecha_vencimiento'],
                    'fecha_vencimiento_formateada' => date('d/m/Y', strtotime($doc['fecha_vencimiento'])),
                    'estado' => $doc['estado'],
                    'estado_clase' => $estado_clase,
                    'estado_texto' => $estado_texto,
                    'fecha_subida' => $doc['fecha_subida'],
                    'fecha_subida_formateada' => date('d/m/Y H:i', strtotime($doc['fecha_subida'])),
                    'tamano_archivo' => $doc['tamano_archivo'],
                    'tamano_formateado' => formatFileSize($doc['tamano_archivo']),
                    'extension' => $doc['extension'],
                    'uuid' => $doc['uuid'],
                    'icono' => getFileIcon($doc['extension']),
                    'es_imagen' => in_array(strtolower($doc['extension']), ['jpg', 'jpeg', 'png']),
                    'dias_restantes_texto' => $dias_restantes_texto,
                    'dias_restantes_clase' => $dias_restantes_clase
                ];
            }
            
            // Obtener estadísticas
            $sql_stats = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN estado = 'vigente' THEN 1 ELSE 0 END) as vigentes,
                            SUM(CASE WHEN estado = 'por_vencer' THEN 1 ELSE 0 END) as por_vencer,
                            SUM(CASE WHEN estado = 'vencido' THEN 1 ELSE 0 END) as vencidos
                          FROM documentos 
                          WHERE usuario_id = ?";
            
            $stmt_stats = $db->query($sql_stats, [$_SESSION['usuario_id']]);
            $estadisticas = $stmt_stats->fetch();
            
            $response['success'] = true;
            $response['documentos'] = $documentos_procesados;
            $response['estadisticas'] = $estadisticas;
            break;
            
        case 'view':
            // Obtener detalles de un documento específico
            $id = intval($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('ID de documento no válido');
            }
            
            $sql = "SELECT 
                        id, 
                        nombre_original, 
                        ruta_archivo, 
                        fecha_vencimiento, 
                        descripcion, 
                        estado, 
                        fecha_subida, 
                        tamano_archivo, 
                        extension,
                        uuid
                    FROM documentos 
                    WHERE id = ? AND usuario_id = ?";
            
            $stmt = $db->query($sql, [$id, $_SESSION['usuario_id']]);
            $documento = $stmt->fetch();
            
            if (!$documento) {
                throw new Exception('Documento no encontrado');
            }
            
            // Procesar datos del documento
            $fecha_vencimiento = new DateTime($documento['fecha_vencimiento']);
            $hoy = new DateTime();
            $diferencia = $hoy->diff($fecha_vencimiento);
            
            $estado_clase = $documento['estado'] == 'vigente' ? 'success' : 
                           ($documento['estado'] == 'por_vencer' ? 'warning' : 'danger');
            $estado_texto = ucfirst(str_replace('_', ' ', $documento['estado']));
            
            if ($fecha_vencimiento < $hoy) {
                $dias_restantes_texto = 'Vencido hace ' . $diferencia->days . ' días';
            } elseif ($diferencia->days == 0) {
                $dias_restantes_texto = 'Vence hoy';
            } elseif ($diferencia->days == 1) {
                $dias_restantes_texto = 'Vence mañana';
            } else {
                $dias_restantes_texto = $diferencia->days . ' días restantes';
            }
            
            $documento_procesado = [
                'id' => $documento['id'],
                'nombre_original' => $documento['nombre_original'],
                'ruta_archivo' => $documento['ruta_archivo'],
                'descripcion' => $documento['descripcion'],
                'fecha_vencimiento' => $documento['fecha_vencimiento'],
                'fecha_vencimiento_formateada' => date('d/m/Y', strtotime($documento['fecha_vencimiento'])),
                'estado' => $documento['estado'],
                'estado_clase' => $estado_clase,
                'estado_texto' => $estado_texto,
                'fecha_subida' => $documento['fecha_subida'],
                'fecha_subida_formateada' => date('d/m/Y H:i', strtotime($documento['fecha_subida'])),
                'tamano_archivo' => $documento['tamano_archivo'],
                'tamano_formateado' => formatFileSize($documento['tamano_archivo']),
                'extension' => $documento['extension'],
                'uuid' => $documento['uuid'],
                'icono' => getFileIcon($documento['extension']),
                'es_imagen' => in_array(strtolower($documento['extension']), ['jpg', 'jpeg', 'png']),
                'dias_restantes_texto' => $dias_restantes_texto
            ];
            
            $response['success'] = true;
            $response['documento'] = $documento_procesado;
            break;
            
        case 'stats':
            // Obtener solo estadísticas
            $sql_stats = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN estado = 'vigente' THEN 1 ELSE 0 END) as vigentes,
                            SUM(CASE WHEN estado = 'por_vencer' THEN 1 ELSE 0 END) as por_vencer,
                            SUM(CASE WHEN estado = 'vencido' THEN 1 ELSE 0 END) as vencidos,
                            SUM(tamano_archivo) as espacio_total
                          FROM documentos 
                          WHERE usuario_id = ?";
            
            $stmt_stats = $db->query($sql_stats, [$_SESSION['usuario_id']]);
            $estadisticas = $stmt_stats->fetch();
            
            // Formatear espacio total
            if ($estadisticas['espacio_total']) {
                $estadisticas['espacio_formateado'] = formatFileSize($estadisticas['espacio_total']);
            } else {
                $estadisticas['espacio_formateado'] = '0 bytes';
            }
            
            $response['success'] = true;
            $response['estadisticas'] = $estadisticas;
            break;
            
        case 'quick_stats':
            // Estadísticas rápidas para la barra de estado
            $sql_quick = "SELECT 
                            SUM(CASE WHEN estado = 'por_vencer' THEN 1 ELSE 0 END) as por_vencer,
                            SUM(CASE WHEN estado = 'vencido' THEN 1 ELSE 0 END) as vencidos
                          FROM documentos 
                          WHERE usuario_id = ?";
            
            $stmt_quick = $db->query($sql_quick, [$_SESSION['usuario_id']]);
            $estadisticas_rapidas = $stmt_quick->fetch();
            
            $response['success'] = true;
            $response['estadisticas'] = $estadisticas_rapidas;
            break;
            
        case 'search':
            // Búsqueda de documentos
            $search_term = sanitize($_GET['q'] ?? '');
            $status_filter = sanitize($_GET['status'] ?? '');
            
            $sql = "SELECT 
                        id, 
                        nombre_original, 
                        fecha_vencimiento, 
                        descripcion, 
                        estado, 
                        fecha_subida, 
                        tamano_archivo, 
                        extension,
                        uuid
                    FROM documentos 
                    WHERE usuario_id = ?";
            
            $params = [$_SESSION['usuario_id']];
            
            // Agregar término de búsqueda
            if (!empty($search_term)) {
                $sql .= " AND (nombre_original LIKE ? OR descripcion LIKE ?)";
                $search_param = '%' . $search_term . '%';
                $params[] = $search_param;
                $params[] = $search_param;
            }
            
            // Agregar filtro de estado
            if (!empty($status_filter)) {
                $sql .= " AND estado = ?";
                $params[] = $status_filter;
            }
            
            $sql .= " ORDER BY fecha_subida DESC";
            
            $stmt = $db->query($sql, $params);
            $documentos = $stmt->fetchAll();
            
            // Procesar documentos (igual que en el caso 'list')
            $documentos_procesados = [];
            foreach ($documentos as $doc) {
                $fecha_vencimiento = new DateTime($doc['fecha_vencimiento']);
                $hoy = new DateTime();
                $diferencia = $hoy->diff($fecha_vencimiento);
                
                $estado_clase = $doc['estado'] == 'vigente' ? 'success' : 
                               ($doc['estado'] == 'por_vencer' ? 'warning' : 'danger');
                $estado_texto = ucfirst(str_replace('_', ' ', $doc['estado']));
                
                if ($fecha_vencimiento < $hoy) {
                    $dias_restantes_texto = 'Vencido hace ' . $diferencia->days . ' días';
                    $dias_restantes_clase = 'text-danger';
                } elseif ($diferencia->days == 0) {
                    $dias_restantes_texto = 'Vence hoy';
                    $dias_restantes_clase = 'text-warning';
                } elseif ($diferencia->days == 1) {
                    $dias_restantes_texto = 'Vence mañana';
                    $dias_restantes_clase = 'text-warning';
                } else {
                    $dias_restantes_texto = $diferencia->days . ' días restantes';
                    $dias_restantes_clase = $diferencia->days <= 30 ? 'text-warning' : 'text-success';
                }
                
                $documentos_procesados[] = [
                    'id' => $doc['id'],
                    'nombre_original' => $doc['nombre_original'],
                    'descripcion' => $doc['descripcion'],
                    'fecha_vencimiento' => $doc['fecha_vencimiento'],
                    'fecha_vencimiento_formateada' => date('d/m/Y', strtotime($doc['fecha_vencimiento'])),
                    'estado' => $doc['estado'],
                    'estado_clase' => $estado_clase,
                    'estado_texto' => $estado_texto,
                    'fecha_subida' => $doc['fecha_subida'],
                    'fecha_subida_formateada' => date('d/m/Y H:i', strtotime($doc['fecha_subida'])),
                    'tamano_archivo' => $doc['tamano_archivo'],
                    'tamano_formateado' => formatFileSize($doc['tamano_archivo']),
                    'extension' => $doc['extension'],
                    'uuid' => $doc['uuid'],
                    'icono' => getFileIcon($doc['extension']),
                    'es_imagen' => in_array(strtolower($doc['extension']), ['jpg', 'jpeg', 'png']),
                    'dias_restantes_texto' => $dias_restantes_texto,
                    'dias_restantes_clase' => $dias_restantes_clase
                ];
            }
            
            $response['success'] = true;
            $response['documentos'] = $documentos_procesados;
            $response['search_term'] = $search_term;
            $response['status_filter'] = $status_filter;
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error en lista.php: " . $e->getMessage());
}

// Enviar respuesta JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>
