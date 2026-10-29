<?php
// Página de inicio de sesión y recuperación de contraseña
session_start();
require_once 'config.php';  // Configuración de base de datos

// PASO 1: Verificar si usuario ya está logueado
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: index.php');
    exit;
}

// PASO 2: Inicializar variables
$login_error = '';          // Mensaje error en login
$forgot_message = '';       // Mensaje recuperación contraseña
$forgot_step = 1;           // Paso actual en recuperación (1-3)
$action = $_GET['action'] ?? '';  // Acción actual (login/recuperación)
$username_for_forgot = $_POST['username_for_forgot'] ?? $_SESSION['forgot_username'] ?? '';

// PASO 3: Manejar envío de formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CASO 3.1: Formulario de login normal
    if (isset($_POST['login'])) {
        $username = trim($_POST['username_or_email']);
        $password = $_POST['password'];
        // Validar campos requeridos
        if (empty($username) || empty($password)) {
            $login_error = 'Por favor, ingrese usuario y contraseña.';
        } else {
            // Buscar usuario en base de datos
            $stmt = $conn->prepare("SELECT id, username, password, rol, activo FROM usuarios WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                // Verificar estado y contraseña
                if ($user['activo'] && password_verify($password, $user['password'])) { 
                    $_SESSION['loggedin'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['rol'] = $user['rol']; // Corrected 'rol' field name
                    header('Location: index.php'); // Redirect to a dashboard or main page
                    exit;
                } else if (!$user['activo']) { // Corrected 'activo' field name
                    $login_error = 'Su cuenta está desactivada. Contacte al administrador.';
                } else {
                    $login_error = 'Usuario o contraseña incorrectos.';
                }
            } else {
                $login_error = 'Usuario o contraseña incorrectos.';
            }
            $stmt->close();
        }
    // CASO 3.2: Recuperación de contraseña (Paso 1)
    } elseif (isset($_POST['forgot_password_step1'])) { 
        $username_for_forgot = trim($_POST['username_for_forgot']);
        $_SESSION['forgot_username'] = $username_for_forgot;
        
        $stmt_user = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt_user->bind_param("s", $username_for_forgot);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        if ($user_data = $result_user->fetch_assoc()) {
            $_SESSION['forgot_user_id'] = $user_data['id'];
            header('Location: login.php?action=forgot_questions');
            exit;
        } else {
            $forgot_message = 'Usuario no encontrado.';
            $action = 'forgot'; 
        }
        $stmt_user->close();
    // CASO 3.3: Recuperación de contraseña (Paso 2)
    } elseif (isset($_POST['forgot_password_step2'])) { 
        $user_id = $_SESSION['forgot_user_id'] ?? null;
        $answers = $_POST['answers'] ?? []; 
        
        if ($user_id && count($answers) === 2) {
            $all_correct = true;
            
            $stmt_security = $conn->prepare("SELECT pregunta1, respuesta1, pregunta2, respuesta2 FROM usuarios WHERE id = ?");
            $stmt_security->bind_param("i", $user_id);
            $stmt_security->execute();
            $result_security = $stmt_security->get_result();
            
            if ($security_data = $result_security->fetch_assoc()) {
                // Verificar pregunta1
                if (!isset($answers['pregunta1']) || !password_verify(trim($answers['pregunta1']), $security_data['respuesta1'])) {
                    $all_correct = false;
                }
                // Verificar pregunta2
                if ($all_correct && (!isset($answers['pregunta2']) || !password_verify(trim($answers['pregunta2']), $security_data['respuesta2']))) {
                    $all_correct = false;
                }
            } else {
                $all_correct = false; 
            }
            $stmt_security->close();

            if ($all_correct) {
                // si las preguntas son correctas, permitir cambiar la contraseña
                $_SESSION['password_reset_allowed_for_user'] = $user_id;
                unset($_SESSION['forgot_username']);
                unset($_SESSION['forgot_user_id']);
                header('Location: reset_password.php'); 
                exit;
            } else {
                $forgot_message = 'Una o más respuestas son incorrectas.';
                $action = 'forgot_questions'; 
            }
        } else {
            $forgot_message = 'Error procesando las respuestas. Intente de nuevo.';
            $action = 'forgot_questions';
        }
    }
}


