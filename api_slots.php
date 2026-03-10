<?php
session_start();
include 'conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['jugador'])) { echo json_encode(['error' => 'No autorizado']); exit; }

$user_session = mysqli_real_escape_string($conexion, $_SESSION['jugador']);
$apuesta = floatval($_POST['apuesta'] ?? 0);

if ($apuesta < 10 || $apuesta > 50000) { echo json_encode(['error' => 'Monto inválido']); exit; }

mysqli_begin_transaction($conexion);

try {
    $stmt = mysqli_prepare($conexion, "SELECT id, saldo, saldo_bono FROM usuarios WHERE username = ? FOR UPDATE");
    mysqli_stmt_bind_param($stmt, "s", $user_session);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $jugador = mysqli_fetch_assoc($res);

    if (!$jugador) throw new Exception('Usuario no encontrado');

    $saldo_real = floatval($jugador['saldo']);
    $saldo_bono = floatval($jugador['saldo_bono']);

    if ($saldo_real + $saldo_bono < $apuesta) { throw new Exception('Saldo insuficiente'); }

    // 1. COBRAMOS LA APUESTA (Gasta Wager primero)
    if ($saldo_bono >= $apuesta) {
        $saldo_bono -= $apuesta;
    } else {
        $restante = $apuesta - $saldo_bono;
        $saldo_bono = 0;
        $saldo_real -= $restante;
    }

    // 2. MATEMÁTICA EXTREMA (La casa es una aspiradora)
    $suerte = mt_rand(1, 1000); 
    $multiplicador = 0;
    $simbolos = ['🍒', '🍋', '🔔', '💎', '🦉', '7️⃣'];

    if ($suerte <= 820) {
        // 82.0% - PIERDE TODO (Cruel y constante)
        $resultado = [$simbolos[mt_rand(0,5)], $simbolos[mt_rand(0,5)], $simbolos[mt_rand(0,5)]];
        while($resultado[0] == $resultado[1] && $resultado[1] == $resultado[2]) { $resultado[2] = $simbolos[mt_rand(0,5)]; }
    } elseif ($suerte <= 960) {
        // 14.0% - EMPATE x1 (La famosa "esperanza" sin perder plata tuya)
        $multiplicador = 1;
        $s = mt_rand(0,1) ? '🍒' : '🍋';
        $resultado = [$s, $s, $s];
    } elseif ($suerte <= 990) {
        // 3.0% - PREMIO CHICO x2 (Para que no se aburra)
        $multiplicador = 2; $resultado = ['🔔', '🔔', '🔔'];
    } elseif ($suerte <= 998) {
        // 0.8% - PREMIO MEDIANO x5 (Casi un milagro)
        $multiplicador = 5;
        $s = mt_rand(0,1) ? '💎' : '🦉';
        $resultado = [$s, $s, $s];
    } else {
        // 0.2% - JACKPOT x10 (Para la foto promocional)
        $multiplicador = 10; $resultado = ['7️⃣', '7️⃣', '7️⃣'];
    }

    $premio = $apuesta * $multiplicador;

    // 3. PAGAMOS EL PREMIO
    if ($premio > 0) { $saldo_real += $premio; }

    // 4. GUARDAMOS LOS SALDOS EN LA BÓVEDA
    $stmt_upd = mysqli_prepare($conexion, "UPDATE usuarios SET saldo = ?, saldo_bono = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt_upd, "ddi", $saldo_real, $saldo_bono, $jugador['id']);
    mysqli_stmt_execute($stmt_upd);

    mysqli_commit($conexion);

    echo json_encode([
        'status' => 'ok',
        'simbolos' => $resultado,
        'premio' => $premio,
        'multiplicador' => $multiplicador,
        's_real' => number_format($saldo_real, 2, '.', ''),
        's_bono' => number_format($saldo_bono, 2, '.', '')
    ]);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(['error' => $e->getMessage()]);
}
?>