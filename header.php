<?php
/**
 * Header modular del Sistema de Gestión de Documentos
 * Contiene la estructura común del sitio web
 */

require_once 'config.php';

// Iniciar sesión si es necesario
if (isset($requiere_autenticacion) && $requiere_autenticacion) {
    requerirAutenticacion();
}

// Obtener información del usuario si está autenticado
$usuario_actual = null;
if (estaAutenticado()) {
    try {
        $db = Database::getInstance();
        $sql = "SELECT id, username, nombre_completo, email, rol FROM usuarios WHERE id = ? AND activo = 1";
        $stmt = $db->query($sql, [$_SESSION['usuario_id']]);
        $usuario_actual = $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error al obtener usuario: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'Sistema de gestión de documentos empresarial'; ?>">
    <meta name="author" content="<?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    
    <!-- CSS -->
    <link rel="stylesheet" href="style.css">
    
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- JavaScript -->
    <script src="script.js"></script>
</head>
<body>
    <!-- Header Principal -->
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-file-alt"></i>
                    <h1><?php echo APP_NAME; ?></h1>
                </div>
                
                <?php if (estaAutenticado() && $usuario_actual): ?>
                <nav class="main-nav">
                    <ul class="nav-menu">
                        <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a></li>
                        <li><a href="index.php#upload" class="<?php echo isset($_GET['action']) && $_GET['action'] == 'upload' ? 'active' : ''; ?>">
                            <i class="fas fa-upload"></i> Subir Documento
                        </a></li>
                        <li><a href="export_pdf.php" target="_blank">
                            <i class="fas fa-file-pdf"></i> Exportar PDF
                        </a></li>
                    </ul>
                </nav>
                
                <div class="user-menu">
                    <div class="user-info">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($usuario_actual['nombre_completo']); ?>&background=007BFF&color=fff&size=32" alt="Avatar" class="user-avatar">
                        <span class="user-name"><?php echo htmlspecialchars($usuario_actual['nombre_completo']); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($usuario_actual['rol']); ?></span>
                    </div>
                    <div class="user-dropdown">
                        <button class="dropdown-toggle" onclick="toggleDropdown()">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu" id="userDropdown">
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-user"></i> Mi Perfil
                            </a>
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-cog"></i> Configuración
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item logout">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Mensajes de notificación -->
    <div id="notification-container" class="notification-container"></div>

    <?php if (estaAutenticado()): ?>
    <!-- Barra de estado -->
    <div class="status-bar">
        <div class="container">
            <div class="status-content">
                <div class="status-info">
                    <span class="status-item">
                        <i class="fas fa-clock"></i>
                        Último acceso: <?php echo isset($usuario_actual['ultimo_acceso']) ? date('d/m/Y H:i', strtotime($usuario_actual['ultimo_acceso'])) : 'Primera vez'; ?>
                    </span>
                    <span class="status-item">
                        <i class="fas fa-calendar"></i>
                        <?php echo date('d/m/Y'); ?>
                    </span>
                </div>
                <div class="quick-stats" id="quickStats">
                    <!-- Las estadísticas se cargarán vía AJAX -->
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Contenido Principal -->
    <main class="main-content">
        <div class="container">
            <?php if (isset($show_breadcrumb) && $show_breadcrumb): ?>
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <ol class="breadcrumb-list">
                    <li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <?php if (isset($breadcrumb)): ?>
                        <?php foreach ($breadcrumb as $item): ?>
                            <li>
                                <?php if (isset($item['link'])): ?>
                                    <a href="<?php echo $item['link']; ?>"><?php echo htmlspecialchars($item['text']); ?></a>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($item['text']); ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ol>
            </nav>
            <?php endif; ?>

            <!-- Page Header -->
            <?php if (isset($page_header)): ?>
            <div class="page-header">
                <div class="page-header-content">
                    <h2 class="page-title"><?php echo htmlspecialchars($page_header['title']); ?></h2>
                    <?php if (isset($page_header['subtitle'])): ?>
                    <p class="page-subtitle"><?php echo htmlspecialchars($page_header['subtitle']); ?></p>
                    <?php endif; ?>
                </div>
                <?php if (isset($page_header['actions'])): ?>
                <div class="page-actions">
                    <?php foreach ($page_header['actions'] as $action): ?>
                        <?php if (isset($action['modal'])): ?>
                            <button class="btn btn-<?php echo $action['type'] ?? 'primary'; ?>" onclick="<?php echo $action['modal']; ?>">
                                <i class="fas fa-<?php echo $action['icon'] ?? 'plus'; ?>"></i>
                                <?php echo htmlspecialchars($action['text']); ?>
                            </button>
                        <?php else: ?>
                            <a href="<?php echo $action['link']; ?>" class="btn btn-<?php echo $action['type'] ?? 'primary'; ?>">
                                <i class="fas fa-<?php echo $action['icon'] ?? 'arrow-right'; ?>"></i>
                                <?php echo htmlspecialchars($action['text']); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Mensajes Flash -->
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible">
                    <i class="fas fa-<?php echo $_SESSION['flash_type'] ?? 'info'; ?>"></i>
                    <?php 
                    echo htmlspecialchars($_SESSION['flash_message']);
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                    ?>
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>
?>
