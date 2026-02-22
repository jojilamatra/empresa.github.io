<?php
/**
 * Script para corregir el password del usuario admin
 */

require_once 'config.php';

echo "<h2>🔧 Corrección de Password Admin</h2>";

try {
    $db = Database::getInstance();
    
    // Generar nuevo hash para "admin"
    $new_password = 'admin';
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    echo "<h3>📝 Nuevo Password Hash:</h3>";
    echo "<code>$new_hash</code><br><br>";
    
    // Actualizar el password en la base de datos
    $sql = "UPDATE usuarios SET password = ? WHERE username = 'admin'";
    $stmt = $db->query($sql, [$new_hash]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Password actualizado correctamente<br><br>";
        
        // Verificar el nuevo password
        $sql_check = "SELECT password FROM usuarios WHERE username = 'admin'";
        $stmt_check = $db->query($sql_check);
        $user = $stmt_check->fetch();
        
        if ($user && password_verify('admin', $user['password'])) {
            echo "✅ Verificación de password: CORRECTA<br><br>";
            echo "🎉 Ahora puedes hacer login con:<br>";
            echo "<strong>Usuario:</strong> admin<br>";
            echo "<strong>Contraseña:</strong> admin<br><br>";
            
            echo '<a href="login.php" style="background: #007BFF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Ir al Login</a>';
        } else {
            echo "❌ Error en la verificación del nuevo password<br>";
        }
    } else {
        echo "❌ No se pudo actualizar el password<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>
