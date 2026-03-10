<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['admin']) && !isset($_SESSION['cajero'])) {
    header("Location: auth.php");
    exit;
}

$rol = isset($_SESSION['admin']) ? 'admin' : 'cajero';
$es_admin = ($rol === 'admin');
$mi_usuario = $_SESSION[$rol];
$msg_express = "";

// =========================================================================
// 1. PROCESAR GESTIÓN RÁPIDA (CREAR Y CARGAR)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion_express'])) {
    $acc = $_POST['accion_express'];

    if ($acc == 'crear_jugador') {
        $u = mysqli_real_escape_string($conexion, trim($_POST['nuevo_user']));
        $p = password_hash($_POST['nueva_pass'], PASSWORD_DEFAULT);

        $check = mysqli_query($conexion, "SELECT id FROM usuarios WHERE username = '$u'");
        if (mysqli_num_rows($check) > 0) {
            $msg_express = "<div class='alert alert-error'>❌ El usuario ya existe. Elegí otro.</div>";
        } else {
            $sql_crear = "INSERT INTO usuarios (username, password, rol, estado, ip_registro, saldo, bono) VALUES ('$u', '$p', 'jugador', 1, 'creado_por_staff', 0, 0)";
            if (mysqli_query($conexion, $sql_crear)) {
                $msg_express = "<div class='alert alert-success'>✅ Jugador '$u' creado correctamente.</div>";
            } else {
                $msg_express = "<div class='alert alert-error'>❌ Error MySQL: " . mysqli_error($conexion) . "</div>";
            }
        }
    } elseif ($acc == 'carga_directa') {
        $u = mysqli_real_escape_string($conexion, trim($_POST['user_carga']));
        $m = floatval($_POST['monto_carga']);

        $check = mysqli_query($conexion, "SELECT id FROM usuarios WHERE username = '$u' AND rol = 'jugador'");
        if (mysqli_num_rows($check) == 0) {
            $msg_express = "<div class='alert alert-error'>❌ El jugador '$u' no existe.</div>";
        } else {
            if ($rol == 'cajero') {
                $q_stock = mysqli_query($conexion, "SELECT saldo_cajero FROM usuarios WHERE username = '$mi_usuario'");
                if ($q_stock) {
                    $stock = mysqli_fetch_assoc($q_stock)['saldo_cajero'];
                    if ($stock < $m) {
                        $msg_express = "<div class='alert alert-error'>❌ No tenés stock suficiente. Te quedan $$stock.</div>";
                    } else {
                        mysqli_query($conexion, "UPDATE usuarios SET saldo_cajero = saldo_cajero - $m WHERE username = '$mi_usuario'");
                        procesarCargaDirecta($conexion, $u, $m, $mi_usuario);
                        $msg_express = "<div class='alert alert-success'>✅ Carga de $$m enviada a '$u'.</div>";
                    }
                }
            } else {
                procesarCargaDirecta($conexion, $u, $m, $mi_usuario);
                $msg_express = "<div class='alert alert-success'>✅ Carga VIP de $$m enviada a '$u'.</div>";
            }
        }
    }
}

function procesarCargaDirecta($conexion, $user, $monto, $cajero)
{
    mysqli_query($conexion, "UPDATE usuarios SET saldo = saldo + $monto WHERE username = '$user'");
    mysqli_query($conexion, "INSERT INTO auditoria (cajero, jugador, accion, monto, fecha) VALUES ('$cajero', '$user', 'CARGA DIRECTA', $monto, NOW())");
}

// =========================================================================
// 2. FINANZAS GLOBALES (Solo para Admin)
// =========================================================================
$ganancia_neta = 0;
$total_cargas = 0;
$total_retiros = 0;
if ($es_admin) {
    $q_finanzas = mysqli_query($conexion, "SELECT SUM(CASE WHEN accion LIKE '%CARGA%' THEN monto ELSE 0 END) as cargas, SUM(CASE WHEN accion LIKE '%RETIRO%' THEN monto ELSE 0 END) as retiros FROM auditoria");
    if ($q_finanzas) {
        $finanzas = mysqli_fetch_assoc($q_finanzas);
        $total_cargas = $finanzas['cargas'] ?? 0;
        $total_retiros = $finanzas['retiros'] ?? 0;
        $ganancia_neta = $total_cargas - $total_retiros;
    }
}

