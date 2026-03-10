</html>
<?php
session_start();
include 'conexion.php';

// Seguridad: Pueden entrar Vos y tus Cajeros (para rendirte la plata al final del día)
if (!isset($_SESSION['admin']) && !isset($_SESSION['cajero'])) {
    header("Location: auth.php");
    exit;
}

$rol = isset($_SESSION['admin']) ? 'admin' : 'cajero';

// Configuramos la zona horaria para que el "HOY" sea el de Argentina
date_default_timezone_set('America/Argentina/Buenos_Aires');
$hoy = date('Y-m-d');

// --- MOTOR FINANCIERO DIARIO ---
// 1. Fichas vendidas HOY
$q_cargas = mysqli_query($conexion, "SELECT SUM(monto) as total FROM tickets WHERE tipo='Carga' AND estado='aprobado' AND DATE(fecha) = '$hoy'");
$cargas_hoy = mysqli_fetch_assoc($q_cargas)['total'];
$cargas_hoy = $cargas_hoy ? $cargas_hoy : 0;

// 2. Premios pagados HOY
$q_retiros = mysqli_query($conexion, "SELECT SUM(monto) as total FROM tickets WHERE tipo='Retiro' AND estado='aprobado' AND DATE(fecha) = '$hoy'");
$retiros_hoy = mysqli_fetch_assoc($q_retiros)['total'];
$retiros_hoy = $retiros_hoy ? $retiros_hoy : 0;

// 3. Balance de la caja de HOY
$caja_hoy = $cargas_hoy - $retiros_hoy;

// 4. Detalle de los movimientos del día
$q_movimientos = mysqli_query($conexion, "SELECT * FROM tickets WHERE estado='aprobado' AND DATE(fecha) = '$hoy' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja | EL POINT</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Roboto+Mono:wght@700&display=swap"
        rel="stylesheet">
    <style>
        /* --- ESTÉTICA VIP - EL POINT --- */
        :root {
            --bg: #030304;
            --panel: #0a0c10;
            --border: #1a1e26;
            --green: #00ff88;
            --red: #ff3366;
            --blue: #7000ff;
            --text: #ffffff;
            --text-muted: #6b7280;
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 0;
        }

        /* Navbar Staff */
        .topbar {
            background: var(--panel);
            border-bottom: 1px solid var(--border);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.9);
        }

        .logo {
            color: #fff;
            font-weight: 900;
            font-size: 1.5rem;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: -1px;
        }

        .logo span {
            color: var(--blue);
            text-shadow: 0 0 15px rgba(112, 0, 255, 0.4);
        }

        .badge-rol {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            margin-left: 10px;
        }

        .badge-admin {
            background: rgba(255, 51, 102, 0.1);
            color: var(--red);
            border: 1px solid var(--red);
        }

        .badge-cajero {
            background: rgba(112, 0, 255, 0.1);
            color: var(--blue);
            border: 1px solid var(--blue);
        }

        .btn-volver {
            background: transparent;
            color: var(--text-muted);
            border: 1px solid var(--border);
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 900;
            transition: 0.3s;
            text-transform: uppercase;
        }

        .btn-volver:hover {
            color: #fff;
            border-color: #fff;
        }

        .main-content {
            padding: 30px 20px;
            max-width: 1000px;
            margin: 0 auto;
        }

        /* Ticket de Cierre */
        .ticket-cierre {
            background: var(--panel);
            border: 1px dashed var(--text-muted);
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 40px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.8);
            position: relative;
        }

        .ticket-cierre::before {
            content: '';
            position: absolute;
            top: -1px;
            left: 20px;
            right: 20px;
            height: 1px;
            box-shadow: 0 0 20px var(--blue);
        }

        .ticket-header {
            text-align: center;
            border-bottom: 1px dashed var(--border);
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .ticket-header h2 {
            font-size: 18px;
            font-weight: 900;
            text-transform: uppercase;
            margin: 0 0 5px 0;
            letter-spacing: 2px;
        }

        .ticket-header p {
            color: var(--text-muted);
            font-size: 12px;
            margin: 0;
            font-family: 'Roboto Mono', monospace;
        }

        .fila-monto {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 15px;
            font-weight: bold;
        }

        .fila-monto.ingreso {
            color: var(--green);
        }

        .fila-monto.egreso {
            color: var(--red);
        }

        .fila-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed var(--border);
            font-size: 20px;
            font-weight: 900;
        }

        .monto-valor {
            font-family: 'Roboto Mono', monospace;
        }

        .seccion-titulo {
            color: #fff;
            font-size: 14px;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 1px;
        }

        .seccion-titulo span {
            width: 8px;
            height: 8px;
            background: var(--text-muted);
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        /* Tabla de Movimientos */
        .table-container {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: rgba(255, 255, 255, 0.02);
            color: var(--text-muted);
            padding: 15px;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border);
            letter-spacing: 1px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            font-weight: bold;
            color: #fff;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.01);
        }

        .ticket-tipo {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .tipo-carga {
            background: rgba(0, 255, 136, 0.1);
            color: var(--green);
            border: 1px solid rgba(0, 255, 136, 0.3);
        }

        .tipo-retiro {
            background: rgba(255, 51, 102, 0.1);
            color: var(--red);
            border: 1px solid rgba(255, 51, 102, 0.3);
        }

        .empty-state {
            padding: 40px;
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: bold;
        }
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

        <div class="ticket-cierre">
            <div class="ticket-header">
                <h2>Liquidación Diaria</h2>
                <p>FECHA: <?php echo date('d/m/Y'); ?></p>
            </div>

            <div class="fila-monto ingreso">
                <span>[+] Ingresos por Cargas:</span>
                <span class="monto-valor">$<?php echo number_format($cargas_hoy, 2); ?></span>
            </div>

            <div class="fila-monto egreso">
                <span>[-] Retiros Pagados:</span>
                <span class="monto-valor">-$<?php echo number_format($retiros_hoy, 2); ?></span>
            </div>

            <div class="fila-total">
                <span>BALANCE DEL DÍA:</span>
                <span class="monto-valor"
                    style="color: <?php echo ($caja_hoy >= 0) ? 'var(--green)' : 'var(--red)'; ?>">
                    $<?php echo number_format($caja_hoy, 2); ?>
                </span>
            </div>
        </div>

        <div class="seccion-titulo"><span></span> OPERACIONES APROBADAS HOY</div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Jugador</th>
                        <th>Operación</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($q_movimientos) > 0): ?>
                        <?php while ($mov = mysqli_fetch_assoc($q_movimientos)): ?>
                            <tr>
                                <td style="color:var(--text-muted); font-family:'Roboto Mono', monospace; font-size: 12px;">
                                    <?php echo date('H:i', strtotime($mov['fecha'])); ?>
                                </td>
                                <td style="text-transform: uppercase;">
                                    <?php echo htmlspecialchars($mov['username']); ?>
                                </td>
                                <td>
                                    <span
                                        class="ticket-tipo <?php echo ($mov['tipo'] == 'Carga') ? 'tipo-carga' : 'tipo-retiro'; ?>">
                                        <?php echo strtoupper($mov['tipo']); ?>
                                    </span>
                                </td>
                                <td style="font-family:'Roboto Mono', monospace;">
                                    $<?php echo number_format($mov['monto'], 2); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">No hubo operaciones aprobadas en el día de hoy.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

</body>

</html>