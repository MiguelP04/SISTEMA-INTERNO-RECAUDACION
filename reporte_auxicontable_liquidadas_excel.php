<?php
// Incluir el archivo de configuración de la base de datos
require('config.php');

// Mapeo de valores para mostrar (base de datos -> interfaz)
$dbToDisplayMap = [
    '19' => '80',
    '20' => '81',
    '21' => '86'
];
$filterFormaCodes = ['20'];
$fechaInicio = $_GET['fechaInicio'] ?? date('Y-m-d');
$fechaCierre = $_GET['fechaCierre'] ?? date('Y-m-d');
$ordenarPor = $_GET['ordenarPor'] ?? '';

// Construir la cláusula ORDER BY
$orderByClause = "ORDER BY p.fecha_contabilidad_liq";
$allowedOrderByColumns = [
    'cod_banco', 'cod_agencia', 'tipo_de_planilla',
    'cod_de_forma', 'codigo_aduana'
];

if (!empty($ordenarPor) && in_array($ordenarPor, $allowedOrderByColumns)) {
    $orderByClause .= ", p." . $ordenarPor;
}
$orderByClause .= ", nro_formulario";

// --- Encabezados para la descarga del archivo Excel ---
$filename = "Reporte_Auxi_Contable_Liquidados_" . date('Y-m-d') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

// --- Inicio de la estructura HTML para Excel ---
echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head><meta charset="utf-8"></head>';
echo '<body>';
echo '<h1>Reporte Auxi Contable Liquidados</h1>';
echo '<h3>Periodo de Emisión Desde: ' . date("d/m/Y", strtotime($fechaInicio)) . '    Hasta: ' . date("d/m/Y", strtotime($fechaCierre)) . '</h3>';
echo '<table border="1">';
echo '<thead><tr style="background-color:#A9A9A9; color: #FFFFFF;">';
echo '<th>Nro. Confrontación</th><th>Nro. Formulario</th><th>Fecha Liq.</th><th>RIF Consignatario</th><th>Nombre Consignatario</th><th>Fecha Pago</th><th>Monto</th><th>Tipo Forma</th><th>Concepto</th><th>Cód. Aduana</th><th>Fecha Venc.</th><th>Estatus</th>';
echo '</tr></thead><tbody>';

