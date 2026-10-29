<?php
// Archivo para cerrar sesión y redirigir al login
session_start();

// PASO 1: Limpiar todas las variables de sesión
$_SESSION = array();

// PASO 2: Eliminar la cookie de sesión del navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// PASO 3: Destruir la sesión completamente
session_destroy();

// PASO 4: Redirigir al usuario a la página de login
header('Location: login.php');
exit;
?>