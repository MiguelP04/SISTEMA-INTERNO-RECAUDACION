<?php
session_start();
require_once '../../config.php';
require_once '../../menu.php';

// --- Función para obtener datos de una tabla para selects con código y nombre ---
function getDataForSelect($conn, $table, $valueColumn, $codeColumn, $nameColumn, $orderByColumn, $orderDirection = 'ASC') {
    $data = [];
    $sql = "SELECT `" . $conn->real_escape_string($valueColumn) . "`, `" . $conn->real_escape_string($codeColumn) . "`, `" . $conn->real_escape_string($nameColumn) . "`
            FROM `" . $conn->real_escape_string($table) . "`
            ORDER BY `" . $conn->real_escape_string($orderByColumn) . "` " . $conn->real_escape_string($orderDirection);
    $result = $conn->query($sql);

    if ($result === false) {
        error_log("Error al obtener datos de la tabla $table: " . $conn->error);
        return [];
    }

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    $result->free();
    return $data;
}


function getTipoContribuyenteForSelectFilter($conn) {
    $data = [];
    $sql = "SELECT `id_tipo_contribuyente`, `desc_tipo_contribuyente` FROM `tipo_contribuyente` ORDER BY `id_tipo_contribuyente` ASC";
    $result = $conn->query($sql);

    if ($result === false) {
        error_log("Error al obtener datos de la tabla tipo_contribuyente: " . $conn->error);
        return [];
    }

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $display_text = $row['desc_tipo_contribuyente'];
            switch (strtoupper($display_text)) {
                case 'GOBIERNO':
                    $display_text = 'G';
                    break;
                case 'JURIDICO':
                    $display_text = 'J';
                    break;
                case 'NATURAL':
                    $display_text = 'V';
                    break;
                case 'EXTRANJERO':
                    $display_text = 'E';
                    break;
            }
            $data[] = [
                'id_tipo_contribuyente' => $row['id_tipo_contribuyente'],
                'desc_tipo_contribuyente' => $display_text
            ];
        }
    }
    $result->free();
    return $data;
}

// --- Function to get Consignatario data including inferred type for filtering ---
function getAllConsignatariosWithInferredType($conn) {
    $consignatarios = [];
    $sql = "SELECT rif_consignatario, nombre_consignatario, tipo_contribuyente FROM consignatarios ORDER BY rif_consignatario ASC";
    $result = $conn->query($sql);

    if ($result === false) {
        error_log("Error al obtener datos de consignatarios: " . $conn->error);
        return [];
    }

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $rif = $row['rif_consignatario'];
            $rif_letter = strtoupper(substr($rif, 0, 1));
            $type_id = null;

            if (!empty($row['tipo_contribuyente'])) {
                $type_id = (int)$row['tipo_contribuyente'];
            } else {
                switch ($rif_letter) {
                    case 'V':
                    case 'E':
                        $type_id = 3;
                        break;
                    case 'J':
                        $type_id = 2;
                        break;
                    case 'G':
                        $type_id = 1;
                        break;
                    default:
                        $type_id = null;
                        break;
                }
            }

            $consignatarios[] = [
                'rif_consignatario' => $rif,
                'nombre_consignatario' => $row['nombre_consignatario'],
                'tipo_contribuyente_id' => $type_id
            ];
        }
    }
    $result->free();
    return $consignatarios;
}

// --- Function to get Agent data including inferred type for filtering ---
function getAllAgentsWithInferredType($conn) {
    $agents = [];
    $sql = "SELECT rif_agentes, nombre_agente FROM agentes_aduanales ORDER BY rif_agentes ASC";
    $result = $conn->query($sql);

    if ($result === false) {
        error_log("Error al obtener datos de agentes_aduanales: " . $conn->error);
        return [];
    }

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $rif = $row['rif_agentes'];
            $rif_letter = strtoupper(substr($rif, 0, 1));
            $type_id = null;
            switch ($rif_letter) {
                case 'V':
                case 'E':
                    $type_id = 3;
                    break;
                case 'J':
                    $type_id = 2;
                    break;
                case 'G':
                    $type_id = 1;
                    break;
                default:
                    $type_id = null;
                    break;
            }

            $agents[] = [
                'rif_agentes' => $rif,
                'nombre_agente' => $row['nombre_agente'],
                'tipo_contribuyente_id' => $type_id
            ];
        }
    }
    $result->free();
    return $agents;
}

