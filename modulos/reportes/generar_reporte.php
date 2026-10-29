<?php
// generar_reporte.php
$fechaInicio = '';
$fechaCierre = '';
$tipo = ''; // Valor por defecto
$formato = 'pdf';    // Valor por defecto
$ordenar_por = '';   // Nuevo parámetro para ordenar

// --- Procesar peticiones GET (Botones predefinidos) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['periodo'])) {
    $periodo = $_GET['periodo'];
    $tipo = $_GET['tipo'] ?? 'pagado';
    $formato = $_GET['formato'] ?? 'pdf';
    // No se aplica ordenar_por directamente en GET predefinidos, a menos que se añada al formulario de botones.

    switch ($periodo) {
        case 'diario':
            $fechaInicio = date('Y-m-d');
            $fechaCierre = date('Y-m-d');
            break;
        case 'semanal':
            $fechaInicio = date('Y-m-d', strtotime('monday this week'));
            $fechaCierre = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'mensual':
            $fechaInicio = date('Y-m-01');
            $fechaCierre = date('Y-m-t');
            break;
        case 'anual':
            $fechaInicio = date('Y-01-01');
            $fechaCierre = date('Y-12-31');
            break;
    }
}
// --- Procesar peticiones POST (Formulario personalizado) ---
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fechaInicio = $_POST['fechaInicio'];
    $fechaCierre = $_POST['fechaCierre'];
    $tipo = $_POST['tipoReporte'];
    $formato = $_POST['formato'];
    $ordenar_por = $_POST['ordenarPor'] ?? ''; // Asume un campo 'ordenarPor' en el formulario POST
}

// --- Validar que tengamos fechas antes de redirigir ---
if (empty($fechaInicio) || empty($fechaCierre)) {
    // Si el tipo de reporte no requiere rango de fechas (ej. 'derechos_pendientes_sin_pagar'),
    // no se debería validar la fecha de inicio/cierre aquí.
    // Ajustar esta validación según la lógica de cada reporte.
    if (!in_array($tipo, ['derechos_pendientes', 'derechos_pendientes_pagados', 'derechos_pendientes_sin_pagar'])) {
        die("Error: Período o rango de fechas no válido.");
    }
}

// --- Construir el nombre del script de destino y redirigir ---
$targetScript = '';
$queryParams = http_build_query([
    'fechaInicio' => $fechaInicio,
    'fechaCierre' => $fechaCierre,
    'ordenarPor' => $ordenar_por // Pasa el parámetro de ordenación
]);

// Determine target script based on report type and format
switch ($tipo) {
    case 'pagado':
        if ($formato === 'pdf') {
            $targetScript = 'reporte_planillas_pagadas_pdf.php'; 
        } elseif ($formato === 'excel') {
            $targetScript = 'reporte_planillas_pagadas_excel.php'; 
        }
        break;
    case 'pagado_por_aduana':
        if ($formato === 'pdf') {
            $targetScript = 'reporte_planillas_pagadas_pdf_aduanas.php'; 
        } elseif ($formato === 'excel') {
            $targetScript = 'reporte_planillas_pagadas_excel.php'; 
        }
        break;    
    case 'planillas_sin_pagar':
        if ($formato === 'pdf') {
            $targetScript = 'reporte_planillas_sin_pagar_pdf.php'; 
        } elseif ($formato === 'excel') {
            $targetScript = 'reporte_planillas_sin_pagar_excel.php'; 
        }
        break;
    case 'reportes_liquidados_contabilidad':
        if ($formato === 'pdf') {
            $targetScript = 'reporte_liquidado_contabilidad_pdf.php'; 
        } elseif ($formato === 'excel') {
            $targetScript = 'reporte_liquidado_contabilidad_excel.php'; 
        }
        break;
    case 'derechos_pendientes':
        if ($formato === 'pdf') {
            $targetScript = 'reporte_derechos_pendientes_pdf.php';
        } elseif ($formato === 'excel') {
            $targetScript = 'reporte_derechos_pendientes_excel.php'; 
        }
        break;
    case 'derechos_pendientes_pagados':
        if ($formato === 'pdf') {
            $targetScript = 'reporte_derechos_pendientes_pagados_pdf.php'; 
        } elseif ($formato === 'excel') {
            $targetScript = 'reporte_derechos_pendientes_pagados_excel.php'; 
        }
        break;
    case 'derechos_pendientes_sin_pagar':
        if ($formato === 'pdf') {
            $targetScript = 'reporte_derechos_pendientes_sin_pagar_pdf.php'; 
        } elseif ($formato === 'excel') {
            $targetScript = 'reporte_derechos_pendientes_sin_pagar_excel.php'; 
        }
        break;
    case 'liquidado':
        if ($formato === 'pdf') {
            $targetScript = 'reporte_liquidado_pdf.php'; 
        } elseif ($formato === 'excel') {
            $targetScript = 'reporte_liquidado_excel.php';
        }
        break;
    case 'auxicontable_liquidadas':
        if ($formato === 'pdf') {
            $targetScript = 'reporte_auxicontable_liquidadas_pdf.php'; 
        } elseif ($formato === 'excel') {
            $targetScript = 'reporte_auxicontable_liquidadas_excel.php';
        }
        break;
    case 'auxicontable_autoliquidadas':
        if ($formato === 'pdf') {
            $targetScript = 'reporte_auxicontable_autoliquidadas_pdf.php'; 
        } elseif ($formato === 'excel') {
            $targetScript = 'reporte_auxicontable_autoliquidadas_excel.php';
        }
        break;
    case 'reporte_cobranzas':
        if ($formato === 'excel') {
                $targetScript = 'reporte_cobranzas_excel.php';
        } else {
    
        echo "Formato no soportado para este reporte.";
        exit;
        }
        break;
}

if (!empty($targetScript)) {
    header("Location: {$targetScript}?{$queryParams}");
    exit;
} else {
    die("Error: Tipo de reporte o formato no válido.");
}
?>