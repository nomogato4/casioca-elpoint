<?php
session_start();
include 'conexion.php';

$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');
$mi_usuario = $_SESSION['admin'] ?? ($_SESSION['cajero'] ?? ($_SESSION['jugador'] ?? ''));
$rol = isset($_SESSION['admin']) ? 'admin' : (isset($_SESSION['cajero']) ? 'cajero' : (isset($_SESSION['jugador']) ? 'jugador' : ''));

if ($mi_usuario === '')
    die("No autorizado.");

// AUTO-REPARACIÓN: Agregar columnas para el trabajo en equipo si no existen
$columnas_nuevas = [
    'tomado_por' => "VARCHAR(50) DEFAULT NULL",
    'escribiendo' => "DATETIME DEFAULT NULL"
];
foreach ($columnas_nuevas as $col => $tipo) {
    $check = mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE '$col'");
    if (mysqli_num_rows($check) == 0)
        mysqli_query($conexion, "ALTER TABLE usuarios ADD COLUMN $col $tipo");
}

// ---------------------------------------------------------
// 1. CARGAR LISTA DE TICKETS (PANEL CAJERO/ADMIN)
// ---------------------------------------------------------
if ($accion == 'fetch_admin_users' && $rol != 'jugador') {
    $q = mysqli_query($conexion, "
        SELECT u.username, u.tomado_por, 
        (SELECT mensaje FROM mensajes WHERE (de_usuario = u.username OR para_usuario = u.username) ORDER BY id DESC LIMIT 1) as ultimo_msg,
        (SELECT COUNT(*) FROM tickets WHERE usuario = u.username AND estado = 'pendiente') as t_pendientes
        FROM usuarios u 
        WHERE u.rol = 'jugador' AND (
            (SELECT COUNT(*) FROM tickets WHERE usuario = u.username AND estado = 'pendiente') > 0 
            OR 
            (SELECT COUNT(*) FROM mensajes WHERE de_usuario = u.username AND leido = 0) > 0
        )
        ORDER BY t_pendientes DESC
    ");

    $html = "";

    // 🔥 EL PARACAÍDAS: Solo intentamos armar la lista si la consulta fue exitosa
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $tomado = $r['tomado_por'];
            $clase_tomado = ($tomado && $tomado != $mi_usuario) ? "tomado" : "";
            $candado = ($tomado && $tomado != $mi_usuario) ? "<span class='badge-lock'>🔒 $tomado</span>" : "";
            $pendientes = $r['t_pendientes'] > 0 ? "🔔 " : "";
            $prev = substr(htmlspecialchars($r['ultimo_msg'] ?? 'Sin mensajes'), 0, 30) . "...";

            $html .= "<div class='contacto $clase_tomado' onclick=\"cargarChatAdmin('{$r['username']}', " . ($clase_tomado ? 'true' : 'false') . ")\">
                        <div class='c-nombre'>$pendientes" . strtoupper($r['username']) . " $candado</div>
                        <div class='c-prev'>$prev</div>
                      </div>";
        }
    } else {
        // Si hay error en la base de datos, te avisa en rojo pero NO crashea.
        $error_db = mysqli_error($conexion);
        $html = "<div style='padding: 20px; text-align: center; color: var(--red); font-size: 11px; font-weight:bold;'>⚠️ Error DB: Ejecutá reparar_db.php<br><br>$error_db</div>";
    }

    echo $html ?: "<div style='padding: 20px; text-align: center; color: #6b7280; font-size: 12px;'>No hay tickets activos.</div>";
    exit;
}

// ---------------------------------------------------------
// 2. CARGAR CHAT DEL JUGADOR
// ---------------------------------------------------------
if ($accion == 'fetch_admin_chat' && $rol != 'jugador') {
    $id_user = mysqli_real_escape_string($conexion, $_POST['id_usuario']);

    // Marcar como leídos
    mysqli_query($conexion, "UPDATE mensajes SET leido = 1 WHERE de_usuario = '$id_user'");

    // Traer historial
    $q = mysqli_query($conexion, "SELECT * FROM mensajes WHERE (de_usuario = '$id_user' OR para_usuario = '$id_user') ORDER BY id ASC");
    $html = "";
    while ($r = mysqli_fetch_assoc($q)) {
        $clase = ($r['de_usuario'] == $id_user) ? 'msg-jugador' : 'msg-yo';
        $texto = htmlspecialchars($r['mensaje']);
        if ($r['es_imagen']) {
            $texto = "<img src='{$r['mensaje']}' style='max-width:200px; border-radius:8px; cursor:pointer;' onclick=\"abrirGaleria('{$r['mensaje']}')\">";
        }
        $html .= "<div class='msg-burbuja $clase'>$texto <div class='hora'>" . date('H:i', strtotime($r['fecha'])) . "</div></div>";
    }

    // Verificar si está escribiendo (últimos 3 segundos)
    $q_escribiendo = mysqli_query($conexion, "SELECT escribiendo FROM usuarios WHERE username = '$id_user' AND escribiendo >= DATE_SUB(NOW(), INTERVAL 3 SECOND)");
    $escribiendo = mysqli_num_rows($q_escribiendo) > 0;

    echo json_encode(['html' => $html, 'escribiendo' => $escribiendo]);
    exit;
}

