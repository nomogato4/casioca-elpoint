<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['jugador'])) {
    header("Location: login_jugador.php");
    exit;
}

$user = $_SESSION['jugador'];

// Obtenemos datos del jugador (Saldo, IP, etc.)
$query = mysqli_query($conexion, "SELECT saldo, ip_registro, fecha_registro FROM jugadores WHERE usuario = '$user'");
$datos = mysqli_fetch_assoc($query);

// Generamos el link de referido único (usando su nombre de usuario)
$link_referido = "https://elpoint.com/registro.php?ref=" . urlencode($user);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Panel VIP | EL POINT</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Roboto+Mono:wght@700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        :root { --bg: #030304; --panel: #0a0c10; --border: #1a1e26; --gold: #ffd700; --gold-glow: rgba(255, 215, 0, 0.3); --text: #ffffff; --text-muted: #6b7280; --blue: #7000ff; }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding-bottom: 50px; }
        
        .header-vip { background: var(--panel); padding: 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 15px; position: sticky; top: 0; z-index: 10; }
        .btn-back { text-decoration: none; color: var(--text-muted); font-size: 20px; font-weight: bold; }
        .header-vip h1 { margin: 0; font-size: 18px; text-transform: uppercase; letter-spacing: 1px; color: var(--gold); }

        .container { padding: 20px; max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; }

        /* SECCIÓN RULETA */
        .card-ruleta { background: var(--panel); border: 1px solid var(--gold); border-radius: 20px; padding: 30px 10px; text-align: center; position: relative; overflow: hidden; box-shadow: 0 0 30px var(--gold-glow); }
        .canvas-container { position: relative; width: 280px; height: 280px; margin: 0 auto 20px auto; }
        #canvas { width: 100%; height: 100%; transition: transform 4s cubic-bezier(0.15, 0, 0.15, 1); }
        .flecha-ruleta { position: absolute; top: -10px; left: 50%; transform: translateX(-50%); width: 30px; z-index: 5; filter: drop-shadow(0 0 5px #000); }
        .btn-girar { background: var(--gold); color: #000; border: none; padding: 15px 40px; border-radius: 50px; font-weight: 900; text-transform: uppercase; cursor: pointer; box-shadow: 0 5px 15px var(--gold-glow); transition: 0.2s; }
        .btn-girar:disabled { opacity: 0.5; cursor: not-allowed; filter: grayscale(1); }

        /* SECCIÓN REFERIDOS */
        .card-vip { background: var(--panel); border: 1px solid var(--border); border-radius: 16px; padding: 20px; }
        .titulo-seccion { font-size: 12px; font-weight: 900; text-transform: uppercase; color: var(--gold); margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        
        .ref-box { background: #000; border: 1px dashed var(--border); padding: 15px; border-radius: 12px; margin-bottom: 15px; text-align: center; }
        .link-text { font-family: 'Roboto Mono', monospace; font-size: 11px; color: var(--blue); word-break: break-all; display: block; margin-bottom: 10px; }
        #qrcode { display: flex; justify-content: center; margin: 15px 0; padding: 10px; background: #fff; border-radius: 10px; width: fit-content; margin-left: auto; margin-right: auto; }
        
        /* SEGURIDAD */
        .info-seguridad { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); padding: 10px; border-top: 1px solid var(--border); margin-top: 10px; }
        .badge-seguro { color: #00ff88; font-weight: bold; }

        /* MODAL PREMIO */
        #modalPremio { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); display: none; align-items: center; justify-content: center; z-index: 1000; text-align: center; }
        .premio-content { background: var(--panel); border: 2px solid var(--gold); padding: 40px; border-radius: 20px; animation: pop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        @keyframes pop { from { transform: scale(0.5); } to { transform: scale(1); } }
    </style>
</head>
<body>

<div class="header-vip">
    <a href="lobby.php" class="btn-back">←</a>
    <h1>Club VIP El Point</h1>
</div>

<div class="container">

    <div class="card-ruleta">
        <div class="titulo-seccion" style="justify-content: center;">🎰 Tu Giro Diario Gratís</div>
        <div class="canvas-container">
            <img src="https://cdn-icons-png.flaticon.com/512/3588/3588294.png" class="flecha-ruleta">
            <canvas id="canvas" width="500" height="500"></canvas>
        </div>
        <button id="btnGirar" class="btn-girar" onclick="girarRuleta()">¡Girar Ahora!</button>
        <p style="font-size: 10px; color: var(--text-muted); margin-top: 15px;">Solo disponible para usuarios con carga activa.</p>
    </div>

    <div class="card-vip">
        <div class="titulo-seccion">🔗 Sistema de Referidos</div>
        <p style="font-size: 13px; margin-top: 0;">Invitá a tus amigos y ganá un % de sus jugadas directamente a tu Wager.</p>
        
        <div class="ref-box">
            <span class="link-text" id="linkRef"><?php echo $link_referido; ?></span>
            <button onclick="copiarLink()" style="background:var(--blue); color:#fff; border:none; padding:8px 15px; border-radius:6px; font-size:11px; font-weight:bold; cursor:pointer;">Copiar Link</button>
            
            <div id="qrcode"></div>
            <p style="font-size: 10px; color: var(--text-muted);">Mostrá este QR para que escaneen tu link</p>
        </div>
    </div>

    <div class="card-vip" style="border-color: #161b22;">
        <div class="titulo-seccion" style="color: var(--text-muted);">🕵️ Transparencia y Seguridad</div>
        <div class="info-seguridad">
            <span>Tu IP Actual: <strong><?php echo $_SERVER['REMOTE_ADDR']; ?></strong></span>
            <span class="badge-seguro">● Conexión Segura</span>
        </div>
        <div class="info-seguridad">
            <span>Miembro desde: <?php echo date("d/m/Y", strtotime($datos['fecha_registro'])); ?></span>
            <span>Cuenta Verificada</span>
        </div>
    </div>

</div>

<div id="modalPremio">
    <div class="premio-content">
        <h2 style="color: var(--gold); margin: 0;">¡FELICITACIONES!</h2>
        <p id="textoPremio" style="font-size: 24px; font-weight: 900; margin: 20px 0;"></p>
        <button class="btn-girar" onclick="location.reload()">Aceptar</button>
    </div>
</div>

<script>
// CONFIGURACIÓN DE LA RULETA
const canvas = document.getElementById("canvas");
const ctx = canvas.getContext("2d");
const premios = [
    {n: "$500", c: "#1a1e26"}, {n: "Sigue Participando", c: "#000"},
    {n: "$1.000", c: "#7000ff"}, {n: "Casi!", c: "#000"},
    {n: "$2.000", c: "#ffd700"}, {n: "Vuelve mañana", c: "#000"},
    {n: "Ticket $5k", c: "#00ff88"}, {n: "X", c: "#000"}
];
const tot = premios.length;
const rad = canvas.width / 2;
const ang = 2 * Math.PI / tot;

function dibujarRuleta() {
    premios.forEach((p, i) => {
        ctx.beginPath();
        ctx.fillStyle = p.c;
        ctx.moveTo(rad, rad);
        ctx.arc(rad, rad, rad, i * ang, (i + 1) * ang);
        ctx.fill();
        ctx.save();
        ctx.translate(rad, rad);
        ctx.rotate(i * ang + ang / 2);
        ctx.textAlign = "right";
        ctx.fillStyle = "#fff";
        ctx.font = "bold 25px Inter";
        ctx.fillText(p.n, rad - 20, 10);
        ctx.restore();
    });
}
dibujarRuleta();

let girando = false;
function girarRuleta() {
    if (girando) return;
    girando = true;
    document.getElementById('btnGirar').disabled = true;
    
    const vueltas = 5 + Math.floor(Math.random() * 5);
    const sectorInvertido = Math.floor(Math.random() * tot);
    const gradosFinales = (vueltas * 360) + (sectorInvertido * (360/tot));
    
    canvas.style.transform = `rotate(${gradosFinales}deg)`;
    
    setTimeout(() => {
        // Lógica simple para mostrar el premio (invertido por la rotación)
        const realIndex = (tot - (sectorInvertido % tot)) % tot;
        const resultado = premios[realIndex].n;
        
        document.getElementById('textoPremio').innerText = "Ganaste: " + resultado;
        document.getElementById('modalPremio').style.display = 'flex';
    }, 4500);
}

// GENERADOR DE QR
new QRCode(document.getElementById("qrcode"), {
    text: "<?php echo $link_referido; ?>",
    width: 128,
    height: 128,
    colorDark : "#000000",
    colorLight : "#ffffff"
});

function copiarLink() {
    const el = document.createElement('textarea');
    el.value = document.getElementById('linkRef').innerText;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
    alert("¡Link copiado para compartir!");
}
</script>

</body>
</html>