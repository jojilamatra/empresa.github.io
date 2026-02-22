<?php
/**
 * Dashboard Principal del Sistema de Gestión de Documentos
 */

require_once 'config.php';

// Verificar autenticación
requerirAutenticacion();

// Configuración de la página
$page_title = 'Dashboard';
$page_description = 'Panel principal de gestión de documentos';
$show_breadcrumb = false;
$requiere_autenticacion = true;

$page_header = [
    'title' => 'Panel de Control',
    'subtitle' => 'Gestiona y monitorea todos tus documentos',
    'actions' => [
        [
            'text' => 'Cargar Nuevo Documento',
            'icon' => 'upload',
            'type' => 'primary',
            'modal' => 'openModal("uploadModal")'
        ],
        [
            'text' => 'Exportar Reporte PDF',
            'icon' => 'file-pdf',
            'type' => 'secondary',
            'link' => 'export_pdf.php'
        ]
    ]
];

// Obtener estadísticas
try {
    $db = Database::getInstance();
    
    // Estadísticas generales
    $sql_stats = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'vigente' THEN 1 ELSE 0 END) as vigentes,
                    SUM(CASE WHEN estado = 'por_vencer' THEN 1 ELSE 0 END) as por_vencer,
                    SUM(CASE WHEN estado = 'vencido' THEN 1 ELSE 0 END) as vencidos
                  FROM documentos 
                  WHERE usuario_id = ?";
    $stmt_stats = $db->query($sql_stats, [$_SESSION['usuario_id']]);
    $stats = $stmt_stats->fetch();
    
    // Documentos recientes
    $sql_recientes = "SELECT id, nombre_original, fecha_vencimiento, estado, fecha_subida, extension
                      FROM documentos 
                      WHERE usuario_id = ? 
                      ORDER BY fecha_subida DESC 
                      LIMIT 5";
    $stmt_recientes = $db->query($sql_recientes, [$_SESSION['usuario_id']]);
    $documentos_recientes = $stmt_recientes->fetchAll();
    
    // Documentos por vencer pronto
    $sql_proximos = "SELECT id, nombre_original, fecha_vencimiento, DATEDIFF(fecha_vencimiento, CURDATE()) as dias_restantes
                     FROM documentos 
                     WHERE usuario_id = ? 
                     AND estado = 'por_vencer' 
                     ORDER BY fecha_vencimiento ASC 
                     LIMIT 5";
    $stmt_proximos = $db->query($sql_proximos, [$_SESSION['usuario_id']]);
    $documentos_proximos = $stmt_proximos->fetchAll();
    
} catch (Exception $e) {
    error_log("Error al obtener estadísticas: " . $e->getMessage());
    $stats = ['total' => 0, 'vigentes' => 0, 'por_vencer' => 0, 'vencidos' => 0];
    $documentos_recientes = [];
    $documentos_proximos = [];
}

include 'header.php';
?>

