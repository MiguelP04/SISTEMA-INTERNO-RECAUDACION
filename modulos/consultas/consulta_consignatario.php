<?php
session_start();
require_once '../../config.php';
require_once '../../menu.php';

$consignatario_data = null;
$mensaje_estado = "";
$tipo_mensaje = "danger";
$rif_buscado = "";
$edit_mode = false; // Flag to control edit mode

// Cargar tipos de contribuyente para el select
$tipos_contribuyente_db = [];
$result_tipos_contr = $conn->query("SELECT id_tipo_contribuyente, desc_tipo_contribuyente FROM tipo_contribuyente ORDER BY desc_tipo_contribuyente");
if ($result_tipos_contr) {
    while ($row = $result_tipos_contr->fetch_assoc()) {
        $tipos_contribuyente_db[$row['id_tipo_contribuyente']] = $row['desc_tipo_contribuyente'];
    }
}

// Process POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'update' && isset($_POST['original_rif'])) {
        $original_rif = trim($conn->real_escape_string($_POST['original_rif']));
        $rif_consignatario = $original_rif; // Asumimos que el RIF NO se edita

        $nombre_consignatario = trim($conn->real_escape_string($_POST['nombre_consignatario']));
        $siglas = trim($conn->real_escape_string($_POST['siglas']));
        $representante = trim($conn->real_escape_string($_POST['representante']));
        $telefono = trim($conn->real_escape_string($_POST['telefono']));
        $direccion = trim($conn->real_escape_string($_POST['direccion']));
        $tipo_contribuyente_id = trim($conn->real_escape_string($_POST['tipo_contribuyente']));
        // Campos adicionales de consignatarios.sql
        $cod_ciudad = isset($_POST['cod_ciudad']) ? trim($conn->real_escape_string($_POST['cod_ciudad'])) : '';
        $referencia = isset($_POST['referencia']) ? trim($conn->real_escape_string($_POST['referencia'])) : '';
        $codigo_postal = isset($_POST['codigo_postal']) ? trim($conn->real_escape_string($_POST['codigo_postal'])) : '';
        $cir = isset($_POST['CIR']) ? trim($conn->real_escape_string($_POST['CIR'])) : '';

        $stmt_update = $conn->prepare("UPDATE consignatarios SET nombre_consignatario = ?, siglas = ?, representante = ?, telefono = ?, direccion = ?, tipo_contribuyente = ?, cod_ciudad = ?, referencia = ?, zona_postal = ?, CIR = ? WHERE rif_consignatario = ?");
        $stmt_update->bind_param("sssssisssss", $nombre_consignatario, $siglas, $representante, $telefono, $direccion, $tipo_contribuyente_id, $cod_ciudad, $referencia, $codigo_postal, $cir, $original_rif);

        if ($stmt_update->execute()) {
            $mensaje_estado = "Consignatario actualizado con éxito.";
            $tipo_mensaje = "success";
            $edit_mode = false; // Exit edit mode after update
        } else {
            $mensaje_estado = "Error al actualizar el Consignatario: " . $stmt_update->error;
            $tipo_mensaje = "danger";
            $edit_mode = true; // Stay in edit mode on error
        }
        $stmt_update->close();
        $rif_buscado = $original_rif;
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['rif_to_delete'])) {
        $rif_to_delete = trim($conn->real_escape_string($_POST['rif_to_delete']));

        $stmt_delete = $conn->prepare("DELETE FROM consignatarios WHERE rif_consignatario = ?");
        $stmt_delete->bind_param("s", $rif_to_delete);

        if ($stmt_delete->execute()) {
            $mensaje_estado = "Consignatario eliminado con éxito.";
            $tipo_mensaje = "success";
            $consignatario_data = null; // Clear data as it's deleted
        } else {
            $mensaje_estado = "Error al eliminar el Consignatario: " . $stmt_delete->error;
            $tipo_mensaje = "danger";
        }
        $stmt_delete->close();
    }
}


// Process GET requests
if (isset($_GET['rif'])) {
    $rif_buscado = trim($conn->real_escape_string($_GET['rif']));
    if (isset($_GET['edit']) && $_GET['edit'] == 'true') {
        $edit_mode = true;
    }
}

