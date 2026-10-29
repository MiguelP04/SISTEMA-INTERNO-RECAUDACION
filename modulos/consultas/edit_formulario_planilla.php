<?php
session_start();
require_once '../../config.php';
require_once '../../menu.php';

// --- Inicializar variables ---
$nro_formulario_data = '';
$rif_consignatario_data = '';
$nombre_consignatario_data = 'No encontrado';
$fecha_emision_data = '';
$rif_agente_data = '';
$nombre_agente_data = 'No encontrado';
$nro_declaracion_data = '';
$ano_declaracion_data = date('Y');
$nro_liquidacion_data = '';
$codigo_aduana_data = '';
$tipo_de_planilla_data = '';
$monto_total_planilla_data = '0.00';
$fecha_de_notificacion_data = '';
$cod_de_forma_data = '';
$nro_resolucion_data = '';
$fecha_vencimiento_data = '';
$fecha_contabilidad_liq_data = '';
$fecha_cancelacion_data = '';

$ramos_ingreso_data = [];
$search_nro_formulario = '';
$search_message = '';
$planilla_encontrada = false;
$edit_mode = false;
$tipo_mensaje = "danger";

// --- Cargar datos para los SELECT ---
$aduanas = [];
$result_aduanas = $conn->query("SELECT codigo_aduana, nombre_de_la_aduana FROM aduanas ORDER BY nombre_de_la_aduana");
if ($result_aduanas) {
    while ($row = $result_aduanas->fetch_assoc()) {
        $aduanas[] = $row;
    }
}

$tipos_liquidacion = [];
$result_tipos_liq = $conn->query("SELECT tipo_de_liquidacion, descripcion FROM tipo_de_liquidacion ORDER BY descripcion");
if ($result_tipos_liq) {
    while ($row = $result_tipos_liq->fetch_assoc()) {
        $tipos_liquidacion[] = $row;
    }
}

$formas_impuestos = [];
$result_formas_imp = $conn->query("SELECT cod_forma, descripcion FROM formas_de_impuestos ORDER BY cod_forma");
if ($result_formas_imp) {
    while ($row = $result_formas_imp->fetch_assoc()) {
        $formas_impuestos[] = $row;
    }
}

$tipos_impuestos = [];
$result_tipos_imp = $conn->query("SELECT cod_concepto, descripcion_concepto FROM tipos_de_impuestos ORDER BY orden");
if ($result_tipos_imp) {
    while ($row = $result_tipos_imp->fetch_assoc()) {
        $tipos_impuestos[] = $row;
    }
}

// Procesar GET para modo edición y búsqueda
if (isset($_GET['nro_formulario'])) {
    $search_nro_formulario = trim($conn->real_escape_string($_GET['nro_formulario']));
    if (isset($_GET['edit']) && $_GET['edit'] == 'true') {
        $edit_mode = true;
    }
}

