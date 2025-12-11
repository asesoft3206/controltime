<?php
// obtener_fichajes_dia.php
session_start();

// Configuración de errores para depuración (solo visible si no es PDF)
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Rutas posibles para TCPDF
$rutas_tcpdf = [
    '../../tcpdf/tcpdf.php',
    '../tcpdf/tcpdf.php',
    'tcpdf/tcpdf.php'
];

$tcpdf_cargado = false;
$ruta_tcpdf_final = '';
foreach ($rutas_tcpdf as $ruta) {
    if (file_exists($ruta)) {
        $ruta_tcpdf_final = $ruta;
        $tcpdf_cargado = true;
        break;
    }
}

// VALIDACIÓN DE SESIÓN Y PARÁMETROS
if (!isset($_SESSION["servername"]) || !isset($_GET['id']) || !isset($_GET['fecha'])) {
    if (isset($_GET['accion']) && $_GET['accion'] == 'pdf') {
        die("Error: Sesión no iniciada o parámetros faltantes.");
    }
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$conn = new mysqli($_SESSION["servername"], $_SESSION["username"], $_SESSION["password"], $_SESSION["dbname"]);
$conn->set_charset("utf8mb4");

$idt = intval($_GET['id']);
$fecha = $conn->real_escape_string($_GET['fecha']);
$accion = isset($_GET['accion']) ? $_GET['accion'] : 'json';

// --- CONSULTA DE DATOS ---
$sql = "SELECT t.id, t.entrada_real, t.salida_real, t.localizacion_entrada, t.localizacion_salida, tr.nombre, tr.apellidos, tr.nif 
        FROM ticados t 
        LEFT JOIN trabajadores tr ON t.idt = tr.id
        WHERE t.idt = $idt AND DATE(t.fecha) = '$fecha' 
        ORDER BY t.entrada_real ASC";

$result = $conn->query($sql);
$datos = [];
$nombre_empleado = "Empleado";
$nif_empleado = "";

while($row = $result->fetch_assoc()) {
    // Preparamos datos específicos para el JSON
    $row['loc_entrada'] = !empty($row['localizacion_entrada']) ? $row['localizacion_entrada'] : null;
    $row['loc_salida'] = !empty($row['localizacion_salida']) ? $row['localizacion_salida'] : null;
    
    $datos[] = $row;

    if (isset($row['nombre'])) {
        $nombre_empleado = $row['nombre'] . " " . $row['apellidos'];
        $nif_empleado = $row['nif'];
    }
}

// --- GENERACIÓN DE PDF ---
if ($accion == 'pdf') {
    // IMPORTANTE: Limpiar cualquier salida previa (espacios, warnings, echos)
    if (ob_get_length()) ob_end_clean();

    if (!$tcpdf_cargado) {
        die("Error crítico: No se encuentra la librería TCPDF en las rutas esperadas.");
    }
    
    require_once($ruta_tcpdf_final);

    // 1. Configuración del Documento (A4 Vertical)
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

    // Metadatos y Configuración
    $pdf->SetCreator('Sistema de Fichajes');
    $pdf->SetAuthor('Administración');
    $pdf->SetTitle('Reporte Fichajes - ' . $nombre_empleado);
    $pdf->setPrintHeader(false); // Desactivar cabecera por defecto de TCPDF
    $pdf->setPrintFooter(true);  // Mantener pie de página (números página)

    // Márgenes
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);

    // 2. Añadir Página
    $pdf->AddPage();

    // 3. Título y Datos Empleado
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'REPORTE DIARIO DE FICHAJES', 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('helvetica', '', 11);
    $fecha_fmt = date("d/m/Y", strtotime($fecha));
    
    $html_info = <<<EOD
    <table cellpadding="5">
        <tr>
            <td><strong>Empleado:</strong> $nombre_empleado</td>
            <td align="right"><strong>Fecha:</strong> $fecha_fmt</td>
        </tr>
        <tr>
            <td><strong>NIF:</strong> $nif_empleado</td>
            <td></td>
        </tr>
    </table>
    <hr>
EOD;
    $pdf->writeHTML($html_info, true, false, true, false, '');
    $pdf->Ln(2);

    // 4. Tabla de Datos
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(230, 230, 230);
    
    // Anchos: Total ~180mm. 
    $w = array(30, 75, 75); // ID, Entrada, Salida

    $pdf->Cell($w[0], 8, 'ID', 1, 0, 'C', 1);
    $pdf->Cell($w[1], 8, 'Hora Entrada', 1, 0, 'C', 1);
    $pdf->Cell($w[2], 8, 'Hora Salida', 1, 1, 'C', 1);
    $pdf->Ln();

    $pdf->SetFont('helvetica', '', 10);
    
    if (count($datos) > 0) {
        foreach ($datos as $fila) {
            $entrada = substr($fila['entrada_real'], 0, 8); 
            $salida = $fila['salida_real'] ? substr($fila['salida_real'], 0, 8) : '-';

            // Comprobamos salto de página manual si es necesario para evitar romper filas (aunque AutoPageBreak ayuda)
            if ($pdf->getY() > 270) {
                $pdf->AddPage();
                // Repetir cabecera si se desea (opcional)
            }

            $pdf->Cell($w[0], 7, "#" . $fila['id'], 1, 0, 'C');
            $pdf->Cell($w[1], 7, $entrada, 1, 0, 'C');
            $pdf->Cell($w[2], 7, $salida, 1, 0, 'C');
            $pdf->Ln();
        }
    } else {
        $pdf->Cell(array_sum($w), 10, 'No hay registros para este día.', 1, 1, 'C');
    }

    // Pie de página manual
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Documento generado automáticamente el ' . date('d/m/Y H:i:s'), 0, 1, 'R');

    // Salida
    $pdf->Output('Reporte_' . $fecha . '.pdf', 'I');
    exit;
}

// --- SALIDA JSON (WEB) ---
header('Content-Type: application/json');
echo json_encode($datos);
$conn->close();
?>