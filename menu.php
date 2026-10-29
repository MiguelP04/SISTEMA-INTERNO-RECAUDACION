<?php
// Barra de navegación superior del sistema
require_once 'config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Determinar si es administrador
$es_admin = ($_SESSION['rol'] === 'admin');

// Definir ruta base
$base_path = '/SISTEMA INTERNO RECAUDACION';
?>
<!-- Barra de navegación -->
<img src="./img/ministerio.jpg" width="100%" height="55">
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #1a365d;">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_path; ?>/index.php"><i class="fas fa-home me-2"></i> Inicio</a>
                </li>
                
                <?php if ($es_admin): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-cog me-1"></i> Administración
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>/modulos/administracion de usuarios/gestion_usuarios.php"><i class="fas fa-users-cog me-1"></i> Usuarios</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="consultarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-search me-1"></i> Consulta
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="consultarDropdown">
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>/modulos/consultas/consulta_ca.php"><i class="fas fa-users me-1"></i> Agentes/Consignatarios</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>/modulos/consultas/edit_formulario_planilla.php"><i class="fas fa-file-alt me-1"></i> Planillas</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="registrosDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-edit me-1"></i> Registro
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="registrosDropdown">
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>/modulos/registros/registro_agente.php"><i class="fas fa-user-shield me-1"></i> Agentes Aduanales</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>/modulos/registros/registro_consignatario.php"><i class="fas fa-user-tie me-1"></i> Consignatarios</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>/modulos/registros/formulario_planilla.php"><i class="fas fa-file-alt me-1"></i> Planillas</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>/modulos/preguntas de seguridad/cambiar_preguntas.php"><i class="fas fa-shield-alt me-1"></i> Preguntas de Seguridad</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

</nav>

<style>
    .navbar {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .dropdown-menu {
        border: none;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .dropdown-item:hover {
        background-color: #f8f9fa;
        color: #1a365d;
    }
    
    .navbar-nav .nav-link {
        font-weight: 500;
        padding: 0.5rem 1rem;
    }
    
    .navbar-brand {
        padding: 0;
    }
</style>