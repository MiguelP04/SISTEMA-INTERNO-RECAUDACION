<?php
session_start();
require_once '../../config.php';
require_once '../../menu.php';
// --- Authentication and Authorization Check ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['rol'] !== 'admin') {
    echo "<div class='container mt-3'><div class='alert alert-danger'>Acceso denegado. Esta sección es solo para administradores.</div></div>";
    exit;
}

$PREDEFINED_SECURITY_QUESTIONS = [
    "¿Cuál era el nombre de tu primera mascota?", "¿Cuál es el nombre de la ciudad donde naciste?",
    "¿Cuál era tu apodo de la infancia?", "¿Cuál es el segundo nombre de tu madre?",
    "¿Cuál es tu comida favorita?",
];

$user_id = $_GET['id'] ?? null;
if (!$user_id || !filter_var($user_id, FILTER_VALIDATE_INT)) {
    $_SESSION['mensaje_usuarios'] = "<div class='alert alert-danger'>ID de usuario inválido.</div>";
    header('Location: gestion_usuarios.php');
    exit;
}

$username = $nombre_completo = $rol = '';
$pregunta1 = $respuesta1_hash_db = $pregunta2 = $respuesta2_hash_db = ''; // Store original hashed answers to compare if changed
$activo = 1;
$errors = [];
$success_message = '';