$security_questions_for_user = [];
if ($action === 'forgot_questions' && isset($_SESSION['forgot_user_id'])) {
    $user_id_for_questions = $_SESSION['forgot_user_id'];
    $stmt_q = $conn->prepare("SELECT pregunta1, pregunta2 FROM usuarios WHERE id = ?");
    $stmt_q->bind_param("i", $user_id_for_questions);
    $stmt_q->execute();
    $result_q = $stmt_q->get_result();
    if($row_q = $result_q->fetch_assoc()){
        if (!empty($row_q['pregunta1'])) {
            $security_questions_for_user[] = ['question_field' => 'pregunta1', 'question_text' => $row_q['pregunta1']];
        }
        if (!empty($row_q['pregunta2'])) {
            $security_questions_for_user[] = ['question_field' => 'pregunta2', 'question_text' => $row_q['pregunta2']];
        }
    }
    $stmt_q->close();

    if(empty($security_questions_for_user)){
        $forgot_message = 'No hay preguntas de seguridad configuradas para este usuario. Contacte al administrador.';
        $action = 'forgot'; 
        unset($_SESSION['forgot_user_id']);
        unset($_SESSION['forgot_username']);
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - SENIAT</title>
    <link href= "http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="http://10.164.64.91:8080/sistema interno recaudacion/fontawesome/css/all.min.css">
    <style>
        body, html {
            
             /*height: 100%;
             width: 100%;*/
             background-image: url("./img/fachada.jpg");
             opacity: 0.9;
             background-position: center center;
             background-repeat: no-repeat;
             background-size: cover;
             background-attachment: fixed;


            /*background-color: #1a365d;*/
            /*color: #333;*/
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            /*background-image: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);*/
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
            background-color:rgb(26, 54, 93);
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
        .forgot-password-link, .back-to-login-link {
            display: block;
            margin-top: 15px;
            color: #6c757d;
            text-decoration: none;
        }
        .forgot-password-link:hover, .back-to-login-link:hover {
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
    </style>
</head>
<body>
    <div class="login-container">
        <img src="logos/seniat.png" alt="SENIAT Logo" class="logo img-fluid">

        <?php if ($action === 'forgot' || $action === 'forgot_questions'): ?>
            <h2>Recuperar Contraseña</h2>
            <?php if (!empty($forgot_message)): ?>
                <div class="alert alert-danger"><?php echo $forgot_message; ?></div>
            <?php endif; ?>

            <?php if ($action === 'forgot'): ?>
                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label for="username_for_forgot" class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="username_for_forgot" name="username_for_forgot" required>
                    </div>
                    <button type="submit" name="forgot_password_step1" class="btn btn-primary-seniat">Continuar</button>
                    <a href="login.php" class="back-to-login-link">Volver a Iniciar Sesión</a>
                </form>
            <?php elseif ($action === 'forgot_questions' && !empty($security_questions_for_user)): ?>
                   <p class="text-white-50 mb-3">Por favor, responda las siguientes preguntas de seguridad:</p>
                <form method="POST" action="login.php">
                    <?php foreach ($security_questions_for_user as $sq): ?>
                        <div class="mb-3">
                            <label for="answer_<?php echo $sq['question_field']; ?>" class="form-label"><?php echo htmlspecialchars($sq['question_text']); ?></label>
                            <input type="text" class="form-control" id="answer_<?php echo $sq['question_field']; ?>" name="answers[<?php echo $sq['question_field']; ?>]" required>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" name="forgot_password_step2" class="btn btn-primary-seniat">Verificar Respuestas</button>
                    <a href="login.php?action=forgot" class="back-to-login-link">Intentar con otro usuario</a>
                    <a href="login.php" class="back-to-login-link mt-2">Volver a Iniciar Sesión</a>
                </form>
            <?php elseif ($action === 'forgot_questions' && empty($security_questions_for_user) && empty($forgot_message)) : ?>
                <div class="alert alert-warning">No se encontraron preguntas de seguridad para <?php echo htmlspecialchars($_SESSION['forgot_username'] ?? 'este usuario'); ?>. Contacte al administrador.</div>
                <a href="login.php?action=forgot" class="back-to-login-link">Intentar con otro usuario</a>
                <a href="login.php" class="back-to-login-link mt-2">Volver a Iniciar Sesión</a>
            <?php endif; ?>

        <?php else: // Login por defecto ?>
            <h3 style="color:#000020">Iniciar Sesión</h3>
            <h5 class="title is-5 has-text-centered is-uppercase" style="color:#000020">SISCOIR V 1.0</h5>
        <h6 class="title is-4 has-text-centered is-uppercase" style="color:#000020">SISTEMA DE CONTROL INTERNO - RECAUDACIÓN</h6>
        <h9 class="title is-9 has-text-centered is-uppercase" style="color:#000020">ADUANA PRINCIPAL LAS PIEDRAS PARAGUANÁ</h9>
            <?php if (!empty($login_error)): ?>
                <div class="alert alert-danger"><?php echo $login_error; ?></div>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label for="username_or_email" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="username_or_email" name="username_or_email" placeholder="Usuario" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary-seniat" style="color:#FFFFFF">Ingresar</button>
                <a href="login.php?action=forgot" class="forgot-password-link" style="color:#000020">¿Olvidaste tu contraseña?</a>
            </form>
        <?php endif; ?>
    </div>

    <script src="http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>