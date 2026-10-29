<?php
// Este archivo se encarga de mostrar mensajes de feedback (éxito/error)
// basándose en los parámetros 'estado' y 'mensaje' de la URL ($_GET).
// Debe ser incluido en el archivo HTML/PHP principal donde quieres que aparezcan los mensajes.

// Asegurarse de que este archivo no sea accedido directamente
// if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
//     die('Acceso directo no permitido');
// }

// Verificar si hay un estado en la URL
if (isset($_GET['estado'])) {
    $estado = $_GET['estado'];
    $mensaje = '';
    $clase_alerta = '';

    if ($estado == 'exito') {
        $mensaje = '¡La planilla se guardó correctamente!';
        $clase_alerta = 'alert-success';
    } elseif ($estado == 'error') {
        // Decodificar y sanear el mensaje de error de la URL
        $mensaje_error_url = isset($_GET['mensaje']) ? urldecode($_GET['mensaje']) : 'Error desconocido al guardar la planilla.';
        // Usamos htmlspecialchars para prevenir XSS si el mensaje contiene código HTML
        $mensaje = 'Error al guardar la planilla: ' . htmlspecialchars($mensaje_error_url);
        $clase_alerta = 'alert-danger';
    }

    // Mostrar la alerta usando Bootstrap si hay un mensaje
    if (!empty($mensaje)) {
        // Bootstrap classes: alert, alert-*, alert-dismissible, fade, show
        echo '<div class="alert ' . $clase_alerta . ' alert-dismissible fade show" role="alert">';
        echo $mensaje;
        // Botón para cerrar la alerta (requiere Bootstrap JS)
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
}
?>