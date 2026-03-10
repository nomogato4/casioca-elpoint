<?php
include 'auth.php'; include 'conexion.php';
$user_session = mysqli_real_escape_string($conexion, trim($_SESSION['jugador']));
$sql = "SELECT saldo, saldo_bono FROM usuarios WHERE username = '$user_session'";
$resultado = mysqli_query($conexion, $sql);
$jugador = mysqli_fetch_assoc($resultado);
$saldo_real = $jugador['saldo'] ?? 0;
$saldo_bono = $jugador['saldo_bono'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>The Wise Owl | Mi Apuesta</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Roboto+Mono:wght@700;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        :root { --bg: #030406; --panel: #0f131a; --border: #1f2530; --green: #2ecc71; --blue: #3498db; --gold: #f1c40f; --text-muted: #8b949e; --red: #e74c3c;}
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: #c9d1d9; margin: 0; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }

        .navbar-game { background: #000; border-bottom: 1px solid var(--border); padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; z-index: 100; }
        .logo-back { text-decoration: none; color: #fff; font-weight: 900; font-size: 16px; letter-spacing: -0.5px; } .logo-back span { color: var(--gold); }
        .saldos-top { display: flex; gap: 10px; align-items: center; }
        .saldo-chip { background: #161b22; border: 1px solid var(--border); padding: 6px 12px; border-radius: 8px; text-align: right; }
        .s-lbl { font-size: 9px; color: var(--text-muted); text-transform: uppercase; font-weight: 900; display: block; }
        .s-val { font-family: 'Roboto Mono', monospace; font-size: 15px; font-weight: 900; }
        
        .game-area { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; background: radial-gradient(circle at center, #111827 0%, #000 100%); }
        
        /* EL BÚHO DE FONDO */
        .character-owl { position: absolute; font-size: 150px; opacity: 0.05; z-index: 0; text-shadow: 0 0 50px var(--gold); }
        
        /* LA MÁQUINA */
        .slot-machine { background: linear-gradient(180deg, #1f2530, #0a0c10); border: 4px solid #30363d; padding: 20px; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.9), inset 0 5px 20px rgba(0,0,0,0.5); display: flex; gap: 10px; z-index: 10; position: relative; transition: 0.3s; }
        .slot-machine.win-glow { border-color: var(--gold); box-shadow: 0 0 50px rgba(241,196,15,0.5), inset 0 0 30px rgba(241,196,15,0.2); animation: shake 0.5s ease-in-out; }
        
        @keyframes shake { 0% { transform: translateX(0); } 25% { transform: translateX(-5px); } 50% { transform: translateX(5px); } 75% { transform: translateX(-5px); } 100% { transform: translateX(0); } }

        .reel { width: 100px; height: 140px; background: #000; border-radius: 12px; border: 2px solid #000; overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative; box-shadow: inset 0 10px 20px rgba(0,0,0,0.8); }
        @media (max-width: 400px) { .reel { width: 80px; height: 110px; } }
        
        .symbol { font-size: 60px; filter: drop-shadow(0 5px 5px rgba(0,0,0,0.5)); transition: 0.1s; }
        .spinning { animation: blurSpin 0.15s linear infinite; opacity: 0.5; transform: translateY(20px); }
        @keyframes blurSpin { 0% { transform: translateY(-50px); filter: blur(5px); } 100% { transform: translateY(50px); filter: blur(5px); } }

        /* CARTEL DE PREMIO */
        .win-banner { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0); background: var(--gold); color: #000; padding: 15px 30px; border-radius: 50px; font-weight: 900; font-size: 28px; text-transform: uppercase; box-shadow: 0 10px 40px rgba(241,196,15,0.8); z-index: 20; opacity: 0; transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); white-space: nowrap;}
        .win-banner.show { transform: translate(-50%, -50%) scale(1); opacity: 1; }

        /* CONTROLES MINIMALISTAS */
        .control-panel { background: #0a0c10; padding: 20px; display: flex; flex-direction: column; gap: 15px; border-top: 1px solid var(--border); }
        .bet-row { display: flex; justify-content: center; align-items: center; gap: 15px; }
        .btn-bet { background: #161b22; border: 1px solid var(--border); color: #fff; width: 45px; height: 45px; border-radius: 12px; font-size: 20px; font-weight: 900; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s;}
        .btn-bet:hover { background: var(--border); }
        .bet-display { font-family: 'Roboto Mono', monospace; font-size: 24px; font-weight: 900; color: var(--gold); width: 120px; text-align: center; background: #000; padding: 10px; border-radius: 12px; border: 1px solid var(--border); }
        
        .btn-spin { background: var(--gold); color: #000; border: none; padding: 18px; border-radius: 16px; font-weight: 900; font-size: 20px; text-transform: uppercase; cursor: pointer; letter-spacing: 2px; box-shadow: 0 10px 30px rgba(241,196,15,0.3); transition: 0.2s; position: relative; overflow: hidden; }
        .btn-spin:active { transform: scale(0.95); box-shadow: none; }
        .btn-spin:disabled { background: #30363d; color: var(--text-muted); box-shadow: none; cursor: not-allowed; }
    </style>
</head>
<body>

    <nav class="navbar-game">
        <a href="lobby.php" class="logo-back">⬅ SALIR</a>
        <div class="saldos-top">
            <div class="saldo-chip"><span class="s-lbl">FICHAS</span><span class="s-val" id="s_real" style="color:var(--green);">$<?php echo number_format($saldo_real, 2, '.', ''); ?></span></div>
            <div class="saldo-chip"><span class="s-lbl">WAGER</span><span class="s-val" id="s_bono" style="color:var(--gold);">$<?php echo number_format($saldo_bono, 2, '.', ''); ?></span></div>
        </div>
    </nav>

    <main class="game-area">
        <div class="character-owl">🦉</div>
        
        <div class="slot-machine" id="slotMachine">
            <div class="reel"><div class="symbol" id="r1">🍒</div></div>
            <div class="reel"><div class="symbol" id="r2">🦉</div></div>
            <div class="reel"><div class="symbol" id="r3">💎</div></div>
            
            <div class="win-banner" id="winBanner">¡GANASTE $0!</div>
        </div>
    </main>

    <div class="control-panel">
        <div class="bet-row">
            <button class="btn-bet" onclick="modificarApuesta(-50)">-</button>
            <div class="bet-display" id="betDisplay">$100</div>
            <button class="btn-bet" onclick="modificarApuesta(50)">+</button>
        </div>
        <button class="btn-spin" id="btnSpin" onclick="tirarSlots()">🎰 GIRAR</button>
    </div>

    <script>
        let apuesta = 100;
        let girando = false;

        function modificarApuesta(val) {
            if(girando) return;
            apuesta += val;
            if(apuesta < 10) apuesta = 10;
            if(apuesta > 10000) apuesta = 10000;
            document.getElementById('betDisplay').innerText = '$' + apuesta;
        }

        function tirarSlots() {
            if(girando) return;
            
            // Validar saldo visualmente rápido
            let sReal = parseFloat(document.getElementById('s_real').innerText.replace('$', ''));
            let sBono = parseFloat(document.getElementById('s_bono').innerText.replace('$', ''));
            if ((sReal + sBono) < apuesta) {
                alert("Saldo insuficiente para esta apuesta.");
                return;
            }

            girando = true;
            document.getElementById('btnSpin').disabled = true;
            document.getElementById('btnSpin').innerText = 'GIRANDO...';
            document.getElementById('winBanner').classList.remove('show');
            document.getElementById('slotMachine').classList.remove('win-glow');

            // Arrancar animación de giro (difuminado)
            let r1 = document.getElementById('r1');
            let r2 = document.getElementById('r2');
            let r3 = document.getElementById('r3');
            r1.classList.add('spinning'); r1.innerText = '🎰';
            r2.classList.add('spinning'); r2.innerText = '🎰';
            r3.classList.add('spinning'); r3.innerText = '🎰';

            // Llamar al cerebro (PHP)
            let fd = new FormData();
            fd.append('apuesta', apuesta);

            fetch('api_slots.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(data.error) {
                    alert(data.error);
                    resetSlots();
                    return;
                }

                // Simular que frenan uno por uno (Tensión timbera)
                setTimeout(() => { r1.classList.remove('spinning'); r1.innerText = data.simbolos[0]; }, 500);
                setTimeout(() => { r2.classList.remove('spinning'); r2.innerText = data.simbolos[1]; }, 1000);
                setTimeout(() => { 
                    r3.classList.remove('spinning'); r3.innerText = data.simbolos[2]; 
                    
                    // Actualizar saldos
                    document.getElementById('s_real').innerText = '$' + data.s_real;
                    document.getElementById('s_bono').innerText = '$' + data.s_bono;

                    // Si hay premio, festejar
                    if(data.premio > 0) {
                        document.getElementById('slotMachine').classList.add('win-glow');
                        let banner = document.getElementById('winBanner');
                        banner.innerText = '¡GANASTE $' + data.premio + '!';
                        banner.classList.add('show');
                    }

                    resetSlots();

                }, 1500);

            }).catch(err => {
                alert("Error de conexión. Intente de nuevo.");
                resetSlots();
            });
        }

        function resetSlots() {
            girando = false;
            document.getElementById('btnSpin').disabled = false;
            document.getElementById('btnSpin').innerText = '🎰 GIRAR';
        }
    </script>
</body>
</html>