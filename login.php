<?php
/**
 * Página de Login del Sistema de Gestión de Documentos
 */

require_once 'config.php';

// Si ya está autenticado, redirigir al dashboard
if (estaAutenticado()) {
    header('Location: index.php');
    exit;
}

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    $errores = [];
    
    // Validaciones
    if (empty($username)) {
        $errores[] = 'El nombre de usuario es obligatorio';
    }
    
    if (empty($password)) {
        $errores[] = 'La contraseña es obligatoria';
    }
    
    if (empty($errores)) {
        try {
            $db = Database::getInstance();
            
            // Buscar usuario en la base de datos
            $sql = "SELECT id, username, password, nombre_completo, email, rol, activo 
                    FROM usuarios 
                    WHERE username = ? AND activo = 1";
            $stmt = $db->query($sql, [$username]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($password, $usuario['password'])) {
                // Iniciar sesión
                iniciarSesion();
                
                // Regenerar ID de sesión para seguridad
                session_regenerate_id(true);
                
                // Guardar datos en sesión
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['username'] = $usuario['username'];
                $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
                $_SESSION['rol'] = $usuario['rol'];
                $_SESSION['login_time'] = time();
                
                // Actualizar último acceso
                $sql_update = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?";
                $db->query($sql_update, [$usuario['id']]);
                
                // Registrar actividad
                registrarActividad($usuario['id'], 'login', 'Inicio de sesión exitoso');
                
                // Configurar duración de sesión si se seleccionó "recordar"
                if ($remember) {
                    ini_set('session.cookie_lifetime', SESSION_LIFETIME * 24); // 48 horas
                }
                
                // Redirigir al dashboard
                $_SESSION['flash_message'] = '¡Bienvenido ' . htmlspecialchars($usuario['nombre_completo']) . '!';
                $_SESSION['flash_type'] = 'success';
                header('Location: index.php');
                exit;
                
            } else {
                // Registrar intento fallido
                registrarActividad(0, 'login_fallido', 'Intento de login fallido para usuario: ' . $username);
                $errores[] = 'Usuario o contraseña incorrectos';
            }
            
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            $errores[] = 'Error del sistema. Por favor, intente más tarde.';
        }
    }
    
    // Si hay errores, guardarlos en sesión para mostrarlos
    if (!empty($errores)) {
        $_SESSION['flash_message'] = implode('<br>', $errores);
        $_SESSION['flash_type'] = 'danger';
    }
}

$page_title = 'Iniciar Sesión';
$page_description = 'Acceso al sistema de gestión de documentos';
$show_breadcrumb = false;
$requiere_autenticacion = false;

include 'header.php';
?>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-file-alt"></i>
            </div>
            <h2><?php echo APP_NAME; ?></h2>
            <p>Sistema de Gestión de Documentos</p>
        </div>
        
        <div class="login-body">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible">
                    <?php 
                    echo $_SESSION['flash_message'];
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                    ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" id="loginForm" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Nombre de Usuario
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        placeholder="Ingresa tu nombre de usuario"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        required
                        autocomplete="username"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Contraseña
                    </label>
                    <div class="password-input-group">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="Ingresa tu contraseña"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="checkmark"></span>
                        Recordar mi sesión
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg btn-block" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    Iniciar Sesión
                </button>
            </form>
            
            <div class="login-help">
                <div class="demo-credentials">
                    <h4><i class="fas fa-info-circle"></i> Credenciales de Demo</h4>
                    <div class="demo-info">
                        <p><strong>Usuario:</strong> admin</p>
                        <p><strong>Contraseña:</strong> admin</p>
                    </div>
                </div>
                
                <div class="login-tips">
                    <h4><i class="fas fa-shield-alt"></i> Seguridad</h4>
                    <ul>
                        <li>Mantén tu contraseña segura</li>
                        <li>Cierra sesión al terminar</li>
                        <li>No compartas tus credenciales</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?></p>
            <div class="footer-links">
                <a href="#"><i class="fas fa-question-circle"></i> Ayuda</a>
                <a href="#"><i class="fas fa-envelope"></i> Soporte</a>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para la página de login */
.login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    padding: 2rem;
}

