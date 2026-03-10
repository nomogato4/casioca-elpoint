<?php
session_start();
include 'conexion.php';

// 🛡️ SEGURIDAD: Solo el Dueño (Admin) entra acá
if (!isset($_SESSION['admin'])) {
    header("Location: auth.php");
    exit;
}

// 1. AUTO-REPARACIÓN DE TABLA (Columnas para Stock y Sesiones)
$cols_cajeros = [
    'saldo_cajero' => 'DECIMAL(15,2) DEFAULT 0.00',
    'ultimo_acceso' => 'DATETIME DEFAULT NULL',
    'token_sesion' => 'VARCHAR(255) DEFAULT NULL',
    'estado' => 'TINYINT(1) DEFAULT 1'
];
foreach($cols_cajeros as $col => $tipo) {
    $check = mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE '$col'");
    if (mysqli_num_rows($check) == 0) mysqli_query($conexion, "ALTER TABLE usuarios ADD COLUMN $col $tipo");
}

$mensaje = "";

// 2. LÓGICA DE ACCIONES (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id_cajero = intval($_POST['id_cajero'] ?? 0);

    if ($accion == 'crear_cajero') {
        $user = mysqli_real_escape_string($conexion, $_POST['username']);
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        mysqli_query($conexion, "INSERT INTO usuarios (username, password, rol, estado) VALUES ('$user', '$pass', 'cajero', 1)");
        $mensaje = "Cajero creado con éxito.";
    }

    if ($accion == 'cargar_stock') {
        $monto = floatval($_POST['monto']);
        if ($monto > 0) {
            mysqli_query($conexion, "UPDATE usuarios SET saldo_cajero = saldo_cajero + $monto WHERE id = $id_cajero");
            mysqli_query($conexion, "INSERT INTO historial (username, accion) VALUES ('".$_SESSION['admin']."', 'CARGÓ STOCK: $$monto al ID: $id_cajero')");
            $mensaje = "Stock cargado correctamente.";
        }
    }

    if ($accion == 'reset_pass') {
        $nueva_pass = password_hash("123456", PASSWORD_DEFAULT);
        mysqli_query($conexion, "UPDATE usuarios SET password = '$nueva_pass', token_sesion = NULL WHERE id = $id_cajero");
        $mensaje = "Clave reseteada a: 123456";
    }

    if ($accion == 'toggle_estado') {
        mysqli_query($conexion, "UPDATE usuarios SET estado = NOT estado, token_sesion = NULL WHERE id = $id_cajero");
    }
}

