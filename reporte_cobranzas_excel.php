<?php
// reporte_cobranzas_excel.php

require('config.php');

// Configurar headers para descarga de Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Cobranzas.xls"');
header('Cache-Control: max-age=0');

// Obtener fechas del reporte
$fecha_actual_inicio = $_GET['fechaInicio'] ?? date('Y-m-01');
$fecha_actual_fin = $_GET['fechaCierre'] ?? date('Y-m-t');

// Calcular fechas del mes anterior
$fecha_inicio_mes_anterior = date('Y-m-01', strtotime('-1 month', strtotime($fecha_actual_inicio)));
$fecha_fin_mes_anterior = date('Y-m-t', strtotime('-1 month', strtotime($fecha_actual_inicio)));

// Definir estructura de impuestos con sus códigos
$impuestos_config = [
    ['header_title' => 'Impuesto de Importación Ordinario', 'cod_concepto' => ['01']],
    ['header_title' => 'Tasa de Servicio de Aduana', 'cod_concepto' => ['12']],
    ['header_title' => 'Multas', 'cod_concepto' => ['23']],
    ['header_title' => 'Intereses', 'cod_concepto' => ['22']],
    ['header_title' => 'Reintegros al fisco', 'cod_concepto' => []],
    ['header_title' => 'IVA', 'cod_concepto' => ['10']],
    ['header_title' => 'P.V.P. de Licores Importados', 'cod_concepto' => []],
    ['header_title' => 'Impuestos de Importación Bultos Postales', 'cod_concepto' => ['04']],
    ['header_title' => 'Habilitaciones', 'cod_concepto' => ['13']],
    ['header_title' => 'Derechos de Almacenaje', 'cod_concepto' => ['31']],
    ['header_title' => 'Timbres Fiscales', 'cod_concepto' => ['28']],
    ['header_title' => 'Otros 1', 'cod_concepto' => [], 'is_other' => true]
];

// Jerarquía de impuestos (1 = mayor prioridad)
$jerarquia_impuestos = [
    'Impuesto de Importación Ordinario' => 1,
    'IVA' => 2,
    'Bandas de Garantía, Capsulas y Sellos' => 3,
    'Tasa de Servicio de Aduana' => 4,
    'Multas' => 5,
    'Timbres Fiscales' => 6,
    'Derechos de Almacenaje' => 7,
    'Habilitaciones' => 8,
    'Intereses' => 9,
    'Reparo Fiscal' => 10
];

// Mapeo de códigos de concepto a nombres de impuesto
$codigo_a_impuesto = [];
foreach ($impuestos_config as $impuesto) {
    foreach ($impuesto['cod_concepto'] as $codigo) {
        $codigo_a_impuesto[$codigo] = $impuesto['header_title'];
    }
}
// Agregar impuestos de la jerarquía que no están en la configuración principal
$codigo_a_impuesto['30'] = 'Bandas de Garantía, Capsulas y Sellos';
$codigo_a_impuesto['19'] = 'Reparo Fiscal';

// Función para determinar el impuesto principal de una planilla
function obtenerImpuestoPrincipal($codigos_concepto, $jerarquia_impuestos, $codigo_a_impuesto) {
    $impuesto_principal = 'Otros 1';
    $prioridad_mas_alta = PHP_INT_MAX;
    
    foreach ($codigos_concepto as $codigo) {
        $nombre_impuesto = $codigo_a_impuesto[$codigo] ?? null;
        
        if ($nombre_impuesto && isset($jerarquia_impuestos[$nombre_impuesto])) {
            $prioridad = $jerarquia_impuestos[$nombre_impuesto];
            
            if ($prioridad < $prioridad_mas_alta) {
                $prioridad_mas_alta = $prioridad;
                $impuesto_principal = $nombre_impuesto;
            }
        }
    }
    
    return $impuesto_principal;
}