.login-card {
    background: var(--white);
    border-radius: 1rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    width: 100%;
    max-width: 450px;
    animation: slideUp 0.5s ease;
}

.login-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: var(--white);
    text-align: center;
    padding: 2.5rem 2rem;
}

.login-logo {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    border: 3px solid rgba(255, 255, 255, 0.3);
}

.login-logo i {
    font-size: 2.5rem;
}

.login-header h2 {
    font-size: 1.75rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.login-header p {
    opacity: 0.9;
    margin: 0;
}

.login-body {
    padding: 2.5rem 2rem;
}

.login-form .form-group {
    margin-bottom: 1.5rem;
}

.login-form label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--gray-700);
}

.password-input-group {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--gray-400);
    cursor: pointer;
    padding: 0.25rem;
}

.password-toggle:hover {
    color: var(--gray-600);
}

.checkbox-group {
    margin-bottom: 2rem !important;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    font-weight: 400;
    color: var(--gray-600);
}

.checkbox-label input[type="checkbox"] {
    display: none;
}

.checkmark {
    width: 18px;
    height: 18px;
    border: 2px solid var(--gray-300);
    border-radius: 3px;
    position: relative;
    transition: var(--transition);
}

.checkbox-label input[type="checkbox"]:checked + .checkmark {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.checkbox-label input[type="checkbox"]:checked + .checkmark::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: var(--white);
    font-size: 12px;
    font-weight: bold;
}

.btn-block {
    width: 100%;
}

.login-help {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--gray-200);
}

.demo-credentials,
.login-tips {
    margin-bottom: 1.5rem;
}

.demo-credentials h4,
.login-tips h4 {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.demo-info {
    background: rgba(0, 123, 255, 0.1);
    border: 1px solid var(--primary-color);
    border-radius: var(--border-radius);
    padding: 1rem;
}

.demo-info p {
    margin: 0.25rem 0;
    font-size: 0.9rem;
}

.login-tips ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.login-tips li {
    font-size: 0.85rem;
    color: var(--gray-600);
    margin-bottom: 0.5rem;
    padding-left: 1rem;
    position: relative;
}

.login-tips li::before {
    content: '•';
    position: absolute;
    left: 0;
    color: var(--primary-color);
}

.login-footer {
    background: var(--gray-100);
    padding: 1.5rem 2rem;
    text-align: center;
    border-top: 1px solid var(--gray-200);
}

.login-footer p {
    margin: 0 0 0.75rem 0;
    font-size: 0.85rem;
    color: var(--gray-600);
}

.footer-links {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
}

.footer-links a {
    color: var(--primary-color);
    text-decoration: none;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: var(--transition);
}

.footer-links a:hover {
    color: var(--primary-dark);
}

/* Responsive */
@media (max-width: 480px) {
    .login-container {
        padding: 1rem;
    }
    
    .login-card {
        max-width: 100%;
    }
    
    .login-header,
    .login-body,
    .login-footer {
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }
    
    .login-header {
        padding-top: 2rem;
        padding-bottom: 2rem;
    }
    
    .login-logo {
        width: 60px;
        height: 60px;
    }
    
    .login-logo i {
        font-size: 2rem;
    }
    
    .login-header h2 {
        font-size: 1.5rem;
    }
}
</style>

<script>
// Función para mostrar/ocultar contraseña
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const passwordIcon = document.getElementById('passwordIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.className = 'fas fa-eye-slash';
    } else {
        passwordInput.type = 'password';
        passwordIcon.className = 'fas fa-eye';
    }
}

// Validación del formulario
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    
    if (!username) {
        e.preventDefault();
        showNotification('Por favor, ingresa tu nombre de usuario', 'warning');
        document.getElementById('username').focus();
        return false;
    }
    
    if (!password) {
        e.preventDefault();
        showNotification('Por favor, ingresa tu contraseña', 'warning');
        document.getElementById('password').focus();
        return false;
    }
    
    // Mostrar loading
    const loginBtn = document.getElementById('loginBtn');
    loginBtn.disabled = true;
    loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando sesión...';
});

// Auto-focus en el campo username
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('username').focus();
    
    // Permitir Enter en cualquier campo para enviar formulario
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });
    });
});
</script>

<?php include 'footer.php'; ?>