// =========================================================================
// 3. LISTA DE JUGADORES
// =========================================================================
$q_jugadores = mysqli_query($conexion, "SELECT username, ip_registro, last_ip, saldo, bono, fecha_creacion FROM usuarios WHERE rol = 'jugador' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM y Gestión | EL POINT</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Roboto+Mono:wght@700&display=swap"
        rel="stylesheet">
    <style>
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

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 20px;
        }

        /* HEADER & ALERTAS */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 20px;
        }

        .logo {
            font-weight: 900;
            font-size: 20px;
            text-transform: uppercase;
        }

        .logo span {
            color: var(--blue);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 900;
            text-decoration: none;
            font-size: 11px;
            text-transform: uppercase;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(0, 255, 136, 0.1);
            color: var(--green);
            border: 1px solid var(--green);
        }

        .alert-error {
            background: rgba(255, 51, 102, 0.1);
            color: var(--red);
            border: 1px solid var(--red);
        }

        /* ACCIONES RÁPIDAS */
        .toolbar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            background: var(--panel);
            padding: 15px;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .btn-accion {
            flex: 1;
            padding: 15px;
            border-radius: 8px;
            font-weight: 900;
            text-transform: uppercase;
            cursor: pointer;
            border: none;
            font-size: 13px;
            transition: 0.3s;
        }

        .btn-crear {
            background: var(--green);
            color: #000;
        }

        .btn-cargar {
            background: var(--blue);
            color: #fff;
        }

        /* STATS ADMIN */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--panel);
            border: 1px solid var(--border);
            padding: 20px;
            border-radius: 12px;
        }

        .stat-title {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .stat-value {
            font-family: 'Roboto Mono', monospace;
            font-size: 24px;
            font-weight: 900;
        }

        .val-green {
            color: var(--green);
        }

        .val-red {
            color: var(--red);
        }

        .val-blue {
            color: var(--blue);
        }

        /* TABLA Y BUSCADOR */
        .table-container {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow-x: auto;
        }

        .buscador-caja {
            padding: 15px;
            border-bottom: 1px solid var(--border);
        }

        .input-dark {
            width: 100%;
            background: #000;
            border: 1px solid var(--border);
            padding: 12px;
            border-radius: 8px;
            color: #fff;
            font-size: 13px;
            outline: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: rgba(255, 255, 255, 0.02);
            padding: 15px;
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .btn-mini {
            background: rgba(112, 0, 255, 0.2);
            border: 1px solid var(--blue);
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 900;
        }

        /* MODALES */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 3000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content {
            background: var(--panel);
            border: 1px solid var(--border);
            padding: 30px;
            border-radius: 16px;
            width: 100%;
            max-width: 400px;
        }

        .btn-submit {
            width: 100%;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-weight: 900;
            cursor: pointer;
            text-transform: uppercase;
            margin-top: 10px;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="logo">EL <span>POINT</span> | CRM</div>
        <a href="dashboard.php" class="btn-outline">← Volver al Panel</a>
    </div>

    <?php echo $msg_express; ?>

    <div class="toolbar">
        <button class="btn-accion btn-crear" onclick="abrirModal('modalCrear')">⚡ Crear Nuevo Jugador</button>
        <button class="btn-accion btn-cargar" onclick="abrirModal('modalCargar', '')">💰 Carga Directa a
            Usuario</button>
    </div>

    <?php if ($es_admin): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Ingresos Brutos</div>
                <div class="stat-value val-blue">$<?php echo number_format($total_cargas, 2, ',', '.'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Egresos Pagados</div>
                <div class="stat-value val-red">-$<?php echo number_format($total_retiros, 2, ',', '.'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">PROFIT NETO</div>
                <div class="stat-value <?php echo ($ganancia_neta >= 0) ? 'val-green' : 'val-red'; ?>">
                    $<?php echo number_format($ganancia_neta, 2, ',', '.'); ?></div>
            </div>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <div class="buscador-caja">
            <input type="text" id="buscador" class="input-dark" placeholder="🔍 Buscar jugador por nombre o IP..."
                onkeyup="filtrarTabla()">
        </div>
        <table id="tablaJugadores">
            <thead>
                <tr>
                    <th>Acción</th>
                    <th>Jugador</th>
                    <th>Saldo Real</th>
                    <th>Wager (Bono)</th>
                    <th>IP Registro / Actual</th>
                    <th>Fecha Creación</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($q_jugadores) {
                    while ($row = mysqli_fetch_assoc($q_jugadores)):
                        ?>
                        <tr class="fila-jugador">
                            <td>
                                <button class="btn-mini"
                                    onclick="abrirModal('modalCargar', '<?php echo $row['username']; ?>')">CARGAR</button>
                            </td>
                            <td class="nombre-col" style="color:var(--green); text-transform:uppercase;">
                                <?php echo $row['username']; ?></td>
                            <td style="font-family:'Roboto Mono';">$<?php echo number_format($row['saldo'], 2, ',', '.'); ?>
                            </td>
                            <td>
                                <?php echo ($row['bono'] > 0) ? "<span style='color:var(--yellow);'>$" . number_format($row['bono'], 2, ',', '.') . "</span>" : "<span style='color:var(--text-muted);'>Limpio</span>"; ?>
                            </td>
                            <td class="ip-col" style="font-family:'Roboto Mono'; font-size:11px; color:var(--text-muted);">
                                R: <?php echo $row['ip_registro'] ?: '-'; ?><br>
                                A: <?php echo $row['last_ip'] ?: '-'; ?>
                            </td>
                            <td style="color:var(--text-muted); font-size:11px;">
                                <?php echo $row['fecha_creacion'] ? date('d/m/Y H:i', strtotime($row['fecha_creacion'])) : '-'; ?>
                            </td>
                        </tr>
                    <?php
                    endwhile;
                } else {
                    echo "<tr><td colspan='6' style='text-align:center;'>Error al cargar jugadores.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="modal-overlay" id="modalCrear">
        <div class="modal-content">
            <h3 style="margin-top:0;">⚡ Crear Jugador</h3>
            <form method="POST">
                <input type="hidden" name="accion_express" value="crear_jugador">
                <input type="text" name="nuevo_user" class="input-dark" placeholder="Nombre de Usuario"
                    style="margin-bottom:10px;" required>
                <input type="text" name="nueva_pass" class="input-dark" placeholder="Contraseña Inicial" value="123456"
                    required>
                <button type="submit" class="btn-submit" style="background:var(--green); color:#000;">Crear
                    Cuenta</button>
            </form>
            <button onclick="cerrarModal('modalCrear')"
                style="width:100%; background:transparent; border:none; color:var(--text-muted); margin-top:15px; cursor:pointer; font-weight:bold;">Cancelar</button>
        </div>
    </div>

    <div class="modal-overlay" id="modalCargar">
        <div class="modal-content">
            <h3 style="margin-top:0;">💰 Cargar Fichas</h3>
            <form method="POST">
                <input type="hidden" name="accion_express" value="carga_directa">
                <input type="text" name="user_carga" id="inputUserCarga" class="input-dark" placeholder="Usuario"
                    style="margin-bottom:10px;" required>
                <input type="number" name="monto_carga" class="input-dark" placeholder="$ Monto a cargar" required>
                <button type="submit" class="btn-submit" style="background:var(--blue); color:#fff;">Enviar
                    Fichas</button>
            </form>
            <button onclick="cerrarModal('modalCargar')"
                style="width:100%; background:transparent; border:none; color:var(--text-muted); margin-top:15px; cursor:pointer; font-weight:bold;">Cancelar</button>
        </div>
    </div>

    <script>
        function abrirModal(id, prefillUser = '') {
            document.getElementById(id).style.display = 'flex';
            if (id === 'modalCargar' && prefillUser !== '') {
                document.getElementById('inputUserCarga').value = prefillUser;
            }
        }
        function cerrarModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        // Buscador Anti-Lag en tiempo real
        function filtrarTabla() {
            let input = document.getElementById("buscador").value.toUpperCase();
            let filas = document.getElementsByClassName("fila-jugador");

            for (let i = 0; i < filas.length; i++) {
                let nombre = filas[i].getElementsByClassName("nombre-col")[0].innerText.toUpperCase();
                let ip = filas[i].getElementsByClassName("ip-col")[0].innerText.toUpperCase();
                if (nombre.indexOf(input) > -1 || ip.indexOf(input) > -1) {
                    filas[i].style.display = "";
                } else {
                    filas[i].style.display = "none";
                }
            }
        }
    </script>

</body>

</html>