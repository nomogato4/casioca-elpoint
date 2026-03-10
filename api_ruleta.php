<?php
session_start();
include 'conexion.php';

header('Content-Type: application/json');

// 1. Validar que el jugador esté conectado
if (!isset($_SESSION['jugador'])) { 
    echo json_encode(['error' => 'No autorizado']); 
    exit; 
}

$user_session = mysqli_real_escape_string($conexion, $_SESSION['jugador']);

// 2. Recibir las apuestas desde el paño visual (Llegan en formato JSON)
// Ejemplo de lo que recibe: {"n_15": 100, "color_rojo": 500, "docena_1": 200}
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['apuestas'])) {
    echo json_encode(['error' => 'No hay apuestas']); 
    exit;
}

$apuestas = $input['apuestas'];
$total_apuesta = 0;

// Calcular cuánta plata apostó en total en la mesa
foreach ($apuestas as $monto) {
    $total_apuesta += floatval($monto);
}

if ($total_apuesta < 50) { 
    echo json_encode(['error' => 'La apuesta mínima total es de $50']); 
    exit; 
}

mysqli_begin_transaction($conexion);

try {
    // 3. Revisar la billetera del jugador y congelar el saldo
    $stmt = mysqli_prepare($conexion, "SELECT id, saldo, saldo_bono FROM usuarios WHERE username = ? FOR UPDATE");
    mysqli_stmt_bind_param($stmt, "s", $user_session);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $jugador = mysqli_fetch_assoc($res);

    if (!$jugador) throw new Exception('Usuario no encontrado');

    $saldo_real = floatval($jugador['saldo']);
    $saldo_bono = floatval($jugador['saldo_bono']);

    if ($saldo_real + $saldo_bono < $total_apuesta) { 
        throw new Exception('Saldo insuficiente para cubrir las fichas en la mesa.'); 
    }

    // 4. COBRAR LA APUESTA (Prioridad Wager / Bono)
    if ($saldo_bono >= $total_apuesta) {
        $saldo_bono -= $total_apuesta;
    } else {
        $restante = $total_apuesta - $saldo_bono;
        $saldo_bono = 0;
        $saldo_real -= $restante;
    }

    // 5. ¡NO VA MÁS! GIRAR LA BOLA (RNG 0 al 36)
    $numero_ganador = mt_rand(0, 36);

    // Definir la anatomía de la ruleta europea
    $rojos = [1,3,5,7,9,12,14,16,18,19,21,23,25,27,30,32,34,36];
    $negros = [2,4,6,8,10,11,13,15,17,20,22,24,26,28,29,31,33,35];
    
    $columna_1 = [1,4,7,10,13,16,19,22,25,28,31,34];
    $columna_2 = [2,5,8,11,14,17,20,23,26,29,32,35];
    $columna_3 = [3,6,9,12,15,18,21,24,27,30,33,36];

    $premio_total = 0;

    // 6. PASAR LA ESCOBA Y PAGAR A LOS GANADORES
    foreach ($apuestas as $tipo => $monto) {
        $monto = floatval($monto);
        
        // Apuesta a Número Pleno (Ej: n_15) -> Paga 36 veces (tu ficha + 35)
        if (strpos($tipo, 'n_') === 0) {
            $num_apuesta = intval(str_replace('n_', '', $tipo));
            if ($numero_ganador === $num_apuesta) $premio_total += $monto * 36;
        }
        
        // Solo evaluamos apuestas externas si no salió el Cero (El Cero se come todo el exterior)
        if ($numero_ganador !== 0) {
            // Colores -> Paga x2
            if ($tipo === 'color_rojo' && in_array($numero_ganador, $rojos)) $premio_total += $monto * 2;
            if ($tipo === 'color_negro' && in_array($numero_ganador, $negros)) $premio_total += $monto * 2;
            
            // Pares / Impares -> Paga x2
            if ($tipo === 'par' && $numero_ganador % 2 == 0) $premio_total += $monto * 2;
            if ($tipo === 'impar' && $numero_ganador % 2 != 0) $premio_total += $monto * 2;
            
            // Mitades (Menor/Mayor) -> Paga x2
            if ($tipo === 'mitad_1' && $numero_ganador >= 1 && $numero_ganador <= 18) $premio_total += $monto * 2;
            if ($tipo === 'mitad_2' && $numero_ganador >= 19 && $numero_ganador <= 36) $premio_total += $monto * 2;
            
            // Docenas -> Paga x3
            if ($tipo === 'docena_1' && $numero_ganador >= 1 && $numero_ganador <= 12) $premio_total += $monto * 3;
            if ($tipo === 'docena_2' && $numero_ganador >= 13 && $numero_ganador <= 24) $premio_total += $monto * 3;
            if ($tipo === 'docena_3' && $numero_ganador >= 25 && $numero_ganador <= 36) $premio_total += $monto * 3;
            
            // Columnas -> Paga x3
            if ($tipo === 'col_1' && in_array($numero_ganador, $columna_1)) $premio_total += $monto * 3;
            if ($tipo === 'col_2' && in_array($numero_ganador, $columna_2)) $premio_total += $monto * 3;
            if ($tipo === 'col_3' && in_array($numero_ganador, $columna_3)) $premio_total += $monto * 3;
        }
    }

    // 7. ENTREGAR PREMIOS Y GUARDAR
    if ($premio_total > 0) {
        $saldo_real += $premio_total;
    }

    $stmt_upd = mysqli_prepare($conexion, "UPDATE usuarios SET saldo = ?, saldo_bono = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt_upd, "ddi", $saldo_real, $saldo_bono, $jugador['id']);
    mysqli_stmt_execute($stmt_upd);

    mysqli_commit($conexion);

    // 8. DEVOLVER EL RESULTADO AL PAÑO VISUAL
    echo json_encode([
        'status' => 'ok',
        'numero' => $numero_ganador,
        'premio' => $premio_total,
        's_real' => number_format($saldo_real, 2, '.', ''),
        's_bono' => number_format($saldo_bono, 2, '.', '')
    ]);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(['error' => $e->getMessage()]);
}
?>