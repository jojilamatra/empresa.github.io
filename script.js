/**
 * Sistema de Gestión de Documentos - JavaScript Principal
 * Funcionalidades interactivas y utilidades del sistema
 */

// ===== VARIABLES GLOBALES =====
let selectedFiles = [];
let isUploading = false;

// ===== FUNCIONES DE MODALES =====

/**
 * Abrir modal
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Focus en el primer input del modal
        const firstInput = modal.querySelector('input, textarea, select');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
}

/**
 * Cerrar modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        
        // Limpiar formulario si es el modal de upload
        if (modalId === 'uploadModal') {
            resetUploadForm();
        }
    }
}

/**
 * Cerrar modales al hacer clic fuera
 */
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal(e.target.id);
    }
});

/**
 * Cerrar modales con tecla Escape
 */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            closeModal(openModal.id);
        }
    }
});

// ===== FUNCIONES DE NOTIFICACIONES =====

/**
 * Mostrar notificación
 */
function showNotification(message, type = 'info', duration = 5000) {
    const container = document.getElementById('notification-container');
    if (!container) return;
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Auto-remover después del tiempo especificado
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, duration);
}

/**
 * Obtener icono para notificación
 */
function getNotificationIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// ===== FUNCIONES DE LOADING =====

/**
 * Mostrar loading overlay
 */
function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.add('show');
    }
}

/**
 * Ocultar loading overlay
 */
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.remove('show');
    }
}

// ===== FUNCIONES DE UPLOAD DE ARCHIVOS =====

/**
 * Inicializar drag and drop
 */
function initializeDragDrop() {
    const uploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('documentFiles');
    
    if (!uploadArea || !fileInput) return;
    
    // Prevenir comportamientos por defecto
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });
    
    // Resaltar área de arrastre
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => {
            uploadArea.classList.add('dragover');
        }, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => {
            uploadArea.classList.remove('dragover');
        }, false);
    });
    
    // Manejar drop
    uploadArea.addEventListener('drop', handleDrop, false);
    
    // Manejar selección de archivos
    fileInput.addEventListener('change', handleFileSelect, false);
}

/**
 * Prevenir comportamientos por defecto
 */
function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

/**
 * Manejar drop de archivos
 */
function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    handleFiles(files);
}

/**
 * Manejar selección de archivos
 */
function handleFileSelect(e) {
    const files = e.target.files;
    handleFiles(files);
}

/**
 * Procesar archivos seleccionados
 */
function handleFiles(files) {
    const fileArray = Array.from(files);
    const validFiles = [];
    const errors = [];
    
    fileArray.forEach(file => {
        // Validar tipo de archivo
        const extension = file.name.split('.').pop().toLowerCase();
        const allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
        
        if (!allowedTypes.includes(extension)) {
            errors.push(`${file.name}: Tipo de archivo no permitido`);
            return;
        }
        
        // Validar tamaño (10MB)
        if (file.size > 10 * 1024 * 1024) {
            errors.push(`${file.name}: El archivo excede 10MB`);
            return;
        }
        
        validFiles.push(file);
    });
    
    // Mostrar errores si hay
    if (errors.length > 0) {
        showNotification(errors.join('<br>'), 'error');
    }
    
    // Agregar archivos válidos a la lista
    selectedFiles = [...selectedFiles, ...validFiles];
    updateFileList();
}

/**
 * Actualizar lista de archivos seleccionados
 */