// Obtener datos para los desplegables
$aduanas = getDataForSelect($conn, 'aduanas', 'codigo_aduana', 'codigo_aduana', 'nombre_de_la_aduana', 'codigo_aduana');
$bancos = getDataForSelect($conn, 'bancos', 'cod_banco', 'cod_banco', 'nombre_banco', 'cod_banco');
$dependencias_liquidadoras = getDataForSelect($conn, 'dependencia_liquidadora', 'codigo_dl', 'codigo_dl', 'denominacion', 'codigo_dl');
$tipos_liquidacion = getDataForSelect($conn, 'tipo_de_liquidacion', 'tipo_de_liquidacion', 'tipo_de_liquidacion', 'descripcion', 'tipo_de_liquidacion');
$tformas = getDataForSelect($conn, 'tformas', 'codigo_de_forma', 'codigo_de_forma', 'denominacion', 'codigo_de_forma');

// Obtener datos para los nuevos desplegables
$formas_impuestos = getDataForSelect($conn, 'formas_de_impuestos', 'cod_forma', 'cod_forma', 'descripcion', 'cod_forma');
$tipos_impuestos = getDataForSelect($conn, 'tipos_de_impuestos', 'cod_concepto', 'cod_concepto', 'descripcion_concepto', 'orden');

$consignatarios_with_type = getAllConsignatariosWithInferredType($conn);
$agentes_aduanales_with_type = getAllAgentsWithInferredType($conn);
$tipos_contribuyente_filter = getTipoContribuyenteForSelectFilter($conn);

