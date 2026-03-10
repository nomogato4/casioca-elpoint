<?php
session_start();
include 'conexion.php';

// Solo el Dueño (Admin) debería ver la contabilidad profunda
if (!isset($_SESSION['admin'])) {
    header("Location: auth.php");
    exit;
}

// 🔥 PARACAÍDAS 1: CALCULADORA DE CIERRE AUTOMÁTICA
$mes_actual = date('Y-m');
$q_stats = mysqli_query($conexion, "
    SELECT 
        SUM(CASE WHEN accion = 'CARGA' THEN monto ELSE 0 END) as total_cargas,
        SUM(CASE WHEN accion = 'RETIRO' THEN monto ELSE 0 END) as total_retiros
    FROM auditoria 
    WHERE DATE_FORMAT(fecha, '%Y-%m') = '$mes_actual'
");

// Si la consulta fue exitosa, calculamos. Si no, ponemos todo en 0.
if ($q_stats) {
    $stats = mysqli_fetch_assoc($q_stats);
    $cargas = $stats['total_cargas'] ?? 0;
    $retiros = $stats['total_retiros'] ?? 0;
    $error_bd = "";
} else {
    $cargas = 0;
    $retiros = 0;
    $error_bd = mysqli_error($conexion); // Capturamos el error real
}
$profit = $cargas - $retiros;

// 🔥 PARACAÍDAS 2: PAGINACIÓN Y CONSULTA
$query = mysqli_query($conexion, "SELECT * FROM auditoria ORDER BY id DESC LIMIT 500");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finanzas y Auditoría | EL POINT</title>
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

        /* HEADER */
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
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: -1px;
        }

        .logo span {
            color: var(--blue);
        }

        .btn {
            padding: 10px 15px;
            border-radius: 8px;
            border: none;
            font-weight: 900;
            cursor: pointer;
            text-transform: uppercase;
            font-size: 11px;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-blue {
            background: var(--blue);
            color: #fff;
        }

        .btn-green {
            background: rgba(0, 255, 136, 0.1);
            color: var(--green);
            border: 1px solid var(--green);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-muted);
        }

        /* CALCULADORA CARDS */
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
            position: relative;
            overflow: hidden;
        }

        .stat-title {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 900;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-family: 'Roboto Mono', monospace;
            font-size: 28px;
            font-weight: 900;
        }

        .val-green {
            color: var(--green);
            text-shadow: 0 0 15px rgba(0, 255, 136, 0.3);
        }

        .val-red {
            color: var(--red);
            text-shadow: 0 0 15px rgba(255, 51, 102, 0.3);
        }

        .val-blue {
            color: var(--blue);
            text-shadow: 0 0 15px rgba(112, 0, 255, 0.3);
        }

        /* TABLA ESTILO DASHBOARD */
        .table-container {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
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
            font-weight: 900;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.01);
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 900;
        }

        .b-carga {
            background: rgba(0, 255, 136, 0.1);
            color: var(--green);
            border: 1px solid var(--green);
        }

        .b-retiro {
            background: rgba(255, 51, 102, 0.1);
            color: var(--red);
            border: 1px solid var(--red);
        }

        /* ETIQUETAS VIP Y FUGAS */
        .row-ballena td {
            color: var(--yellow);
            background: rgba(255, 215, 0, 0.03);
        }

        .alerta-fuga {
            color: var(--red);
            font-size: 12px;
            margin-left: 5px;
            cursor: help;
        }

        .monto-mono {
            font-family: 'Roboto Mono', monospace;
        }

        /* MODAL TICKET FÍSICO */
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

        .ticket-fisico {
            background: #fff;
            color: #000;
            padding: 30px;
            width: 300px;
            font-family: 'Courier New', Courier, monospace;
            position: relative;
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.2);
        }

        .ticket-fisico::before,
        .ticket-fisico::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            height: 10px;
            background-size: 20px 20px;
        }

        .ticket-fisico::before {
            top: -5px;
            background-image: radial-gradient(circle at 10px 0, transparent 10px, #fff 11px);
        }

        .ticket-fisico::after {
            bottom: -5px;
            background-image: radial-gradient(circle at 10px 20px, transparent 10px, #fff 11px);
        }

        .t-logo {
            text-align: center;
            font-size: 24px;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .t-linea {
            border-bottom: 1px dashed #000;
            margin: 10px 0;
        }

        .t-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 5px;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="logo">EL <span>POINT</span> | FINANZAS</div>
        <div style="display:flex; gap:10px;">
            <button class="btn btn-green" onclick="exportarCSV()">📥 Bajar Excel (CSV)</button>
            <a href="dashboard.php" class="btn btn-outline">Volver al Panel</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-title">Ingresos Brutos (Cargas)</div>
            <div class="stat-value val-green">$<?php echo number_format($cargas, 2, ',', '.'); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Egresos (Retiros Pagados)</div>
            <div class="stat-value val-red">$<?php echo number_format($retiros, 2, ',', '.'); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-title">PROFIT NETO (Mes Actual)</div>
            <div class="stat-value val-blue">$<?php echo number_format($profit, 2, ',', '.'); ?></div>
        </div>
    </div>

    <div class="table-container">
        <table id="tablaAuditoria">
            <thead>
                <tr>
                    <th>ID Tx</th>
                    <th>Fecha y Hora</th>
                    <th>Cajero / Operador</th>
                    <th>Jugador</th>
                    <th>Operación</th>
                    <th>Monto</th>
                    <th>Recibo</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($query) {
                    while ($row = mysqli_fetch_assoc($query)):
                        $es_ballena = ($row['monto'] >= 50000) ? 'row-ballena' : '';
                        $badge_class = ($row['accion'] == 'CARGA') ? 'b-carga' : 'b-retiro';
                        $signo = ($row['accion'] == 'CARGA') ? '+' : '-';
                        $alerta = ($row['accion'] == 'RETIRO' && $row['monto'] >= 10000 && $row['monto'] % 5000 == 0) ? "<span class='alerta-fuga' title='Posible falta de Wager. Revisar jugadas.'>⚠️ FUGA</span>" : "";
                        ?>
                        <tr class="<?php echo $es_ballena; ?>">
                            <td style="color:var(--text-muted);">#<?php echo str_pad($row['id'], 6, "0", STR_PAD_LEFT); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?></td>
                            <td style="color:var(--blue);"><?php echo strtoupper($row['cajero'] ?? 'SISTEMA'); ?></td>
                            <td><?php echo strtoupper($row['jugador'] ?? 'DESCONOCIDO'); ?></td>
                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo $row['accion']; ?></span></td>
                            <td class="monto-mono">
                                <?php echo $signo . '$' . number_format($row['monto'], 2, ',', '.'); ?>
                                <?php echo ($es_ballena != '') ? ' 🐳' : ''; ?>
                                <?php echo $alerta; ?>
                            </td>
                            <td>
                                <button class="btn btn-outline" style="padding: 4px 8px;"
                                    onclick="abrirTicket('<?php echo $row['id']; ?>', '<?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?>', '<?php echo strtoupper($row['cajero'] ?? ''); ?>', '<?php echo strtoupper($row['jugador'] ?? ''); ?>', '<?php echo $row['accion']; ?>', '<?php echo number_format($row['monto'], 2, ',', '.'); ?>')">👁️</button>
                            </td>
                        </tr>
                        <?php
                    endwhile;
                } else {
                    // Si la tabla no existe o faltan columnas, te lo dice acá
                    echo "<tr><td colspan='7' style='text-align:center; color:var(--red); padding:30px;'>⚠️ Faltan columnas en la base de datos: <br><br> $error_bd</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="modal-overlay" id="modalTicket" onclick="if(event.target === this) this.style.display='none'">
        <div class="ticket-fisico" id="ticketPrint">
            <div class="t-logo">EL POINT</div>
            <div style="text-align:center; font-size:12px; margin-bottom:10px;">Comprobante de Operación</div>
            <div class="t-linea"></div>
            <div class="t-row"><span>FECHA:</span> <span id="t-fecha">--</span></div>
            <div class="t-row"><span>TX ID:</span> <span id="t-id">--</span></div>
            <div class="t-row"><span>CAJERO:</span> <span id="t-cajero">--</span></div>
            <div class="t-linea"></div>
            <div class="t-row"><span>JUGADOR:</span> <span id="t-jugador">--</span></div>
            <div class="t-row"><span>TIPO:</span> <span id="t-tipo">--</span></div>
            <div class="t-linea"></div>
            <div class="t-row" style="font-size:18px;"><span>TOTAL:</span> <span id="t-monto">--</span></div>
            <div class="t-linea"></div>
            <div style="text-align:center; font-size:10px; margin-top:15px;">Documento no válido como factura. Uso
                interno del sistema.</div>

            <button onclick="document.getElementById('modalTicket').style.display='none'"
                style="width:100%; background:#000; color:#fff; border:none; padding:10px; margin-top:20px; font-weight:bold; cursor:pointer; font-family:'Inter', sans-serif;">Cerrar
                Ticket</button>
        </div>
    </div>

    <script>
        // ABRIR EL RECIBO DE PAPEL
        function abrirTicket(id, fecha, cajero, jugador, tipo, monto) {
            document.getElementById('t-id').innerText = "#" + id.padStart(6, '0');
            document.getElementById('t-fecha').innerText = fecha;
            document.getElementById('t-cajero').innerText = cajero;
            document.getElementById('t-jugador').innerText = jugador;
            document.getElementById('t-tipo').innerText = tipo;
            document.getElementById('t-monto').innerText = "$" + monto;
            document.getElementById('modalTicket').style.display = 'flex';
        }

        // EXPORTAR A EXCEL (CSV) - Anti Lag, se procesa en la PC del usuario
        function exportarCSV() {
            let tabla = document.getElementById("tablaAuditoria");
            let filas = tabla.querySelectorAll("tr");
            let csv = [];

            for (let i = 0; i < filas.length; i++) {
                let fila = [], cols = filas[i].querySelectorAll("td, th");
                // No exportamos la última columna (el botón del ojito)
                for (let j = 0; j < cols.length - 1; j++) {
                    let dato = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").trim();
                    dato = dato.replace(/🐳|⚠️ FUGA|\+/g, "").trim(); // Limpiamos emojis para el Excel
                    fila.push('"' + dato + '"');
                }
                csv.push(fila.join(","));
            }

            let csvString = csv.join("\n");
            let a = document.createElement("a");
            a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvString);
            a.target = '_blank';
            a.download = 'El_Point_Finanzas_' + new Date().toISOString().slice(0, 10) + '.csv';
            a.click();
        }
    </script>

</body>

</html>