// 3. CONSULTA OPTIMIZADA (Solo Cajeros)
$resultado = mysqli_query($conexion, "SELECT * FROM usuarios WHERE rol = 'cajero' ORDER BY username ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cajeros | EL POINT</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Roboto+Mono:wght@700&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #030304; --panel: #0a0c10; --border: #1a1e26; --blue: #7000ff; --green: #00ff88; --red: #ff3366; --text: #ffffff; --text-muted: #6b7280; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid var(--border); padding-bottom: 20px;}
        .logo { font-weight: 900; font-size: 24px; text-transform: uppercase;}
        .logo span { color: var(--blue); }

        .btn { padding: 10px 15px; border-radius: 8px; border: none; font-weight: 900; cursor: pointer; text-transform: uppercase; font-size: 11px; transition: 0.3s;}
        .btn-blue { background: var(--blue); color: #fff; }
        .btn-green { background: var(--green); color: #000; }
        .btn-red { background: rgba(255,51,102,0.1); color: var(--red); border: 1px solid var(--red); }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-muted); }

        .grid-cajeros { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .card-cajero { background: var(--panel); border: 1px solid var(--border); border-radius: 12px; padding: 20px; position: relative; overflow: hidden;}
        
        .c-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .c-name { font-weight: 900; font-size: 18px; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }

        .stock-box { background: #000; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px dashed var(--border); text-align: center;}
        .stock-label { font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;}
        .stock-valor { font-family: 'Roboto Mono', monospace; font-size: 22px; color: var(--green); display: block;}

        .info-row { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 8px; color: var(--text-muted); }
        .info-row strong { color: #fff; }

        .acciones-cajero { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 15px; }
        
        .form-inline { display: flex; gap: 5px; margin-top: 10px; border-top: 1px solid var(--border); padding-top: 10px; }
        .input-mini { flex: 1; background: #000; border: 1px solid var(--border); color: #fff; padding: 8px; border-radius: 6px; outline: none; font-size: 12px; }

        .alert { background: rgba(0,255,136,0.1); color: var(--green); padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--green); font-weight: bold; font-size: 13px;}
    </style>
</head>
<body>

<div class="header">
    <div class="logo">EL <span>POINT</span> | STAFF</div>
    <div style="display:flex; gap:10px;">
        <button class="btn btn-blue" onclick="document.getElementById('modalCrear').style.display='flex'">+ Nuevo Cajero</button>
        <a href="dashboard.php" class="btn btn-outline">Volver</a>
    </div>
</div>

<?php if($mensaje != "") echo "<div class='alert'>$mensaje</div>"; ?>

<div class="grid-cajeros">
    <?php while($row = mysqli_fetch_assoc($resultado)): 
        $offline = (strtotime($row['ultimo_acceso']) < strtotime('-5 minutes'));
        $dot_color = $offline ? '#555' : 'var(--green)';
        $status_text = $offline ? 'Desconectado' : 'En línea';
    ?>
        <div class="card-cajero" style="opacity: <?php echo $row['estado'] ? '1' : '0.5'; ?>">
            <div class="c-header">
                <div class="c-name"><?php echo strtoupper($row['username']); ?></div>
                <div style="font-size: 10px; font-weight: 900; color: <?php echo $dot_color; ?>">
                    <span class="status-dot" style="background: <?php echo $dot_color; ?>"></span> <?php echo $status_text; ?>
                </div>
            </div>

            <div class="stock-box">
                <span class="stock-label">Stock de Fichas</span>
                <span class="stock-valor">$<?php echo number_format($row['saldo_cajero'], 2); ?></span>
            </div>

            <div class="info-row">
                <span>Última conexión:</span>
                <strong><?php echo $row['ultimo_acceso'] ? date('H:i d/m', strtotime($row['ultimo_acceso'])) : 'Nunca'; ?></strong>
            </div>

            <form method="POST" class="form-inline">
                <input type="hidden" name="accion" value="cargar_stock">
                <input type="hidden" name="id_cajero" value="<?php echo $row['id']; ?>">
                <input type="number" name="monto" class="input-mini" placeholder="Monto stock" required>
                <button type="submit" class="btn btn-green">Cargar</button>
            </form>

            <div class="acciones-cajero">
                <form method="POST">
                    <input type="hidden" name="accion" value="reset_pass">
                    <input type="hidden" name="id_cajero" value="<?php echo $row['id']; ?>">
                    <button type="submit" class="btn btn-outline" style="width:100%" onclick="return confirm('¿Resetear clave?')">Reset Clave</button>
                </form>
                <form method="POST">
                    <input type="hidden" name="accion" value="toggle_estado">
                    <input type="hidden" name="id_cajero" value="<?php echo $row['id']; ?>">
                    <button type="submit" class="btn <?php echo $row['estado'] ? 'btn-red' : 'btn-green'; ?>" style="width:100%">
                        <?php echo $row['estado'] ? 'Suspender' : 'Activar'; ?>
                    </button>
                </form>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<div id="modalCrear" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); align-items:center; justify-content:center; z-index:1000;">
    <div style="background:var(--panel); padding:30px; border-radius:16px; width:350px; border:1px solid var(--border);">
        <h2 style="margin-top:0;">Nuevo Cajero</h2>
        <form method="POST">
            <input type="hidden" name="accion" value="crear_cajero">
            <input type="text" name="username" class="input-mini" placeholder="Usuario" required style="margin-bottom:10px; width:100%; padding:12px;">
            <input type="password" name="password" class="input-mini" placeholder="Contraseña" required style="margin-bottom:20px; width:100%; padding:12px;">
            <button type="submit" class="btn btn-blue" style="width:100%; padding:12px;">Crear Acceso</button>
            <button type="button" class="btn btn-outline" style="width:100%; margin-top:10px;" onclick="this.parentElement.parentElement.parentElement.style.display='none'">Cancelar</button>
        </form>
    </div>
</div>

</body>
</html>