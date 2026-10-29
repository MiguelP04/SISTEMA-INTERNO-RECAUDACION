<?php
session_start();
require_once '../../config.php';

// Verificar si se proporcionó el número de formulario
if (!isset($_GET['nro_formulario'])) {
    header("Location: consulta_planillas.php");
    exit();
}

$nro_formulario = $conn->real_escape_string($_GET['nro_formulario']);

// Obtener datos de la planilla
$planilla = [];
$stmt = $conn->prepare("SELECT * FROM planillas_emitidas WHERE nro_formulario = ?");
$stmt->bind_param("s", $nro_formulario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: consulta_planillas.php?error=Planilla no encontrada");
    exit();
}

$planilla = $result->fetch_assoc();
$stmt->close();

// Obtener lista de bancos
$bancos = [];
$result_bancos = $conn->query("SELECT cod_banco, nombre_banco FROM bancos ORDER BY nombre_banco");
if ($result_bancos) {
    while ($row = $result_bancos->fetch_assoc()) {
        $bancos[] = $row;
    }
}

// Obtener agencias del banco actual
$agencias = [];
if ($planilla['cod_banco'] != 999) {
    $stmt_agencias = $conn->prepare("SELECT cod_agencia FROM agencias WHERE cod_banco = ? ORDER BY cod_agencia");
    $stmt_agencias->bind_param("i", $planilla['cod_banco']);
    $stmt_agencias->execute();
    $result_agencias = $stmt_agencias->get_result();
    
    while ($row = $result_agencias->fetch_assoc()) {
        $agencias[] = $row;
    }
    $stmt_agencias->close();
}

// Procesar el formulario de actualización
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cod_banco = (int)$_POST['cod_banco'];
    $cod_agencia = (int)$_POST['cod_agencia'];
    $fecha_cancelacion = $_POST['fecha_cancelacion'];
    $fecha_contabilidad_rec = $_POST['fecha_contabilidad_rec'] ?? $fecha_cancelacion;
    $fecha_de_entreguese = $_POST['fecha_de_entreguese'] ?? $fecha_cancelacion;

    $stmt = $conn->prepare("UPDATE planillas_emitidas SET 
        cod_banco = ?, cod_agencia = ?, fecha_cancelacion = ?, 
        fecha_contabilidad_rec = ?, fecha_de_entreguese = ?
        WHERE nro_formulario = ?");
    
    $stmt->bind_param("iissss", $cod_banco, $cod_agencia, $fecha_cancelacion, 
        $fecha_contabilidad_rec, $fecha_de_entreguese, $nro_formulario);
    
    if ($stmt->execute()) {
        header("Location: edit_formulario_planilla.php?nro_formulario=" . urlencode($nro_formulario) . "&success=Recaudación actualizada");
        exit();
    } else {
        $error = "Error al actualizar la recaudación: " . $stmt->error;
    }
    $stmt->close();
}
require_once '../../menu.php';
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
        body { background-color: #f8f9fa; }
        .container { max-width: 800px; margin-top: 50px; }
        .card-header { background-color: #17a2b8; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Recaudación</h3>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="nro_formulario" value="<?= htmlspecialchars($nro_formulario) ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">N° Formulario</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($nro_formulario) ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cod_banco" class="form-label">Banco</label>
                        <select class="form-select" id="cod_banco" name="cod_banco" required>
                            <option value="">Seleccionar banco...</option>
                            <?php foreach ($bancos as $banco): ?>
                                <option value="<?= htmlspecialchars($banco['cod_banco']) ?>" <?= ($banco['cod_banco'] == $planilla['cod_banco']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($banco['cod_banco'] . ' - ' . trim($banco['nombre_banco'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cod_agencia" class="form-label">Agencia</label>
                        <select class="form-select" id="cod_agencia" name="cod_agencia" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($agencias as $agencia): ?>
                                <option value="<?= htmlspecialchars($agencia['cod_agencia']) ?>" <?= ($agencia['cod_agencia'] == $planilla['cod_agencia']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($agencia['cod_agencia']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="fecha_cancelacion" class="form-label">Fecha Cancelación</label>
                            <input type="date" class="form-control" id="fecha_cancelacion" name="fecha_cancelacion" 
                                   value="<?= htmlspecialchars($planilla['fecha_cancelacion']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_contabilidad_rec" class="form-label">Fecha Contabilidad Rec.</label>
                            <input type="date" class="form-control" id="fecha_contabilidad_rec" name="fecha_contabilidad_rec" 
                                   value="<?= htmlspecialchars($planilla['fecha_contabilidad_rec']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_de_entreguese" class="form-label">Fecha Entreguese</label>
                            <input type="date" class="form-control" id="fecha_de_entreguese" name="fecha_de_entreguese" 
                                   value="<?= htmlspecialchars($planilla['fecha_de_entreguese']) ?>">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Actualizar Recaudación
                        </button>
                        <a href="edit_formulario_planilla.php?nro_formulario=<?= urlencode($nro_formulario) ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cargar agencias dinámicamente
        document.getElementById('cod_banco').addEventListener('change', function() {
            const codBanco = this.value;
            const selectAgencia = document.getElementById('cod_agencia');
            
            if (codBanco) {
                fetch(`get_agencias.php?cod_banco=${codBanco}`)
                    .then(response => response.json())
                    .then(data => {
                        selectAgencia.innerHTML = '<option value="">Seleccionar...</option>';
                        data.forEach(agencia => {
                            const option = document.createElement('option');
                            option.value = agencia.cod_agencia;
                            option.textContent = agencia.cod_agencia;
                            selectAgencia.appendChild(option);
                        });
                    });
            } else {
                selectAgencia.innerHTML = '<option value="">Seleccione un banco primero</option>';
            }
        });

        // Auto-rellenar fechas relacionadas
        document.getElementById('fecha_cancelacion').addEventListener('change', function() {
            const fecha = this.value;
            if (fecha) {
                document.getElementById('fecha_contabilidad_rec').value = fecha;
                document.getElementById('fecha_de_entreguese').value = fecha;
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>