// Obtener el año actual
$ano_actual = date('Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Control Interno Recaudación</title>
    <link href= "http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="http://10.164.64.91:8080/sistema interno recaudacion/fontawesome/css/all.min.css">
    
    <style>
        .parallel-sections {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .parallel-section {
            flex: 1;
            min-width: 300px;
        }
        
        .form-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #0d6efd;
        }
        .form-section h5 {
            color: #0d6efd;
            margin-bottom: 20px;
        }
        .rif-input-group {
            display: flex;
            align-items: center;
        }
        .rif-input-group select {
            width: 80px;
            margin-right: 10px;
        }
        .suggestions {
            position: absolute;
            z-index: 1000;
            width: calc(100% - 30px);
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border: 1px solid #ced4da;
            border-radius: 0 0 4px 4px;
            display: none;
        }
        .suggestion-item {
            padding: 8px 15px;
            cursor: pointer;
        }
        .suggestion-item:hover {
            background-color: #f8f9fa;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        .numeric-input {
            text-align: right;
        }
        .toggle-advanced {
            cursor: pointer;
            color: #0d6efd;
            margin-bottom: 15px;
            display: inline-block;
        }
        .date-auto-fill {
            background-color: #f0f8ff;
        }

        .rif-type-select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            padding-right: 25px; /* Espacio para el ícono */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-chevron-down' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
        }

        /* Estilos para la sección de impuestos */
        .impuestos-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .total-a-pagar-container {
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .total-a-pagar-label {
            font-weight: bold;
            margin-right: 10px;
        }
        .total-a-pagar-value {
            font-size: 1.2rem;
            color: #0d6efd;
        }
        
        /* Estilos para los ramos de ingreso */
        .ramo-ingreso-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            position: relative;
        }
        .ramo-ingreso-row {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
            align-items: flex-end;
        }
        .ramo-ingreso-field {
            flex: 1;
        }
        .remove-ramo-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #dc3545;
            background: none;
            border: none;
            cursor: pointer;
        }
        .add-ramo-btn {
            margin-top: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Registro de Planillas Aduanales</h3>
            </div>
            
            <div class="card-body">
                <?php if (isset($_GET['estado'])): ?>
                    <div class="alert alert-<?php echo ($_GET['estado'] == 'exito') ? 'success' : 'danger'; ?>">
                        <?php if ($_GET['estado'] == 'exito'): ?>
                            <i class="fas fa-check-circle me-2"></i>Planilla registrada con éxito.
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo isset($_GET['mensaje']) ? htmlspecialchars($_GET['mensaje']) : 'Error al registrar la planilla.'; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

               <form action="procesar_formulario.php" method="post" class="needs-validation" novalidate>
                    <!-- Sección 1: Datos Básicos -->
                    <div class="form-section">
                        <h5><i class="fas fa-info-circle me-2"></i>Datos Básicos</h5>
                        <div class="row g-3">
                            <!-- Primera fila - 4 campos -->
                            <div class="col-md-3">
                                <label for="nro_formulario" class="form-label required-field">N° Formulario</label>
                                <input type="text" class="form-control" id="nro_formulario" name="nro_formulario" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="codigo_aduana" class="form-label required-field">Aduana</label>
                                <select class="form-select" id="codigo_aduana" name="codigo_aduana" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($aduanas as $aduana): ?>
                                        <option value="<?php echo htmlspecialchars($aduana['codigo_aduana']); ?>">
                                            <?php echo htmlspecialchars($aduana['codigo_aduana'] . ' - ' . $aduana['nombre_de_la_aduana']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="cod_de_forma" class="form-label">Forma</label>
                                <select class="form-select cod-forma-select" id="cod_de_forma" name="cod_de_forma" 
                                data-mapping="storage" required>
                                <option value="">Seleccionar...</option>
                                <option value="80">80</option>
                                <option value="81">81</option>
                                <option value="86">86</option>
                                </select>
                                <input type="hidden" id="cod_de_forma_real" name="cod_de_forma_real" value="<?php echo htmlspecialchars($cod_de_forma_data); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="ano_declaracion" class="form-label required-field">Año</label>
                                <input type="number" class="form-control" id="ano_declaracion" name="ano_declaracion" value="<?php echo $ano_actual; ?>" required>
                            </div>
                            
                            <!-- Segunda fila - 5 campos -->
                            <div class="col-md-3">
                                <label for="nro_declaracion" class="form-label required-field">N° Declaración</label>
                                <input type="text" class="form-control" id="nro_declaracion" name="nro_declaracion" required>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="fecha_emision" class="form-label required-field">Fecha Emisión</label>
                                <input type="date" class="form-control" id="fecha_emision" name="fecha_emision" required>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="fecha_contabilidad_liq" class="form-label required-field">Fecha Liquidación</label>
                                <input type="date" class="form-control date-auto-fill" id="fecha_contabilidad_liq" name="fecha_contabilidad_liq" required>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="fecha_de_notificacion" class="form-label required-field">Fecha Notificación</label>
                                <input type="date" class="form-control date-auto-fill" id="fecha_de_notificacion" name="fecha_de_notificacion" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="fecha_vencimiento" class="form-label required-field">Fecha Vencimiento</label>
                                <input type="date" class="form-control date-auto-fill" id="fecha_vencimiento" name="fecha_vencimiento" required>
                            </div>
                            
                            <!-- Tercera fila - 3 campos -->
                            <div class="col-md-4">
                                <label for="nro_resolucion" class="form-label">N° Resolución</label>
                                <input type="text" class="form-control" id="nro_resolucion" name="nro_resolucion">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="nro_liquidacion" class="form-label required-field">N° Liquidación</label>
                                <input type="text" class="form-control" id="nro_liquidacion" name="nro_liquidacion" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="tipo_de_planilla" class="form-label required-field">Tipo de Planilla</label>
                                <select class="form-select" id="tipo_de_planilla" name="tipo_de_planilla" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($tipos_liquidacion as $tipo): ?>
                                        <option value="<?php echo htmlspecialchars($tipo['tipo_de_liquidacion']); ?>">
                                            <?php echo htmlspecialchars($tipo['tipo_de_liquidacion'] . ' - ' . $tipo['descripcion']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                   <!-- Sección 2 y 3: Consignatario y Agente Aduanal en paralelo -->
                    <div class="parallel-sections">
                        <!-- Sección Consignatario -->
                        <div class="parallel-section form-section">
                            <h5><i class="fas fa-user-tie me-2"></i>Datos del Consignatario</h5>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label required-field">RIF Consignatario</label>
                                    <div class="rif-input-group">
                                        <select class="form-select rif-type-select" id="tipo_consignatario_filter" style="max-width: 80px;">
                                            <option value="">-</option>
                                            <?php foreach ($tipos_contribuyente_filter as $tipo): ?>
                                                <option value="<?php echo htmlspecialchars($tipo['id_tipo_contribuyente']); ?>">
                                                    <?php echo htmlspecialchars($tipo['desc_tipo_contribuyente']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <option value="4">E</option>
                                        </select>
                                        <input type="text" class="form-control" id="rif_consignatario_numero" placeholder="Número" inputmode="numeric">
                                        <div id="suggestions_consignatario" class="suggestions"></div>
                                    </div>
                                    <input type="hidden" id="rif_consignatario" name="rif_consignatario" required>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label">Nombre Consignatario</label>
                                    <input type="text" class="form-control" id="nombre_consignatario_display" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- Sección Agente Aduanal -->
                        <div class="parallel-section form-section">
                            <h5><i class="fas fa-user-shield me-2"></i>Datos del Agente Aduanal</h5>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label required-field">RIF Agente</label>
                                    <div class="rif-input-group">
                                        <select class="form-select rif-type-select" id="tipo_agente_filter" style="max-width: 80px;">
                                            <option value="">-</option>
                                            <?php foreach ($tipos_contribuyente_filter as $tipo): ?>
                                                <option value="<?php echo htmlspecialchars($tipo['id_tipo_contribuyente']); ?>">
                                                    <?php echo htmlspecialchars($tipo['desc_tipo_contribuyente']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <option value="4">E</option>
                                        </select>
                                        <input type="text" class="form-control" id="rif_agente_numero" placeholder="Número" inputmode="numeric">
                                        <div id="suggestions_agente" class="suggestions"></div>
                                    </div>
                                    <input type="hidden" id="rif_agente" name="rif_agente" required>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label">Nombre Agente</label>
                                    <input type="text" class="form-control" id="nombre_agente_display" readonly>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Sección 3: Impuestos -->
                    <div class="form-section">
                        <div class="impuestos-header">
                            <h5><i class="fas fa-calculator me-2"></i>Impuestos</h5>
                            <div class="total-a-pagar-container">
                                <span class="total-a-pagar-label">Total a Pagar:</span>
                                <span class="total-a-pagar-value">Bs <span id="total-a-pagar-display">0.00</span></span>
                            </div>
                        </div>
                        
                        <!-- Ramos de Ingreso -->
                        <div id="ramos-ingreso-container">
                            <!-- Los ramos se agregarán dinámicamente aquí -->
                        </div>
                        
                        <button type="button" class="btn btn-outline-primary add-ramo-btn" id="add-ramo-btn">
                            <i class="fas fa-plus me-2"></i>Agregar Ramo de Ingreso
                        </button>
                        
                        <!-- Campos ocultos con valores por defecto -->
                        <input type="hidden" name="cod_org_liq" value="01">
                        <input type="hidden" name="cod_dl" value="1116">
                        <input type="hidden" name="dias" value="0">
                        <input type="hidden" name="fecha_cancelacion" value="0000-00-00">
                        <input type="hidden" name="cod_banco" value="999">
                        <input type="hidden" name="cod_agencia" value="999">
                        <input type="hidden" name="bl" value="">
                        <input type="hidden" name="fecha_contabilidad_rec" value="0000-00-00">
                        <input type="hidden" name="control_de_copias" value="0">
                        <input type="hidden" name="fecha_de_entreguese" value="0000-00-00">
                        <input type="hidden" name="anulada" value="0">
                        <input type="hidden" name="fecha_anulacion" value="0000-00-00">
                        <input type="hidden" name="fecha_recalmac" value="0000-00-00">
                        <input type="hidden" name="valor_cif" value="0">
                        <input type="hidden" name="peso_disp" value="0">
                        <input type="hidden" name="cantidad_dispon" value="0">
                        <input type="hidden" name="cod_status" value="DP">
                        <input type="hidden" name="cod_nuevo" value="DP">
                        <input type="hidden" name="fecha_providencia" value="0000-00-00">
                        <input type="hidden" name="valor_fob" value="0.00">
                        <input type="hidden" name="tipo_forma" value="">
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Registrar Planilla
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script para auto-rellenar fechas -->
    <script>
        document.getElementById('fecha_emision').addEventListener('change', function() {
            const fechaEmision = this.value;
            if (fechaEmision) {
                // Auto-rellenar otras fechas con la misma fecha de emisión
                document.getElementById('fecha_contabilidad_liq').value = fechaEmision;
                document.getElementById('fecha_de_notificacion').value = fechaEmision;
                document.getElementById('fecha_vencimiento').value = fechaEmision;
            }
        });

        // Configurar campos dependientes del valor de "Forma"
        document.getElementById('cod_de_forma').addEventListener('change', function() {
            const formaValue = this.value;
            let codPeriodo = '';
            let codDeForma = '';
            
            if (formaValue === '80' || formaValue === '81') {
                codPeriodo = '08';
            } else if (formaValue === '86') {
                codPeriodo = '07';
            }
            
            // Crear campo oculto para cod_periodo si no existe
            if (!document.querySelector('input[name="cod_periodo"]')) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'cod_periodo';
                input.value = codPeriodo;
                document.querySelector('form').appendChild(input);
            } else {
                document.querySelector('input[name="cod_periodo"]').value = codPeriodo;
            }
        });
    </script>
    
    <!-- Script para autocompletado y validación de RIF -->
    <script>
        window.ALL_CONSIGNATARIOS_DATA = <?php echo json_encode($consignatarios_with_type); ?>;
        window.ALL_AGENTS_DATA = <?php echo json_encode($agentes_aduanales_with_type); ?>;
        
        // Validación de formulario
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    // Establecer fecha y hora actual antes de enviar
                    const now = new Date();
                    const fechaSist = now.toISOString().split('T')[0];
                    const horaSist = now.toTimeString().split(' ')[0];
                    
                    // Crear campos ocultos para fecha_sist y hora_sist si no existen
                    if (!document.querySelector('input[name="fecha_sist"]')) {
                        const fechaInput = document.createElement('input');
                        fechaInput.type = 'hidden';
                        fechaInput.name = 'fecha_sist';
                        fechaInput.value = fechaSist;
                        form.appendChild(fechaInput);
                        
                        const horaInput = document.createElement('input');
                        horaInput.type = 'hidden';
                        horaInput.name = 'hora_sist';
                        horaInput.value = horaSist;
                        form.appendChild(horaInput);
                    } else {
                        document.querySelector('input[name="fecha_sist"]').value = fechaSist;
                        document.querySelector('input[name="hora_sist"]').value = horaSist;
                    }
                    
                    // Establecer monto2 con el total a pagar
                    const totalAPagar = parseFloat(document.getElementById('total-a-pagar-display').textContent) || 0;
                    if (!document.querySelector('input[name="monto2"]')) {
                        const montoInput = document.createElement('input');
                        montoInput.type = 'hidden';
                        montoInput.name = 'monto2';
                        montoInput.value = totalAPagar.toFixed(2);
                        form.appendChild(montoInput);
                    } else {
                        document.querySelector('input[name="monto2"]').value = totalAPagar.toFixed(2);
                    }
                    
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();

        // Contador para IDs únicos de ramos
        let ramoCounter = 0;
        let totalAPagar = 0;

        // Función para agregar un nuevo ramo de ingreso
        function addRamoIngreso() {
            ramoCounter++;
            const container = document.getElementById('ramos-ingreso-container');
            
            const ramoDiv = document.createElement('div');
            ramoDiv.className = 'ramo-ingreso-item';
            ramoDiv.id = `ramo-${ramoCounter}`;
            
            ramoDiv.innerHTML = `
                <button type="button" class="remove-ramo-btn" onclick="removeRamoIngreso(${ramoCounter})">
                    <i class="fas fa-times"></i>
                </button>
                <div class="ramo-ingreso-row">
                    <div class="ramo-ingreso-field">
                        <label class="form-label required-field">Ramo de Ingreso</label>
                        <select class="form-select ramo-ingreso-select" name="ramo_ingreso[]" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($tipos_impuestos as $tipo): ?>
                                <option value="<?php echo htmlspecialchars($tipo['cod_concepto']); ?>">
                                    <?php echo htmlspecialchars($tipo['descripcion_concepto']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ramo-ingreso-field">
                        <label class="form-label required-field">Monto</label>
                        <input type="number" class="form-control ramo-monto" name="ramo_monto[]" step="0.01" min="0" required>
                    </div>
                    <div class="ramo-ingreso-field">
                        <label class="form-label required-field">Forma de Impuesto</label>
                        <select class="form-select ramo-forma" name="ramo_forma[]" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($formas_impuestos as $forma): ?>
                                <option value="<?php echo htmlspecialchars($forma['cod_forma']); ?>" <?php echo ($forma['cod_forma'] == 'PG') ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($forma['descripcion']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            `;
            
            container.appendChild(ramoDiv);
            
            // Agregar event listeners para actualizar el total
            const montoInput = ramoDiv.querySelector('.ramo-monto');
            montoInput.addEventListener('input', updateTotalAPagar);
        }
        
        // Función para eliminar un ramo de ingreso
        function removeRamoIngreso(id) {
            const ramoDiv = document.getElementById(`ramo-${id}`);
            if (ramoDiv) {
                // Restar el monto de este ramo al total
                const montoInput = ramoDiv.querySelector('.ramo-monto');
                if (montoInput && montoInput.value) {
                    totalAPagar -= parseFloat(montoInput.value) || 0;
                    updateTotalDisplay();
                }
                
                ramoDiv.remove();
            }
        }
        
        // Función para actualizar el total a pagar
        function updateTotalAPagar() {
            totalAPagar = 0;
            document.querySelectorAll('.ramo-monto').forEach(input => {
                totalAPagar += parseFloat(input.value) || 0;
            });
            
            updateTotalDisplay();
        }
        
        // Función para actualizar el display del total
        function updateTotalDisplay() {
            document.getElementById('total-a-pagar-display').textContent = totalAPagar.toFixed(2);
        }
        
        // Event listeners
        document.getElementById('add-ramo-btn').addEventListener('click', addRamoIngreso);
        
        // Agregar un ramo inicial
        addRamoIngreso();

        // --- JavaScript for Conditional Due Date Calculation (NEW) ---
        const fechaEmisionInput = document.getElementById('fecha_emision');
        const codDeFormaSelect = document.getElementById('cod_de_forma');
        const fechaVencimientoInput = document.getElementById('fecha_vencimiento');

        function calculateFechaVencimiento() {
            const fechaEmision = fechaEmisionInput.value;
            const codDeForma = codDeFormaSelect.value;

            if (fechaEmision) {
                let vencimientoDate = new Date(fechaEmision + 'T00:00:00');

                if (codDeForma === '81') {
                    vencimientoDate.setDate(vencimientoDate.getDate() + 5);
                }
                const year = vencimientoDate.getFullYear();
                const month = String(vencimientoDate.getMonth() + 1).padStart(2, '0');
                const day = String(vencimientoDate.getDate()).padStart(2, '0');
                fechaVencimientoInput.value = `${year}-${month}-${day}`;
            } else {
                fechaVencimientoInput.value = ''; 
            }
        }

        // Agregar Cambios
        fechaEmisionInput.addEventListener('change', calculateFechaVencimiento);
        codDeFormaSelect.addEventListener('change', calculateFechaVencimiento);

        calculateFechaVencimiento();
    </script>
    
    <script src="../../js/autocomplete.js"></script>
    <script src="../../js/cod_forma_cambiar.js"></script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>