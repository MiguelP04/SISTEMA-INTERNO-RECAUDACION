<?php
include('../../config.php');

// 1. Obtener parámetros de la URL
$fechaInicio = $_GET['fechaInicio'] ?? date('Y-m-d');
$fechaCierre = $_GET['fechaCierre'] ?? date('Y-m-d');

// --- Consulta SQL ---
// La lógica es para planillas cuya fecha_cancelacion es nula o '0000-00-00'
$orderByClause = "ORDER BY CASE WHEN UPPER(c.rif_consignatario) LIKE 'G%' THEN 1 WHEN UPPER(c.rif_consignatario) LIKE 'J%' THEN 2 WHEN UPPER(c.rif_consignatario) LIKE 'V%' OR UPPER(c.rif_consignatario) LIKE 'E%' THEN 3 ELSE 4 END, p.fecha_vencimiento";
$sql = "SELECT p.nro_formulario, c.rif_consignatario, c.nombre_consignatario, p.fecha_cancelacion, p.monto2, p.cod_de_forma, p.tipo_de_planilla, p.codigo_aduana, p.fecha_contabilidad_liq, p.nro_declaracion, p.fecha_vencimiento, p.cod_status FROM planillas_emitidas p LEFT JOIN consignatarios c ON p.rif_consignatario = c.rif_consignatario LEFT JOIN agentes_aduanales a ON p.rif_agente = a.rif_agentes WHERE p.fecha_vencimiento < CURDATE() AND p.fecha_contabilidad_liq BETWEEN ? AND ? " . $orderByClause;

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error al preparar la consulta: " . $conn->error);
}
$stmt->bind_param("ss", $fechaInicio, $fechaCierre);
$stmt->execute();
$resultado = $stmt->get_result();

// --- Encabezados para la descarga del archivo Excel ---
$filename = "Reporte_Derechos_Pendientes_" . date('Y-m-d') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

// --- Inicio de la estructura HTML para Excel ---
echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head><meta charset="utf-8"></head>';
echo '<body>';
echo '<h1>Reporte de Derechos Pendientes</h1>';
echo '<h3>Periodo de Emisión Desde: ' . date("d/m/Y", strtotime($fechaInicio)) . '    Hasta: ' . date("d/m/Y", strtotime($fechaCierre)) . '</h3>';
echo '<table border="1">';
echo '<thead><tr style="background-color:#A9A9A9; color: #FFFFFF;">';
echo '<th>Nro. Confrontación</th><th>Nro. Formulario</th><th>Fecha Liq.</th><th>RIF Consignatario</th><th>Nombre Consignatario</th><th>Fecha Pago</th><th>Monto</th><th>Tipo Forma</th><th>Concepto</th><th>Cód. Aduana</th><th>Fecha Venc.</th><th>Estatus</th>';
echo '</tr></thead><tbody>';

$current_group = '';
$totalGeneral = 0;

if ($resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $rif_first_char = strtoupper(substr($fila['rif_consignatario'], 0, 1));
        $rif_type = 'Otro';
        if ($rif_first_char === 'G') $rif_type = 'Sector Público';
        elseif ($rif_first_char === 'J') $rif_type = 'Sector Privado';
        elseif (in_array($rif_first_char, ['V', 'E'])) $rif_type = 'Natural';

        if ($rif_type !== $current_group) {
            $current_group = $rif_type;
            echo '<tr><td colspan="12" style="background-color:#D3D3D3; font-weight:bold;">Contribuyente: ' . htmlspecialchars($current_group) . '</td></tr>';
        }

        echo '<tr>';
        echo '<td>' . htmlspecialchars($fila['nro_declaracion']) . '</td>';
        echo '<td>' . htmlspecialchars($fila['nro_formulario']) . '</td>';
        echo '<td>' . ($fila['fecha_contabilidad_liq'] === '0000-00-00' ? 'N/A' : date('d/m/Y', strtotime($fila['fecha_contabilidad_liq']))) . '</td>';
        echo '<td>' . htmlspecialchars($fila['rif_consignatario']) . '</td>';
        echo '<td>' . htmlspecialchars($fila['nombre_consignatario']) . '</td>';
        echo '<td>' . ($fila['fecha_cancelacion'] === '0000-00-00' ? 'N/A' : date('d/m/Y', strtotime($fila['fecha_cancelacion']))) . '</td>';
        echo '<td>' . number_format($fila['monto2'], 2, ',', '.') . '</td>';
        echo '<td>' . htmlspecialchars($fila['cod_de_forma']) . '</td>';
        echo '<td>' . htmlspecialchars($fila['tipo_de_planilla']) . '</td>';
        echo '<td>' . htmlspecialchars($fila['codigo_aduana']) . '</td>';
        echo '<td>' . ($fila['fecha_vencimiento'] === '0000-00-00' ? 'N/A' : date('d/m/Y', strtotime($fila['fecha_vencimiento']))) . '</td>';
        echo '<td>' . htmlspecialchars($fila['cod_status']) . '</td>';
        echo '</tr>';
        $totalGeneral += $fila['monto2'];
    }
} else {
    echo '<tr><td colspan="12">No se encontraron registros.</td></tr>';
}

echo '</tbody></table>';
echo '<h3>TOTAL GENERAL: Bs. ' . number_format($totalGeneral, 2, ',', '.') . '</h3>';
echo '</body></html>';

$stmt->close();
$conn->close();
exit;