<?php
// --- CONFIGURACIÓN DE LA BASE DE DATOS ---
// IMPORTANTE: Cambiar estos valores según tu entorno de producción
$servername = "10.164.64.141";   // Dirección del servidor de base de datos
$username = "ngvasque";          // Usuario de la base de datos
$password = "Seniat2011";        // Contraseña del usuario
$dbname = "contabilidad";        // Nombre de la base de datos

// --- CREAR CONEXIÓN ---
// Intenta conectar con la base de datos usando los parámetros anteriores
$conn = new mysqli($servername, $username, $password, $dbname);

// --- VERIFICAR CONEXIÓN ---
// Si hay error en la conexión, muestra mensaje y detiene la ejecución
if ($conn->connect_error) {
    // En producción es mejor registrar este error en lugar de mostrarlo al usuario
    die("La conexión a la base de datos falló: " . $conn->connect_error);
}

// Establecer juego de caracteres a utf8 para soportar caracteres especiales
$conn->set_charset("utf8");
?>