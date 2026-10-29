<?php
session_start();
require_once '../../config.php';
require_once '../../menu.php';

// Para cargar los tipos de contribuyente en el select
$tipos_contribuyente_filter = [];
// Asumiendo que $conn está definido en config.php y es una conexión válida
if (isset($conn)) {
    $result_tipos_contr = $conn->query("SELECT id_tipo_contribuyente, desc_tipo_contribuyente FROM tipo_contribuyente ORDER BY desc_tipo_contribuyente");
    if ($result_tipos_contr) {
        while ($row = $result_tipos_contr->fetch_assoc()) {
            $tipos_contribuyente_filter[] = $row;
        }
    }
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
        .container { max-width: 600px; margin: 40px auto; }
        .card-header h3 { margin-bottom: 0; }
        .form-label { font-weight: 500; }
        .rif-input-group { display: flex; align-items: center; gap: 10px; }
        .rif-input-group .form-select { max-width: 80px; }
        .btn-primary { background-color: #0d6efd; border-color: #0d6efd; }
        .btn-primary:hover { background-color: #0b5ed7; border-color: #0a58ca; }
        .alert { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3><i class="fas fa-search me-2"></i>Consultar Consignatario / Agente</h3>
            </div>
            <div class="card-body p-4">
                <?php if (isset($_GET['estado']) && $_GET['estado'] == 'no_encontrado'): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>RIF no encontrado. Verifique el RIF y el tipo seleccionado.
                    </div>
                <?php endif; ?>
                 <?php if (isset($_GET['mensaje'])): ?>
                    <div class="alert alert-<?php echo (isset($_GET['tipo_mensaje']) && $_GET['tipo_mensaje'] == 'exito') ? 'success' : 'danger'; ?>">
                        <?php echo htmlspecialchars(urldecode($_GET['mensaje'])); ?>
                    </div>
                <?php endif; ?>

                <form id="formularioConsultaCA" action="procesar_consulta_ca.php" method="POST">
                    <div class="mb-3">
                        <label for="tipo_entidad" class="form-label required-field">Tipo de Entidad</label>
                        <select class="form-select" id="tipo_entidad" name="tipo_entidad" required>
                            <option value="">Seleccione...</option>
                            <option value="Consignatario">Consignatario</option>
                            <option value="Agente">Agente Aduanal</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required-field">RIF</label>
                        <div class="rif-input-group">
                             <select class="form-select rif-type-select" id="rif_prefijo" name="rif_prefijo" required>
                                <option value="">-</option>
                                <?php
                              
                                $prefijos_rif = [['id' => 'J', 'desc' => 'J'], ['id' => 'G', 'desc' => 'G'], ['id' => 'V', 'desc' => 'V'], ['id' => 'E', 'desc' => 'E'], ['id' => 'P', 'desc' => 'P']];
                                foreach ($prefijos_rif as $prefijo):
                                ?>
                                <option value="<?php echo htmlspecialchars($prefijo['id']); ?>"><?php echo htmlspecialchars($prefijo['desc']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" class="form-control" id="rif_numero" name="rif_numero" placeholder="Número del RIF (Ej: 123456789)" inputmode="numeric" pattern="[0-9]{7,9}" title="Debe contener entre 7 y 9 dígitos." required>
                            </div>
                         <small class="form-text text-muted">Ingrese el prefijo y el número del RIF.</small>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Consultar / Editar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>