?>
<meta charset="UTF-8">
<style>
    th { background-color: #0d6efd; color: white; font-weight: bold; border: 1px solid #000; }
    td { border: 1px solid #ccc; text-align: left; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .total-row td { background-color: #f2f2f2; font-weight: bold; }
</style>
<table>
    <thead>
        <?php
        // Obtener todos los conceptos de la base de datos para mapear cod_concepto a descripcion_concepto
        $conceptos_db_raw_sql = "SELECT cod_concepto, descripcion_concepto FROM tipos_de_impuestos";
        $conceptos_db_raw_result = $conn->query($conceptos_db_raw_sql);

        $db_concept_map_code_to_desc = []; // [cod_concepto => descripcion_concepto]
        if ($conceptos_db_raw_result) {
            while ($row = $conceptos_db_raw_result->fetch_assoc()) {
                $db_concept_map_code_to_desc[$row['cod_concepto']] = $row['descripcion_concepto'];
            }
        }

        // Definición de las columnas de "Ramos de Ingreso" en el orden EXACTO y con sus asociaciones
        $custom_ramos_columns_config = [
            ['header_title' => 'Impuesto de Importación Ordinario', 'target_cod_concepto' => '01'],
            ['header_title' => 'Impuesto de Importación de Bultos Postales', 'target_cod_concepto' => '04'],
            ['header_title' => 'Impuesto Interno por la Importaciónde Alcoholes y Bebidas Alcohólicas', 'target_cod_concepto' => null],
            ['header_title' => 'Impuesto sobre el Precio de Venta al Público de las Cervezas y Vinos Naturales Importados', 'target_cod_concepto' => null],
            ['header_title' => 'Impuesto Sobre el Precio de Venta al Público de Otras Bbeidas hasta 50.0° G.L. Importadas', 'target_cod_concepto' => null],
            ['header_title' => 'Impuesto sobre expedición al público de especies alcohólicas importadas', 'target_cod_concepto' => null],
            ['header_title' => 'Impto.sobre la vta. De cigarrillos , tabacos y picaduras', 'target_cod_concepto' => '56'],
            ['header_title' => 'Impuesto al Valor Agregado sobre la Importación de Bienes y Servicios', 'target_cod_concepto' => '10'],
            ['header_title' => 'Ingresos por Tasas', 'target_cod_concepto' => null],
            ['header_title' => 'Servicios de Aduana', 'target_cod_concepto' => '12'],
            ['header_title' => 'Habilitación de Aduanas', 'target_cod_concepto' => '13'],
            ['header_title' => 'Derechos de Almacenaje', 'target_cod_concepto' => '31'],
            ['header_title' => 'Corretaje de Bultos Postales', 'target_cod_concepto' => '15'],
            ['header_title' => 'Servicios de consulta sobre clasificación arancelaria, aduanera', 'target_cod_concepto' => '35'],
            ['header_title' => 'Bandas de Garantía, Capsulas y Sellos', 'target_cod_concepto' => '30'],
            ['header_title' => 'Estampillas Fiscales', 'target_cod_concepto' => '28'],
            ['header_title' => 'Intereses Moratorios', 'target_cod_concepto' => '22'],
            ['header_title' => 'Reparos Fiscales', 'target_cod_concepto' => '19'],
            ['header_title' => 'Sanciones Fiscales', 'target_cod_concepto' => '23'],
            ['header_title' => 'Juicios y Costas Procesales', 'target_cod_concepto' => null],
            ['header_title' => 'Multas y Recargos', 'target_cod_concepto' => null],
            ['header_title' => 'Interes por Financiamiento de Deudas Tributarias', 'target_cod_concepto' => null],
            ['header_title' => 'Producto de Remate', 'target_cod_concepto' => null],
            ['header_title' => 'Otros Ingresos Ordinarios', 'target_cod_concepto' => null],
        ];

        // Construir el array de conceptos para el display final de ramos.
        $conceptos_for_display = [];
        $concept_codes_to_sum_in_sql = []; // Para construir la parte SELECT de la consulta principal
        foreach ($custom_ramos_columns_config as $config) {
            $mapped_cod = $config['target_cod_concepto'];
            $db_desc_for_alias = null;
            if ($mapped_cod !== null && isset($db_concept_map_code_to_desc[$mapped_cod])) {
                $db_desc_for_alias = $db_concept_map_code_to_desc[$mapped_cod];
                $concept_codes_to_sum_in_sql[] = $mapped_cod; // Añadir solo los que tienen un código asociado
            }
            $conceptos_for_display[] = [
                'title' => $config['header_title'], // Cambiado a 'title' para consistencia con 'sub_headers'
                'mapped_cod_concepto' => $mapped_cod,
                'db_description_for_alias' => $db_desc_for_alias
            ];
        }

        // Definir la estructura completa de los encabezados y sus correspondientes campos/tipos
        $column_groups = [
            [
                'main_header_title' => 'Contribuyentes',
                'sub_headers' => [
                    ['title' => 'RIF Agente Aduanal', 'field' => 'rif_agentes'],
                    ['title' => 'Nombre Agente Aduanal', 'field' => 'nombre_agente'],
                    ['title' => 'RIF Consignatario', 'field' => 'rif_consignatario'],
                    ['title' => 'Nombre Consignatario', 'field' => 'nombre_consignatario'],
                ],
                'type' => 'grouped'
            ],
            [
                'main_header_title' => null, // Indica columnas individuales para la Fila 3
                'sub_headers' => [
                    ['title' => 'Nro. Documento (Nro. Formulario)', 'field' => 'nro_formulario'],
                    ['title' => 'Fech. Liq', 'field' => 'fecha_contabilidad_liq'],
                    ['title' => 'Nro Liq', 'field' => 'nro_liquidacion'],
                    ['title' => 'Fech Notificación', 'field' => 'fecha_de_notificacion'],
                    ['title' => 'Numero de Resolución', 'field' => 'nro_resolucion'],
                    ['title' => 'Ejercicios Fiscales', 'field' => null] // Empty
                ],
                'type' => 'individual'
            ],
            [
                'main_header_title' => 'Ramos de Ingreso',
                'sub_headers' => $conceptos_for_display, // Esto ya contiene los títulos individuales de ramos
                'type' => 'grouped'
            ],
            [
                'main_header_title' => null, // Columna individual
                'sub_headers' => [['title' => 'Total Planilla Liquidada', 'field' => 'calculated_ramos_sum']],
                'type' => 'individual'
            ],
            [
                'main_header_title' => 'Anuladas',
                'sub_headers' => [
                    ['title' => 'Resolucion N°', 'field' => null], // Empty
                    ['title' => 'Fecha', 'field' => null],       // Empty
                    ['title' => 'Monto', 'field' => null]        // Empty
                ],
                'type' => 'grouped'
            ],
            [
                'main_header_title' => null, // Columnas individuales
                'sub_headers' => [
                    ['title' => 'Recurso', 'field' => null],
                    ['title' => 'Compensacion', 'field' => null],
                    ['title' => 'cesion', 'field' => null],
                    ['title' => 'proceso de Incobrabilidad', 'field' => null]
                ],
                'type' => 'individual'
            ],
            [
                'main_header_title' => 'Recaudado / Verificación SIVIT',
                'sub_headers' => [
                    ['title' => 'Banco (Cod_banco)', 'field' => 'cod_banco'],
                    ['title' => 'N° de Documento (nro_formulario)', 'field' => 'nro_formulario'],
                    ['title' => 'Fecha (fecha_cancelacion)', 'field' => 'fecha_cancelacion'],
                    ['title' => 'Monto (monto2)', 'field' => 'monto2']
                ],
                'type' => 'grouped'
            ],
            [
                'main_header_title' => null, // Columnas individuales
                'sub_headers' => [
                    ['title' => 'F. Emisión', 'field' => 'fecha_emision'],
                    ['title' => 'F. Vencimiento', 'field' => 'fecha_vencimiento'],
                    ['title' => 'Tipo Planilla', 'field' => 'tipo_de_planilla'],
                    ['title' => 'Cod. Forma', 'field' => 'cod_de_forma'],
                    ['title' => 'Cod. Aduana', 'field' => 'codigo_aduana'],
                    ['title' => 'Cod. Agencia', 'field' => 'cod_agencia']
                ],
                'type' => 'individual'
            ]
        ];

        // Calcular el número total de columnas de "hoja" (la última fila de encabezados)
        $total_cols_final_leaf_nodes = 0;
        foreach ($column_groups as $group) {
            $total_cols_final_leaf_nodes += count($group['sub_headers']);
        }
        ?>

        <tr>
            <th colspan="<?php echo $total_cols_final_leaf_nodes; ?>" style="text-align:center; font-size: 16px;">Reporte de Planillas Liquidadas Desde <?php echo date('d/m/Y', strtotime($fechaInicio)); ?> hasta <?php echo date('d/m/Y', strtotime($fechaCierre)); ?></th>
        </tr>

        <tr>
            <?php foreach ($column_groups as $group): ?>
                <?php if ($group['type'] === 'grouped'): ?>
                    <th colspan="<?php echo count($group['sub_headers']); ?>" style="text-align:center;"><?php echo htmlspecialchars($group['main_header_title']); ?></th>
                <?php elseif ($group['type'] === 'individual'): ?>
                    <?php foreach ($group['sub_headers'] as $sub_header_item): ?>
                        <th></th> <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </tr>

        <tr>
            <?php foreach ($column_groups as $group): ?>
                <?php if ($group['type'] === 'grouped'): ?>
                    <?php foreach ($group['sub_headers'] as $sub_header_item): ?>
                        <th><?php echo htmlspecialchars($sub_header_item['title']); ?></th>
                    <?php endforeach; ?>
                <?php elseif ($group['type'] === 'individual'): ?>
                    <?php foreach ($group['sub_headers'] as $sub_header_item): ?>
                        <th><?php echo htmlspecialchars($sub_header_item['title']); ?></th> <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
    <?php
    // Construir la parte SELECT para las columnas dinámicas de "Ramos de Ingreso"
    $select_ramos = [];
    foreach (array_unique($concept_codes_to_sum_in_sql) as $cod_to_sum) {
        $desc_for_alias = $db_concept_map_code_to_desc[$cod_to_sum];
        $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $desc_for_alias);
        $select_ramos[] = "SUM(CASE WHEN iap.cod_concepto = '" . $conn->real_escape_string($cod_to_sum) . "' THEN iap.monto ELSE 0 END) AS " . $alias;
    }
    $select_ramos_str = implode(', ', $select_ramos);

    // Consulta SQL principal para obtener los datos de las planillas.
    $sql = "SELECT p.nro_formulario, p.fecha_emision, p.fecha_vencimiento,
                   p.cod_de_forma, p.tipo_de_planilla, p.monto2, p.fecha_cancelacion,
                   p.cod_banco, p.cod_agencia, p.codigo_aduana, p.fecha_contabilidad_liq,
                   p.nro_liquidacion, p.fecha_de_notificacion, p.nro_resolucion,
                   c.rif_consignatario, c.nombre_consignatario,
                   a.rif_agentes, a.nombre_agente";

    if (!empty($select_ramos_str)) {
        $sql .= ", " . $select_ramos_str;
    }

    $sql .= " FROM planillas_emitidas p
              LEFT JOIN consignatarios c ON p.rif_consignatario = c.rif_consignatario
              LEFT JOIN agentes_aduanales a ON p.rif_agente = a.rif_agentes
              LEFT JOIN impuestos_a_pagar iap ON p.nro_formulario = iap.nro_formulario
              WHERE p.fecha_contabilidad_liq BETWEEN ? AND ?
              GROUP BY p.nro_formulario ";

    $sql .= " " . $orderByClause;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fechaInicio, $fechaCierre);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_general_reporte = 0; // Total para la columna 'Monto (monto2)' en SIVIT, o el total final de planillas.

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()):
            $total_general_reporte += $row['monto2']; // Sumar al total general del reporte

            // Formatear fechas para display
            $fechaEmisionDisplay = ($row['fecha_emision'] === '0000-00-00' || empty($row['fecha_emision'])) ? 'N/A' : date('d/m/Y', strtotime($row['fecha_emision']));
            $fechaVencimientoDisplay = ($row['fecha_vencimiento'] === '0000-00-00' || empty($row['fecha_vencimiento'])) ? 'N/A' : date('d/m/Y', strtotime($row['fecha_vencimiento']));
            $fechaCancelacionDisplay = ($row['fecha_cancelacion'] === '0000-00-00' || empty($row['fecha_cancelacion'])) ? 'Pendiente' : date('d/m/Y', strtotime($row['fecha_cancelacion']));
            $fechaContabilidadLiqDisplay = ($row['fecha_contabilidad_liq'] === '0000-00-00' || empty($row['fecha_contabilidad_liq'])) ? 'N/A' : date('d/m/Y', strtotime($row['fecha_contabilidad_liq']));
            $fechaNotificacionDisplay = ($row['fecha_de_notificacion'] === '0000-00-00' || empty($row['fecha_de_notificacion'])) ? 'N/A' : date('d/m/Y', strtotime($row['fecha_de_notificacion']));

            $codFormaDisplay = $dbToDisplayMap[$row['cod_de_forma']] ?? $row['cod_de_forma'];
        ?>
            <tr>
                <?php
                $current_ramos_sum = 0; // Initialize sum for 'Total Planilla Liquidada' per row

                // Loop through column groups to print data cells
                foreach ($column_groups as $group) {
                    if ($group['type'] === 'grouped') {
                        foreach ($group['sub_headers'] as $sub_header_item) {
                            echo '<td class="text-right">'; // Default alignment for numbers, adjust if specific text is needed

                            if ($group['main_header_title'] === 'Ramos de Ingreso') {
                                $mapped_cod = $sub_header_item['mapped_cod_concepto'];
                                if ($mapped_cod !== null) {
                                    $db_desc_for_alias = $sub_header_item['db_description_for_alias'];
                                    if ($db_desc_for_alias !== null) {
                                        $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $db_desc_for_alias);
                                        if (isset($row[$alias]) && $row[$alias] > 0) {
                                            echo number_format($row[$alias], 2, ',', '.');
                                            $current_ramos_sum += $row[$alias];
                                        }
                                    }
                                }
                            } else { // Para otros tipos 'grouped' (Contribuyentes, Anuladas, Recaudado/SIVIT)
                                $field = $sub_header_item['field'] ?? null; // Obtener 'field' de forma segura
                                if ($field === 'fecha_cancelacion' && $group['main_header_title'] === 'Recaudado / Verificación SIVIT') {
                                    echo $fechaCancelacionDisplay;
                                } elseif ($field === 'monto2' && $group['main_header_title'] === 'Recaudado / Verificación SIVIT') {
                                    echo number_format($row[$field], 2, ',', '.');
                                } elseif ($field !== null && isset($row[$field])) { // Verificar si el campo existe en la fila
                                    echo htmlspecialchars($row[$field]);
                                }
                                // Para Anuladas, si los campos son null, se imprimirá una celda vacía, lo cual es correcto.
                            }
                            echo '</td>';
                        }
                    } elseif ($group['type'] === 'individual') {
                        foreach ($group['sub_headers'] as $sub_header_item) {
                            $field = $sub_header_item['field'] ?? null; // Obtener 'field' de forma segura
                            echo '<td';
                            // Aplicar text-right para campos de moneda
                            if (in_array($field, ['calculated_ramos_sum', 'monto2'])) {
                                echo ' class="text-right"';
                            }
                            echo '>';
                            if ($field === null) {
                                echo ''; // Celda vacía para campos nulos
                            } elseif ($field === 'fecha_contabilidad_liq') {
                                echo $fechaContabilidadLiqDisplay;
                            } elseif ($field === 'fecha_de_notificacion') {
                                echo $fechaNotificacionDisplay;
                            } elseif ($field === 'fecha_emision') {
                                echo $fechaEmisionDisplay;
                            } elseif ($field === 'fecha_vencimiento') {
                                echo $fechaVencimientoDisplay;
                            } elseif ($field === 'cod_de_forma') {
                                echo htmlspecialchars($codFormaDisplay);
                            } elseif ($field === 'calculated_ramos_sum') {
                                echo '<strong>' . number_format($current_ramos_sum, 2, ',', '.') . '</strong>';
                            } elseif ($field !== null && isset($row[$field])) { // Verificar si el campo existe en la fila
                                echo htmlspecialchars($row[$field]);
                            }
                            echo '</td>';
                        }
                    }
                }
                ?>
            </tr>
        <?php
        endwhile;
    } else {
        $total_cols_no_data = $total_cols_final_leaf_nodes;
        ?>
        <tr>
            <td colspan="<?php echo $total_cols_no_data; ?>" class="text-center">No se encontraron registros para el rango de fechas seleccionado.</td>
        </tr>
        <?php
    }
    ?>
    <tr class="total-row">
        <?php
        // Prepare data for the total row calculations for ramos
        $total_ramos_sql = "SELECT " . $select_ramos_str . "
                            FROM planillas_emitidas p
                            LEFT JOIN impuestos_a_pagar iap ON p.nro_formulario = iap.nro_formulario
                            WHERE p.fecha_contabilidad_liq BETWEEN ? AND ?";
        $total_ramos_stmt = $conn->prepare($total_ramos_sql);
        $total_ramos_stmt->bind_param("ss", $fechaInicio, $fechaCierre);
        $total_ramos_stmt->execute();
        $total_ramos_result = $total_ramos_stmt->get_result();
        $total_ramos_row = $total_ramos_result->fetch_assoc();
        $total_ramos_stmt->close();

        $total_ramos_sum_all_report = 0; // For 'Total Planilla Liquidada' in the total row

        // Loop through column groups to print total row cells
        foreach ($column_groups as $group) {
            if ($group['type'] === 'grouped') {
                if ($group['main_header_title'] === 'Ramos de Ingreso') {
                    foreach ($group['sub_headers'] as $sub_header_item) {
                        $mapped_cod = $sub_header_item['mapped_cod_concepto'];
                        echo '<td class="text-right">';
                        if ($mapped_cod !== null) {
                            $db_desc_for_alias = $sub_header_item['db_description_for_alias'];
                            if ($db_desc_for_alias !== null) {
                                $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $db_desc_for_alias);
                                if (isset($total_ramos_row[$alias]) && $total_ramos_row[$alias] > 0) {
                                    echo '<strong>' . number_format($total_ramos_row[$alias], 2, ',', '.') . '</strong>';
                                    $total_ramos_sum_all_report += $total_ramos_row[$alias];
                                }
                            }
                        }
                        echo '</td>';
                    }
                } elseif ($group['main_header_title'] === 'Recaudado / Verificación SIVIT') {
                    foreach ($group['sub_headers'] as $sub_header_item) {
                        $field = $sub_header_item['field'] ?? null; // Obtener 'field' de forma segura
                        echo '<td class="text-right">';
                        if ($field === 'monto2') { // Solo para el campo 'Monto' en SIVIT
                            echo '<strong>' . number_format($total_general_reporte, 2, ',', '.') . '</strong>';
                        } else {
                            echo ''; // Otros campos en SIVIT están vacíos en la fila total
                        }
                        echo '</td>';
                    }
                } elseif ($group['main_header_title'] === 'Contribuyentes') {
                     // colspan para el grupo Contribuyentes, con el texto "TOTAL GENERAL RECAUDADO"
                    echo '<td colspan="' . count($group['sub_headers']) . '" class="text-right"><strong>TOTAL GENERAL RECAUDADO</strong></td>';
                } else { // El grupo Anuladas está vacío en la fila total
                    echo '<td colspan="' . count($group['sub_headers']) . '"></td>';
                }
            } elseif ($group['type'] === 'individual') {
                if (isset($group['sub_headers'][0]['field']) && $group['sub_headers'][0]['field'] === 'calculated_ramos_sum') {
                    echo '<td class="text-right"><strong>' . number_format($total_ramos_sum_all_report, 2, ',', '.') . '</strong></td>';
                } else {
                    echo '<td colspan="' . count($group['sub_headers']) . '"></td>';
                }
            }
        }
        ?>
    </tr>
    </tbody>
</table>
<?php
$stmt->close();
$conn->close();
?>