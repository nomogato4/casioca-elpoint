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
// GESTIÓN RÁPIDA (CREAR JUGADORES Y CARGAR SALDO SIN TICKET)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion_express'])) {
    $acc = $_POST['accion_express'];

    if ($acc == 'crear_jugador') {
        $u = mysqli_real_escape_string($conexion, trim($_POST['nuevo_user']));
        $p = password_hash($_POST['nueva_pass'], PASSWORD_DEFAULT);

        $check = mysqli_query($conexion, "SELECT id FROM usuarios WHERE username = '$u'");
        if (mysqli_num_rows($check) > 0) {
            $msg_express = "❌ El usuario ya existe. Elegí otro nombre.";
        } else {
            // ACÁ ESTABA EL QUILOMBO: Ahora verificamos si la base de datos lo acepta de verdad
            $sql_crear = "INSERT INTO usuarios (username, password, rol, estado, ip_registro, saldo, bono) VALUES ('$u', '$p', 'jugador', 1, 'creado_por_staff', 0, 0)";

            if (mysqli_query($conexion, $sql_crear)) {
                $msg_express = "✅ Jugador '$u' creado de verdad.";
            } else {
                // Si la BD lo rebota, te avisa por qué (cero mentiras)
                $msg_express = "❌ Error MySQL al crear: " . mysqli_error($conexion);
            }
        }
    } elseif ($acc == 'carga_directa') {
        $u = mysqli_real_escape_string($conexion, trim($_POST['user_carga']));
        $m = floatval($_POST['monto_carga']);

        $check = mysqli_query($conexion, "SELECT id FROM usuarios WHERE username = '$u' AND rol = 'jugador'");
        if (mysqli_num_rows($check) == 0) {
            $msg_express = "❌ El jugador '$u' no existe en la base de datos.";
        } else {
            // Si es cajero, le descontamos su stock
            if ($rol == 'cajero') {
                $q_stock = mysqli_query($conexion, "SELECT saldo_cajero FROM usuarios WHERE username = '$mi_usuario'");
                if ($q_stock) {
                    $stock = mysqli_fetch_assoc($q_stock)['saldo_cajero'];
                    if ($stock < $m) {
                        $msg_express = "❌ No tenés stock suficiente.";
                    } else {
                        mysqli_query($conexion, "UPDATE usuarios SET saldo_cajero = saldo_cajero - $m WHERE username = '$mi_usuario'");
                        procesarCargaDirecta($conexion, $u, $m, $mi_usuario);
                        $msg_express = "✅ Carga de $$m enviada a '$u'.";
                    }
                } else {
                    $msg_express = "❌ Error leyendo tu stock.";
                }
            } else {
                // Admin tiene saldo infinito
                procesarCargaDirecta($conexion, $u, $m, $mi_usuario);
                $msg_express = "✅ Carga VIP de $$m enviada a '$u'.";
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
// CEREBRO AJAX (Skeleton Loading)
// =========================================================================
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $data = [];
    $q_tickets = mysqli_query($conexion, "SELECT COUNT(*) as t FROM tickets WHERE estado = 'pendiente'");
    $data['tickets'] = $q_tickets ? mysqli_fetch_assoc($q_tickets)['t'] : 0;

    $archivo_anuncio = __DIR__ . '/anuncio_turno.txt';
    $data['anuncio'] = file_exists($archivo_anuncio) ? file_get_contents($archivo_anuncio) : "Bienvenidos al turno.";

    if ($es_admin) {
        $q_staff = mysqli_query($conexion, "SELECT username FROM usuarios WHERE rol = 'cajero' AND ultimo_acceso >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $staff = [];
        if ($q_staff) {
            while ($r = mysqli_fetch_assoc($q_staff)) {
                $staff[] = strtoupper($r['username']);
            }
        }
        $data['staff_online'] = $staff;

        $q_log = mysqli_query($conexion, "SELECT cajero, accion, monto FROM auditoria ORDER BY id DESC LIMIT 5");
        $logs = [];
        if ($q_log) {
            while ($r = mysqli_fetch_assoc($q_log)) {
                $logs[] = "👁️ {$r['cajero']} operó $" . number_format($r['monto'], 0);
            }
        }
        $data['logs'] = implode(" &nbsp;&nbsp;|&nbsp;&nbsp; ", $logs);

        $data['chart'] = [20, 50, 30, 80, 40, 90, 60];
    }
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $es_admin && isset($_POST['guardar_anuncio'])) {
    file_put_contents(__DIR__ . '/anuncio_turno.txt', strip_tags($_POST['texto_anuncio']));
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | EL POINT</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Roboto+Mono:wght@700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --bg: #030304;
            --panel: #0a0c10;
            --border: #1a1e26;
            --blue: #7000ff;
            --green: #00ff88;
            --red: #ff3366;
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
            padding-bottom: 40px;
        }

        /* TOPBAR & RELOJ */
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
        }

        .logo {
            font-weight: 900;
            font-size: 20px;
            text-transform: uppercase;
        }

        .logo span {
            color: var(--blue);
            text-shadow: 0 0 15px rgba(112, 0, 255, 0.4);
        }

        .reloj-caja {
            font-family: 'Roboto Mono', monospace;
            font-size: 14px;
            color: var(--green);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .bienvenida {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 10px;
        }

        .b-texto h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }

        .b-texto p {
            margin: 5px 0 0 0;
            font-size: 11px;
            color: var(--text-muted);
            font-family: 'Roboto Mono';
        }

        .pizarra {
            background: rgba(112, 0, 255, 0.05);
            border: 1px dashed var(--blue);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .input-pizarra {
            width: 100%;
            background: #000;
            border: 1px solid var(--border);
            color: #fff;
            padding: 10px;
            border-radius: 8px;
        }

        /* BOTONERA FAT-FINGER */
        .grid-apps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .app-btn {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 30px 20px;
            text-align: center;
            text-decoration: none;
            color: #fff;
            transition: 0.3s;
            position: relative;
            cursor: pointer;
        }

        .app-btn:hover {
            transform: translateY(-5px);
            border-color: var(--blue);
            box-shadow: 0 15px 40px rgba(112, 0, 255, 0.2);
        }

        .app-icon {
            font-size: 40px;
            margin-bottom: 15px;
            display: block;
        }

        .app-title {
            font-weight: 900;
            font-size: 16px;
            text-transform: uppercase;
        }

        .badge-radar {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--red);
            color: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 900;
        }

        /* ZONA ADMIN */
        .admin-zone {
            border-top: 1px solid var(--border);
            padding-top: 30px;
            margin-top: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .card-sauron {
            background: #000;
            border: 1px solid #161b22;
            padding: 20px;
            border-radius: 12px;
        }

        .cs-title {
            font-size: 11px;
            font-weight: 900;
            color: var(--text-muted);
            margin-bottom: 15px;
        }

        .staff-list {
            margin: 0;
            padding: 0;
            list-style: none;
            font-size: 13px;
            font-weight: bold;
        }

        .chart-svg {
            width: 100%;
            height: 60px;
            stroke: var(--blue);
            stroke-width: 3;
            fill: none;
        }

        .ticker-gh {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #000;
            border-top: 1px solid var(--border);
            height: 30px;
            display: flex;
            align-items: center;
            overflow: hidden;
            z-index: 100;
        }

        .ticker-move {
            white-space: nowrap;
            font-family: 'Roboto Mono';
            font-size: 11px;
            color: var(--text-muted);
            animation: moveGH 20s linear infinite;
            padding-left: 100%;
        }

        @keyframes moveGH {
            to {
                transform: translateX(-100%);
            }
        }

        /* MODAL GESTIÓN RÁPIDA */
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
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.9);
        }

        .input-dark {
            width: 100%;
            background: #000;
            border: 1px solid var(--border);
            padding: 12px;
            border-radius: 8px;
            color: #fff;
            margin-bottom: 15px;
        }

        .btn-green {
            width: 100%;
            background: var(--green);
            color: #000;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-weight: 900;
            cursor: pointer;
            text-transform: uppercase;
        }
    </style>
</head>

<body>

    <nav class="topbar">
        <div class="logo">EL <span>POINT</span></div>
        <div class="reloj-caja" id="relojSistema">00:00:00</div>
    </nav>

    <div class="container">

        <div class="bienvenida">
            <div class="b-texto">
                <h1>Hola, <?php echo strtoupper($mi_usuario); ?></h1>
                <p>IP Registrada: <?php echo $_SERVER['REMOTE_ADDR']; ?> | Rol: <?php echo strtoupper($rol); ?></p>
            </div>
            <a href="logout_staff.php" style="color:var(--text-muted); font-size:12px; font-weight:bold;">Cerrar
                Sesión</a>
        </div>

        <?php if ($msg_express != '')
            echo "<div style='background:#111; color:var(--green); padding:15px; border-radius:8px; border:1px solid var(--green); margin-bottom:20px; font-weight:bold; text-align:center;'>$msg_express</div>"; ?>

        <div class="pizarra">
            <div style="font-size:10px; font-weight:900; color:var(--blue); margin-bottom:5px;">📣 ANUNCIO DEL TURNO
            </div>
            <?php if ($es_admin): ?>
                <form method="POST" style="display:flex; gap:10px;">
                    <input type="text" name="texto_anuncio" id="txtAnuncio" class="input-pizarra"
                        placeholder="Escribir anuncio...">
                    <button type="submit" name="guardar_anuncio"
                        style="background:var(--blue); color:#fff; border:none; border-radius:8px; padding:0 15px; font-weight:bold; cursor:pointer;">Fijar</button>
                </form>
            <?php else: ?>
                <div style="font-size:14px; font-weight:bold;" id="lblAnuncio">Cargando anuncio...</div>
            <?php endif; ?>
        </div>

        <div class="grid-apps">
            <a href="interno.php" class="app-btn">
                <span class="badge-radar" id="badgeTickets" style="display:none;">0</span>
                <span class="app-icon">🎧</span><span class="app-title">Cabina de Chat</span>
            </a>

            <div class="app-btn" style="border-color: var(--green);"
                onclick="document.getElementById('modalGestion').style.display='flex'">
                <span class="app-icon">⚡</span><span class="app-title" style="color:var(--green);">Gestión Rápida</span>
                <div style="font-size:10px; color:var(--text-muted); margin-top:5px;">Crear Usuarios y Cargar</div>
            </div>

            <?php if ($es_admin): ?>
                <a href="cajeros.php" class="app-btn"><span class="app-icon">👥</span><span class="app-title">Control de
                        Staff</span></a>
                <a href="historial.php" class="app-btn"><span class="app-icon">📊</span><span class="app-title">Finanzas y
                        Cierre</span></a>
                <a href="jugadores.php" class="app-btn" style="border-color: #161b22;">
                    <span class="app-icon">🕵️</span>
                    <span class="app-title">Control de Jugadores</span>
                </a>
            <?php else: ?>
                <div class="app-btn" style="cursor:default;">
                    <span class="app-icon">💰</span><span class="app-title" style="font-size:10px;">Mi Stock</span>
                    <div style="font-family:'Roboto Mono'; font-size:24px; color:var(--green); margin-top:10px;">
                        <?php
                        $q_stock = mysqli_query($conexion, "SELECT saldo_cajero FROM usuarios WHERE username='$mi_usuario'");
                        echo "$" . number_format(mysqli_fetch_assoc($q_stock)['saldo_cajero'], 2);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($es_admin): ?>
            <div class="admin-zone">
                <div class="card-sauron">
                    <div class="cs-title">🟢 Staff en Línea</div>
                    <ul class="staff-list" id="listaStaff"></ul>
                </div>
                <div class="card-sauron">
                    <div class="cs-title">📈 Latidos del Casino</div>
                    <div style="height: 100px; display:flex; align-items:flex-end;">
                        <svg class="chart-svg" viewBox="0 0 100 40" preserveAspectRatio="none">
                            <polyline id="lineaLatidos" points="0,40 100,40"></polyline>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="ticker-gh">
                <div class="ticker-move" id="tickerGH">Buscando actividad...</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal-overlay" id="modalGestion">
        <div class="modal-content">
            <div style="display:flex; gap:10px; margin-bottom:20px;">
                <button onclick="tabGestion('crear')"
                    style="flex:1; padding:10px; background:#000; color:#fff; border:1px solid var(--border); border-radius:8px; cursor:pointer;"
                    id="btnT1">Crear Jugador</button>
                <button onclick="tabGestion('cargar')"
                    style="flex:1; padding:10px; background:#000; color:#fff; border:1px solid var(--border); border-radius:8px; cursor:pointer;"
                    id="btnT2">Carga Directa</button>
            </div>

            <form method="POST" id="formCrear">
                <input type="hidden" name="accion_express" value="crear_jugador">
                <input type="text" name="nuevo_user" class="input-dark" placeholder="Usuario del jugador" required>
                <input type="text" name="nueva_pass" class="input-dark" placeholder="Contraseña inicial" required
                    value="123456">
                <button type="submit" class="btn-green">CREAR CUENTA</button>
            </form>

            <form method="POST" id="formCargar" style="display:none;">
                <input type="hidden" name="accion_express" value="carga_directa">
                <input type="text" name="user_carga" class="input-dark" placeholder="Usuario a cargar" required>
                <input type="number" name="monto_carga" class="input-dark" placeholder="$ Monto a cargar" required>
                <button type="submit" class="btn-green" style="background:var(--blue); color:#fff;">ENVIAR
                    FICHAS</button>
            </form>

            <button onclick="document.getElementById('modalGestion').style.display='none'"
                style="width:100%; background:transparent; color:var(--text-muted); border:none; margin-top:15px; cursor:pointer; font-weight:bold;">Cancelar</button>
        </div>
    </div>

    <script>
        function actualizarReloj() {
            document.getElementById('relojSistema').innerText = new Intl.DateTimeFormat('es-AR', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).format(new Date());
        }
        setInterval(actualizarReloj, 1000);

        function tabGestion(tab) {
            document.getElementById('formCrear').style.display = tab === 'crear' ? 'block' : 'none';
            document.getElementById('formCargar').style.display = tab === 'cargar' ? 'block' : 'none';
            document.getElementById('btnT1').style.borderColor = tab === 'crear' ? 'var(--green)' : 'var(--border)';
            document.getElementById('btnT2').style.borderColor = tab === 'cargar' ? 'var(--blue)' : 'var(--border)';
        }
        tabGestion('crear');

        function cargarDatosMagicos() {
            fetch('dashboard.php?ajax=1').then(r => r.json()).then(data => {
                let b = document.getElementById('badgeTickets');
                b.innerText = data.tickets; b.style.display = data.tickets > 0 ? 'inline-block' : 'none';

                let txtA = document.getElementById('txtAnuncio'); if (txtA) txtA.value = data.anuncio;
                let lblA = document.getElementById('lblAnuncio'); if (lblA) lblA.innerText = data.anuncio;

                <?php if ($es_admin): ?>
                    let ul = document.getElementById('listaStaff'); ul.innerHTML = '';
                    if (data.staff_online.length === 0) ul.innerHTML = '<li style="color:var(--text-muted);">Nadie en línea.</li>';
                    data.staff_online.forEach(n => ul.innerHTML += `<li><div style="width:8px;height:8px;background:var(--green);border-radius:50%;margin-right:8px;box-shadow:0 0 8px var(--green);"></div> ${n}</li>`);
                    document.getElementById('tickerGH').innerHTML = data.logs;
                    let max = Math.max(...data.chart, 1);
                    let puntos = data.chart.map((val, i) => `${(i / (data.chart.length - 1)) * 100},${40 - ((val / max) * 40)}`).join(' ');
                    document.getElementById('lineaLatidos').setAttribute('points', puntos);
                <?php endif; ?>
            });
        }
        setInterval(cargarDatosMagicos, 4000); cargarDatosMagicos();
    </script>
</body>

</html>