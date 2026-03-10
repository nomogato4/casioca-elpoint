<?php
session_start();
include 'conexion.php';

// Seguridad: Solo Staff
if (!isset($_SESSION['admin']) && !isset($_SESSION['cajero'])) {
    header("Location: auth.php");
    exit;
}

$rol = isset($_SESSION['admin']) ? 'admin' : 'cajero';

// OPTIMIZACIÓN: Traemos solo los últimos 100 tickets que sean exclusivamente de "Retiro"
$q_retiros = mysqli_query($conexion, "SELECT * FROM tickets WHERE tipo='Retiro' ORDER BY id DESC LIMIT 100");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Retiros | EL POINT</title>
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
        .badge-rol { padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 900; text-transform: uppercase; margin-left: 10px;}
        .badge-admin { background: rgba(255,51,102,0.1); color: var(--red); border: 1px solid var(--red); }
        .badge-cajero { background: rgba(112,0,255,0.1); color: var(--blue); border: 1px solid var(--blue); }

        .btn-volver { background: transparent; color: var(--text-muted); border: 1px solid var(--border); padding: 8px 12px; border-radius: 8px; text-decoration: none; font-size: 12px; font-weight: 900; transition: 0.3s; text-transform: uppercase;}
        .btn-volver:hover { color: #fff; border-color: #fff; }

        .main-content { padding: 30px 20px; max-width: 1000px; margin: 0 auto;}

        .seccion-titulo { color: #fff; font-size: 14px; font-weight: 900; text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; letter-spacing: 1px;}
        .seccion-titulo span { width: 8px; height: 8px; background: var(--red); border-radius: 50%; display: inline-block; box-shadow: 0 0 10px var(--red);}

        /* Tabla Optimizada */
        .table-container { background: var(--panel); border: 1px solid var(--border); border-radius: 12px; overflow-x: auto;}
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: rgba(255,255,255,0.02); color: var(--text-muted); padding: 15px; font-size: 11px; font-weight: 900; text-transform: uppercase; border-bottom: 1px solid var(--border); letter-spacing: 1px;}
        td { padding: 15px; border-bottom: 1px solid var(--border); font-size: 13px; font-weight: bold; color: #fff; vertical-align: middle;}
        tr:last-child td { border-bottom: none; }
        tr:hover { background: rgba(255,255,255,0.01); }

        .estado-badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 900; text-transform: uppercase;}
        .estado-aprobado { background: rgba(0,255,136,0.1); color: var(--green); border: 1px solid rgba(0,255,136,0.3);}
        .estado-rechazado { background: rgba(255,51,102,0.1); color: var(--red); border: 1px solid rgba(255,51,102,0.3);}
        .estado-pendiente { background: rgba(255,215,0,0.1); color: var(--yellow); border: 1px solid rgba(255,215,0,0.3);}
        
        .monto-retiro { font-family: 'Roboto Mono', monospace; font-size: 15px; color: var(--red);}
        .empty-state { padding: 40px; text-align: center; color: var(--text-muted); font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>

<nav class="topbar">
    <div style="display:flex; align-items:center;">
        <a href="#" class="logo">EL <span>POINT</span></a>
        <span class="badge-rol <?php echo ($rol == 'admin') ? 'badge-admin' : 'badge-cajero'; ?>">
            <?php echo strtoupper($rol); ?>
        </span>
    </div>
    <a href="dashboard.php" class="btn-volver">← VOLVER AL PANEL</a>
</nav>

<main class="main-content">
    
    <div class="seccion-titulo"><span></span> REGISTRO DE SALIDAS DE DINERO (RETIROS)</div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Fecha / Hora</th>
                    <th>Jugador</th>
                    <th>Monto Solicitado</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($q_retiros) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($q_retiros)): ?>
                        <tr>
                            <td style="color:var(--text-muted); font-family:'Roboto Mono', monospace; font-size: 12px;">
                                <?php echo date('d/m/Y - H:i', strtotime($row['fecha'])); ?>
                            </td>
                            <td style="font-family:'Roboto Mono', monospace; text-transform: uppercase;">
                                <?php echo htmlspecialchars($row['username']); ?>
                            </td>
                            <td class="monto-retiro">
                                $<?php echo number_format($row['monto'], 2); ?>
                            </td>
                            <td>
                                <?php if($row['estado'] == 'aprobado'): ?>
                                    <span class="estado-badge estado-aprobado">Pagado</span>
                                <?php elseif($row['estado'] == 'rechazado'): ?>
                                    <span class="estado-badge estado-rechazado">Rechazado</span>
                                <?php else: ?>
                                    <span class="estado-badge estado-pendiente">Pendiente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">No hay registros de retiros solicitados.</div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</main>

</body>
</html>