// --- Procesar acciones ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'update' && isset($_POST['nro_formulario'])) {
        // Procesar actualización
        $nro_formulario = trim($conn->real_escape_string($_POST['nro_formulario']));
        
        $codigo_aduana = trim($conn->real_escape_string($_POST['codigo_aduana']));
        $cod_de_forma = trim($conn->real_escape_string($_POST['cod_de_forma']));
        $ano_declaracion = trim($conn->real_escape_string($_POST['ano_declaracion']));
        $nro_declaracion = trim($conn->real_escape_string($_POST['nro_declaracion']));
        $fecha_emision = trim($conn->real_escape_string($_POST['fecha_emision']));
        $fecha_contabilidad_liq = trim($conn->real_escape_string($_POST['fecha_contabilidad_liq']));
        $fecha_de_notificacion = trim($conn->real_escape_string($_POST['fecha_de_notificacion']));
        $fecha_vencimiento = trim($conn->real_escape_string($_POST['fecha_vencimiento']));
        $nro_resolucion = trim($conn->real_escape_string($_POST['nro_resolucion']));
        $nro_liquidacion = trim($conn->real_escape_string($_POST['nro_liquidacion']));
        $tipo_de_planilla = trim($conn->real_escape_string($_POST['tipo_de_planilla']));
        $rif_consignatario = trim($conn->real_escape_string($_POST['rif_consignatario']));
        $rif_agente = trim($conn->real_escape_string($_POST['rif_agente']));
        
        $conn->begin_transaction();
        try {
            // Actualizar planilla principal
            $stmt_update = $conn->prepare("UPDATE planillas_emitidas SET 
                codigo_aduana = ?, cod_de_forma = ?, ano_declaracion = ?, nro_declaracion = ?, 
                fecha_emision = ?, fecha_contabilidad_liq = ?, fecha_de_notificacion = ?, 
                fecha_vencimiento = ?, nro_resolucion = ?, nro_liquidacion = ?, 
                tipo_de_planilla = ?, rif_consignatario = ?, rif_agente = ? 
                WHERE nro_formulario = ?");
                
            $stmt_update->bind_param("ssisssssssssss", 
                $codigo_aduana, $cod_de_forma, $ano_declaracion, $nro_declaracion,
                $fecha_emision, $fecha_contabilidad_liq, $fecha_de_notificacion,
                $fecha_vencimiento, $nro_resolucion, $nro_liquidacion,
                $tipo_de_planilla, $rif_consignatario, $rif_agente, $nro_formulario);
                
            if (!$stmt_update->execute()) {
                throw new Exception("Error al actualizar la planilla: " . $stmt_update->error);
            }
            $stmt_update->close();
            
            // Eliminar ramos existentes
            $stmt_delete_ramos = $conn->prepare("DELETE FROM impuestos_a_pagar WHERE nro_formulario = ?");
            $stmt_delete_ramos->bind_param("s", $nro_formulario);
            if (!$stmt_delete_ramos->execute()) {
                throw new Exception("Error al eliminar ramos existentes: " . $stmt_delete_ramos->error);
            }
            $stmt_delete_ramos->close();
            
            // Insertar nuevos ramos
            if (isset($_POST['ramo_ingreso'])) {
                $stmt_insert_ramo = $conn->prepare("INSERT INTO impuestos_a_pagar
                    (nro_formulario, cod_concepto, cod_forma, monto) 
                    VALUES (?, ?, ?, ?)");
                    
                foreach ($_POST['ramo_ingreso'] as $index => $cod_concepto) {
                    $monto = $_POST['ramo_monto'][$index];
                    $cod_forma = $_POST['ramo_forma'][$index];
                    
                    $stmt_insert_ramo->bind_param("sssd", $nro_formulario, $cod_concepto, $monto, $cod_forma);
                    if (!$stmt_insert_ramo->execute()) {
                        throw new Exception("Error al insertar ramo: " . $stmt_insert_ramo->error);
                    }
                }
                $stmt_insert_ramo->close();
            }
            
            $conn->commit();
            $search_message = "Planilla actualizada con éxito.";
            $tipo_mensaje = "success";
            $edit_mode = false;
            
        } catch (Exception $e) {
            $conn->rollback();
            $search_message = $e->getMessage();
            $tipo_mensaje = "danger";
            $edit_mode = true;
        }
        
        $search_nro_formulario = $nro_formulario;
        
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['nro_formulario'])) {
        // Procesar eliminación
        $nro_formulario = trim($conn->real_escape_string($_POST['nro_formulario']));
        
        $conn->begin_transaction();
        try {
            // Eliminar ramos de ingreso primero
            $stmt_delete_ramos = $conn->prepare("DELETE FROM impuestos_a_pagar WHERE nro_formulario = ?");
            $stmt_delete_ramos->bind_param("s", $nro_formulario);
            $stmt_delete_ramos->execute();
            $stmt_delete_ramos->close();
            
            // Eliminar planilla
            $stmt_delete = $conn->prepare("DELETE FROM planillas_emitidas WHERE nro_formulario = ?");
            $stmt_delete->bind_param("s", $nro_formulario);
            
            if ($stmt_delete->execute()) {
                $conn->commit();
                $search_message = "Planilla eliminada correctamente.";
                $tipo_mensaje = "success";
                $planilla_encontrada = false;
            } else {
                throw new Exception("Error al eliminar la planilla: " . $stmt_delete->error);
            }
            $stmt_delete->close();
        } catch (Exception $e) {
            $conn->rollback();
            $search_message = $e->getMessage();
            $tipo_mensaje = "danger";
        }
    } elseif (isset($_POST['search_nro_formulario'])) {
        // Procesar búsqueda
        $search_nro_formulario = trim($conn->real_escape_string($_POST['search_nro_formulario']));
    }
}

