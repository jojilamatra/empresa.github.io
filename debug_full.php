<!DOCTYPE html>
<html>
<head>
    <title>Depuración Completa del Sistema</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        .error { color: red; background: #ffe6e6; }
        .success { color: green; background: #e6ffe6; }
        .warning { color: orange; background: #fff3e6; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Depuración Completa del Sistema de Documentos</h1>
    
    <?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    echo '<div class="section">';
    echo '<h2>1. Configuración PHP</h2>';
    echo '<p><strong>PHP Version:</strong> ' . phpversion() . '</p>';
    echo '<p><strong>Memory Limit:</strong> ' . ini_get('memory_limit') . '</p>';
    echo '<p><strong>Max Execution Time:</strong> ' . ini_get('max_execution_time') . '</p>';
    echo '<p><strong>Upload Max Filesize:</strong> ' . ini_get('upload_max_filesize') . '</p>';
    echo '<p><strong>Post Max Size:</strong> ' . ini_get('post_max_size') . '</p>';
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>2. Archivos y Directorios</h2>';
    echo '<p><strong>Directorio Actual:</strong> ' . __DIR__ . '</p>';
    echo '<p><strong>UPLOAD_PATH:</strong> ' . (defined('UPLOAD_PATH') ? UPLOAD_PATH : 'NO DEFINIDO') . '</p>';
    echo '<p><strong>Existe uploads:</strong> ' . (file_exists(__DIR__ . '/uploads') ? 'SÍ' : 'NO') . '</p>';
    echo '<p><strong>Permisos uploads:</strong> ' . (is_writable(__DIR__ . '/uploads') ? 'ESCRIBIBLE' : 'NO ESCRIBIBLE') . '</p>';
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>3. Sesión</h2>';
    session_start();
    echo '<p><strong>Session Status:</strong> ' . session_status() . '</p>';
    echo '<p><strong>Session ID:</strong> ' . session_id() . '</p>';
    echo '<p><strong>Variables de Sesión:</strong></p>';
    echo '<pre>';
    print_r($_SESSION);
    echo '</pre>';
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>4. Configuración de Base de Datos</h2>';
    echo '<p><strong>DB_HOST:</strong> ' . (defined('DB_HOST') ? DB_HOST : 'NO DEFINIDO') . '</p>';
    echo '<p><strong>DB_NAME:</strong> ' . (defined('DB_NAME') ? DB_NAME : 'NO DEFINIDO') . '</p>';
    echo '<p><strong>DB_USER:</strong> ' . (defined('DB_USER') ? DB_USER : 'NO DEFINIDO') . '</p>';
    echo '<p><strong>DB_PASS:</strong> ' . (defined('DB_PASS') ? (empty(DB_PASS) ? 'VACÍA' : '***CONFIGURADA***') : 'NO DEFINIDO') . '</p>';
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>5. Prueba de Conexión a BD</h2>';
    try {
        require_once 'config.php';
        echo '<p class="success">✅ config.php cargado correctamente</p>';
        
        if (class_exists('Database')) {
            echo '<p class="success">✅ Clase Database existe</p>';
            
            $db = Database::getInstance();
            echo '<p class="success">✅ Conexión a BD establecida</p>';
            
            // Verificar tablas
            $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo '<p><strong>Tablas encontradas:</strong> ' . implode(', ', $tables) . '</p>';
            
            if (in_array('documentos', $tables)) {
                echo '<p class="success">✅ Tabla documentos existe</p>';
                
                // Verificar estructura
                $columns = $db->query("DESCRIBE documentos")->fetchAll();
                echo '<h4>Estructura tabla documentos:</h4>';
                echo '<table border="1">';
                echo '<tr><th>Campo</th><th>Tipo</th></tr>';
                foreach ($columns as $col) {
                    echo '<tr><td>' . $col['Field'] . '</td><td>' . $col['Type'] . '</td></tr>';
                }
                echo '</table>';
                
                // Verificar si hay documentos
                $count = $db->query("SELECT COUNT(*) as count FROM documentos")->fetch();
                echo '<p><strong>Total documentos en BD:</strong> ' . $count['count'] . '</p>';
                
                if ($count['count'] > 0) {
                    // Mostrar primeros documentos
                    $docs = $db->query("SELECT * FROM documentos LIMIT 3")->fetchAll();
                    echo '<h4>Primeros 3 documentos:</h4>';
                    echo '<pre>';
                    print_r($docs);
                    echo '</pre>';
                }
                
            } else {
                echo '<p class="error">❌ Tabla documentos NO existe</p>';
            }
            
        } else {
            echo '<p class="error">❌ Clase Database NO existe</p>';
        }
        
    } catch (Exception $e) {
        echo '<p class="error">❌ Error: ' . $e->getMessage() . '</p>';
        echo '<p class="error">Stack trace:</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>6. Prueba de API lista.php</h2>';
    try {
        // Simular llamada AJAX
        $_GET['action'] = 'list';
        $_SESSION['usuario_id'] = 1; // Simular usuario logueado
        
        ob_start();
        include 'lista.php';
        $output = ob_get_clean();
        
        echo '<p><strong>Respuesta de lista.php:</strong></p>';
        echo '<pre>' . htmlspecialchars($output) . '</pre>';
        
        // Intentar decodificar JSON
        $json_data = json_decode($output, true);
        if ($json_data) {
            echo '<p class="success">✅ JSON válido</p>';
            echo '<p><strong>Documentos en JSON:</strong> ' . count($json_data['documentos'] ?? []) . '</p>';
        } else {
            echo '<p class="error">❌ JSON inválido</p>';
            echo '<p><strong>Salida cruda:</strong></p>';
            echo '<pre>' . htmlspecialchars($output) . '</pre>';
        }
        
    } catch (Exception $e) {
        echo '<p class="error">❌ Error en lista.php: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>7. JavaScript Console Check</h2>';
    echo '<p>Abre la consola del navegador (F12) y revisa si hay errores JavaScript</p>';
    echo '<p>Carga la página principal y mira la pestaña Network</p>';
    echo '</div>';
    ?>
    
    <div class="section">
        <h2>🔧 Acciones Recomendadas</h2>
        <ol>
            <li>Si la conexión a BD falla, verifica que MySQL esté corriendo en XAMPP</li>
            <li>Si el directorio uploads no es escribible, cambia los permisos</li>
            <li>Si la sesión no funciona, verifica las cookies del navegador</li>
            <li>Si lista.php no responde, revisa errores de sintaxis PHP</li>
        </ol>
    </div>
</body>
</html>
