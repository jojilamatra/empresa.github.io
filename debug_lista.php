<?php
/**
 * Script de depuración para lista.php
 */

require_once 'config.php';

echo "<h2>🔍 Depuración de lista.php</h2>";

// Verificar autenticación
echo "<h3>🔐 Autenticación</h3>";
if (estaAutenticado()) {
    echo "✅ Usuario autenticado<br>";
    echo "Usuario ID: " . $_SESSION['usuario_id'] . "<br>";
    echo "Username: " . $_SESSION['username'] . "<br>";
} else {
    echo "❌ Usuario no autenticado<br>";
    exit;
}

// Probar conexión a la base de datos
echo "<h3>🔗 Conexión a Base de Datos</h3>";
try {
    $db = Database::getInstance();
    echo "✅ Conexión exitosa<br>";
    
    // Probar consulta simple
    $sql_test = "SELECT 1 as test";
    $stmt_test = $db->query($sql_test);
    $result = $stmt_test->fetch();
    echo "✅ Consulta simple exitosa: " . $result['test'] . "<br>";
    
    // Verificar tabla documentos
    $sql_table = "SHOW TABLES LIKE 'documentos'";
    $stmt_table = $db->query($sql_table);
    $table_exists = $stmt_table->fetch();
    
    if ($table_exists) {
        echo "✅ Tabla 'documentos' existe<br>";
        
        // Verificar estructura de la tabla
        $sql_structure = "DESCRIBE documentos";
        $stmt_structure = $db->query($sql_structure);
        echo "<h4>Estructura de la tabla:</h4>";
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th></tr>";
        while ($row = $stmt_structure->fetch()) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
        }
        echo "</table>";
        
        // Contar documentos del usuario
        $sql_count = "SELECT COUNT(*) as count FROM documentos WHERE usuario_id = ?";
        $stmt_count = $db->query($sql_count, [$_SESSION['usuario_id']]);
        $count = $stmt_count->fetch();
        echo "<h3>📊 Documentos del Usuario</h3>";
        echo "Total documentos: " . $count['count'] . "<br>";
        
        // Probar consulta principal
        echo "<h3>🔍 Consulta Principal</h3>";
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
        
        echo "SQL: " . $sql . "<br>";
        echo "Parámetro: " . $_SESSION['usuario_id'] . "<br><br>";
        
        $stmt = $db->query($sql, [$_SESSION['usuario_id']]);
        $documentos = $stmt->fetchAll();
        
        echo "Documentos encontrados: " . count($documentos) . "<br>";
        
        if (!empty($documentos)) {
            echo "<h4>Primer documento:</h4>";
            echo "<pre>";
            print_r($documentos[0]);
            echo "</pre>";
        }
        
        // Simular respuesta JSON
        echo "<h3>📤 Respuesta JSON Simulada</h3>";
        
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
        
        $response = [
            'success' => true,
            'documentos' => $documentos_procesados,
            'estadisticas' => $count
        ];
        
        echo "<pre>";
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</pre>";
        
    } else {
        echo "❌ Tabla 'documentos' no existe<br>";
        echo "Debes importar el archivo database.sql<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<h3>Stack Trace:</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>🔧 Configuración Actual</h3>";
echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "DB_USER: " . DB_USER . "<br>";
echo "DB_PASS: " . (empty(DB_PASS) ? '(vacía)' : '***') . "<br>";
?>
