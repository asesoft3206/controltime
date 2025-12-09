<?php
session_start();

// 1. Seguridad básica
if (!isset($_SESSION["servername"])) {
    header("Location: index.html");
    exit();
}

$servername = $_SESSION["servername"];
$username = $_SESSION["username"];
$password = $_SESSION["password"];
$dbname = $_SESSION["dbname"];

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Error: " . $conn->connect_error); }

// --- 2. RECUPERAR DATOS DEL ENTORNO ---

// A) Página de origen (para volver allí)
$pagina_destino = isset($_POST['origen']) ? $_POST['origen'] : 'principal.html';

// B) IP en formato IPv4
$ip_raw = $_SERVER['REMOTE_ADDR'];
if ($ip_raw === '::1') { $ip_actual = '127.0.0.1'; }
elseif (strpos($ip_raw, '::ffff:') === 0) { $ip_actual = substr($ip_raw, 7); }
else { $ip_actual = $ip_raw; }

// C) Nombre del Equipo
// Recibimos 'info_dispositivo' del formulario (JS detectó modelo de móvil)
$info_movil = isset($_POST['info_dispositivo']) ? $_POST['info_dispositivo'] : '';

// Intentamos obtener nombre de red del servidor
$hostname = gethostbyaddr($ip_raw);

if (!empty($info_movil)) {
    // Si JS detectó un móvil (ej: "ZTE Blade"), usamos eso
    $equipo_final = $info_movil;
} else {
    // Si no es móvil, usamos el nombre de red. Si falla nombre de red, usamos la IP.
    $equipo_final = ($hostname !== $ip_raw) ? $hostname : "PC-Escritorio (" . $ip_actual . ")";
}

// D) Ubicación (Recuperada de la SESIÓN del Login)
$lat = isset($_SESSION['latitud_sesion']) ? $_SESSION['latitud_sesion'] : '';
$lon = isset($_SESSION['longitud_sesion']) ? $_SESSION['longitud_sesion'] : '';
$localizacion_string = "";

if (!empty($lat) && !empty($lon)) {
    $localizacion_string = $lat . ", " . $lon;
}

// --- 3. PROCESAMIENTO ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo = $_POST["codigo"];
    $fechaActual = date("Y/m/d"); 
    $horaActual = date("H:i:s"); 
    
    // Evitar inyección SQL simple en el código
    $codigo = $conn->real_escape_string($codigo);

    // Verificar si hay turno abierto
    $sql = "SELECT t.ide, t.id, t.idt, t.trabajador 
            FROM ticados t
            WHERE t.idt IN (SELECT id FROM trabajadores tr WHERE tr.id='$codigo' OR tr.nif = '$codigo') 
            AND t.salida_real IS NULL";
            
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // --- SALIDA ---
        $row = $result->fetch_assoc();
        $id_trabajador = $row["idt"];
        $nombre_trabajador = $row["trabajador"];

        $sql_update = "UPDATE ticados SET 
                salida_real = '$horaActual',
                equipo_salida = '$equipo_final',
                localizacion_salida = '$localizacion_string',
                ip_salida = '$ip_actual' 
                WHERE idt = $id_trabajador AND salida_real IS NULL 
                ORDER BY id DESC LIMIT 1";
        
        if ($conn->query($sql_update) === TRUE) {
            $mensaje = "Salida registrada: " . $nombre_trabajador;
            $color = "#059669"; // Verde
        } else {
            $mensaje = "Error al actualizar: " . $conn->error;
            $color = "#dc2626"; // Rojo
        }

    } else {
        // --- ENTRADA ---
        $sql_trabajador = "SELECT t.ide, t.id, t.nombre, t.activo, g.descripcion
                FROM trabajadores t
                LEFT JOIN grupos_trabajadores g ON t.activo = g.id AND t.ide = g.ide 
                WHERE t.id = '$codigo' OR t.nif = '$codigo'";
        $res_trab = $conn->query($sql_trabajador);

        if ($res_trab && $res_trab->num_rows > 0) {
            $row = $res_trab->fetch_assoc();
            $id_trabajador = $row["id"];
            $nombre_trabajador = $row["nombre"];
            $grupo_trabajador = isset($row["descripcion"]) ? $row["descripcion"] : "General";
            $ide_empresa = $row["ide"];

            $sql_insert = "INSERT INTO ticados (ide, idt, trabajador, fecha, entrada_real, centro, ip_entrada, equipo_entrada, localizacion_entrada) 
                    VALUES ('$ide_empresa', '$id_trabajador', '$nombre_trabajador', '$fechaActual', '$horaActual', '$grupo_trabajador', '$ip_actual', '$equipo_final', '$localizacion_string')";
            
            if ($conn->query($sql_insert) === TRUE) {
                $mensaje = "Entrada registrada: " . $nombre_trabajador;
                $color = "#059669"; // Verde
            } else {
                $mensaje = "Error al insertar: " . $conn->error;
                $color = "#dc2626"; // Rojo
            }
        } else {
            $mensaje = "Código no encontrado.";
            $color = "#dc2626";
        }
    }

    // --- PANTALLA DE RESULTADO Y REDIRECCIÓN ---
    echo "<!DOCTYPE html><html><body style='margin:0; font-family:sans-serif; background-color: #f3f4f6; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh;'>";
    
    echo "<h1 style='color: $color; font-size: 2rem; margin-bottom: 1rem;'>$mensaje</h1>";
    
    if(!empty($localizacion_string)) {
        echo "<p style='color: #4b5563;'>📍 Ubicación: $localizacion_string</p>";
    }
    echo "<p style='color: #6b7280; font-size: 0.9rem;'>Dispositivo: $equipo_final</p>";
    
    // Script de redirección automática usando la variable $pagina_destino
    echo "<script>
            setTimeout(function(){ 
                window.location.href = '$pagina_destino'; 
            }, 2000);
          </script>";
          
    echo "</body></html>";
}
$conn->close();
?>