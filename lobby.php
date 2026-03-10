<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['jugador'])) {
    header("Location: login_jugador.php");
    exit;
}

$jugador = $_SESSION['jugador'];

// Mensajes de alerta rápidos
$mensaje = '';
if (isset($_GET['ticket'])) {
    if ($_GET['ticket'] == 'ok') { $mensaje = "<div class='alerta exito'>¡Ticket generado! Mandanos el comprobante por el chat abajo.</div>"; }
    elseif ($_GET['ticket'] == 'error_saldo') { $mensaje = "<div class='alerta error'>Saldo insuficiente o no llegás al mínimo.</div>"; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Lobby VIP | EL POINT</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Roboto+Mono:wght@700;900&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #030304; --panel: #0a0c10; --border: #1a1e26; --green: #00ff88; --red: #ff3366; --blue: #7000ff; --yellow: #ffd700; --text: #ffffff; --text-muted: #6b7280; }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding-bottom: 80px;}

        /* Navbar */
        .topbar { background: var(--panel); border-bottom: 1px solid var(--border); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100;}
        .logo { color: #fff; font-weight: 900; font-size: 1.5rem; text-decoration: none; text-transform: uppercase; letter-spacing: -1px;} 
        .logo span { color: var(--green); text-shadow: 0 0 15px rgba(0,255,136,0.4); }
        
        .nav-btns { display: flex; gap: 10px; }
        .btn-nav { background: rgba(112,0,255,0.1); color: var(--blue); border: 1px solid var(--blue); padding: 8px 15px; border-radius: 8px; text-decoration: none; font-size: 11px; font-weight: 900; text-transform: uppercase; transition: 0.3s;}
        .btn-nav:hover { background: var(--blue); color: #fff; box-shadow: 0 0 15px var(--blue); }
        .btn-vip { background: rgba(255,215,0,0.1); color: var(--yellow); border-color: var(--yellow); }
        .btn-vip:hover { background: var(--yellow); color: #000; box-shadow: 0 0 15px var(--yellow); }

        .main-content { padding: 20px; max-width: 800px; margin: 0 auto;}

        /* Saldo */
        .saldo-card { background: linear-gradient(135deg, var(--panel) 0%, #05080c 100%); border: 1px solid var(--border); padding: 25px; border-radius: 16px; text-align: center; margin-bottom: 25px; position: relative; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.8);}
        .saldo-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 3px; background: var(--green); box-shadow: 0 0 20px var(--green);}
        .saldo-monto { font-family: 'Roboto Mono', monospace; font-size: 42px; font-weight: 900; color: var(--green); text-shadow: 0 0 20px rgba(0,255,136,0.3); margin: 10px 0;}

        /* Ticket Express */
        .express-container { display: flex; gap: 10px; margin-bottom: 15px; justify-content: center;}
        .btn-express { flex: 1; background: #000; border: 1px solid var(--blue); color: var(--blue); padding: 10px; border-radius: 8px; font-weight: 900; cursor: pointer; transition: 0.3s; font-size: 12px;}
        .btn-express:hover { background: var(--blue); color: #fff; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(112,0,255,0.3);}

        /* Operaciones */
        .operaciones-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px;}
        @media (max-width: 600px) { .operaciones-grid { grid-template-columns: 1fr; } }
        
        .op-card { background: var(--panel); border: 1px solid var(--border); padding: 20px; border-radius: 12px;}
        .op-titulo { font-size: 12px; font-weight: 900; text-transform: uppercase; margin-bottom: 15px; color: var(--text-muted);}
        .input-dark { width: 100%; background: #000; border: 1px solid var(--border); padding: 12px; border-radius: 8px; color: #fff; font-size: 16px; outline: none; transition: 0.3s; font-family: 'Roboto Mono', monospace; margin-bottom: 12px;}
        
        .btn-op { width: 100%; padding: 14px; border-radius: 8px; font-weight: 900; text-transform: uppercase; font-size: 13px; border: none; cursor: pointer; transition: 0.3s;}
        .btn-carga { background: var(--blue); color: #fff; }
        .btn-retiro { background: transparent; border: 1px solid var(--red); color: var(--red); }

        /* Banner VIP */
        .banner-vip { background: linear-gradient(90deg, #1a1500, #332b00); border: 1px solid var(--yellow); padding: 20px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: 0.3s; text-decoration: none;}
        .banner-vip:hover { transform: scale(1.02); box-shadow: 0 0 25px rgba(255,215,0,0.2); }
        .banner-text h3 { margin: 0; color: var(--yellow); font-size: 16px; text-transform: uppercase;}
        .banner-text p { margin: 5px 0 0 0; font-size: 11px; color: #fff; opacity: 0.7;}
        .banner-icon { font-size: 30px; }

        /* Chat Widget */
        .chat-widget { position: fixed; bottom: 20px; right: 20px; z-index: 1000;}
        .chat-burbuja { width: 55px; height: 55px; background: var(--blue); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 10px 25px rgba(112,0,255,0.4); font-size: 24px; position: relative;}
        .notif-badge { position: absolute; top: -5px; right: -5px; background: var(--red); color: #fff; width: 22px; height: 22px; border-radius: 50%; font-size: 10px; display: none; align-items: center; justify-content: center; font-weight: 900; border: 2px solid var(--bg);}
        
        .chat-panel { width: 320px; height: 450px; background: var(--panel); border: 1px solid var(--border); border-radius: 16px; margin-bottom: 15px; display: none; flex-direction: column; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.9);}
        .chat-panel.abierto { display: flex; animation: slideUp 0.3s ease; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .c-header { background: #000; padding: 15px; border-bottom: 1px solid var(--border); font-weight: 900; font-size: 12px; text-align: center; color: var(--blue);}
        .c-body { flex: 1; padding: 15px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; background: #030406;}
        .msg { max-width: 85%; padding: 10px 14px; border-radius: 12px; font-size: 13px; font-weight: bold;}
        .msg.yo { background: #161b22; color: #fff; align-self: flex-end; border: 1px solid var(--border);}
        .msg.admin { background: var(--blue); color: #fff; align-self: flex-start; }
    </style>
</head>
<body>
    
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php
if (isset($_SESSION['toast_exito'])) {
    echo "<script>Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: '".$_SESSION['toast_exito']."', showConfirmButton: false, timer: 3000, background: '#0a0c10', color: '#00ff88' });</script>";
    unset($_SESSION['toast_exito']);
}
if (isset($_SESSION['toast_error'])) {
    echo "<script>Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: '".$_SESSION['toast_error']."', showConfirmButton: false, timer: 4500, background: '#0a0c10', color: '#ff3366' });</script>";
    unset($_SESSION['toast_error']);
}
?>

<nav class="topbar">
    <a href="#" class="logo">EL <span>POINT</span></a>
    <div class="nav-btns">
        <a href="vip.php" class="btn-nav btn-vip">Premios 👑</a>
        <a href="logout_jugador.php" class="btn-nav" style="border-color:var(--red); color:var(--red);">Salir</a>
    </div>
</nav>

<main class="main-content">
    
    <?php echo $mensaje; ?>

    <div class="saldo-card">
        <div style="font-size:11px; font-weight:900; color:var(--text-muted); text-transform:uppercase; letter-spacing:2px;">Saldo Disponible</div>
        <div class="saldo-monto" id="saldo-pantalla">$0.00</div>
        <div style="font-size:10px; color:var(--text-muted);">Jugador: <?php echo strtoupper($jugador); ?></div>
    </div>

    <a href="vip.php" class="banner-vip">
        <div class="banner-text">
            <h3>Centro de Recompensas VIP</h3>
            <p>Girá la Ruleta Diaria y traé amigos para ganar Wager.</p>
        </div>
        <div class="banner-icon">🎰</div>
    </a>

    <div style="margin: 30px 0 10px 0; font-size: 11px; font-weight: 900; color: var(--text-muted); text-align: center; text-transform: uppercase; letter-spacing: 2px;">Caja Rápida</div>

    <div class="operaciones-grid">
        <div class="op-card">
            <div class="op-titulo">Ingresar Fichas</div>
            
            <div class="express-container">
                <button class="btn-express" onclick="setMonto(2000)">+$2.000</button>
                <button class="btn-express" onclick="setMonto(5000)">+$5.000</button>
                <button class="btn-express" onclick="setMonto(10000)">+$10.000</button>
            </div>

            <form action="procesar_ticket.php" method="POST">
                <input type="hidden" name="tipo_ticket" value="Carga">
                <input type="number" id="inputMontoCarga" name="monto_ticket_carga" class="input-dark" placeholder="Monto" required min="1500">
                <button type="submit" class="btn-op btn-carga">Generar Ticket</button>
            </form>
        </div>

        <div class="op-card">
            <div class="op-titulo">Retirar Ganancias</div>
            <form action="procesar_ticket.php" method="POST">
                <input type="hidden" name="tipo_ticket" value="Retiro">
                <input type="number" name="monto_ticket_retiro" class="input-dark" placeholder="Monto" required min="3000">
                <button type="submit" class="btn-op btn-retiro">Pedir Retiro</button>
            </form>
        </div>
    </div>
</main>

<div class="chat-widget">
    <div class="chat-panel" id="panelChat">
        <div class="c-header">SOPORTE 24/7</div>
        <div class="c-body" id="boxMsgs"></div>
        <div style="padding:10px; background:#000; display:flex; gap:5px;">
            <input type="text" id="inChat" style="flex:1; background:#111; border:1px solid var(--border); color:#fff; padding:10px; border-radius:8px; outline:none;" placeholder="Escribí acá...">
            <button onclick="enviarMsj()" style="background:var(--blue); border:none; color:#fff; padding:0 15px; border-radius:8px; cursor:pointer;">➤</button>
        </div>
    </div>
    <div class="chat-burbuja" onclick="toggleChat()">
        💬
        <div class="notif-badge" id="notifChat">0</div>
    </div>
</div>

<script>
function setMonto(v) { document.getElementById('inputMontoCarga').value = v; }

function actualizarSaldo() {
    fetch('obtener_saldo.php').then(r => r.json()).then(data => {
        if(data.saldo !== undefined) {
            document.getElementById('saldo-pantalla').innerText = '$' + parseFloat(data.saldo).toLocaleString('es-AR', { minimumFractionDigits: 2 });
        }
    });
}

function checkNotificaciones() {
    fetch('api_chat.php?accion=check_notificaciones').then(r => r.json()).then(data => {
        let b = document.getElementById('notifChat');
        if(data.n > 0) { b.innerText = data.n; b.style.display = 'flex'; }
        else { b.style.display = 'none'; }
    });
}

let chatAbierto = false;
function toggleChat() {
    chatAbierto = !chatAbierto;
    document.getElementById('panelChat').classList.toggle('abierto', chatAbierto);
    if(chatAbierto) refrescarMsgs();
}

function refrescarMsgs() {
    if(!chatAbierto) return;
    fetch('api_chat.php?accion=fetch_jugador').then(r => r.text()).then(html => {
        let box = document.getElementById('boxMsgs');
        if(box.innerHTML !== html) { box.innerHTML = html; box.scrollTop = box.scrollHeight; }
    });
}

function enviarMsj() {
    let inp = document.getElementById('inChat');
    if(inp.value.trim() === '') return;
    let fd = new FormData(); fd.append('accion', 'send_jugador'); fd.append('mensaje', inp.value);
    inp.value = '';
    fetch('api_chat.php', {method: 'POST', body: fd}).then(() => refrescarMsgs());
}

setInterval(actualizarSaldo, 3000);
setInterval(checkNotificaciones, 4000);
setInterval(refrescarMsgs, 3000);
actualizarSaldo();
</script>
</body>
</html>