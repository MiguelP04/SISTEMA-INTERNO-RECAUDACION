<?php
// Página principal del sistema después del login
session_start();
require_once 'config.php';
require_once 'menu.php';

// PASO 1: Verificar si el usuario está logueado
// Si no hay sesión activa, redirigir al login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// PASO 2: Determinar si el usuario es administrador
$es_admin = ($_SESSION['rol'] === 'admin');

// FUNCIÓN AUXILIAR: Obtener total recaudado en un período
function obtenerTotalRecaudado($conn, $fechaInicio, $fechaCierre) {
    // Consulta SQL para sumar montos en un rango de fechas
    $sql = "SELECT SUM(monto2) as total FROM planillas_emitidas WHERE fecha_cancelacion BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fechaInicio, $fechaCierre);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['total'] ?? 0;  // Devolver 0 si no hay resultados
}

// PASO 3: Calcular totales para diferentes períodos
$totalDiario = obtenerTotalRecaudado($conn, date('Y-m-d'), date('Y-m-d'));
$totalSemanal = obtenerTotalRecaudado($conn, date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week')));
$totalMensual = obtenerTotalRecaudado($conn, date('Y-m-01'), date('Y-m-t'));
$totalAnual = obtenerTotalRecaudado($conn, date('Y-01-01'), date('Y-12-31'));
$calculoM = $totalMensual * 100;
$porcentajeMensual = $calculoM / 1039754154;
$calculoA = $totalAnual * 100;
$porcentajeAnual = $calculoA / 3917745562;    
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
        body { background-color: #f0f0f0; }
        .main-container { margin-top: 20px; }
        .welcome-card, .reports-card {
            background-color: #fff;
            border-left: 4px solid #0d6efd;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .report-option-card {
            border: 1px solid #dee2e6;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border:0 2px 4px rgba(0,0,0,0.1);
        }
        .report-option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .form-label { font-weight: 600; }
        .total-amount {
            font-size: 1.6rem; 
            font-weight: bold; 
            color: #ffffff; 
            background-color:rgb(34, 122, 255); 
            padding: 15px 20px; 
            border-radius: 8px; 
            margin: 15px 0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            text-align: center; 
        }

</style>
</head>
<body>
    <div class="container main-container">
        <div class="card welcome-card mb-4">
            <div class="card-body">
                <h1 class="card-title"></i>Bienvenido al Sistema de Control Interno de Recaudación</h1>
                <p class="card-text">Utilice las opciones a continuación para generar sus reportes.</p>
            </div>
        </div>

        <div class="card reports-card">
            <div class="card-header bg-light">
                <h3 class="mb-0"><i class="fas fa-chart-line me-2"></i>Generación de Reportes</h3>
            </div>
            <div class="card-body p-4">
                
                <div class="row text-center g-4">
                    <div class="col-md-3">
                        <div class="card report-option-card h-100">
                            <div class="card-body">
                                <i class="fas fa-calendar-day fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Reporte Diario</h5>
                                <p class="card-text">Genera reportes con los datos del día de hoy.</p>
                                <div class="total-amount">Bs <?php echo number_format($totalDiario, 2, ',', '.'); ?></div>
                                <div class="btn-group w-100">
                                    <a href="modulos/reportes/generar_reporte.php?periodo=diario&tipo=pagado&formato=pdf" class="btn btn-outline-danger"><i class="fas fa-file-pdf me-1"></i> PDF</a>
                                    <a href="generar_reporte.php?periodo=diario&tipo=pagado&formato=excel" class="btn btn-outline-success"><i class="fas fa-file-excel me-1"></i> Excel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card report-option-card h-100">
                            <div class="card-body">
                                <i class="fas fa-calendar-week fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Reporte Semanal</h5>
                                <p class="card-text">Genera reportes con los datos de la semana actual.</p>
                                <div class="total-amount">Bs <?php echo number_format($totalSemanal, 2, ',', '.'); ?></div>
                                <div class="btn-group w-100">
                                    <a href="modulos/reportes/generar_reporte.php?periodo=semanal&tipo=pagado&formato=pdf" class="btn btn-outline-danger"><i class="fas fa-file-pdf me-1"></i> PDF</a>
                                    <a href="modulos/reportes/generar_reporte.php?periodo=semanal&tipo=pagado&formato=excel" class="btn btn-outline-success"><i class="fas fa-file-excel me-1"></i> Excel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card report-option-card h-100">
                            <div class="card-body">
                                <i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Reporte Mensual</h5>
                                <p class="card-text">Genera reportes con los datos del mes actual.</p>
                                <div class="total-amount">Bs <?php echo number_format($totalMensual, 2, ',', '.'); ?> % <?php echo number_format(($porcentajeMensual), 2, ',', '.'); ?></div>
                                <div class="btn-group w-100">
                                    <a href="modulos/reportes/generar_reporte.php?periodo=mensual&tipo=pagado&formato=pdf" class="btn btn-outline-danger"><i class="fas fa-file-pdf me-1"></i> PDF</a>
                                    <a href="modulos/reportes/generar_reporte.php?periodo=mensual&tipo=pagado&formato=excel" class="btn btn-outline-success"><i class="fas fa-file-excel me-1"></i> Excel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card report-option-card h-100">
                            <div class="card-body">
                                <i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Reporte Anual</h5>
                                <p class="card-text">Genera reportes con los datos del año actual.</p>
                                <div class="total-amount">Bs <?php echo number_format($totalAnual, 2, ',', '.'); ?> % <?php echo number_format(($porcentajeAnual), 2, ',', '.'); ?></div>
                                <div class="btn-group w-100">
                                    <a href="modulos/reportes/generar_reporte.php?periodo=anual&tipo=pagado&formato=pdf" class="btn btn-outline-danger"><i class="fas fa-file-pdf me-1"></i> PDF</a>
                                    <a href="modulos/reportes/generar_reporte.php?periodo=anual&tipo=pagado&formato=excel" class="btn btn-outline-success" target="_blank"><i class="fas fa-file-excel me-1"></i> Excel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="my-4">

                <h5 class="mb-3">Reporte Personalizado</h5>
                <form action="modulos/reportes/generar_reporte.php" method="POST">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="fechaInicio" class="form-label">Fecha de Inicio:</label>
                            <input type="date" class="form-control" id="fechaInicio" name="fechaInicio" required>
                        </div>
                        <div class="col-md-3">
                            <label for="fechaCierre" class="form-label">Fecha de Cierre:</label>
                            <input type="date" class="form-control" id="fechaCierre" name="fechaCierre" required>
                        </div>
                        <div class="col-md-3">
                            <label for="tipoReporte" class="form-label">Tipo de Reporte:</label>
                            <select class="form-select" id="tipoReporte" name="tipoReporte" required>
                                <option value="liquidado" selected>Planillas Liquidadas</option>
                                <option value="pagado">Planillas Pagadas</option>
                                <option value="pagado_por_aduana">Planillas Pagadas Por Aduana</option>
                                <option value="planillas_sin_pagar">Planillas Sin Pagar</option>
                                <option value="derechos_pendientes">Planillas de Derechos Pendientes</option>
                                <option value="derechos_pendientes_pagados">Planillas de Derechos Pendientes Pagadas</option>
                                <option value="derechos_pendientes_sin_pagar">Planillas de Derechos Pendientes Sin Pagar</option>
                                <option value="auxicontable_liquidadas">Planillas Auxi Contable Liquidadas</option>
                                <option value="auxicontable_autoliquidadas">Planillas Auxi Contable Autoliquidadas</option>
                                <option value="reporte_cobranzas">Gestion de Cobranzas</option>
                            </select>
                        </div>       
                        <div class="col-md-3">
                            <div class="d-grid gap-2">
                                <button type="submit" name="formato" value="pdf" class="btn btn-danger"><i class="fas fa-file-pdf me-2"></i>Generar PDF</button>
                                <button type="submit" name="formato" value="excel" class="btn btn-success"><i class="fas fa-file-excel me-2"></i>Generar Excel</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="http://10.164.64.91:8080/sistema interno recaudacion/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    function animateValue(obj, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const currentValue = start + (end - start) * progress;
            obj.innerHTML = 'Bs ' + currentValue.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const totalDiarioElement = document.getElementById('animatedTotalDiario');
        if (totalDiarioElement) {
            const targetDiario = parseFloat(totalDiarioElement.dataset.target);
            animateValue(totalDiarioElement, 0, targetDiario, 2000); // 2000ms = 2 segundos
        }

        const totalSemanalElement = document.getElementById('animatedTotalSemanal');
        if (totalSemanalElement) {
            const targetSemanal = parseFloat(totalSemanalElement.dataset.target);
            animateValue(totalSemanalElement, 0, targetSemanal, 2000);
        }

        const totalMensualElement = document.getElementById('animatedTotalMensual');
        if (totalMensualElement) {
            const targetMensual = parseFloat(totalMensualElement.dataset.target);
            animateValue(totalMensualElement, 0, targetMensual, 2000);
        }
    });
</script>
</body>
</html>