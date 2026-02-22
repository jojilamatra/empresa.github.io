</div>
    </main>

    <!-- Footer Principal -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <p>&copy; 2026 Todos los derechos reservados</p>
            </div>
        </div>
    </footer>

    <!-- Modal para carga de documentos -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-upload"></i> Cargar Nuevo Documento</h3>
                <button class="modal-close" onclick="closeModal('uploadModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="documentFiles">Archivos <span class="required">*</span></label>
                        <div class="file-upload-area" id="fileUploadArea">
                            <input type="file" id="documentFiles" name="documentFiles[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                            <div class="file-upload-content">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Arrastra archivos aquí o haz clic para seleccionar</p>
                                <small>Formatos permitidos: PDF, Word, Excel, JPG, PNG (Máx. 10MB por archivo)</small>
                            </div>
                        </div>
                        <div id="fileList" class="file-list"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="3" placeholder="Ingresa una descripción para el documento..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="fechaVencimiento">Fecha de Vencimiento <span class="required">*</span></label>
                        <input type="date" id="fechaVencimiento" name="fechaVencimiento" required>
                        <small class="form-text">La fecha debe ser futura</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('uploadModal')">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="uploadBtn">
                            <i class="fas fa-upload"></i> Cargar Documento(s)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Sincronización de Portal -->
    <div id="syncModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-sync"></i> Sincronización con Portal Externo</h3>
                <button class="modal-close" onclick="closeModal('syncModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="sync-config">
                    <div class="form-group">
                        <label for="portalUrl">URL del Portal Externo</label>
                        <input type="url" id="portalUrl" class="form-control" 
                               value="https://portal-externo.empresa.com/api/documentos" 
                               placeholder="https://portal-externo.empresa.com/api/documentos">
                        <small class="form-text">URL del API del portal empresarial externo</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="apiKey">API Key</label>
                        <input type="text" id="apiKey" class="form-control" 
                               value="demo_api_key_12345" 
                               placeholder="Ingrese su API key">
                        <small class="form-text">Clave de API para autenticación con el portal externo</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="syncType">Tipo de Sincronización</label>
                        <select id="syncType" class="form-control">
                            <option value="all">Todos los documentos</option>
                            <option value="new">Solo documentos nuevos</option>
                            <option value="updated">Solo documentos actualizados</option>
                        </select>
                        <small class="form-text">Seleccione qué documentos desea sincronizar</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="testPortalConnection()">
                            <i class="fas fa-plug"></i> Probar Conexión
                        </button>
                        <button type="button" class="btn btn-primary" onclick="performSync()">
                            <i class="fas fa-sync"></i> Sincronizar Documentos
                        </button>
                    </div>
                </div>
                
                <div id="syncResults" class="sync-results" style="display: none;">
                    <h4>Resultados de la Sincronización</h4>
                    <div id="syncResultsContent"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div id="confirmModal" class="modal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirmar Acción</h3>
                <button class="modal-close" onclick="closeModal('confirmModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">¿Estás seguro de realizar esta acción?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('confirmModal')">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmBtn">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- Modal de visualización de documento -->
    <div id="viewModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i> <span id="viewModalTitle">Visualizar Documento</span></h3>
                <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="viewContent">
                    <!-- El contenido se cargará dinámicamente -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewModal')">Cerrar</button>
                <a href="#" id="downloadBtn" class="btn btn-primary" download>
                    <i class="fas fa-download"></i> Descargar
                </a>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Cargando...</p>
        </div>
    </div>

    <!-- JavaScript al final del body -->
    <script>
        // Cargar estadísticas en el footer si está autenticado
        <?php if (estaAutenticado()): ?>
        document.addEventListener('DOMContentLoaded', function() {
            loadFooterStats();
            loadQuickStats();
        });
        <?php endif; ?>
    </script>
</body>
</html>
