<?php
/**
 * Script de prueba para verificar la conexión a la base de datos
 */

echo "<h2>🔍 Verificación de Base de Datos</h2>";

// 1. Verificar extensiones PHP requeridas
echo "<h3>✅ Extensiones PHP</h3>";
$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'session'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext: Instalada<br>";
    } else {
        echo "❌ $ext: NO INSTALADA - Requerida<br>";
    }
}

// 2. Probar conexión PDO
echo "<h3>🔗 Prueba de Conexión PDO</h3>";
try {
    $dsn = "mysql:host=localhost;dbname=portal_documentos;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✅ Conexión PDO exitosa<br>";
    
    // 3. Verificar si la base de datos existe
    echo "<h3>📊 Verificación de Base de Datos</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "❌ La base de datos 'portal_documentos' está vacía o no existe<br>";
        echo "📝 Debes importar el archivo database.sql<br>";
    } else {
        echo "✅ Tablas encontradas:<br>";
        foreach ($tables as $table) {
            echo "  - $table<br>";
        }
    }
    
    // 4. Verificar usuario admin
    if (in_array('usuarios', $tables)) {
        echo "<h3>👤 Verificación de Usuario Admin</h3>";
        $stmt = $pdo->prepare("SELECT username, password FROM usuarios WHERE username = ?");
        $stmt->execute(['admin']);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "✅ Usuario 'admin' encontrado<br>";
            echo "🔐 Password hash: " . substr($user['password'], 0, 20) . "...<br>";
            
            // Verificar si el password es correcto
            if (password_verify('admin', $user['password'])) {
                echo "✅ Password verification: CORRECTO<br>";
            } else {
                echo "❌ Password verification: INCORRECTO<br>";
            }
        } else {
            echo "❌ Usuario 'admin' NO encontrado<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
    echo "<h3>🛠️ Soluciones Posibles:</h3>";
    echo "1. Verifica que MySQL/MariaDB esté corriendo en XAMPP<br>";
    echo "2. Confirma que la base de datos 'portal_documentos' exista<br>";
    echo "3. Importa el archivo database.sql en phpMyAdmin<br>";
    echo "4. Verifica que el usuario 'root' no tenga contraseña<br>";
}

echo "<h3>📁 Información del Servidor</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Current File: " . __FILE__ . "<br>";

echo "<h3>🔧 Prueba de Login</h3>";
echo "<form method='post' action='login.php'>";
echo "Usuario: <input type='text' name='username' value='admin'><br><br>";
echo "Contraseña: <input type='password' name='password' value='admin'><br><br>";
echo "<input type='submit' value='Probar Login'>";
echo "</form>";
?>
