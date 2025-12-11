<?php
// modulos/control_horas.php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$conn = null;
if (isset($_SESSION["servername"])) {
    $conn = new mysqli($_SESSION["servername"], $_SESSION["username"], $_SESSION["password"], $_SESSION["dbname"]);
    $conn->set_charset("utf8mb4");
} else {
    if(file_exists('../../db_connection.php')) include '../../db_connection.php';
}

if (!$conn || $conn->connect_error) {
    echo "<div class='text-red-600 p-4 bg-red-100 border border-red-400 rounded'>Error: No hay conexión a base de datos.</div>";
    exit;
}

$mes = isset($_POST['mes']) ? $_POST['mes'] : date('m');
$anio = isset($_POST['anio']) ? $_POST['anio'] : date('Y');
$id_empleado_filtro = isset($_POST['id_empleado']) ? $_POST['id_empleado'] : 'todos';

// CORRECCIÓN 1: Filtramos el desplegable para mostrar SOLO empleados con actividad en este mes/año
$sql_emp = "SELECT DISTINCT tr.ID, tr.nombre, tr.APELLIDOS 
            FROM trabajadores tr
            INNER JOIN ticados t ON tr.ID = t.idt
            WHERE tr.activo = 1 
            AND MONTH(t.fecha) = '$mes' AND YEAR(t.fecha) = '$anio'
            ORDER BY tr.nombre ASC";

$res_emp = $conn->query($sql_emp);
?>

