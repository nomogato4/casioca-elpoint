<?php
include 'conexion.php';

echo "<div style='background:#030304; color:#fff; padding:40px; font-family:sans-serif;'>";
echo "<h2>🛠️ Reparando Historial Financiero...</h2>";

// Creamos la tabla si por alguna razón no existe
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS auditoria (id INT AUTO_INCREMENT PRIMARY KEY)");

// Lista de columnas que necesita El Point para la contabilidad
$columnas = [
    'cajero' => 'VARCHAR(50)',
    'jugador' => 'VARCHAR(50)',
    'accion' => 'VARCHAR(50)',
    'monto' => 'DECIMAL(15,2)',
    'fecha' => 'DATETIME'
];

foreach ($columnas as $columna => $tipo) {
    $check = mysqli_query($conexion, "SHOW COLUMNS FROM auditoria LIKE '$columna'");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conexion, "ALTER TABLE auditoria ADD COLUMN $columna $tipo");
        echo "<p style='color:#00ff88;'>✅ Columna '$columna' inyectada correctamente.</p>";
    } else {
        echo "<p style='color:#6b7280;'>➡️ La columna '$columna' ya estaba lista.</p>";
    }
}

echo "<h3>🚀 ¡Listo! El historial.php ya debería funcionar 100% sin errores.</h3>";
echo "</div>";
?>