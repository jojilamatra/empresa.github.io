# Portal de Gestión de Documentos

Sistema completo de gestión de documentos empresarial con alertas de vencimiento, desarrollado en PHP 8+, MySQL/MariaDB, HTML5, CSS3 y JavaScript vanilla.

## 🚀 Características Principales

### 📁 Gestión de Documentos
- **Carga de archivos**: Subida individual o por lotes con drag-and-drop
- **Formatos soportados**: PDF, Word (.doc/.docx), Excel (.xls/.xlsx), imágenes (.jpg/.png)
- **Almacenamiento seguro**: Archivos guardados con nombres UUID únicos
- **Control de vencimiento**: Sistema automático de alertas por fecha de vencimiento

### 🎨 Diseño Profesional
- **Paleta de colores**: Azul corporativo (#007BFF), blanco (#FFFFFF), rojo para alertas (#DC3545)
- **Interfaz moderna**: Diseño responsive y profesional
- **Gradientes sutiles**: Elegantes transiciones azul-blanco
- **Sin frameworks pesados**: JavaScript vanilla para máximo rendimiento

### 🔐 Seguridad
- **Autenticación segura**: Sistema de login con password hashing
- **Prepared statements**: Protección contra inyección SQL
- **Validación de archivos**: Control estricto de tipos y tamaños
- **Sesiones seguras**: Configuración robusta de cookies

### 📊 Dashboard y Reportes
- **Estadísticas en tiempo real**: Cards con conteos por estado
- **Alertas inteligentes**: Sistema de notificaciones por vencimiento
- **Exportación PDF**: Reportes generados con TCPDF
- **Búsqueda y filtros**: Búsqueda rápida y filtrado por estado

## 📋 Requisitos del Sistema

### Servidor
- **PHP**: 8.0 o superior
- **Base de datos**: MySQL 5.7+ o MariaDB 10.2+
- **Servidor web**: Apache (con mod_rewrite) o Nginx
- **Extensiones PHP**: 
  - `pdo_mysql`
  - `mbstring`
  - `fileinfo`
  - `json`
  - `session`

### Opcional (para PDF)
- **TCPDF**: Para generación de reportes PDF
  ```bash
  composer require tecnickcom/tcpdf
  ```

## 🛠️ Instalación

### 1. Clonar/Descargar los archivos
```bash
# Copiar los archivos al directorio web
cp -r portal_documentos /xampp/htdocs/
```

### 2. Configurar la base de datos
```bash
# Importar el script SQL
mysql -u root -p < database.sql
```

O ejecutar manualmente el contenido de `database.sql` en phpMyAdmin.

### 3. Configurar permisos
```bash
# Dar permisos de escritura al directorio uploads
chmod 755 uploads/
chmod 755 uploads/*
```

### 4. Verificar configuración
Editar `config.php` si necesitas ajustar las credenciales de la base de datos:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'portal_documentos');
define('DB_USER', 'root');
define('DB_PASS', '');
```

## 🚀 Acceso al Sistema

### URL Principal
```
http://localhost/portal_documentos/
```

### Credenciales de Demo
- **Usuario**: `admin`
- **Contraseña**: `admin`

## 📁 Estructura de Archivos

```
portal_documentos/
├── database.sql              # Script de base de datos
├── config.php                # Configuración principal
├── header.php                # Header modular
├── footer.php                # Footer modular
├── index.php                 # Dashboard principal
├── login.php                 # Página de login
├── logout.php                # Cierre de sesión
├── upload.php                # Procesador de carga
├── lista.php                 # API AJAX para documentos
├── delete.php                # Eliminación de documentos
├── download.php              # Descarga de archivos
├── export_pdf.php            # Exportación PDF
├── style.css                 # Hoja de estilos
├── script.js                 # JavaScript principal
├── uploads/                  # Directorio de archivos
└── README.md                 # Este archivo
```

## 💡 Uso del Sistema

### 1. Iniciar Sesión
- Accede a `http://localhost/portal_documentos/`
- Ingresa con las credenciales de demo

### 2. Cargar Documentos
- Haz clic en "Cargar Nuevo Documento"
- Arrastra archivos o selecciónalos
- Completa la descripción y fecha de vencimiento
- Haz clic en "Cargar Documento(s)"

### 3. Gestionar Documentos
- **Ver**: Click en el ícono del ojo
- **Descargar**: Click en el ícono de descarga
- **Eliminar**: Click en el ícono de la papelera

### 4. Alertas de Vencimiento
- **Verde**: Vigentes (>30 días)
- **Amarillo/Naranja**: Por vencer (1-30 días)
- **Rojo**: Vencidos (<1 día o pasado)

## 🔧 Configuración Avanzada

### Tamaño Máximo de Archivos
En `config.php`:
```php
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
```

### Tipos de Archivos Permitidos
En `config.php`:
```php
define('ALLOWED_EXTENSIONS', [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    // ... más tipos
]);
```

### Tiempo de Sesión
En `config.php`:
```php
define('SESSION_LIFETIME', 7200); // 2 horas
```

## 🐛 Solución de Problemas

### Problemas Comunes

#### 1. Error de conexión a la base de datos
- Verifica que MySQL/MariaDB esté corriendo
- Confirma las credenciales en `config.php`
- Asegúrate que la base de datos `portal_documentos` exista

#### 2. Error al subir archivos
- Verifica permisos del directorio `uploads/`
- Confirma que el tamaño no exceda el límite
- Revisa que el tipo de archivo sea permitido

#### 3. Error de sesión
- Verifica que las cookies estén habilitadas
- Confirma que `session.save_path` sea escribible
- Revisa la configuración de `session.cookie_domain`

#### 4. PDF no se genera
- Instala TCPDF: `composer require tecnickcom/tcpdf`
- Verifica que `vendor/tcpdf/tcpdf.php` exista
- Confirma permisos de escritura

### Logs de Errores
Revisa los logs de errores de PHP:
```bash
# En XAMPP
tail -f C:/xampp/apache/logs/error.log

# O revisa el log de PHP
php -i | grep error_log
```

## 🔒 Consideraciones de Seguridad

### Para Producción
1. **Cambiar credenciales**: Modifica usuario/contraseña de la base de datos
2. **HTTPS**: Configura SSL/TLS
3. **CORS**: Ajusta headers si es necesario
4. **Firewall**: Configura reglas adecuadas
5. **Backups**: Implementa respaldos regulares

### Recomendaciones
- Mantén PHP y las dependencias actualizadas
- Usa contraseñas fuertes
- Limita los intentos de login
- Monitorea los logs de actividad
- Implementa auditoría regular

## 📞 Soporte

### Documentación Adicional
- **Manual de Usuario**: Contacta al administrador
- **API Documentation**: Revisa los comentarios en el código
- **Base de Conocimiento**: Documentación interna

### Contacto
- **Email de Soporte**: admin@empresa.com
- **Issues**: Reporta problemas al administrador del sistema

## 📄 Licencia

Este software es propiedad de la empresa y está sujeto a los términos y condiciones de uso corporativos.

---

**Versión**: 1.0.0  
**Última Actualización**: <?php echo date('d/m/Y'); ?>  
**Desarrollado por**: Equipo de Desarrollo Interno