<!-- Tarjetas de Estadísticas con Animaciones -->
<div class="stats-grid">
    <div class="stat-card total">
        <div class="stat-icon">
            <i class="fas fa-file-alt"></i>
        </div>
        <div class="stat-value"><?php echo number_format($stats['total'] ?? 0); ?></div>
        <div class="stat-label">Total de Documentos</div>
    </div>
    
    <div class="stat-card vigente">
        <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-value"><?php echo number_format($stats['vigentes'] ?? 0); ?></div>
        <div class="stat-label">Documentos Vigentes</div>
    </div>
    
    <div class="stat-card por-vencer">
        <div class="stat-icon">
            <i class="fas fa-exclamation-triangle pulse-warning"></i>
        </div>
        <div class="stat-value"><?php echo number_format($stats['por_vencer'] ?? 0); ?></div>
        <div class="stat-label">Por Vencer</div>
    </div>
    
    <div class="stat-card vencido">
        <div class="stat-icon">
            <i class="fas fa-times-circle pulse-danger"></i>
        </div>
        <div class="stat-value"><?php echo number_format($stats['vencidos'] ?? 0); ?></div>
        <div class="stat-label">Vencidos</div>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Grid de Documentos Cards -->
    <div class="dashboard-section">
        <div class="cards-container">
            <div class="table-header">
                <h3 class="table-title">
                    <i class="fas fa-folder-open"></i>
                    Mis Documentos
                </h3>
                <div class="table-controls">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Buscar documentos..." onkeyup="filterDocuments()">
                        <i class="fas fa-search"></i>
                    </div>
                    <select class="filter-select" id="statusFilter" onchange="filterDocuments()">
                        <option value="">Todos los estados</option>
                        <option value="vigente">Vigentes</option>
                        <option value="por_vencer">Por Vencer</option>
                        <option value="vencido">Vencidos</option>
                    </select>
                    <div class="export-dropdown">
                        <button class="btn btn-secondary dropdown-toggle" onclick="toggleExportMenu()">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                        <div class="dropdown-menu" id="exportMenu">
                            <a href="export.php?format=pdf" class="dropdown-item">
                                <i class="fas fa-file-pdf"></i> Exportar PDF
                            </a>
                            <a href="export.php?format=excel" class="dropdown-item">
                                <i class="fas fa-file-excel"></i> Exportar Excel
                            </a>
                            <a href="export.php?format=word" class="dropdown-item">
                                <i class="fas fa-file-word"></i> Exportar Word
                            </a>
                        </div>
                    </div>
                    <button class="btn btn-outline-primary" onclick="openSyncModal()" title="Sincronizar Portal">
                        <i class="fas fa-sync"></i> Sincronizar
                    </button>
                </div>
                </div>
            </div>
            
            <div class="documents-grid" id="documentsGrid">
                <!-- Los documentos se cargarán vía AJAX como cards -->
                <div class="loading-cards">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p>Cargando documentos...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Panel Lateral -->
    <div class="dashboard-sidebar">
        <!-- Documentos Recientes -->
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-clock"></i> Documentos Recientes</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($documentos_recientes)): ?>
                    <div class="recent-documents">
                        <?php foreach ($documentos_recientes as $doc): ?>
                            <div class="recent-doc-item">
                                <div class="recent-doc-icon">
                                    <?php echo getFileIcon($doc['extension']); ?>
                                </div>
                                <div class="recent-doc-info">
                                    <div class="recent-doc-name"><?php echo htmlspecialchars($doc['nombre_original']); ?></div>
                                    <div class="recent-doc-date">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('d/m/Y', strtotime($doc['fecha_subida'])); ?>
                                    </div>
                                </div>
                                <div class="recent-doc-status">
                                    <span class="badge badge-<?php 
                                        echo $doc['estado'] == 'vigente' ? 'success' : 
                                             ($doc['estado'] == 'por_vencer' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $doc['estado'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No hay documentos recientes</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Alertas de Vencimiento -->
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-bell"></i> Alertas de Vencimiento</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($documentos_proximos)): ?>
                    <div class="alert-documents">
                        <?php foreach ($documentos_proximos as $doc): ?>
                            <div class="alert-doc-item">
                                <div class="alert-doc-icon">
                                    <i class="fas fa-exclamation-triangle" style="color: var(--warning-color);"></i>
                                </div>
                                <div class="alert-doc-info">
                                    <div class="alert-doc-name"><?php echo htmlspecialchars($doc['nombre_original']); ?></div>
                                    <div class="alert-doc-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        Vence en <?php echo $doc['dias_restantes']; ?> días
                                        <small>(<?php echo date('d/m/Y', strtotime($doc['fecha_vencimiento'])); ?>)</small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                        <p>No hay documentos próximos a vencer</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Acciones Rápidas -->
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-bolt"></i> Acciones Rápidas</h4>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <button class="btn btn-primary btn-block" onclick="openModal('uploadModal')">
                        <i class="fas fa-upload"></i> Subir Documento
                    </button>
                    <a href="export_pdf.php" class="btn btn-secondary btn-block">
                        <i class="fas fa-file-pdf"></i> Exportar PDF
                    </a>
                    <button class="btn btn-outline-primary btn-block" onclick="refreshDocuments()">
                        <i class="fas fa-sync-alt"></i> Actualizar Lista
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos específicos del dashboard */
.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
    margin-top: 2rem;
}

.dashboard-section {
    min-width: 0; /* Para que la tabla no se desborde */
}

.dashboard-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.recent-documents,
.alert-documents {
    max-height: 300px;
    overflow-y: auto;
}

.recent-doc-item,
.alert-doc-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: var(--border-radius);
    margin-bottom: 0.5rem;
    transition: var(--transition);
}

.recent-doc-item:hover,
.alert-doc-item:hover {
    background-color: var(--gray-100);
}

