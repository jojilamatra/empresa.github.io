<?php
/**
 * Archivo de configuración del Sistema de Gestión de Documentos
 * Contiene todas las constantes y variables de configuración
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'portal_documentos');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('APP_NAME', 'Portal de Gestión de Documentos');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/EMPRESAS%20POLAR/');
define('UPLOAD_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB en bytes

// Configuración de sesión
define('SESSION_LIFETIME', 7200); // 2 horas en segundos
define('SESSION_NAME', 'portal_docs_session');

// Configuración de seguridad
define('HASH_ALGO', PASSWORD_DEFAULT);
define('SALT_LENGTH', 32);

// Configuración de archivos permitidos
define('ALLOWED_EXTENSIONS', [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
]);

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City');

// Configuración de errores (desactivar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de headers de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

/**
 * Clase para manejar la conexión a la base de datos
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error en consulta: " . $e->getMessage());
            throw new Exception("Error en la consulta a la base de datos");
        }
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}

/**
 * Funciones de utilidad
 */

/**
 * Generar un UUID único
 */
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * Sanitizar entrada de datos
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar formato de fecha
 */
function validarFecha($fecha) {
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d && $d->format('Y-m-d') === $fecha;
}

/**
 * Calcular estado del documento basado en fecha de vencimiento
 */
function calcularEstado($fechaVencimiento) {
    $hoy = new DateTime();
    $vencimiento = new DateTime($fechaVencimiento);
    $diferencia = $hoy->diff($vencimiento);
    
    if ($vencimiento < $hoy) {
        return 'vencido';
    } elseif ($diferencia->days <= 30) {
        return 'por_vencer';
    } else {
        return 'vigente';
    }
}

/**
 * Formatear tamaño de archivo
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Obtener icono según tipo de archivo
 */
function getFileIcon($extension) {
    $icons = [
        'pdf' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="#DC3545"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18.5,9H13V3.5L18.5,9M6,20V4H11V10H18V20H6Z"/></svg>',
        'doc' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="#007BFF"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18.5,9H13V3.5L18.5,9M6,20V4H11V10H18V20H6Z"/></svg>',
        'docx' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="#007BFF"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18.5,9H13V3.5L18.5,9M6,20V4H11V10H18V20H6Z"/></svg>',
        'xls' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="#28A745"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18.5,9H13V3.5L18.5,9M6,20V4H11V10H18V20H6Z"/></svg>',
        'xlsx' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="#28A745"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18.5,9H13V3.5L18.5,9M6,20V4H11V10H18V20H6Z"/></svg>',
        'jpg' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="#6C757D"><path d="M8.5,13.5L11,16.5L14.5,12L19,18H5M21,19V5C21,3.89 20.1,3 19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19Z"/></svg>',
        'jpeg' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="#6C757D"><path d="M8.5,13.5L11,16.5L14.5,12L19,18H5M21,19V5C21,3.89 20.1,3 19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19Z"/></svg>',
        'png' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="#6C757D"><path d="M8.5,13.5L11,16.5L14.5,12L19,18H5M21,19V5C21,3.89 20.1,3 19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19Z"/></svg>'
    ];
    
    return isset($icons[strtolower($extension)]) ? $icons[strtolower($extension)] : '<svg width="24" height="24" viewBox="0 0 24 24" fill="#6C757D"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18.5,9H13V3.5L18.5,9M6,20V4H11V10H18V20H6Z"/></svg>';
}

/**
 * Iniciar sesión segura
 */
function iniciarSesion() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS
        session_name(SESSION_NAME);
        session_start();
    }
}

/**
 * Verificar si el usuario está autenticado
 */
function estaAutenticado() {
    iniciarSesion();
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Redirigir si no está autenticado
 */
function requerirAutenticacion() {
    if (!estaAutenticado()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Registrar actividad en el log
 */
function registrarActividad($usuarioId, $accion, $descripcion = '') {
    try {
        $db = Database::getInstance();
        $sql = "INSERT INTO logs_actividad (usuario_id, accion, descripcion, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)";
        $params = [
            $usuarioId,
            $accion,
            $descripcion,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        $db->query($sql, $params);
    } catch (Exception $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
    }
}
?>
