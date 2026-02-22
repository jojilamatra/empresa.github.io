<?php
/**
 * Exportador de reporte PDF de documentos
 * Genera un PDF con la lista de documentos y sus estados
 */

require_once 'config.php';

// Verificar autenticación
requerirAutenticacion();

// Intentar cargar TCPDF
$tcpdf_path = __DIR__ . '/vendor/tcpdf/tcpdf.php';

if (!file_exists($tcpdf_path)) {
    // Si TCPDF no está instalado, mostrar mensaje de instalación
    $page_title = 'Exportar PDF';
    $show_breadcrumb = true;
    $breadcrumb = [
        ['text' => 'Dashboard', 'link' => 'index.php'],
        ['text' => 'Exportar PDF']
    ];
    
    include 'header.php';
    ?>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Biblioteca TCPDF Requerida</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <h4><i class="fas fa-info-circle"></i> Instalación Requerida</h4>
                <p>Para generar reportes PDF, necesitas instalar la biblioteca TCPDF. Puedes instalarla usando Composer:</p>
                <pre><code>composer require tecnickcom/tcpdf</code></pre>
                <p>O descargarla manualmente desde: <a href="https://github.com/tecnickcom/TCPDF" target="_blank">GitHub - TCPDF</a></p>
                <p>Una vez instalada, colócala en la carpeta <code>vendor/tcpdf/</code> o ajusta la ruta en este archivo.</p>
            </div>
            
            <div class="alert alert-info">
                <h4><i class="fas fa-lightbulb"></i> Alternativa</h4>
                <p>Mientras tanto, puedes exportar los datos a CSV usando el siguiente enlace:</p>
                <a href="export_csv.php" class="btn btn-primary">
                    <i class="fas fa-file-csv"></i> Exportar a CSV
                </a>
            </div>
        </div>
    </div>
    
    <?php
    include 'footer.php';
    exit;
}

// Incluir TCPDF
require_once $tcpdf_path;

