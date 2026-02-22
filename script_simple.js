/**
 * JavaScript simplificado para depurar el problema
 */

console.log('🚀 Script simplificado cargado...');

// Función loadDocuments simplificada
function loadDocumentsSimple() {
    console.log('📥 Iniciando carga simple...');
    
    // Mostrar loading
    const loadingDiv = document.getElementById('documentsGrid');
    if (loadingDiv) {
        loadingDiv.innerHTML = '<div class="loading-cards"><div class="loading-spinner"><div class="spinner"></div><p>Cargando documentos...</p></div></div>';
    }
    
    fetch('lista.php')
        .then(response => {
            console.log('📡 Response status:', response.status);
            console.log('📡 Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text();
        })
        .then(text => {
            console.log('📄 Response text:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('📊 JSON parsed:', data);
                
                if (data.success && data.documentos) {
                    console.log('✅ Documentos encontrados:', data.documentos.length);
                    renderDocumentCardsSimple(data.documentos);
                } else {
                    console.error('❌ Error en datos:', data.message);
                    showNotification('Error: ' + (data.message || 'Error desconocido'), 'error');
                }
            } catch (e) {
                console.error('❌ Error parseando JSON:', e);
                console.error('Texto que falló:', text);
                showNotification('Error al procesar respuesta', 'error');
            }
        })
        .catch(error => {
            console.error('🔥 Error de red:', error);
            showNotification('Error de conexión', 'error');
        });
}

// Función renderDocumentCards simplificada
function renderDocumentCardsSimple(documentos) {
    console.log('🎨 Renderizando', documentos.length, 'documentos');
    
    const grid = document.getElementById('documentsGrid');
    if (!grid) {
        console.error('❌ No se encontró el grid #documentsGrid');
        return;
    }
    
    if (documentos.length === 0) {
        grid.innerHTML = '<div class="loading-cards"><div class="empty-state"><i class="fas fa-inbox"></i><p>No hay documentos registrados</p></div></div>';
        return;
    }
    
    let html = '';
    documentos.forEach((doc, index) => {
        console.log(`📄 Procesando documento ${index + 1}:`, doc.nombre_original);
        
        html += `
            <div class="document-card ${doc.estado}" data-id="${doc.id}">
                <div class="document-card-header">
                    <div class="document-icon ${doc.extension}">
                        ${doc.icono || '📄'}
                    </div>
                    <div class="document-info">
                        <div class="document-name" title="${doc.nombre_original}">${doc.nombre_original}</div>
                        <div class="document-meta">${doc.tamano_formateado || '0 KB'} • ${doc.extension.toUpperCase()}</div>
                    </div>
                </div>
                <div class="document-card-body">
                    <div class="document-description">${doc.descripcion || 'Sin descripción'}</div>
                    <div class="document-dates">
                        <div class="document-date">
                            <div class="document-date-label">Vencimiento</div>
                            <div class="document-date-value ${doc.dias_restantes_clase || ''}">${doc.fecha_vencimiento_formateada || 'N/A'}</div>
                        </div>
                        <div class="document-date">
                            <div class="document-date-label">Subida</div>
                            <div class="document-date-value">${doc.fecha_subida_formateada || 'N/A'}</div>
                        </div>
                    </div>
                </div>
                <div class="document-status">
                    <span class="status-badge ${doc.estado_clase || ''}">
                        <i class="fas fa-${getStatusIcon(doc.estado)}"></i>
                        ${doc.estado_texto || 'N/A'}
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
        `;
    });
    
    console.log('🎨 HTML generado, longitud:', html.length);
    grid.innerHTML = html;
    console.log('✅ HTML insertado en el grid');
}

// Función getStatusIcon
function getStatusIcon(estado) {
    const icons = {
        'vigente': 'check-circle',
        'por_vencer': 'exclamation-triangle',
        'vencido': 'times-circle'
    };
    return icons[estado] || 'question-circle';
}

// Reemplazar la función loadDocuments original
window.loadDocuments = loadDocumentsSimple;

console.log('📝 Función loadDocuments reemplazada');
