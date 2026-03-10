<?php
session_start();
include 'conexion.php';

// Si no es jugador, no le damos información
if (!isset($_SESSION['jugador'])) {
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$jugador = $_SESSION['jugador'];

// Escudo activado para consultar el saldo
$stmt = mysqli_prepare($conexion, "SELECT saldo FROM usuarios WHERE username=?");
mysqli_stmt_bind_param($stmt, "s", $jugador);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($resultado)) {
    // Devolvemos el saldo en formato JSON para que el Lobby lo lea sin recargar la página
    echo json_encode(['saldo' => $row['saldo']]);
} else {
    echo json_encode(['saldo' => 0]);
}

mysqli_stmt_close($stmt);
?>