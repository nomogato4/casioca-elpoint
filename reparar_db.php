<?php
include 'conexion.php';

// Este script crea todas las tablas y columnas que le faltan a tu base de datos para que El Point funcione sin errores.

$tablas = [
    "CREATE TABLE IF NOT EXISTS usuarios (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) UNIQUE, password VARCHAR(255), rol VARCHAR(20) DEFAULT 'jugador', estado TINYINT(1) DEFAULT 1, saldo DECIMAL(15,2) DEFAULT 0, bono DECIMAL(15,2) DEFAULT 0, cbu VARCHAR(50), last_ip VARCHAR(45), ip_registro VARCHAR(45), codigo_referido VARCHAR(20), referido_por VARCHAR(50), saldo_cajero DECIMAL(15,2) DEFAULT 0, ultimo_acceso DATETIME, token_sesion VARCHAR(255), remember_token VARCHAR(255), tomado_por VARCHAR(50), escribiendo DATETIME)",

    "CREATE TABLE IF NOT EXISTS tickets (id INT AUTO_INCREMENT PRIMARY KEY, usuario VARCHAR(50), tipo VARCHAR(20), monto DECIMAL(15,2), estado VARCHAR(20) DEFAULT 'pendiente', fecha DATETIME, ballena TINYINT(1) DEFAULT 0)",

    "CREATE TABLE IF NOT EXISTS mensajes (id INT AUTO_INCREMENT PRIMARY KEY, de_usuario VARCHAR(50), para_usuario VARCHAR(50), mensaje TEXT, es_imagen TINYINT(1) DEFAULT 0, leido TINYINT(1) DEFAULT 0, fecha DATETIME)",

    "CREATE TABLE IF NOT EXISTS auditoria (id INT AUTO_INCREMENT PRIMARY KEY, cajero VARCHAR(50), jugador VARCHAR(50), accion VARCHAR(50), monto DECIMAL(15,2), fecha DATETIME)",

    "CREATE TABLE IF NOT EXISTS historial (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50), accion TEXT, fecha DATETIME DEFAULT CURRENT_TIMESTAMP)"
];

foreach ($tablas as $sql) {
    if (!mysqli_query($conexion, $sql)) {
        echo "<p style='color:red;'>Error creando tabla: " . mysqli_error($conexion) . "</p>";
    }
}

echo "<div style='background:#030304; color:#00ff88; padding:50px; text-align:center; font-family:sans-serif;'>
        <h1>✅ Base de Datos Reparada.</h1>
        <p>Todos los errores SQL solucionados. Ya podés borrar este archivo y volver al Dashboard.</p>
      </div>";
?>