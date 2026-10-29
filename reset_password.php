<?php
// Página para restablecer contraseña después de verificar preguntas de seguridad
session_start();
require_once 'config.php';  // Para operaciones con base de datos

// PASO 1: Verificar permiso para restablecer contraseña
// Si no hay sesión válida, redirigir al login
if (!isset($_SESSION['password_reset_allowed_for_user'])) {
    header('Location: login.php');
    exit;
}

// PASO 2: Inicializar variables
$user_id_to_reset = $_SESSION['password_reset_allowed_for_user'];
$update_message = '';  // Mensajes de resultado

// PASO 3: Procesar formulario de nueva contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'], $_POST['confirm_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validación 1: Campos vacíos
    if (empty($new_password) || empty($confirm_password)) {
        $update_message = "<div class='alert alert-danger'>Por favor, complete ambos campos de contraseña.</div>";
    } 
    // Validación 2: Coincidencia de contraseñas
    elseif ($new_password !== $confirm_password) {
        $update_message = "<div class='alert alert-danger'>Las contraseñas no coinciden.</div>";
    } 
    // Validación 3: Longitud mínima (8 caracteres)
    elseif (strlen($new_password) < 8) {
        $update_message = "<div class='alert alert-danger'>La contraseña debe tener al menos 8 caracteres.</div>";
    } 
    // Si pasa todas las validaciones
    else {
        // Encriptar nueva contraseña
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Actualizar contraseña en la base de datos
        $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?"); 
        $stmt->bind_param("si", $new_password_hash, $user_id_to_reset);
        
        if ($stmt->execute()) {
            // Éxito: limpiar sesión y mostrar mensaje
            unset($_SESSION['password_reset_allowed_for_user']);
            $update_message = "<div class='alert alert-success'>Contraseña actualizada con éxito. Ahora puede <a href='login.php'>iniciar sesión</a>.</div>";
        } else {
            // Error en la actualización
            $update_message = "<div class='alert alert-danger'>Error al actualizar la contraseña. Intente de nuevo.</div>";
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
    <title>Restablecer Contraseña - SENIAT</title>
    <link href= "http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="http://10.164.64.91:8080/sistema interno recaudacion/fontawesome/css/all.min.css">
    <style>
        body {
            background-color: #1a365d;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
        }
        .login-container {
            background-color: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            text-align: center;
        }
        .login-container img.logo {
            max-width: 200px;
            margin-bottom: 25px;
        }
        .login-container h2 {
            margin-bottom: 25px;
            color: #1a365d;
            font-weight: 600;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #ddd;
        }
        .form-control:focus {
            border-color: #1a365d;
            box-shadow: 0 0 0 0.25rem rgba(26, 54, 93, 0.25);
        }
        .btn-primary-seniat {
            background-color: #1a365d;
            border-color: #1a365d;
            padding: 12px 15px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
        }
        .btn-primary-seniat:hover {
            background-color: #15305a;
            border-color: #15305a;
        }
        .back-to-login-link {
            display: block;
            margin-top: 15px;
            color: #6c757d;
            text-decoration: none;
        }
        .back-to-login-link:hover {
            color: #1a365d;
            text-decoration: underline;
        }
        .form-label {
            text-align: left;
            display: block;
            margin-bottom: .5rem;
            color: #495057;
            font-weight: 500;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="LOGO SENIAT.png" alt="SENIAT Logo" class="logo img-fluid">
        <h2>Restablecer Contraseña</h2>
        
        <?php echo $update_message; ?>
        
        <?php if(isset($_SESSION['password_reset_allowed_for_user'])): // Show form only if password not yet updated ?>
        <form method="POST" action="reset_password.php">
            <div class="mb-3">
                <label for="new_password" class="form-label">Nueva Contraseña</label>
                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Nueva contraseña" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirmar contraseña" required>
            </div>
            <button type="submit" class="btn btn-primary-seniat">Actualizar Contraseña</button>
            <a href="login.php" class="back-to-login-link">Volver a Iniciar Sesión</a>
        </form>
        <?php endif; ?>
    </div>

   <script src="http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>