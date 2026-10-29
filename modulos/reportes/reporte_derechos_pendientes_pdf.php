<?php
require('../../fpdf/fpdf.php');
include('../../config.php');

class PDF extends FPDF
{
    // Propiedades para almacenar las fechas del reporte
    var $fechaInicio;
    var $fechaCierre;

    // Cabecera de página actualizada
    function Header()
    {
        // --- PRIMERA LÍNEA: LOGOS Y TÍTULO PRINCIPAL ---
        
        // Logo SENIAT (Izquierda)
        $this->Image('../../logos/seniat.png', 10, 5, 40); // (path, x, y, width)
        
        // Logo 0800 (Derecha)
        $this->Image('../../logos/08000.png', 240, 5, 40); // Ajustar X para orientación horizontal
        
        // Título Principal (Centro)
        $this->SetY(12); // Ajustar posición vertical
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, utf8_decode('Reporte de Derechos Pendientes'), 0, 1, 'C');
        
        // --- PRIMERA LÍNEA GRUESA ---
        $this->SetLineWidth(0.6);
        $this->Line(10, 28, 275, 28); // Ajustar X2 para orientación horizontal

        // --- SEGUNDA LÍNEA: INFORMACIÓN DE LA ADUANA Y FECHA/PÁGINA ---
        
        // Bloque Izquierdo: Información de la Aduana
        $this->SetY(30);
        $this->SetFont('Arial', '', 7);
        $this->MultiCell(100, 3, utf8_decode("Gerencia de Aduana Principal Las Piedras Paraguaná\nDivisión de Recaudación\nÁrea de Contabilidad Fiscal"), 0, 'L');
        
        // Bloque Derecho: Fecha y Paginación (con recuadro)
        $this->SetXY(230, 30); // Posicionar a la derecha
        $this->SetFont('Arial', '', 8);
        $this->Cell(40, 5, utf8_decode('Fecha: ' . date('d/m/Y')), 'TRL', 2, 'L');
        $this->SetX(230);
        $this->Cell(40, 5, utf8_decode('Pág. ' . $this->PageNo() . ' de {nb}'), 'BRL', 1, 'L');

        // --- SEGUNDA LÍNEA GRUESA ---
        $this->SetLineWidth(0.6);
        $this->Line(10, 44, 275, 44);
        
        // --- TERCERA LÍNEA: RANGO DE FECHAS ---
        
        // Formatear las fechas desde las propiedades de la clase
        $fecha_desde = date("d/m/Y", strtotime($this->fechaInicio));
        $fecha_hasta = date("d/m/Y", strtotime($this->fechaCierre));
        
        $this->SetY(46);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 10, utf8_decode("Desde: " . $fecha_desde . "    Hasta: " . $fecha_hasta), 0, 1, 'C');

        // Salto de línea para empezar el contenido de la tabla
        $this->Ln(5);
    }

    // Pie de página
    function Footer()
    {
        // Posición: a 1,5 cm del final
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Número de página
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // --- FUNCIONES PARA LA TABLA MULTICELDA ---
    var $widths;
    var $aligns;

    function SetWidths($w)
    {
        $this->widths = $w;
    }

    function Row($data)
    {
        $nb = 0;
        for($i = 0; $i < count($data); $i++)
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        $h = 5 * $nb;
        $this->CheckPageBreak($h);
        for($i = 0; $i < count($data); $i++)
        {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x, $y, $w, $h);
            $this->MultiCell($w, 5, $data[$i], 0, $a);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    function CheckPageBreak($h)
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
        }
    }

    function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 and $s[$nb - 1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                } else
                    $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else
                $i++;
        }
        return $nl;
    }
}

// --- Lógica Principal del Reporte ---

// 1. Obtener fechas y orden desde la URL
$fechaInicio = $_GET['fechaInicio'] ?? date('Y-m-d');
$fechaCierre = $_GET['fechaCierre'] ?? date('Y-m-d');
$ordenarPor = $_GET['ordenarPor'] ?? '';

