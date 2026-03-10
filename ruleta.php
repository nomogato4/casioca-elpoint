<?php
session_start();
include 'conexion.php'; // Asegurar que conexion esté para obtener el saldo
if (!isset($_SESSION['jugador'])) {
    header("Location: login_jugador.php"); // Asegurar que login_jugador esté para expulsar
    exit;
}
$username = $_SESSION['jugador'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ruleta Europea Pro | Mi Apuesta</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Roboto+Mono:wght@700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Colores Base Profundos */
            --bg: #0c0f13; /* Fondo ligeramente texturizado, más profundo que el negro puro */
            --panel: #0f131a; 
            --border: #1f2530; 
            --text: #c9d1d9; 
            --text-muted: #8b949e;

            /* Colores Casino Neon */
            --green: #2ecc71; 
            --red: #e74c3c; 
            --blue: #3498db; 
            --purple: #9b59b6; 
            --yellow: #f1c40f; 
            
            /* Color del fieltro profundo */
            --pano-deep: #0b4a26;
            --pano-brilliant: #127a3f;
        }

        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; user-select: none; transition: background 0.1s, transform 0.1s; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 0; display: flex; flex-direction: column; height: 100vh; overflow: hidden; background-image: radial-gradient(#1f2530 1px, transparent 1px); background-size: 20px 20px; }

        /* 🔥 HEADER INTERFAZ MEJORADO (EstiloNavbar) 🔥 */
        .topbar { background: var(--panel); border-bottom: 2px solid var(--border); padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; z-index: 100; box-shadow: 0 5px 15px rgba(0,0,0,0.4); }
        .logo { color: #fff; font-weight: 900; font-size: 1.3rem; text-decoration: none; letter-spacing: -0.5px;} .logo span { color: var(--yellow); }
        .btn-volver { color: var(--text-muted); text-decoration: none; font-weight: 700; background: rgba(255,255,255,0.03); padding: 8px 15px; border-radius: 8px; border: 1px solid var(--border); display: flex; align-items: center; gap: 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;}
        .btn-volver:hover { color: #fff; border-color: var(--text-muted); background: rgba(255,255,255,0.05); }
        .saldo-box { display: flex; gap: 12px; }
        .s-real, .s-bono { padding: 8px 12px; border-radius: 8px; font-family: 'Roboto Mono', monospace; font-size: 13px; font-weight: 900; display: flex; align-items: center; gap: 6px; border: 1px solid; box-shadow: inset 0 0 10px rgba(0,0,0,0.5); }
        .s-real { background: rgba(46,204,113,0.1); border-color: rgba(46,204,113,0.3); color: var(--green); text-shadow: 0 0 10px rgba(46,204,113,0.3); }
        .s-bono { background: rgba(241,196,15,0.1); border-color: rgba(241,196,15,0.3); color: var(--yellow); text-shadow: 0 0 10px rgba(241,196,15,0.3); }

        /* 🔥 ZONA DE JUEGO (Cámara Visual) 🔥 */
        .game-area { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 15px; overflow-y: auto; perspective: 1000px; /* Perspectiva 3D */ }
        
        /* 🔥 NUEVO VISOR VISUAL DE RULETA GIRANDO Y BOLA 🔥 */
        .wheel-container { width: 100%; max-width: 600px; height: 160px; background: var(--panel); border: 2px solid var(--yellow); border-radius: 20px; margin-bottom: 25px; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; box-shadow: 0 15px 40px rgba(241, 196, 15, 0.2); transition: 0.3s; transform-style: preserve-3d; }
        .wheel-container.spinning { box-shadow: 0 0 30px rgba(241, 196, 15, 0.4), inset 0 0 20px rgba(0,0,0,0.5); }
        
        /* Reemplazar la línea de .wheel-graphic actual por esta: */
.wheel-graphic { 
    position: absolute; width: 140px; height: 140px; border-radius: 50%; border: 3px solid #000; transition: transform 0.1s linear; box-shadow: 0 5px 15px rgba(0,0,0,0.8), inset 0 0 20px rgba(0,0,0,0.5); 
    /* RULETA GENERADA POR CSS (Rojo, Negro y Centro Verde) */
    background: radial-gradient(circle at center, #2ecc71 0%, #2ecc71 15%, transparent 16%), repeating-conic-gradient(#e74c3c 0 18deg, #1a1a1a 18deg 36deg);
}

        /* La Bola animada orbitando */
        .ball-path { position: absolute; width: 120px; height: 120px; border-radius: 50%; border: 2px dashed rgba(255,255,255,0.05); display: none; }
        .ball { position: absolute; top: -5px; left: calc(50% - 5px); width: 10px; height: 10px; background: #fff; border-radius: 50%; box-shadow: 0 0 15px #fff; display: none; }
        
        /* Estado de Giro Activo */
        .wheel-container.spinning .ball-path { display: block; animation: orbit_ball 0.8s linear infinite; /* Bola visual gira rápido */ }
        .wheel-container.spinning .ball { display: block; }
        
        /* Mensajes y Resultados */
        .wheel-info { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; background: rgba(0,0,0,0.7); padding: 10px 20px; border-radius: 12px; border: 1px solid var(--border); z-index: 10; width: 200px; }
        .r-mensaje { font-size: 11px; font-weight: 900; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px;}
        .r-numero { font-family: 'Roboto Mono', monospace; font-size: 50px; font-weight: 900; line-height: 1; text-shadow: 0 0 15px rgba(255,255,255,0.2); transition: 0.3s;}
        .n-rojo { color: var(--red); text-shadow: 0 0 15px rgba(231, 76, 60, 0.4); } .n-negro { color: #fff; text-shadow: 0 0 15px rgba(255,255,255,0.4); } .n-verde { color: var(--green); text-shadow: 0 0 15px rgba(46, 204, 113, 0.4); }

        /* Reemplazar la línea de .board actual por esta: */
.board { 
    display: grid; grid-template-columns: 50px repeat(12, 1fr) 55px; grid-template-rows: repeat(5, 48px); gap: 3px; padding: 8px; border-radius: 12px; border: 3px solid #08361b; min-width: 700px; box-shadow: inset 0 0 30px rgba(0,0,0,0.6), 0 10px 30px rgba(0,0,0,0.4);
    /* TEXTURA DE FIELTRO PURA EN CSS */
    background-color: var(--pano-deep);
    background-image: radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px), radial-gradient(rgba(255,255,255,0.03) 1px, transparent 1px);
    background-size: 10px 10px, 15px 15px; background-position: 0 0, 5px 5px;
}
        
        .b-zone { background: transparent; border: 1px solid rgba(255,255,255,0.1); color: #fff; font-weight: 900; font-size: 15px; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
        .b-zone:hover { background: rgba(255,255,255,0.05); }
        .b-zone:active { transform: scale(0.96); box-shadow: none; }
        
        /* Colores del Paño MEJORADOS con degradados visuales */
        .bg-rojo { background: linear-gradient(135deg, #c0392b, #8e2318) !important; border: 1px solid rgba(255,255,255,0.2); text-shadow: 0 1px 1px rgba(0,0,0,0.5); } 
        .bg-negro { background: linear-gradient(135deg, #1a1a1a, #000) !important; border: 1px solid rgba(255,255,255,0.1); text-shadow: 0 1px 1px rgba(0,0,0,0.5); }
        
        /* Distribución del Grid */
        #n_0 { grid-column: 1 / 2; grid-row: 1 / 4; border: 3px solid var(--green); background: linear-gradient(135deg, var(--green), #1b683e); }
        
        /* Filas de Plenos */
        .row-3 { grid-row: 1; } .row-2 { grid-row: 2; } .row-1 { grid-row: 3; }
        
        /* Columnas externas */
        #col_3 { grid-column: 14; grid-row: 1; font-size: 12px; color: var(--yellow);} #col_2 { grid-column: 14; grid-row: 2; font-size: 12px; color: var(--yellow);} #col_1 { grid-column: 14; grid-row: 3; font-size: 12px; color: var(--yellow);}
        
        /* Docenas */
        #docena_1, #docena_2, #docena_3 { color: var(--blue); }
        #docena_1 { grid-column: 2 / 6; grid-row: 4; } #docena_2 { grid-column: 6 / 10; grid-row: 4; } #docena_3 { grid-column: 10 / 14; grid-row: 4; }
        
        /* Suertes sencillas */
        #par, #impar, #mitad_1, #mitad_2 { color: var(--blue); }
        #mitad_1 { grid-column: 2 / 4; grid-row: 5; } #par { grid-column: 4 / 6; grid-row: 5; }
        #color_rojo { grid-column: 6 / 8; grid-row: 5; background: linear-gradient(135deg, #c0392b, #8e2318); border-color: rgba(255,255,255,0.2);} 
        #color_negro { grid-column: 8 / 10; grid-row: 5; background: linear-gradient(135deg, #1a1a1a, #000); border-color: rgba(255,255,255,0.1);}
        #impar { grid-column: 10 / 12; grid-row: 5; } #mitad_2 { grid-column: 12 / 14; grid-row: 5; }

        /* 🔥 CONTROLES Y Acciones Profundas 🔥 */
        .controls { width: 100%; max-width: 850px; background: var(--panel); padding: 15px; border-radius: 12px; border: 1px solid var(--border); display: flex; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: space-between; margin-top: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        
        .chips-container { display: flex; gap: 12px; padding-bottom: 5px;}
        
        /* 🔥 NUEVO DISEÑO DE FICHAS MEJORADO (Basado en image_5.png) 🔥 */
        .chip { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 14px; cursor: pointer; transition: 0.2s; position: relative; box-shadow: 0 6px 0 rgba(0,0,0,0.8), 0 6px 15px rgba(0,0,0,0.5); }
        .chip:active { transform: translateY(4px); box-shadow: 0 2px 0 rgba(0,0,0,0.8), 0 3px 5px rgba(0,0,0,0.5); }
        .chip::after { content: ''; position: absolute; width: 42px; height: 42px; border-radius: 50%; border: 3px dashed rgba(255,255,255,0.1); }
        .chip.active { transform: translateY(-4px) scale(1.1); box-shadow: 0 10px 20px rgba(241,196,15,0.3); border: 2px solid var(--yellow); animation: chip_latir 1.5s infinite; }
        
        .c-50 { background: radial-gradient(circle, #95a5a6, #7f8c8d); color: #fff;}
        .c-100 { background: radial-gradient(circle, #3498db, #2980b9); color: #fff; text-shadow: 0 0 10px #fff;}
        .c-500 { background: radial-gradient(circle, #9b59b6, #8e44ad); color: #fff;}
        .c-1000 { background: radial-gradient(circle, #e67e22, #d35400); color: #fff;}
        .c-5000 { background: radial-gradient(circle, #f1c40f, #f39c12); color: #000;}

        .action-btns { display: flex; gap: 12px; flex: 1; justify-content: flex-end;}
        .btn-action { padding: 14px 25px; border: none; border-radius: 10px; font-weight: 900; font-size: 14px; text-transform: uppercase; cursor: pointer; transition: 0.2s; letter-spacing: 1px; }
        .btn-clear { background: transparent; border: 1px solid var(--text-muted); color: var(--text-muted); } .btn-clear:hover { background: rgba(231, 76, 60, 0.05); color: var(--red); border-color: var(--red); }
        .btn-spin { background: var(--green); color: #000; flex: 1; max-width: 200px; box-shadow: 0 5px 15px rgba(46,204,113,0.3), inset 0 2px 5px rgba(255,255,255,0.5); } .btn-spin:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(46,204,113,0.5); }
        .btn-spin:disabled { background: #2c3e50; color: #7f8c8d; box-shadow: none !important; cursor: not-allowed; transform: none; border: 1px solid var(--border); }

        /* 🔥 FICHAS puestas en el tablero MEJORADAS 🔥 */
        .chip-placed { position: absolute; width: 28px; height: 28px; background: var(--blue); border: 2px dashed #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 900; pointer-events: none; z-index: 5; box-shadow: 3px 3px 6px rgba(0,0,0,0.6), inset 0 0 5px rgba(0,0,0,0.3); animation: drop_chip 0.2s ease-out;}
        .apuesta-torta { color: var(--green); grid-column: 1 / 15; text-align: center; margin-top: 10px; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 13px; font-weight: 800; background: rgba(0,0,0,0.3); padding: 8px; border-radius: 8px; border: 1px solid var(--border);}
        
        /* 🔥 ANIMACIONES 🔥 */
        @keyframes chip_latir { 0% { box-shadow: 0 0 10px rgba(241,196,15,0.2); } 50% { box-shadow: 0 0 25px rgba(241,196,15,0.5); } 100% { box-shadow: 0 0 10px rgba(241,196,15,0.2); } }
        @keyframes drop_chip { 0% { transform: scale(3) translateY(-15px) rotate(15deg); opacity: 0; } 100% { transform: scale(1) translateY(0) rotate(0); opacity: 1; } }
        @keyframes spin_wheel { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes orbit_ball { 0% { transform: rotate(0deg); } 100% { transform: rotate(-360deg); } }

        @media (max-width: 950px) { .sidebar { display: none; } .main { margin-left: 0; } .board { min-width: 600px; gap: 2px; } .b-zone { font-size: 13px; } }
    </style>
</head>
<body>

<header class="topbar">
    <a href="lobby.php" class="logo">MI <span>APUESTA</span></a> <a href="lobby.php" class="btn-volver"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3"><polyline points="15 18 9 12 15 6"></polyline></svg>Salir</a>
    <div class="saldo-box">
        <div class="s-real" title="Fichas Reales">💰 $<span id="saldoReal">0.00</span></div>
        <div class="s-bono" title="Wager">🎁 $<span id="saldoBono">0.00</span></div>
    </div>
</header>

<div class="game-area">
    <div class="wheel-container" id="containerRuleta">
        <div class="wheel-graphic"></div>
        <div class="ball-path"><div class="ball"></div></div>
        
        <div class="wheel-info">
            <div class="r-mensaje" id="mensajeRuleta">HAGAN SUS APUESTAS</div>
            <div class="r-numero n-verde" id="numeroRuleta">--</div>
        </div>
    </div>

    <div class="board-wrapper">
        <div class="board" id="pano">
            <div class="b-zone" id="n_0" onclick="apostar('n_0')">0</div>
            
            <?php
            // Generar los 36 números del paño dinámicamente
            $rojos = [1,3,5,7,9,12,14,16,18,19,21,23,25,27,30,32,34,36];
            $layout = [
                [3,6,9,12,15,18,21,24,27,30,33,36], // Fila 3
                [2,5,8,11,14,17,20,23,26,29,32,35], // Fila 2
                [1,4,7,10,13,16,19,22,25,28,31,34]  // Fila 1
            ];
            
            for($f = 0; $f < 3; $f++) {
                foreach($layout[$f] as $i => $num) {
                    $color = in_array($num, $rojos) ? 'bg-rojo' : 'bg-negro';
                    $col_grid = $i + 2; // Empieza en la columna 2 del grid (después del 0)
                    $clase_row = "row-" . (3 - $f);
                    echo "<div class='b-zone $color $clase_row' style='grid-column: $col_grid;' id='n_$num' onclick=\"apostar('n_$num')\">$num</div>";
                }
            }
            ?>
            
            <div class="b-zone" id="col_3" onclick="apostar('col_3')">2a1</div>
            <div class="b-zone" id="col_2" onclick="apostar('col_2')">2a1</div>
            <div class="b-zone" id="col_1" onclick="apostar('col_1')">2a1</div>
            
            <div class="b-zone" id="docena_1" onclick="apostar('docena_1')">1ª 12</div>
            <div class="b-zone" id="docena_2" onclick="apostar('docena_2')">2ª 12</div>
            <div class="b-zone" id="docena_3" onclick="apostar('docena_3')">3ª 12</div>
            
            <div class="b-zone" id="mitad_1" onclick="apostar('mitad_1')">1 - 18</div>
            <div class="b-zone" id="par" onclick="apostar('par')">PAR</div>
            <div class="b-zone" id="color_rojo" onclick="apostar('color_rojo')">ROJO</div>
            <div class="b-zone" id="color_negro" onclick="apostar('color_negro')">NEGRO</div>
            <div class="b-zone" id="impar" onclick="apostar('impar')">IMPAR</div>
            <div class="b-zone" id="mitad_2" onclick="apostar('mitad_2')">19 - 36</div>
            
            <div class="apuesta-torta">TOTAL APOSTADO EN MESA: $<span id="lblTotalApuesta">0</span></div>
        </div>
    </div>

    <div class="controls">
        <div class="chips-container">
            <div class="chip c-50 active" onclick="seleccionarFicha(50, this)">50</div>
            <div class="chip c-100" onclick="seleccionarFicha(100, this)">100</div>
            <div class="chip c-500" onclick="seleccionarFicha(500, this)">500</div>
            <div class="chip c-1000" onclick="seleccionarFicha(1000, this)">1k</div>
            <div class="chip c-5000" onclick="seleccionarFicha(5000, this)">5k</div>
        </div>
        
        <div class="action-btns">
            <button class="btn-action btn-clear" onclick="limpiarMesa()" id="btnLimpiar">Pasar Limpia</button>
            <button class="btn-action btn-spin" onclick="girarRuleta()" id="btnGirar">¡NO VA MÁS!</button>
        </div>
    </div>
</div>

<script>
let saldoReal = 0;
let saldoBono = 0;
let fichaActual = 50;
let apuestasActivas = {}; 
let totalApostado = 0;
let girando = false;

const rojos = [1,3,5,7,9,12,14,16,18,19,21,23,25,27,30,32,34,36];

// Traer saldo real en vivo desde el backend optimizado
function actualizarSaldo() {
    if(girando) return;
    fetch('obtener_saldo.php')
        .then(r => r.json())
        .then(data => {
            if (data.bloqueado) { window.location.href = 'logout_jugador.php'; }
            saldoReal = parseFloat(data.saldo);
            saldoBono = parseFloat(data.bono);
            document.getElementById('saldoReal').innerText = saldoReal.toFixed(2);
            document.getElementById('saldoBono').innerText = saldoBono.toFixed(2);
        });
}
actualizarSaldo();

function seleccionarFicha(valor, elemento) {
    if(girando) return;
    fichaActual = valor;
    document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
    elemento.classList.add('active');
}

function apostar(zonaId) {
    if(girando) return;
    
    // Verificamos si tiene saldo suficiente
    if((saldoReal + saldoBono) < (totalApostado + fichaActual)) {
        document.getElementById('mensajeRuleta').innerText = "❌ SALDO INSUFICIENTE";
        document.getElementById('mensajeRuleta').style.color = "var(--red)";
        setTimeout(() => { if(!girando) document.getElementById('mensajeRuleta').innerText = "HAGAN SUS APUESTAS"; document.getElementById('mensajeRuleta').style.color = "var(--text-muted)"; }, 2000);
        return;
    }

    if (!apuestasActivas[zonaId]) { apuestasActivas[zonaId] = 0; }
    apuestasActivas[zonaId] += fichaActual;
    totalApostado += fichaActual;
    
    actualizarFichaVisual(zonaId);
    document.getElementById('lblTotalApuesta').innerText = totalApostado;
}

function actualizarFichaVisual(zonaId) {
    let zona = document.getElementById(zonaId);
    let idChip = 'chip_' + zonaId;
    let chip = document.getElementById(idChip);
    
    if (!chip) {
        chip = document.createElement('div');
        chip.id = idChip;
        chip.className = 'chip-placed';
        zona.appendChild(chip);
    }
    
    // Convertir a formato corto
    let valorVista = apuestasActivas[zonaId];
    if (valorVista >= 1000) { valorVista = (valorVista / 1000) + 'k'; }
    chip.innerText = valorVista;
}

function limpiarMesa() {
    if(girando) return;
    apuestasActivas = {};
    totalApostado = 0;
    document.getElementById('lblTotalApuesta').innerText = "0";
    document.querySelectorAll('.chip-placed').forEach(c => c.remove());
    document.getElementById('numeroRuleta').innerText = "--";
    document.getElementById('numeroRuleta').className = "r-numero n-verde";
    document.getElementById('mensajeRuleta').innerText = "HAGAN SUS APUESTAS";
    document.getElementById('mensajeRuleta').style.color = "var(--text-muted)";
}

function girarRuleta() {
    if(girando) return;
    if(totalApostado < 50) { alert('Apostá al menos $50 en fieltro para girar.'); return; }
    
    girando = true;
    
    // 🔥 INICIAR ANIMACIÓN VISUAL DE GIRO PROFUNDO 🔥
    let container = document.getElementById('containerRuleta');
    let displayNum = document.getElementById('numeroRuleta');
    let displayMsg = document.getElementById('mensajeRuleta');
    
    container.classList.add('spinning');
    document.getElementById('btnGirar').disabled = true;
    document.getElementById('btnLimpiar').disabled = true;
    document.getElementById('btnGirar').innerText = "GIRANDO...";
    
    displayMsg.innerText = "¡NO VA MÁS!";
    displayMsg.style.color = "var(--yellow)";
    
    // Efecto de parpadeo del número durante el giro visual
    let parpadeoInterval = setInterval(() => {
        let rng = Math.floor(Math.random() * 37);
        displayNum.innerText = rng;
        displayNum.className = "r-numero " + (rng === 0 ? "n-verde" : (rojos.includes(rng) ? "n-rojo" : "n-negro"));
    }, 100);

    // Mandar al Cerebro Backend blindado y optimizado
    fetch('api_ruleta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ apuestas: apuestasActivas })
    })
    .then(r => r.json())
    .then(data => {
        // Tarda 3 segundos en total para un suspenso visual de lujo
        setTimeout(() => {
            clearInterval(parpadeoInterval); // Frena parpadeo
            container.classList.remove('spinning'); // Frena giro visual
            girando = false;
            
            if (data.error) {
                alert(data.error);
                limpiarMesa();
                return;
            }

            // Mostrar el número real que tiró el motor
            let numReal = data.numero;
            displayNum.innerText = numReal;
            displayNum.className = "r-numero " + (numReal === 0 ? "n-verde" : (rojos.includes(numReal) ? "n-rojo" : "n-negro"));
            
            // Actualizar saldos locales con la respuesta real de la base de datos
            document.getElementById('saldoReal').innerText = data.s_real;
            document.getElementById('saldoBono').innerText = data.s_bono;

            if (data.premio > 0) {
                displayMsg.innerText = "¡GANASTE $" + data.premio + "!";
                displayMsg.style.color = "var(--green)";
                displayMsg.style.fontSize = "14px";
            } else {
                displayMsg.innerText = "NÚMERO " + numReal + " CAYÓ";
                displayMsg.style.color = "var(--text-muted)";
                displayMsg.style.fontSize = "11px";
            }

            // Resetear para la próxima mano (oscurecemos fichas visuales viejas)
            apuestasActivas = {};
            totalApostado = 0;
            document.getElementById('lblTotalApuesta').innerText = "0";
            document.querySelectorAll('.chip-placed').forEach(c => c.style.opacity = '0.3');
            
            // Restaurar controles
            document.getElementById('btnGirar').disabled = false;
            document.getElementById('btnLimpiar').disabled = false;
            document.getElementById('btnGirar').innerText = "¡NO VA MÁS!";
        }, 3000); // 3 segundos de suspenso visual
    })
    .catch(err => {
        clearInterval(parpadeoInterval);
        container.classList.remove('spinning');
        alert("Error crítico de conexión visual con el servidor.");
        girando = false;
        document.getElementById('btnGirar').disabled = false;
        document.getElementById('btnLimpiar').disabled = false;
    });
}
</script>
</body>
</html>