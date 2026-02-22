-- Script SQL para crear la base de datos y tabla del sistema de gestión de documentos
-- Compatible con MySQL/MariaDB

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS portal_documentos 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_spanish_ci;

-- Usar la base de datos
USE portal_documentos;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    rol ENUM('admin', 'usuario') DEFAULT 'usuario',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME,
    activo BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB;

-- Insertar usuario administrador por defecto (admin/admin)
INSERT INTO usuarios (username, password, nombre_completo, email, rol) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador del Sistema', 'admin@empresa.com', 'admin')
ON DUPLICATE KEY UPDATE username = username;

-- Tabla de documentos
CREATE TABLE IF NOT EXISTS documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_original VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    descripcion TEXT,
    estado ENUM('vigente', 'por_vencer', 'vencido') NOT NULL,
    fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT NOT NULL,
    tamano_archivo BIGINT NOT NULL,
    tipo_archivo VARCHAR(50) NOT NULL,
    extension VARCHAR(10) NOT NULL,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_estado (estado),
    INDEX idx_fecha_vencimiento (fecha_vencimiento),
    INDEX idx_usuario (usuario_id),
    INDEX idx_uuid (uuid)
) ENGINE=InnoDB;

-- Tabla de logs de actividad (opcional, para auditoría)
CREATE TABLE IF NOT EXISTS logs_actividad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    accion VARCHAR(50) NOT NULL,
    descripcion TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_fecha (usuario_id, fecha_registro),
    INDEX idx_accion (accion)
) ENGINE=InnoDB;

-- Vista para obtener estadísticas rápidas
CREATE OR REPLACE VIEW vista_estadisticas_documentos AS
SELECT 
    COUNT(*) as total_documentos,
    SUM(CASE WHEN estado = 'vigente' THEN 1 ELSE 0 END) as vigentes,
    SUM(CASE WHEN estado = 'por_vencer' THEN 1 ELSE 0 END) as por_vencer,
    SUM(CASE WHEN estado = 'vencido' THEN 1 ELSE 0 END) as vencidos,
    SUM(tamano_archivo) as espacio_total_ocupado
FROM documentos;

-- Procedimiento para actualizar estados automáticamente
DELIMITER //
CREATE PROCEDURE actualizar_estados_documentos()
BEGIN
    UPDATE documentos 
    SET estado = CASE
        WHEN fecha_vencimiento < CURDATE() THEN 'vencido'
        WHEN fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'por_vencer'
        ELSE 'vigente'
    END
    WHERE estado != CASE
        WHEN fecha_vencimiento < CURDATE() THEN 'vencido'
        WHEN fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'por_vencer'
        ELSE 'vigente'
    END;
END //
DELIMITER ;

-- Evento para ejecutar el procedimiento automáticamente cada día (opcional)
-- SET GLOBAL event_scheduler = ON;
-- CREATE EVENT IF NOT EXISTS actualizar_estados_diario
-- ON SCHEDULE EVERY 1 DAY
-- STARTS CURRENT_TIMESTAMP
-- DO CALL actualizar_estados_documentos();

COMMIT;
