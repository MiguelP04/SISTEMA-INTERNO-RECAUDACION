<?php
require_once '../../config.php';

// Activar visualización de errores (Útil para depuración, desactivar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener y sanear datos del formulario
    $nro_formulario = trim($conn->real_escape_string($_POST['nro_formulario']));
    $cod_org_liq = trim($conn->real_escape_string($_POST['cod_org_liq']));
    $cod_periodo = trim($conn->real_escape_string($_POST['cod_periodo']));
    $cod_dl = trim($conn->real_escape_string($_POST['cod_dl']));
    $rif_consignatario = trim($conn->real_escape_string($_POST['rif_consignatario']));
    $dias = trim($conn->real_escape_string($_POST['dias']));
    $fecha_emision = trim($conn->real_escape_string($_POST['fecha_emision']));
    $fecha_cancelacion = trim($conn->real_escape_string($_POST['fecha_cancelacion']));
    $cod_banco = (int)$_POST['cod_banco'];
    $cod_agencia = (int)$_POST['cod_agencia'];
    $rif_agente = trim($conn->real_escape_string($_POST['rif_agente']));
    $nro_declaracion = trim($conn->real_escape_string($_POST['nro_declaracion']));
    $ano_declaracion = trim($conn->real_escape_string($_POST['ano_declaracion']));
    $bl = trim($conn->real_escape_string($_POST['bl']));
    $fecha_contabilidad_liq = trim($conn->real_escape_string($_POST['fecha_contabilidad_liq']));
    $nro_liquidacion = trim($conn->real_escape_string($_POST['nro_liquidacion']));
    $codigo_aduana = trim($conn->real_escape_string($_POST['codigo_aduana']));
    $fecha_contabilidad_rec = trim($conn->real_escape_string($_POST['fecha_contabilidad_rec']));
    $tipo_de_planilla = trim($conn->real_escape_string($_POST['tipo_de_planilla']));
    $control_de_copias = trim($conn->real_escape_string($_POST['control_de_copias']));
    $monto2 = $_POST['monto2'] !== '' ? (float)$_POST['monto2'] : null;
    $fecha_de_notificacion = trim($conn->real_escape_string($_POST['fecha_de_notificacion']));
    $cod_de_forma = isset($_POST['cod_de_forma_real']) ? trim($conn->real_escape_string($_POST['cod_de_forma_real'])) : '';
    $fecha_de_entreguese = trim($conn->real_escape_string($_POST['fecha_de_entreguese']));
    $anulada = isset($_POST['anulada']) ? 1 : 0;
    $fecha_anulacion = trim($conn->real_escape_string($_POST['fecha_anulacion']));
    $fecha_recalmac = trim($conn->real_escape_string($_POST['fecha_recalmac']));
    $valor_cif = (float)$_POST['valor_cif'];
    $peso_disp = (float)$_POST['peso_disp'];
    $cantidad_dispon = trim($conn->real_escape_string($_POST['cantidad_dispon']));
    $fecha_sist = date('Y-m-d');
    $hora_sist = date('H:i:s');
    $cod_status = trim($conn->real_escape_string($_POST['cod_status']));
    $cod_nuevo = trim($conn->real_escape_string($_POST['cod_nuevo']));
    $fecha_providencia = trim($conn->real_escape_string($_POST['fecha_providencia']));
    $valor_fob = (float)$_POST['valor_fob'];
    $nro_resolucion = trim($conn->real_escape_string($_POST['nro_resolucion']));
    $tipo_forma = trim($conn->real_escape_string($_POST['tipo_forma']));
    $fecha_vencimiento = trim($conn->real_escape_string($_POST['fecha_vencimiento']));

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Insertar planilla principal
        $sql = "INSERT INTO planillas_emitidas (
                    nro_formulario, cod_org_liq, cod_periodo, cod_dl, rif_consignatario,
                    dias, fecha_emision, fecha_cancelacion, cod_banco, cod_agencia,
                    rif_agente, nro_declaracion, ano_declaracion, bl, fecha_contabilidad_liq,
                    nro_liquidacion, codigo_aduana, fecha_contabilidad_rec, tipo_de_planilla, control_de_copias,
                    monto2, fecha_de_notificacion, cod_de_forma, fecha_de_entreguese, anulada,
                    fecha_anulacion, fecha_recalmac, valor_cif, peso_disp, cantidad_dispon,
                    fecha_sist, hora_sist, cod_status, cod_nuevo, fecha_providencia,
                    valor_fob, nro_resolucion, tipo_forma, fecha_vencimiento
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Error al preparar la sentencia: " . $conn->error);
        }

        $stmt->bind_param(
            "ssssssssiissssssssssdsiiidddssssssdssss",
            $nro_formulario, $cod_org_liq, $cod_periodo, $cod_dl, $rif_consignatario,
            $dias, $fecha_emision, $fecha_cancelacion, $cod_banco, $cod_agencia,
            $rif_agente, $nro_declaracion, $ano_declaracion, $bl, $fecha_contabilidad_liq,
            $nro_liquidacion, $codigo_aduana, $fecha_contabilidad_rec, $tipo_de_planilla, $control_de_copias,
            $monto2, $fecha_de_notificacion, $cod_de_forma, $fecha_de_entreguese, $anulada,
            $fecha_anulacion, $fecha_recalmac, $valor_cif, $peso_disp, $cantidad_dispon,
            $fecha_sist, $hora_sist, $cod_status, $cod_nuevo, $fecha_providencia,
            $valor_fob, $nro_resolucion, $tipo_forma, $fecha_vencimiento
        );

        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la inserción: " . $stmt->error);
        }

        // Procesar ramos de ingreso
        if (isset($_POST['ramo_ingreso']) && is_array($_POST['ramo_ingreso'])) {
            $ramos = $_POST['ramo_ingreso'];
            $montos = $_POST['ramo_monto'];
            $formas = $_POST['ramo_forma'];
            
            $stmt_ramo = $conn->prepare("INSERT INTO impuestos_a_pagar
                                        (nro_formulario, cod_concepto, cod_forma, monto) 
                                        VALUES (?, ?, ?, ?)");
            
            if ($stmt_ramo === false) {
                throw new Exception("Error al preparar la sentencia de ramos: " . $conn->error);
            }
            
            for ($i = 0; $i < count($ramos); $i++) {
                $cod_concepto = trim($conn->real_escape_string($ramos[$i]));
                $monto = floatval($montos[$i]);
                $cod_forma = trim($conn->real_escape_string($formas[$i]));
                
                $stmt_ramo->bind_param("sssd", $nro_formulario, $cod_concepto, $cod_forma, $monto);
                if (!$stmt_ramo->execute()) {
                    throw new Exception("Error al insertar ramo: " . $stmt_ramo->error);
                }
            }
            $stmt_ramo->close();
        }

        // Confirmar transacción
        $conn->commit();
        
        header("Location: formulario_planilla.php?estado=exito");
        exit();
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        header("Location: formulario_planilla.php?estado=error&mensaje=" . urlencode($e->getMessage()));
        exit();
    }
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>