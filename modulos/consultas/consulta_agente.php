<?php
session_start();
require_once '../../config.php';
require_once '../../menu.php';

$agente_data = null;
$mensaje_estado = "";
$tipo_mensaje = "danger";
$rif_buscado = "";
$edit_mode = false; // Flag para controlar modo edición

// Procesar actualización POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'update' && isset($_POST['original_rif'])) {
        $original_rif = trim($conn->real_escape_string($_POST['original_rif']));
        $nombre_agente = trim($conn->real_escape_string($_POST['nombre_agente']));
        $representante = trim($conn->real_escape_string($_POST['representante']));
        $siglas = trim($conn->real_escape_string($_POST['siglas']));
        $telefono = trim($conn->real_escape_string($_POST['telefono']));
        $domicilio = trim($conn->real_escape_string($_POST['domicilio']));
        $numero_registro = trim($conn->real_escape_string($_POST['numero_registro']));

        $stmt_update = $conn->prepare("UPDATE agentes_aduanales SET nombre_agente = ?, representante = ?, siglas_agente = ?, telefono = ?, domicilio_fiscal = ?, numero_registro_agente_aduanal = ? WHERE rif_agentes = ?");
        $stmt_update->bind_param("sssssss", $nombre_agente, $representante, $siglas, $telefono, $domicilio, $numero_registro, $original_rif);

        if ($stmt_update->execute()) {
            $mensaje_estado = "Agente actualizado con éxito.";
            $tipo_mensaje = "success";
            $edit_mode = false; // Salir del modo edición
        } else {
            $mensaje_estado = "Error al actualizar el Agente: " . $stmt_update->error;
            $tipo_mensaje = "danger";
            $edit_mode = true; // Permanecer en modo edición
        }
        $stmt_update->close();
        $rif_buscado = $original_rif;
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['rif_to_delete'])) {
        $rif_to_delete = trim($conn->real_escape_string($_POST['rif_to_delete']));

        $stmt_delete = $conn->prepare("DELETE FROM agentes_aduanales WHERE rif_agentes = ?");
        $stmt_delete->bind_param("s", $rif_to_delete);

        if ($stmt_delete->execute()) {
            $mensaje_estado = "Agente eliminado con éxito.";
            $tipo_mensaje = "success";
            $agente_data = null; // Limpiar datos
        } else {
            $mensaje_estado = "Error al eliminar el Agente: " . $stmt_delete->error;
            $tipo_mensaje = "danger";
        }
        $stmt_delete->close();
    }
} elseif (isset($_GET['rif'])) {
    $rif_buscado = trim($conn->real_escape_string($_GET['rif']));
    if (isset($_GET['edit']) && $_GET['edit'] == 'true') {
        $edit_mode = true;
    }
}

