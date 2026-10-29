<?php
session_start();
require_once '../../config.php';
require_once '../../menu.php';

$mensaje_estado = "";

function getCiudades($conn) {
    $data = [];
    $sql = "SELECT `codigo`, `nombre` FROM `ciudades` ORDER BY `codigo` ASC";
    $result = $conn->query($sql);

    if ($result === false) {
        error_log("Error al obtener datos de la tabla ciudades: " . $conn->error);
        return [];
    }}

$ciudades = getCiudades($conn, 'codigo', 'nombre');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener y sanear datos del formulario
    $rif = trim($conn->real_escape_string($_POST['txt_rifconsig']));
    $nombreConsig = trim($conn->real_escape_string($_POST['txt_nombreConsig']));
    $representante = trim($conn->real_escape_string($_POST['txt_represent']));
    $siglas = trim($conn->real_escape_string($_POST['txt_siglasConsig']));
    $tipoContribuyente = trim($conn->real_escape_string($_POST['txt__selectTipo']));
    $direccion = trim($conn->real_escape_string($_POST['txt_direccion']));
    $nacionalidad = isset($_POST['text__selectNacionalidad']) ? trim($conn->real_escape_string($_POST['text__selectNacionalidad'])) : '';
    $ciudad = isset($_POST['text__selectCiudad']) ? trim($conn->real_escape_string($_POST['text__selectCiudad'])) : '';
    $codigoPostal = isset($_POST['text__selectCodigo']) ? trim($conn->real_escape_string($_POST['text__selectCodigo'])) : '';
    $referencia = isset($_POST['txt_referencia']) ? trim($conn->real_escape_string($_POST['txt_referencia'])) : '';
    $telefono = trim($conn->real_escape_string($_POST['txt__telefono']));
    $cir = isset($_POST['txt_cir']) ? trim($conn->real_escape_string($_POST['txt_cir'])) : '';
    // Verificar si el RIF ya existe
    $stmt_check = $conn->prepare("SELECT rif_consignatario FROM consignatarios WHERE LOWER(rif_consignatario) = LOWER(?)");
    $stmt_check->bind_param("s", $rif);
    $stmt_check->execute();
    $stmt_check->store_result();
    

    if ($stmt_check->num_rows > 0) {
        $mensaje_estado = "Error: Ya existe un consignatario con este RIF.";
    } else {
        // Insertar nuevo consignatario
        $stmt_insert = $conn->prepare("INSERT INTO consignatarios (rif_consignatario, nombre_consignatario, direccion, telefono, siglas, representante, tipo_contribuyente, cod_ciudad, referencia, zona_postal, CIR) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("sssssssssss", $rif, $nombreConsig, $direccion, $telefono, $siglas, $representante, $tipoContribuyente, $ciudad, $referencia, $codigoPostal, $cir);
        if ($stmt_insert->execute()) {
            $mensaje_estado = "Consignatario registrado con éxito.";
        } else {
            $mensaje_estado = "Error al registrar consignatario: " . $stmt_insert->error;
        }
        $stmt_insert->close();
    }
    $stmt_check->close();
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
        .form-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #0d6efd;
        }
        .form-section h5 {
            color: #0d6efd;
            margin-bottom: 20px;
        }
        .rif-input-group {
            display: flex;
            align-items: center;
        }
        .rif-input-group select {
            width: 80px;
            margin-right: 10px;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        .rif-type-select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            padding-right: 25px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-chevron-down' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0"><i class="fas fa-user-tie me-2"></i>Registro de Consignatario</h3>
            </div>
            
            <div class="card-body">
                <?php if ($mensaje_estado): ?>
                    <div class="alert alert-<?php echo (strpos($mensaje_estado, 'éxito') !== false ? 'success' : 'danger'); ?>">
                        <?php if (strpos($mensaje_estado, 'éxito') !== false): ?>
                            <i class="fas fa-check-circle me-2"></i><?php echo $mensaje_estado; ?>
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $mensaje_estado; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form id="formularioRegistroConsignatario" method="post" class="needs-validation" novalidate>
                    <div class="form-section">
                        <h5><i class="fas fa-info-circle me-2"></i>Datos del Consignatario</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="txt_rifconsig" class="form-label required-field">RIF</label>
                                <input type="text" class="form-control" id="txt_rifconsig" name="txt_rifconsig" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="txt_nombreConsig" class="form-label required-field">Nombre o Razón Social</label>
                                <input type="text" class="form-control" id="txt_nombreConsig" name="txt_nombreConsig" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="txt_represent" class="form-label required-field">Representante</label>
                                <input type="text" class="form-control" id="txt_represent" name="txt_represent" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="txt_siglasConsig" class="form-label">Siglas</label>
                                <input type="text" class="form-control" id="txt_siglasConsig" name="txt_siglasConsig">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="txt__selectTipo" class="form-label required-field">Tipo de Contribuyente</label>
                                <select class="form-select" id="txt__selectTipo" name="txt__selectTipo" required>
                                    <option value="">Seleccione</option>
                                    <option value="1">Natural</option>
                                    <option value="2">Jurídico</option>
                                    <option value="3">Gubernamental</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="txt_direccion" class="form-label required-field">Dirección</label>
                                <input type="text" class="form-control" id="txt_direccion" name="txt_direccion" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="text__selectNacionalidad" class="form-label">Nacionalidad</label>
                                <select class="form-select" id="text__selectNacionalidad" name="text__selectNacionalidad">
                                    <option value="">Seleccione</option>
                                    <option value="V">Venezolana</option>
                                    <option value="E">Extranjera</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="text__selectCiudad" class="form-label">Ciudad</label>
                                <select class="form-select"  id="text__selectCiudad" name="text__selectCiudad">
                                    <option value="">Seleccionar...</option>
                                    <option value="000000">BOLIVAR</option><option value="000001">PUNTO FIJO</option><option value="000002">TACHIRA</option>
                                    <option value="000003">CORO</option><option value="000004">TUCACAS</option><option value="000005">ANZOATEGUI</option>
                                    <option value="000006">CARACAS</option><option value="000007">VALENCIA</option><option value="000008">MARACAIBO</option>
                                    <option value="000009">LARA</option><option value="000010">ARAGUA</option><option value="000011">BARINAS</option>
                                    <option value="000012">MERIDA</option><option value="000013">SUCRE</option><option value="000014">GUARICO</option>
                                    <option value="000015">PORTUGUESA</option><option value="000016">MONAGAS</option><option value="000017">NUEVA ESPARTA</option>
                                    <option value="000018">MIRANDA</option><option value="000019">YARACUY</option><option value="000020">TRUJILLO</option>
                                    <option value="62548">MAIQUETIA</option><option value="158966">BARQUISIMETO</option><option value="158967">CIUDAD GUAYANA</option>
                                    <option value="158968">BARCELONA</option><option value="158969">MATURIN</option><option value="158970">MARACAY</option>
                                    <option value="158971">PETARE</option><option value="158972">TURMERO</option><option value="158973">CIUDAD BOLIVAR</option>
                                    <option value="158974">SANTA TERESA DEL TUY</option><option value="158975">CUMANA</option><option value="158976">SAN CRIDTOBAL</option>
                                    <option value="158977">BARUTA</option><option value="158978">PUERTO LA CRUZ</option><option value="158979">CABIMAS</option>
                                    <option value="158980">GUATIRE</option><option value="158981">CUA</option><option value="158982">GUARENAS</option>
                                    <option value="158983">LOS TEQUES</option><option value="158984">OCUMARE DEL TUY</option><option value="158985">PUERTO CABELLO</option>
                                    <option value=158986">GUACARA</option><option value="158987">EL TIGRE</option><option value="158988">EL LIMON</option>
                                    <option value="158989">ACARIGUA</option><option value="158990">CABUDARE</option><option value="158991">CHARALLAVE</option>
                                    <option value="158992">PALO NEGRO</option><option value="158993">CAGUA</option><option value="158994">ANACO</option>
                                    <option value="158995">CALABOZO</option><option value="158996">GUANARE</option><option value="158997">CARUPANO</option>
                                    <option value="158998">CATIA LA MAR</option><option value="159899">MARIARA</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="text__selectCodigo" class="form-label">Código Postal</label>
                                <input type="text" class="form-control" id="text__selectCodigo" name="text__selectCodigo">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="txt_referencia" class="form-label">Referencia</label>
                                <input type="text" class="form-control" id="txt_referencia" name="txt_referencia">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="txt__telefono" class="form-label required-field">Teléfono</label>
                                <input type="tel" class="form-control" id="txt__telefono" name="txt__telefono" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="txt_cir" class="form-label required-field">CIR</label>
                                <select class="form-select" id="text__selectcir" name="text__selectcir">
                                    <option value="">Seleccione</option>
                                    <option value="V">V</option>
                                    <option value="E">E</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Registrar Consignatario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Validación de formulario -->
    <script>
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>
<?php
// Cerrar la conexión
$conn->close();
?>