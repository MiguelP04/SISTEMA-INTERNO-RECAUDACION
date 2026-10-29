<?php
require_once '../../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener y sanear datos del formulario
    $rif_prefijo = trim($_POST['rif_prefijo'] ?? '');
    $rif_numero = trim($_POST['rif_numero'] ?? '');
    $tipo_entidad = trim($_POST['tipo_entidad'] ?? '');

    // Validar que todos los campos requeridos estén presentes
    if (empty($rif_prefijo) || empty($rif_numero) || empty($tipo_entidad)) {
        header("Location: consulta_ca.php?estado=no_encontrado");
        exit();
    }

    // Construir el RIF completo (prefijo + número sin guión, como está en la BD)
    $rif = $rif_prefijo . $rif_numero;

    if ($tipo_entidad === "Consignatario") {
        // Check if consignatario exists
        $stmt_check = $conn->prepare("SELECT rif_consignatario FROM consignatarios WHERE rif_consignatario = ?");
        $stmt_check->bind_param("s", $rif);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            // Redirect to consignatario consultation page
            header("Location: consulta_consignatario.php?rif=" . urlencode($rif));
            exit();
        } else {
            // RIF not found
            header("Location: consulta_ca.php?estado=no_encontrado");
            exit();
        }
        $stmt_check->close();

    } elseif ($tipo_entidad === "Agente") {
        // Check if agente exists
        $stmt_check = $conn->prepare("SELECT rif_agentes FROM agentes_aduanales WHERE rif_agentes = ?");
        $stmt_check->bind_param("s", $rif);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            // Redirect to agente consultation page
            header("Location: consulta_agente.php?rif=" . urlencode($rif));
            exit();
        } else {
            // RIF not found
            header("Location: consulta_ca.php?estado=no_encontrado");
            exit();
        }
        $stmt_check->close();

    } else {
        // Invalid type selected, redirect back to search
        header("Location: consulta_ca.php?estado=no_encontrado");
        exit();
    }
} else {
    // Not a POST request, redirect to search page
    header("Location: consulta_ca.php");
    exit();
}

// Close connection if it's still open
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}
?>