try {
    $db = Database::getInstance();
    
    // Obtener todos los documentos del usuario
    $sql = "SELECT 
                nombre_original, 
                fecha_vencimiento, 
                descripcion, 
                estado, 
                fecha_subida, 
                tamano_archivo, 
                extension
            FROM documentos 
            WHERE usuario_id = ? 
            ORDER BY fecha_subida DESC";
    
    $stmt = $db->query($sql, [$_SESSION['usuario_id']]);
    $documentos = $stmt->fetchAll();
    
    // Obtener estadísticas
    $sql_stats = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'vigente' THEN 1 ELSE 0 END) as vigentes,
                    SUM(CASE WHEN estado = 'por_vencer' THEN 1 ELSE 0 END) as por_vencer,
                    SUM(CASE WHEN estado = 'vencido' THEN 1 ELSE 0 END) as vencidos
                  FROM documentos 
                  WHERE usuario_id = ?";
    
    $stmt_stats = $db->query($sql_stats, [$_SESSION['usuario_id']]);
    $stats = $stmt_stats->fetch();
    
    // Crear instancia de TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Configuración del documento
    $pdf->SetCreator(APP_NAME . ' v' . APP_VERSION);
    $pdf->SetAuthor($_SESSION['nombre_completo']);
    $pdf->SetTitle('Reporte de Documentos');
    $pdf->SetSubject('Reporte de Gestión de Documentos');
    $pdf->SetKeywords('documentos, reporte, PDF');
    
    // Configurar márgenes
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Configurar saltos de página automáticos
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Configurar fuente
    $pdf->SetFont('helvetica', '', 10);
    
    // Añadir página
    $pdf->AddPage();
    
    // Título del reporte
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 10, 'Reporte de Documentos', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 8, 'Generado por: ' . $_SESSION['nombre_completo'], 0, 1, 'C');
    $pdf->Cell(0, 8, 'Fecha: ' . date('d/m/Y H:i'), 0, 1, 'C');
    $pdf->Ln(10);
    
    // Estadísticas
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Resumen de Documentos', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    
    $pdf->Cell(60, 8, 'Total de Documentos:', 0, 0, 'L');
    $pdf->Cell(40, 8, $stats['total'], 0, 1, 'R');
    
    $pdf->Cell(60, 8, 'Documentos Vigentes:', 0, 0, 'L');
    $pdf->Cell(40, 8, $stats['vigentes'], 0, 1, 'R');
    
    $pdf->Cell(60, 8, 'Por Vencer:', 0, 0, 'L');
    $pdf->Cell(40, 8, $stats['por_vencer'], 0, 1, 'R');
    
    $pdf->Cell(60, 8, 'Vencidos:', 0, 0, 'L');
    $pdf->Cell(40, 8, $stats['vencidos'], 0, 1, 'R');
    
    $pdf->Ln(15);
    
    // Tabla de documentos
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Lista Detallada de Documentos', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Cabecera de la tabla
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(0, 123, 255);
    $pdf->SetTextColor(255);
    $pdf->Cell(70, 7, 'Nombre del Documento', 1, 0, 'L', 1);
    $pdf->Cell(30, 7, 'Vencimiento', 1, 0, 'C', 1);
    $pdf->Cell(25, 7, 'Estado', 1, 0, 'C', 1);
    $pdf->Cell(30, 7, 'Tamaño', 1, 0, 'C', 1);
    $pdf->Cell(35, 7, 'Fecha Subida', 1, 1, 'C', 1);
    
    // Restaurar colores
    $pdf->SetFillColor(255);
    $pdf->SetTextColor(0);
    $pdf->SetFont('helvetica', '', 9);
    
    // Datos de la tabla
    foreach ($documentos as $doc) {
        // Nombre del documento (truncado si es muy largo)
        $nombre = strlen($doc['nombre_original']) > 25 ? 
                  substr($doc['nombre_original'], 0, 22) . '...' : 
                  $doc['nombre_original'];
        
        // Fecha de vencimiento
        $fecha_venc = date('d/m/Y', strtotime($doc['fecha_vencimiento']));
        
        // Estado con color
        $estado = ucfirst(str_replace('_', ' ', $doc['estado']));
        switch ($doc['estado']) {
            case 'vigente':
                $pdf->SetTextColor(40, 167, 69);
                break;
            case 'por_vencer':
                $pdf->SetTextColor(255, 193, 7);
                break;
            case 'vencido':
                $pdf->SetTextColor(220, 53, 69);
                break;
        }
        
        // Tamaño formateado
        $tamano = formatFileSize($doc['tamano_archivo']);
        
        // Fecha de subida
        $fecha_subida = date('d/m/Y', strtotime($doc['fecha_subida']));
        
        // Imprimir fila
        $pdf->Cell(70, 7, $nombre, 1, 0, 'L');
        $pdf->Cell(30, 7, $fecha_venc, 1, 0, 'C');
        $pdf->Cell(25, 7, $estado, 1, 0, 'C');
        $pdf->SetTextColor(0); // Restaurar color para las siguientes celdas
        $pdf->Cell(30, 7, $tamano, 1, 0, 'R');
        $pdf->Cell(35, 7, $fecha_subida, 1, 1, 'C');
    }
    
    // Pie del reporte
    $pdf->Ln(15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Reporte generado automáticamente por ' . APP_NAME . ' v' . APP_VERSION, 0, 1, 'C');
    $pdf->Cell(0, 5, 'Para más información, contacte al administrador del sistema', 0, 1, 'C');
    
    // Registrar actividad
    registrarActividad($_SESSION['usuario_id'], 'export_pdf', 'Exportó reporte PDF de documentos');
    
    // Salida del PDF
    $pdf->Output('reporte_documentos_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
    exit;
    
} catch (Exception $e) {
    error_log("Error en export_pdf.php: " . $e->getMessage());
    
    // Redirigir con mensaje de error
    $_SESSION['flash_message'] = 'Error al generar el PDF: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

// Función de respaldo si TCPDF no está disponible
function generateSimplePDF($documentos, $stats) {
    // Cabecera para forzar descarga
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="reporte_documentos_' . date('Y-m-d_H-i-s') . '.pdf"');
    
    // Crear PDF simple (solo texto básico)
    echo '%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj

2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj

3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Contents 4 0 R
/Resources <<
/Font <<
/F1 5 0 R
>>
>>
>>
endobj

4 0 obj
<<
/Length 100
>>
stream
BT
/F1 12 Tf
72 720 Td
(Reporte de Documentos) Tj
ET
endstream
endobj

5 0 obj
<<
/Type /Font
/Subtype /Type1
/BaseFont /Helvetica
>>
endobj

xref
0 6
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000256 00000 n 
0000000361 00000 n 
trailer
<<
/Size 6
/Root 1 0 R
>>
startxref
456
%%EOF';
    
    exit;
}
?>