function updateFileList() {
    const fileList = document.getElementById('fileList');
    if (!fileList) return;
    
    if (selectedFiles.length === 0) {
        fileList.innerHTML = '';
        return;
    }
    
    fileList.innerHTML = selectedFiles.map((file, index) => `
        <div class="file-item">
            <div class="file-info">
                <div class="file-icon">
                    ${getFileIcon(file.name.split('.').pop().toLowerCase())}
                </div>
                <div class="file-details">
                    <div class="file-name">${file.name}</div>
                    <div class="file-size">${formatFileSize(file.size)}</div>
                </div>
            </div>
            <button class="file-remove" onclick="removeFile(${index})" title="Quitar archivo">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');
}

/**
 * Remover archivo de la lista
 */
function removeFile(index) {
    selectedFiles.splice(index, 1);
    updateFileList();
}

/**
 * Obtener icono de archivo
 */
function getFileIcon(extension) {
    const icons = {
        'pdf': '<svg width="24" height="24" viewBox="0 0 24 24" fill="#DC3545"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18.5,9H13V3.5L18.5,9M6,20V4H11V10H18V20H6Z"/></svg>',
        'doc': '<svg width="24" height="24" viewBox="0 0 24 24" fill="#007BFF"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18.5,9H13V3.5L18.5,9M6,20V4H11V10H18V20H6Z"/></svg>',
        'docx': '<svg width="24" height="24" viewBox="0 0 24 24" fill="#007BFF"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18.5,9H13V3.5L18.5,9M6,20V4H11V10H18V20H6Z"/></svg>',
        'xls': '<svg width="24" height="24" viewBox="0 0 24 24" fill="#28A745"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18.5,9H13V3.5L18.5,9M6,20V4H11V10H18V20H6Z"/></svg>',
        'xlsx': '<svg width="24" height="24" viewBox="0 0 24 24" fill="#28A745"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18.5,9H13V3.5L18.5,9M6,20V4H11V10H18V20H6Z"/></svg>',
        'jpg': '<svg width="24" height="24" viewBox="0 0 24 24" fill="#6C757D"><path d="M8.5,13.5L11,16.5L14.5,12L19,18H5M21,19V5C21,3.89 20.1,3 19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19Z"/></svg>',
        'jpeg': '<svg width="24" height="24" viewBox="0 0 24 24" fill="#6C757D"><path d="M8.5,13.5L11,16.5L14.5,12L19,18H5M21,19V5C21,3.89 20.1,3 19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19Z"/></svg>',
        'png': '<svg width="24" height="24" viewBox="0 0 24 24" fill="#6C757D"><path d="M8.5,13.5L11,16.5L14.5,12L19,18H5M21,19V5C21,3.89 20.1,3 19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19Z"/></svg>'
    };
    return icons[extension] || '<svg width="24" height="24" viewBox="0 0 24 24" fill="#6C757D"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18.5,9H13V3.5L18.5,9M6,20V4H11V10H18V20H6Z"/></svg>';
}

/**
 * Formatear tamaño de archivo
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Resetear formulario de upload
 */
function resetUploadForm() {
    selectedFiles = [];
    updateFileList();
    
    const form = document.getElementById('uploadForm');
    if (form) {
        form.reset();
    }
    
    const fileInput = document.getElementById('documentFiles');
    if (fileInput) {
        fileInput.value = '';
    }
}

// ===== FUNCIONES DE FORMULARIO DE UPLOAD =====

/**
 * Inicializar formulario de upload
 */
function initializeUploadForm() {
    const form = document.getElementById('uploadForm');
    if (!form) return;
    
    form.addEventListener('submit', handleUploadSubmit, false);
    
    // Validar fecha de vencimiento
    const fechaInput = document.getElementById('fechaVencimiento');
    if (fechaInput) {
        // Establecer fecha mínima como mañana
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        fechaInput.min = tomorrow.toISOString().split('T')[0];
        
        fechaInput.addEventListener('change', validateFechaVencimiento, false);
    }
}

/**
 * Validar fecha de vencimiento
 */
function validateFechaVencimiento(e) {
    const input = e.target;
    const selectedDate = new Date(input.value);
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    if (selectedDate < tomorrow) {
        input.setCustomValidity('La fecha de vencimiento debe ser futura');
    } else {
        input.setCustomValidity('');
    }
}

/**
 * Manejar envío del formulario de upload
 */
function handleUploadSubmit(e) {
    e.preventDefault();
    
    if (selectedFiles.length === 0) {
        showNotification('Por favor, selecciona al menos un archivo', 'warning');
        return;
    }
    
    if (isUploading) {
        showNotification('Ya hay una carga en progreso', 'warning');
        return;
    }
    
    const formData = new FormData();
    const descripcion = document.getElementById('descripcion').value;
    const fechaVencimiento = document.getElementById('fechaVencimiento').value;
    
    // Agregar archivos al FormData
    selectedFiles.forEach(file => {
        formData.append('documentFiles[]', file);
    });
    
    // Agregar otros campos
    formData.append('descripcion', descripcion);
    formData.append('fechaVencimiento', fechaVencimiento);
    
    isUploading = true;
    showLoading();
    
    const uploadBtn = document.getElementById('uploadBtn');
    if (uploadBtn) {
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
    }
    
    fetch('upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeModal('uploadModal');
            
            // Recargar la lista de documentos
            if (typeof loadDocuments === 'function') {
                loadDocuments();
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión al subir archivos', 'error');
    })
    .finally(() => {
        isUploading = false;
        hideLoading();
        
        if (uploadBtn) {
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Cargar Documento(s)';
        }
    });
}

// ===== FUNCIONES DE SINCRONIZACIÓN =====

/**
 * Abrir modal de sincronización
 */
function openSyncModal() {
    document.getElementById('syncModal').classList.add('show');
}

/**
 * Probar conexión con portal externo
 */
function testPortalConnection() {
    const portalUrl = document.getElementById('portalUrl').value;
    const apiKey = document.getElementById('apiKey').value;
    
    if (!portalUrl || !apiKey) {
        showNotification('Por favor, complete la URL del portal y la API key', 'warning');
        return;
    }
    
    showLoading();
    
    fetch('sync_portal.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'test_connection',
            portal_url: portalUrl,
            api_key: apiKey
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Conexión exitosa con el portal externo', 'success');
        } else {
            showNotification('Error de conexión: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    })
    .finally(() => {
        hideLoading();
    });
}

/**
 * Realizar sincronización
 */
function performSync() {
    const portalUrl = document.getElementById('portalUrl').value;
    const apiKey = document.getElementById('apiKey').value;
    const syncType = document.getElementById('syncType').value;
    
    if (!portalUrl || !apiKey) {
        showNotification('Por favor, configure la URL del portal y la API key', 'warning');
        return;
    }
    
    showLoading();
    
    fetch('sync_portal.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'sync',
            portal_url: portalUrl,
            api_key: apiKey,
            sync_type: syncType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            
            // Actualizar resultados en el modal
            const resultsDiv = document.getElementById('syncResultsContent');
            if (resultsDiv) {
                resultsDiv.innerHTML = `
                    <div class="sync-success">
                        <h4>✅ Sincronización Exitosa</h4>
                        <p><strong>${data.data.imported || 0}</strong> documentos importados</p>
                        <p><strong>${data.data.updated || 0}</strong> documentos actualizados</p>
                        <p><strong>${data.data.skipped || 0}</strong> documentos omitidos</p>
                    </div>
                `;
            }
            
            // Recargar documentos después de sincronizar
            setTimeout(() => {
                loadDocuments();
            }, 2000);
            
        } else {
            showNotification('Error en sincronización: ' + data.message, 'error');
            
            const resultsDiv = document.getElementById('syncResultsContent');
            if (resultsDiv) {
                resultsDiv.innerHTML = `
                    <div class="sync-error">
                        <h4>❌ Error en Sincronización</h4>
                        <p>${data.message}</p>
                    </div>
                `;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    })
    .finally(() => {
        hideLoading();
    });
}

/**
 * Mostrar/ocultar resultados de sincronización
 */
function toggleSyncResults() {
    const resultsDiv = document.getElementById('syncResults');
    if (resultsDiv) {
        if (resultsDiv.style.display === 'none') {
            resultsDiv.style.display = 'block';
        } else {
            resultsDiv.style.display = 'none';
        }
    }
}

/**
 * Toggle dropdown de usuario
 */
function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

/**
 * Cerrar dropdown al hacer clic fuera
 */
document.addEventListener('click', function(e) {
    const userDropdown = document.getElementById('userDropdown');
    const dropdownToggle = document.querySelector('.dropdown-toggle');
    
    if (userDropdown && dropdownToggle && 
        !userDropdown.contains(e.target) && 
        !dropdownToggle.contains(e.target)) {
        userDropdown.classList.remove('show');
    }
});

// ===== FUNCIONES DE EXPORTACIÓN =====

/**
 * Toggle del menú de exportación
 */
function toggleExportMenu() {
    const menu = document.getElementById('exportMenu');
    if (menu) {
        menu.classList.toggle('show');
    }
}

/**
 * Cerrar menú de exportación al hacer clic fuera
 */
document.addEventListener('click', function(e) {
    const exportDropdown = document.querySelector('.export-dropdown');
    const exportMenu = document.getElementById('exportMenu');
    
    if (exportDropdown && exportMenu && 
        !exportDropdown.contains(e.target) && 
        !exportMenu.contains(e.target)) {
        exportMenu.classList.remove('show');
    }
});

// ===== FUNCIONES DE CARDS =====

/**
 * Renderizar documentos como cards
 */
function renderDocumentCards(documentos) {
    const grid = document.getElementById('documentsGrid');
    
    if (documentos.length === 0) {
        grid.innerHTML = `
            <div class="loading-cards">
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No hay documentos registrados</p>
                </div>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = documentos.map(doc => `
        <div class="document-card ${doc.estado}" data-id="${doc.id}">
            <div class="document-card-header">
                <div class="document-icon ${doc.extension}">
                    ${doc.icono}
                </div>
                <div class="document-info">
                    <div class="document-name" title="${doc.nombre_original}">${doc.nombre_original}</div>
                    <div class="document-meta">${doc.tamano_formateado} • ${doc.extension.toUpperCase()}</div>
                </div>
            </div>
            <div class="document-card-body">
                <div class="document-description">
                    ${doc.descripcion || 'Sin descripción'}
                </div>
                <div class="document-dates">
                    <div class="document-date">
                        <div class="document-date-label">Vencimiento</div>
                        <div class="document-date-value ${doc.dias_restantes_clase}">
                            ${doc.fecha_vencimiento_formateada}
                        </div>
                    </div>
                    <div class="document-date">
                        <div class="document-date-label">Subida</div>
                        <div class="document-date-value">
                            ${doc.fecha_subida_formateada}
                        </div>
                    </div>
                </div>
                <div class="document-status">
                    <span class="status-badge ${doc.estado}">
                        <i class="fas fa-${getStatusIcon(doc.estado)}"></i>
                        ${doc.estado_texto}
                    </span>
                </div>
                <div class="document-actions">
                    <button class="btn btn-outline-primary" onclick="viewDocument(${doc.id})" title="Ver">
                        <i class="fas fa-eye"></i>
                    </button>
                    <a href="download.php?id=${doc.id}" class="btn btn-outline-success" title="Descargar">
                        <i class="fas fa-download"></i>
                    </a>
                    <button class="btn btn-outline-danger" onclick="deleteDocument(${doc.id})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
    
    // Agregar animación de entrada
    const cards = grid.querySelectorAll('.document-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 50);
        }, index * 100);
    });
}

/**
 * Obtener icono según estado
 */
function getStatusIcon(estado) {
    const icons = {
        'vigente': 'check-circle',
        'por_vencer': 'exclamation-triangle',
        'vencido': 'times-circle'
    };
    return icons[estado] || 'question-circle';
}

/**
 * Actualizar función loadDocuments para usar cards
 */
function loadDocuments() {
    console.log('🚀 Iniciando carga de documentos...');
    showLoading();
    
    fetch('lista.php')
        .then(response => {
            console.log('📡 Respuesta cruda:', response);
            return response.json();
        })
        .then(data => {
            console.log('📊 Datos procesados:', data);
            if (data.success) {
                console.log('✅ Documentos cargados:', data.documentos.length);
                console.log('📈 Estadísticas:', data.estadisticas);
                renderDocumentCards(data.documentos);
                updateStats(data.estadisticas);
            } else {
                console.error('❌ Error en respuesta:', data.message);
                showNotification('Error al cargar documentos: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('🔥 Error de red:', error);
            showNotification('Error de conexión al cargar documentos', 'error');
        })
        .finally(() => {
            console.log('🏁 Carga finalizada');
            hideLoading();
        });
}

/**
 * Actualizar función filterDocuments para cards
 */
function filterDocuments() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const cards = document.querySelectorAll('.document-card');
    
    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        const statusElement = card.querySelector('.status-badge');
        const status = statusElement ? statusElement.textContent.toLowerCase() : '';
        
        const matchesSearch = text.includes(searchTerm);
        const matchesStatus = !statusFilter || status.includes(statusFilter.toLowerCase().replace('_', ' '));
        
        if (matchesSearch && matchesStatus) {
            card.style.display = '';
            card.style.animation = 'fadeIn 0.5s ease';
        } else {
            card.style.display = 'none';
        }
    });
}

// ===== FUNCIONES DE UTILIDAD =====

/**
 * Confirmar acción
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Copiar al portapapeles
 */
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('Copiado al portapapeles', 'success');
        }).catch(() => {
            fallbackCopyToClipboard(text);
        });
    } else {
        fallbackCopyToClipboard(text);
    }
}

/**
 * Método alternativo para copiar al portapapeles
 */
function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showNotification('Copiado al portapapeles', 'success');
    } catch (err) {
        showNotification('No se pudo copiar al portapapeles', 'error');
    }
    
    document.body.removeChild(textArea);
}

/**
 * Formatear fecha
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

/**
 * Formatear fecha y hora
 */
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Calcular días restantes
 */
function calculateDaysRemaining(fechaVencimiento) {
    const vencimiento = new Date(fechaVencimiento);
    const hoy = new Date();
    const diferencia = vencimiento - hoy;
    const dias = Math.ceil(diferencia / (1000 * 60 * 60 * 24));
    
    if (dias < 0) {
        return { text: `Vencido hace ${Math.abs(dias)} días`, class: 'text-danger' };
    } else if (dias === 0) {
        return { text: 'Vence hoy', class: 'text-warning' };
    } else if (dias === 1) {
        return { text: 'Vence mañana', class: 'text-warning' };
    } else if (dias <= 30) {
        return { text: `${dias} días restantes`, class: 'text-warning' };
    } else {
        return { text: `${dias} días restantes`, class: 'text-success' };
    }
}

/**
 * Debounce function para optimizar búsquedas
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle function para optimizar eventos scroll
 */
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ===== INICIALIZACIÓN =====

/**
 * Inicializar todas las funcionalidades cuando el DOM esté listo
 */
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar drag and drop
    initializeDragDrop();
    
    // Inicializar formulario de upload
    initializeUploadForm();
    
    // Auto-ocultar alertas después de 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentElement) {
                alert.remove();
            }
        }, 5000);
    });
    
    // Inicializar tooltips si se usa alguna librería
    initializeTooltips();
    
    // Configurar atajos de teclado
    initializeKeyboardShortcuts();
});

/**
 * Inicializar tooltips (opcional)
 */
function initializeTooltips() {
    // Aquí se podría inicializar una librería de tooltips como Tippy.js
    // Por ahora, usamos el atributo title nativo
}

/**
 * Inicializar atajos de teclado
 */
function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + N: Nuevo documento
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            openModal('uploadModal');
        }
        
        // Ctrl/Cmd + R: Refrescar (evitar recarga de página)
        if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
            e.preventDefault();
            if (typeof refreshDocuments === 'function') {
                refreshDocuments();
            }
        }
        
        // Escape: Cerrar modales
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
}

// ===== EXPORTACIONES =====

// Exportar funciones para uso global
window.showNotification = showNotification;
window.openModal = openModal;
window.closeModal = closeModal;
window.toggleDropdown = toggleDropdown;
window.copyToClipboard = copyToClipboard;
window.formatDate = formatDate;
window.formatDateTime = formatDateTime;
window.calculateDaysRemaining = calculateDaysRemaining;
window.debounce = debounce;
window.throttle = throttle;
