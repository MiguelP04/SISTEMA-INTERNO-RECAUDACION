<?php
session_start();
require_once '../../config.php';
require_once '../../menu.php';

$mensaje_estado = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener y sanear datos del formulario
    $rif = trim($conn->real_escape_string($_POST['text_rifAgent']));
    $nombreAgent = trim($conn->real_escape_string($_POST['text_nombreAgent']));
    $representante = trim($conn->real_escape_string($_POST['text_representAgent']));
    $siglas = trim($conn->real_escape_string($_POST['text_siglasAgent']));
    $telefono = trim($conn->real_escape_string($_POST['text_numeroAgent']));
    $domicilio = trim($conn->real_escape_string($_POST['text_domicilioAgent']));
    $numeroRegistro = isset($_POST['text_numeroRegistroAgent']) ? trim($conn->real_escape_string($_POST['text_numeroRegistroAgent'])) : ''; // Assuming this field exists

    // Verificar si el RIF ya existe
    $stmt_check = $conn->prepare("SELECT rif_agentes FROM agentes_aduanales WHERE LOWER(rif_agentes) = LOWER(?)");
    $stmt_check->bind_param("s", $rif);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $mensaje_estado = "Error: Ya existe un agente con este RIF.";
    } else {
        // Insertar nuevo agente
        $stmt_insert = $conn->prepare("INSERT INTO agentes_aduanales (rif_agentes, nombre_agente, siglas_agente, representante, telefono, domicilio_fiscal, numero_registro_agente_aduanal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("sssssss", $rif, $nombreAgent, $siglas, $representante, $telefono, $domicilio, $numeroRegistro);

        if ($stmt_insert->execute()) {
            $mensaje_estado = "Agente registrado con éxito.";
        } else {
            $mensaje_estado = "Error al registrar agente: " . $stmt_insert->error;
        }
        $stmt_insert->close();
    }
    $stmt_check->close();
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
        .required-field::after {
            content: " *";
            color: red;
        }
        .rif-type-select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            padding-right: 25px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-chevron-down' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0"><i class="fas fa-user-shield me-2"></i>Registro de Agente Aduanal</h3>
            </div>
            
            <div class="card-body">
                <?php if ($mensaje_estado): ?>
                    <div class="alert alert-<?php echo (strpos($mensaje_estado, 'éxito') !== false ? 'success' : 'danger');?>">
                        <?php if (strpos($mensaje_estado, 'éxito') !== false): ?>
                            <i class="fas fa-check-circle me-2"></i><?php echo $mensaje_estado; ?>
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $mensaje_estado; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form id="formularioRegistroAgente" method="post" class="needs-validation" novalidate>
                    <div class="form-section">
                        <h5><i class="fas fa-info-circle me-2"></i>Datos del Agente Aduanal</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="text_rifAgent" class="form-label required-field">RIF</label>
                                <input type="text" class="form-control" id="text_rifAgent" name="text_rifAgent" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="text_nombreAgent" class="form-label required-field">Nombre</label>
                                <input type="text" class="form-control" id="text_nombreAgent" name="text_nombreAgent" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="text_representAgent" class="form-label required-field">Representante</label>
                                <input type="text" class="form-control" id="text_representAgent" name="text_representAgent" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="text_siglasAgent" class="form-label">Siglas</label>
                                <input type="text" class="form-control" id="text_siglasAgent" name="text_siglasAgent">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="text_numeroAgent" class="form-label required-field">Teléfono</label>
                                <input type="tel" class="form-control" id="text_numeroAgent" name="text_numeroAgent" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="text_domicilioAgent" class="form-label required-field">Domicilio Fiscal</label>
                                <input type="text" class="form-control" id="text_domicilioAgent" name="text_domicilioAgent" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="text_numeroRegistroAgent" class="form-label">Número de Registro</label>
                                <input type="text" class="form-control" id="text_numeroRegistroAgent" name="text_numeroRegistroAgent">
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Registrar Agente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Validación de formulario -->
    <script>
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
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
// Cerrar la conexión
$conn->close();
?>