<?php
// 1. Apagamos el reporte de errores visible para los jugadores (Anti-Hackers)
mysqli_report(MYSQLI_REPORT_OFF);

// 2. Configuración de la Base de Datos
$host = "p:localhost"; // "p:" activa la Conexión Persistente (Anti-Lag)
$user = "root";        // Usuario de la base de datos (XAMPP por defecto)
$pass = "";            // Contraseña (XAMPP por defecto suele estar vacía)
$db   = "elpoint";     // Nombre de tu base de datos

$conexion = false;
$max_reintentos = 3;
$intento = 0;

// 3. Sistema de Reintentos Automáticos (Retry Loop) y Timeout
while (!$conexion && $intento < $max_reintentos) {
    $conexion = mysqli_init();
    
    // 4. Timeout de 3 segundos (Si tarda más de 3 seg, corta la carga)
    mysqli_options($conexion, MYSQLI_OPT_CONNECT_TIMEOUT, 3); 
    
    // Intentamos conectar silenciosamente
    @mysqli_real_connect($conexion, $host, $user, $pass, $db);
    
    if (!$conexion) {
        $intento++;
        if ($intento < $max_reintentos) {
            usleep(500000); // Espera medio segundo (500ms) antes de reintentar
        }
    }
}

// 5. Log de Errores Oculto
if (!$conexion) {
    // Guardamos el error real en un archivo de texto en tu servidor, lejos del público
    $error_msg = "[" . date('Y-m-d H:i:s') . "] Error DB: " . mysqli_connect_error() . "\n";
    error_log($error_msg, 3, __DIR__ . "/errores_db_oculto.log");
    
    // Al jugador le mostramos una pantalla limpia VIP
    die("<div style='background:#030304; color:#ff3366; padding:20px; text-align:center; font-family:sans-serif; height:100vh; display:flex; align-items:center; justify-content:center; flex-direction:column;'>
            <h1 style='font-size:50px; margin:0;'>🛠️</h1>
            <h2 style='color:#fff;'>EL POINT está en mantenimiento.</h2>
            <p style='color:#6b7280;'>Estamos mejorando las mesas. Volvemos en unos minutos.</p>
         </div>");
}

// 6. Forzamos compatibilidad absoluta con Emojis en el chat (UTF8MB4)
mysqli_set_charset($conexion, "utf8mb4");

// 7. Sincronizamos la hora exacta de Argentina (Para Tickets, Auditoría y Cajeros)
date_default_timezone_set('America/Argentina/Buenos_Aires');
mysqli_query($conexion, "SET time_zone = '-03:00'");
?>