$allowedOrderByColumns = ['cod_banco', 'cod_agencia', 'tipo_de_planilla', 'cod_de_forma', 'codigo_aduana'];
// The primary ordering will be by the custom grouping logic, then secondary by the selected column and form number
$orderByClause = ""; 
if (!empty($ordenarPor) && in_array($ordenarPor, $allowedOrderByColumns)) {
    $orderByClause .= ", p." . $ordenarPor;
}
$orderByClause .= ", p.nro_formulario"; // Always order by form number at the end

// 2. Crear instancia de PDF
$pdf = new PDF('L', 'mm', 'Letter'); // Cambiado a orientación 'L' (Landscape)

// Asignar las fechas a las propiedades del objeto PDF antes de crear la página
$pdf->fechaInicio = $fechaInicio;
$pdf->fechaCierre = $fechaCierre;

$pdf->AliasNbPages();
$pdf->AddPage(); // El Header se llamará automáticamente aquí
$pdf->SetFont('Arial', '', 8);

// 3. Definir la consulta SQL
// Incluir nro_declaracion, fecha_vencimiento, cod_status
$sql = "SELECT p.nro_formulario, c.rif_consignatario, c.nombre_consignatario,
               a.rif_agentes, a.nombre_agente,
               p.fecha_cancelacion, p.monto2,
               p.cod_de_forma, p.tipo_de_planilla,
               p.codigo_aduana, p.cod_banco, p.cod_agencia, p.fecha_contabilidad_liq,
               p.nro_declaracion, p.fecha_vencimiento, p.cod_status, p.anulada
        FROM planillas_emitidas p
        LEFT JOIN consignatarios c ON p.rif_consignatario = c.rif_consignatario
        LEFT JOIN agentes_aduanales a ON p.rif_agente = a.rif_agentes
        WHERE p.fecha_vencimiento < CURDATE()
        AND (p.fecha_cancelacion = '0000-00-00' OR p.fecha_cancelacion IS NULL OR p.fecha_cancelacion > p.fecha_vencimiento) 
        OR p.anulada = '0'
        AND p.fecha_contabilidad_liq BETWEEN ? AND ? 
        ORDER BY 
            CASE 
                WHEN UPPER(c.rif_consignatario) LIKE 'G%' THEN 1 -- Sector Público (starts with G, case-insensitive)
                WHEN UPPER(c.rif_consignatario) LIKE 'J%' THEN 2 -- Sector Privado (starts with J, case-insensitive)
                WHEN UPPER(c.rif_consignatario) LIKE 'V%' OR UPPER(c.rif_consignatario) LIKE 'E%' THEN 3 -- Natural (starts with V or E, case-insensitive)
                ELSE 4 
            END " . $orderByClause;

// 4. Preparar y ejecutar la consulta
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error al preparar la consulta: " . $conn->error);
}
$stmt->bind_param("ss", $fechaInicio, $fechaCierre);
$stmt->execute();
$resultado = $stmt->get_result();

// 5. Definir cabeceras de la tabla del cuerpo del reporte
// Ancho total de la página: 280 mm (Letter Landscape tiene 280-2x10 = 260 utilizables)
// Ajustar anchos de columna para 11 columnas
$pdf->SetFont('Arial', 'B', 7); // Fuente más pequeña para más columnas
$pdf->SetFillColor(200, 200, 200); // Color de fondo del encabezado igual al total
$pdf->SetTextColor(0);
$pdf->SetDrawColor(143, 143, 143);
$pdf->SetLineWidth(.3);

// Definir anchos de columna para el nuevo encabezado
$col_widths = array(
    25, // Nro Confrontación
    20, // Nro Formulario
    18, // Fecha Liq
    20, // RIF Consignatario
    45, // Nombre Consignatario
    18, // Fecha Pago
    18, // Monto
    15, // Tipo Forma
    20, // Tipo Planilla (Concepto)
    18, // Cód Aduana
    18, // Fecha Vencimiento
    15  // Estatus
);
$pdf->SetWidths($col_widths);

$header_labels = array(
    utf8_decode('Nro. Confrontación'),
    utf8_decode('Nro. Formulario'),
    utf8_decode('Fecha Liq.'),
    'RIF Consignatario',
    'Nombre Consignatario',
    utf8_decode('Fecha Pago'),
    'Monto',
    utf8_decode('Tipo Forma'),
    utf8_decode('Concepto'),
    utf8_decode('Cód. Aduana'),
    utf8_decode('Fecha Venc.'),
    'Estatus'
);

