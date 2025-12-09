<?php
include '../../db_connection.php'; // Ajusta la ruta

// Filtros básicos (por defecto mes actual)
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');
$dias_en_mes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);

// 1. Obtener empleados para el select (si quieres filtrar por uno) o ver todos
$empleados = $conn->query("SELECT id, nombre, horas_contrato FROM empleados WHERE estado='activo'");

echo "<h3>Control de Horas - $mes / $anio</h3>";
echo "<table><thead><tr>
        <th>Empleado</th>
        <th>Fecha</th>
        <th>Horas Fichadas</th>
        <th>Horas Contrato</th>
        <th>Diferencia</th>
        <th>Estado</th>
      </tr></thead><tbody>";

$total_acumulado_fichado = 0;
$total_acumulado_contrato = 0;

// Lógica simplificada: Iteramos por empleado y luego por día
while($emp = $empleados->fetch_assoc()) {
    $id_emp = $emp['id'];
    $horas_contrato = $emp['horas_contrato'];

    for ($d = 1; $d <= $dias_en_mes; $d++) {
        $fecha_actual = sprintf("%s-%s-%02d", $anio, $mes, $d);
        $dia_semana = date('N', strtotime($fecha_actual)); // 1 (Lunes) a 7 (Domingo)

        // Verificar si es laborable (Simplificado: Lunes a Viernes y no festivo en DB)
        // Aquí deberías consultar tu tabla 'calendario_laboral'
        $es_laborable = ($dia_semana < 6); 
        // Ejemplo consulta calendario: $res = $conn->query("SELECT * FROM calendario WHERE fecha='$fecha_actual'"); if($res->num_rows > 0) $es_laborable = false;

        $horas_objetivo = $es_laborable ? $horas_contrato : 0;

        // Calcular horas fichadas ese día
        // Asumiendo tabla fichajes: id_empleado, fecha_entrada (datetime), fecha_salida (datetime)
        $sql_fichajes = "SELECT TIMESTAMPDIFF(MINUTE, fecha_entrada, fecha_salida) as minutos 
                         FROM fichajes 
                         WHERE id_empleado = $id_emp 
                         AND DATE(fecha_entrada) = '$fecha_actual' 
                         AND fecha_salida IS NOT NULL";
        
        $res_fich = $conn->query($sql_fichajes);
        $minutos_totales = 0;
        while($fila = $res_fich->fetch_assoc()) {
            $minutos_totales += $fila['minutos'];
        }
        $horas_reales = round($minutos_totales / 60, 2);

        // Acumuladores globales
        $total_acumulado_fichado += $horas_reales;
        $total_acumulado_contrato += $horas_objetivo;

        $diferencia = $horas_reales - $horas_objetivo;
        $color = $diferencia >= 0 ? 'green' : 'red';

        // Solo mostramos si hay actividad o es laborable para no llenar la tabla de ceros inútiles
        if($es_laborable || $horas_reales > 0) {
            echo "<tr>
                <td>{$emp['nombre']}</td>
                <td>$fecha_actual</td>
                <td>$horas_reales h</td>
                <td>$horas_objetivo h</td>
                <td style='color:$color'>$diferencia h</td>
                <td>". ($es_laborable ? 'Laborable' : 'Descanso') ."</td>
            </tr>";
        }
    }
}
echo "</tbody></table>";

echo "<div style='margin-top:20px; padding:10px; background:#e0e0e0;'>";
echo "<strong>Resumen del Periodo:</strong><br>";
echo "Total Horas Fichadas: $total_acumulado_fichado h<br>";
echo "Total Horas Según Contrato/Calendario: $total_acumulado_contrato h"; 
echo "</div>";
?>