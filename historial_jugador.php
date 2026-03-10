<?php
session_start();
include 'conexion.php';

// Si no está logueado, lo pateamos
if (!isset($_SESSION['jugador'])) {
    header("Location: login_jugador.php");
    exit;
}

$username = mysqli_real_escape_string($conexion, $_SESSION['jugador']);

// Buscamos el ID y el saldo del jugador
$sql = "SELECT id, saldo, estado FROM usuarios WHERE username = '$username'";
$resultado = mysqli_query($conexion, $sql);
$jugador = mysqli_fetch_assoc($resultado);

// Patovica
if (!$jugador || $jugador['estado'] == 0) {
    header("Location: logout_jugador.php");
    exit;
}

$id_jugador = $jugador['id'];

// Traemos SOLO los movimientos de este jugador (los últimos 50)
$query_historial = "SELECT * FROM historial_transacciones WHERE id_usuario = $id_jugador ORDER BY id DESC LIMIT 50";
$res_historial = mysqli_query($conexion, $query_historial);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Mi Historial | Mi Apuesta</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Roboto+Mono:wght@700&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #030406; --panel: #0f131a; --green: #2ecc71; --red: #e74c3c; --blue: #3498db; --border: #1f2530; --text-muted: #8b949e; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: #c9d1d9; margin: 0; padding-bottom: 50px; }
        
        /* NAVBAR SIMPLE PARA SUBPÁGINAS */
        .navbar { background: var(--panel); border-bottom: 1px solid var(--border); padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .btn-volver { background: #161b22; color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 8px; font-size: 12px; font-weight: 800; display: flex; align-items: center; gap: 8px; border: 1px solid #30363d; transition: 0.2s; }
        .btn-volver:hover { background: #30363d; }
        .saldo-box { background: #000; border: 1px solid var(--border); padding: 5px 12px; border-radius: 8px; display: flex; flex-direction: column; align-items: flex-end; }
        .saldo-lbl { font-size: 9px; color: var(--text-muted); font-weight: 800; text-transform: uppercase; }
        .saldo-val { font-family: 'Roboto Mono', monospace; font-size: 14px; font-weight: 900; color: var(--green); }

        .container { padding: 25px 20px; max-width: 800px; margin: 0 auto; }
        .titulo-seccion { font-size: 18px; font-weight: 900; margin-bottom: 20px; color: #fff; display: flex; align-items: center; gap: 10px; }
        
        /* TABLA MÓVIL (En celular no se ven como tabla, se ven como tarjetitas) */
        .historial-list { display: flex; flex-direction: column; gap: 12px; }
        .mov-card { background: var(--panel); border: 1px solid var(--border); padding: 15px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; }
        .mov-info { display: flex; align-items: center; gap: 15px; }
        .mov-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .icon-in { background: rgba(46, 204, 113, 0.1); color: var(--green); }
        .icon-out { background: rgba(231, 76, 60, 0.1); color: var(--red); }
        
        .mov-detalle { display: flex; flex-direction: column; gap: 4px; }
        .mov-tipo { font-size: 14px; font-weight: 800; color: #fff; text-transform: uppercase; }
        .mov-fecha { font-size: 11px; color: var(--text-muted); font-weight: 600; font-family: 'Roboto Mono', monospace; }
        
        .mov-monto { font-size: 16px; font-weight: 900; font-family: 'Roboto Mono', monospace; }
        .monto-in { color: var(--green); }
        .monto-out { color: var(--red); }

        .sin-datos { text-align: center; padding: 40px 20px; color: var(--text-muted); font-size: 14px; background: var(--panel); border-radius: 12px; border: 1px dashed var(--border); }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="lobby.php" class="btn-volver">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            VOLVER
        </a>
        <div class="saldo-box">
            <span class="saldo-lbl">Créditos</span>
            <span class="saldo-val">$<?php echo number_format($jugador['saldo'], 2); ?></span>
        </div>
    </nav>

    <div class="container">
        <div class="titulo-seccion">
            <svg viewBox="0 0 24 24" width="22" height="22" stroke="var(--blue)" stroke-width="2" fill="none"><polyline points="12 8 12 12 14 14"></polyline><circle cx="12" cy="12" r="10"></circle></svg>
            Historial de Movimientos
        </div>

        <div class="historial-list">
            <?php if(mysqli_num_rows($res_historial) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($res_historial)): 
                    $es_ingreso = ($row['monto'] > 0);
                    $monto_formateado = number_format(abs($row['monto']), 2);
                    $fecha_formateada = date("d/m/Y - H:i", strtotime($row['fecha']));
                ?>
                <div class="mov-card">
                    <div class="mov-info">
                        <?php if($es_ingreso): ?>
                            <div class="mov-icon icon-in">
                                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="3" fill="none"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                            </div>
                            <div class="mov-detalle">
                                <span class="mov-tipo">Carga de Fichas</span>
                                <span class="mov-fecha"><?php echo $fecha_formateada; ?></span>
                            </div>
                        <?php else: ?>
                            <div class="mov-icon icon-out">
                                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="3" fill="none"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                            </div>
                            <div class="mov-detalle">
                                <span class="mov-tipo">Retiro Solicitado</span>
                                <span class="mov-fecha"><?php echo $fecha_formateada; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mov-monto <?php echo $es_ingreso ? 'monto-in' : 'monto-out'; ?>">
                        <?php echo $es_ingreso ? '+' : '-'; ?> $<?php echo $monto_formateado; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="sin-datos">
                    <svg viewBox="0 0 24 24" width="40" height="40" stroke="currentColor" stroke-width="1" fill="none" style="margin-bottom:10px; opacity:0.5;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg><br>
                    Aún no tenés movimientos registrados.
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>