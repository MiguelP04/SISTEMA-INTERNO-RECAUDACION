<?php
//ob_start();
// Iniciar sesión y conectar a la base de datos
//session_start();
require_once '../../config.php';
//require_once '../../menu.php';

// Inicializar variables para mensajes
$estado = 'error';
$mensaje = '';

// Verificar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener el número de formulario (debe existir siempre)
    $nro_formulario = isset($_POST['nro_formulario']) ? trim($conn->real_escape_string($_POST['nro_formulario'])) : null;

    if ($nro_formulario) {
        // Iniciar transacción
        $conn->begin_transaction();

        try {
            // --- Recoger solo los datos del formulario que se van a actualizar ---
            $codigo_aduana = isset($_POST['codigo_aduana']) ? trim($conn->real_escape_string($_POST['codigo_aduana'])) : '';
            // El valor real de la forma viene de un campo oculto
            $cod_de_forma = isset($_POST['cod_de_forma_real']) ? trim($conn->real_escape_string($_POST['cod_de_forma_real'])) : '';
            $ano_declaracion = isset($_POST['ano_declaracion']) ? intval($_POST['ano_declaracion']) : 0;
            $nro_declaracion = isset($_POST['nro_declaracion']) ? trim($conn->real_escape_string($_POST['nro_declaracion'])) : '';
            $fecha_emision = isset($_POST['fecha_emision']) ? trim($conn->real_escape_string($_POST['fecha_emision'])) : '';
            $fecha_contabilidad_liq = isset($_POST['fecha_contabilidad_liq']) ? trim($conn->real_escape_string($_POST['fecha_contabilidad_liq'])) : '';
            $fecha_de_notificacion = isset($_POST['fecha_de_notificacion']) ? trim($conn->real_escape_string($_POST['fecha_de_notificacion'])) : '';
            $fecha_vencimiento = isset($_POST['fecha_vencimiento']) ? trim($conn->real_escape_string($_POST['fecha_vencimiento'])) : '';
            $nro_resolucion = isset($_POST['nro_resolucion']) ? trim($conn->real_escape_string($_POST['nro_resolucion'])) : '';
            $nro_liquidacion = isset($_POST['nro_liquidacion']) ? trim($conn->real_escape_string($_POST['nro_liquidacion'])) : '';
            $tipo_de_planilla = isset($_POST['tipo_de_planilla']) ? trim($conn->real_escape_string($_POST['tipo_de_planilla'])) : '';

            // Calcular monto2 como suma de todos los ramos
            $monto2 = 0;
            if (isset($_POST['ramo_monto'])) {
            foreach ($_POST['ramo_monto'] as $monto) {
            $monto2 += floatval($monto);
            }
            }

            // Preparar la consulta SQL de actualización
            $sql = "UPDATE planillas_emitidas SET 
                    codigo_aduana = ?,
                    cod_de_forma = ?,
                    ano_declaracion = ?,
                    nro_declaracion = ?,
                    fecha_emision = ?,
                    fecha_contabilidad_liq = ?,
                    fecha_de_notificacion = ?,
                    fecha_vencimiento = ?,
                    nro_resolucion = ?,
                    nro_liquidacion = ?,
                    tipo_de_planilla = ?,
                    monto2 = ?
                    WHERE nro_formulario = ?";

            // Preparar la sentencia
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                throw new Exception("Error al preparar la consulta: " . $conn->error);
            }
            // Contar los parámetros en la consulta SQL
            $paramCount = substr_count($sql, '?');
            
            // Crear el tipo de parámetros y los valores
            $types = '';
            $params = [];
            
            /// Vincular parámetros de forma directa
            $stmt->bind_param("ssissssssssds",
                $codigo_aduana,
                $cod_de_forma,
                $ano_declaracion,
                $nro_declaracion,
                $fecha_emision,
                $fecha_contabilidad_liq,
                $fecha_de_notificacion,
                $fecha_vencimiento,
                $nro_resolucion,
                $nro_liquidacion,
                $tipo_de_planilla,
                $monto2,
                $nro_formulario
            );


            // Ejecutar la sentencia
            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar la actualización: " . $stmt->error);
            }

             // Eliminar ramos existentes
            $delete_ramos = $conn->prepare("DELETE FROM impuestos_a_pagar WHERE nro_formulario = ?");
            $delete_ramos->bind_param("s", $nro_formulario);
            if (!$delete_ramos->execute()) {
                throw new Exception("Error al eliminar ramos existentes: " . $delete_ramos->error);
            }
            $delete_ramos->close();

            // Insertar nuevos ramos si existen
            if (isset($_POST['ramo_ingreso'])) {
                $stmt_ramo = $conn->prepare("INSERT INTO impuestos_a_pagar 
                                            (nro_formulario, cod_concepto, cod_forma, monto) 
                                            VALUES (?, ?, ?, ?)");
                
                if ($stmt_ramo === false) {
                    throw new Exception("Error al preparar la inserción de ramos: " . $conn->error);
                }
                
                foreach ($_POST['ramo_ingreso'] as $i => $cod_concepto) {
                    $monto = floatval($_POST['ramo_monto'][$i]);
                    $cod_forma = $_POST['ramo_forma'][$i];
                    
                    $stmt_ramo->bind_param("sssd", $nro_formulario, $cod_concepto, $cod_forma, $monto);
                    if (!$stmt_ramo->execute()) {
                        throw new Exception("Error al insertar ramo: " . $stmt_ramo->error);
                    }
                }
                $stmt_ramo->close();
            }

            // Confirmar transacción
            $conn->commit();
            
            $estado = 'exito';
            $mensaje = 'Planilla actualizada correctamente.';
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $conn->rollback();
            $mensaje = $e->getMessage();
        }
    } else {
        $mensaje = "No se proporcionó el número de formulario.";
    }
} else {
    $mensaje = "Método de solicitud no válido.";
}

// Cerrar la conexión
$conn->close();

// Redirigir de vuelta al formulario con un mensaje de estado
header("Location: edit_formulario_planilla.php?estado=$mensaje&mensaje" . urlencode($mensaje) . "&search_nro_formulario=" . urlencode($nro_formulario));
exit();
?>