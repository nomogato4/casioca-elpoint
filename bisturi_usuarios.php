<?php
include 'conexion.php';

echo "<div style='background:#030304; color:#fff; padding:40px; font-family:sans-serif;'>";
echo "<h2>🛠️ Cirugía Mayor: Tabla de Usuarios</h2>";

// Todas las columnas VIP que necesita El Point en los usuarios
$columnas_usuarios = [
    'saldo' => 'DECIMAL(15,2) DEFAULT 0',
    'bono' => 'DECIMAL(15,2) DEFAULT 0',
    'cbu' => 'VARCHAR(50)',
    'last_ip' => 'VARCHAR(45)',
    'ip_registro' => 'VARCHAR(45)',
    'codigo_referido' => 'VARCHAR(20)',
    'referido_por' => 'VARCHAR(50)',
    'saldo_cajero' => 'DECIMAL(15,2) DEFAULT 0',
    'ultimo_acceso' => 'DATETIME',
    'token_sesion' => 'VARCHAR(255)',
    'remember_token' => 'VARCHAR(255)',
    'tomado_por' => 'VARCHAR(50)',
    'escribiendo' => 'DATETIME',
    'fecha_creacion' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
];

foreach ($columnas_usuarios as $columna => $tipo) {
    $check = mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE '$columna'");
    if (mysqli_num_rows($check) == 0) {
        $sql = "ALTER TABLE usuarios ADD COLUMN $columna $tipo";
        if (mysqli_query($conexion, $sql)) {
            echo "<p style='color:#00ff88;'>✅ Columna '$columna' inyectada correctamente.</p>";
        } else {
            echo "<p style='color:#ff3366;'>❌ Error al inyectar '$columna': " . mysqli_error($conexion) . "</p>";
        }
    } else {
        echo "<p style='color:#6b7280;'>➡️ La columna '$columna' ya estaba lista.</p>";
    }
}

echo "<h3>🚀 ¡Listo el pollo! La base de datos está blindada.</h3>";
echo "<p>Ya podés volver al Dashboard y crear el usuario sin problemas.</p>";
echo "</div>";
?>