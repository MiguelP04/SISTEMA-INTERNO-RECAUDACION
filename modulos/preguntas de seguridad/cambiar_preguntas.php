<?php
session_start();
require_once '../../config.php';
require_once '../../menu.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$mensaje = '';
$preguntas_disponibles = [
    '¿Cuál era el nombre de tu primera mascota?',
    '¿Cuál es el nombre de la ciudad donde naciste?',
    '¿Cuál es tu color favorito?',
    '¿Cuál es el segundo nombre de tu madre?',
    '¿Cuál es tu comida favorita?'
];

// Obtener preguntas actuales del usuario
$preguntas_actuales = ['', ''];
$usuario_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT pregunta1, pregunta2 FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$stmt->bind_result($preguntas_actuales[0], $preguntas_actuales[1]);
$stmt->fetch();
$stmt->close();

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pregunta1 = $_POST['pregunta1'];
    $respuesta1 = trim($_POST['respuesta1']);
    $pregunta2 = $_POST['pregunta2'];
    $respuesta2 = trim($_POST['respuesta2']);
    
    // Validaciones
    if ($pregunta1 === $pregunta2) {
        $mensaje = '<div class="alert alert-danger">Debe seleccionar preguntas diferentes.</div>';
    } elseif (empty($respuesta1) || empty($respuesta2)) {
        $mensaje = '<div class="alert alert-danger">Debe proporcionar respuestas para ambas preguntas.</div>';
    } else {
        // Hash de las respuestas
        $respuesta1_hash = password_hash($respuesta1, PASSWORD_DEFAULT);
        $respuesta2_hash = password_hash($respuesta2, PASSWORD_DEFAULT);
        
        // Actualizar en la BD
        $stmt = $conn->prepare("UPDATE usuarios SET pregunta1 = ?, respuesta1 = ?, pregunta2 = ?, respuesta2 = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $pregunta1, $respuesta1_hash, $pregunta2, $respuesta2_hash, $usuario_id);
        
        if ($stmt->execute()) {
            $mensaje = '<div class="alert alert-success">Preguntas de seguridad actualizadas correctamente.</div>';
            // Actualizar variables para mostrar las nuevas preguntas
            $preguntas_actuales = [$pregunta1, $pregunta2];
        } else {
            $mensaje = '<div class="alert alert-danger">Error al actualizar: '.$stmt->error.'</div>';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Preguntas de Seguridad</title>
    <link href= "http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="http://10.164.64.91:8080/sistema interno recaudacion/fontawesome/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-left: 4px solid #0d6efd; }
        .required-field::after { content: " *"; color: red; }
        .current-question { background-color: #e9ffe9; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Preguntas de Seguridad</h3>
                    </div>
                    <div class="card-body">
                        <?php echo $mensaje; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="pregunta1" class="form-label required-field">Nueva Primera Pregunta</label>
                                <select class="form-select" id="pregunta1" name="pregunta1" required>
                                    <option value="">Seleccione una pregunta...</option>
                                    <?php foreach ($preguntas_disponibles as $pregunta): ?>
                                        <option value="<?php echo htmlspecialchars($pregunta); ?>"
                                            <?php echo ($preguntas_actuales[0] === $pregunta) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pregunta); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="respuesta1" class="form-label required-field">Nueva Respuesta</label>
                                <input type="text" class="form-control" id="respuesta1" name="respuesta1" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="pregunta2" class="form-label required-field">Nueva Segunda Pregunta</label>
                                <select class="form-select" id="pregunta2" name="pregunta2" required>
                                    <option value="">Seleccione una pregunta...</option>
                                    <?php foreach ($preguntas_disponibles as $pregunta): ?>
                                        <option value="<?php echo htmlspecialchars($pregunta); ?>"
                                            <?php echo ($preguntas_actuales[1] === $pregunta) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pregunta); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="respuesta2" class="form-label required-field">Nueva Respuesta</label>
                                <input type="text" class="form-control" id="respuesta2" name="respuesta2" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Guardar Cambios
                                </button>
                                <a href="index.php" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-arrow-left me-2"></i>Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validar que las preguntas sean diferentes
        document.querySelector('form').addEventListener('submit', function(e) {
            const pregunta1 = document.getElementById('pregunta1').value;
            const pregunta2 = document.getElementById('pregunta2').value;
            
            if (pregunta1 === pregunta2 && pregunta1 !== '') {
                e.preventDefault();
                alert('Debe seleccionar preguntas diferentes.');
                return false;
            }
            
            const respuesta1 = document.getElementById('respuesta1').value;
            const respuesta2 = document.getElementById('respuesta2').value;
            
            if (respuesta1.length < 3 || respuesta2.length < 3) {
                e.preventDefault();
                alert('Las respuestas deben tener al menos 3 caracteres.');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>