if (!empty($rif_buscado)) {
    // Note: The `codigo_postal` column in the database is actually `zona_postal`
    // The previous error was due to `zona_postal` being present in the database but `codigo_postal` being used in the query.
    // I am using `zona_postal` in the SELECT query and mapping it to `codigo_postal` in the form for consistency with the user's view.
    $stmt_select = $conn->prepare("SELECT rif_consignatario, nombre_consignatario, direccion, telefono, siglas, representante, tipo_contribuyente, cod_ciudad, referencia, zona_postal, CIR FROM consignatarios WHERE rif_consignatario = ?");
    $stmt_select->bind_param("s", $rif_buscado);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    if ($result->num_rows > 0) {
        $consignatario_data = $result->fetch_assoc();
        // Map zona_postal to codigo_postal for form display
        $consignatario_data['codigo_postal'] = $consignatario_data['zona_postal'];
    } else {
        if (empty($mensaje_estado)) {
            $mensaje_estado = "No se encontró información para el RIF: " . htmlspecialchars($rif_buscado) . ". Puede <a href='registrar_consignatario.php?rif=" . urlencode($rif_buscado) . "'>registrarlo</a> o <a href='consulta_ca.php'>intentar otra búsqueda</a>.";
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
    <title>Consultar / Editar Consignatario</title>
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
                <h3><i class="fas fa-user-tie me-2"></i>Datos del Consignatario</h3>
                <a href="consulta_ca.php" class="btn btn-light btn-sm"><i class="fas fa-search me-1"></i>Nueva Consulta</a>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($mensaje_estado)): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo $mensaje_estado; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($consignatario_data): ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="original_rif" value="<?php echo htmlspecialchars($consignatario_data['rif_consignatario']); ?>">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="action" value="update">
                        <?php endif; ?>

                        <div class="form-section">
                            <h5><i class="fas fa-id-card me-2"></i>Identificación</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="rif_consignatario" class="form-label">RIF Consignatario</label>
                                    <input type="text" class="form-control" id="rif_consignatario" name="rif_consignatario" value="<?php echo htmlspecialchars($consignatario_data['rif_consignatario']); ?>" readonly>
                                    <small class="form-text text-muted">El RIF no es editable.</small>
                                </div>
                                <div class="col-md-8">
                                    <label for="nombre_consignatario" class="form-label required-field">Nombre o Razón Social</label>
                                    <input type="text" class="form-control" id="nombre_consignatario" name="nombre_consignatario" value="<?php echo htmlspecialchars($consignatario_data['nombre_consignatario']); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5><i class="fas fa-building me-2"></i>Detalles del Consignatario</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="siglas" class="form-label">Siglas</label>
                                    <input type="text" class="form-control" id="siglas" name="siglas" value="<?php echo htmlspecialchars($consignatario_data['siglas']); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label for="representante" class="form-label">Representante Legal</label>
                                    <input type="text" class="form-control" id="representante" name="representante" value="<?php echo htmlspecialchars($consignatario_data['representante']); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($consignatario_data['telefono']); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label for="tipo_contribuyente" class="form-label required-field">Tipo Contribuyente</label>
                                    <select class="form-select" id="tipo_contribuyente" name="tipo_contribuyente" <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                                        <option value="">Seleccionar...</option>
                                        <?php foreach ($tipos_contribuyente_db as $id => $descripcion): ?>
                                            <option value="<?php echo $id; ?>" <?php echo ($consignatario_data['tipo_contribuyente'] == $id) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($descripcion); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="direccion" class="form-label">Dirección Fiscal</label>
                                    <textarea class="form-control" id="direccion" name="direccion" rows="2" <?php echo $edit_mode ? '' : 'readonly'; ?>><?php echo htmlspecialchars($consignatario_data['direccion']); ?></textarea>
                                </div>
                               <div class="row g-3">
                               <div class="col-md-3">
                                    <label for="cod_ciudad" class="form-label">Código Ciudad</label>
                                    <input type="text" class="form-control" id="cod_ciudad" name="cod_ciudad" value="<?php echo htmlspecialchars($consignatario_data['cod_ciudad']); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?>>
                               </div>
                               <div class="col-md-3">
                                    <label for="codigo_postal" class="form-label">Código Postal</label>
                                    <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" value="<?php echo htmlspecialchars($consignatario_data['codigo_postal']); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?>>
                               </div>
                               <div class="col-md-3">
                                    <label for="referencia" class="form-label">Referencia</label>
                                    <input type="text" class="form-control" id="referencia" name="referencia" value="<?php echo htmlspecialchars($consignatario_data['referencia']); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?>>
                               </div>
                               <div class="col-md-3">
                                    <label for="CIR" class="form-label">CIR</label>
                                    <input type="text" class="form-control" id="CIR" name="CIR" value="<?php echo htmlspecialchars($consignatario_data['CIR']); ?>" <?php echo $edit_mode ? '' : 'readonly'; ?>>
                               </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-end">
    <?php if (!$edit_mode && $es_admin): ?>
        <a href="?rif=<?php echo urlencode($consignatario_data['rif_consignatario']); ?>&edit=true" class="btn btn-warning btn-lg me-2">
            <i class="fas fa-edit me-2"></i>Editar Datos
        </a>
        <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal">
            <i class="fas fa-trash-alt me-2"></i>Eliminar Consignatario
        </button>
    <?php elseif ($edit_mode && $es_admin): ?>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save me-2"></i>Actualizar Consignatario
        </button>
        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?rif=' . urlencode($consignatario_data['rif_consignatario']); ?>" class="btn btn-secondary btn-lg ms-2"><i class="fas fa-undo me-2"></i>Cancelar Edición</a>
    <?php endif; ?>
</div>
                    </form>
                <?php elseif (empty($mensaje_estado) && empty($rif_buscado)): ?>
                    <div class="alert alert-info">
                        Por favor, <a href="consulta_ca.php">realice una búsqueda</a> para ver o editar los datos de un Consignatario.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar al consignatario con RIF: <strong><?php echo htmlspecialchars($consignatario_data['rif_consignatario'] ?? ''); ?></strong>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="rif_to_delete" value="<?php echo htmlspecialchars($consignatario_data['rif_consignatario'] ?? ''); ?>">
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