// Inicializar estructura de datos para el reporte
$datos_reporte = [];
foreach ($impuestos_config as $impuesto) {
    $nombre = $impuesto['header_title'];
    $datos_reporte[$nombre] = [
        'Pendientes por cobrar al inicio del mes' => ['cantidad' => 0, 'importe' => 0],
        'Planillas ingresadas en el mes' => ['cantidad' => 0, 'importe' => 0],
        'Total planillas' => ['cantidad' => 0, 'importe' => 0],
        'Planillas remitidas a cobros judiciales' => ['cantidad' => 0, 'importe' => 0],
        'Planillas recurridas en el mes con efectos suspendidos' => ['cantidad' => 0, 'importe' => 0],
        'Pagadas en el mes' => ['cantidad' => 0, 'importe' => 0],
        'Pagadas en meses anteriores' => ['cantidad' => 0, 'importe' => 0],
        'Anuladas en el mes' => ['cantidad' => 0, 'importe' => 0],
        'Compensadas en el mes' => ['cantidad' => 0, 'importe' => 0],
        'Descargadas en el mes' => ['cantidad' => 0, 'importe' => 0],
        'Declaradas prescritas en el mes' => ['cantidad' => 0, 'importe' => 0],
        'Declaradas incobrables en el mes' => ['cantidad' => 0, 'importe' => 0],
        'Pendientes de gestion de cobranzas al final del mes' => ['cantidad' => 0, 'importe' => 0]
    ];
}

// Obtener todas las planillas con sus impuestos
$sql = "SELECT p.nro_formulario, p.monto2, p.fecha_vencimiento, p.fecha_contabilidad_liq, 
                p.fecha_cancelacion, GROUP_CONCAT(iap.cod_concepto) AS conceptos
        FROM planillas_emitidas p
        LEFT JOIN impuestos_a_pagar iap ON p.nro_formulario = iap.nro_formulario
        GROUP BY p.nro_formulario";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $nro_formulario = $row['nro_formulario'];
        $monto = (float)$row['monto2'];
        $fecha_vencimiento = $row['fecha_vencimiento'];
        $fecha_contabilidad_liq = $row['fecha_contabilidad_liq'];
        $fecha_cancelacion = $row['fecha_cancelacion'];
        $codigos_concepto = $row['conceptos'] ? explode(',', $row['conceptos']) : [];
        
        // Determinar impuesto principal según jerarquía
        $impuesto_principal = obtenerImpuestoPrincipal($codigos_concepto, $jerarquia_impuestos, $codigo_a_impuesto);
        
        // Si el impuesto principal no está en los configurados, va a "Otros 1"
        if (!isset($datos_reporte[$impuesto_principal])) {
            $impuesto_principal = 'Otros 1';
        }
        
        // 1. Pendientes por cobrar al inicio del mes
        if (strtotime($fecha_vencimiento) < strtotime($fecha_inicio_mes_anterior) && 
            ($fecha_cancelacion === '0000-00-00' || $fecha_cancelacion === null)) {
            $datos_reporte[$impuesto_principal]['Pendientes por cobrar al inicio del mes']['cantidad']++;
            $datos_reporte[$impuesto_principal]['Pendientes por cobrar al inicio del mes']['importe'] += $monto;
        }
        
        // 2. Planillas ingresadas en el mes
        if ($fecha_contabilidad_liq >= $fecha_actual_inicio && $fecha_contabilidad_liq <= $fecha_actual_fin) {
            $datos_reporte[$impuesto_principal]['Planillas ingresadas en el mes']['cantidad']++;
            $datos_reporte[$impuesto_principal]['Planillas ingresadas en el mes']['importe'] += $monto;
        }
        
        // 3. Pagadas en el mes
        if ($fecha_cancelacion && $fecha_cancelacion !== '0000-00-00' && 
            $fecha_cancelacion >= $fecha_actual_inicio && $fecha_cancelacion <= $fecha_actual_fin) {
            $datos_reporte[$impuesto_principal]['Pagadas en el mes']['cantidad']++;
            $datos_reporte[$impuesto_principal]['Pagadas en el mes']['importe'] += $monto;
        }
    }
    
    // Calcular totales (suma de pendientes + ingresadas)
    foreach ($datos_reporte as $impuesto => $valores) {
        $pendientes = $valores['Pendientes por cobrar al inicio del mes'];
        $ingresadas = $valores['Planillas ingresadas en el mes'];
        
        $datos_reporte[$impuesto]['Total planillas']['cantidad'] = $pendientes['cantidad'] + $ingresadas['cantidad'];
        $datos_reporte[$impuesto]['Total planillas']['importe'] = $pendientes['importe'] + $ingresadas['importe'];
    }
} else {
    die("Error al consultar las planillas: " . $conn->error);
}

