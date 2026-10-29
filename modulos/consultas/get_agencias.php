<?php
// Este archivo devuelve agencias bancarias en formato JSON para ser usado con AJAX
require_once '../../config.php';  // Incluir configuración de base de datos

// Indicar que la respuesta será en formato JSON
header('Content-Type: application/json');

// Verificar si se recibió el parámetro 'cod_banco' por GET
if (isset($_GET['cod_banco'])) {
    $cod_banco = (int)$_GET['cod_banco'];  // Convertir a entero por seguridad
    
    // Preparar consulta SQL para obtener agencias del banco especificado
    $stmt = $conn->prepare("SELECT cod_agencia FROM agencias WHERE cod_banco = ? ORDER BY cod_agencia");
    $stmt->bind_param("i", $cod_banco);  // Vincular parámetro (i = integer)
    $stmt->execute();
    
    // Obtener resultados y guardar en array
    $result = $stmt->get_result();
    $agencias = [];
    while ($row = $result->fetch_assoc()) {
        $agencias[] = $row;
    }
    
    // Devolver resultados en formato JSON
    echo json_encode($agencias);
    exit();
}

// Si no se recibió parámetro, devolver array vacío
echo json_encode([]);
?>