$current_group = '';
$totalGeneral = 0;

if ($resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        // Determine the group for the current row, making it case-insensitive
        $rif_first_char = strtoupper(substr($fila['rif_consignatario'], 0, 1));
        $rif_type = 'Otro'; // Default to 'Otro'

        if ($rif_first_char === 'G') {
            $rif_type = 'Sector Público';
        } elseif ($rif_first_char === 'J') {
            $rif_type = 'Sector Privado';
        } elseif ($rif_first_char === 'V' || $rif_first_char === 'E') {
            $rif_type = 'Natural';
        }

        // Add group header if it's a new group
        if ($rif_type !== $current_group) {
            $current_group = $rif_type;
            $pdf->Ln(5); // Small break
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, utf8_decode('Contribuyente: ' . $current_group), 0, 1, 'L');
            $pdf->Ln(2);
            // Re-print table headers for the new group
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetFillColor(200, 200, 200);
            $pdf->SetTextColor(0);
            $pdf->SetDrawColor(143, 143, 143);
            $pdf->SetLineWidth(.3);
            $pdf->Row($header_labels);
            $pdf->SetFont('Arial', '', 7); // Reset font for data rows
        }
        
            $fechaContabilidadDisplay = ($fila['fecha_contabilidad_liq'] === '0000-00-00' || empty($fila['fecha_contabilidad_liq'])) ? 'N/A' : date('d/m/Y', strtotime($fila['fecha_contabilidad_liq']));
            $fechaVencimientoDisplay = ($fila['fecha_vencimiento'] === '0000-00-00' || empty($fila['fecha_vencimiento'])) ? 'N/A' : date('d/m/Y', strtotime($fila['fecha_vencimiento']));
            $fechaCancelacionDisplay = ($fila['fecha_cancelacion'] === '0000-00-00' || empty($fila['fecha_cancelacion'])) ? 'N/A' : date('d/m/Y', strtotime($fila['fecha_cancelacion']));
        // Fill the table with data
        $pdf->SetFont('Arial', '', 7); // Set font for data rows
        $pdf->Row(array(
            utf8_decode($fila['nro_declaracion']), // Nro Confrontación
            utf8_decode($fila['nro_formulario']),
            $fechaContabilidadDisplay, // Fecha Liq
            utf8_decode($fila['rif_consignatario']),
            utf8_decode($fila['nombre_consignatario']),
            $fechaCancelacionDisplay, // Fecha Pago
            number_format($fila['monto2'], 2, ',', '.'), // Monto
            utf8_decode($fila['cod_de_forma']), // Tipo Forma
            utf8_decode($fila['tipo_de_planilla']), // Tipo Planilla (Concepto)
            utf8_decode($fila['codigo_aduana']), // Cód Aduana
            $fechaVencimientoDisplay, // Fecha Vencimiento
            utf8_decode($fila['cod_status']) // Estatus
        ));
        $totalGeneral += $fila['monto2'];
    }
} else {
    $pdf->Cell(array_sum($col_widths), 10, utf8_decode('No se encontraron registros para el rango de fechas seleccionado.'), 1, 1, 'C');
}

// 7. Mostrar total general
$pdf->Ln(5); // Add some space before total
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(200, 200, 200);
$pdf->SetTextColor(0);
$pdf->Cell(array_sum($col_widths) - 50, 10, 'TOTAL GENERAL RECAUDADO', 1, 0, 'C', true); // Adjust width for total
$pdf->Cell(50, 10, 'Bs. ' . number_format($totalGeneral, 2, ',', '.'), 1, 1, 'R', true);

// 8. Cerrar statement y conexión
$stmt->close();
$conn->close();

// 9. Salida del PDF con nombre de archivo dinámico
$fechaInicioFormatted = date('Y-m-d', strtotime($fechaInicio));
$fechaCierreFormatted = date('Y-m-d', strtotime($fechaCierre));
$filename = "reporte_derechos_pendientes_{$fechaInicioFormatted}_a_{$fechaCierreFormatted}.pdf";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

$pdf->Output('D', $filename); // 'D' para descargar el archivo
?>