// Generar el HTML para Excel
echo '<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
      <head>
          <meta http-equiv="Content-type" content="text/html;charset=utf-8" />
          <style>
              table {
                  border-collapse: collapse;
                  width: 100%;
              }
              th, td {
                  border: 1px solid black;
                  padding: 5px;
                  text-align: center;
              }
              .left-align {
                  text-align: left;
              }
              .numeric {
                  mso-number-format:"\#\,\#\#0\.00";
              }
          </style>
      </head>
      <body>
          <table>
              <thead>
                  <tr>
                      <th rowspan="2">Impuesto</th>
                      <th colspan="2">Pendientes por cobrar al inicio del mes</th>
                      <th colspan="2">Planillas ingresadas en el mes</th>
                      <th colspan="2">Total planillas</th>
                      <th colspan="2">Planillas remitidas a cobros judiciales</th>
                      <th colspan="2">Planillas recurridas en el mes con efectos suspendidos</th>
                      <th colspan="2">Pagadas en el mes</th>
                      <th colspan="2">Pagadas en meses anteriores</th>
                      <th colspan="2">Anuladas en el mes</th>
                      <th colspan="2">Compensadas en el mes</th>
                      <th colspan="2">Descargadas en el mes</th>
                      <th colspan="2">Declaradas prescritas en el mes</th>
                      <th colspan="2">Declaradas incobrables en el mes</th>
                      <th colspan="2">Pendientes de gestion de cobranzas al final del mes</th>
                  </tr>
                  <tr>
                      <!-- Repetir "Cantidad" e "Importe" para cada columna -->
                      <th>Cantidad</th><th>Importe</th>
                      <th>Cantidad</th><th>Importe</th>
                      <th>Cantidad</th><th>Importe</th>
                      <th>Cantidad</th><th>Importe</th>
                      <th>Cantidad</th><th>Importe</th>
                      <th>Cantidad</th><th>Importe</th>
                      <th>Cantidad</th><th>Importe</th>
                      <th>Cantidad</th><th>Importe</th>
                      <th>Cantidad</th><th>Importe</th>
                      <th>Cantidad</th><th>Importe</th>
                      <th>Cantidad</th><th>Importe</th>
                      <th>Cantidad</th><th>Importe</th>
                  </tr>
              </thead>
              <tbody>';

// Llenar las filas con los datos
foreach ($impuestos_config as $impuesto) {
    $nombre = $impuesto['header_title'];
    $datos = $datos_reporte[$nombre];
    
    echo '<tr>
          <td class="left-align">' . $nombre . '</td>';
    
    // Para cada categoría, mostrar cantidad e importe
    foreach ($datos as $categoria => $valores) {
        echo '<td>' . ($valores['cantidad'] > 0 ? $valores['cantidad'] : '') . '</td>
              <td class="numeric">' . ($valores['importe'] > 0 ? number_format($valores['importe'], 2, '.', ',') : '') . '</td>';
    }
    
    echo '</tr>';
}

echo '        </tbody>
          </table>
          <div style="margin-top: 20px;">
              <strong>Periodo del reporte:</strong> ' . date('d/m/Y', strtotime($fecha_actual_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_actual_fin)) . '
          </div>
      </body>
      </html>';

$conn->close();
?>