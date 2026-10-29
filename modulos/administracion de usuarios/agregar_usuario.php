<?php
session_start();
require_once '../../config.php';
require_once '../../menu.php';

$PREDEFINED_SECURITY_QUESTIONS = [
    "¿Cuál era el nombre de tu primera mascota?", "¿Cuál es el nombre de la ciudad donde naciste?",
    "¿Cuál era tu apodo de la infancia?", "¿Cuál es el segundo nombre de tu madre?",
    "¿Cuál es tu comida favorita?", "¿Cuál es tu color favorito?",
    "¿Cuál es el primer nombre de tu padre?"
];

$username = $nombre_completo = $rol = '';
$pregunta1 = $respuesta1 = $pregunta2 = $respuesta2 = '';
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nombre_completo = trim($_POST['nombre_completo']);
    $rol = $_POST['rol'];
    $pregunta1 = $_POST['pregunta1'];
    $respuesta1_plain = trim($_POST['respuesta1']);
    $pregunta2 = $_POST['pregunta2'];
    $respuesta2_plain = trim($_POST['respuesta2']);

    // Validations
    if (empty($username)) $errors[] = "El nombre de usuario es obligatorio.";
    // Check if username already exists
    $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
    $stmt_check->bind_param("s", $username);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $errors[] = "El nombre de usuario ya existe.";
    }
    $stmt_check->close();

    if (empty($password)) $errors[] = "La contraseña es obligatoria.";
    if ($password !== $confirm_password) $errors[] = "Las contraseñas no coinciden.";
    if (strlen($password) < 8 && !empty($password)) $errors[] = "La contraseña debe tener al menos 8 caracteres.";
    if (empty($nombre_completo)) $errors[] = "El nombre completo es obligatorio.";
    if (empty($rol) || !in_array($rol, ['admin', 'usuario'])) $errors[] = "Seleccione un rol válido.";
    if (empty($pregunta1) || !in_array($pregunta1, $PREDEFINED_SECURITY_QUESTIONS)) $errors[] = "Seleccione una Pregunta de Seguridad 1 válida.";
    if (empty($respuesta1_plain)) $errors[] = "La Respuesta de Seguridad 1 es obligatoria.";
    if (empty($pregunta2) || !in_array($pregunta2, $PREDEFINED_SECURITY_QUESTIONS)) $errors[] = "Seleccione una Pregunta de Seguridad 2 válida.";
    if ($pregunta1 === $pregunta2 && !empty($pregunta1)) $errors[] = "Las preguntas de seguridad deben ser diferentes.";
    if (empty($respuesta2_plain)) $errors[] = "La Respuesta de Seguridad 2 es obligatoria.";


    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $respuesta1_hash = password_hash($respuesta1_plain, PASSWORD_DEFAULT);
        $respuesta2_hash = password_hash($respuesta2_plain, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO usuarios (username, password, nombre_completo, rol, pregunta1, respuesta1, pregunta2, respuesta2, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)");
        $stmt->bind_param("ssssssss", $username, $password_hash, $nombre_completo, $rol, $pregunta1, $respuesta1_hash, $pregunta2, $respuesta2_hash);

        if ($stmt->execute()) {
            $success_message = "Usuario '".htmlspecialchars($username)."' agregado con éxito. <a href='gestion_usuarios.php'>Volver a la lista</a>";
            // Clear form fields after successful submission
            $username = $nombre_completo = $rol = $pregunta1 = $respuesta1 = $pregunta2 = $respuesta2 = '';
        } else {
            $errors[] = "Error al agregar el usuario: " . $stmt->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Nuevo Usuario - SENIAT</title>
    <link href= "http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="http://10.164.64.91:8080/sistema interno recaudacion/fontawesome/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; }
        .container { max-width: 900px; }
        .card-header h3 { margin-bottom: 0; }
        .form-section { margin-bottom: 1.5rem; }
        .form-section h5 { border-bottom: 1px solid #dee2e6; padding-bottom: 0.5rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h3><i class="fas fa-user-plus me-2"></i>Agregar Nuevo Usuario</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p class="mb-0"><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <form action="agregar_usuario.php" method="POST">
                    <div class="form-section">
                        <h5>Información Básica</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nombre_completo" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" value="<?php echo htmlspecialchars($nombre_completo); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="form-text text-muted">Mínimo 8 caracteres.</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="confirm_password" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="rol" class="form-label">Rol <span class="text-danger">*</span></label>
                                <select class="form-select" id="rol" name="rol" required>
                                    <option value="">Seleccione un rol...</option>
                                    <option value="usuario" <?php echo ($rol === 'usuario') ? 'selected' : ''; ?>>Usuario</option>
                                    <option value="admin" <?php echo ($rol === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h5>Preguntas de Seguridad</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="pregunta1" class="form-label">Pregunta de Seguridad 1 <span class="text-danger">*</span></label>
                                <select class="form-select" id="pregunta1" name="pregunta1" required>
                                    <option value="">Seleccione una pregunta...</option>
                                    <?php foreach ($PREDEFINED_SECURITY_QUESTIONS as $q): ?>
                                        <option value="<?php echo htmlspecialchars($q); ?>" <?php echo ($pregunta1 === $q) ? 'selected' : ''; ?>><?php echo htmlspecialchars($q); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="respuesta1" class="form-label">Respuesta 1 <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="respuesta1" name="respuesta1" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="pregunta2" class="form-label">Pregunta de Seguridad 2 <span class="text-danger">*</span></label>
                                <select class="form-select" id="pregunta2" name="pregunta2" required>
                                    <option value="">Seleccione una pregunta...</option>
                                    <?php foreach ($PREDEFINED_SECURITY_QUESTIONS as $q): ?>
                                        <option value="<?php echo htmlspecialchars($q); ?>" <?php echo ($pregunta2 === $q) ? 'selected' : ''; ?>><?php echo htmlspecialchars($q); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="respuesta2" class="form-label">Respuesta 2 <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="respuesta2" name="respuesta2" required>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="gestion_usuarios.php" class="btn btn-secondary me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-success"><i class="fas fa-plus-circle me-2"></i>Agregar Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>