if (!empty($rif_buscado)) {
    $stmt_select = $conn->prepare("SELECT rif_agentes, nombre_agente, representante, siglas_agente, telefono, domicilio_fiscal, numero_registro_agente_aduanal FROM agentes_aduanales WHERE rif_agentes = ?");
    $stmt_select->bind_param("s", $rif_buscado);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    if ($result->num_rows > 0) {
        $agente_data = $result->fetch_assoc();
    } else {
        if (empty($mensaje_estado)) {
            $mensaje_estado = "No se encontró información para el RIF: " . htmlspecialchars($rif_buscado) . ". Puede <a href='registro_agente.php?rif=" . urlencode($rif_buscado) . "'>registrarlo</a> o <a href='consulta_ca.php'>intentar otra búsqueda</a>.";
            $tipo_mensaje = "warning";
        }
    }
    $stmt_select->close();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar / Editar Agente Aduanal</title>
    <link href= "http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="http://10.164.64.91:8080/sistema interno recaudacion/fontawesome/css/all.min.css">
    <style>
        body { background-color: #f0f0f0; }
        .container { max-width: 900px; margin: 20px auto; }
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
        .card-header { 
            background-color: #0d6efd !important; 
        }
        .card-header h3 { margin-bottom: 0; }
        .required-field::after { content: " *"; color: red; }
        .form-control[readonly] {
            background-color: #e9ecef;
            opacity: 1;
        }
        .form-select[readonly] {
            background-color: #e9ecef;
            opacity: 1;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h3><i class="fas fa-user-shield me-2"></i>Datos del Agente Aduanal</h3>
                <a href="consulta_ca.php" class="btn btn-light btn-sm"><i class="fas fa-search me-1"></i>Nueva Consulta</a>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($mensaje_estado)): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo $mensaje_estado; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($agente_data): ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="original_rif" value="<?php echo htmlspecialchars($agente_data['rif_agentes']); ?>">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="action" value="update">
                        <?php endif; ?>

                        <div class="form-section">
                            <h5><i class="fas fa-id-card me-2"></i>Identificación</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="rif_agente" class="form-label">RIF Agente</label>
                                    <input type="text" class="form-control" id="rif_agente" name="rif_agente" value="<?php echo htmlspecialchars($agente_data['rif_agentes']); ?>" readonly>
                                    <small class="form-text text-muted">El RIF no es editable.</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="nombre_agente" class="form-label required-field">Nombre o Razón Social</label>
                                    <input type="text" class="form-control" id="nombre_agente" name="nombre_agente" value="<?php echo htmlspecialchars($agente_data['nombre_agente']); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5><i class="fas fa-building me-2"></i>Detalles del Agente</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="siglas" class="form-label">Siglas</label>
                                    <input type="text" class="form-control" id="siglas" name="siglas" value="<?php echo htmlspecialchars($agente_data['siglas_agente']); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label for="representante" class="form-label required-field">Representante Legal</label>
                                    <input type="text" class="form-control" id="representante" name="representante" value="<?php echo htmlspecialchars($agente_data['representante']); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                                </div>
                                <div class="col-md-6">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($agente_data['telefono']); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label for="numero_registro" class="form-label">Número de Registro</label>
                                    <input type="text" class="form-control" id="numero_registro" name="numero_registro" value="<?php echo htmlspecialchars($agente_data['numero_registro_agente_aduanal']); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?>>
                                </div>
                                <div class="col-12">
                                    <label for="domicilio" class="form-label required-field">Domicilio Fiscal</label>
                                    <textarea class="form-control" id="domicilio" name="domicilio" rows="2" <?php echo $edit_mode ? '' : 'readonly'; ?> required><?php echo htmlspecialchars($agente_data['domicilio_fiscal']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-end">
    <?php if (!$edit_mode && $es_admin): ?>
        <a href="?rif=<?php echo urlencode($agente_data['rif_agentes']); ?>&edit=true" class="btn btn-primary btn-lg me-2">
            <i class="fas fa-edit me-2"></i>Editar Datos
        </a>
        <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal">
            <i class="fas fa-trash-alt me-2"></i>Eliminar Agente
        </button>
    <?php elseif ($edit_mode && $es_admin): ?>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save me-2"></i>Actualizar Agente
        </button>
        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?rif=' . urlencode($agente_data['rif_agentes']); ?>" class="btn btn-secondary btn-lg ms-2"><i class="fas fa-undo me-2"></i>Cancelar Edición</a>
    <?php endif; ?>
</div>
                    </form>
                <?php elseif (empty($mensaje_estado) && empty($rif_buscado)): ?>
                    <div class="alert alert-info">
                        Por favor, <a href="consulta_ca.php">realice una búsqueda</a> para ver o editar los datos de un Agente Aduanal.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación de Eliminación -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar al agente con RIF: <strong><?php echo htmlspecialchars($agente_data['rif_agentes'] ?? ''); ?></strong>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="rif_to_delete" value="<?php echo htmlspecialchars($agente_data['rif_agentes'] ?? ''); ?>">
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
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