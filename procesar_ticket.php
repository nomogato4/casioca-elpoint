<?php
session_start();
include 'conexion.php';

// Si no está logueado, lo pateamos afuera
if (!isset($_SESSION['jugador'])) {
    header("Location: index.php");
    exit;
}

$jugador = $_SESSION['jugador'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $tipo = mysqli_real_escape_string($conexion, $_POST['tipo_ticket']);
    
    // 1. BLINDAJE ANTI-ESTAFAS: Filtramos números negativos y decimales raros
    if ($tipo == 'Carga') {
        $monto = isset($_POST['monto_ticket_carga']) ? intval(abs($_POST['monto_ticket_carga'])) : 0;
        $minimo = 1500;
    } else {
        $monto = isset($_POST['monto_ticket_retiro']) ? intval(abs($_POST['monto_ticket_retiro'])) : 0;
        $minimo = 3000;
    }

    // 2. DOBLE CHEQUEO DE MÍNIMOS
    if ($monto < $minimo) {
        $_SESSION['toast_error'] = "El mínimo de $tipo es de $$minimo.";
        header("Location: lobby.php");
        exit;
    }

    // Obtenemos los datos frescos del jugador
    $q_datos = mysqli_query($conexion, "SELECT saldo, bono, cbu FROM usuarios WHERE username = '$jugador'");
    $datos = mysqli_fetch_assoc($q_datos);
    $saldo_real = floatval($datos['saldo']);
    $saldo_bono = floatval($datos['bono']);
    $cbu_jugador = $datos['cbu'] ? $datos['cbu'] : 'No registrado';

    if ($tipo == 'Retiro') {
        // 5. CANDADO WAGER (Explicación clara)
        if ($saldo_bono > 0) {
            $_SESSION['toast_error'] = "⚠️ No podés retirar todavía. Tenés $$saldo_bono de Bono activo. Apostalo en los juegos para liberarlo.";
            header("Location: lobby.php");
            exit;
        }

        // 4. VERIFICADOR DE SALDO EN TIEMPO REAL
        if ($monto > $saldo_real) {
            $_SESSION['toast_error'] = "Saldo insuficiente. Querés retirar $$monto pero tenés $$saldo_real.";
            header("Location: lobby.php");
            exit;
        }
    }

    // 3 y 7. AUTO-LIMPIEZA DE BANDEJA Y SEGURO DE 1 CHAT
    // Borramos cualquier ticket "Pendiente" anterior de este jugador para no saturar al cajero
    mysqli_query($conexion, "DELETE FROM tickets WHERE usuario = '$jugador' AND estado = 'pendiente'");

    // 6. ALARMA DE BALLENA (VIP)
    $es_ballena = ($tipo == 'Carga' && $monto >= 50000) ? 1 : 0;
    $etiqueta = $es_ballena ? ' 🐳 VIP' : '';

    // CREAMOS EL TICKET LIMPIO
    $sql_ticket = "INSERT INTO tickets (usuario, tipo, monto, estado, fecha, ballena) VALUES ('$jugador', '$tipo', $monto, 'pendiente', NOW(), $es_ballena)";
    
    if (mysqli_query($conexion, $sql_ticket)) {
        
        // 8 y 10. DISPARO AUTOMÁTICO AL CHAT (Bot Cajero)
        if ($tipo == 'Carga') {
            $msg_bot = "🎟️ SISTEMA: Generaste un pedido de CARGA por $$monto$etiqueta. Por favor, subí la foto del comprobante acá abajo para acreditarte las fichas.";
        } else {
            $msg_bot = "💸 SISTEMA: Pediste un RETIRO de $$monto. Ya lo estamos procesando. \n🏦 Tu CBU actual: $cbu_jugador";
            // Bloqueamos la plata del jugador temporalmente para que no la gaste mientras espera el retiro
            mysqli_query($conexion, "UPDATE usuarios SET saldo = saldo - $monto WHERE username = '$jugador'");
        }

        mysqli_query($conexion, "INSERT INTO mensajes (de_usuario, para_usuario, mensaje, fecha) VALUES ('admin', '$jugador', '$msg_bot', NOW())");

        // 9. REDIRECCIÓN CON TOAST DE ÉXITO
        $_SESSION['toast_exito'] = "¡Ticket de $tipo generado con éxito!";
        header("Location: lobby.php");
        exit;
    } else {
        $_SESSION['toast_error'] = "Error de conexión. Intentá de nuevo.";
        header("Location: lobby.php");
        exit;
    }
} else {
    header("Location: lobby.php");
    exit;
}
?>