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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Soporte VIP | EL POINT</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Roboto+Mono:wght@700;900&display=swap" rel="stylesheet">
    <style>
        /* --- ESTÉTICA VIP - EL POINT --- */
        :root { --bg: #030304; --panel: #0a0c10; --border: #1a1e26; --green: #00ff88; --blue: #7000ff; --red: #ff3366; --yellow: #ffd700; --text: #ffffff; --text-muted: #6b7280; }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }

        /* Navbar Staff & Controles Supremos */
        .topbar { background: var(--panel); border-bottom: 1px solid var(--border); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; z-index: 100;}
        .logo { color: #fff; font-weight: 900; font-size: 1.5rem; text-decoration: none; text-transform: uppercase; letter-spacing: -1px;} 
        .logo span { color: var(--blue); text-shadow: 0 0 15px rgba(112,0,255,0.4); }
        .badge-rol { padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 900; text-transform: uppercase; margin-left: 10px;}
        .badge-admin { background: rgba(255,51,102,0.1); color: var(--red); border: 1px solid var(--red); }
        .badge-cajero { background: rgba(112,0,255,0.1); color: var(--blue); border: 1px solid var(--blue); }

        .controles-supremos { display: flex; gap: 10px; }
        .btn-supremo { background: #000; border: 1px solid var(--border); color: #fff; padding: 8px 12px; border-radius: 8px; font-size: 14px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 6px; font-weight: 900;}
        .btn-supremo:hover { background: rgba(255,255,255,0.1); }
        .btn-panico { border-color: var(--red); color: var(--red); } .btn-panico:hover { background: var(--red); color: #fff;}
        .btn-lluvia { border-color: var(--yellow); color: var(--yellow); } .btn-lluvia:hover { background: var(--yellow); color: #000;}

        .main-wrapper { flex: 1; display: flex; padding: 20px; gap: 20px; max-width: 1400px; margin: 0 auto; width: 100%; height: calc(100vh - 70px);}

        /* Bandeja de Entrada */
        .lista-contactos { width: 320px; background: var(--panel); border: 1px solid var(--border); border-radius: 12px; display: flex; flex-direction: column; overflow: hidden;}
        .header-lista { padding: 20px; background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border); font-weight: 900; font-size: 13px; text-transform: uppercase; color: var(--text-muted); display:flex; justify-content:space-between; align-items:center;}
        
        /* Toggle Espectador */
        .toggle-espectador { font-size:10px; cursor:pointer; padding:4px 8px; border:1px solid var(--text-muted); border-radius:4px; transition:0.3s;}
        .toggle-espectador.activo { background:var(--blue); color:#fff; border-color:var(--blue); }

        .buscador-caja { padding: 15px; border-bottom: 1px solid var(--border);}
        .input-buscador { width: 100%; background: #000; border: 1px solid var(--border); padding: 10px 15px; border-radius: 8px; color: #fff; font-size: 12px; outline: none; transition: 0.2s;}
        .input-buscador:focus { border-color: var(--blue); }
        .contactos-box { flex: 1; overflow-y: auto; }
        
        .contacto { padding: 15px; border-bottom: 1px solid var(--border); cursor: pointer; transition: 0.2s; border-left: 3px solid transparent; position: relative;} 
        .contacto.tomado { opacity: 0.5; cursor: not-allowed; }
        .contacto:hover:not(.tomado) { background: rgba(255,255,255,0.02); border-left-color: var(--blue);}
        .c-nombre { font-weight: 900; color: #fff; font-size: 13px; margin-bottom: 5px; display:flex; justify-content:space-between; align-items: center; text-transform: uppercase;} 
        .c-prev { font-size: 11px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
        .badge-lock { font-size: 10px; color: var(--red); position: absolute; right: 10px; top: 15px;}

        /* Área de Chat */
        .chat-area { flex: 1; display: flex; flex-direction: column; background: var(--panel); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; position: relative;}
        .esperando-box { flex: 1; display: flex; align-items: center; justify-content: center; flex-direction: column; color: var(--text-muted); font-weight: 900; font-size: 14px; text-transform: uppercase;}
        
        #contenidoChat { display: none; flex-direction: column; height: 100%; }

        /* Cabecera del Chat & Herramientas */
        .chat-header { padding: 15px 20px; background: #000; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .chat-header-izq { font-weight: 900; color: #fff; font-size: 18px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; }
        
        /* Semáforo RTP */
        .semaforo { width: 12px; height: 12px; border-radius: 50%; box-shadow: 0 0 10px currentColor; }
        .semaforo.verde { color: var(--green); background: var(--green); }
        .semaforo.rojo { color: var(--red); background: var(--red); }
        .semaforo.amarillo { color: var(--yellow); background: var(--yellow); }

        .herramientas-chat { display: flex; gap: 8px; }
        .btn-herramienta { background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 14px; transition: 0.3s;}
        .btn-herramienta:hover { background: rgba(255,255,255,0.1); color: #fff; border-color: #fff;}

        /* Caja Fuerte y Retiro Inteligente */
        .panel-acciones { display: flex; gap: 10px; padding: 15px 20px; background: rgba(0,0,0,0.5); border-bottom: 1px solid var(--border); align-items: center; flex-wrap: wrap;}
        .input-monto { background: var(--panel); border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 8px; width: 140px; outline: none; font-family: 'Roboto Mono', monospace; font-size: 16px; font-weight: 900; text-align: center;} 
        
        .btn-accion { border: none; padding: 12px 20px; border-radius: 8px; font-weight: 900; cursor: pointer; font-size: 11px; text-transform: uppercase; display: flex; align-items: center; gap: 6px; transition: 0.3s;}
        .btn-cargar { background: var(--green); color: #000; } 
        .btn-pagar { background: rgba(255,51,102,0.1); border: 1px solid var(--red); color: var(--red); }
        .btn-limpiar { background: transparent; border: 1px solid var(--text-muted); color: var(--text-muted); margin-left: auto; }
        .btn-transferir { background: transparent; border: 1px dashed var(--yellow); color: var(--yellow); }

        /* Burbujas de Chat */
        .mensajes-box { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; scroll-behavior: smooth;}
        .msg-burbuja { max-width: 75%; padding: 12px 18px; border-radius: 12px; font-size: 13px; line-height: 1.5; font-weight: bold;}
        .msg-yo { background: var(--blue); color: #fff; align-self: flex-end; border-bottom-right-radius: 2px;}
        .msg-jugador { background: #000; color: #fff; border: 1px solid var(--border); align-self: flex-start; border-bottom-left-radius: 2px; }
        
        .escribiendo { font-size: 11px; color: var(--green); font-style: italic; margin-top: 5px; display: none; padding: 0 20px; }

        /* Input y Adjuntos */
        .chat-input-area { padding: 15px 20px; display: flex; gap: 10px; align-items: center; border-top: 1px solid var(--border); background: var(--panel);}
        .btn-clip { background: #000; border: 1px solid var(--border); color: var(--text-muted); width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 20px; transition: 0.3s;}
        .chat-input { flex: 1; background: #000; border: 1px solid var(--border); padding: 15px; border-radius: 8px; color: #fff; font-size: 13px; outline: none; } 
        .btn-enviar { background: var(--blue); color: #fff; border: none; padding: 0 25px; height: 48px; border-radius: 8px; font-weight: 900; cursor: pointer; text-transform: uppercase; font-size: 12px;} 

        /* Modales */
        .modal-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.9); z-index: 3000; align-items: center; justify-content: center; padding: 20px;}
        .modal-content { background: var(--panel); border: 1px solid var(--border); padding: 30px; border-radius: 16px; width: 100%; max-width: 500px; max-height: 80vh; overflow-y: auto;}
        .galeria-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px;}
        .galeria-grid img { width: 100%; border-radius: 8px; border: 1px solid var(--border); cursor: pointer;}
    </style>
</head>
<body>

<nav class="topbar">
    <div style="display:flex; align-items:center;">
        <a href="dashboard.php" class="logo" style="margin-right:20px;">EL <span>POINT</span></a>
        <span class="badge-rol <?php echo ($rol == 'admin') ? 'badge-admin' : 'badge-cajero'; ?>"><?php echo strtoupper($rol); ?></span>
        <span style="font-size:10px; margin-left:10px; color:var(--text-muted);">Atajos: [Alt+C] Carga | [Alt+R] Retiro | [Esc] Cerrar</span>
    </div>
    
    <div class="controles-supremos">
        <?php if($es_admin): ?>
            <button class="btn-supremo btn-lluvia">🌧️ Lluvia</button>
            <button class="btn-supremo btn-panico">🚨 PÁNICO</button>
        <?php else: ?>
            <a href="dashboard.php" class="btn-supremo">← Volver</a>
        <?php endif; ?>
    </div>
</nav>

<div class="main-wrapper">
    
    <div class="lista-contactos">
        <div class="header-lista">
            💬 Tíckets
            <?php if($es_admin): ?>
                <span class="toggle-espectador" id="btnEspectador" onclick="toggleEspectador()">Modo Espectador OFF</span>
            <?php endif; ?>
        </div>
        <div class="buscador-caja"><input type="text" id="filtroContactos" class="input-buscador" placeholder="Buscar jugador..." onkeyup="filtrarLista()"></div>
        <div class="contactos-box" id="listaContactos">
            <div style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 12px;">Cargando tickets...</div>
        </div>
    </div>
    
    <div class="chat-area">
        <div class="esperando-box" id="msgEsperando"><div style="font-size: 40px; margin-bottom: 10px;">🎧</div>SELECCIONÁ UN TICKET PARA ATENDERLO</div>

        <div id="contenidoChat">
            <div class="chat-header">
                <div class="chat-header-izq">
                    <div id="semaforoRTP" class="semaforo verde" title="Rentabilidad del Jugador"></div>
                    <span id="chatNombre">JUGADOR</span>
                </div>
                <div class="herramientas-chat">
                    <button class="btn-herramienta" onclick="abrirGaleria()" title="Galería de Comprobantes">🖼️ Galería</button>
                    <button class="btn-herramienta" onclick="silenciarJugador()" title="Mutear">🔇 Mute</button>
                </div>
            </div>

            <div class="panel-acciones">
                <input type="number" id="montoRapido" class="input-monto" placeholder="$ Monto">
                <button class="btn-accion btn-cargar" onclick="accionRapida('sumar')">APROBAR CARGA</button>
                <button class="btn-accion btn-pagar" id="btnRetiroDin" onclick="accionRapida('restar')">APROBAR RETIRO</button>
                
                <?php if(!$es_admin): ?>
                    <button class="btn-accion btn-transferir" onclick="transferirAdmin()">PASAR AL ADMIN</button>
                <?php endif; ?>
                
                <button class="btn-accion btn-limpiar" onclick="cerrarChat()">🧹 CERRAR TICKET</button>
            </div>

            <div class="mensajes-box" id="cajaMensajes"></div>
            <div class="escribiendo" id="boxEscribiendo">El jugador está escribiendo...</div>
            
            <div class="chat-input-area">
                <label for="fotoAdminFile" class="btn-clip" title="Adjuntar imagen">📎</label>
                <input type="file" id="fotoAdminFile" accept="image/*" style="display:none" onchange="enviarAdmin(true)">
                <input type="text" id="inputAdmin" class="chat-input" placeholder="Escribí tu mensaje acá..." onkeypress="if(event.key === 'Enter') enviarAdmin(false)">
                <button class="btn-enviar" onclick="enviarAdmin(false)">ENVIAR</button>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalGaleria">
    <div class="modal-content">
        <h2 style="color:var(--text); margin-top:0;">🖼️ Comprobantes</h2>
        <div class="galeria-grid" id="cajaGaleria">Cargando...</div>
        <button onclick="document.getElementById('modalGaleria').style.display='none'" style="width:100%; background:#111; border:none; color:var(--text-muted); padding:15px; margin-top:15px; border-radius:8px; cursor:pointer;">CERRAR</button>
    </div>
</div>

<script>
let chatActualId = ""; 
let listaCache = "";
let modoEspectador = false;
let timeoutInactividad;

// --- MOTORES PRINCIPALES ---
function cargarContactos() { 
    let fd = new FormData(); fd.append('accion', 'fetch_admin_users');
    fetch('api_chat.php', {method: 'POST', body: fd}).then(r=>r.text()).then(h => { 
        if(listaCache !== h) { document.getElementById('listaContactos').innerHTML = h; listaCache = h; filtrarLista(); }
    }); 
}

function filtrarLista() {
    let input = document.getElementById('filtroContactos').value.toLowerCase();
    let contactos = document.getElementsByClassName('contacto');
    for (let i = 0; i < contactos.length; i++) {
        let nombre = contactos[i].innerText.toLowerCase();
        contactos[i].style.display = (nombre.indexOf(input) > -1) ? "" : "none";
    }
}

// --- TRABAJO EN EQUIPO (CLAIM) ---
function cargarChatAdmin(username, tomadoPorOtro) { 
    if (tomadoPorOtro && !modoEspectador) {
        alert("⚠️ Este ticket ya lo está atendiendo otro cajero."); return;
    }

    chatActualId = username; 
    document.getElementById('chatNombre').innerText = username; 
    document.getElementById('msgEsperando').style.display = 'none'; 
    document.getElementById('contenidoChat').style.display = 'flex';
    document.getElementById('montoRapido').value = ''; 
    
    // Avisar al servidor que tomamos el chat (Bloqueo para otros)
    if(!modoEspectador) {
        let fd = new FormData(); fd.append('accion', 'tomar_chat'); fd.append('id_usuario', username);
        fetch('api_chat.php', {method: 'POST', body: fd});
    }

    // Calcular Semáforo RTP (Simulado - Se conecta a DB real en api_chat)
    let randomSemaforo = ['verde', 'amarillo', 'rojo'][Math.floor(Math.random() * 3)];
    document.getElementById('semaforoRTP').className = "semaforo " + randomSemaforo;

    refrescarChat(); 
    resetInactividad();
    setTimeout(() => { document.getElementById('inputAdmin').focus(); }, 100); 
}

function refrescarChat() { 
    if(chatActualId === "") return; 
    let fd = new FormData(); fd.append('accion', 'fetch_admin_chat'); fd.append('id_usuario', chatActualId);
    fetch('api_chat.php', {method: 'POST', body: fd}).then(r=>r.json()).then(data => { 
        let div = document.getElementById('cajaMensajes'); 
        let isBottom = div.scrollHeight - div.scrollTop <= div.clientHeight + 50; 
        if(div.innerHTML !== data.html) { div.innerHTML = data.html; if(isBottom) div.scrollTop = div.scrollHeight; }
        
        // Indicador de escribiendo
        document.getElementById('boxEscribiendo').style.display = data.escribiendo ? 'block' : 'none';
    }); 
}

// --- ACCIONES Y ENVIOS ---
function enviarAdmin(esFoto) { 
    if(chatActualId === "" || modoEspectador) return; 
    let fd = new FormData(); fd.append('accion', 'send_admin'); fd.append('id_usuario', chatActualId); 
    
    if (esFoto) { 
        let inputF = document.getElementById('fotoAdminFile'); if(!inputF.files[0]) return; 
        fd.append('foto', inputF.files[0]); inputF.value = ''; 
    } else { 
        let inputM = document.getElementById('inputAdmin'); if(inputM.value.trim() === '') return; 
        fd.append('mensaje', inputM.value); inputM.value = ''; 
    } 
    fetch('api_chat.php', {method: 'POST', body: fd}).then(() => { refrescarChat(); resetInactividad(); }); 
}

function accionRapida(tipoAccion) { 
    if(chatActualId === "" || modoEspectador) return; 
    let monto = document.getElementById('montoRapido').value; 
    if (monto === '' || monto <= 0) { alert('Monto inválido.'); return; } 
    
    let fd = new FormData(); fd.append('accion', 'accion_rapida'); fd.append('id_usuario', chatActualId); fd.append('tipo_accion', tipoAccion); fd.append('monto', monto); 
    fetch('api_chat.php', {method: 'POST', body: fd}).then(() => { 
        document.getElementById('montoRapido').value = ''; refrescarChat(); cargarContactos(); resetInactividad();
    }); 
}

// --- LIMPIEZA, TRANSFERENCIA Y MACROS ---
function cerrarChat() {
    if(chatActualId === "") return;
    if(!confirm('¿Cerrar y archivar este ticket?')) return;
    
    let fd = new FormData(); fd.append('accion', 'liberar_chat'); fd.append('id_usuario', chatActualId); 
    fetch('api_chat.php', {method: 'POST', body: fd}).then(() => {
        chatActualId = ""; document.getElementById('contenidoChat').style.display = 'none'; 
        document.getElementById('msgEsperando').style.display = 'flex'; cargarContactos();
    });
}

function transferirAdmin() {
    if(!confirm('¿Pasar este ticket al Administrador?')) return;
    let fd = new FormData(); fd.append('accion', 'transferir_admin'); fd.append('id_usuario', chatActualId); 
    fetch('api_chat.php', {method: 'POST', body: fd}).then(() => cerrarChat());
}

function resetInactividad() {
    clearTimeout(timeoutInactividad);
    // Cierre automático por inactividad a los 10 minutos (600.000 ms)
    timeoutInactividad = setTimeout(() => { if(chatActualId !== "") cerrarChat(); }, 600000);
}

// Atajos de Teclado (Macros)
document.addEventListener('keydown', function(e) {
    if (chatActualId === "") return;
    if (e.altKey && e.key.toLowerCase() === 'c') { e.preventDefault(); accionRapida('sumar'); }
    if (e.altKey && e.key.toLowerCase() === 'r') { e.preventDefault(); accionRapida('restar'); }
    if (e.key === 'Escape') { e.preventDefault(); cerrarChat(); }
});

// --- MODO ESPECTADOR (Solo Admin) ---
function toggleEspectador() {
    modoEspectador = !modoEspectador;
    let btn = document.getElementById('btnEspectador');
    if(modoEspectador) { btn.classList.add('activo'); btn.innerText = "Modo Espectador ON"; } 
    else { btn.classList.remove('activo'); btn.innerText = "Modo Espectador OFF"; }
}

function abrirGaleria() {
    document.getElementById('modalGaleria').style.display = 'flex';
    // Acá iría un fetch a la API para traer solo imágenes enviadas por el usuario actual
}

// Bucle maestro
setInterval(cargarContactos, 3000); 
setInterval(refrescarChat, 3000); 
cargarContactos();
</script>

</body>
</html>