.recent-doc-icon,
.alert-doc-icon {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.recent-doc-info,
.alert-doc-info {
    flex: 1;
    min-width: 0;
}

.recent-doc-name,
.alert-doc-name {
    font-weight: 500;
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.recent-doc-date,
.alert-doc-date {
    font-size: 0.8rem;
    color: var(--gray-600);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.recent-doc-status {
    flex-shrink: 0;
}

.empty-state {
    text-align: center;
    padding: 2rem 1rem;
    color: var(--gray-500);
}

.empty-state i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Responsive */
@media (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-sidebar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .dashboard-sidebar {
        grid-template-columns: 1fr;
    }
    
    .table-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .table-controls {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .search-box input {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Cargar documentos al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    loadDocuments();
    
    // Auto-refresh cada 5 minutos
    setInterval(loadDocuments, 300000);
});

// Función para cargar documentos vía AJAX
function loadDocuments() {
    showLoading();
    
    fetch('lista.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderDocumentsTable(data.documentos);
                updateStats(data.estadisticas);
            } else {
                showNotification('Error al cargar documentos: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error de conexión al cargar documentos', 'error');
        })
        .finally(() => {
            hideLoading();
        });
}

// Función para renderizar la tabla de documentos (actualizada para cards)
function renderDocumentsTable(documentos) {
    renderDocumentCards(documentos);
}

// Función para actualizar estadísticas
function updateStats(estadisticas) {
    if (estadisticas) {
        // Actualizar tarjetas principales
        document.querySelector('.stat-card.total .stat-value').textContent = estadisticas.total || 0;
        document.querySelector('.stat-card.vigente .stat-value').textContent = estadisticas.vigentes || 0;
        document.querySelector('.stat-card.por-vencer .stat-value').textContent = estadisticas.por_vencer || 0;
        document.querySelector('.stat-card.vencido .stat-value').textContent = estadisticas.vencidos || 0;
    }
}

// Función para filtrar documentos
function filterDocuments() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#documentsTableBody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const statusElement = row.querySelector('.badge');
        const status = statusElement ? statusElement.textContent.toLowerCase() : '';
        
        const matchesSearch = text.includes(searchTerm);
        const matchesStatus = !statusFilter || status.includes(statusFilter.toLowerCase().replace('_', ' '));
        
        row.style.display = matchesSearch && matchesStatus ? '' : 'none';
    });
}

// Función para ver documento
function viewDocument(id) {
    showLoading();
    
    fetch(`lista.php?action=view&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const doc = data.documento;
                document.getElementById('viewModalTitle').textContent = doc.nombre_original;
                document.getElementById('viewContent').innerHTML = `
                    <div class="document-view">
                        <div class="document-info">
                            <p><strong>Nombre:</strong> ${doc.nombre_original}</p>
                            <p><strong>Descripción:</strong> ${doc.descripcion || 'Sin descripción'}</p>
                            <p><strong>Fecha de Vencimiento:</strong> ${doc.fecha_vencimiento_formateada}</p>
                            <p><strong>Estado:</strong> <span class="badge badge-${doc.estado_clase}">${doc.estado_texto}</span></p>
                            <p><strong>Tamaño:</strong> ${doc.tamano_formateado}</p>
                            <p><strong>Fecha de Subida:</strong> ${doc.fecha_subida_formateada}</p>
                        </div>
                        ${doc.es_imagen ? `<img src="download.php?id=${doc.id}&preview=1" style="max-width: 100%; height: auto; border-radius: var(--border-radius);">` : ''}
                    </div>
                `;
                document.getElementById('downloadBtn').href = `download.php?id=${doc.id}`;
                openModal('viewModal');
            } else {
                showNotification('Error al cargar documento: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error al cargar documento', 'error');
        })
        .finally(() => {
            hideLoading();
        });
}

// Función para eliminar documento
function deleteDocument(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este documento? Esta acción no se puede deshacer.')) {
        showLoading();
        
        fetch('delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Documento eliminado correctamente', 'success');
                loadDocuments(); // Recargar la tabla
            } else {
                showNotification('Error al eliminar documento: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error al eliminar documento', 'error');
        })
        .finally(() => {
            hideLoading();
        });
    }
}

// Función para refrescar documentos
function refreshDocuments() {
    loadDocuments();
    showNotification('Lista actualizada', 'info');
}

// Función para cargar estadísticas del footer
function loadFooterStats() {
    // No hacer nada ya que el footer está simplificado
}

// Función para cargar estadísticas rápidas
function loadQuickStats() {
    // No hacer nada ya que la barra de estado está simplificada
}
</script>

<?php include 'footer.php'; ?>