// Cargar datos si hay un número de formulario
if (!empty($search_nro_formulario)) {
    $stmt = $conn->prepare("SELECT nro_formulario, rif_consignatario, fecha_emision, rif_agente, nro_declaracion, ano_declaracion, nro_liquidacion, codigo_aduana, tipo_de_planilla, monto2, fecha_de_notificacion, cod_de_forma, nro_resolucion, fecha_vencimiento, fecha_contabilidad_liq, fecha_cancelacion FROM planillas_emitidas WHERE nro_formulario = ?");
    $stmt->bind_param("s", $search_nro_formulario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $planilla = $result->fetch_assoc();
        $planilla_encontrada = true;

        // Asignar datos a las variables
        $nro_formulario_data = $planilla['nro_formulario'];
        $rif_consignatario_data = $planilla['rif_consignatario'];
        $fecha_emision_data = $planilla['fecha_emision'];
        $rif_agente_data = $planilla['rif_agente'];
        $nro_declaracion_data = $planilla['nro_declaracion'];
        $ano_declaracion_data = $planilla['ano_declaracion'];
        $nro_liquidacion_data = $planilla['nro_liquidacion'];
        $codigo_aduana_data = $planilla['codigo_aduana'];
        $tipo_de_planilla_data = $planilla['tipo_de_planilla'];
        $monto_total_planilla_data = $planilla['monto2'];
        $fecha_de_notificacion_data = $planilla['fecha_de_notificacion'];
        $cod_de_forma_data = $planilla['cod_de_forma'];
        $nro_resolucion_data = $planilla['nro_resolucion'];
        $fecha_vencimiento_data = $planilla['fecha_vencimiento'];
        $fecha_contabilidad_liq_data = $planilla['fecha_contabilidad_liq'];
        $fecha_cancelacion_data = $planilla['fecha_cancelacion'];

        // Verificar si ya tiene recaudación registrada
        $recaudada = !empty($planilla['fecha_cancelacion']) && $planilla['fecha_cancelacion'] != '0000-00-00';

        // Buscar nombre del consignatario
        if (!empty($rif_consignatario_data)) {
            $stmt_consignatario = $conn->prepare("SELECT nombre_consignatario FROM consignatarios WHERE rif_consignatario = ?");
            if ($stmt_consignatario) {
                $stmt_consignatario->bind_param("s", $rif_consignatario_data);
                $stmt_consignatario->execute();
                $result_consignatario = $stmt_consignatario->get_result();
                if ($result_consignatario->num_rows > 0) {
                    $nombre_consignatario_data = $result_consignatario->fetch_assoc()['nombre_consignatario'];
                }
                $stmt_consignatario->close();
            }
        }

        // Buscar nombre del agente
        if (!empty($rif_agente_data)) {
            $stmt_agente = $conn->prepare("SELECT nombre_agente FROM agentes_aduanales WHERE rif_agentes = ?");
            if ($stmt_agente) {
                $stmt_agente->bind_param("s", $rif_agente_data);
                $stmt_agente->execute();
                $result_agente = $stmt_agente->get_result();
                if ($result_agente->num_rows > 0) {
                    $nombre_agente_data = $result_agente->fetch_assoc()['nombre_agente'];
                }
                $stmt_agente->close();
            }
        }

        // Buscar ramos de ingreso asociados
        $stmt_ramos = $conn->prepare("SELECT ri.*, ti.descripcion_concepto, fi.descripcion as forma_desc 
                                     FROM impuestos_a_pagar ri
                                     JOIN tipos_de_impuestos ti ON ri.cod_concepto = ti.cod_concepto
                                     JOIN formas_de_impuestos fi ON ri.cod_forma = fi.cod_forma
                                     WHERE ri.nro_formulario = ?");
        $stmt_ramos->bind_param("s", $search_nro_formulario);
        $stmt_ramos->execute();
        $result_ramos = $stmt_ramos->get_result();
        
        while ($ramo = $result_ramos->fetch_assoc()) {
            $ramos_ingreso_data[] = $ramo;
        }
        $stmt_ramos->close();
    } else {
        if (empty($search_message)) {
            $search_message = "No se encontró ninguna planilla con el N° Formulario: " . htmlspecialchars($search_nro_formulario);
            $tipo_mensaje = "warning";
        }
    }
    $stmt->close();
}
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
        body { background-color: #f0f0f0; }
        .container { max-width: 1200px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .search-container-box { border: 1px solid #ccc; padding: 20px; border-radius: 8px; margin-bottom: 25px; background-color: #f9f9f9; }
        .search-form-styled { display: flex; flex-direction: column; gap: 15px; align-items: center; }
        .form-group-inline { display: flex; align-items: center; gap: 10px; width: 100%; max-width: 500px; }
        .search-label { font-weight: bold; flex-shrink: 0; color: #555; }
        .inputConsult__input { flex-grow: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 1rem; }
        .inputConsult__input:focus { outline: none; border-color: #007bff; box-shadow: 0 0 5px rgba(0,123,255,0.25); }
        .consult__btn { padding: 8px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .consult__btn:hover { background-color: #0056b3; }
        .parallel-sections { display: flex; flex-wrap: wrap; gap: 20px; }
        .parallel-section { flex: 1; min-width: 300px; }
        .form-section { background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px; border-left: 4px solid #0d6efd; }
        .form-section h5 { color: #0d6efd; margin-bottom: 20px; }
        .rif-input-group { display: flex; align-items: center; }
        .rif-input-group select { width: 80px; margin-right: 10px; }
        .required-field::after { content: " *"; color: red; }
        .numeric-input { text-align: right; }
        .impuestos-header { display: flex; align-items: center; gap: 20px; margin-bottom: 20px; }
        .total-a-pagar-container { display: flex; align-items: center; background-color: #f8f9fa; padding: 10px 15px; border-radius: 5px; border: 1px solid #dee2e6; }
        .total-a-pagar-label { font-weight: bold; margin-right: 10px; }
        .total-a-pagar-value { font-size: 1.2rem; color: #0d6efd; }
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
        .form-control[readonly] {
            background-color: #e9ecef;
            opacity: 1;
            cursor: default;
        }
        .form-select[disabled] {
            background-color: #e9ecef;
            opacity: 1;
            cursor: default;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="search-container-box">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="search-form-styled">
                <div class="form-group-inline">
                    <label for="search_nro_formulario" class="search-label">N° Formulario a Consultar:</label>
                    <input type="text" id="search_nro_formulario" name="search_nro_formulario" class="form-control inputConsult__input" value="<?php echo htmlspecialchars($search_nro_formulario); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary consult__btn"><i class="fas fa-search me-2"></i>Consultar</button>
            </form>
            <?php if (!empty($search_message)) { echo $search_message; } ?>
        </div>

        <?php if ($planilla_encontrada): ?>
        <div class="card shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h3 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Planilla Aduanal</h3>
                <a href="consulta_planillas.php" class="btn btn-light btn-sm"><i class="fas fa-search me-1"></i>Nueva Consulta</a>
            </div>
            
            <div class="card-body">
                <form action="actualizar_planilla.php" method="post" class="needs-validation" novalidate>
                    <input type="hidden" name="nro_formulario" value="<?php echo htmlspecialchars($nro_formulario_data); ?>">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="action" value="update">
                    <?php endif; ?>
                    
                    <div class="form-section">
                        <h5><i class="fas fa-info-circle me-2"></i>Datos Básicos</h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="nro_formulario" class="form-label">N° Formulario</label>
                                <input type="text" class="form-control" id="nro_formulario" value="<?php echo htmlspecialchars($nro_formulario_data); ?>" readonly>
                            </div>
                            <div class="col-md-3">
                                <label for="codigo_aduana" class="form-label">Aduana</label>
                                <select class="form-select" id="codigo_aduana" name="codigo_aduana" <?php echo $edit_mode ? '' : 'disabled'; ?> required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($aduanas as $aduana): ?>
                                        <option value="<?php echo htmlspecialchars($aduana['codigo_aduana']); ?>" <?php echo ($aduana['codigo_aduana'] == $codigo_aduana_data) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($aduana['codigo_aduana'] . ' - ' . $aduana['nombre_de_la_aduana']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="cod_de_forma" class="form-label">Forma</label>
                                <select class="form-select cod-forma-select" id="cod_de_forma" name="cod_de_forma" 
                                data-mapping="display" <?php echo $edit_mode ? '' : 'disabled'; ?> required>
                                <option value="">Seleccionar...</option>
                                <option value="19" <?php echo ('19' == $cod_de_forma_data) ? 'selected' : ''; ?>>80</option>
                                <option value="20" <?php echo ('20' == $cod_de_forma_data) ? 'selected' : ''; ?>>81</option>
                                <option value="21" <?php echo ('21' == $cod_de_forma_data) ? 'selected' : ''; ?>>86</option>
                                </select>
                                <input type="hidden" id="cod_de_forma_real" name="cod_de_forma_real" value="<?php echo htmlspecialchars($cod_de_forma_data); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="ano_declaracion" class="form-label">Año</label>
                                <input type="number" class="form-control" id="ano_declaracion" name="ano_declaracion" value="<?php echo htmlspecialchars($ano_declaracion_data); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="nro_declaracion" class="form-label">N° Declaración</label>
                                <input type="text" class="form-control" id="nro_declaracion" name="nro_declaracion" value="<?php echo htmlspecialchars($nro_declaracion_data); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_emision" class="form-label">Fecha Emisión</label>
                                <input type="date" class="form-control" id="fecha_emision" name="fecha_emision" value="<?php echo htmlspecialchars($fecha_emision_data); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_contabilidad_liq" class="form-label">Fecha Liquidación</label>
                                <input type="date" class="form-control date-auto-fill" id="fecha_contabilidad_liq" name="fecha_contabilidad_liq" value="<?php echo htmlspecialchars($fecha_contabilidad_liq_data); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_de_notificacion" class="form-label">Fecha Notificación</label>
                                <input type="date" class="form-control date-auto-fill" id="fecha_de_notificacion" name="fecha_de_notificacion" value="<?php echo htmlspecialchars($fecha_de_notificacion_data); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_vencimiento" class="form-label">Fecha Vencimiento</label>
                                <input type="date" class="form-control date-auto-fill" id="fecha_vencimiento" name="fecha_vencimiento" value="<?php echo htmlspecialchars($fecha_vencimiento_data); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="nro_resolucion" class="form-label">N° Resolución</label>
                                <input type="text" class="form-control" id="nro_resolucion" name="nro_resolucion" value="<?php echo htmlspecialchars($nro_resolucion_data); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?>>
                            </div>
                            <div class="col-md-4">
                                <label for="nro_liquidacion" class="form-label">N° Liquidación</label>
                                <input type="text" class="form-control" id="nro_liquidacion" name="nro_liquidacion" value="<?php echo htmlspecialchars($nro_liquidacion_data); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                            </div>
                            <div class="col-md-4">
                                <label for="tipo_de_planilla" class="form-label">Tipo de Planilla</label>
                                <select class="form-select" id="tipo_de_planilla" name="tipo_de_planilla" <?php echo $edit_mode ? '' : 'disabled'; ?> required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($tipos_liquidacion as $tipo): ?>
                                        <option value="<?php echo htmlspecialchars($tipo['tipo_de_liquidacion']); ?>" <?php echo ($tipo['tipo_de_liquidacion'] == $tipo_de_planilla_data) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tipo['tipo_de_liquidacion'] . ' - ' . $tipo['descripcion']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="parallel-sections">
                        <div class="parallel-section form-section">
                            <h5><i class="fas fa-user-tie me-2"></i>Datos del Consignatario</h5>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">RIF Consignatario</label>
                                    <input type="text" class="form-control" id="rif_consignatario_display" name="rif_consignatario" value="<?php echo htmlspecialchars($rif_consignatario_data); ?>" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Nombre Consignatario</label>
                                    <input type="text" class="form-control" id="nombre_consignatario_display" value="<?php echo htmlspecialchars($nombre_consignatario_data); ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="parallel-section form-section">
                            <h5><i class="fas fa-user-shield me-2"></i>Datos del Agente Aduanal</h5>
                            <div class="row g-3">
                            <div class="col-12">
                                    <label class="form-label">RIF Agente</label>
                                    <input type="text" class="form-control" id="rif_agente_display" name="rif_agente" value="<?php echo htmlspecialchars($rif_agente_data); ?>" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Nombre Agente</label>
                                    <input type="text" class="form-control" id="nombre_agente_display" value="<?php echo htmlspecialchars($nombre_agente_data); ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="impuestos-header">
                            <h5><i class="fas fa-calculator me-2"></i>Impuestos</h5>
                            <div class="total-a-pagar-container">
                                <span class="total-a-pagar-label">Total a Pagar:</span>
                                <span class="total-a-pagar-value">Bs. <span id="total-a-pagar-display"><?php echo htmlspecialchars(number_format((float)$monto_total_planilla_data, 2, '.', ',')); ?></span></span>
                            </div>
                        </div>
                        
                        <div id="ramos-ingreso-container">
                            <?php if (!empty($ramos_ingreso_data)): ?>
                                <?php foreach ($ramos_ingreso_data as $index => $ramo): ?>
                                    <div class="ramo-ingreso-item" id="ramo-<?php echo $index; ?>">
                                        <?php if ($edit_mode): ?>
                                            <button type="button" class="remove-ramo-btn" onclick="removeRamoIngreso(<?php echo $index; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                        <div class="ramo-ingreso-row">
                                            <div class="ramo-ingreso-field">
                                                <label class="form-label required-field">Ramo de Ingreso</label>
                                                <select class="form-select ramo-ingreso-select" name="ramo_ingreso[]" <?php echo $edit_mode ? '' : 'disabled'; ?> required>
                                                    <option value="">Seleccionar...</option>
                                                    <?php foreach ($tipos_impuestos as $tipo): ?>
                                                        <option value="<?php echo htmlspecialchars($tipo['cod_concepto']); ?>" <?php echo ($tipo['cod_concepto'] == $ramo['cod_concepto']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($tipo['descripcion_concepto']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="ramo-ingreso-field">
                                                <label class="form-label required-field">Monto</label>
                                                <input type="number" class="form-control ramo-monto" name="ramo_monto[]" value="<?php echo htmlspecialchars($ramo['monto']); ?>" step="0.01" min="0" <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                                            </div>
                                            <div class="ramo-ingreso-field">
                                                <label class="form-label required-field">Forma de Impuesto</label>
                                                <select class="form-select ramo-forma" name="ramo_forma[]" <?php echo $edit_mode ? '' : 'disabled'; ?> required>
                                                    <option value="">Seleccionar...</option>
                                                    <?php foreach ($formas_impuestos as $forma): ?>
                                                        <option value="<?php echo htmlspecialchars($forma['cod_forma']); ?>" <?php echo ($forma['cod_forma'] == $ramo['cod_forma']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($forma['descripcion']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted"><em>No se encontraron ramos de ingreso para esta planilla.</em></p>
                            <?php endif; ?>
                        </div>
                            
                            <?php if ($edit_mode): ?>
                                <button type="button" class="btn btn-outline-primary mt-3" id="add-ramo-btn">
                                    <i class="fas fa-plus me-2"></i>Agregar Ramo de Ingreso
                                </button>
                            <?php endif; ?>
                        </div>
<div class="mt-4 d-flex justify-content-end">
    <?php if (empty($fecha_cancelacion_data) || $fecha_cancelacion_data == '0000-00-00'): ?>
        <a href="recaudar_planilla.php?nro_formulario=<?= urlencode($nro_formulario_data) ?>" class="btn btn-success btn-lg me-2">
            <i class="fas fa-money-bill-wave me-2"></i>Recaudar
        </a>
    <?php else: ?>
        <a href="editar_recaudacion.php?nro_formulario=<?= urlencode($nro_formulario_data) ?>" class="btn btn-info btn-lg me-2">
            <i class="fas fa-edit me-2"></i>Editar Recaudación
        </a>
    <?php endif; ?>
    
    <?php if ($es_admin): ?>
    <?php if (!$edit_mode): ?>
        <a href="?nro_formulario=<?php echo urlencode($nro_formulario_data); ?>&edit=true" class="btn btn-warning btn-lg me-2">
            <i class="fas fa-edit me-2"></i>Editar Planilla
        </a>
        <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal">
            <i class="fas fa-trash-alt me-2"></i>Eliminar Planilla
        </button>
    <?php else: ?>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save me-2"></i>Guardar Cambios
        </button>
        <a href="?nro_formulario=<?php echo urlencode($nro_formulario_data); ?>" class="btn btn-secondary btn-lg ms-2">
            <i class="fas fa-undo me-2"></i>Cancelar Edición
        </a>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

    <!-- Modal de Confirmación de Eliminación -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar la planilla con N° Formulario: <strong><?php echo htmlspecialchars($nro_formulario_data); ?></strong>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="nro_formulario" value="<?php echo htmlspecialchars($nro_formulario_data); ?>">
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    
    <script src="http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="cod_forma_cambiar.js"></script>
    <script>
        // Contador para IDs únicos de ramos
        let ramoCounter = <?php echo count($ramos_ingreso_data); ?>;
        let totalAPagar = <?php echo $monto_total_planilla_data; ?>;

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
                                <option value="<?php echo htmlspecialchars($forma['cod_forma']); ?>">
                                    <?php echo htmlspecialchars($forma['descripcion']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            `;
            
            container.appendChild(ramoDiv);
            
            // Si era el mensaje de "no hay ramos", lo eliminamos
            const noRamosMsg = container.querySelector('p.text-muted');
            if (noRamosMsg) {
                noRamosMsg.remove();
            }
            
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
                
                // Si no quedan ramos, mostramos el mensaje
                const container = document.getElementById('ramos-ingreso-container');
                if (container.children.length === 0) {
                    container.innerHTML = '<p class="text-muted"><em>No se encontraron ramos de ingreso para esta planilla.</em></p>';
                }
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
        document.getElementById('add-ramo-btn')?.addEventListener('click', addRamoIngreso);
        
        // Configurar event listeners para los montos existentes
        document.querySelectorAll('.ramo-monto').forEach(input => {
            input.addEventListener('input', updateTotalAPagar);
        });
        
        // Auto-rellenar fechas relacionadas
        document.getElementById('fecha_emision')?.addEventListener('change', function() {
            const fechaEmision = this.value;
            if (fechaEmision) {
                document.getElementById('fecha_contabilidad_liq').value = fechaEmision;
                document.getElementById('fecha_de_notificacion').value = fechaEmision;
                document.getElementById('fecha_vencimiento').value = fechaEmision;
            }
        });

        // Validación de formulario
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>
<?php
$conn->close();
?>