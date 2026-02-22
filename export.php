<?php
/**
 * Sistema de Exportación Múltiple
 * Soporta PDF, Excel y Word
 */

require_once 'config.php';

// Verificar autenticación
requerirAutenticacion();

// Obtener formato de exportación
$format = $_GET['format'] ?? 'pdf';
$allowed_formats = ['pdf', 'excel', 'word'];

if (!in_array($format, $allowed_formats)) {
    $_SESSION['flash_message'] = 'Formato de exportación no válido';
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

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
    
    // Registrar actividad
    registrarActividad($_SESSION['usuario_id'], 'export', "Exportó reporte en formato {$format}");
    
    // Exportar según formato
    switch ($format) {
        case 'excel':
            exportToExcel($documentos, $stats);
            break;
        case 'word':
            exportToWord($documentos, $stats);
            break;
        case 'pdf':
        default:
            exportToPDF($documentos, $stats);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error en export.php: " . $e->getMessage());
    $_SESSION['flash_message'] = 'Error al exportar: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

/**
 * Exportar a Excel (CSV simple)
 */
function exportToExcel($documentos, $stats) {
    $filename = 'reporte_documentos_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // BOM para caracteres especiales en Excel
    echo "\xEF\xBB\xBF";
    
    // Cabecera del CSV
    $output = fopen('php://output', 'w');
    
    // Estadísticas
    fputcsv($output, ['REPORTE DE DOCUMENTOS']);
    fputcsv($output, ['Generado por: ' . $_SESSION['nombre_completo']]);
    fputcsv($output, ['Fecha: ' . date('d/m/Y H:i')]);
    fputcsv($output, []);
    
    fputcsv($output, ['RESUMEN']);
    fputcsv($output, ['Total de Documentos', $stats['total']]);
    fputcsv($output, ['Documentos Vigentes', $stats['vigentes']]);
    fputcsv($output, ['Por Vencer', $stats['por_vencer']]);
    fputcsv($output, ['Vencidos', $stats['vencidos']]);
    fputcsv($output, []);
    
    // Cabecera de la tabla
    fputcsv($output, ['Nombre del Documento', 'Fecha Vencimiento', 'Estado', 'Descripción', 'Tamaño', 'Fecha Subida']);
    
    // Datos
    foreach ($documentos as $doc) {
        fputcsv($output, [
            $doc['nombre_original'],
            date('d/m/Y', strtotime($doc['fecha_vencimiento'])),
            ucfirst(str_replace('_', ' ', $doc['estado'])),
            $doc['descripcion'] ?: 'Sin descripción',
            formatFileSize($doc['tamano_archivo']),
            date('d/m/Y H:i', strtotime($doc['fecha_subida']))
        ]);
    }
    
    fclose($output);
    exit;
}

/**
 * Exportar a Word (HTML simple)
 */
function exportToWord($documentos, $stats) {
    $filename = 'reporte_documentos_' . date('Y-m-d_H-i-s') . '.doc';
    
    header('Content-Type: application/msword');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Documentos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #007BFF; }
        h2 { color: #0056B3; border-bottom: 2px solid #007BFF; padding-bottom: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #007BFF; color: white; }
        .vigente { color: #28A745; }
        .por_vencer { color: #FFC107; }
        .vencido { color: #DC3545; }
        .stats { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Reporte de Documentos</h1>
    <p><strong>Generado por:</strong> ' . $_SESSION['nombre_completo'] . '</p>
    <p><strong>Fecha:</strong> ' . date('d/m/Y H:i') . '</p>
    
    <div class="stats">
        <h2>Resumen</h2>
        <p><strong>Total de Documentos:</strong> ' . $stats['total'] . '</p>
        <p><strong>Documentos Vigentes:</strong> <span class="vigente">' . $stats['vigentes'] . '</span></p>
        <p><strong>Por Vencer:</strong> <span class="por_vencer">' . $stats['por_vencer'] . '</span></p>
        <p><strong>Vencidos:</strong> <span class="vencido">' . $stats['vencidos'] . '</span></p>
    </div>
    
    <h2>Lista Detallada</h2>
    <table>
        <tr>
            <th>Nombre del Documento</th>
            <th>Fecha Vencimiento</th>
            <th>Estado</th>
            <th>Descripción</th>
            <th>Tamaño</th>
            <th>Fecha Subida</th>
        </tr>';
    
    foreach ($documentos as $doc) {
        $estado_class = $doc['estado'];
        $html .= '
        <tr>
            <td>' . htmlspecialchars($doc['nombre_original']) . '</td>
            <td>' . date('d/m/Y', strtotime($doc['fecha_vencimiento'])) . '</td>
            <td><span class="' . $estado_class . '">' . ucfirst(str_replace('_', ' ', $doc['estado'])) . '</span></td>
            <td>' . htmlspecialchars($doc['descripcion'] ?: 'Sin descripción') . '</td>
            <td>' . formatFileSize($doc['tamano_archivo']) . '</td>
            <td>' . date('d/m/Y H:i', strtotime($doc['fecha_subida'])) . '</td>
        </tr>';
    }
    
    $html .= '
    </table>
    
    <p><em>Reporte generado automáticamente por ' . APP_NAME . ' v' . APP_VERSION . '</em></p>
</body>
</html>';
    
    echo $html;
    exit;
}

/**
 * Exportar a PDF (TCPDF si está disponible, sino HTML simple)
 */
function exportToPDF($documentos, $stats) {
    $tcpdf_path = __DIR__ . '/vendor/tcpdf/tcpdf.php';
    
    if (file_exists($tcpdf_path)) {
        // Usar TCPDF si está disponible
        require_once $tcpdf_path;
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        $pdf->SetCreator(APP_NAME . ' v' . APP_VERSION);
        $pdf->SetAuthor($_SESSION['nombre_completo']);
        $pdf->SetTitle('Reporte de Documentos');
        
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->SetFont('helvetica', '', 10);
        
        $pdf->AddPage();
        
        // Título
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 10, 'Reporte de Documentos', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, 'Generado por: ' . $_SESSION['nombre_completo'], 0, 1, 'C');
        $pdf->Cell(0, 8, 'Fecha: ' . date('d/m/Y H:i'), 0, 1, 'C');
        $pdf->Ln(10);
        
        // Estadísticas
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Resumen', 0, 1, 'L');
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
        
        // Tabla
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Lista Detallada', 0, 1, 'L');
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
        
        // Datos
        $pdf->SetFillColor(255);
        $pdf->SetTextColor(0);
        $pdf->SetFont('helvetica', '', 9);
        
        foreach ($documentos as $doc) {
            $nombre = strlen($doc['nombre_original']) > 25 ? 
                      substr($doc['nombre_original'], 0, 22) . '...' : 
                      $doc['nombre_original'];
            
            $fecha_venc = date('d/m/Y', strtotime($doc['fecha_vencimiento']));
            $estado = ucfirst(str_replace('_', ' ', $doc['estado']));
            $tamano = formatFileSize($doc['tamano_archivo']);
            $fecha_subida = date('d/m/Y', strtotime($doc['fecha_subida']));
            
            // Color según estado
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
            
            $pdf->Cell(70, 7, $nombre, 1, 0, 'L');
            $pdf->Cell(30, 7, $fecha_venc, 1, 0, 'C');
            $pdf->Cell(25, 7, $estado, 1, 0, 'C');
            $pdf->SetTextColor(0); // Restaurar color
            $pdf->Cell(30, 7, $tamano, 1, 0, 'R');
            $pdf->Cell(35, 7, $fecha_subida, 1, 1, 'C');
        }
        
        $pdf->Ln(15);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 5, 'Reporte generado automáticamente por ' . APP_NAME . ' v' . APP_VERSION, 0, 1, 'C');
        
        $pdf->Output('reporte_documentos_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
        exit;
    } else {
        // Si TCPDF no está disponible, redirigir con mensaje
        $_SESSION['flash_message'] = 'TCPDF no está instalado. Por favor, instálalo con: composer require tecnickcom/tcpdf';
        $_SESSION['flash_type'] = 'warning';
        header('Location: index.php');
        exit;
    }
}
?>