// Fetch user data
$stmt_fetch = $conn->prepare("SELECT username, nombre_completo, rol, pregunta1, respuesta1, pregunta2, respuesta2, activo FROM usuarios WHERE id = ?");
$stmt_fetch->bind_param("i", $user_id);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
if ($user = $result->fetch_assoc()) {
    $username = $user['username'];
    $nombre_completo = $user['nombre_completo'];
    $rol = $user['rol'];
    $pregunta1 = $user['pregunta1'];
    $respuesta1_hash_db = $user['respuesta1']; // Hashed value from DB
    $pregunta2 = $user['pregunta2'];
    $respuesta2_hash_db = $user['respuesta2']; // Hashed value from DB
    $activo = $user['activo'];
} else {
    $_SESSION['mensaje_usuarios'] = "<div class='alert alert-danger'>Usuario no encontrado.</div>";
    header('Location: gestion_usuarios.php');
    exit;
}
$stmt_fetch->close();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $new_username = trim($_POST['username']);
    $new_password = $_POST['password']; 
    $confirm_password = $_POST['confirm_password'];
    $new_nombre_completo = trim($_POST['nombre_completo']);
    $new_rol = $_POST['rol'];
    $new_pregunta1 = $_POST['pregunta1'];
    $new_respuesta1_plain = trim($_POST['respuesta1']); 
    $new_pregunta2 = $_POST['pregunta2'];
    $new_respuesta2_plain = trim($_POST['respuesta2']); 
    $new_activo = isset($_POST['activo']) ? 1 : 0;

    // Validaciones
    if (empty($new_username)) $errors[] = "El nombre de usuario es obligatorio.";
    
    if ($new_username !== $username) {
        $stmt_check_edit = $conn->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
        $stmt_check_edit->bind_param("si", $new_username, $user_id);
        $stmt_check_edit->execute();
        if ($stmt_check_edit->get_result()->num_rows > 0) {
            $errors[] = "El nuevo nombre de usuario ya está en uso por otro usuario.";
        }
        $stmt_check_edit->close();
    }

    if (!empty($new_password)) { 
        if ($new_password !== $confirm_password) $errors[] = "Las nuevas contraseñas no coinciden.";
        if (strlen($new_password) < 8) $errors[] = "La nueva contraseña debe tener al menos 8 caracteres.";
    }
    if (empty($new_nombre_completo)) $errors[] = "El nombre completo es obligatorio.";
    if (empty($new_rol) || !in_array($new_rol, ['admin', 'usuario'])) $errors[] = "Seleccione un rol válido.";
    
    
    if (!empty($new_respuesta1_plain) || $new_pregunta1 !== $pregunta1) {
        if (empty($new_pregunta1) || !in_array($new_pregunta1, $PREDEFINED_SECURITY_QUESTIONS)) $errors[] = "Seleccione una Pregunta de Seguridad 1 válida.";
        if (empty($new_respuesta1_plain)) $errors[] = "Si cambia la Pregunta 1 o desea actualizarla, la Respuesta 1 es obligatoria.";
    }
    if (!empty($new_respuesta2_plain) || $new_pregunta2 !== $pregunta2) {
        if (empty($new_pregunta2) || !in_array($new_pregunta2, $PREDEFINED_SECURITY_QUESTIONS)) $errors[] = "Seleccione una Pregunta de Seguridad 2 válida.";
        if (empty($new_respuesta2_plain)) $errors[] = "Si cambia la Pregunta 2 o desea actualizarla, la Respuesta 2 es obligatoria.";
    }
    if (($new_pregunta1 === $new_pregunta2) && !empty($new_pregunta1) && (!empty($new_respuesta1_plain) || !empty($new_respuesta2_plain)) ) {
        $errors[] = "Las preguntas de seguridad deben ser diferentes.";
    }


    if (empty($errors)) {
        $query_parts = [];
        $params = [];
        $types = "";

        // Username
        if ($new_username !== $username) {
            $query_parts[] = "username = ?";
            $params[] = $new_username;
            $types .= "s";
        }
        // Password
        if (!empty($new_password)) {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $query_parts[] = "password = ?";
            $params[] = $new_password_hash;
            $types .= "s";
        }
        // Nombre Completo
        if ($new_nombre_completo !== $nombre_completo) {
            $query_parts[] = "nombre_completo = ?";
            $params[] = $new_nombre_completo;
            $types .= "s";
        }
        // Rol
        if ($new_rol !== $rol) {
            $query_parts[] = "rol = ?";
            $params[] = $new_rol;
            $types .= "s";
        }
        // Pregunta 1 y Respuesta 1
        if ($new_pregunta1 !== $pregunta1 || !empty($new_respuesta1_plain)) {
            $query_parts[] = "pregunta1 = ?";
            $params[] = $new_pregunta1;
            $types .= "s";
            $query_parts[] = "respuesta1 = ?";
            $params[] = password_hash($new_respuesta1_plain, PASSWORD_DEFAULT);
            $types .= "s";
        }
         // Pregunta 2 y Respuesta 2
        if ($new_pregunta2 !== $pregunta2 || !empty($new_respuesta2_plain)) {
            $query_parts[] = "pregunta2 = ?";
            $params[] = $new_pregunta2;
            $types .= "s";
            $query_parts[] = "respuesta2 = ?";
            $params[] = password_hash($new_respuesta2_plain, PASSWORD_DEFAULT);
            $types .= "s";
        }
        // Activo
        if ($new_activo != $activo) {
            $query_parts[] = "activo = ?";
            $params[] = $new_activo;
            $types .= "i";
        }

        if (!empty($query_parts)) {
            $sql_update = "UPDATE usuarios SET " . implode(", ", $query_parts) . " WHERE id = ?";
            $types .= "i";
            $params[] = $user_id;

            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param($types, ...$params);

            if ($stmt_update->execute()) {
                $success_message = "Usuario '".htmlspecialchars($new_username)."' actualizado con éxito. <a href='gestion_usuarios.php'>Volver a la lista</a>";
                // Refresh data for the form
                $username = $new_username;
                $nombre_completo = $new_nombre_completo;
                $rol = $new_rol;
                $pregunta1 = $new_pregunta1;
                $pregunta2 = $new_pregunta2;
                $activo = $new_activo;
                // Security answers are not displayed back, only their questions.
            } else {
                $errors[] = "Error al actualizar el usuario: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $success_message = "No se realizaron cambios. <a href='gestion_usuarios.php'>Volver a la lista</a>";
        }
    }
    // If errors, current POST values should be redisplayed if they are different from original
    if(!empty($errors)){
        $username = $new_username; // Show attempted username
        $nombre_completo = $new_nombre_completo;
        $rol = $new_rol;
        $pregunta1 = $new_pregunta1;
        $pregunta2 = $new_pregunta2;
        $activo = $new_activo;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - SENIAT</title>
    <link href= "http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="http://10.164.64.91:8080/sistema interno recaudacion/fontawesome/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; }
        .container { max-width: 700px; }
        .card-header h3 { margin-bottom: 0; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-warning text-dark">
                <h3><i class="fas fa-user-edit me-2"></i>Editar Usuario: <?php echo htmlspecialchars($username); ?></h3>
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

                <form action="editar_usuario.php?id=<?php echo $user_id; ?>" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="nombre_completo" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" value="<?php echo htmlspecialchars($nombre_completo); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol <span class="text-danger">*</span></label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="usuario" <?php echo ($rol === 'usuario') ? 'selected' : ''; ?>>Usuario</option>
                            <option value="admin" <?php echo ($rol === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                        </select>
                    </div>
                     <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="activo" name="activo" <?php echo ($activo == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="activo">
                            Usuario Activo
                        </label>
                    </div>

                    <hr>
                    <h5>Cambiar Contraseña (dejar en blanco para no cambiar)</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="form-text text-muted">Mínimo 8 caracteres.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>

                    <hr>
                    <h5>Preguntas de Seguridad (actualizar si es necesario)</h5>
                     <p class="text-muted small">Si desea cambiar las preguntas o respuestas, complete los campos correspondientes. Las respuestas anteriores se sobrescribirán.</p>
                    <div class="mb-3">
                        <label for="pregunta1" class="form-label">Pregunta de Seguridad 1</label>
                        <select class="form-select" id="pregunta1" name="pregunta1">
                            <option value="">Seleccione una pregunta...</option>
                            <?php foreach ($PREDEFINED_SECURITY_QUESTIONS as $q): ?>
                                <option value="<?php echo htmlspecialchars($q); ?>" <?php echo ($pregunta1 === $q) ? 'selected' : ''; ?>><?php echo htmlspecialchars($q); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="respuesta1" class="form-label">Nueva Respuesta 1</label>
                        <input type="password" class="form-control" id="respuesta1" name="respuesta1" placeholder="Dejar en blanco para no cambiar respuesta si la pregunta no cambia">
                        <small class="form-text text-muted">Si cambia la pregunta, debe ingresar una nueva respuesta.</small>
                    </div>

                    <div class="mb-3">
                        <label for="pregunta2" class="form-label">Pregunta de Seguridad 2</label>
                        <select class="form-select" id="pregunta2" name="pregunta2">
                            <option value="">Seleccione una pregunta...</option>
                            <?php foreach ($PREDEFINED_SECURITY_QUESTIONS as $q): ?>
                                <option value="<?php echo htmlspecialchars($q); ?>" <?php echo ($pregunta2 === $q) ? 'selected' : ''; ?>><?php echo htmlspecialchars($q); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="respuesta2" class="form-label">Nueva Respuesta 2</label>
                        <input type="password" class="form-control" id="respuesta2" name="respuesta2" placeholder="Dejar en blanco para no cambiar respuesta si la pregunta no cambia">
                        <small class="form-text text-muted">Si cambia la pregunta, debe ingresar una nueva respuesta.</small>
                    </div>


                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                         <a href="gestion_usuarios.php" class="btn btn-secondary me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save me-2"></i>Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>