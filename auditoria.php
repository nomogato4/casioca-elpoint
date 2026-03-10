<?php
session_start();
include 'conexion.php';

// SEGURIDAD EXTREMA: Solo vos (el Admin) podés ver la plata del casino. Los cajeros rebotan.
if (!isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit;
}

// --- MOTOR DE CÁLCULO FINANCIERO (Ultra Optimizado) ---
// 1. Total de plata que entró (Cargas aprobadas)
$q_ingresos = mysqli_query($conexion, "SELECT SUM(monto) as total FROM tickets WHERE tipo='Carga' AND estado='aprobado'");
$ingresos = mysqli_fetch_assoc($q_ingresos)['total'];
$ingresos = $ingresos ? $ingresos : 0;

// 2. Total de plata que salió (Retiros aprobados)
$q_retiros = mysqli_query($conexion, "SELECT SUM(monto) as total FROM tickets WHERE tipo='Retiro' AND estado='aprobado'");
$retiros = mysqli_fetch_assoc($q_retiros)['total'];
$retiros = $retiros ? $retiros : 0;

// 3. Ganancia Neta del Casino (Lo que te queda en el bolsillo)
$ganancia_neta = $ingresos - $retiros;

// 4. Radar de Ballenas: Top 5 jugadores con más fichas en este momento
$q_top_jugadores = mysqli_query($conexion, "SELECT username, saldo FROM usuarios WHERE rol='jugador' ORDER BY saldo DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría Financiera | EL POINT</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Roboto+Mono:wght@700&display=swap" rel="stylesheet">
    <style>
        /* --- ESTÉTICA VIP - EL POINT --- */
        :root { 
            --bg: #030304; 
            --panel: #0a0c10; 
            --border: #1a1e26; 
            --green: #00ff88; 
            --red: #ff3366; 
            --blue: #7000ff; 
            --yellow: #ffd700;
            --text: #ffffff; 
            --text-muted: #6b7280;
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 0;}

        /* Navbar Staff */
        .topbar { background: var(--panel); border-bottom: 1px solid var(--border); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; box-shadow: 0 10px 30px rgba(0,0,0,0.9);}
        .logo { color: #fff; font-weight: 900; font-size: 1.5rem; text-decoration: none; text-transform: uppercase; letter-spacing: -1px;} 
        .logo span { color: var(--blue); text-shadow: 0 0 15px rgba(112,0,255,0.4); }
        .badge-admin { background: rgba(255,51,102,0.1); color: var(--red); border: 1px solid var(--red); padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 900; text-transform: uppercase; margin-left: 10px;}

        .btn-volver { background: transparent; color: var(--text-muted); border: 1px solid var(--border); padding: 8px 12px; border-radius: 8px; text-decoration: none; font-size: 12px; font-weight: 900; transition: 0.3s; text-transform: uppercase;}
        .btn-volver:hover { color: #fff; border-color: #fff; }

        .main-content { padding: 30px 20px; max-width: 1000px; margin: 0 auto;}

        /* Grilla Financiera */
        .finance-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .finance-card { background: var(--panel); border: 1px solid var(--border); padding: 30px; border-radius: 12px; display: flex; flex-direction: column; gap: 10px; position: relative; overflow: hidden; box-shadow: 0 15px 30px rgba(0,0,0,0.6);}
        
        .finance-card.ingresos { border-top: 4px solid var(--green); }
        .finance-card.ingresos .f-value { color: var(--green); text-shadow: 0 0 20px rgba(0,255,136,0.3); }
        
        .finance-card.retiros { border-top: 4px solid var(--red); }
        .finance-card.retiros .f-value { color: var(--red); text-shadow: 0 0 20px rgba(255,51,102,0.3); }
        
        .finance-card.neta { border-top: 4px solid var(--blue); background: linear-gradient(180deg, rgba(112,0,255,0.05) 0%, var(--panel) 100%);}
        .finance-card.neta .f-value { color: #fff; text-shadow: 0 0 20px rgba(112,0,255,0.5); font-size: 36px;}

        .f-title { color: var(--text-muted); font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;}
        .f-value { font-family: 'Roboto Mono', monospace; font-size: 28px; font-weight: 900; }

        .seccion-titulo { color: #fff; font-size: 14px; font-weight: 900; text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; letter-spacing: 1px;}
        .seccion-titulo span { width: 8px; height: 8px; background: var(--yellow); border-radius: 50%; display: inline-block; box-shadow: 0 0 10px var(--yellow);}

        /* Tabla de Ballenas */
        .table-container { background: var(--panel); border: 1px solid var(--border); border-radius: 12px; overflow-x: auto;}
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: rgba(255,255,255,0.02); color: var(--text-muted); padding: 15px; font-size: 11px; font-weight: 900; text-transform: uppercase; border-bottom: 1px solid var(--border); letter-spacing: 1px;}
        td { padding: 15px; border-bottom: 1px solid var(--border); font-size: 13px; font-weight: bold; color: #fff; vertical-align: middle;}
        tr:last-child td { border-bottom: none; }
        tr:hover { background: rgba(255,255,255,0.01); }
        
        .saldo-ballena { font-family: 'Roboto Mono', monospace; color: var(--yellow); font-size: 16px;}
        .empty-state { padding: 40px; text-align: center; color: var(--text-muted); font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>

<nav class="topbar">
    <div style="display:flex; align-items:center;">
        <a href="#" class="logo">EL <span>POINT</span></a>
        <span class="badge-admin">DUEÑO</span>
    </div>
    <a href="dashboard.php" class="btn-volver">← VOLVER AL PANEL</a>
</nav>

<main class="main-content">
    
    <div class="finance-grid">
        <div class="finance-card ingresos">
            <div class="f-title">Total Ingresos (Cargas)</div>
            <div class="f-value">$<?php echo number_format($ingresos, 2); ?></div>
        </div>
        <div class="finance-card retiros">
            <div class="f-title">Total Pagado (Retiros)</div>
            <div class="f-value">-$<?php echo number_format($retiros, 2); ?></div>
        </div>
        <div class="finance-card neta">
            <div class="f-title">Ganancia Neta del Casino</div>
            <div class="f-value">$<?php echo number_format($ganancia_neta, 2); ?></div>
        </div>
    </div>

    <div class="seccion-titulo"><span></span> RADAR: TOP 5 JUGADORES CON MÁS FICHAS</div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Usuario del Jugador</th>
                    <th>Saldo Actual en Cuenta</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if(mysqli_num_rows($q_top_jugadores) > 0): 
                    $pos = 1;
                    while($jugador = mysqli_fetch_assoc($q_top_jugadores)): 
                ?>
                        <tr>
                            <td style="color:var(--text-muted); font-size: 16px;">#<?php echo $pos; ?></td>
                            <td style="font-family:'Roboto Mono', monospace; font-size: 14px; text-transform: uppercase;">
                                <?php echo htmlspecialchars($jugador['username']); ?>
                            </td>
                            <td class="saldo-ballena">
                                $<?php echo number_format($jugador['saldo'], 2); ?>
                            </td>
                        </tr>
                <?php 
                    $pos++;
                    endwhile; 
                else: 
                ?>
                    <tr>
                        <td colspan="3">
                            <div class="empty-state">Todavía no hay jugadores registrados en el casino.</div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</main>

</body>
</html>