<?php
session_start();
include 'conexion.php';

// Si ya está logueado, directo al lobby
if (isset($_SESSION['jugador'])) {
    header("Location: lobby.php");
    exit;
}

$error = '';
$exito = '';
$ip_actual = $_SERVER['REMOTE_ADDR'];

// Capturar código de referido de la URL (Ej: elpoint.com/?ref=MARTIN658)
$ref_code = isset($_GET['ref']) ? htmlspecialchars($_GET['ref']) : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = $_POST['accion'];
    $user = mysqli_real_escape_string($conexion, trim($_POST['username']));
    $pass = $_POST['password'];

    if ($accion == 'login') {
        // LÓGICA DE LOGIN
        $q = mysqli_query($conexion, "SELECT id, password, estado FROM usuarios WHERE username = '$user' AND rol = 'jugador'");
        if (mysqli_num_rows($q) > 0) {
            $row = mysqli_fetch_assoc($q);
            if ($row['estado'] == 0) {
                $error = "Cuenta suspendida. Contacte a soporte.";
            } elseif (password_verify($pass, $row['password'])) {
                // Actualizamos IP para el "Espía" de tu panel
                mysqli_query($conexion, "UPDATE usuarios SET last_ip = '$ip_actual' WHERE id = " . $row['id']);
                $_SESSION['jugador'] = $user;
                header("Location: lobby.php");
                exit;
            } else {
                $error = "Credenciales incorrectas.";
            }
        } else {
            $error = "Credenciales incorrectas.";
        }
    } 
    elseif ($accion == 'registro') {
        // LÓGICA DE REGISTRO
        $referido_por = mysqli_real_escape_string($conexion, trim($_POST['referido_por']));
        
        $check = mysqli_query($conexion, "SELECT id FROM usuarios WHERE username = '$user'");
        if (mysqli_num_rows($check) > 0) {
            $error = "El usuario ya existe. Elegí otro.";
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            // Generamos su propio código de referido al registrarse
            $mi_codigo = strtoupper(substr(md5($user . time()), 0, 8));
            
            $sql = "INSERT INTO usuarios (username, password, rol, estado, last_ip, ip_registro, codigo_referido, referido_por) 
                    VALUES ('$user', '$hash', 'jugador', 1, '$ip_actual', '$ip_actual', '$mi_codigo', '$referido_por')";
            
            if (mysqli_query($conexion, $sql)) {
                $exito = "¡Cuenta creada! Ya podés ingresar.";
            } else {
                $error = "Error al crear la cuenta. Intentá de nuevo.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>EL POINT | Casino VIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Roboto+Mono:wght@700&display=swap" rel="stylesheet">
    
    <link rel="preload" href="lobby.php" as="document">
    
    <style>
        :root { --bg: #030304; --panel: #0a0c10; --border: #1a1e26; --blue: #7000ff; --green: #00ff88; --red: #ff3366; --text: #ffffff; --text-muted: #6b7280; }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; overflow: hidden; }
        
        /* Efecto de luz minimalista de fondo */
        .glow-bg { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 300px; height: 300px; background: radial-gradient(circle, rgba(112,0,255,0.15) 0%, transparent 70%); z-index: 0; pointer-events: none; }

        .auth-container { background: var(--panel); border: 1px solid var(--border); padding: 40px 30px; border-radius: 16px; width: 90%; max-width: 400px; position: relative; z-index: 10; box-shadow: 0 20px 50px rgba(0,0,0,0.8); }
        
        .logo { text-align: center; font-size: 28px; font-weight: 900; margin-bottom: 5px; text-transform: uppercase; letter-spacing: -1px; }
        .logo span { color: var(--green); text-shadow: 0 0 15px rgba(0,255,136,0.4); }
        .subtitle { text-align: center; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 30px; }

        /* Pestañas Híbridas (Login / Registro) */
        .tabs { display: flex; background: #000; border-radius: 8px; margin-bottom: 25px; border: 1px solid var(--border); padding: 4px; }
        .tab { flex: 1; text-align: center; padding: 10px; font-size: 12px; font-weight: bold; cursor: pointer; border-radius: 6px; color: var(--text-muted); transition: 0.3s; text-transform: uppercase; }
        .tab.active { background: var(--blue); color: #fff; box-shadow: 0 5px 15px rgba(112,0,255,0.3); }

        .form-group { position: relative; margin-bottom: 20px; }
        .input-dark { width: 100%; background: #000; border: 1px solid var(--border); padding: 15px; border-radius: 8px; color: #fff; font-size: 14px; outline: none; transition: 0.3s; font-family: 'Inter', sans-serif; }
        .input-dark:focus { border-color: var(--blue); }
        
        .btn-eye { position: absolute; right: 15px; top: 15px; background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 16px; }

        .btn-submit { width: 100%; background: var(--blue); color: #fff; border: none; padding: 16px; border-radius: 8px; font-weight: 900; text-transform: uppercase; font-size: 14px; cursor: pointer; transition: 0.3s; letter-spacing: 1px; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(112,0,255,0.4); }

        .alert { padding: 12px; border-radius: 8px; font-size: 12px; font-weight: bold; text-align: center; margin-bottom: 20px; }
        .alert-error { background: rgba(255,51,102,0.1); color: var(--red); border: 1px solid var(--red); }
        .alert-success { background: rgba(0,255,136,0.1); color: var(--green); border: 1px solid var(--green); }

        /* Ticker Minimalista (Cero Lag, puro CSS) */
        .ticker-wrap { position: absolute; bottom: 0; width: 100%; height: 30px; background: rgba(0,0,0,0.8); border-top: 1px solid var(--border); overflow: hidden; display: flex; align-items: center; z-index: 10; }
        .ticker-move { display: inline-block; white-space: nowrap; padding-right: 100%; animation: ticker 25s linear infinite; font-family: 'Roboto Mono', monospace; font-size: 10px; color: var(--text-muted); }
        .ticker-move span { margin-right: 40px; color: var(--green); }
        @keyframes ticker { 0% { transform: translate3d(100%, 0, 0); } 100% { transform: translate3d(-100%, 0, 0); } }
        
        #form-registro { display: <?php echo ($ref_code != '') ? 'block' : 'none'; ?>; }
        #form-login { display: <?php echo ($ref_code != '') ? 'none' : 'block'; ?>; }
    </style>
</head>
<body>

<div class="glow-bg"></div>

<div class="auth-container">
    <div class="logo">EL <span>POINT</span></div>
    <div class="subtitle">Salón Exclusivo</div>

    <?php if ($error != '') echo "<div class='alert alert-error'>⚠️ $error</div>"; ?>
    <?php if ($exito != '') echo "<div class='alert alert-success'>✅ $exito</div>"; ?>

    <div class="tabs">
        <div class="tab <?php echo ($ref_code == '') ? 'active' : ''; ?>" id="tab-login" onclick="switchTab('login')">Ingresar</div>
        <div class="tab <?php echo ($ref_code != '') ? 'active' : ''; ?>" id="tab-registro" onclick="switchTab('registro')">Crear Cuenta</div>
    </div>

    <form id="form-login" method="POST" action="">
        <input type="hidden" name="accion" value="login">
        <div class="form-group">
            <input type="text" name="username" class="input-dark" placeholder="Usuario" required autocomplete="off">
        </div>
        <div class="form-group">
            <input type="password" name="password" id="pass-login" class="input-dark" placeholder="Contraseña" required>
            <button type="button" class="btn-eye" onclick="togglePass('pass-login')">👁️</button>
        </div>
        <button type="submit" class="btn-submit">ENTRAR AL SALÓN</button>
    </form>

    <form id="form-registro" method="POST" action="">
        <input type="hidden" name="accion" value="registro">
        
        <div class="form-group">
            <input type="text" name="username" id="reg-user" class="input-dark" placeholder="Elegí un Usuario" required autocomplete="off">
            <small id="user-status" style="position:absolute; right:15px; top:15px; font-size:12px;"></small>
        </div>
        
        <div class="form-group">
            <input type="password" name="password" id="pass-reg" class="input-dark" placeholder="Crear Contraseña" required>
            <button type="button" class="btn-eye" onclick="togglePass('pass-reg')">👁️</button>
        </div>

        <div class="form-group">
            <input type="text" name="referido_por" class="input-dark" placeholder="Código de Invitado (Opcional)" value="<?php echo $ref_code; ?>" <?php echo ($ref_code != '') ? 'readonly style="color:var(--green); border-color:var(--green);"' : ''; ?>>
        </div>

        <button type="submit" class="btn-submit" style="background:var(--green); color:#000;">REGISTRARSE</button>
    </form>
</div>

<div class="ticker-wrap">
    <div class="ticker-move">
        <span>🔥 Matias_22 retiró $15.000</span>
        <span>💸 Carlitos_vip cargó $5.000</span>
        <span>🎰 Nuevo récord en Wager superado</span>
        <span>🎁 Bono de Bienvenida Activo 300%</span>
        <span>👑 Se unió un nuevo jugador VIP</span>
    </div>
</div>

<script>
// Lógica para cambiar entre Login y Registro sin recargar la página
function switchTab(tab) {
    document.getElementById('tab-login').classList.remove('active');
    document.getElementById('tab-registro').classList.remove('active');
    document.getElementById('form-login').style.display = 'none';
    document.getElementById('form-registro').style.display = 'none';
    
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById('form-' + tab).style.display = 'block';
}

// Botón del Ojito (Ver contraseña)
function togglePass(id) {
    let input = document.getElementById(id);
    if (input.type === "password") { input.type = "text"; } 
    else { input.type = "password"; }
}

// Simulación de Check de Usuario Disponible en Vivo (Para no saturar la BD en cada tecla)
document.getElementById('reg-user').addEventListener('input', function() {
    let val = this.value.trim();
    let status = document.getElementById('user-status');
    if(val.length < 4) { status.innerText = ''; return; }
    // En un futuro se puede hacer un 'fetch' real a la base de datos acá.
    // Por ahora le damos feedback visual positivo al instante para evitar fricción.
    status.innerText = '✅'; 
});
</script>

</body>
</html>