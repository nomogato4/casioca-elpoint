<?php
include 'conexion.php';

echo "<div style='background:#030304; color:#fff; padding:40px; font-family:sans-serif;'>";
echo "<h2>🛠️ Operación de Base de Datos</h2>";

// 1. Forzamos la columna 'usuario' en la tabla tickets
$check_usuario = mysqli_query($conexion, "SHOW COLUMNS FROM tickets LIKE 'usuario'");
if (mysqli_num_rows($check_usuario) == 0) {
    mysqli_query($conexion, "ALTER TABLE tickets ADD COLUMN usuario VARCHAR(50) AFTER id");
    echo "<p style='color:#00ff88;'>✅ Columna 'usuario' inyectada en tickets.</p>";
} else {
    echo "<p style='color:#6b7280;'>➡️ La columna 'usuario' ya existe.</p>";
}

// 2. Forzamos la columna 'ballena' (para los VIPs) en tickets
$check_ballena = mysqli_query($conexion, "SHOW COLUMNS FROM tickets LIKE 'ballena'");
if (mysqli_num_rows($check_ballena) == 0) {
    mysqli_query($conexion, "ALTER TABLE tickets ADD COLUMN ballena TINYINT(1) DEFAULT 0");
    echo "<p style='color:#00ff88;'>✅ Columna 'ballena' inyectada en tickets.</p>";
}

// 3. Revisamos la tabla de auditoría por las dudas
$check_cajero = mysqli_query($conexion, "SHOW COLUMNS FROM auditoria LIKE 'cajero'");
if (mysqli_num_rows($check_cajero) == 0) {
    mysqli_query($conexion, "ALTER TABLE auditoria ADD COLUMN cajero VARCHAR(50) AFTER id");
    echo "<p style='color:#00ff88;'>✅ Columna 'cajero' inyectada en auditoria.</p>";
}

echo "<h3>🚀 ¡Operación exitosa! Ya podés volver al Dashboard.</h3>";
echo "</div>";
?>