<div class="space-y-6">
    <!-- FILTROS (Ocultos al imprimir) -->
    <div class="bg-white p-4 rounded-lg shadow border-l-4 border-primary no-print">
        <form id="form-filtros" method="POST" onsubmit="return cargarModuloConFiltros('control_horas', event);" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                <select name="mes" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                    <?php
                    $meses = ["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio",
                              "07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"];
                    foreach($meses as $k => $v) {
                        $selected = ($k == $mes) ? 'selected' : '';
                        echo "<option value='$k' $selected>$v</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                <select name="anio" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                    <?php
                    for($y = date('Y'); $y >= 2020; $y--) {
                        $selected = ($y == $anio) ? 'selected' : '';
                        echo "<option value='$y' $selected>$y</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Empleado (Con actividad)</label>
                <select name="id_empleado" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                    <option value="todos">-- Mostrar Todos --</option>
                    <?php
                    if ($res_emp && $res_emp->num_rows > 0) {
                        while($row = $res_emp->fetch_assoc()) {
                            $sel = ($row['ID'] == $id_empleado_filtro) ? 'selected' : '';
                            $nombre_mostrar = $row['nombre'] ;
                            echo "<option value='".$row['ID']."' $sel>" . htmlspecialchars($nombre_mostrar) . "</option>";
                        }
                    } else {
                        echo "<option value='' disabled>Sin actividad este mes</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="flex space-x-2">
                <button type="submit" class="bg-primary hover:bg-blue-600 text-white font-bold py-2 px-4 rounded shadow transition w-full flex justify-center items-center">
                    <span class="material-icons-outlined text-sm mr-2">filter_alt</span> Filtrar
                </button>
            </div>
        </form>
    </div>

    <?php
    $dias_en_mes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
    $datos_informe = [];
    $total_horas_reales = 0;
    $total_horas_teoricas = 0;

    // CORRECCIÓN 2: Consulta principal también filtrada por JOIN con ticados
    // Esto asegura que el informe solo itere sobre empleados que han fichado al menos una vez.
    $sql_trabajadores = "SELECT DISTINCT tr.ID, tr.NIF, tr.nombre, tr.APELLIDOS, tr.horascontrato 
                         FROM trabajadores tr
                         INNER JOIN ticados t ON tr.ID = t.idt
                         WHERE tr.activo = 1 
                         AND MONTH(t.fecha) = '$mes' AND YEAR(t.fecha) = '$anio'";

    if ($id_empleado_filtro != 'todos') {
        $sql_trabajadores .= " AND tr.ID = " . intval($id_empleado_filtro);
    }
    
    $res_trab = $conn->query($sql_trabajadores);

    // Obtener festivos
    $festivos = [];
    $sql_festivos = "SELECT fecha FROM calendario_laboral WHERE festivo = 1 AND MONTH(fecha) = '$mes' AND YEAR(fecha) = '$anio'";
    $res_fest = $conn->query($sql_festivos);
    if($res_fest){
        while($f = $res_fest->fetch_assoc()) $festivos[] = $f['fecha'];
    }

    if ($res_trab) {
        while ($emp = $res_trab->fetch_assoc()) {
            $id_emp = $emp['ID'];
            $horas_contrato = floatval($emp['horascontrato']); 

            // Sumamos horas reales agrupadas por día
            $sql_fichajes = "SELECT DATE(fecha) as dia, SUM(TIMESTAMPDIFF(MINUTE, entrada_real, salida_real)) as minutos_totales
                             FROM ticados 
                             WHERE idt = $id_emp 
                             AND MONTH(fecha) = '$mes' AND YEAR(fecha) = '$anio'
                             AND salida_real IS NOT NULL
                             GROUP BY dia";
            
            $fichajes_mes = [];
            $res_fich = $conn->query($sql_fichajes);
            if($res_fich){
                while($fic = $res_fich->fetch_assoc()){
                    $fichajes_mes[$fic['dia']] = round($fic['minutos_totales'] / 60, 2);
                }
            }

            // Bucle completo del mes: Si el empleado entró en el while anterior (tiene fichajes),
            // aquí generamos TODOS los días del mes para él.
            for ($d = 1; $d <= $dias_en_mes; $d++) {
                $fecha_actual = sprintf("%s-%s-%02d", $anio, $mes, $d);
                $timestamp = strtotime($fecha_actual);
                $dia_semana = date('N', $timestamp); // 1 (Lunes) a 7 (Domingo)
                
                $es_festivo = in_array($fecha_actual, $festivos);
                $es_laborable = ($dia_semana <= 5) && !$es_festivo;
                
                $horas_objetivo = $es_laborable ? $horas_contrato : 0;
                $horas_reales = isset($fichajes_mes[$fecha_actual]) ? $fichajes_mes[$fecha_actual] : 0;

                // Mostramos el día si hay algo relevante (trabajo, festivo o fichaje)
                if ($horas_objetivo > 0 || $horas_reales > 0 || $es_festivo) {
                    $nombre_completo = $emp['nombre'] ;
                    
                    $datos_informe[] = [
                        'fecha_sort' => $fecha_actual,
                        'fecha' => date('d/m/Y', $timestamp),
                        'fecha_iso' => $fecha_actual,
                        'id_emp' => $id_emp,
                        'nif' => $emp['NIF'],
                        'nombre_completo' => $nombre_completo,
                        'horas_reales' => $horas_reales,
                        'horas_teoricas' => $horas_objetivo,
                        'diferencia' => $horas_reales - $horas_objetivo,
                        'es_festivo' => $es_festivo
                    ];
                    
                    $total_horas_reales += $horas_reales;
                    $total_horas_teoricas += $horas_objetivo;
                }
            }
        }
    }
    
    // Ordenar por nombre y luego por fecha
    usort($datos_informe, function($a, $b) {
        $res = strcmp($a['nombre_completo'], $b['nombre_completo']);
        if ($res !== 0) return $res;
        return strcmp($a['fecha_sort'], $b['fecha_sort']);
    });
    
    $balance_global = $total_horas_reales - $total_horas_teoricas;
    $color_balance = $balance_global >= 0 ? 'text-green-600' : 'text-red-600';
    ?>

    <!-- DASHBOARD -->
    <div id="dashboard-resumen" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg p-6 shadow-sm border-l-4 border-blue-500">
            <p class="text-sm text-gray-500">Horas Reales</p>
            <p class="text-3xl font-bold text-gray-800"><?php echo number_format($total_horas_reales, 2); ?> h</p>
        </div>
        <div class="bg-white rounded-lg p-6 shadow-sm border-l-4 border-gray-400">
            <p class="text-sm text-gray-500">Horas Teóricas</p>
            <p class="text-3xl font-bold text-gray-800"><?php echo number_format($total_horas_teoricas, 2); ?> h</p>
        </div>
        <div class="bg-white rounded-lg p-6 shadow-sm border-l-4 <?php echo ($balance_global >=0)?'border-green-500':'border-red-500';?>">
            <p class="text-sm text-gray-500">Balance</p>
            <p class="text-3xl font-bold <?php echo $color_balance; ?>">
                <?php echo ($balance_global > 0 ? '+' : '') . number_format($balance_global, 2); ?> h
            </p>
        </div>
    </div>

    <!-- BOTONES -->
    <div class="flex justify-end space-x-3 mb-4 no-print">
        <button onclick="exportarExcel()" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded shadow flex items-center">
            <span class="material-icons-outlined mr-2">description</span> Excel (.xlsx)
        </button>
        <button onclick="window.print()" class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded shadow flex items-center">
            <span class="material-icons-outlined mr-2">print</span> Imprimir / PDF
        </button>
    </div>

    <!-- TABLA -->
    <div class="bg-white rounded-lg shadow overflow-hidden print-overflow-visible">
        <div class="overflow-x-auto print-overflow-visible">
            <table id="tabla-horas" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIF</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Teóricas</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Reales</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Dif.</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase no-print">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (count($datos_informe) > 0): ?>
                        <?php foreach($datos_informe as $fila): ?>
                            <?php 
                                $clase_fila = "hover:bg-gray-50 transition-colors";
                                $texto_extra = "";
                                if ($fila['es_festivo']) {
                                    $clase_fila = "bg-orange-100 hover:bg-orange-200 text-orange-900 font-medium";
                                    $texto_extra = " (Festivo)";
                                }
                            ?>
                            <tr class="<?php echo $clase_fila; ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo $fila['fecha'] . $texto_extra; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"><?php echo htmlspecialchars($fila['nombre_completo']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $fila['nif']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right"><?php echo number_format($fila['horas_teoricas'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold"><?php echo number_format($fila['horas_reales'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold <?php echo $fila['diferencia'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo number_format($fila['diferencia'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center no-print">
                                    <button onclick="verDetalleFichajes('<?php echo $fila['id_emp']; ?>', '<?php echo $fila['fecha_iso']; ?>', '<?php echo addslashes($fila['nombre_completo']); ?>')" 
                                            class="text-blue-600 hover:text-blue-900 focus:outline-none" title="Ver Detalle">
                                        <span class="material-icons-outlined">visibility</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="p-6 text-center text-gray-500">No hay actividad registrada en este mes.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    /* ESTILOS ESPECÍFICOS PARA IMPRESIÓN / PDF */
    @media print {
        @page {
            margin: 1cm;
            size: auto;
        }

        html, body, #wrapper, main, #contenido-dinamico {
            height: auto !important;
            min-height: 0 !important;
            overflow: visible !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            display: block !important;
        }

        .print-overflow-visible, .overflow-hidden, .overflow-x-auto {
            overflow: visible !important;
            box-shadow: none !important;
        }

        .no-print, header, nav, aside, #sidebar, #form-filtros {
            display: none !important;
        }

        #dashboard-resumen {
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            gap: 15px !important;
            margin-bottom: 20px !important;
            border-bottom: 2px solid #ccc;
            padding-bottom: 10px;
        }
        
        #dashboard-resumen > div {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
            flex: 1 1 0 !important;
            padding: 10px !important;
            text-align: center;
        }
        
        #dashboard-resumen p { margin: 0 !important; }
        
        #dashboard-resumen p.text-3xl { font-size: 1.5rem !important; }

        table {
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            font-size: 9pt !important;
        }

        thead { display: table-header-group !important; }
        tr { page-break-inside: avoid !important; }

        th, td {
            padding: 4px 2px !important;
            border: 1px solid #ccc !important;
            white-space: normal !important;
            overflow: visible !important;
        }

        .text-green-600 { color: green !important; -webkit-print-color-adjust: exact; }
        .text-red-600 { color: red !important; -webkit-print-color-adjust: exact; }
    }
</style>

<?php if(isset($conn)) $conn->close(); ?>