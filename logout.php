<?php
/**
 * Página de cierre de sesión
 * Maneja el logout seguro del sistema
 */

require_once 'config.php';

// Iniciar sesión si no está iniciada
iniciarSesion();

// Verificar si hay una sesión activa
if (estaAutenticado()) {
    // Registrar actividad de logout
    try {
        $usuario_id = $_SESSION['usuario_id'] ?? 0;
        $username = $_SESSION['username'] ?? 'desconocido';
        
        registrarActividad($usuario_id, 'logout', "Cierre de sesión del usuario: {$username}");
        
        // Destruir todas las variables de sesión
        $_SESSION = [];
        
        // Eliminar la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
        
        // Mensaje de éxito
        $_SESSION['flash_message'] = 'Has cerrado sesión correctamente. ¡Hasta pronto!';
        $_SESSION['flash_type'] = 'success';
        
    } catch (Exception $e) {
        error_log("Error en logout.php: " . $e->getMessage());
        
        // Aún así intentar destruir la sesión
        session_destroy();
        
        $_SESSION['flash_message'] = 'Sesión cerrada. Ocurrió un error al registrar la actividad.';
        $_SESSION['flash_type'] = 'warning';
    }
} else {
    // Si no hay sesión activa, redirigir con mensaje informativo
    $_SESSION['flash_message'] = 'No había una sesión activa.';
    $_SESSION['flash_type'] = 'info';
}

// Redirigir a la página de login
header('Location: login.php');
exit;
?>