// ---------------------------------------------------------
// 3. ENVIAR MENSAJE (ADMIN/CAJERO -> JUGADOR)
// ---------------------------------------------------------
if ($accion == 'send_admin' && $rol != 'jugador') {
    $id_user = mysqli_real_escape_string($conexion, $_POST['id_usuario']);

    if (isset($_FILES['foto'])) {
        $ruta = "img/comprobantes/" . time() . "_" . $_FILES['foto']['name'];
        move_uploaded_file($_FILES['foto']['tmp_name'], $ruta);
        mysqli_query($conexion, "INSERT INTO mensajes (de_usuario, para_usuario, mensaje, es_imagen, fecha) VALUES ('$mi_usuario', '$id_user', '$ruta', 1, NOW())");
    } else {
        $msg = mysqli_real_escape_string($conexion, $_POST['mensaje']);
        mysqli_query($conexion, "INSERT INTO mensajes (de_usuario, para_usuario, mensaje, fecha) VALUES ('$mi_usuario', '$id_user', '$msg', NOW())");
    }
    exit;
}

// ---------------------------------------------------------
// 4. TRABAJO EN EQUIPO: TOMAR, LIBERAR Y TRANSFERIR CHAT
// ---------------------------------------------------------
if ($accion == 'tomar_chat' && $rol != 'jugador') {
    $id_user = mysqli_real_escape_string($conexion, $_POST['id_usuario']);
    mysqli_query($conexion, "UPDATE usuarios SET tomado_por = '$mi_usuario' WHERE username = '$id_user'");
    exit;
}

if ($accion == 'liberar_chat' && $rol != 'jugador') {
    $id_user = mysqli_real_escape_string($conexion, $_POST['id_usuario']);
    mysqli_query($conexion, "UPDATE usuarios SET tomado_por = NULL WHERE username = '$id_user'");
    mysqli_query($conexion, "UPDATE tickets SET estado = 'resuelto' WHERE usuario = '$id_user'");
    exit;
}

if ($accion == 'transferir_admin' && $rol == 'cajero') {
    $id_user = mysqli_real_escape_string($conexion, $_POST['id_usuario']);
    mysqli_query($conexion, "UPDATE usuarios SET tomado_por = 'admin' WHERE username = '$id_user'");
    mysqli_query($conexion, "INSERT INTO mensajes (de_usuario, para_usuario, mensaje, fecha) VALUES ('sistema', '$id_user', '🔄 Aguardá un momento, fuiste transferido al Administrador.', NOW())");
    exit;
}

// ---------------------------------------------------------
// 5. ACCIONES RÁPIDAS (CAJA FUERTE)
// ---------------------------------------------------------
if ($accion == 'accion_rapida' && $rol != 'jugador') {
    $id_user = mysqli_real_escape_string($conexion, $_POST['id_usuario']);
    $tipo = $_POST['tipo_accion'];
    $monto = floatval($_POST['monto']);

    if ($tipo == 'sumar' || $tipo == 'restar') {
        $operador = ($tipo == 'sumar') ? '+' : '-';
        $accion_hist = ($tipo == 'sumar') ? 'CARGA' : 'RETIRO';
        $msg_jugador = ($tipo == 'sumar') ? "✅ Fichas acreditadas: $$monto. ¡Mucha suerte!" : "✅ Retiro aprobado: $$monto. Ya lo enviamos a tu cuenta.";

        // Descontar del stock del cajero si no es admin
        if ($rol == 'cajero' && $tipo == 'sumar') {
            $q_stock = mysqli_query($conexion, "SELECT saldo_cajero FROM usuarios WHERE username = '$mi_usuario'");
            $stock = mysqli_fetch_assoc($q_stock)['saldo_cajero'];
            if ($stock < $monto)
                die("Sin stock");
            mysqli_query($conexion, "UPDATE usuarios SET saldo_cajero = saldo_cajero - $monto WHERE username = '$mi_usuario'");
        }

        mysqli_query($conexion, "UPDATE usuarios SET saldo = saldo $operador $monto WHERE username = '$id_user'");
        mysqli_query($conexion, "UPDATE tickets SET estado = 'resuelto' WHERE usuario = '$id_user' AND tipo = '$accion_hist'");
        mysqli_query($conexion, "INSERT INTO mensajes (de_usuario, para_usuario, mensaje, fecha) VALUES ('$mi_usuario', '$id_user', '$msg_jugador', NOW())");
        mysqli_query($conexion, "INSERT INTO auditoria (cajero, jugador, accion, monto, fecha) VALUES ('$mi_usuario', '$id_user', '$accion_hist', $monto, NOW())");
    }
    echo "ok";
    exit;
}
?>