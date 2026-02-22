<?php
/**
 * Sistema de Sincronización con Portal Externo
 * Permite importar documentos desde otro portal empresarial
 */

require_once 'config.php';

// Verificar autenticación
requerirAutenticacion();

// Configuración de respuesta
header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    $action = $_GET['action'] ?? 'config';
    
    switch ($action) {
        case 'config':
            // Obtener configuración de sincronización
            $response['success'] = true;
            $response['data'] = [
                'portal_url' => 'https://portal-externo.empresa.com/api/documentos',
                'api_key' => 'demo_api_key_12345',
                'sync_interval' => 3600, // 1 hora
                'last_sync' => getSetting('last_sync_time'),
                'auto_sync' => getSetting('auto_sync_enabled', false)
            ];
            break;
            
        case 'test_connection':
            // Probar conexión con portal externo
            $portal_url = $_POST['portal_url'] ?? '';
            $api_key = $_POST['api_key'] ?? '';
            
            if (empty($portal_url) || empty($api_key)) {
                throw new Exception('URL del portal y API key son requeridos');
            }
            
            // Simular conexión (en producción sería una llamada real)
            $test_result = simulatePortalConnection($portal_url, $api_key);
            
            $response['success'] = $test_result['success'];
            $response['message'] = $test_result['message'];
            $response['data'] = $test_result['data'];
            break;
            
        case 'sync':
            // Realizar sincronización
            $portal_url = $_POST['portal_url'] ?? '';
            $api_key = $_POST['api_key'] ?? '';
            $sync_type = $_POST['sync_type'] ?? 'all'; // all, new, updated
            
            if (empty($portal_url) || empty($api_key)) {
                throw new Exception('URL del portal y API key son requeridos');
            }
            
            $sync_result = syncDocumentsFromPortal($portal_url, $api_key, $sync_type);
            
            $response['success'] = $sync_result['success'];
            $response['message'] = $sync_result['message'];
            $response['data'] = $sync_result['data'];
            
            // Guardar timestamp de última sincronización
            if ($sync_result['success']) {
                setSetting('last_sync_time', date('Y-m-d H:i:s'));
                registrarActividad($_SESSION['usuario_id'], 'sync_portal', "Sincronización con portal externo: {$sync_result['imported']} documentos");
            }
            break;
            
        case 'status':
            // Obtener estado de sincronización
            $response['success'] = true;
            $response['data'] = [
                'last_sync' => getSetting('last_sync_time'),
                'auto_sync' => getSetting('auto_sync_enabled', false),
                'total_imported' => getTotalImportedDocuments(),
                'sync_history' => getSyncHistory()
            ];
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error en sync_portal.php: " . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;

/**
 * Simular conexión con portal externo
 */
function simulatePortalConnection($portal_url, $api_key) {
    // Simular respuesta exitosa
    return [
        'success' => true,
        'message' => 'Conexión exitosa con el portal externo',
        'data' => [
            'portal_name' => 'Portal Empresarial Principal',
            'version' => '2.1.0',
            'total_documents' => 156,
            'last_updated' => date('Y-m-d H:i:s'),
            'api_status' => 'active'
        ]
    ];
}

/**
 * Sincronizar documentos desde portal externo
 */
function syncDocumentsFromPortal($portal_url, $api_key, $sync_type) {
    $db = Database::getInstance();
    
    try {
        // Simular obtención de documentos del portal externo
        $external_documents = simulateExternalDocuments($sync_type);
        
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($external_documents as $doc) {
            try {
                // Verificar si el documento ya existe
                $existing = $db->query(
                    "SELECT id FROM documentos WHERE uuid = ? OR nombre_original = ? AND usuario_id = ?",
                    [$doc['uuid'], $doc['nombre_original'], $_SESSION['usuario_id']]
                )->fetch();
                
                if ($existing) {
                    if ($sync_type === 'all' || $sync_type === 'updated') {
                        // Actualizar documento existente
                        $sql = "UPDATE documentos SET 
                                descripcion = ?, 
                                fecha_vencimiento = ?, 
                                estado = ?, 
                                fecha_actualizacion = NOW() 
                                WHERE uuid = ? AND usuario_id = ?";
                        
                        $params = [
                            $doc['descripcion'],
                            $doc['fecha_vencimiento'],
                            $doc['estado'],
                            $doc['uuid'],
                            $_SESSION['usuario_id']
                        ];
                        
                        $db->query($sql, $params);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    // Insertar nuevo documento
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
                        $doc['nombre_original'],
                        $doc['ruta_archivo'],
                        $doc['fecha_vencimiento'],
                        $doc['descripcion'],
                        $doc['estado'],
                        $_SESSION['usuario_id'],
                        $doc['tamano_archivo'],
                        $doc['tipo_archivo'],
                        $doc['extension'],
                        $doc['uuid']
                    ];
                    
                    $db->query($sql, $params);
                    $imported++;
                }
                
            } catch (Exception $e) {
                $errors[] = "Error procesando documento {$doc['nombre_original']}: " . $e->getMessage();
            }
        }
        
        return [
            'success' => true,
            'message' => "Sincronización completada",
            'data' => [
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
                'total_processed' => $imported + $updated + $skipped
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error durante la sincronización: ' . $e->getMessage(),
            'data' => null
        ];
    }
}

/**
 * Simular documentos externos
 */
function simulateExternalDocuments($sync_type) {
    $documents = [
        [
            'uuid' => 'ext-001-' . uniqid(),
            'nombre_original' => 'Contrato Corporativo 2024.pdf',
            'ruta_archivo' => 'external_files/contrato_2024.pdf',
            'fecha_vencimiento' => date('Y-m-d', strtotime('+90 days')),
            'descripcion' => 'Contrato principal de la empresa para el año 2024',
            'estado' => 'vigente',
            'tamano_archivo' => 2048576,
            'tipo_archivo' => 'application/pdf',
            'extension' => 'pdf'
        ],
        [
            'uuid' => 'ext-002-' . uniqid(),
            'nombre_original' => 'Informe Financiero Q4.xlsx',
            'ruta_archivo' => 'external_files/informe_q4.xlsx',
            'fecha_vencimiento' => date('Y-m-d', strtotime('+30 days')),
            'descripcion' => 'Informe financiero del cuarto trimestre',
            'estado' => 'por_vencer',
            'tamano_archivo' => 1536000,
            'tipo_archivo' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'extension' => 'xlsx'
        ],
        [
            'uuid' => 'ext-003-' . uniqid(),
            'nombre_original' => 'Políticas de Seguridad.docx',
            'ruta_archivo' => 'external_files/politicas_seguridad.docx',
            'fecha_vencimiento' => date('Y-m-d', strtotime('+180 days')),
            'descripcion' => 'Políticas actualizadas de seguridad de la información',
            'estado' => 'vigente',
            'tamano_archivo' => 512000,
            'tipo_archivo' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'extension' => 'docx'
        ],
        [
            'uuid' => 'ext-004-' . uniqid(),
            'nombre_original' => 'Auditoría Interna 2023.pdf',
            'ruta_archivo' => 'external_files/auditoria_2023.pdf',
            'fecha_vencimiento' => date('Y-m-d', strtotime('-10 days')),
            'descripcion' => 'Informe de auditoría interna del año 2023',
            'estado' => 'vencido',
            'tamano_archivo' => 3072000,
            'tipo_archivo' => 'application/pdf',
            'extension' => 'pdf'
        ]
    ];
    
    // Filtrar según tipo de sincronización
    if ($sync_type === 'new') {
        return array_filter($documents, function($doc) {
            return $doc['estado'] === 'vigente';
        });
    } elseif ($sync_type === 'updated') {
        return array_filter($documents, function($doc) {
            return $doc['estado'] === 'por_vencer';
        });
    }
    
    return $documents;
}

/**
 * Obtener configuración de settings
 */
function getSetting($key, $default = null) {
    // En producción, esto vendría de una tabla de configuración
    $settings = [
        'last_sync_time' => null,
        'auto_sync_enabled' => false,
        'portal_url' => 'https://portal-externo.empresa.com/api/documentos'
    ];
    
    return $settings[$key] ?? $default;
}

/**
 * Guardar configuración
 */
function setSetting($key, $value) {
    // En producción, esto se guardaría en una tabla de configuración
    // Por ahora, solo lo registramos en el log
    error_log("Setting guardado: $key = $value");
}

/**
 * Obtener total de documentos importados
 */
function getTotalImportedDocuments() {
    try {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT COUNT(*) as count FROM documentos WHERE usuario_id = ? AND ruta_archivo LIKE 'external_files/%'",
            [$_SESSION['usuario_id']]
        );
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Obtener historial de sincronización
 */
function getSyncHistory() {
    // En producción, esto vendría de una tabla de logs
    return [
        [
            'date' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'action' => 'sync',
            'documents' => 4,
            'status' => 'success'
        ],
        [
            'date' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'action' => 'sync',
            'documents' => 2,
            'status' => 'success'
        ]
    ];
}
?>
