<?php
ob_start();
session_start();

require_once '../../config.php';
require_once '../../menu.php';
// --- Autenticacion de rol ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['rol'] !== 'admin') {
    echo "<div class='container mt-3'><div class='alert alert-danger'>Acceso denegado. Esta sección es solo para administradores.</div></div>";
    exit;
}

$mensaje = $_SESSION['mensaje_usuarios'] ?? ''; 
unset($_SESSION['mensaje_usuarios']);



// Para borrar o desactivar usuario
if (isset($_GET['action']) && $_GET['action'] == 'toggle_active' && isset($_GET['id'])) { 
    $user_id_to_toggle = intval($_GET['id']);
    if ($user_id_to_toggle == $_SESSION['user_id']) { 
        $_SESSION['mensaje_usuarios'] = "<div class='alert alert-danger'>No puede desactivar/activar su propia cuenta.</div>";
    } else {
        //Cambiar estatus
        $stmt_check_active = $conn->prepare("SELECT activo FROM usuarios WHERE id = ?"); 
        $stmt_check_active->bind_param("i", $user_id_to_toggle);
        $stmt_check_active->execute();
        $current_status = $stmt_check_active->get_result()->fetch_assoc()['activo']; 
        $stmt_check_active->close();

        $new_status = $current_status ? 0 : 1; 
        $action_text = $new_status ? "activado" : "desactivado";

        $stmt_update_active = $conn->prepare("UPDATE usuarios SET activo = ? WHERE id = ?"); 
        $stmt_update_active->bind_param("ii", $new_status, $user_id_to_toggle);
        if ($stmt_update_active->execute()) {
            $_SESSION['mensaje_usuarios'] = "<div class='alert alert-success'>Usuario ".htmlspecialchars($action_text)." con éxito.</div>";
        } else {
            $_SESSION['mensaje_usuarios'] = "<div class='alert alert-danger'>Error al ".htmlspecialchars($action_text)." el usuario.</div>";
        }
        $stmt_update_active->close();
    }
    header('Location: gestion_usuarios.php');
    exit;
}


// Buscar usuarios en la base de datos
$users = [];

$result = $conn->query("SELECT id, username, rol, activo, fecha_creacion FROM usuarios ORDER BY username ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
$conn->close();
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
        body { background-color: #f0f2f5; }
        .container { max-width: 1000px; }
        .card-header h3 { margin-bottom: 0; }
        .table th { background-color: #e9ecef; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3><i class="fas fa-users-cog me-2"></i>Gestión de Usuarios</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($mensaje)) { echo $mensaje; } ?>

                <div class="mb-3 text-end">
                    <a href="agregar_usuario.php" class="btn btn-success"><i class="fas fa-user-plus me-2"></i>Agregar Nuevo Usuario</a>
                </div>

                <?php if (!empty($users)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Creado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($user['rol'])); ?></td> <td>
                                    <?php if ($user['activo']): ?> <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date("d/m/Y H:i", strtotime($user['fecha_creacion'])); ?></td> <td>
                                    <a href="editar_usuario.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" title="Editar"><i class="fas fa-edit"></i></a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): // Admin cannot delete/deactivate themselves this way ?>
                                        <a href="gestion_usuarios.php?action=toggle_active&id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm <?php echo $user['activo'] ? 'btn-danger' : 'btn-success'; ?>" 
                                           title="<?php echo $user['activo'] ? 'Desactivar' : 'Activar'; ?>"
                                           onclick="return confirm('¿Está seguro de <?php echo $user['activo'] ? 'DESACTIVAR' : 'ACTIVAR'; ?> este usuario?');">
                                           <i class="fas <?php echo $user['activo'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                        </a>
                                    <?php endif; ?>
                                     <a href="../reset_password_admin.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="Resetear Contraseña"><i class="fas fa-key"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center">No hay usuarios registrados.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>